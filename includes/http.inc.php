<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage functions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

/**
 * Common functions for http requests
 */

function is_http_curl() {
    // Detect available curl module
    if (!defined('OBS_HTTP')) {
        $tmp = get_versions(); // cached versions
        if ($tmp['curl_old']) {
            define('OBS_HTTP', 'php');
        } else {
            define('OBS_HTTP', 'curl');
        }
        print_debug("HTTP '" . OBS_HTTP . "' library used.");
    }

    return OBS_HTTP === 'curl';
}

/**
 * Request an http (s) url.
 * Note. If the first runtime request exits with timeout,
 *       then will be set constant OBS_HTTP_REQUEST as FALSE
 *       and all other requests will skip with FALSE response!
 *
 * @param string      $request                  Requested URL
 * @param array       $context                  Set additional HTTP context options, see http://php.net/manual/en/context.http.php
 * @param int|boolean $rate_limit               Rate limit per day for specified domain (in url). If FALSE no limits
 *
 * @return string|boolean                       Return response content or FALSE
 * @global array      $response_headers         Response headers with keys:
 *                                              code (HTTP code status), status (HTTP status description) and all other
 * @global boolean    $request_status           TRUE if response code is 2xx or 3xx
 *
 * @global array      $config
 */
function get_http_request($request, $context = [], $rate_limit = FALSE) {
    global $config;

    $ok = !safe_empty($request);
    if (!$ok) {
        print_debug("HTTP request url is empty");
        $GLOBALS['response_headers'] = [ 'code' => 400, 'descr' => 'Bad Request' ];
    }

    if (defined('OBS_HTTP_REQUEST') && OBS_HTTP_REQUEST === FALSE) {
        print_debug("HTTP requests skipped since previous request exit with timeout");
        $ok                          = FALSE;
        $GLOBALS['response_headers'] = [ 'code' => 408, 'descr' => 'Previous Request Timeout' ];
    }

    if ($ok && !is_http_curl()) {
        if (!ini_get('allow_url_fopen')) {
            print_debug('HTTP requests disabled, since PHP config option "allow_url_fopen" set to off. Please enable this option in your PHP config.');
            $ok                          = FALSE;
            $GLOBALS['response_headers'] = ['code' => 400, 'descr' => 'HTTP Method Disabled'];
        } elseif (str_istarts($request, 'https') && !check_extension_exists('openssl')) {
            // Check if Secure requests allowed, but ssl extension not exists
            print_debug(__FUNCTION__ . '() wants to connect with https but https is not enabled on this server. Please check your PHP settings, the openssl extension must exist and be enabled.');
            logfile(__FUNCTION__ . '() wants to connect with https but https is not enabled on this server. Please check your PHP settings, the openssl extension must exist and be enabled.');
            $ok                          = FALSE;
            $GLOBALS['response_headers'] = ['code' => 400, 'descr' => 'HTTPS Method Disabled'];
        }
    }

    if ($ok && process_http_ratelimit($request, $rate_limit)) {
        // request exceeded rate limit
        $GLOBALS['response_headers'] = ['code' => 429, 'descr' => 'Too Many Requests'];
        $ok                          = FALSE;
    }

    if (OBS_DEBUG > 0) {
        $debug_request = $request;
        if (OBS_DEBUG < 2 && strpos($request, 'update.observium.org')) {
            $debug_request = preg_replace('/&stats=.+/', '&stats=***', $debug_request);
        }
        $debug_msg = PHP_EOL . 'REQUEST[%y' . $debug_request . '%n]';
    }

    if (!$ok) {
        if (OBS_DEBUG > 0) {
            print_message($debug_msg . PHP_EOL .
                'REQUEST STATUS[%rFALSE%n]' . PHP_EOL .
                'RESPONSE CODE[' . $GLOBALS['response_headers']['code'] . ' ' . $GLOBALS['response_headers']['descr'] . ']', 'console');
        }

        // Set GLOBAL var $request_status for use as validate status of last response
        $GLOBALS['request_status'] = FALSE;
        return FALSE;
    }

    if (!isset($GLOBALS['http_stats'])) {
        // Init stats
        $GLOBALS['http_stats'] = [ 'sec' => 0, 'requests' => 0,
                                   'ok' => 0, 'error' => 0, 'timeout' => 0 ];
    }
    if (is_http_curl()) {
        $response_array = process_http_curl($request, $context);
    } else {
        $response_array = process_http_php($request, $context);
    }
    $GLOBALS['http_stats']['sec'] += $response_array['request_runtime'];
    $GLOBALS['http_stats']['requests']++;

    // Request response
    $response = $response_array['response'];
    $runtime  = $response_array['request_runtime'];

    // Request end unixtime and runtime
    $GLOBALS['request_unixtime'] = $response_array['request_unixtime'];
    $GLOBALS['request_runtime']  = $response_array['request_runtime'];

    // Headers
    $head                        = $response_array['response_headers'];
    $GLOBALS['response_headers'] = $response_array['response_headers'];

    // Set GLOBAL var $request_status for use as validate status of last response
    if (isset($head['code']) && ($head['code'] < 200 || $head['code'] >= 400) && $head['code'] !== 28) {
        $GLOBALS['request_status'] = FALSE;
        $GLOBALS['http_stats']['error']++;
        bdump($response_array);
    } elseif ($response === FALSE) {
        // An error in get response
        $GLOBALS['response_headers'] = ['code' => 408, 'descr' => 'Request Timeout'];
        $GLOBALS['request_status']   = FALSE;
        $GLOBALS['http_stats']['timeout']++;
    } else {
        // Valid statuses: 2xx Success, 3xx Redirection or head code not set (ie response not correctly parsed)
        $GLOBALS['request_status'] = TRUE;
        $GLOBALS['http_stats']['ok']++;
    }

    // if (str_contains($request, 'somewrong')) {
    //   print_vars($head);
    //   var_dump($response);
    // }
    // Set OBS_HTTP_REQUEST for skip all other requests (FALSE for skip all other requests)
    if (!defined('OBS_HTTP_REQUEST')) {
        if ($response === FALSE && empty($head)) {
            // Derp, no way for get proxy headers
            if ($runtime < 1 &&
                isset($config['http_proxy']) && $config['http_proxy'] &&
                !(isset($config['proxy_user']) || isset($config['proxy_password']))) {
                $GLOBALS['response_headers'] = ['code' => 407, 'descr' => 'Proxy Authentication Required'];
            } else {
                $GLOBALS['response_headers'] = ['code' => 408, 'descr' => 'Request Timeout'];
            }
            $GLOBALS['request_status'] = FALSE;

            // Validate host from request and check if it timeout request
            if (OBS_PROCESS_NAME === 'poller' && gethostbyname6(parse_url($request, PHP_URL_HOST))) {
                // Timeout error, only if not received response headers
                define('OBS_HTTP_REQUEST', FALSE);
                print_debug(__FUNCTION__ . '() exit with timeout. Access to outside localnet is blocked by firewall or network problems. Check proxy settings.');
                logfile(__FUNCTION__ . '() exit with timeout. Access to outside localnet is blocked by firewall or network problems. Check proxy settings.');
            }
        } else {
            define('OBS_HTTP_REQUEST', TRUE);
        }
    }
    // FIXME. what if first request fine, but second broken?
    //else if ($response === FALSE)
    //{
    //  if (function_exists('runkit_constant_redefine')) { runkit_constant_redefine('OBS_HTTP_REQUEST', FALSE); }
    //}

    if (defined('OBS_DEBUG') && OBS_DEBUG) {
        // Hide extended stats in normal debug level = 1
        if (OBS_DEBUG < 2 && strpos($request, 'update.observium.org')) {
            $request = preg_replace('/&stats=.+/', '&stats=***', $request);
        }
        // Show debug info
        print_message($debug_msg . PHP_EOL .
            'REQUEST STATUS[' . ($GLOBALS['request_status'] ? '%gTRUE' : '%rFALSE') . '%n]' . PHP_EOL .
            'REQUEST RUNTIME[' . ($runtime > 3 ? '%r' : '%g') . round($runtime, 4) . 's%n]' . PHP_EOL .
            'RESPONSE CODE[' . $GLOBALS['response_headers']['code'] . ' ' . $GLOBALS['response_headers']['descr'] . ']', 'console');
        if (OBS_DEBUG > 1) {
            echo "RESPONSE[\n" . $response . "\n]";
            print_vars($http_response_header);
            //print_vars($opts);
        }
    }

    return $response;
}

