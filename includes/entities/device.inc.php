<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

/**
 * Generate device array.
 *
 * @param string $hostname
 * @param string $snmp_community
 * @param string $snmp_version
 * @param int    $snmp_port
 * @param string $snmp_transport
 * @param array  $options
 *
 * @return array
 */
function build_initial_device_array($hostname, $snmp_community, $snmp_version, $snmp_port = 161, $snmp_transport = 'udp', $options = [])
{
    $device                   = [];
    $device['hostname']       = $hostname;
    $device['snmp_port']      = $snmp_port;
    $device['snmp_transport'] = $snmp_transport;
    $device['snmp_version']   = $snmp_version;

    if ($snmp_version === "v2c" || $snmp_version === "v1") {
        $device['snmp_community'] = $snmp_community;
    } elseif ($snmp_version === "v3") {
        $device['snmp_authlevel']  = $options['authlevel'];
        $device['snmp_authname']   = $options['authname'];
        $device['snmp_authpass']   = $options['authpass'];
        $device['snmp_authalgo']   = $options['authalgo'];
        $device['snmp_cryptopass'] = $options['cryptopass'];
        $device['snmp_cryptoalgo'] = $options['cryptoalgo'];
    }

    foreach (['snmp_maxrep', 'snmp_timeout', 'snmp_retries'] as $param) {
        if (isset($options[$param]) && is_numeric($options[$param])) {
            $device[$param] = (int)$options[$param];
        }
    }

    // Append SNMPable OIDs if passed
    if (isset($options['snmpable']) && strlen($options['snmpable'])) {
        $device['snmpable'] = $options['snmpable'];
    }

    // Append SNMP context if passed
    if (isset($options['snmp_context']) && strlen($options['snmp_context'])) {
        $device['snmp_context'] = $options['snmp_context'];
    }

    print_debug_vars($device);

    return $device;
}

function get_device_snmp_argv($add, &$snmp_options) {
    global $config;

    if (!$add) {
        return [];
    }

    // SNMPv1/2c:                    add_device.php <hostname> [community] [v1|v2c] [port] [udp|udp6|tcp|tcp6] [context]
    // SNMPv3   :         Defaults : add_device.php <hostname> any v3 [user] [port] [udp|udp6|tcp|tcp6] [context]
    //            No Auth, No Priv : add_device.php <hostname> nanp v3 [user] [port] [udp|udp6|tcp|tcp6] [context]
    //               Auth, No Priv : add_device.php <hostname> anp v3 <user> <password> [md5|sha|sha-224|sha-256|sha-384|sha-512] [port] [udp|udp6|tcp|tcp6] [context]
    //               Auth,    Priv : add_device.php <hostname> ap v3 <user> <password> <enckey> [md5|sha|sha-224|sha-256|sha-384|sha-512] [des|aes|aes-192|aes-192-c|aes-256|aes-256-c] [port] [udp|udp6|tcp|tcp6] [context]

    $hostname       = strtolower($add[0]);
    $snmp_community = $add[1];
    $snmp_version   = strtolower($add[2]);

    $snmp_port = 161;
    if (str_contains($hostname, ':') && get_ip_version($hostname) !== 6) {
        // Allow pass common hostname:port
        [ $host_tmp, $port_tmp ] = explode(':', $hostname, 2);
        if (is_valid_param($port_tmp, 'port')) {
            $hostname  = $host_tmp;
            $snmp_port = $port_tmp;
        }
        unset($host_tmp, $port_tmp);
    }

    $return = [ 'hostname' => $hostname ];

    $snmp_transport = 'udp';

    if ($snmp_version === "v3") {
        //$config['snmp']['v3'] = $snmp_config_v3; // Restore base SNMP v3 credentials
        $snmp_v3_seclevel     = $snmp_community;

        // These values are the same as in defaults.inc.php
        $snmp_v3_auth = [
            'authlevel'  => "noAuthNoPriv",
            'authname'   => "observium",
            'authpass'   => "",
            'authalgo'   => "MD5",
            'cryptopass' => "",
            'cryptoalgo' => "AES"
        ];

        $add_context = FALSE; // Derp, last arg after transport is context
        if ($snmp_v3_seclevel === "nanp" || $snmp_v3_seclevel === "any" || $snmp_v3_seclevel === "noAuthNoPriv") {
            $snmp_v3_auth['authlevel'] = "noAuthNoPriv";
            $snmp_v3_args              = array_slice($add, 3);

            while ($arg = array_shift($snmp_v3_args)) {
                // parse all remaining args
                if (is_valid_param($arg, 'port')) {
                    $snmp_port = $arg;
                } elseif (preg_match('/^(' . implode("|", $config['snmp']['transports']) . ')$/', $arg)) {
                    $snmp_transport = $arg;
                    $add_context    = TRUE; // Derp, last arg after transport is context
                } elseif ($add_context && strlen($arg)) {
                    $snmp_context = $arg;
                    break;
                } else {
                    // FIXME: should add a sanity check of chars allowed in user
                    $user = $arg;
                }
            }

            if ($snmp_v3_seclevel !== "any") {
                //$config['snmp']['v3'] = [$snmp_v3_auth];
                $return['snmp_v3_auth'] = [ $snmp_v3_auth ];
            }
        } elseif ($snmp_v3_seclevel === "anp" || $snmp_v3_seclevel === "authNoPriv") {

            $snmp_v3_auth['authlevel'] = "authNoPriv";
            $snmp_v3_args              = array_slice($add, 4);
            $snmp_v3_auth['authname']  = array_shift($snmp_v3_args);
            $snmp_v3_auth['authpass']  = array_shift($snmp_v3_args);

            while ($arg = array_shift($snmp_v3_args)) {
                // parse all remaining args
                if (is_valid_param($arg, 'port')) {
                    $snmp_port = $arg;
                } elseif (preg_match('/^(' . implode("|", $config['snmp']['transports']) . ')$/i', $arg)) {
                    $snmp_transport = $arg;
                    $add_context    = TRUE; // Derp, last arg after transport is context
                } elseif (is_valid_param($arg, 'snmp_authalgo')) {
                    $snmp_v3_auth['authalgo'] = $arg;
                } elseif ($add_context && !safe_empty($arg)) {
                    $snmp_context = $arg;
                    break;
                }
            }

            //$config['snmp']['v3'] = [$snmp_v3_auth];
            $return['snmp_v3_auth'] = [ $snmp_v3_auth ];
        } elseif ($snmp_v3_seclevel === "ap" || $snmp_v3_seclevel === "authPriv") {
            $snmp_v3_auth['authlevel']  = "authPriv";
            $snmp_v3_args               = array_slice($add, 4);
            $snmp_v3_auth['authname']   = array_shift($snmp_v3_args);
            $snmp_v3_auth['authpass']   = array_shift($snmp_v3_args);
            $snmp_v3_auth['cryptopass'] = array_shift($snmp_v3_args);

            while ($arg = array_shift($snmp_v3_args)) {
                // parse all remaining args
                if (is_valid_param($arg, 'port')) {
                    $snmp_port = $arg;
                } elseif (preg_match('/^(' . implode("|", $config['snmp']['transports']) . ')$/i', $arg)) {
                    $snmp_transport = $arg;
                    $add_context    = TRUE; // Derp, last arg after transport is context
                } elseif (is_valid_param($arg, 'snmp_authalgo')) {
                    $snmp_v3_auth['authalgo'] = $arg;
                } elseif (is_valid_param($arg, 'snmp_cryptoalgo')) {
                    $snmp_v3_auth['cryptoalgo'] = $arg;
                } elseif ($add_context && !safe_empty($arg)) {
                    $snmp_context = $arg;
                    break;
                }
            }

            //$config['snmp']['v3'] = [ $snmp_v3_auth ];
            $return['snmp_v3_auth'] = [ $snmp_v3_auth ];
        }
        //print_debug_vars($snmp_v3_auth);
        //print_debug_vars($config['snmp']['v3']);
    } else {
        // v1 or v2c
        $snmp_v2_args = array_slice($add, 2);

        $add_context = FALSE; // Derp, last arg after transport is context
        while ($arg = array_shift($snmp_v2_args)) {
            // parse all remaining args
            if (is_valid_param($arg, 'port')) {
                $snmp_port = $arg;
            } elseif (preg_match('/(' . implode("|", $config['snmp']['transports']) . ')/i', $arg)) {
                $snmp_transport = $arg;
                $add_context    = TRUE; // Derp, last arg after transport is context
            } elseif (preg_match('/^(v1|v2c)$/i', $arg)) {
                $snmp_version = $arg;
            } elseif ($add_context && !safe_empty($arg)) {
                $snmp_context = $arg;
                break;
            }
        }
        if (!preg_match('/^(v1|v2c)$/i', $snmp_version)) {
            $snmp_version = $config['snmp']['version'] !== 'v3' ? $config['snmp']['version'] : 'v2c';
        }

        //$config['snmp']['community'] = ($snmp_community ? [$snmp_community] : $snmp_config_community);
        $return['snmp_community'] = $snmp_community ? [ $snmp_community ] : $config['snmp']['community'];
    }
    $return['snmp_version']   = $snmp_version;
    $return['snmp_transport'] = $snmp_transport;
    $return['snmp_port']      = $snmp_port;

    // Add snmp context to params
    if (isset($snmp_context)) {
        $snmp_options['snmp_context'] = $snmp_context;
    } else {
        // Reset snmp context if in loop
        unset($snmp_options['snmp_context']);
    }

    return $return;
}

/**
 * Add device to DB or queue.
 *
 * @param array $vars
 *
 * @return bool|int
 */
function add_device_vars($vars) {
    global $config;

    $hostname = strip_tags($vars['hostname']);
    $printname = escape_html($hostname);

    // Add a device to remote poller,
    // only validate vars and add to pollers_actions
    if (is_intnum($vars['poller_id']) && $vars['poller_id'] != $config['poller_id']) {
        print_message("Requested add device with hostname '$printname' to remote Poller [{$vars['poller_id']}].");
        if (!(is_valid_hostname($hostname) || get_ip_version($hostname))) {
            // Failed DNS lookup
            print_error("Hostname '$printname' is not valid.");
            return FALSE;
        }
        if (!dbExist('pollers', '`poller_id` = ?', [$vars['poller_id']])) {
            // Incorrect Poller ID
            print_error("Device with hostname '$printname' not added. Unknown target Poller requested.");
            return FALSE;
        }
        if ($tmp_id = dbFetchCell('SELECT `poller_id` FROM `observium_actions` WHERE `action` = ? AND `identifier` = ?', ['device_add', $hostname])) {
            // Incorrect Poller ID
            print_error("Already queued addition device with hostname '$printname' on remote Poller [$tmp_id].");
            return FALSE;
        }
        if (dbExist('devices', '`hostname` = ?', [$hostname])) {
            // found in database
            print_error("Already got device with hostname '$printname'.");
            return FALSE;
        }
        if (function_exists('add_action_queue') &&
            $action_id = add_action_queue('device_add', $hostname, $vars)) {
            print_message("Device with hostname '$printname' added to queue [$action_id] for addition on remote Poller [{$vars['poller_id']}].");
            log_event("Device with hostname '$printname' added to queue [$action_id] for addition on remote Poller [{$vars['poller_id']}].", NULL, 'info', NULL, 7);
            return TRUE;
        }
        print_error("Device with hostname '$printname' not added. Incorrect addition to actions queue.");
        return FALSE;
    }

    // Keep original snmp/rrd config, for revert at end
    $config_snmp = $config['snmp'];
    $config_rrd  = $config['rrd_override'];

    $snmp_oids = [];
    if (isset($vars['snmpable']) && !empty($vars['snmpable'])) {
        foreach (explode(' ', $vars['snmpable']) as $oid) {
            if (preg_match(OBS_PATTERN_SNMP_OID_NUM, $oid)) {
                // Valid Numeric OID
                $snmp_oids[] = $oid;
            } elseif (str_contains($oid, '::') && $oid_num = snmp_translate($oid)) {
                // Named MIB::Oid which we can translate
                $snmp_oids[] = $oid_num;
            } else {
                print_warning("Invalid or unknown OID: " . escape_html($oid));
            }
        }
        if (empty($snmp_oids)) {
            print_error("Incorrect or not numeric OIDs passed for check device availability.");
            return FALSE;
        }
    }

    // Default snmp port
    if (is_valid_param($vars['snmp_port'], 'port')) {
        $snmp_port = (int)$vars['snmp_port'];
    } else {
        $snmp_port = 161;
    }

    // Default snmp version
    if ($vars['snmp_version'] !== "v2c" &&
        $vars['snmp_version'] !== "v3" &&
        $vars['snmp_version'] !== "v1") {
        $vars['snmp_version'] = $config['snmp']['version'];
    }

    switch ($vars['snmp_version']) {
        case 'v2c':
        case 'v1':

            if (!safe_empty($vars['snmp_community'])) {
                // Hrm, I not sure why strip_tags
                $snmp_community              = strip_tags($vars['snmp_community']);
                $config['snmp']['community'] = [$snmp_community];
            }

            $snmp_version = $vars['snmp_version'];

            print_message("Adding SNMP$snmp_version host $printname port $snmp_port");
            break;

        case 'v3':

            if (!safe_empty($vars['snmp_authlevel'])) {
                $snmp_v3 = [
                  'authlevel'  => $vars['snmp_authlevel'],
                  'authname'   => $vars['snmp_authname'],
                  'authpass'   => $vars['snmp_authpass'],
                  'authalgo'   => $vars['snmp_authalgo'],
                  'cryptopass' => $vars['snmp_cryptopass'],
                  'cryptoalgo' => $vars['snmp_cryptoalgo'],
                ];

                array_unshift($config['snmp']['v3'], $snmp_v3);
            }

            $snmp_version = "v3";

            print_message("Adding SNMPv3 host $printname port $snmp_port");
            break;

        default:
            print_error("Unsupported SNMP Version. There was a dropdown menu, how did you reach this error?"); // We have a hacker!
            return FALSE;
    }

    if (get_var_true($vars['ignorerrd'], 'confirm')) {
        $config['rrd_override'] = TRUE;
    }

    $snmp_options = [];
    if (get_var_true($vars['ping_skip'])) {
        $snmp_options['ping_skip'] = TRUE;
    }

    if (is_valid_param($vars['snmp_timeout'], 'snmp_timeout')) {
        $snmp_options['snmp_timeout'] = (int)$vars['snmp_timeout'];
    }

    if (is_valid_param($vars['snmp_retries'], 'snmp_retries')) {
        $snmp_options['snmp_retries'] = (int)$vars['snmp_retries'];
    }

    // Optional max repetition
    if (is_intnum($vars['snmp_maxrep']) && $vars['snmp_maxrep'] >= 0 && $vars['snmp_maxrep'] <= 500) {
        $snmp_options['snmp_maxrep'] = trim($vars['snmp_maxrep']);
    }

    // Optional SNMPable OIDs
    if ($snmp_oids) {
        $snmp_options['snmpable'] = implode(' ', $snmp_oids);
    }

    // Optional SNMP Context
    if (trim($vars['snmp_context']) !== '') {
        $snmp_options['snmp_context'] = trim($vars['snmp_context']);
    }

    $result = add_device($hostname, $snmp_version, $snmp_port, strip_tags($vars['snmp_transport']), $snmp_options);

    // Revert original snmp/rrd config
    $config['snmp']         = $config_snmp;
    $config['rrd_override'] = $config_rrd;

    return $result;
}

