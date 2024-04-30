<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage authentication
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

/**
 * Retrieves list of domain controllers from DNS through SRV records.
 * Private function for this LDAP/AD modules only.
 *
 * @param string $domain Domain name (fqdn-style) for the AD domain.
 *
 * @return array Array of server names to be used for LDAP.
 * @throws Net_DNS2_Exception
 */
function ldap_domain_servers_from_dns($domain) {

    $servers = [];

    $resolver = new Net_DNS2_Resolver();

    if ($response = $resolver->query("_ldap._tcp.dc._msdcs.$domain", 'SRV', 'IN')) {
        foreach ($response->answer as $answer) {
            $servers[] = $answer->target;
        }
    }

    return $servers;
}

function ldap_paged_entries($filter, $attributes, $dn) {
    global $config, $ds;

    $ldap_v3 = ($config['auth_mechanism'] === 'ad') || ($config['auth_ldap_version'] >= 3);
    $entries = [];

    if ($ldap_v3 && PHP_VERSION_ID >= 70300) {
        // Use pagination for speedup fetch huge lists, there is new style, see:
        // https://www.php.net/manual/en/ldap.examples-controls.php (Example #5)
        $page_size = 200;
        $cookie    = '';

        print_debug("Configuring LDAP for paged result ($page_size entries)");
        do {
            $search = ldap_search(
                $ds, $dn, $filter, $attributes, 0, 0, 0, LDAP_DEREF_NEVER,
                [['oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => ['size' => $page_size, 'cookie' => $cookie]]]
            );
            if (ldap_internal_is_valid($search)) {
                ldap_parse_result($ds, $search, $errcode, $matcheddn, $errmsg, $referrals, $controls);
                print_debug(ldap_internal_error($ds));
                $entries[] = ldap_get_entries($ds, $search);

                if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
                    // You need to pass the cookie from the last call to the next one
                    $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
                } else {
                    $cookie = '';
                }
            } else {
                $cookie = '';
            }
            // Empty cookie means last page
        } while (!empty($cookie));
        $entries = array_merge([], ...$entries);

    } elseif ($ldap_v3 && function_exists('ldap_control_paged_result')) {
        // Use pagination for speedup fetch huge lists, pre 7.3 style
        $page_size = 200;
        $cookie    = '';

        print_debug("Configuring LDAP for paged result ($page_size entries)");
        do {
            // WARNING, do not make any ldap queries between ldap_control_paged_result() and ldap_control_paged_result_response()!!
            //          this produces a loop and errors in queries
            $page_test = ldap_control_paged_result($ds, $page_size, TRUE, $cookie);
            //print_vars($page_test);
            print_debug(ldap_internal_error($ds));

            $search = ldap_search($ds, $dn, $filter, $attributes);
            print_debug(ldap_internal_error($ds));
            if (ldap_internal_is_valid($search)) {
                $entries[] = ldap_get_entries($ds, $search);
                //print_vars($filter);
                //print_vars($search);

                //ldap_internal_user_entries($entries, $userlist);

                ldap_control_paged_result_response($ds, $search, $cookie);
            } else {
                $cookie = '';
            }

        } while ($page_test && $cookie !== NULL && $cookie != '');
        $entries = array_merge([], ...$entries);
        // Reset LDAP paged result
        ldap_control_paged_result($ds, 1000);

    } else {
        // Old php < 5.4, trouble with limit 1000 entries, see:
        // http://stackoverflow.com/questions/24990243/ldap-search-not-returning-more-than-1000-user

        print_debug("Configuring LDAP for Non-Paged result");
        $search = ldap_search($ds, $dn, $filter, $attributes);
        print_debug(ldap_internal_error($ds));

        if (ldap_internal_is_valid($search)) {
            $entries = ldap_get_entries($ds, $search);
            //print_vars($filter);
            //print_vars($search);

            //ldap_internal_user_entries($entries, $userlist);
        }
    }

    return $entries;
}

/**
 * Constructor of a new part of a LDAP filter.
 *
 * Example:
 *  ldap_filter_create('memberOf', 'name', '=') >>> '(memberOf=name)'
 *
 * @param string  $param     Name of the attribute the filter should apply to
 * @param string  $value     Filter value
 * @param string  $condition Matching rule
 * @param boolean $escape    Should $value be escaped? (default: yes)
 *
 * @return string Generated filter
 */
function ldap_filter_create($param, $value, $condition = '=', $escape = TRUE)
{
    if ($escape) {
        $value = ldap_escape_filter_value($value);
        $value = array_shift($value);
    }

    // Convert common rule name to ldap rule
    // Default rule is equals
    $condition = strtolower(trim($condition));
    switch ($condition) {
        case 'ge':
        case '>=':
            $filter = '(' . $param . '>=' . $value . ')';
            break;
        case 'le':
        case '<=':
            $filter = '(' . $param . '<=' . $value . ')';
            break;
        case 'gt':
        case 'greater':
        case '>':
            $filter = '(' . $param . '>' . $value . ')';
            break;
        case 'lt':
        case 'less':
        case '<':
            $filter = '(' . $param . '<' . $value . ')';
            break;
        case 'match':
        case 'matches':
        case '~=':
            $filter = '(' . $param . '~=' . $value . ')';
            break;
        case 'notmatches':
        case 'notmatch':
        case '!match':
        case '!~=':
            $filter = '(!(' . $param . '~=' . $value . '))';
            break;
        case 'notequals':
        case 'isnot':
        case 'ne':
        case '!=':
        case '!':
            $filter = '(!(' . $param . '=' . $value . '))';
            break;
        case 'equals':
        case 'eq':
        case 'is':
        case '==':
        case '=':
        default:
            $filter = '(' . $param . '=' . $value . ')';
    }

    return $filter;
}

/**
 * Combine two or more filter objects using a logical operator
 *
 * @param array  $values    Array with Filter entries generated by ldap_filter_create()
 * @param string $condition The logical operator. May be "and", "or", "not" or the subsequent logical equivalents "&", "|", "!"
 *
 * @return string Generated filter
 */
function ldap_filter_combine($values = [], $condition = '&')
{
    $count = safe_count($values);
    if (!$count) {
        return '';
    }

    $condition = strtolower(trim($condition));
    switch ($condition) {
        case '!':
        case 'not':
            $filter = '(!' . implode('', $values) . ')';
            break;
        case '|':
        case 'or':
            if ($count === 1) {
                $filter = array_shift($values);
            } else {
                $filter = '(|' . implode('', $values) . ')';
            }
            break;
        case '&':
        case 'and':
        default:
            if ($count === 1) {
                $filter = array_shift($values);
            } else {
                $filter = '(&' . implode('', $values) . ')';
            }
    }

    return $filter;
}

/**
 * Escapes the given VALUES according to RFC 2254 so that they can be safely used in LDAP filters.
 *
 * Any control characters with an ACII code < 32 as well as the characters with special meaning in
 * LDAP filters "*", "(", ")", and "\" (the backslash) are converted into the representation of a
 * backslash followed by two hex digits representing the hexadecimal value of the character.
 *
 * @param array|string $values Array of values to escape
 *
 * @return array Array $values, but escaped
 */
function ldap_escape_filter_value($values = [])
{
    // Parameter validation
    if (!is_array($values)) {
        $values = [$values];
    }

    foreach ($values as $key => $val) {
        // Escaping of filter meta characters
        $val = str_replace(['\\', '\5c,', '*', '(', ')'],
                           ['\5c', '\2c', '\2a', '\28', '\29'], $val);

        // ASCII < 32 escaping
        $val = asc2hex32($val);

        if (NULL === $val) {
            $val = '\0';
        }  // apply escaped "null" if string is empty

        $values[$key] = $val;
    }

    return $values;
}

/**
 * Undoes the conversion done by {@link ldap_escape_filter_value()}.
 *
 * Converts any sequences of a backslash followed by two hex digits into the corresponding character.
 *
 * @param array $values Array of values to escape
 *
 * @return array Array $values, but unescaped
 */
function ldap_unescape_filter_value($values = [])
{
    // Parameter validation
    if (!is_array($values)) {
        $values = [$values];
    }

    foreach ($values as $key => $value) {
        // Translate hex code into ascii
        $values[$key] = hex2asc($value);
    }

    return $values;
}

/**
 * Converts all ASCII chars < 32 to "\HEX"
 *
 * @param string $string String to convert
 *
 * @return string
 */
function asc2hex32($string)
{
    for ($i = 0, $max = strlen($string); $i < $max; $i++) {
        $char = $string[$i];
        if (ord($char) < 32) {
            $hex = dechex(ord($char));
            if (strlen($hex) === 1) {
                $hex = '0' . $hex;
            }
            $string = str_replace($char, '\\' . $hex, $string);
        }
    }
    return $string;
}

/**
 * Converts all Hex expressions ("\HEX") to their original ASCII characters
 *
 * @param string $string String to convert
 *
 * @return string
 * @author beni@php.net, heavily based on work from DavidSmith@byu.net
 */
function hex2asc($string)
{
    return preg_replace_callback("/\\\([0-9A-Fa-f]{2})/", function ($matches) {
        foreach ($matches as $match) {
            return chr(hexdec($match));
        }
    }, $string);
}

/**
 * Returns the textual SID for Active Directory
 *
 * Source: http://stackoverflow.com/questions/13130291/how-to-query-ldap-adfs-by-objectsid-in-php-or-any-language-really
 *
 * @param string $binsid Binary SID
 *
 * @return string Textual SID
 */
function ldap_bin_to_str_sid($binsid)
{
    $hex_sid  = bin2hex($binsid);
    $rev      = hexdec(substr($hex_sid, 0, 2));
    $subcount = hexdec(substr($hex_sid, 2, 2));
    $auth     = hexdec(substr($hex_sid, 4, 12));
    $result   = "$rev-$auth";

    for ($x = 0; $x < $subcount; $x++) {
        $subauth[$x] = hexdec(ldap_little_endian(substr($hex_sid, 16 + ($x * 8), 8)));
        $result      .= "-" . $subauth[$x];
    }

    // Cheat by tacking on the S-
    return 'S-' . $result;
}

/**
 * Convert a little-endian hex-number to one that 'hexdec' can convert.
 *
 * Source: http://stackoverflow.com/questions/13130291/how-to-query-ldap-adfs-by-objectsid-in-php-or-any-language-really
 *
 * @param string $hex Hexadecimal number
 *
 * @return string Converted hexadecimal number
 */
function ldap_little_endian($hex)
{
    $result = '';
    for ($x = strlen($hex) - 2; $x >= 0; $x -= 2) {
        $result .= substr($hex, $x, 2);
    }

    return $result;
}

// DOCME
function ldap_internal_is_valid($obj) {
    if (PHP_VERSION_ID >= 80100) {
        // ldap_bind() returns an LDAP\Connection instance in 8.1; previously, a resource was returned
        // ldap_search() returns an LDAP\Result instance in 8.1; previously, a resource was returned.
        return is_object($obj);
    }

    return is_resource($obj);
}

// DOCME
function ldap_internal_error($ds) {
    if (is_bool($ds)) { return ''; }

    $error_msg = ldap_error($ds);
    if ($error_no = ldap_errno($ds)) {
        $error_msg .= ' (' . $error_no . ': ' . ldap_err2str($error_no) . ')';
        ldap_get_option($ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $diag);
        if ($diag) {
            $error_msg .= ' [' . $diag . ']';
        }
    }
    return $error_msg;
}

// EOF