function process_http_ratelimit($request, $rate_limit = FALSE) {
    if ($rate_limit && is_numeric($rate_limit) && $rate_limit >= 0) {
        // Check limit rates to this domain (per/day)
        if (preg_match('/^https?:\/\/([\w\.]+[\w\-\.]*(:\d+)?)/i', $request, $matches)) {
            $date    = format_unixtime(get_time(), 'Y-m-d');
            $domain  = $matches[0]; // base domain (with http(s)): https://test-me.com/ -> https://test-me.com
            $rate_db = safe_json_decode(get_obs_attrib('http_rate_' . $domain));
            //print_vars($date); print_vars($rate_db);
            if (is_array($rate_db) && isset($rate_db[$date])) {
                $rate_count = $rate_db[$date];
            } else {
                $rate_count = 0;
            }
            $rate_count++;
            set_obs_attrib('http_rate_' . $domain, safe_json_encode([$date => $rate_count]));
            if ($rate_count > $rate_limit) {
                print_debug("HTTP requests skipped because the rate limit $rate_limit/day for domain '$domain' is exceeded (count: $rate_count)");
                //$GLOBALS['response_headers'] = [ 'code' => 429, 'descr' => 'Too Many Requests' ];
                //$ok = FALSE;
                return TRUE;
            }
            if (OBS_DEBUG > 1) {
                print_debug("HTTP rate count for domain '$domain': $rate_count ($rate_limit/day)");
            }
        }
    }

    return FALSE;
}