/**
 * Adds the new device to the database.
 *
 * Before adding the device, checks duplicates in the database and the availability of device over a network.
 *
 * @param string       $hostname                           Device hostname
 * @param string|array $snmp_version                       SNMP version(s) (default: $config['snmp']['version'])
 * @param int          $snmp_port                          SNMP port (default: 161)
 * @param string       $snmp_transport                     SNMP transport (default: udp)
 * @param array        $options                            Additional options can be passed ('ping_skip' - for skip ping test and add device attrib for skip
 *                                                         pings later
 *                                                         'break' - for break recursion,
 *                                                         'test'  - for skip adding, only test device availability)
 * @param int          $flags
 *
 * @return mixed Returns $device_id number if added, 0 (zero) if device not accessible with current auth and FALSE if device complete not accessible by
 *               network. When testing, returns -1 if the device is available.
 */
// TESTME needs unit testing
function add_device($hostname, $snmp_version = [], $snmp_port = 161, $snmp_transport = 'udp', $options = [], $flags = OBS_DNS_ALL)
{
    global $config;

    // If $options['break'] set as TRUE, break recursive function execute
    if (isset($options['break']) && $options['break']) {
        return FALSE;
    }
    $return = FALSE; // By default return FALSE

    // Reset snmp timeout and retries options for speedup device adding
    unset($config['snmp']['timeout'], $config['snmp']['retries']);

    $snmp_transport = strtolower($snmp_transport);

    $hostname = strtolower(trim($hostname));

    // Try detect if hostname is IP
    switch (get_ip_version($hostname)) {
        case 6:
        case 4:
            if ($config['require_hostname']) {
                print_error("Hostname should be a valid resolvable FQDN name. Or set config option \$config['require_hostname'] as FALSE.");
                return $return;
            }
            $hostname = ip_compress($hostname); // Always use compressed IPv6 name
            $ip       = $hostname;
            break;
        default:
            if ($snmp_transport === 'udp6' || $snmp_transport === 'tcp6') { // IPv6 used only if transport 'udp6' or 'tcp6'
                $flags ^= OBS_DNS_A;                                        // exclude A
            }
            // Test DNS lookup.
            $ip = gethostbyname6($hostname, $flags);
    }

    // Test if host exists in database
    if (!dbExist('devices', '`hostname` = ?', [$hostname])) {
        if ($ip) {
            $ip_version = get_ip_version($ip);

            // Test reachability
            $options['ping_skip'] = isset($options['ping_skip']) && $options['ping_skip'];
            if ($options['ping_skip']) {
                $flags |= OBS_PING_SKIP;
            }

            if (is_pingable($hostname, $flags)) {
                // Test directory exists in /rrd/
                if (!$config['rrd_override'] && file_exists($config['rrd_dir'] . '/' . $hostname)) {
                    print_error("Directory <observium>/rrd/$hostname already exists.");
                    return FALSE;
                }

                // Detect snmp transport
                if (str_istarts($snmp_transport, 'tcp')) {
                    $snmp_transport = ($ip_version == 4 ? 'tcp' : 'tcp6');
                } else {
                    $snmp_transport = ($ip_version == 4 ? 'udp' : 'udp6');
                }

                // Detect snmp port
                if (!is_valid_param($snmp_port, 'port')) {
                    $snmp_port = 161;
                } else {
                    $snmp_port = (int)$snmp_port;
                }

                // Detect snmp version
                if (empty($snmp_version)) {
                    // Here set default snmp version order
                    $i                  = 1;
                    $snmp_version_order = [];
                    foreach (['v2c', 'v3', 'v1'] as $tmp_version) {
                        if ($config['snmp']['version'] == $tmp_version) {
                            $snmp_version_order[0] = $tmp_version;
                        } else {
                            $snmp_version_order[$i] = $tmp_version;
                        }
                        $i++;
                    }
                    ksort($snmp_version_order);

                    foreach ($snmp_version_order as $tmp_version) {
                        $ret = add_device($hostname, $tmp_version, $snmp_port, $snmp_transport, $options);
                        if ($ret === FALSE) {
                            // Set $options['break'] for break recursive
                            $options['break'] = TRUE;
                        } elseif (is_intnum($ret) && $ret != 0) {
                            return $ret;
                        }
                    }
                } elseif ($snmp_version === "v3") {
                    // Try each set of parameters from config
                    foreach ($config['snmp']['v3'] as $auth_iter => $snmp) {
                        $snmp['version']   = $snmp_version;
                        $snmp['port']      = $snmp_port;
                        $snmp['transport'] = $snmp_transport;

                        foreach (['snmp_maxrep', 'snmp_timeout', 'snmp_retries'] as $param) {
                            if (isset($options[$param]) && is_numeric($options[$param])) {
                                $snmp[$param] = (int)$options[$param];
                            }
                        }

                        // Append SNMPable oids if passed
                        if (isset($options['snmpable']) && strlen($options['snmpable'])) {
                            $snmp['snmpable'] = $options['snmpable'];
                        }

                        // Append SNMP context if passed
                        if (isset($options['snmp_context']) && strlen($options['snmp_context'])) {
                            $snmp['snmp_context'] = $options['snmp_context'];
                        }

                        $device = build_initial_device_array($hostname, NULL, $snmp_version, $snmp_port, $snmp_transport, $snmp);

                        if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
                            // Hide snmp auth
                            print_message("Trying v3 parameters *** / ### [$auth_iter] ... ");
                        } else {
                            print_message("Trying v3 parameters " . escape_html($device['snmp_authname'] . "/" . $device['snmp_authlevel']) . " ... ");
                        }

                        if (is_snmpable($device)) {
                            if (!check_device_duplicated($device)) {
                                if (isset($options['test']) && $options['test']) {
                                    print_message('%WDevice "' . $hostname . '" has successfully been tested and available by ' . strtoupper($snmp_transport) . ' transport with SNMP ' . $snmp_version . ' credentials.%n', 'color');
                                    $device_id = -1;
                                } else {
                                    //$device_id = createHost($hostname, NULL, $snmp_version, $snmp_port, $snmp_transport, $snmp);
                                    $device_id = create_device($hostname, $snmp);
                                    if ($options['ping_skip']) {
                                        set_entity_attrib('device', $device_id, 'ping_skip', 1);
                                        // Force pingable check
                                        if (is_pingable($hostname, $flags ^ OBS_PING_SKIP)) {
                                            //print_warning("You passed the option the skip device ICMP echo pingable checks, but device responds to ICMP echo. Please check device preferences.");
                                            print_message("You have checked the option to skip ICMP ping, but the device responds to an ICMP ping. Perhaps you need to check the device settings.");
                                        }
                                    }
                                }
                                return $device_id;
                            }
                            // When detected a duplicate device, this mean it already SNMPable and not need to check next auth!
                            return FALSE;
                        } else {
                            print_warning("No reply on credentials " . escape_html($device['snmp_authname'] . "/" . $device['snmp_authlevel']) . " using $snmp_version.");
                        }
                    }
                } elseif ($snmp_version === "v2c" || $snmp_version === "v1") {
                    // Try each community from config
                    foreach ($config['snmp']['community'] as $auth_iter => $snmp_community) {
                        $snmp = [
                          'community' => $snmp_community,
                          'version'   => $snmp_version,
                          'port'      => $snmp_port,
                          'transport' => $snmp_transport,
                        ];

                        foreach (['snmp_maxrep', 'snmp_timeout', 'snmp_retries'] as $param) {
                            if (isset($options[$param]) && is_numeric($options[$param])) {
                                $snmp[$param] = (int)$options[$param];
                            }
                        }

                        // Append SNMPable oids if passed
                        if (isset($options['snmpable']) && strlen($options['snmpable'])) {
                            $snmp['snmpable'] = $options['snmpable'];
                        }

                        // Append SNMP context if passed
                        if (isset($options['snmp_context']) && strlen($options['snmp_context'])) {
                            $snmp['snmp_context'] = $options['snmp_context'];
                        }
                        $device = build_initial_device_array($hostname, $snmp_community, $snmp_version, $snmp_port, $snmp_transport, $snmp);

                        if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
                            // Hide snmp auth
                            print_message("Trying $snmp_version community *** [$auth_iter] ...");
                        } else {
                            print_message("Trying $snmp_version community " . escape_html($snmp_community) . " ...");
                        }

                        //r($options);
                        //r($snmp);
                        //r($device);
                        if (is_snmpable($device)) {
                            if (!check_device_duplicated($device)) {
                                if (isset($options['test']) && $options['test']) {
                                    print_message('%WDevice "' . $hostname . '" has successfully been tested and available by ' . strtoupper($snmp_transport) . ' transport with SNMP ' . $snmp_version . ' credentials.%n', 'color');
                                    $device_id = -1;
                                } else {
                                    //$device_id = createHost($hostname, $snmp_community, $snmp_version, $snmp_port, $snmp_transport, $snmp);
                                    $device_id = create_device($hostname, $snmp);
                                    if ($options['ping_skip']) {
                                        set_entity_attrib('device', $device_id, 'ping_skip', 1);
                                        // Force pingable check
                                        if (is_pingable($hostname, $flags ^ OBS_PING_SKIP)) {
                                            //print_warning("You passed the option the skip device ICMP echo pingable checks, but device responds to ICMP echo. Please check device preferences.");
                                            print_message("You have checked the option to skip ICMP ping, but the device responds to an ICMP ping. Perhaps you need to check the device settings.");
                                        }
                                    }
                                }
                                return $device_id;
                            }
                            // When detected a duplicate device, this mean it already SNMPable and not need to check next auth!
                            return FALSE;
                        } else {
                            if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
                                print_warning("No reply on given community *** using $snmp_version.");
                            } else {
                                print_warning("No reply on community " . escape_html($snmp_community) . " using $snmp_version.");
                            }
                            $return = 0; // Return zero for continue trying next auth
                        }
                    }
                } else {
                    print_error("Unsupported SNMP Version \"$snmp_version\".");
                    $return = 0; // Return zero for continue trying next auth
                }

                if (!$device_id) {
                    // Failed SNMP
                    print_error("Could not reach $hostname with given SNMP parameters using $snmp_version.");
                    $return = 0; // Return zero for continue trying next auth
                }
            } else {
                // failed Reachability
                print_error("Could not ping $hostname.");
            }
        } else {
            // Failed DNS lookup
            print_error("Could not resolve $hostname.");
        }
    } else {
        // found in database
        print_error("Already got device $hostname.");
    }

    return $return;
}

/**
 * Detect the device's OS
 *
 * Order for detect:
 *  if device rechecking (know old os): complex discovery (all), sysObjectID, sysDescr, file check
 *  if device first checking:           complex discovery (except network), sysObjectID, sysDescr, complex discovery (network), file check
 *
 * @param array $device Device array
 *
 * @return string Detected device os name
 */