function print_debug_curl_cmd($request, $opts_http) {
    global $config;

    // DEBUG, generate curl cmd for testing:
    if (!defined('OBS_DEBUG') || !OBS_DEBUG) {
        return;
    }

    $curl_cmd = 'curl';
    if (OBS_DEBUG > 1) {
        // Show response headers
        $curl_cmd .= ' -i';
    }
    if (isset($config['http_ip_version'])) {
        $curl_cmd .= str_contains($config['http_ip_version'], '6') ? ' -6' : ' -4';
    }
    if (isset($opts_http['timeout'])) {
        $curl_cmd .= ' --connect-timeout ' . $opts_http['timeout'];
    }
    if (isset($opts_http['method'])) {
        $curl_cmd .= ' -X ' . $opts_http['method'];
    }
    if (isset($opts_http['header'])) {
        foreach (explode("\r\n", $opts_http['header']) as $curl_header) {
            if (safe_empty($curl_header)) {
                continue;
            }
            $curl_cmd .= ' -H \'' . $curl_header . '\'';
        }
    }
    if (isset($opts_http['content'])) {
        $curl_cmd .= ' -d \'' . $opts_http['content'] . '\'';
    }
    // Proxy
    // -x, --proxy <[protocol://][user:password@]proxyhost[:port]>
    // -U, --proxy-user <user:password>
    if (isset($config['http_proxy']) && $config['http_proxy']) {
        $http_proxy = $config['http_proxy'];

        // Basic proxy auth
        if (isset($config['proxy_user'], $config['proxy_password']) && $config['proxy_user']) {
            $http_proxy = $config['proxy_user'] . ':' . $config['proxy_password'] . '@' . $http_proxy;
        }
        $curl_cmd .= ' -x ' . $http_proxy;
    }
    print_cli("HTTP CURL cmd:\n$curl_cmd $request", FALSE);
}

/**
 * Http query by native php function, compat when curl not installed.
 *
 * @param string $request
 * @param array $context
 * @return array
 */
function process_http_php($request, $context = []) {
    global $config;

    // Add common http context
    $opts = ['http' => generate_http_context_defaults($context)];

    // Force IPv4 or IPv6
    if (isset($config['http_ip_version'])) {
        // Bind to IPv4 -> 0:0
        // Bind to IPv6 -> [::]:0
        $bindto         = str_contains($config['http_ip_version'], '6') ? '[::]:0' : '0:0';
        $opts['socket'] = ['bindto' => $bindto];
    }

    // HTTPS
    // if ($parse_url = parse_url($request))
    // {
    //   if ($parse_url['scheme'] == 'https')
    //   {
    //     $opts['ssl'] = [ 'SNI_enabled' => TRUE, 'SNI_server_name' => $parse_url['host'] ];
    //   }
    // }

    // DEBUG, generate curl cmd for testing:
    print_debug_curl_cmd($request, $opts['http']);

    // Process http request and calculate runtime
    $start    = utime();
    $context  = stream_context_create($opts);
    $response = file_get_contents($request, FALSE, $context);
    $end      = utime();
    $runtime  = $end - $start;

    // Request end unixtime and runtime
    $GLOBALS['request_unixtime'] = $end;
    $GLOBALS['request_runtime']  = $runtime;

    // Parse response headers
    // Note: $http_response_header - see: http://php.net/manual/en/reserved.variables.httpresponseheader.php
    $head = [];
    foreach ($http_response_header as $k => $v) {
        $t = explode(':', $v, 2);
        if (isset($t[1])) {
            // Date: Sat, 12 Apr 2008 17:30:38 GMT
            $head[trim($t[0])] = trim($t[1]);
        } elseif (preg_match("!HTTP/([\d\.]+)\s+(\d+)(.*)!", $v, $matches)) {
            // HTTP/1.1 200 OK
            $head['http']  = $matches[1];
            $head['code']  = (int)$matches[2];
            $head['descr'] = trim($matches[3]);
        } else {
            $head[] = $v;
        }
    }

    return [
        'response'         => $response,
        'request_unixtime' => $end,
        'request_runtime'  => $runtime,
        'response_headers' => $head
    ];
}