function get_device_os($device)
{
    global $config, $table_rows, $cache_os;

    // If $recheck sets as TRUE, verified that 'os' corresponds to the old value.
    // recheck only if old device exist in definitions
    $recheck = isset($config['os'][$device['os']]);

    // Always force snmpwalk in os discovery without bulk for prevent fetch timeouts
    // https://jira.observium.org/browse/OBS-3922
    if (!isset($device['snmp_nobulk'])) {
        $device['snmp_nobulk'] = TRUE;
    }

    $sysDescr    = snmp_fix_string(snmp_cache_oid($device, 'sysDescr.0', 'SNMPv2-MIB'));
    $sysDescr_ok = snmp_status() || snmp_error_code() === OBS_SNMP_ERROR_EMPTY_RESPONSE; // Allow empty response for sysDescr (not timeouts)
    $sysObjectID = snmp_cache_sysObjectID($device);

    // Cache discovery os definitions
    cache_discovery_definitions();
    $discovery_os = $GLOBALS['cache']['discovery_os'];
    $cache_os     = [];

    $table_rows    = [];
    $table_opts    = ['max-table-width' => TRUE]; // Set maximum table width as available columns in terminal
    $table_headers = ['%WOID%n', ''];
    $table_rows[]  = ['sysDescr', $sysDescr];
    $table_rows[]  = ['sysObjectID', $sysObjectID];
    print_cli_table($table_rows, $table_headers, NULL, $table_opts);
    //print_debug("Detect OS. sysDescr: '$sysDescr', sysObjectID: '$sysObjectID'");

    $table_rows = []; // Reinit
    //$table_opts    = array('max-table-width' => 200);
    $table_headers = ['%WOID%n', '%WMatched definition%n', ''];
    // By first check all sysObjectID
    foreach ($discovery_os['sysObjectID'] as $def => $cos) {
        if (match_oid_num($sysObjectID, $def)) {
            // Store matched OS, but by first need check by complex discovery arrays!
            $sysObjectID_def = $def;
            $sysObjectID_os  = $cos;
            //print_debug_vars($sysObjectID_def);
            //print_debug_vars($sysObjectID_os);
            break;
        }
    }

    if ($recheck) {
        $table_desc = 'Re-Detect OS matched';
        $old_os     = $device['os'];

        /*
        if (!$sysDescr_ok && !empty($old_os)) {
          // If sysDescr empty - return old os, because some snmp error
          print_debug("ERROR: sysDescr not received, OS re-check stopped.");
          return $old_os;
        }
        */

        // Recheck by complex discovery array
        // Yes, before sysObjectID, because complex more accurate and can intersect with it!
        foreach ($discovery_os['discovery'][$old_os] as $def) {
            if (match_discovery_oids($device, $def, $sysObjectID, $sysDescr)) {
                print_cli_table($table_rows, $table_headers, $table_desc . " ($old_os: " . $config['os'][$old_os]['text'] . '):', $table_opts);
                return $old_os;
            }
        }
        foreach ($discovery_os['discovery_network'][$old_os] as $def) {
            if (match_discovery_oids($device, $def, $sysObjectID, $sysDescr)) {
                print_cli_table($table_rows, $table_headers, $table_desc . " ($old_os: " . $config['os'][$old_os]['text'] . '):', $table_opts);
                return $old_os;
            }
        }

        /** DISABLED.
         * Recheck only by complex, networked and file rules
         *
         * // Recheck by sysObjectID
         * if ($sysObjectID_os)
         * {
         * // If OS detected by sysObjectID just return it
         * $table_rows[] = array('sysObjectID', $sysObjectID_def, $sysObjectID);
         * print_cli_table($table_rows, $table_headers, $table_desc . " ($old_os: ".$config['os'][$old_os]['text'].'):', $table_opts);
         * return $sysObjectID_os;
         * }
         *
         * // Recheck by sysDescr from definitions
         * foreach ($discovery_os['sysDescr'][$old_os] as $pattern)
         * {
         * if (preg_match($pattern, $sysDescr))
         * {
         * $table_rows[] = array('sysDescr', $pattern, $sysDescr);
         * print_cli_table($table_rows, $table_headers, $table_desc . " ($old_os: ".$config['os'][$old_os]['text'].'):', $table_opts);
         * return $old_os;
         * }
         * }
         */

        // Recheck by include file (moved to end!)

        // Else full recheck 'os'!
        unset($os, $file);

    } // End recheck

    $table_desc = 'Detect OS matched';

    // Check by complex discovery arrays (except networked)
    // Yes, before sysObjectID, because complex more accurate and can intersect with it!
    foreach ($discovery_os['discovery'] as $cos => $defs) {
        foreach ($defs as $def) {
            if (match_discovery_oids($device, $def, $sysObjectID, $sysDescr)) {
                $os = $cos;
                if (OBS_DEBUG && $sysObjectID_os && $sysObjectID_os !== $cos) {
                    print_cli("OS %b$cos%n discovered by complex definition match, but also found %r$sysObjectID_os%n by exact sysObjectID '$sysObjectID_def' definition.\n");
                }
                break 2;
            }
        }
    }

    // Check by sysObjectID
    if (!$os && $sysObjectID_os) {
        // If OS detected by sysObjectID just return it
        $os           = $sysObjectID_os;
        $table_rows[] = ['sysObjectID', $sysObjectID_def, $sysObjectID];
        print_cli_table($table_rows, $table_headers, $table_desc . " ($os: " . $config['os'][$os]['text'] . '):', $table_opts);
        return $os;
    }

    if (!$os && $sysDescr) {
        // Check by sysDescr from definitions
        foreach ($discovery_os['sysDescr'] as $cos => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $sysDescr)) {
                    $table_rows[] = ['sysDescr', $pattern, $sysDescr];
                    $os           = $cos;
                    break 2;
                }
            }
        }
    }

    // Check by complex discovery arrays, now networked
    if (!$os) {
        foreach ($discovery_os['discovery_network'] as $cos => $defs) {
            foreach ($defs as $def) {
                if (match_discovery_oids($device, $def, $sysObjectID, $sysDescr)) {
                    $os = $cos;
                    break 2;
                }
            }
        }
    }

    if (!$os) {
        $path        = $config['install_dir'] . '/includes/discovery/os';
        $sysObjectId = $sysObjectID; // old files use wrong variable name

        // Recheck first
        $recheck_file = FALSE;
        if ($recheck && $old_os) {
            if (is_file($path . "/$old_os.inc.php")) {
                $recheck_file = $path . "/$old_os.inc.php";
            } elseif (isset($config['os'][$old_os]['discovery_os']) &&
                      is_file($path . '/' . $config['os'][$old_os]['discovery_os'] . '.inc.php')) {
                $recheck_file = $path . '/' . $config['os'][$old_os]['discovery_os'] . '.inc.php';
            }

            if ($recheck_file) {
                print_debug("Including $recheck_file");

                $sysObjectId = $sysObjectID; // old files use wrong variable name
                include($recheck_file);

                if ($os && $os == $old_os) {
                    $table_rows[] = ['file', $recheck_file, ''];
                    print_cli_table($table_rows, $table_headers, $table_desc . " ($old_os: " . $config['os'][$old_os]['text'] . '):', $table_opts);
                    return $old_os;
                }
            }
        }

        // Check all other by include file
        $dir_handle = @opendir($path) or die("Unable to open $path");
        while ($file = readdir($dir_handle)) {
            if (preg_match('/\.inc\.php$/', $file) && $file !== $recheck_file) {
                print_debug("Including $file");

                include($path . '/' . $file);

                if ($os) {
                    $table_rows[] = ['file', $file, ''];
                    break; // Stop while if os detected
                }
            }
        }
        closedir($dir_handle);
    }

    if ($os) {
        print_cli_table($table_rows, $table_headers, $table_desc . " ($os: " . $config['os'][$os]['text'] . '):', $table_opts);
        return $os;
    }

    return 'generic';
}

function check_device_os_changed(&$device) {
    $old_os       = $device['os'];
    $device['os'] = get_device_os($device);
    if ($device['os'] != $old_os) {
        print_cli_data("Device OS changed", $old_os . " -> " . $device['os'], 1);
        log_event('OS changed: ' . $old_os . ' -> ' . $device['os'], $device, 'device', $device['device_id'], 'warning');

        // Additionally, reset icon and type for device if os changed
        dbUpdate(['os' => $device['os'], 'icon' => ['NULL'], 'type' => ['NULL']], 'devices', '`device_id` = ?', [$device['device_id']]);
        if (isset($device['attribs']['override_icon'])) {
            del_entity_attrib('device', $device, 'override_icon');
        }
        if (isset($device['attribs']['override_type'])) {
            del_entity_attrib('device', $device, 'override_type');
        }

        // Reset models cache
        if (isset($GLOBALS['cache']['devices']['model'][$device['device_id']])) {
            unset($GLOBALS['cache']['devices']['model'][$device['device_id']]);
        }

        return TRUE;
    }
    return FALSE;
}

/**
 * Check duplicated devices in DB by sysName, snmpEngineID and entPhysicalSerialNum (if possible)
 * Can work only on local poller
 *
 * If found duplicate devices return TRUE, in other cases return FALSE
 *
 * @param array $device Device array which should be checked for duplicates
 *
 * @return bool TRUE if duplicates found
 */
// TESTME needs unit testing
function check_device_duplicated($device)
{

    switch (get_device_duplicated($device)) {
        case 'hostname':
            // Hostname should be uniq
            // Return TRUE if we have device with same hostname in DB
            print_error("Already got device with hostname (" . $device['hostname'] . ").");
            return TRUE;

        case 'ip_snmp_v1':
        case 'ip_snmp_v2c':
            if (get_ip_version($device['hostname'])) {
                $dns_ip = $device['hostname'];
            } elseif (in_array($device['snmp_transport'], ['udp6', 'tcp6'])) {
                $dns_ip = gethostbyname6($device['hostname'], OBS_DNS_AAAA); // IPv6
            } else {
                $dns_ip = gethostbyname6($device['hostname'], OBS_DNS_A); // IPv4
            }
            print_error("Already got device with resolved IP ($dns_ip) and SNMP v1/v2c community.");
            return TRUE;
        case 'ip_snmp_v3':
            if (get_ip_version($device['hostname'])) {
                $dns_ip = $device['hostname'];
            } elseif (in_array($device['snmp_transport'], ['udp6', 'tcp6'])) {
                $dns_ip = gethostbyname6($device['hostname'], OBS_DNS_AAAA); // IPv6
            } else {
                $dns_ip = gethostbyname6($device['hostname'], OBS_DNS_A); // IPv4
            }
            print_error("Already got device with resolved IP ($dns_ip) and SNMP v3 auth.");
            return TRUE;
    }

    $snmpEngineID = snmp_cache_snmpEngineID($device);
    $sysName_orig = snmp_get_oid($device, 'sysName.0', 'SNMPv2-MIB');
    $sysName      = strtolower($sysName_orig);
    if (empty($sysName)) {
        $sysName_type = 'empty';
        $sysName      = FALSE;
    } elseif (is_valid_hostname($sysName_orig, TRUE)) {
        // sysName stored in db as lowercase, always compare as lowercase too!
        $sysName_type = 'fqdn';
    } else {
        // sysName not FQDN, hard case, many devices have default sysname or user just not write full sysname
        $sysName_type = 'notfqdn';
    }

    if (!empty($snmpEngineID)) {
        $test_devices = dbFetchRows('SELECT * FROM `devices` WHERE `disabled` = 0 AND `snmpEngineID` = ?', [$snmpEngineID]);
        foreach ($test_devices as $test) {
            $compare = strtolower($test['sysName']) === $sysName;
            if ($compare) {
                // Check (if possible) serial, for cluster devices sysName and snmpEngineID same
                $test_entPhysical = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalSerialNum` != ? ORDER BY `entPhysicalClass` LIMIT 1', [$test['device_id'], '']);
                if (isset($test_entPhysical['entPhysicalSerialNum'])) {
                    // Compare by any common serial
                    $serial  = snmp_get_oid($device, 'entPhysicalSerialNum.' . $test_entPhysical['entPhysicalIndex'], 'ENTITY-MIB');
                    $compare = strtolower($serial) === strtolower($test_entPhysical['entPhysicalSerialNum']);
                    if ($compare) {
                        // This devices really same, with same sysName, snmpEngineID and entPhysicalSerialNum
                        print_error("Already got device with SNMP-read sysName ($sysName), 'snmpEngineID' = $snmpEngineID and 'entPhysicalSerialNum' = $serial (" . $test['hostname'] . ").");
                        return TRUE;
                    }
                } else {
                    // For not FQDN sysname check all (other) possible system Oids:
                    if ($sysName_type !== 'fqdn') {
                        $compare = compare_devices_oids($device, $test);
                        // Same sysName and snmpEngineID, but different some other system Oids
                        if (!$compare) {
                            continue;
                        }
                    }
                    // Return TRUE if have same snmpEngineID && sysName in DB
                    print_error("Already got device with SNMP-read sysName ($sysName) and 'snmpEngineID' = $snmpEngineID (" . $test['hostname'] . ").");
                    return TRUE;
                }
            }
        }

    } else {

        if ($sysName_type === 'empty' && ($device['os'] === 'generic' || empty($device['os']))) {
            // For some derp devices (ie, only ent tree Oids) detect os before next checks
            $device['os'] = get_device_os($device);

            $test_devices = dbFetchRows('SELECT * FROM `devices` WHERE `disabled` = 0 AND `sysName` = ? AND `os` = ?', [$sysName, $device['os']]);
        } else {
            // If snmpEngineID empty, check by sysName (and additional system Oids)
            $test_devices = dbFetchRows('SELECT * FROM `devices` WHERE `disabled` = 0 AND `sysName` = ?', [$sysName]);
        }

        foreach ($test_devices as $test) {
            // Last check (if possible) serial, for cluster devices sysName and snmpEngineID same
            $test_entPhysical = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalSerialNum` != ? ORDER BY `entPhysicalClass` LIMIT 1', [$test['device_id'], '']);
            if (isset($test_entPhysical['entPhysicalSerialNum'])) {
                $serial  = snmp_get_oid($device, "entPhysicalSerialNum." . $test_entPhysical['entPhysicalIndex'], "ENTITY-MIB");
                $compare = strtolower($serial) === strtolower($test_entPhysical['entPhysicalSerialNum']);
                if ($compare) {
                    // This devices really same, with same sysName, snmpEngineID and entPhysicalSerialNum
                    print_error("Already got device with SNMP-read sysName ($sysName) and 'entPhysicalSerialNum' = $serial (" . $test['hostname'] . ").");
                    return TRUE;
                }
            } else {
                // Check all (other) possible system Oids:
                $compare = compare_devices_oids($device, $test);
                // Same sysName and other system Oids
                if ($compare) {
                    // Derp case, when device not have uniq Oids.. completely, ie Hikvision cams
                    //if (empty($sysName) && )
                    // This devices really same, with same sysName and other system Oids
                    print_error("Already got device with SNMP-read sysName ($sysName) and other system Oids (" . $test['hostname'] . ").");
                    return TRUE;
                }
            }
        }
        // if (!$has_entPhysical)
        // {
        //   // Return TRUE if we have same sysName in DB
        //   print_error("Already got device with SNMP-read sysName ($sysName).");
        //   return TRUE;
        // }

    }

    // In all other cases return FALSE
    return FALSE;
}

/**
 * Get duplicated device from DB.
 * Can return found devices as array(s):
 *   $return['hostname']    - Same hostname in DB
 *   $return['ip_snmp']     - Same DNS IP and SNMP port (but different SNMP auth, not exactly same!)
 *   $return['ip_snmp_v1']  - Same DNS IP and SNMPv1 with same community
 *   $return['ip_snmp_v2c'] - Same DNS IP and SNMPv2c with same community
 *   $return['ip_snmp_v3']  - Same DNS IP and SNMPv3  with same v3 auth
 *
 * @param array $device Device array
 * @param array $return Return found duplicate device array (if argument passed)
 *
 * @return string
 */
function get_device_duplicated($device, &$return = [])
{
    if (empty($device['hostname'])) {
        return NULL;
    }
    // Check if we need return device
    $return_devices = func_num_args() > 1;
    $duplicate      = NULL;

    // Check by same hostname in DB
    if ($device['device_id'] && $device['device_id'] > 0) {
        // Exclude self device
        $where  = '`hostname` = ? AND `device_id` != ?';
        $params = [$device['hostname'], $device['device_id']];
    } else {
        $where  = '`hostname` = ?';
        $params = [$device['hostname']];
    }
    if (dbExist('devices', $where, $params)) {
        $duplicate = 'hostname';
        if ($return_devices) {
            $return[$duplicate][] = dbFetchRow("SELECT * FROM `devices` WHERE " . $where, $params);
        }
        return $duplicate;
    }

    // Check by network access and SNMP
    if (get_ip_version($device['hostname'])) {
        $dns_ip = $device['hostname'];
    } elseif (in_array($device['snmp_transport'], ['udp6', 'tcp6'])) {
        $dns_ip = gethostbyname6($device['hostname'], OBS_DNS_AAAA); // IPv6
    } else {
        $dns_ip = gethostbyname6($device['hostname'], OBS_DNS_A); // IPv4
    }
    $snmp_port = is_intnum($device['snmp_port']) ? $device['snmp_port'] : 161;
    if ($device['snmp_context']) {
        // Also check snmp context
        $where  = '`ip` = ? AND `snmp_port` = ? AND `snmp_context` = ?';
        $params = [ip_compress($dns_ip), $snmp_port, $device['snmp_context']];
    } else {
        $where  = '`ip` = ? AND `snmp_port` = ? AND `snmp_context` IS NULL';
        $params = [ip_compress($dns_ip), $snmp_port];
    }
    if ($device['device_id'] && $device['device_id'] > 0) {
        // Exclude self device
        $where    .= ' AND `device_id` != ?';
        $params[] = $device['device_id'];
    }
    if ($dns_ip && dbExist('devices', $where, $params)) {
        // Quick check if same Device IP and SNMP port already exist, when true check snmp auth
        foreach (dbFetchRows('SELECT * FROM `devices` WHERE ' . $where, $params) as $entry) {
            if ($device['snmp_transport'] === 'v3') {
                // SNMP v3 auth check
                $device['snmp_authlevel'] = strtolower($device['snmp_authlevel']);
                //$entry['snmp_authlevel']  = strtolower($entry['snmp_authlevel']);
                if ($device['snmp_authlevel'] === 'noauthnopriv' && $device['snmp_authname'] === $entry['snmp_authname']) {
                    // Exactly same host, v3 noAuthNoPriv
                    $duplicate = 'ip_snmp_' . $entry['snmp_transport'];
                    if ($return_devices) {
                        $return[$duplicate][] = $entry;
                    }

                } elseif ($device['snmp_authlevel'] === 'authnopriv' && $device['snmp_authname'] === $entry['snmp_authname'] &&
                          $device['snmp_authpass'] === $entry['snmp_authpass'] && $device['snmp_authalgo'] === $entry['snmp_authalgo']) {
                    // Exactly same host, v3 authNoPriv
                    $duplicate = 'ip_snmp_' . $entry['snmp_transport'];
                    if ($return_devices) {
                        $return[$duplicate][] = $entry;
                    }

                } elseif ($device['snmp_authlevel'] === 'authpriv' && $device['snmp_authname'] === $entry['snmp_authname'] &&
                          $device['snmp_authpass'] === $entry['snmp_authpass'] && $device['snmp_authalgo'] === $entry['snmp_authalgo'] &&
                          $device['snmp_cryptopass'] === $entry['snmp_cryptopass'] && $device['snmp_cryptoalgo'] === $entry['snmp_cryptoalgo']) {
                    // Exactly same host, v3 authNoPriv
                    $duplicate = 'ip_snmp_' . $entry['snmp_transport'];
                    if ($return_devices) {
                        $return[$duplicate][] = $entry;
                    }

                } elseif ($return_devices) {
                    // Not exactly same, just return as array
                    $return['ip_snmp'][] = $entry;
                }

            } else {
                // SNMP v1/v2c community check
                if ($device['snmp_community'] === $entry['snmp_community']) {
                    // Exactly same host
                    $duplicate = 'ip_snmp_' . $entry['snmp_transport'];
                    if ($return_devices) {
                        $return[$duplicate][] = $entry;
                    }
                } elseif ($return_devices) {
                    // Not exactly same, just return as array
                    $return['ip_snmp'][] = $entry;
                }
            }

            // Stop loop if not required devices return
            if (!$return_devices && $duplicate) {
                break;
            }
        }
        // FIXME. Probably need to check poller_id and private nets (can be same on different pollers?)
    }

    return $duplicate;
}

function detect_device_snmp_maxrep($device, $mib_oid = NULL, $force = FALSE, $start_max_rep = NULL) {
    global $config;

    if (!$force) {
        if (!$config['snmp']['max-rep'] || snmp_nobulk($device)) {
            return FALSE;
        }
        if (is_numeric($device['snmp_maxrep'])) {
            // Already configured for a device
            return $device['snmp_maxrep'];
        }
    }

    // Check if snmp bulk errors detected
    $params = [ $device['device_id'], OBS_SNMP_ERROR_BULK_REQUEST_TIMEOUT, [ time() - 86400 ] ];

    // Detect max-rep value when bulk errors detected
    if ($force ||
        ($config['snmp']['errors'] && dbExist('snmp_errors', '`device_id` = ? AND `error_code` = ? AND `updated` > ?', $params))) {

        $snmp_max_rep = 10; // Default when isn't set
        if (is_numeric($start_max_rep) && $start_max_rep > 0) {
            // force max-rep
            $snmp_max_rep = (int)$start_max_rep;
        } elseif (isset($cache['devices']['model'][$device['device_id']]['snmp']['max-rep']) &&
                  is_numeric($cache['devices']['model'][$device['device_id']]['snmp']['max-rep'])) {
            // Model specific can be FALSE
            $snmp_max_rep = $cache['devices']['model'][$device['device_id']]['snmp']['max-rep'];
        } elseif (isset($config['os'][$device['os']]['snmp']['max-rep']) &&
                  is_numeric($config['os'][$device['os']]['snmp']['max-rep'])) {
            // OS specific
            $snmp_max_rep = $config['os'][$device['os']]['snmp']['max-rep'];
        }

        print_debug("Found SNMP bulk errors, try detect minimum max-rep value, starting from '$snmp_max_rep'..");

        $mib_oid = empty($mib_oid) ? 'IF-MIB::ifEntry' : $mib_oid;
        // Mostly common Oid, probably get from snmp_errors instead
        [ $mib, $oid ] = explode('::', $mib_oid, 2);

        $device_tmp = $device;
        $device_tmp['snmp_retries'] = 1;
        $device_tmp['snmp_maxrep'] = 0; // Disable max-rep for first check
        $data_tmp = snmpwalk_cache_oid($device_tmp, $oid, [], $mib);
        if (snmp_status()) {
            while ($snmp_max_rep > 0) {
                $device_tmp['snmp_maxrep'] = $snmp_max_rep;
                $data_tmp = snmpwalk_cache_oid($device_tmp, $oid, [], $mib);
                if (snmp_status()) {
                    print_debug("Detected minimum SNMP max-rep value '$snmp_max_rep' (0 mean need disable).");
                    return (int)$snmp_max_rep;
                }
                $step = $snmp_max_rep > 10 ? 10 : 5;
                $snmp_max_rep -= $step;
            }
        }

        return 0; // Disable max-rep!
    }

    return FALSE;
}

/**
 * Detect SNMP auth params without adding device by hostname or IP
 * if SNMP auth detected return array with auth params or FALSE if not detected
 *
 * @param string $hostname
 * @param int    $snmp_port
 * @param string $snmp_transport
 * @param false  $detect_ip_version
 *
 * @return array|false
 */
function detect_device_snmpauth($hostname, $snmp_port = 161, $snmp_transport = 'udp', $detect_ip_version = FALSE)
{
    global $config;

    // Additional checks for IP version
    if ($detect_ip_version) {
        $ip_version = get_ip_version($hostname);
        if (!$ip_version) {
            $ip         = gethostbyname6($hostname);
            $ip_version = get_ip_version($ip);
        }
        // Detect snmp transport
        if (str_istarts($snmp_transport, 'tcp')) {
            $snmp_transport = ($ip_version == 4 ? 'tcp' : 'tcp6');
        } else {
            $snmp_transport = ($ip_version == 4 ? 'udp' : 'udp6');
        }
    }
    // Detect snmp port
    if (!is_valid_param($snmp_port, 'port')) {
        $snmp_port = 161;
    } else {
        $snmp_port = (int)$snmp_port;
    }

    // Here set default snmp version order
    $i                  = 1;
    $snmp_version_order = [];
    foreach (['v2c', 'v3', 'v1'] as $tmp_version) {
        if ($config['snmp']['version'] == $tmp_version) {
            $snmp_version_order[0] = $tmp_version;
        } else {
            $snmp_version_order[$i] = $tmp_version;
        }
        $i++;
    }
    ksort($snmp_version_order);

    foreach ($snmp_version_order as $snmp_version) {
        if ($snmp_version === 'v3') {
            // Try each set of parameters from config
            foreach ($config['snmp']['v3'] as $auth_iter => $snmp_v3) {
                $device = build_initial_device_array($hostname, NULL, $snmp_version, $snmp_port, $snmp_transport, $snmp_v3);

                if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
                    // Hide snmp auth
                    print_message("Trying v3 parameters *** / ### [$auth_iter] ... ");
                } else {
                    print_message("Trying v3 parameters " . $device['snmp_authname'] . "/" . $device['snmp_authlevel'] . " ... ");
                }

                if (is_snmpable($device)) {
                    return $device;
                } elseif ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
                    print_warning("No reply on credentials *** / ### using $snmp_version.");
                } else {
                    print_warning("No reply on credentials " . $device['snmp_authname'] . "/" . $device['snmp_authlevel'] . " using $snmp_version.");
                }
            }

        } else { // if ($snmp_version === "v2c" || $snmp_version === "v1")

            // Try each community from config
            foreach ($config['snmp']['community'] as $auth_iter => $snmp_community) {
                $device = build_initial_device_array($hostname, $snmp_community, $snmp_version, $snmp_port, $snmp_transport);

                if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
                    // Hide snmp auth
                    print_message("Trying $snmp_version community *** [$auth_iter] ...");
                } else {
                    print_message("Trying $snmp_version community $snmp_community ...");
                }

                if (is_snmpable($device)) {
                    return $device;
                } else {
                    print_warning("No reply on community $snmp_community using $snmp_version.");
                }
            }
        }
    }

    return FALSE;
}