function process_http_curl($request, $context = []) {
    global $config;

    $c = curl_init($request);

    $options = [
        CURLOPT_RETURNTRANSFER        => TRUE,        // return response instead print
        CURLOPT_HTTP_CONTENT_DECODING => FALSE, // return raw response
        //CURLOPT_HEADER => TRUE,
        CURLINFO_HEADER_OUT           => TRUE,           // request headers
        CURLOPT_USERAGENT             => OBSERVIUM_PRODUCT . '/' . OBSERVIUM_VERSION,
        //CURLOPT_CONNECTTIMEOUT => 0,
        //CURLOPT_TIMEOUT => 5,
        //CURLOPT_HTTPHEADER => $header,
        //CURLOPT_CUSTOMREQUEST => 'POST',
        //CURLOPT_POSTFIELDS => $fields,
        //CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        //CURLOPT_ENCODING => '',
        //CURLOPT_DNS_USE_GLOBAL_CACHE => false,
        CURLOPT_SSL_VERIFYHOST        => (bool)$config['http_ssl_verify'], // Disabled SSL Host check
        CURLOPT_SSL_VERIFYPEER        => (bool)$config['http_ssl_verify'], // Disabled SSL Cert check
    ];
    // Append options based on defaults from generate_http_context_defaults($context)
    $options[CURLOPT_TIMEOUT] = isset($context['timeout']) ? (int)$context['timeout'] : 15;

    // Proxy
    if (isset($config['http_proxy']) && $config['http_proxy']) {
        $options[CURLOPT_PROXY] = $config['http_proxy'];
        //$options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP; // CURLPROXY_SOCKS4, CURLPROXY_SOCKS5, CURLPROXY_SOCKS4A, CURLPROXY_SOCKS5_HOSTNAME
    }

    // Basic proxy auth
    if (isset($config['proxy_user'], $config['proxy_password']) && $config['proxy_user']) {
        $options[CURLOPT_PROXYAUTH]    = CURLAUTH_BASIC; // CURLAUTH_NTLM
        $options[CURLOPT_PROXYUSERPWD] = $config['proxy_user'] . ':' . $config['proxy_password'];
    }

    // Headers
    if (isset($context['header'])) {
        $options[CURLOPT_HTTPHEADER] = explode("\r\n", trim($context['header']));
    }

    if (!safe_empty($context['content'])) {
        $options[CURLOPT_POSTFIELDS] = $context['content'];
        print_debug_vars($context['content']);
    }
    if ($context['method']) {
        switch ($context['method']) {
            case 'GET':
                $options[CURLOPT_HTTPGET] = TRUE;
                break;
            case 'POST':
                $options[CURLOPT_POST] = TRUE;
                $options[CURLOPT_POSTREDIR] = 1; // follow 301 redir
                break;
            case 'PUT':
                $options[CURLOPT_PUT] = TRUE;
                $options[CURLOPT_POSTREDIR] = 1; // follow 301 redir
                break;
            default:
                $options[CURLOPT_CUSTOMREQUEST] = $context['method'];
        }
    }

    // Follow up to 3 redirects
    $options[CURLOPT_MAXREDIRS]      = 3;
    $options[CURLOPT_FOLLOWLOCATION] = TRUE;

    // Force IPv4 or IPv6
    if (isset($config['http_ip_version'])) {
        $options[CURLOPT_IPRESOLVE] = str_contains($config['http_ip_version'], '6') ? CURL_IPRESOLVE_V6 : CURL_IPRESOLVE_V4;
    }

    if (OBS_DEBUG) {
        $options[CURLOPT_VERBOSE] = TRUE;

        // DEBUG, generate curl cmd for testing:
        //print_debug_curl_cmd($request, $context);
    }

    curl_setopt_array($c, $options);
    $response = curl_exec($c);
    $end      = utime();
    //r($response);

    if (!curl_errno($c)) {
        // Normal response

        $http_info = curl_getinfo($c);
        print_debug_vars($http_info);
        //r($http_info);

        $head = [
            'http' => $http_info['http_version'],
            'code' => $http_info['http_code'],
            //'descr' => curl_error($c)
        ];
        if (preg_match("!HTTP/([\d\.]+)!", $http_info['request_header'], $matches)) {
            $head['http'] = $matches[1];
        }
        // HTTP code descriptions
        switch ($http_info['http_code']) {
            case 100:
                $head['descr'] = 'Continue';
                break;
            case 101:
                $head['descr'] = 'Switching Protocols';
                break;
            case 200:
                $head['descr'] = 'OK';
                break;
            case 201:
                $head['descr'] = 'Created';
                break;
            case 202:
                $head['descr'] = 'Accepted';
                break;
            case 203:
                $head['descr'] = 'Non-Authoritative Information';
                break;
            case 204:
                $head['descr'] = 'No Content';
                break;
            case 205:
                $head['descr'] = 'Reset Content';
                break;
            case 206:
                $head['descr'] = 'Partial Content';
                break;
            case 300:
                $head['descr'] = 'Multiple Choices';
                break;
            case 301:
                $head['descr'] = 'Moved Permanently';
                break;
            case 302:
                $head['descr'] = 'Moved Temporarily';
                break;
            case 303:
                $head['descr'] = 'See Other';
                break;
            case 304:
                $head['descr'] = 'Not Modified';
                break;
            case 305:
                $head['descr'] = 'Use Proxy';
                break;
            case 400:
                $head['descr'] = 'Bad Request';
                break;
            case 401:
                $head['descr'] = 'Unauthorized';
                break;
            case 402:
                $head['descr'] = 'Payment Required';
                break;
            case 403:
                $head['descr'] = 'Forbidden';
                break;
            case 404:
                $head['descr'] = 'Not Found';
                break;
            case 405:
                $head['descr'] = 'Method Not Allowed';
                break;
            case 406:
                $head['descr'] = 'Not Acceptable';
                break;
            case 407:
                $head['descr'] = 'Proxy Authentication Required';
                break;
            case 408:
                $head['descr'] = 'Request Time-out';
                break;
            case 409:
                $head['descr'] = 'Conflict';
                break;
            case 410:
                $head['descr'] = 'Gone';
                break;
            case 411:
                $head['descr'] = 'Length Required';
                break;
            case 412:
                $head['descr'] = 'Precondition Failed';
                break;
            case 413:
                $head['descr'] = 'Request Entity Too Large';
                break;
            case 414:
                $head['descr'] = 'Request-URI Too Large';
                break;
            case 415:
                $head['descr'] = 'Unsupported Media Type';
                break;
            case 500:
                $head['descr'] = 'Internal Server Error';
                break;
            case 501:
                $head['descr'] = 'Not Implemented';
                break;
            case 502:
                $head['descr'] = 'Bad Gateway';
                break;
            case 503:
                $head['descr'] = 'Service Unavailable';
                break;
            case 504:
                $head['descr'] = 'Gateway Time-out';
                break;
            case 505:
                $head['descr'] = 'HTTP Version not supported';
                break;
            default:
                $head['descr'] = 'Unknown HTTP status';
        }
        $runtime = $http_info['total_time'];
    } else {
        $head    = [
            //'http' => $http_info['http_version'],
            'code'  => curl_errno($c),
            'descr' => curl_error($c)
        ];
        $runtime = curl_getinfo($c, CURLINFO_TOTAL_TIME);
        print_debug_vars(curl_getinfo($c));
    }

    curl_close($c);

    return [
        'response'         => $response,
        'request_unixtime' => $end,
        'request_runtime'  => $runtime,
        'response_headers' => $head
    ];
}

/**
 * Process HTTP request by definition array and process it for valid status.
 * Used definition params in response key.
 *
 * @param string|array $def      Definition array or alert transport key (see transports definitions)
 * @param string       $response Response from get_http_request()
 *
 * @return boolean          Return TRUE if request processed with valid HTTP code (2xx, 3xx) and API response return valid param
 */
function test_http_request($def, $response) {
    $response = trim($response);

    if (is_string($def)) {
        // Get transport definition for responses
        $def = $GLOBALS['config']['transports'][$def]['notification'];
    }

    // Response is array (or xml)?
    $is_response_array = strtolower($def['response_format']) === 'json';

    // Set status by response status
    $success = get_http_last_status();

    // If response return valid code and content, additional parse for specific defined tests
    if ($success) {
        // Decode if request OK
        if ($is_response_array) {
            $response = safe_json_decode($response);
        }
        // else additional formats?

        // Check if call succeeded
        if (isset($def['response_test'])) {
            // Convert single test condition to multi-level condition
            if (isset($def['response_test']['operator'])) {
                $def['response_test'] = [$def['response_test']];
            }

            // Compare all definition fields with response,
            // if response param not equals to expected, set not success
            // multilevel keys should written with '->' separator, ie: $a[key][some][0] - key->some->0
            foreach ($def['response_test'] as $test) {
                if ($is_response_array) {
                    $field = array_get_nested($response, $test['field']);
                } else {
                    // RAW response
                    $field = $response;
                }
                if (test_condition($field, $test['operator'], $test['value']) === FALSE) {
                    print_debug("Response [" . $field . "] not valid: [" . $test['field'] . "] " . $test['operator'] . " [" . implode(', ', (array)$test['value']) . "]");

                    $success = FALSE;
                    break;
                } else {
                    print_debug("Response [" . $field . "] valid: [" . $test['field'] . "] " . $test['operator'] . " [" . implode(', ', (array)$test['value']) . "]");
                }
            }
        }
    } elseif ($is_response_array && isset($def['response_fields']['message'], $def['response_fields']['status'])) {
        // Decode response for useful error reports also for bad statuses
        $response = safe_json_decode($response);
    }

    if (!$success) {
        if (isset($def['response_fields']['message'], $def['response_fields']['status']) && is_array($response)) {
            echo PHP_EOL;
            if (isset($def['response_fields']['status'])) {
                if ($def['response_fields']['status'] === 'raw') {
                    $status = get_http_last_code();
                } else {
                    $status = array_get_nested($response, $def['response_fields']['status']);
                }
                if (OBS_DEBUG) {
                    print_message("%WRESPONSE STATUS%n[%r" . $status . "%n]", 'console');
                }
            }
            $msg = array_get_nested($response, $def['response_fields']['message']);
            if (isset($def['response_fields']['info']) &&
                $info = array_get_nested($response, $def['response_fields']['info'])) {
                $msg .= " ($info)";
            }
            if (OBS_DEBUG) {
                print_message("%WRESPONSE ERROR%n[%y" . $msg . "%n]\n", 'console');
            }
            $GLOBALS['last_message'] = $msg;
        } elseif (is_string($response) && $response && !get_http_last_status()) {
            if (OBS_DEBUG) {
                echo PHP_EOL;
                print_message("%WRESPONSE STATUS%n[%r" . get_http_last_code() . "%n]", 'console');
                print_message("%WRESPONSE ERROR%n[%y" . $response . "%n]\n", 'console');
            }
            $GLOBALS['last_message'] = $response;
        }
    }
    print_debug_vars($response, 1);

    return $success;
}