//DEPRECATED. Compatibility for old style
function create_host($hostname, $snmp_community, $snmp_version, $snmp_port = 161, $snmp_transport = 'udp', $snmp_extra = [])
{
    $snmp_array = [
      'snmp_version'   => $snmp_version,
      'snmp_port'      => $snmp_port,
      'snmp_transport' => $snmp_transport,
      'community'      => $snmp_community,
    ];
    if (!empty($snmp_extra)) {
        $snmp_array = array_merge($snmp_array, $snmp_extra);
    }

    return create_device($hostname, $snmp_array);
}

/**
 * Add device into database
 *
 * @param string $hostname Device hostname
 * @param array  $snmp     SNMP v1/v2c/v3 params and Extra options
 *
 * @return bool|string
 */
function create_device($hostname, $snmp = [])
{
    $hostname = strtolower(trim($hostname));

    $device = [
      'hostname' => $hostname,
      'sysName'  => $hostname,
      'status'   => '1',
    ];

    // Add snmp params & snmp extra
    $snmp_params = [
      'port', 'transport', 'version', 'timeout', 'retries', 'maxrep',
      'community', 'authlevel', 'authname', 'authpass', 'authalgo',
      'cryptopass', 'cryptoalgo', 'context',
    ];
    foreach ($snmp_params as $param) {
        $snmp_param = 'snmp_' . $param;
        if (isset($snmp[$snmp_param])) {
            // Or $snmp_v3['snmp_authlevel']
            $device[$snmp_param] = $snmp[$snmp_param];
        } elseif (isset($snmp[$param])) {
            // Or $snmp_v3['authlevel']
            $device[$snmp_param] = $snmp[$param];
        } elseif ($param === 'port') {
            $device[$snmp_param] = 161;
        } elseif ($param === 'transport') {
            $device[$snmp_param] = 'udp';
        } else {
            //$device[$snmp_param] = [ 'NULL' ];
        }
    }

    // Append SNMPable oids if passed
    if (isset($snmp['snmpable'])) {
        $device['snmpable'] = $snmp['snmpable'];
    }

    // Local poller id (for distributed system)
    $poller_id = $GLOBALS['config']['poller_id']; // $config['poller_id'] sets in sql-config.php
    if (isset($GLOBALS['config']['poller_name']) &&
        $poller_local_id = dbFetchCell("SELECT `poller_id` FROM `pollers` WHERE `poller_name` = ?", [$GLOBALS['config']['poller_name']])) {
        $poller_local = $poller_id == $poller_local_id;
    } else {
        $poller_local = $poller_id == 0;
    }
    $device['poller_id'] = $poller_id;

    // FIXME. Need ability for requests from remote poller or queue to remote
    if ($poller_local) {
        // SNMP requests only on local poller
        $device['os']           = get_device_os($device);
        $device['sysObjectID']  = snmp_cache_sysObjectID($device);
        $device['snmpEngineID'] = snmp_cache_snmpEngineID($device);
        $device['sysName']      = snmp_get_oid($device, 'sysName.0', 'SNMPv2-MIB');
        $device['location']     = snmp_get_oid($device, 'sysLocation.0', 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_UTF8);
        $device['sysContact']   = snmp_get_oid($device, 'sysContact.0', 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_UTF8);
    }

    if (!$poller_local || $device['os']) {
        $device_id = dbInsert($device, 'devices');
        if ($device_id) {
            $device['device_id'] = $device_id;

            $log_msg = "Device added: $hostname";
            if ($poller_id > 0 &&
                $poller_name = dbFetchCell("SELECT `poller_name` FROM `pollers` WHERE `poller_id` = ?", [$poller_id])) {
                // Append poller name
                $log_msg .= " (Poller: $poller_name [$poller_id])";
            }
            log_event($log_msg, $device_id, 'device', $device_id, 5); // severity 5, for logging user/console info
            if (isset($device['sysObjectID']) && strlen($device['sysObjectID'])) {
                log_event('sysObjectID -> ' . $device['sysObjectID'], $device, 'device', $device_id);
            }
            if (isset($device['snmpEngineID']) && strlen($device['snmpEngineID'])) {
                log_event('snmpEngineID -> ' . $device['snmpEngineID'], $device, 'device', $device_id);
            }

            if ($poller_local && is_cli()) {
                print_success("Now discovering " . $device['hostname'] . " (id = " . $device_id . ")");
                $device['device_id'] = $device_id;
                // Discover things we need when linking this to other hosts.
                discover_device($device, $options = ['m' => 'os,mibs,ports,ip-addresses']);
                // Reset `last_discovered` for full rediscover device by cron
                dbUpdate(['last_discovered' => 'NULL'], 'devices', '`device_id` = ?', [$device_id]);
            }

            // Request for clear WUI cache
            set_cache_clear('wui');

            return ($device_id);
        }
    }

    return FALSE;
}

/**
 * Deletes device from database and RRD dir.
 *
 * @param int  $id
 * @param bool $delete_rrd
 *
 * @return false|string
 */
function delete_device($id, $delete_rrd = FALSE)
{
    global $config;

    $ret    = PHP_EOL;
    $device = device_by_id_cache($id);
    $host   = $device['hostname'];

    if (!is_array($device)) {
        return FALSE;
    } else {
        $ports = dbFetchRows("SELECT * FROM `ports` WHERE `device_id` = ?", [$id]);
        if (!empty($ports)) {
            $ret           .= ' * Deleted interfaces: ';
            $deleted_ports = [];
            foreach ($ports as $int_data) {
                $int_if = $int_data['ifDescr'];
                $int_id = $int_data['port_id'];
                delete_port($int_id, $delete_rrd);
                $deleted_ports[] = "id=$int_id ($int_if)";
            }
            $ret .= implode(', ', $deleted_ports) . PHP_EOL;
        }

        // Remove entities from common tables
        $deleted_entities = [];
        foreach (get_device_entities($id) as $entity_type => $entity_ids) {
            foreach ($config['entity_tables'] as $table) {
                $where        = '`entity_type` = ?' . generate_query_values_and($entity_ids, 'entity_id');
                $table_status = dbDelete($table, $where, [$entity_type]);
                if ($table_status) {
                    $deleted_entities[$entity_type] = 1;
                }
            }
        }
        if (count($deleted_entities)) {
            $ret .= ' * Deleted common entity entries linked to device: ';
            $ret .= implode(', ', array_keys($deleted_entities)) . PHP_EOL;
        }

        $deleted_tables = [];
        $ret            .= ' * Deleted device entries from tables: ';
        foreach ($config['device_tables'] as $table) {
            $where        = '`device_id` = ?';
            $table_status = dbDelete($table, $where, [$id]);
            if ($table_status) {
                $deleted_tables[] = $table;
            }
        }

        // Remove autodiscovery entries
        $table_status = dbDelete('autodiscovery', '`remote_device_id` = ?', [$id]);
        if ($table_status) {
            $deleted_tables[] = 'autodiscovery';
        }

        if (count($deleted_tables)) {
            $ret .= implode(', ', $deleted_tables) . PHP_EOL;

            // Request for clear WUI cache
            set_cache_clear('wui');
        }

        if ($delete_rrd) {
            $device_rrd = rtrim(get_rrd_path($device, ''), '/');
            if (is_file($device_rrd . '/status.rrd')) {
                external_exec("rm -rf " . escapeshellarg($device_rrd));
                $ret .= ' * Deleted device RRDs dir: ' . $device_rrd . PHP_EOL;
            }

        }

        log_event("Deleted device: $host", NULL, 'info', $id, 5); // severity 5, for logging user/console info
        $ret .= " * Deleted device: $host";
    }

    return $ret;
}