function process_http_request($def, $url, $options, &$response = NULL, $request_retry = 1) {

    if (is_string($def)) {
        // Get transport definition for responses
        $def = $GLOBALS['config']['transports'][$def]['notification'];
    }

    // Rate limit
    if (isset($def['ratelimit_key']) && !safe_empty($def['key'])) {
        // Ratelimit if used an api key
        $ratelimit = $def['ratelimit_key'];
    } elseif (isset($def['ratelimit'])) {
        $ratelimit = $def['ratelimit'];
    } else {
        $ratelimit = FALSE;
    }

    // Retry count (default 1, max 10). See discord definition
    if (isset($def['request_retry']) && is_intnum($def['request_retry']) &&
        $def['request_retry'] > 1 && $def['request_retry'] <= 10) {
        $request_retry = $def['request_retry'];
    }
    $request_sleep = $request_retry > 1 ? 1 : 0; // sleep for 1s when retry count more than 1

    $request_status = FALSE;
    // Send out API call and parse response
    for ($retry = 1; $retry <= (int)$request_retry; $retry++) {
        print_debug("Request [$url] #$retry:");
        $response = get_http_request($url, $options, $ratelimit);
        if ($request_status = test_http_request($def, $response)) {
            // stop for on success
            return $request_status;
        }
        // wait little time
        sleep($request_sleep);
    }

    return $request_status;
}

/**
 * Return HTTP return code for last request by get_http_request()
 *
 * @return integer HTTP code
 */
function get_http_last_code() {
    return $GLOBALS['response_headers']['code'];
}

/**
 * Return HTTP return code for last request by get_http_request()
 *
 * @return boolean HTTP status TRUE if response code 2xx or 3xx
 */
function get_http_last_status() {
    return $GLOBALS['request_status'];
}

/**
 * Generate HTTP specific context with some defaults for proxy, timeout, user-agent.
 * Used in get_http_request().
 *
 * @param array $context HTTP specified context, see http://php.net/manual/ru/function.stream-context-create.php
 *
 * @return array HTTP context array
 */
function generate_http_context_defaults($context = []) {
    global $config;

    if (!is_array($context)) {
        $context = [];
    } // Fix context if not array passed

    // Defaults
    if (!isset($context['timeout'])) {
        $context['timeout'] = '15';
    }
    // HTTP/1.1
    $context['protocol_version'] = 1.1;
    // get the entire body of the response in case of error (HTTP/1.1 400, for example)
    if (OBS_DEBUG) {
        $context['ignore_errors'] = TRUE;
    }

    // User agent (required for some type of queries, ie geocoding)
    if (!isset($context['header'])) {
        $context['header'] = ''; // Avoid 'undefined index' when concatting below
    }
    $context['header'] .= 'User-Agent: ' . OBSERVIUM_PRODUCT . '/' . OBSERVIUM_VERSION . "\r\n";

    if (isset($config['http_proxy']) && $config['http_proxy']) {
        $context['proxy']           = 'tcp://' . $config['http_proxy'];
        $context['request_fulluri'] = !isset($config['proxy_fulluri']) || (bool)$config['proxy_fulluri'];
    }

    // Basic proxy auth
    if (isset($config['proxy_user'], $config['proxy_password']) && $config['proxy_user']) {
        $auth              = base64_encode($config['proxy_user'] . ':' . $config['proxy_password']);
        $context['header'] .= 'Proxy-Authorization: Basic ' . $auth . "\r\n";
    }

    print_debug_vars($context);

    return $context;
}

function generate_http_data($def, $tags = [], &$params = []) {

    if (isset($def['request_params_key'])) {
        // Key based link to request params, see google-chat notification
        $key = 'request_params_' . strtolower(array_tag_replace($tags, $def['request_params_key']));
        $request_params = $def[$key] ?? $def['request_params'];
    } else {
        // Common default request_params
        $request_params = $def['request_params'];
    }

    // Generate request params
    foreach ((array)$request_params as $param => $entry) {
        // Param based on expression (only true/false)
        // See: pushover notification def
        if (str_contains($param, '?')) {
            [ $param, $param_if ] = explode('?', $param, 2);
            //print_vars($tags);
            //print_vars($params);
            if (!isset($tags[$param_if]) || !get_var_true($tags[$param_if])) {
                print_debug("Request param '$param' skipped, because other param '$param_if' false or unset.");
                continue;
            }
        }

        // Try to find all keys in header like %bot_hash% matched with same key in $endpoint array
        if (is_array($entry)) {
            // i.e., teams and pagerduty
            $params[$param] = array_merge((array)$params[$param], array_tag_replace($tags, $entry));
        } elseif (!isset($params[$param]) || $params[$param] === '') {
            $params[$param] = array_tag_replace($tags, $entry);
        }
        // Clean empty params
        if (safe_empty($params[$param])) {
            unset($params[$param]);
        }
    }

    if (($def['method'] === 'POST' || $def['method'] === 'PUT') &&
        strtolower($def['request_format']) === 'json') {
        // Encode params as json string
        return safe_json_encode($params);
    }

    // Encode params as url encoded string
    return http_build_query($params);
}

/**
 * Generate HTTP context based on passed params, tags and definition.
 * This context will be used in get_http_request()
 *
 * @param string|array $def    Definition array or alert transport key (see transports definitions)
 * @param array        $tags   (optional) Contact array and other tags
 * @param array        $params (optional) Array of requested params with key => value entries (used with request method POST)
 *
 * @return array               HTTP Context which can used in get_http_request()
 * @global array       $config
 */