function device_status_array(&$device)
{
    global $attribs;

    $flags = OBS_DNS_ALL;
    if ($device['snmp_transport'] === 'udp6' || $device['snmp_transport'] === 'tcp6') { // Exclude IPv4 if used transport 'udp6' or 'tcp6'
        $flags ^= OBS_DNS_A;
    }

    $attribs['ping_skip'] = isset($attribs['ping_skip']) && $attribs['ping_skip'];
    if ($attribs['ping_skip']) {
        $flags |= OBS_PING_SKIP; // Add skip ping flag
    }

    $device['status_pingable'] = is_pingable($device['hostname'], $flags);
    $device['pingable']        = $device['status_pingable']; // Compat
    if ($device['status_pingable']) {
        $device['status_snmpable'] = is_snmpable($device);
        if ($device['status_snmpable']) {
            $ping_msg = ($attribs['ping_skip'] ? '' : 'PING (' . $device['status_pingable'] . 'ms) and ');

            //print_cli_data("Device status", "Device is reachable by " . $ping_msg . "SNMP (".$device['status_snmpable']."ms)", 1);
            $status_message = "Device is reachable by " . $ping_msg . "SNMP (" . $device['status_snmpable'] . "ms)";
            $status         = "1";
            $status_type    = 'ok';
        } else {
            //print_cli_data("Device status", "Device is not responding to SNMP requests", 1);
            $status_message = "Device is not responding to SNMP requests";
            $status         = "0";
            $status_type    = 'snmp';
        }
    } else {
        //print_cli_data("Device status", "Device is not responding to PINGs", 1);
        $status_message = "Device is not responding to PINGs";
        $status         = "0";
        //print_vars(get_status_var('ping_dns'));
        if (isset_status_var('ping_dns') && get_status_var('ping_dns') !== 'ok') {
            $status_message = "Device hostname is not resolved";
            $status_type    = 'dns';
        } else {
            $status_type = 'ping';
        }
    }

    return ['status' => $status, 'status_type' => $status_type, 'message' => $status_message];
}

/**
 * Return device name based on default hostname setting, ie purpose, descr, sysname
 *
 * @param array   $device  Device array
 * @param integer $max_len Maximum length for returned name.
 *
 * @return string
 */
function device_name($device, $max_len = FALSE)
{
    global $config;

    switch (strtolower($config['web_device_name'])) {
        case 'sysname':
            $name_field = 'sysName';
            break;
        case 'purpose':
        case 'descr':
        case 'description':
            $name_field = 'purpose';
            break;
        default:
            $name_field = 'hostname';
    }

    if ($max_len && !is_intnum($max_len)) {
        $max_len = $config['short_hostname']['length'];
    }

    if ($name_field !== 'hostname' && !safe_empty($device[$name_field])) {
        if ($name_field === 'sysName' && $max_len && $max_len > 3) {
            // short sysname when is valid hostname (do not escape here)
            return short_hostname($device[$name_field], $max_len, FALSE);
        }
        return $device[$name_field];
    }

    if ($max_len && $max_len > 3) {
        // short hostname (do not escape here)
        return short_hostname($device['hostname'], $max_len, FALSE);
    }
    return $device['hostname'];
}

/**
 * Return device hostname or ip address, based on setting $config['use_ip']
 *
 * @param array   $device   Device array
 * @param boolean $brackets When TRUE, return IPv6 addresses with square brackets
 *
 * @return string
 */
function device_host($device, $brackets = FALSE)
{
    // Return cached IP (only for poller, other processes not resolve IPs)
    if (OBS_PROCESS_NAME === 'poller' && $GLOBALS['config']['use_ip'] && !safe_empty($device['ip'])) {
        // Return IPv6 addresses with square brackets
        return ($brackets && str_contains($device['ip'], ':') ? '[' . $device['ip'] . ']' : $device['ip']);
    }

    // Use brackets when hostname is just IPv6 address
    return ($brackets && str_contains($device['hostname'], ':') ? '[' . $device['hostname'] . ']' : $device['hostname']);
    //return $device['hostname'];
}

/**
 * If a device is up, return its uptime, otherwise return the
 * time since the last time we were able to poll it.  This
 * is not very accurate, but better than reporting what the
 * uptime was at some time before it went down.
 *
 * @param array  $device
 * @param string $format
 *
 * @return string
 */
// TESTME needs unit testing
function device_uptime($device, $format = "long")
{
    if ($device['status'] == 0) {
        if ($device['last_polled'] == 0) {
            return "Never polled";
        }

        $since = time() - strtotime($device['last_polled']);
        //$reason = isset($device['status_type']) && $format == 'long' ? '('.strtoupper($device['status_type']).') ' : '';
        $reason = isset($device['status_type']) ? '(' . strtoupper($device['status_type']) . ') ' : '';

        return "Down $reason" . format_uptime($since, $format);
    }

    return format_uptime($device['uptime'], $format);
}

function device_rebooted($device, &$uptimes) {
    global $config;

    // Notify only if current uptime less than one week (eg if changed from sysUpTime to snmpEngineTime)
    $rebooted = 0;
    if ($uptimes['uptime'] < 604800) {
        if ($uptimes['diff'] > 60) {
            // If the difference between old uptime ($device['uptime']) and new ($uptime)
            // greater than 60 sec, than device truly rebooted
            $rebooted = 1;
        } elseif ($uptimes['previous'] < 300 && $uptimes['diff'] !== 0 && abs($uptimes['diff']) < 280) {
            // This is a rare boundary case, when device rebooted multiple times between polling runs
            // diff 0 mean we monitor snmp dump
            $rebooted = 1;
        }

        // Fix a reboot flag with some borderline states (roll over counters)
        if ($rebooted) {
            switch ($uptimes['use']) {
                case 'hrSystemUptime':
                case 'sysUpTime':
                    $uptimes_max = [ 42949673 ];   // 497 days 2 hours 27 minutes 53 seconds, counter 2^32 (4294967296) divided by 100
                    break;
                case 'snmpEngineTime':
                    $uptimes_max = [ 2147483647 ]; // Average 68.05 years, counter is 2^32 (4294967296) divided by 2
                    break;
                default:
                    // By default, uptime limited only by PHP max values
                    // Usually int(2147483647) in 32 bit systems and int(9223372036854775807) in 64 bit systems
                    $uptimes_max = [ PHP_INT_MAX ];
            }
            if (isset($config['os'][$device['os']]['uptime_max'][$uptimes['use']])) {
                // Add rollover counter time from definitions
                $uptimes_max = array_merge($uptimes_max, (array)$config['os'][$device['os']]['uptime_max'][$uptimes['use']]);
            }
            // Exclude uptime counter rollover
            /**
             * Examples with APC PDU (rollover max sysUpTime is 49 days, 17h 2m 47s):
             * 1. rebooted uptime previous: 49 days, 16h 52m 18s
             *               less than max:              10m 29s
             * 2. rebooted uptime previous: 49 days, 16h 54m 50s
             *               less than max:               7m 57s
             */
            foreach ($uptimes_max as $max) {
                // Exclude 660 sec (11 min) from maximal
                if ($uptimes['previous'] > ($max - 660) && $uptimes['previous'] <= $max) {
                    $uptimes['max'] = $max;
                    $rebooted       = 0;
                    break;
                }
            }
        }
    }
    $uptimes['rebooted'] = $rebooted;

    return $rebooted;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function device_by_name($name, $refresh = FALSE)
{
    // FIXME - cache name > id too.
    return device_by_id_cache(get_device_id_by_hostname($name), $refresh);
}

/**
 * Get localhost device if exist in db
 */
function get_localhost_id($where = '') {
    $localhost = get_localhost();

    if ($dev_id = dbFetchCell('SELECT `device_id` FROM `devices`' . generate_where_clause('`hostname` = ? AND `disabled` = 0', $where), [ $localhost ])) {
        return $dev_id;
    }
    if (safe_empty($where)) {
        $dev_id = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `sysName` = ? AND `disabled` = 0', [ $localhost ]);
    } else {
        $shorthost = explode('.', $localhost)[0];
        $dev_id = dbFetchCell('SELECT `device_id` FROM `devices`' . generate_where_clause('(`sysName` = ? OR `sysName` = ?) AND `disabled` = 0', $where), [ $localhost, $shorthost ]);
    }

    return $dev_id;
}

/**
 * Returns a device_id when given an entity_id and an entity_type. Returns FALSE if the device isn't found.
 *
 * @param $entity_id
 * @param $entity_type
 *
 * @return bool|integer
 */
function get_device_id_by_entity_id($entity_id, $entity_type)
{
    if ($entity_type === 'device') {
        return $entity_id;
    }

    // $entity = get_entity_by_id_cache($entity_type, $entity_id);
    $translate = entity_type_translate_array($entity_type);

    if (isset($translate['device_id_field']) && $translate['device_id_field'] &&
        is_numeric($entity_id) && $entity_type) {
        $device_id = dbFetchCell('SELECT `' . $translate['device_id_field'] . '` FROM `' . $translate['table'] . '` WHERE `' . $translate['id_field'] . '` = ?', [$entity_id]);
    }
    if (isset($device_id) && is_numeric($device_id)) {
        return $device_id;
    } else {
        //bdump([$entity_id, $entity_type]);
    }

    return FALSE;
}

function get_device_id_by_syslog_host($host) {
    global $config;

    // Try by map in config
    if (isset($config['syslog']['host_map'][$host])) {
        $new_host = $config['syslog']['host_map'][$host];
        if (is_numeric($new_host)) {
            // Check if device id exist
            $dev_id = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `device_id` = ?', [ $new_host ]);
        } else {
            $dev_id = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `hostname` = ? OR `sysName` = ?', [ $new_host, $new_host ]);
        }
        // If syslog host map correct, return device id or try onward
        if ($dev_id) {
            return $dev_id;
        }
    } elseif (isset($config['syslog']['host_map_regexp'])) {
        // Regexp conversions for hosts
        foreach ($config['syslog']['host_map_regexp'] as $pattern => $to) {
            $new_host = preg_replace($pattern, $to, $host);
            if (!$new_host || $new_host === $host) {
                continue;
            } // skip same of false
            $dev_id = FALSE;
            if (is_intnum($new_host)) {
                $dev_id = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `device_id` = ?', [ $new_host ]);
            } elseif (is_valid_hostname($new_host) || get_ip_version($new_host)) {
                $dev_id = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `hostname` = ? OR `sysName` = ?', [ $new_host, $new_host ]);
            }

            // If syslog host map correct, return device id or try onward
            if ($dev_id) {
                //print_cli("'$host' -> '$new_host' ($dev_id)");
                return $dev_id;
            }
        }
    }

    // Localhost IPs, try to detect as local system
    if (in_array($host, [ '127.0.0.1', '::1', '0:0:0:0:0:0:0:1', '0000:0000:0000:0000:0000:0000:0000:0001' ], TRUE)) {
        if ($dev_id = get_localhost_id()) {
            return $dev_id;
        }
        // NOTE in other cases localhost IPs associated with random device
    } else {
        // Try by hostname
        $dev_id = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `hostname` = ? OR `sysName` = ?', [ $host, $host ]);
        if (!$dev_id && is_valid_hostname($host, TRUE)) {
            // Try short hostname host.domain -> host (only by hostname field)
            $short_host = explode('.', $host)[0];
            $dev_id = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `hostname` = ?', [ $short_host ]);
        }
    }

    // If failed, try by IP
    if (!is_numeric($dev_id)) {

        if ($ip_version = get_ip_version($host)) {
            $ip = $host;
            if ($ip_version === 6 && preg_match('/::ffff:(\d+\.\d+\.\d+\.\d+)/', $ip, $matches)) {
                // IPv4 mapped to IPv6, like ::ffff:192.0.2.128
                // See: http://jira.observium.org/browse/OBSERVIUM-1274
                $ip         = $matches[1];
                $ip_version = 4;
            }
            $ip = ip_uncompress($ip);

            // Detect associated device by IP address, exclude deleted ports
            // IS NULL allow to search addresses without associated port
            $query         = 'SELECT * FROM `ipv' . $ip_version . '_addresses` LEFT JOIN `ports` USING (`device_id`, `port_id`) WHERE `ipv' . $ip_version . '_address` = ? AND (`ports`.`deleted` = ? OR `ports`.`deleted` IS NULL);';
            $addresses     = dbFetchRows($query, [ $ip, 0 ]);
            $address_count = safe_count($addresses);

            // Try to check cached IP addresses
            if ($address_count === 0 && $config['syslog']['use_ip']) {
                // Cached IPs compressed
                $query         = 'SELECT `device_id`, `hostname`, `disabled`, `status` FROM `devices` WHERE `ip` = ?';
                $addresses     = dbFetchRows($query, [ ip_compress($host) ]);
                $address_count = safe_count($addresses);
            }

            if ($address_count) {
                $dev_id = $addresses[0]['device_id'];

                // Additional checks if multiple addresses found
                if ($address_count > 1) {
                    foreach ($addresses as $entry) {
                        if (isset($entry['ifAdminStatus'])) {
                            // IP & ports table query
                            $device_tmp = device_by_id_cache($entry['device_id']);
                            if ($device_tmp['disabled'] || !$device_tmp['status']) {              // Skip disabled and down devices
                                continue;
                            }
                            if ($entry['ifAdminStatus'] === 'down' ||                             // Skip disabled ports
                                in_array($entry['ifOperStatus'], [ 'down', 'lowerLayerDown' ])) { // Skip down ports
                                continue;
                            }
                        } elseif ($entry['disabled'] || !$entry['status']) {                      // Skip disabled and down devices
                            // Cached IP & devices table query
                            continue;
                        }

                        // Override cached host device_id
                        $dev_id = $entry['device_id'];
                        break; // End loop on first founded entry
                    }
                    unset($device_tmp);
                }

            }

        } elseif ($config['syslog']['use_ip'] && is_valid_hostname($host)) {
            // Try associate hosts by DNS IP query
            $dns_found = FALSE;
            $dns_ip = gethostbyname6($host, OBS_DNS_A); // IPv4
            if (!$dns_ip) {
                $dns_ip = gethostbyname6($host, OBS_DNS_AAAA); // IPv6
            }
            if ($dns_ip) {
                $query = 'SELECT `device_id`, `hostname`, `disabled`, `status` FROM `devices` WHERE `ip` = ?';
                if ($addresses = dbFetchRows($query, [ ip_compress($dns_ip) ])) {
                    $dns_found = TRUE;
                    $dev_id = $addresses[0]['device_id'];

                    foreach ($addresses as $entry) {
                        if ($entry['disabled'] || !$entry['status']) { // Skip disabled and down devices
                            // Cached IP & devices table query
                            continue;
                        }

                        // Override cached host device_id
                        $dev_id = $entry['device_id'];
                        break; // End loop on first founded entry
                    }
                }
            }

            if (!$dns_found) {
                // Set empty device_id for prevent other DNS queries while not expired
                $dev_id = 0;
            }
        }
    }

    return $dev_id;
}

/**
 * Get cached/humanized device array
 *
 * @param integer|string $device_id
 * @param boolean $refresh
 *
 * @return array|mixed
 */
function device_by_id_cache($device_id, $refresh = FALSE)
{
    global $cache;

    if (!is_intnum($device_id)) {
        return FALSE;
    }

    if (!$refresh &&
        isset($cache['devices']['id'][$device_id]) && is_array($cache['devices']['id'][$device_id]))
    {
        $device = $cache['devices']['id'][$device_id];
        // Note, cached $device can be not humanized (by cache-data)
        $refresh = !isset($device['humanized']);
    } else {
        if ($GLOBALS['config']['geocoding']['enable']) {
            $query = "SELECT * FROM `devices` LEFT JOIN `devices_locations` USING (`device_id`)";
        } else {
            $query = "SELECT * FROM `devices`";
        }
        $device  = dbFetchRow($query . " WHERE `device_id` = ?", [ $device_id ]);
        $refresh = TRUE; // Set refresh
    }

    if (!empty($device) && $refresh) {
        humanize_device($device);
        get_device_graphs($device);

        // Add to memory cache
        $cache['devices']['id'][$device_id] = $device;
    }

    return $device;
}

function get_device_graphs(&$device)
{
    //if ($refresh || !isset($device['graphs']))
    //{
    // Fetch device graphs
    foreach (dbFetchRows("SELECT * FROM `device_graphs` WHERE `device_id` = ?", [$device['device_id']]) as $graph) {
        $device['graphs'][$graph['graph']] = $graph;
    }
    //}

}


// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_hostname($hostname) {
    global $cache;

    $id = $cache['devices']['hostname'][$hostname] ?? dbFetchCell("SELECT `device_id` FROM `devices` WHERE `hostname` = ?", [ $hostname ]);

    if (is_numeric($id)) {
        return $id;
    }

    return FALSE;
}