function generate_http_context($def, $tags = [], $params = []) {
    global $config;

    if (is_string($def)) {
        // Get transport definition for requests
        $def = $config['transports'][$def]['notification'];
    }

    $context = []; // Init

    // Request method POST/GET
    if ($def['method']) {
        $def['method']     = strtoupper($def['method']);
        $context['method'] = $def['method'];
    }
    // Request timeout
    if (is_intnum($def['timeout']) || $def['timeout'] >= 1 || $def['timeout'] <= 300) {
        $context['timeout'] = $def['timeout'];
    }

    // Content and headers
    $header = "Connection: close\r\n";

    // Add encode $params for POST request inside http headers
    if ($def['method'] === 'POST' || $def['method'] === 'PUT') {

        $data = generate_http_data($def, $tags, $params);
        if (strtolower($def['request_format']) === 'json') {
            // Encode params as json string
            //$data   = safe_json_encode($params);
            $header .= "Content-Type: application/json; charset=utf-8\r\n";
        } else {
            // Encode params as url encoded string
            //$data = http_build_query($params);
            // https://stackoverflow.com/questions/4007969/application-x-www-form-urlencoded-or-multipart-form-data
            //$header .= "Content-Type: multipart/form-data\r\n";
            $header .= "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n";
        }
        $header .= "Content-Length: " . strlen($data) . "\r\n";

        // Encoded content data
        $context['content'] = $data;
    }

    // Basic auth
    if (isset($def['request_user'])) {
        $basic_auth = $def['request_user'];
        if (isset($def['request_password'])) {
            $basic_auth .= ':' . $def['request_password'];
        }
        $basic_auth = array_tag_replace($tags, $basic_auth);

        $header .= 'Authorization: Basic ' . base64_encode($basic_auth) . "\r\n";
    }

    // Additional headers with contact params
    foreach ($def['request_header'] as $key => $entries) {
        [ $head, $tag ] = explode('?', $key, 2);

        if ($tag && (!isset($tags[$tag]) || !$tags[$tag])) {
            // see webhook json transport
            continue;
        }

        // Try to find all keys in header like %bot_hash% matched with same key in $endpoint array
        foreach ((array)$entries as $entry) {
            // multiple same headers can be exist at same time.
            $header .= $head . ': ' . array_tag_replace($tags, $entry) . "\r\n";
        }
    }

    $context['header'] = $header;

    return $context;
}

/**
 * Generate URL based on passed params, tags and definition.
 * This context will be used in get_http_request() or process_http_request()
 *
 * @param string|array $def    Definition array or alert transport key (see transports definitions)
 * @param array        $tags   (optional) Contact array, used only if transport required additional headers (ie hipchat)
 * @param array        $params (optional) Array of requested params with key => value entries (used with request method GET)
 * @param string       $url_key Definition key which used for get url, default is $def['url']
 *
 * @return string               URL which can used in get_http_request() or process_http_request()
 * @global array       $config
 */
function generate_http_url($def, $tags = [], $params = [], $url_key = 'url') {
    global $config;

    if (is_string($def)) {
        // Get definition for transport API
        $def = $config['transports'][$def]['notification'];
    }

    $url = ''; // Init

    // Append (if set $def['url']) or set hardcoded url for transport
    if (isset($def[$url_key])) {
        // Try to find all keys in URL like %bot_hash% and %%url_encoded%% matched with the same key in $endpoint array
        $url .= array_tag_replace_encode($tags, $def[$url_key]);
    }

    // Add GET params to url
    if (($def['method'] === 'GET' || $def['method'] === 'DELETE')) {

        $data = generate_http_data($def, $tags, $params);
        if (safe_count($params)) {
            if (str_contains($url, '?')) {
                // Append additional params to url string
                $url .= '&' . $data;
            } else {
                // Add get params as first time
                $url .= '?' . $data;
            }
        }
    }

    //print_debug_vars($def);
    //print_debug_vars($params);
    //print_debug_vars($data);
    print_debug_vars($url);

    return $url;
}

function get_http_def($http_def, $tags) {
    global $config;

    if (!is_array($http_def)) {
        $http_def = $config['http_api'][$http_def];
    }

    // Generate context/options with encoded data
    $options = generate_http_context($http_def, $tags);

    // API URL
    $url = generate_http_url($http_def, $tags);

    // Request
    if (process_http_request($http_def, $url, $options, $response)) {
        return $http_def['response_format'] === 'json' ? safe_json_decode($response) : $response;
    }

    return FALSE;
}

// EOF