function get_device_hostname_by_id($id) {
    global $cache;

    if (is_array($id)) {
        if (isset($id['hostname'])) {
            return $id['hostname'];
        }
        $id = $id['device_id'];
    }
    if (is_intnum($id)) {
        return FALSE;
    }

    return $cache['devices']['hostname_map'][$id] ?? dbFetchCell("SELECT `hostname` FROM `devices` WHERE `device_id` = ?", [ $id ]);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_port_id($port_id)
{
    if (is_intnum($port_id)) {
        $device_id = dbFetchCell("SELECT `device_id` FROM `ports` WHERE `port_id` = ?", [$port_id]);
        if (is_numeric($device_id)) {
            return $device_id;
        }
    }

    return FALSE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_mac($mac, $exclude_device_id = NULL)
{
    $remote_mac = mac_zeropad($mac);
    if ($remote_mac && $remote_mac !== '000000000000') {
        $where  = '`deleted` = ? AND `ifPhysAddress` = ?';
        $params = [0, $remote_mac];
        if (is_numeric($exclude_device_id)) {
            $where    .= ' AND `device_id` != ?';
            $params[] = $exclude_device_id;
        }
        $device_id = dbFetchCell("SELECT `device_id` FROM `ports` WHERE $where LIMIT 1;", $params);
        if (is_numeric($device_id)) {
            return $device_id;
        }
    }

    return FALSE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_app_id($app_id)
{
    if (is_numeric($app_id) &&
        $device_id = dbFetchCell("SELECT `device_id` FROM `applications` WHERE `app_id` = ?", [$app_id])) {

        return $device_id;
    }
    return FALSE;
}

function get_device_vrf_contexts($device) {
    if (!safe_empty($device['snmp_context'])) {
        // Device already configured with snmp context
        return NULL;
    }
    if (isset($GLOBALS['config']['os'][$device['os']]['snmp']['virtual']) &&
        $GLOBALS['config']['os'][$device['os']]['snmp']['virtual']) {
        // Context permitted for os
        return safe_json_decode(get_entity_attrib('device', $device, 'vrf_contexts'));
    }
    return NULL;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_entphysical_state($device)
{
    $state = [];
    foreach (dbFetchRows("SELECT * FROM `entPhysical-state` WHERE `device_id` = ?", [$device]) as $entity) {
        $state['group'][$entity['group']][$entity['entPhysicalIndex']][$entity['subindex']][$entity['key']] = $entity['value'];
        $state['index'][$entity['entPhysicalIndex']][$entity['subindex']][$entity['group']][$entity['key']] = $entity['value'];
    }

    return $state;
}

/**
 * Return model array from definitions, based on device sysObjectID
 *
 * @param array  $device          Device array required keys -> os, sysObjectID
 * @param string $sysObjectID_new If passed, then use "new" sysObjectID instead from device array
 *
 * @return array|FALSE            Model array or FALSE if no model specific definitions
 */
function get_model_array($device, $sysObjectID_new = NULL)
{
    global $config, $cache;

    if (!isset($config['os'][$device['os']]['model'])) {
        return FALSE;
    }

    $model = $config['os'][$device['os']]['model'];
    if (!isset($config['model'][$model])) {
        return FALSE;
    }

    $models    = $config['model'][$model];
    $set_cache = FALSE;
    if ($sysObjectID_new && preg_match('/^\.\d[\d\.]+$/', $sysObjectID_new)) {
        // Use passed as param sysObjectID
        $sysObjectID = $sysObjectID_new;
    } elseif (isset($cache['devices']['model'][$device['device_id']])) {
        // Return already cached array if no passed param sysObjectID
        return $cache['devices']['model'][$device['device_id']];
    } elseif (preg_match('/^\.\d[\d\.]+$/', $device['sysObjectID'])) {
        // Use sysObjectID from device array
        $sysObjectID = $device['sysObjectID'];
        $set_cache   = TRUE;
    } else {
        // Just random non empty string
        $sysObjectID = 'empty_sysObjectID_3948ffakc';
        $set_cache   = TRUE;
    }

    if ($set_cache && (!is_numeric($device['device_id']) || defined('__PHPUNIT_PHAR__'))) {
        // Do not set cache for unknown device_id (not added device) or phpunit
        $set_cache = FALSE;
    }

    // Exactly sysObjectID match
    if (isset($models[$sysObjectID])) {
        // Check by device params, ie os version (see Juniper EX model definitions)
        if (isset($models[$sysObjectID]['test'])) {
            // Single associated array with test condition
            $models[$sysObjectID] = [$models[$sysObjectID]];
        }

        if (!is_array_assoc($models[$sysObjectID])) {
            // For cases when multiple test conditions for same sysObjectID
            $model_def = FALSE;
            foreach ($models[$sysObjectID] as $def) {
                if (discovery_check_requires($device, $def, $device, 'device')) {
                    continue;
                }

                $model_def = $def;
                break;
            }
            $models[$sysObjectID] = $model_def;
        }

        if ($set_cache) {
            $cache['devices']['model'][$device['device_id']] = $models[$sysObjectID];
        }
        //r($models[$sysObjectID]);
        print_debug("Found device [{$device['device_id']}] model '$model' exact association for $sysObjectID");
        return $models[$sysObjectID];
    }

    // Resort sysObjectID array by oids with from high to low order!
    uksort($config['model'][$model], 'compare_numeric_oids_reverse');
    foreach ($config['model'][$model] as $key => $entry) {
        $id_partial = $key;
        if (substr($id_partial, -1) !== '.') {
            $id_partial .= '.';
        }
        if (strpos($sysObjectID, $id_partial) === 0) {
            // Check by device params, ie os version (see Juniper EX model definitions)
            if (isset($entry['test'])) {
                // Single associated array with test condition
                $entry = [$entry];
            }
            if (!is_array_assoc($entry)) {
                // For cases when multiple test conditions for same sysObjectID
                $model = FALSE;
                foreach ($entry as $def) {
                    if (discovery_check_requires($device, $def, $device, 'device')) {
                        continue;
                    }

                    $model = $def;
                    break;
                }
                $entry = $model;
            }

            if ($set_cache) {
                $cache['devices']['model'][$device['device_id']] = $entry;
            }
            print_debug("Found device [{$device['device_id']}] model '$model' average association for $sysObjectID -> $id_partial*");
            return $entry;
        }
    }

    // If model array not found, set cache entry to FALSE, for do not search again
    if ($set_cache) {
        $cache['devices']['model'][$device['device_id']] = FALSE;
    }

    print_debug("Not found device [{$device['device_id']}] model '$model' association for $sysObjectID");
    return FALSE;
}

/**
 * Get a device param based on device os/sysObjectID and model definitions
 *
 * @param array  $device          Device array required keys -> os, sysObjectID
 * @param string $param           Requested parameter, ie 'hardware', 'type', 'vendor'
 * @param string $sysObjectID_new If passed, then use "new" sysObjectID instead from device
 *
 * @return string|null Device model parameter or empty string
 */
function get_model_param($device, $param, $sysObjectID_new = NULL) {
    $model_array = get_model_array($device, $sysObjectID_new);

    if ($param === 'hardware' || empty($param)) {
        $param = 'name';
    }
    if (is_array($model_array) && isset($model_array[$param])) {
        return $model_array[$param];
    }
    return NULL;
}

/**
 * Generates list of possible device hostnames for external integrations (ie RANCID, Oxidized)
 *
 * @param array|string $device
 * @param array|string $suffix
 * @param bool         $dprint
 *
 * @return array
 */
function generate_device_hostnames($device, $suffix = '', $dprint = FALSE)
{
    global $config;

    $hostnames = [];

    if (is_intnum($device)) {
        $device = device_by_id_cache($device);
        if ($dprint) {
            echo "Device: get by device id.<br />";
        }
    } elseif (is_string($device)) {
        $hostname = $device;
        $device   = device_by_name($device);

        // Device by IP
        if (!$device && get_ip_version($hostname) &&
            $ids = get_entity_ids_ip_by_network('device', $hostname)) {
            $device = device_by_id_cache($ids[0]);
            if ($dprint) {
                echo "Device: get by device IP.<br />";
            }
        } elseif ($dprint) {
            echo "Device: get by hostname.<br />";
        }
    }

    // Not valid device
    if (!is_array($device) || !isset($device['hostname'])) {
        if ($dprint) {
            echo "Device: No valid device array or hostname passed.<br />";
        }
        return $hostnames;
    }

    // Base hostname
    $hostname    = $device['hostname'];
    $hostnames[] = $device['hostname'];
    if ($dprint) {
        echo("Hostname: $hostname<br />");
    }

    // Also check non-FQDN hostname.
    $is_ip = (bool)get_ip_version($hostname);
    if (!$is_ip && str_contains($hostname, '.')) {
        [$shortname] = explode('.', $hostname);

        $hostnames[] = $shortname;
        if ($dprint) {
            echo("Short hostname: $shortname<br />");
        }
    }

    // Addition of a domain suffix for non-FQDN device names.
    if (!$is_ip && !safe_empty($suffix)) {
        foreach ((array)$suffix as $append) {
            $fqdn = $hostname . '.' . trim($append, ' .');
            if (is_valid_hostname($fqdn, TRUE)) {
                $hostnames[] = $fqdn;
                if ($dprint) {
                    echo("Hostname suffix passed, also looking for " . $fqdn . "<br />");
                }
            }
        }
    }

    // Device sysName
    $sysname = strtolower($device['sysName']);
    if (!in_array($sysname, $hostnames, TRUE) &&
        !in_array($sysname, (array)$config['devices']['ignore_sysname'], TRUE) &&
        is_valid_hostname($sysname)) {

        $hostnames[] = $sysname;
        if ($dprint) {
            echo("sysName: $sysname<br />");
        }

        // Addition of a domain suffix for non-FQDN device sysName.
        if (!str_contains($sysname, '.') && !safe_empty($suffix)) {
            foreach ((array)$suffix as $append) {
                $fqdn = $sysname . '.' . trim($append, ' .');
                if (!in_array($fqdn, $hostnames, TRUE) &&
                    is_valid_hostname($fqdn, TRUE)) {
                    $hostnames[] = $fqdn;
                    if ($dprint) {
                        echo("Hostname suffix passed, also looking for " . $fqdn . "<br />");
                    }
                }
            }
        }
    }

    return $hostnames;
}

/**
 * Generate device tags for use with some entities (ie probes)
 *
 * @param array $device
 * @param bool  $escape
 *
 * @return array
 */
function generate_device_tags($device, $escape = TRUE)
{
    $params = [
        // Basic
        'hostname',
        // SNMP
        'snmp_version', 'snmp_community', 'snmp_context', 'snmp_authlevel', 'snmp_authname', 'snmp_authpass', 'snmp_authalgo',
        'snmp_cryptopass', 'snmp_cryptoalgo', 'snmp_port', 'snmp_timeout', 'snmp_retries'
    ];

    $device_tags = [];

    foreach ($params as $variable) {
        if (!empty($device[$variable]) || in_array($variable, ['snmp_timeout', 'snmp_authname'])) {
            switch ($variable) {
                case 'snmp_version':
                    $device_tags[$variable] = str_replace('v', '', $device[$variable]);
                    break;
                case 'snmp_authalgo':
                case 'snmp_cryptoalgo':
                    $device_tags[$variable] = strtoupper($device[$variable]);
                    break;
                case 'snmp_authname':
                    // Always pass username, default observium (need for noathnopriv)
                    $device_tags[$variable] = strlen($device[$variable]) ? $device[$variable] : 'observium';
                    break;
                case 'snmp_timeout':
                    // Always pass timeout, default 15
                    $device_tags[$variable] = is_numeric($device[$variable]) ? $device[$variable] : 15;
                    break;
                default:
                    $device_tags[$variable] = $device[$variable];
            }
            // Escape for pass to shell cmd
            if ($escape) {
                $device_tags[$variable] = escapeshellarg($device_tags[$variable]);
            }
        }
    }

    return $device_tags;
}

/**
 * Filter SNMP secrets from $device array
 *
 * @param array $device De
 * @param boolean $filter When TRUE, filter device secrets
 * @param string|null $replace When non-empty replace secrets with string
 *
 * @return void
 */
function device_filter_secrets(&$device, $filter = FALSE, $replace = NULL) {
    if ($filter) {
        // Filter secrets
        foreach ([ 'snmp_community', 'snmp_authlevel', 'snmp_authname', 'snmp_authpass',
                   'snmp_authalgo', 'snmp_cryptopass', 'snmp_cryptoalgo', 'snmp_context' ] as $field) {
            if (safe_empty($replace)) {
                unset($device[$field]);
            } elseif (isset($device[$field])) {
                $device[$field] = $replace;
            }
        }

    }
}

/**
 * Poll device metatypes, see os, system and wifi modules.
 *
 * @param array $device    Device array
 * @param array $metatypes List of required metatypes
 * @param array $poll_device
 *
 * @return array
 */
function poll_device_mib_metatypes($device, $metatypes, &$poll_device = []) {
    global $config;

    $return_values = [];
    foreach ($metatypes as $metatype) {

        switch ($metatype) {
            case 'sysUpTime':
                $param = 'uptime'; // For sysUptime use simple param name
                break;
            case 'wifi_clients':
                $param    = 'wifi_clients';
                $metatype = 'wifi_clients1';
                break;
            default:
                $param = strtolower($metatype);
        }

        foreach (get_device_mibs_permitted($device) as $mib) { // Check every MIB supported by the device, in order
            if (isset($config['mibs'][$mib][$param])) {
                $metatype_uptime = $metatype === 'sysUpTime' || $metatype === 'reboot';

                foreach ($config['mibs'][$mib][$param] as $entry) {
                    // For ability override metatype by vendor mib definition use 'force' boolean param
                    if (!isset($poll_device[$metatype]) ||                     // Poll if metatype not already set
                        $metatype_uptime ||                                    // Or uptime/reboot (always polled)
                        (isset($entry['force']) && $entry['force']) ||         // Or forced override (see ekinops EKINOPS-MGNT2-MIB mib definition
                        !is_valid_param($poll_device[$metatype], $metatype)) { // Poll if metatype not valid by previous round

                        if (discovery_check_requires_pre($device, $entry, 'device')) {
                            // Examples in RFC UPS-MIB, QNAP NAS-MIB and APC PowerNet-MIB
                            print_debug("Definition entry [$metatype] [$mib::" . $entry['oid'] . $entry['oid_next'] . $entry['oid_count'] . "] skipped by pre test condition");
                            continue;
                        }

                        // Force HEX to UTF8 conversion by default
                        $flags = $entry['snmp_flags'] ?? OBS_SNMP_ALL_UTF8;

                        if (isset($entry['table'])) {
                            // Get Oid by table walk (with possible tests)
                            $value = NULL;
                            foreach (snmp_cache_table($device, $entry['table'], [], $mib, NULL, $flags) as $index => $values) {
                                if (!isset($values[$entry['oid']])) {
                                    continue;
                                } // Skip unknown entries

                                if (isset($entry['test']) && discovery_check_requires($device, $entry, $values, 'device')) {
                                    // Examples in MDS-SYSTEM-MIB
                                    print_debug("Definition entry [$metatype] [$mib::" . $entry['oid'] . "] skipped by test condition");
                                    continue;
                                }
                                // if test not required, use first entry (as getnext)
                                $value    = $values[$entry['oid']];
                                $full_oid = "$mib::{$entry['oid']}.$index";
                                break;
                            }
                        } elseif (isset($entry['oid_num'])) { // Use numeric OID if set, otherwise fetch text based string
                            $value    = snmp_get_oid($device, $entry['oid_num'], NULL, $flags);
                            $full_oid = $entry['oid_num'];
                        } elseif (isset($entry['oid_next'])) {
                            // If Oid passed without index part use snmpgetnext (see FCMGMT-MIB definitions)
                            $value    = snmp_getnext_oid($device, $entry['oid_next'], $mib, NULL, $flags);
                            $full_oid = "$mib::{$entry['oid_next']}";
                        } elseif (isset($entry['oid_count'])) {
                            // This is special type of get data by snmpwalk and count entries
                            $data     = snmpwalk_values($device, $entry['oid_count'], $mib);
                            $value    = is_array($data) ? count($data) : '';
                            $full_oid = str_starts($entry['oid_count'], '.') ? $entry['oid_count'] : "$mib::{$entry['oid_count']}";
                        } else {
                            $value    = snmp_get_oid($device, $entry['oid'], $mib, NULL, $flags);
                            $full_oid = "$mib::{$entry['oid']}";
                        }

                        if (snmp_status() && !safe_empty($value)) {
                            $polled = round(snmp_endtime());

                            // Additional Oids for current metaparam (see IMM-MIB hardware definition)
                            if (isset($entry['oid_extra']) && is_valid_param($value, $metatype)) {
                                $snmp_next = isset($entry['oid_next']); // Main value use get next?
                                $extra     = [];
                                foreach ((array)$entry['oid_extra'] as $oid_extra) {
                                    //$value_extra = trim(snmp_hexstring(snmp_get_oid($device, $oid_extra, $mib)));
                                    if ($snmp_next && !str_contains($oid_extra, '.')) {
                                        $value_extra = snmp_getnext_oid($device, $oid_extra, $mib, NULL, $flags);
                                    } else {
                                        $value_extra = snmp_get_oid($device, $oid_extra, $mib, NULL, $flags);
                                    }
                                    if (snmp_status() && !safe_empty($value_extra)) {
                                        $extra[] = $value_extra;
                                    }
                                }
                                if (count($extra)) {
                                    if ($metatype === 'version' && preg_match('/^[\d\.]+$/', $value)) {
                                        // version -> xxx.y.z
                                        $value .= '.' . implode('.', $extra);
                                    } elseif ($metatype === 'sysname') {
                                        $tmp = $value . '.' . implode('.', $extra);
                                        if (is_valid_hostname($tmp, TRUE)) {
                                            // Ie: ALLOT-MIB
                                            $value = $tmp;
                                        }
                                        unset($tmp);
                                    } else {
                                        // others ->  xxx (y, z)
                                        $value .= ' (' . implode(', ', $extra) . ')';
                                    }
                                }
                                unset($oid_extra, $extra, $value_extra);
                            }

                            // Field found (no SNMP error), perform optional transformations.
                            if (isset($entry['transform'])) {
                                // Just simplify definition entry (unify with others)
                                $entry['transformations'] = $entry['transform'];
                            }
                            $value = trim(string_transform($value, $entry['transformations']));

                            // Detect uptime with MIB defined oids, see below uptimes 2.
                            if ($metatype_uptime) {
                                // Previous uptime from standard sysUpTime or other MIB::oid
                                $uptime_previous = $poll_device['device_uptime'] ?? $poll_device['sysUpTime'];
                                if ($param === 'uptime' && !isset($entry['transformations']) && !is_numeric($value)) {
                                    // Convert common uptime strings to seconds by default
                                    // See MDS-SYSTEM-MIB
                                    $value = uptime_to_seconds($value);
                                } elseif ($param === 'reboot' && is_numeric($value)) {
                                    // Last reboot is same uptime, but as diff with current (polled) time (see INFINERA-ENTITY-CHASSIS-MIB)
                                    $value = $polled - $value;
                                }
                                // Detect if new sysUpTime value more than the previous
                                if (is_numeric($value) && $value >= $uptime_previous) {
                                    $poll_device['device_uptime'] = $value;
                                    // Fixme, not know how re-add this message
                                    //$uptimes['message'] = isset($entry['oid_num']) ? $entry['oid_num'] : $mib . '::' . $entry['oid'];
                                    //$uptimes['message'] = 'Using device MIB poller '.$metatype.': ' . $uptimes['message'];

                                    print_debug("Added System Uptime from SNMP definition fetch: 'device_uptime' = '$value'");
                                    // Continue for other possible sysUpTime and use maximum value
                                    // but from different MIBs when valid time found (example UBNT-UniFi-MIB)
                                    continue 2;
                                }
                                // Continue for other possible sysUpTime and use maximum value
                                continue;
                            }

                            if (!is_valid_param($value, $metatype)) {
                                // Skip invalid values
                                continue;
                            }

                            $return_values[$metatype] = $value;
                            $poll_device[$metatype]   = $value;
                            print_debug("Added Device param from SNMP definition by [$full_oid]: '$metatype' = '$value'");

                            // Exit both foreach loops and move on to the next metatype.
                            break 2;
                        }
                    }
                }
            }
        }

    }

    print_debug_vars($return_values);
    return $return_values;
}

/**
 * Poll device metatypes by sysDescr and sysName regex
 *
 * @param array $device    Device array
 * @param array $metatypes List of required metatypes
 * @param array $os_values Append found os metatypes to array
 * @param array $poll_device Device polled data with sysDescr and sysName
 *
 * @return array Return only found metatypes and values
 */
function poll_device_sys_regex($device, $metatypes, &$os_values, $poll_device) {
    global $config;

    $return_values = [];
    foreach ([ 'sysDescr', 'sysName' ] as $oid) {
        $regex_param = $oid . '_regex'; // sysDescr_regex, sysName_regex

        if (!isset($config['os'][$device['os']][$regex_param], $poll_device[$oid])) {
            continue;
        }

        // Find OS-specific SNMP data via OID regex: serial number, version number, hardware description, features, asset tag
        foreach ($config['os'][$device['os']][$regex_param] as $pattern) {
            if (preg_match($pattern, $poll_device[$oid], $matches)) {
                foreach ($metatypes as $metatype) {
                    if (!isset($os_values[$metatype]) &&
                        isset($matches[$metatype]) && is_valid_param($matches[$metatype], $metatype)) {
                        // Skip unknown type
                        if ($metatype === 'type' && !array_key_exists($matches[$metatype], $config['devicetypes'])) {
                            continue;
                        }

                        $os_values[$metatype] = $matches[$metatype];

                        // Additional sub-data (up to 2), ie hardware1, hardware2
                        // See example in timos definition
                        for ($num = 1; $num <= 2; $num++) {
                            if (isset($matches[$metatype . $num]) && is_valid_param($matches[$metatype . $num], $metatype)) {
                                $os_values[$metatype] .= ' ' . $matches[$metatype . $num];
                            } else {
                                break;
                            }
                        }

                        $return_values[$metatype] = $os_values[$metatype]; // Set metatype variable
                        print_debug("Added OS param from $oid pattern: '$metatype' = '" . $os_values[$metatype] . "' (" . $pattern . ")");
                    }
                }
            }
        }
    }

    print_debug_vars($return_values);
    return $return_values;
}

function poll_device_unix_packages($device, $metatypes, $defs = [])
{
    global $attribs, $agent_data;

    // packages definitions
    $package_defs = [];
    if (empty($defs)) {
        // Used in poll os packages
        foreach ($GLOBALS['config']['os'][$device['os']]['packages'] as $def) {
            $package_defs[$def['name']] = $def;
        }
        $attrib_key = 'os_package_index';
    } else {
        foreach ($defs as $def) {
            $package_defs[$def['name']] = $def;
        }
        $attrib_key = 'def_package_index';
    }
    if (safe_empty($package_defs)) {
        return [];
    }

    $data = [];

    // Unix-agent packages exist?
    $agent_packages = !safe_empty($agent_data['dpkg']) || !safe_empty($agent_data['rpm']);

    if ($agent_packages) {
        // by unix-agent packages
        //$sql = 'SELECT * FROM `packages` WHERE `device_id` = ? AND `status` = ? AND `name` IN (?, ?, ?)';
        $sql    = 'SELECT * FROM `packages` WHERE `device_id` = ? AND `status` = ?';
        $sql    .= generate_query_values_and(array_keys($package_defs), 'name');
        $params = [$device['device_id'], 1];
        if ($package = dbFetchRow($sql, $params)) {
            //$name    = $package['name'];
            $def = $package_defs[$package['name']];
            print_debug_vars($def, 1);
            print_debug_vars($package, 1);

            // Type, features, version, etc
            foreach ($metatypes as $metatype) {
                if ($metatype === 'version' || $metatype === 'distro_ver') {
                    // Version
                    $data[$metatype] = $package['version'];
                    if (isset($def['transform'], $data[$metatype])) {
                        $data[$metatype] = string_transform($data[$metatype], $def['transform']);
                    }
                } elseif (isset($def[$metatype])) {
                    // all other metatypes
                    $data[$metatype] = $def[$metatype];
                }
            }
        }
    } elseif (is_device_mib($device, 'HOST-RESOURCES-MIB')) {
        // by SNMP query

        $found = FALSE;
        if (isset($attribs[$attrib_key])) {
            // fetch package version and check if indexes was not changed
            $package = snmp_get_oid($device, 'hrSWInstalledName.' . $attribs[$attrib_key], 'HOST-RESOURCES-MIB');
            if (snmp_status()) {
                foreach ($package_defs as $def) {
                    if (preg_match($def['regex'], $package, $matches)) {
                        $found = TRUE;
                        print_debug_vars($def, 1);
                        print_debug_vars($package, 1);

                        // Type, features, version, etc
                        foreach ($metatypes as $metatype) {
                            if ($metatype === 'version' || $metatype === 'distro_ver') {
                                // Version
                                if (isset($matches['version'])) {
                                    $data[$metatype] = $matches['version'];

                                    if (isset($def['transform'])) {
                                        $data[$metatype] = string_transform($data[$metatype], $def['transform']);
                                    }
                                }
                            } elseif (isset($def[$metatype])) {
                                // all other metatypes
                                $data[$metatype] = $def[$metatype];
                            }
                        }
                        break;
                    }
                }
            }

            if (!$found) {
                // packages changed, re-walk all
                print_debug("Packages changed, refetch all");
                del_entity_attrib('device', $device['device_id'], 'os_package_index');
                unset($attribs[$attrib_key]);
            }
            unset($package);
        }

        if (!$found) {
            // walk all packages
            $oids = snmpwalk_cache_oid($device, "hrSWInstalledName", [], 'HOST-RESOURCES-MIB');

            foreach ($oids as $index => $entry) {
                $package = $entry['hrSWInstalledName'];
                foreach ($package_defs as $def) {
                    if (preg_match($def['regex'], $package, $matches)) {
                        $found = TRUE;
                        print_debug_vars($def, 1);
                        print_debug_vars($entry, 1);

                        // Type, features, version, etc
                        foreach ($metatypes as $metatype) {
                            if ($metatype === 'version' || $metatype === 'distro_ver') {
                                // Version
                                if (isset($matches['version'])) {
                                    $data[$metatype] = $matches['version'];

                                    if (isset($def['transform'])) {
                                        $data[$metatype] = string_transform($data[$metatype], $def['transform']);
                                    }
                                }
                            } elseif (isset($def[$metatype])) {
                                // all other metatypes
                                $data[$metatype] = $def[$metatype];
                            }
                        }

                        // store package index for faster polling
                        if (!isset($attribs[$attrib_key]) || $attribs[$attrib_key] != $index) {
                            set_entity_attrib('device', $device['device_id'], 'os_package_index', $index);
                        }
                        break 2;
                    }
                }

            }
        }
    }

    return $data;
}

function poll_device_mib_la($device, $mib, &$device_state) {
    global $config, $graphs;

    if (!isset($config['mibs'][$mib]['la'])) {
        return FALSE;
    }
    if (isset($graphs['la']) && $graphs['la']) {
        // Already polled and found LA
        print_debug("Already polled and found LA for device. MIB $mib skipped.");
        return FALSE;
    }

    $la_def = $config['mibs'][$mib]['la'];

    // See Cisco CISCO-PROCESS-MIB
    if (discovery_check_requires_pre($device, $la_def, 'device')) {
        return FALSE;
    }

    $la = [];
    $la_oids = [];
    if (isset($la_def['type']) && $la_def['type'] === 'table') {
        // First element from table walk, with test condition, see JUNIPER-MIB
        $la_oids = [
            '1min'  => explode('.', $la_def['oid_1min'])[0],
            '5min'  => explode('.', $la_def['oid_5min'])[0],
            '15min' => explode('.', $la_def['oid_15min'])[0]
        ];
        // Fetch table or Oids
        $table_oids    = [ 'oid_1min', 'oid_5min', 'oid_15min', 'oid_extra' ];
        foreach (discover_fetch_oids($device, $mib, $la_def, $table_oids) as $index => $la_entry) {
            // Check array requirements list
            if (discovery_check_requires($device, $la_def, $la_entry, 'la')) {
                continue;
            }
            $la = $la_entry;
            break; // Stop on first test match
        }

    } else {
        // Single oid / getnext
        foreach ([ '1min', '5min', '15min' ] as $min) {
            if (isset($la_def['oid_' . $min . '_num'])) {
                $la_oids[$min] = $la_def['oid_' . $min . '_num'];
            } elseif (isset($la_def['oid_' . $min])) {
                $la_oids[$min] = snmp_translate($la_def['oid_' . $min], $mib);
            } elseif (isset($la_def['oid_next_' . $min])) {
                // See CISCO-PROCESS-MIB, currently only for Cisco IOS-XE
                $oid = $la_def['oid_next_' . $min];
                $la_oids[$min] = $oid;
                $la[$oid] = snmp_getnext_oid($device, $oid, $mib);
            }
        }
        if (empty($la)) {
            $la = snmp_get_multi_oid($device, $la_oids, [], $mib, NULL, OBS_SNMP_ALL_NUMERIC);
        }
    }

    print_debug_vars($la_oids);
    print_debug_vars($la);

    if (snmp_status() && is_numeric($la[$la_oids['5min']])) {
        $scale       = $la_def['scale'] ?? 1;
        $scale_graph = $scale * 100; // Since want to keep compatibility with old UCD-SNMP-MIB LA, graph stored as la * 100

        foreach ($la_oids as $min => $oid) {
            $device_state['la'][$min] = $la[$oid] * $scale;
            // Now, graph specific scale if not equals 1
            $la[$min] = $la[$oid] * $scale_graph;
        }
        $device_state['ucd_load'] = $device_state['la']['5min']; // Compatibility with old UCD-SNMP-MIB code

        rrdtool_update_ng($device, 'la', [ '1min' => $la['1min'], '5min' => $la['5min'], '15min' => $la['15min'] ]);
        $graphs['la'] = TRUE;

        print_cli_data('Load average', $device_state['la']['1min'] . ', ' . $device_state['la']['5min'] . ', ' . $device_state['la']['15min']);

        return TRUE; // Stop walking other LAs
    }

    return FALSE;
}

function cache_device_attribs_exist($device, $refresh = FALSE)
{
    if (is_array($device)) {
        $device_id = $device['device_id'];
    } else {
        $device_id = $device;
    }

    // Pre-check if entity attribs for device exist
    if ($refresh || !isset($GLOBALS['cache']['devices_attribs'][$device_id])) {
        $GLOBALS['cache']['devices_attribs'][$device_id] = [];
        foreach (dbFetchColumn('SELECT DISTINCT `entity_type` FROM `entity_attribs` WHERE `device_id` = ?', [$device_id]) as $entity_type) {
            $GLOBALS['cache']['devices_attribs'][$device_id][$entity_type] = TRUE;
        }
        //r($GLOBALS['cache']['devices_attribs'][$device_id]);
        //$GLOBALS['cache']['devices_attribs'][$device_id][$entity_type] = dbExist('entity_attribs', '`entity_type` = ? AND `device_id` = ?', [ $entity_type, $device_id ]);
    }
}

/**
 * Determine if poller/discovery module is enabled for a device
 *
 * @param array       $device  Device array
 * @param string      $module  Module name
 * @param string|null $process Process name (poller, discovery). Try to detect if not passed.
 *
 * @return bool
 */
function is_module_enabled($device, $module, $process = NULL)
{
    global $config;

    // Detect used process (poller, discovery, etc)
    if (empty($process)) {
        $process = OBS_PROCESS_NAME;
    }

    if (!in_array($process, [ 'poller', 'discovery' ])) {
        print_debug("Module [$module] skipped. Not specified process name (poller or discovery).");
        return FALSE;
    }

    // Detect if we need to check submodule, ie: ports_ifstats
    [ $module, $submodule ] = explode('_', $module, 2);

    // Pre-check if module is known (discovery_modules or poller_modules global config)
    if (!isset($config[$process . '_modules'][$module])) {
        print_debug("Module [$module] not exist for $process.");
        return FALSE;
    }

    if (!safe_empty($submodule)) {
        $ok = check_submodule($device, $module, $submodule);
    } else {
        $ok = check_main_module($device, $module, $process);
    }

    if (!$ok) {
        $GLOBALS['cache']['devices'][$process . '_modules'][$device['device_id']]['disabled'][] = $module;
    } else {
        $GLOBALS['cache']['devices'][$process . '_modules'][$device['device_id']]['enabled'][] = $module;
    }
    return $ok;
}

function check_submodule($device, $module, $submodule)
{
    global $config;

    $module_name  = $module . '_' . $submodule;
    $setting_name = 'enable_' . $module_name;

    if (!isset($config[$setting_name])) {
        print_debug("Submodule [$module_name] not exist.");
        return FALSE;
    }
    if ($module_name === 'ports_junoseatmvp' && $device['os'] !== 'junose') {
        return FALSE;
    }

    $attrib = get_entity_attrib('device', $device, $setting_name);
    if (!safe_empty($attrib)) {
        return (bool)$attrib;
    }

    $enabled = $config[$setting_name];
    $model = get_model_array($device);
    if (isset($model[$module_name])) {
        $enabled = (bool)$model[$module_name];
    } elseif (isset($config['os'][$device['os']]['modules'][$module_name])) {
        $enabled = (bool)$config['os'][$device['os']]['modules'][$module_name];
    } elseif ($device['os_group'] && isset($config['os_group'][$device['os_group']]['modules'][$module_name])) {
        $enabled = (bool)$config['os_group'][$device['os_group']]['modules'][$module_name];
    } elseif (isset($config['os_group']['default']['modules'][$module_name])) {
        $enabled = (bool)$config['os_group']['default']['modules'][$module_name];
    }
    if ($enabled && $module_name === 'ports_separate_walk' &&
        $device['os'] === 'junos' && !isset($model[$module_name])) { // Derp.. bad, bad junos
        return safe_empty($device['version']) || strnatcmp($device['version'], '17') <= 0;
    }

    return (bool)$enabled;
}

function check_main_module($device, $module, $process)
{
    global $config;

    if ($process === 'poller') {
        if (in_array($module, [ 'os', 'system' ])) {
            return TRUE;
        }

        if (poller_module_excluded($device, $module)) {
            $GLOBALS['cache']['devices'][$process . '_modules'][$device['device_id']]['excluded'][$module] = $module;
            return FALSE;
        }

        $setting_name = 'poll_' . $module;
    } elseif ($process === 'discovery') {
        if ($module === 'os') {
            return TRUE;
        }

        $setting_name = 'discover_' . $module;
    } else {
        return FALSE;
    }

    $attrib = get_entity_attrib('device', $device, $setting_name);
    if (!safe_empty($attrib)) {
        return (bool)$attrib;
    }
    $blacklist_name = $process . '_blacklist';
    if (isset($config['os'][$device['os']][$blacklist_name]) &&
        in_array($module, (array)$config['os'][$device['os']][$blacklist_name], TRUE)) {
        return FALSE;
    }

    $enabled = $config[$process . '_modules'][$module];
    if ($enabled && isset($config['os'][$device['os']]['modules'][$module])) {
        $enabled = $config['os'][$device['os']]['modules'][$module];
    } elseif ($enabled && $device['os_group'] && isset($config['os_group'][$device['os_group']]['modules'][$module])) {
        $enabled = $config['os_group'][$device['os_group']]['modules'][$module];
    } elseif ($enabled && isset($config['os_group']['default']['modules'][$module])) {
        $enabled = $config['os_group']['default']['modules'][$module];
    } elseif ($module === 'wifi' && !in_array($device['type'], [ 'network', 'firewall', 'wireless' ], TRUE)) {
        $enabled = FALSE;
    }

    return (bool)$enabled;
}

///FIXME. It's not a very nice solution, but will approach as temporal.
// Function return FALSE, if poller module allowed for device os (otherwise TRUE).
function poller_module_excluded($device, $module)
{
    global $config;

    ///FIXME. rename module: 'wmi' -> 'windows-wmi'
    if ($module === 'wmi') {
        return $device['os'] !== 'windows';
    }

    if ($module === 'ipmi') {
        return (!isset($config['os'][$device['os']]['ipmi']) || !$config['os'][$device['os']]['ipmi']);
    }
    if ($module === 'unix-agent') {
        return !($device['os_group'] === 'unix' || $device['os'] === 'generic');
    }

    if (isset($config['os'][$device['os']]['poller_blacklist']) &&
        in_array($module, (array)$config['os'][$device['os']]['poller_blacklist'], TRUE)) {
        return TRUE;
    }

    if (!str_contains($module, '-')) {
        return FALSE; // Check modules only with a dash.
    }
    $os_test = explode('-', $module)[0];
    //var_dump($os_test);

    ///FIXME. rename module: 'cipsec-tunnels' -> 'cisco-ipsec-tunnels'
    if ($os_test === 'cisco' || $os_test === 'cipsec') {
        return $device['os_group'] !== 'cisco';
    }
    //$os_groups = array('cisco', 'unix');
    //foreach ($os_groups as $os_group)
    //{
    //  if ($os_test == $os_group && $device['os_group'] != $os_group) { return TRUE; }
    //}

    $oses = [ 'junose', 'arista_eos', 'netscaler', 'arubaos' ];
    foreach ($oses as $os) {
        if (strpos($os, $os_test) !== FALSE && $device['os'] != $os) {
            return TRUE;
        }
    }

    return FALSE;
}

/**
 * Set polled/discovered module time
 *
 * @param array       $device  Device array
 * @param string      $module  Module name
 * @param int         $time    Unixtime
 * @param string|null $process Process name (poller, discovery). Try to detect if not passed.
 *
 * @return int        Time entry ID
 */
function set_module_time($device, $module, $time = 0, $process = NULL) {
    global $config;

    // Detect used process (poller, discovery, etc)
    if (is_null($process) || !in_array($process, [ 'poller', 'discovery' ])) {
        $process = OBS_PROCESS_NAME;
    }

    if (!in_array($process, [ 'poller', 'discovery' ])) {
        print_debug("Module [$module] skipped. Not specified process name (poller or discovery).");
        return FALSE;
    }

    // Pre check if module is known (discovery_modules or poller_modules global config)
    if (!isset($config[$process . '_modules'][$module])) {
        print_debug("Module [$module] not exist.");
        return FALSE;
    }

    if ($time < 1) { $time = time(); }
    $time_field = 'module_'.$process;

    $id = dbFetchCell('SELECT `device_module_id` FROM `devices_modules` WHERE `device_id` = ? AND `module_name` = ?', [ $device['device_id'], $module ]);
    if (is_numeric($id)) {
        // update
        dbUpdate([ $time_field => $time ], 'devices_modules', '`device_module_id` = ?', [ $id ]);
    } else {
        // insert
        $insert = [
            'device_id' => $device['device_id'],
            'module_name' => $module,
            $time_field => $time
        ];

        $id = dbInsert($insert, 'devices_modules');
    }

    return $id;
}

/**
 * Get polled/discovered module time
 *
 * @param array       $device  Device array
 * @param string      $module  Module name
 * @param string|null $process Process name (poller, discovery). Try to detect if not passed.
 *
 * @return int        Time entry ID
 */
function get_module_time($device, $module, $process = NULL) {

    // Detect used process (poller, discovery, etc)
    if (is_null($process) || !in_array($process, [ 'poller', 'discovery' ])) {
        $process = OBS_PROCESS_NAME;
    }

    if (!in_array($process, [ 'poller', 'discovery' ])) {
        print_debug("Module [$module] skipped. Not specified process name (poller or discovery).");
        return FALSE;
    }

    $time_field = 'module_'.$process;

    return dbFetchCell('SELECT `'.$time_field.'` FROM `devices_modules` WHERE `device_id` = ? AND `module_name` = ?', [ $device['device_id'], $module ]);
}

/**
 * Print discovery/poller module stats
 *
 * @param array  $device  Device array
 * @param string $module  Module name
 *
 * @global array $GLOBALS ['module_stats']
 */
function print_module_stats($device, $module)
{
    $log_event = FALSE;
    $stats_msg = [];
    foreach (['added', 'updated', 'deleted', 'unchanged'] as $key) {
        if ($GLOBALS['module_stats'][$module][$key]) {
            $stats_msg[] = (int)$GLOBALS['module_stats'][$module][$key] . ' ' . $key;
            if ($key != 'unchanged') {
                $log_event = TRUE;
            }
        }
    }
    if (!empty($GLOBALS['module_stats'][$module])) {
        echo PHP_EOL;
    }
    if (count($stats_msg)) {
        print_cli_data("Changes", implode(', ', $stats_msg));
    }
    if ($GLOBALS['module_stats'][$module]['time']) {
        print_cli_data("Duration", $GLOBALS['module_stats'][$module]['time'] . "s");
    }
    if ($log_event) {
        log_event(nicecase($module) . ': ' . implode(', ', $stats_msg) . '.', $device, 'device', $device['device_id']);
    }
}

/* OBSOLETE, BUT STILL USED FUNCTIONS */

// CLEANME remove when all function calls will be deleted
function get_dev_attrib($device, $attrib_type)
{
    // Call to new function
    return get_entity_attrib('device', $device, $attrib_type);
}

// CLEANME remove when all function calls will be deleted
function get_dev_attribs($device_id)
{
    // Call to new function
    return get_entity_attribs('device', $device_id);
}

// CLEANME remove when all function calls will be deleted
function set_dev_attrib($device, $attrib_type, $attrib_value)
{
    // Call to new function
    return set_entity_attrib('device', $device, $attrib_type, $attrib_value);
}

// CLEANME remove when all function calls will be deleted
function del_dev_attrib($device, $attrib_type)
{
    // Call to new function
    return del_entity_attrib('device', $device, $attrib_type);
}

// EOF
