<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

function discover_ip_address_definition($device, $mib, $entry)
{
    echo(' ['); // FIXME

    // Check that types listed in skip_if_valid_exist have already been found
    //if (discovery_check_if_type_exist($entry, 'ip-address')) { echo '!]'; return; }

    // Check array requirements list
    //if (discovery_check_requires_pre($device, $entry, 'ip-address')) { echo '!]'; return; }

    // Fetch table or Oids
    $table_oids = ['oid_address', 'oid_ifIndex', 'oid_prefix', 'oid_mask', 'oid_gateway', // 'oid_version',
                   'oid_origin', 'oid_type', 'oid_vrf', 'oid_mac', 'oid_extra'];
    $ip_array   = discover_fetch_oids($device, $mib, $entry, $table_oids);

    // Just append mib name to definition entry, for simple pass to external functions
    if (empty($entry['mib'])) {
        $entry['mib'] = $mib;
    }

    // Index delimiter, for split index parts
    // I.e. in HUAWEI-IF-EXT-MIB, CISCOSB-IPv6
    $index_delimiter = is_flag_set(OBS_SNMP_INDEX_PARTS, $entry['snmp_flags']) ? '->' : '.';

    // not indexed part, see CPI-UNIFIED-MIB
    if (is_flag_set(OBS_SNMP_NOINDEX, $entry['snmp_flags']) &&
        isset($ip_array['']) && count($ip_array) > 1) {
        $ip_array_extra = $ip_array[''];
        unset($ip_array['']);
    }

    //$ips_count = count($ip_array);
    foreach ($ip_array as $index => $ip_address) {
        // add index and index parts for tags replace
        $ip_address['index'] = $index;
        foreach (explode($index_delimiter, $index) as $i => $part) {
            $ip_address['index' . $i] = $part;
        }
        // append extra (not indexed) array
        if (isset($ip_array_extra)) {
            $ip_address = array_merge($ip_address, $ip_array_extra);
        }
        print_debug_vars($ip_address);

        // ifIndex
        $ifIndex = set_value_param_definition('ifIndex', $entry, $ip_address);
        if (str_contains($ifIndex, '%')) {
            // I.e. in CISCOSB-IPv6, for addresses with ifIndex parts:
            // ipv6z->ff:02:00:00:00:00:00:00:00:00:00:01:ff:a3:3f:49%100000
            [, $ifIndex] = explode('%', $ifIndex);
        }
        // Rule-based entity linking
        if (!is_numeric($ifIndex) &&
            $measured = entity_measured_match_definition($device, $entry, $ip_address, 'ip-address')) {
            $ifIndex = $measured['ifIndex'];
            //print_debug_vars($measured);
        }
        // Link by MAC
        if (!is_numeric($ifIndex) || $ifIndex == 0) {
            $mac = set_value_param_definition('mac', $entry, $ip_address);
            if (strlen($mac) && $port_id = get_port_id_by_mac($device, $mac)) {
                $port    = get_port_by_id_cache($port_id);
                $ifIndex = $port['ifIndex'];
            }
        }
        if (!is_numeric($ifIndex)) {
            print_debug("Excluded by unknown ifIndex [$ifIndex]");
            continue;
        }

        // Set base ip array
        $data = ['ifIndex' => $ifIndex];

        // IP address
        $ip = set_value_param_definition('address', $entry, $ip_address);
        if (str_contains($ip, '%')) {
            // I.e. in CISCOSB-IPv6, for addresses with ifIndex parts:
            // ipv6z->ff:02:00:00:00:00:00:00:00:00:00:01:ff:a3:3f:49%100000
            [$ip] = explode('%', $ip);
        }
        // IP address with prefix
        if (str_contains($ip, '/')) {
            // I.e. VIPTELA-OPER-VPN address+prefix: 10.123.10.69/32
            [$ip, $prefix] = explode('/', $ip);
            if (is_numeric($prefix)) {
                $data['prefix'] = $prefix;
            } else {
                $data['mask'] = $prefix;
            }
        }
        $ip = hex2ip($ip);

        $data['ip'] = $ip;

        // Other ip params: origin, type, prefix, mask
        foreach (['origin', 'type', 'prefix', 'mask', 'gateway', 'vrf'] as $param) {
            if (!isset($data[$param]) && $value = set_value_param_definition($param, $entry, $ip_address)) {
                if ($param === 'prefix' && !is_numeric($value)) {
                    // Always explode prefix part from oid value, ie:
                    // cIpAddressPrefix.ipv6."20:01:04:70:00:15:00:bb:00:00:00:00:00:00:00:02" = cIpAddressPfxOrigin.450.ipv6."20:01:04:70:00:15:00:bb:00:00:00:00:00:00:00:00".64
                    $tmp_prefix = explode('.', $value);
                    $value      = end($tmp_prefix);
                }
                $data[$param] = $value;
            }
        }

        // Check array requirements list
        if (discovery_check_requires($device, $entry, array_merge($ip_address, $data), 'ip-address')) {
            continue;
        }

        discover_add_ip_address($device, $mib, $data);
    }

    echo '] ';

}

function discover_add_ip_address($device, $mib, $entry) {
    global $ip_data;

    $ip = $entry['ip'];
    if (isset($entry['prefix']) && ($entry['prefix'] === 'zeroDotZero' || safe_empty($entry['prefix']))) {
        unset($entry['prefix']);
    }

    // IP version
    $ip_version = get_ip_version($ip);
    $ip_version = 'ipv' . $ip_version;

    // ifIndex
    if ($entry['ifIndex'] == 0 && !isset($ip_data[$ip_version][0])) {
        // When used system table/oids without known ifIndex,
        // try to find correct ifIndex and update entry
        // ie: SWSYSTEM-MIB, ENVIROMUX16D
        foreach ($ip_data[$ip_version] as $ind => $tmp) {
            if (isset($tmp[$ip])) {
                print_debug("Found ifIndex $ind for IP $ip");
                $entry['ifIndex'] = $ind;
                break;
            }
        }
    }
    $ifIndex = $entry['ifIndex'];

    switch ($ip_version) {
        case 'ipv4':
            // IPv4
            $prefix = get_ip_prefix($entry);
            if (!is_ipv4_valid($ip, $prefix)) {
                print_debug("Address '$ip/$prefix' skipped as invalid.");
                return;
            }
            $entry['prefix'] = $prefix;
            break;

        case 'ipv6':
            // IPv6
            $prefix = get_ip_prefix($entry);
            if (!is_ipv6_valid($ip, $prefix)) {
                print_debug("Address '$ip/$prefix' skipped as invalid.");
                return;
            }
            $entry['prefix'] = $prefix;
            break;

        default:
            print_debug("Excluded by unknown IP address [$ip]");
            return;
    }

    // Always re-detect unicast type, while device can report it incorrectly
    if (empty($entry['type']) || $entry['type'] === 'unicast') {
        $entry['type'] = get_ip_type("$ip/$prefix");
    }

    if (!isset($ip_data[$ip_version][$ifIndex][$ip])) {
        $ip_data[$ip_version][$ifIndex][$ip] = $entry;
        print_debug("Added $ip");
        print_debug_vars($entry);
        echo '.';
    } else {
        // Compare both and merge best
        $old_entry = $ip_data[$ip_version][$ifIndex][$ip];
        if (isset($old_entry['prefix']) && $old_entry['prefix'] === 'zeroDotZero') {
            unset($old_entry['prefix']);
        }
        $check_prefix = $old_entry['prefix'] < 3 ||
                        ($ip_version === 'ipv4' && $old_entry['prefix'] == '32') ||
                        ($ip_version === 'ipv6' && $old_entry['prefix'] == '128');
        $updated      = [];
        foreach (array_keys($entry) as $param) {
            //print_debug_vars($old_entry);
            //print_debug_vars($entry);
            if (!isset($old_entry[$param]) || safe_empty($old_entry[$param])) {
                $ip_data[$ip_version][$ifIndex][$ip][$param] = $entry[$param];
                $updated[]                                   = $param;
            } elseif (($param === 'type' && $old_entry[$param] === 'anycast' && strlen($entry[$param])) ||
                      ($param === 'prefix' && $check_prefix && $entry[$param] > 0)) {
                $ip_data[$ip_version][$ifIndex][$ip][$param] = $entry[$param];
                $updated[]                                   = $param;
            }
        }
        if (count($updated)) {
            print_debug("Already exist $ip, updated params [" . implode(', ', $updated) . "]");
        } else {
            print_debug("Already exist $ip, skip");
        }
        //print_debug_vars($ip_data[$ip_version][$ifIndex][$ip]);
        //print_debug_vars($entry);
    }
}

/**
 * Convert IPv4 netmask to CIDR
 *
 * @param string $netmask
 *
 * @return string|null
 */
function netmask2cidr($netmask) {
    $addr = Net_IPv4::parseAddress("1.2.3.4/$netmask");
    return is_intnum($addr->bitmask) ? $addr->bitmask : NULL;
}

/**
 * Convert CIDR to IPv4 netmask
 *
 * @param $cidr
 *
 * @return string|null
 */
function cidr2netmask($cidr) {
    if (!is_intnum($cidr) || $cidr < 0 || $cidr > 32) {
        return NULL;
    }
    return long2ip(ip2long("255.255.255.255") << (32 - $cidr));
}

/**
 * Detect the best possible IP prefix by IPv4 and Gateway.
 * 192.168.0.3 (ip) + 192.168.0.1 (gw) -> 24
 *
 * @param string $ip
 * @param string $gateway
 *
 * @return int Detected prefix
 */
function gateway2prefix($ip, $gateway) {
    if (!safe_empty($gateway) && get_ip_version($gateway) === 4) {
        // Derp way for detect prefix by gateway (limit by /24 - /32)
        $prefix = 24;
        while ($prefix < 32) {
            if (Net_IPv4::ipInNetwork($gateway, $ip . '/' . $prefix)) {
                // Gateway IP in network, stop loop
                print_debug("Prefix '$prefix' detected by IP '$ip' and Gateway '$gateway'.");
                break;
            }
            $prefix++;
        }

        // Still not found prefix, try increase now /23 - /8
        if ($prefix === 32 && $ip !== $gateway) {
            $tmp_prefix = 23;
            while ($tmp_prefix >= 8) {
                if (Net_IPv4::ipInNetwork($gateway, $ip . '/' . $tmp_prefix)) {
                    // Gateway IP in network, stop loop
                    $prefix = $tmp_prefix;
                    print_debug("Prefix '$prefix' detected by IP '$ip' and Gateway '$gateway'.");
                    break;
                }
                $tmp_prefix--;
            }
        }

        return $prefix;
    }

    return 32;
}

function get_ip_prefix($entry) {
    if (!is_array($entry)) {
        // Convert ip/prefix string to a common array
        $address = $entry;
        $entry   = [];
        [ $entry['ip'], $prefix ] = explode('/', $address);
        if (!safe_empty($prefix)) {
            if (is_numeric($prefix)) {
                $entry['prefix'] = $prefix;
            } else {
                $entry['mask'] = $prefix;
            }
        }
    }
    print_debug_vars($entry);

    switch (get_ip_version($entry['ip'])) {
        case 4:
            if (!safe_empty($entry['prefix']) && $entry['prefix'] !== 'zeroDotZero') {
                $prefix = netmask2cidr($entry['prefix']);
            } else { //if (!safe_empty($entry['mask']) && $entry['mask'] !== 'zeroDotZero') {
                $prefix = netmask2cidr($entry['mask']);
            }
            if (!is_intnum($prefix)) {
                $prefix = gateway2prefix($entry['ip'], $entry['gateway']);
            }
            return (int)$prefix;
        case 6:
            if (!safe_empty($entry['prefix']) && $entry['prefix'] !== 'zeroDotZero') {
                $prefix = $entry['prefix'];
            }
            if (!is_intnum($prefix) || $prefix < 0 || $prefix > 128) {
                $prefix = 128;
            }
            return (int)$prefix;
    }

    // Incorrect IP
    print_debug("Incorrect: {$entry['ip']}");
    return NULL;
}

/**
 * Returns IP version for string or FALSE if string not an IP
 *
 * Examples:
 *  get_ip_version('127.0.0.1')   === 4
 *  get_ip_version('::1')         === 6
 *  get_ip_version('my_hostname') === FALSE
 *
 * @param string $address IP address string
 *
 * @return integer|false IP version or FALSE if passed incorrect address
 */
function get_ip_version($address)
{

    if (str_contains($address, '/')) {
        // Dump condition,
        // IPs with CIDR not correct for us here
        return FALSE;
    }

    if (preg_match('%^' . OBS_PATTERN_IPV4 . '$%', $address)) {
        return 4;
    }
    if (preg_match('%^' . OBS_PATTERN_IPV6 . '$%i', $address)) {
        return 6;
    }

    return FALSE;
}

/**
 * Function for compress IPv6 address and keep as is IPv4 (already compressed)
 *
 * @param string|array $address IPv4 or IPv6 address
 * @param bool         $force   If true, an already compressed IP address will be compressed again
 *
 * @return string|array Compressed address string
 */
function ip_compress($address, $force = TRUE) {

    // Recursive compress for arrays
    if (is_array($address)) {
        $array = [];
        foreach ($address as $entry) {
            $array[] = ip_compress($entry, $force);
        }
        return $array;
    }

    [ $ip, $net ] = explode('/', $address);
    if (get_ip_version($ip) === 6) {
        $address = Net_IPv6::compress($ip, $force);
        if (is_numeric($net) && ($net >= 0) && ($net <= 128)) {
            $address .= '/' . $net;
        }
    }

    return $address;
}

/**
 * Function for uncompress IPv6 address and keep as is IPv4
 *
 * @param string|array $address       IPv4 or IPv6 address
 * @param bool         $leading_zeros If true, the uncompressed address has a fixed length
 *
 * @return string|array Uncompressed address string
 */
function ip_uncompress($address, $leading_zeros = TRUE) {

    // Recursive uncompress for arrays
    if (is_array($address)) {
        $array = [];
        foreach ($address as $entry) {
            $array[] = ip_uncompress($entry, $leading_zeros);
        }
        return $array;
    }

    [ $ip, $net ] = explode('/', $address);
    if (get_ip_version($ip) === 6) {
        $address = Net_IPv6::uncompress($ip, $leading_zeros);
        if (is_numeric($net) && ($net >= 0) && ($net <= 128)) {
            $address .= '/' . $net;
        }
    }

    return $address;
}

/**
 * Return safe key for use in arrays by ip address (replaced with __ if IP is empty or 0.0.0.0/::0)
 * Additionally, passed hostname and/or IPv6 address compressed.
 *
 * @param string      $hostname Hostname (or IP address)
 * @param string|null $ip       IPv4/IPv6 addresss
 *
 * @return string Return compressed IP address or __ if IP is empty
 */
function safe_ip_hostname_key(&$hostname, &$ip = NULL) {
    $hostname = strtolower($hostname);

    // Hostname is IP address
    if ($hostname_ip_version = get_ip_version($hostname)) {
        $hostname = ip_compress($hostname);
        if (empty($ip)) {
            // If hostname is IP, set remote_ip same
            $ip = $hostname;
        }
    }

    $ip_type = get_ip_type($ip);
    if ($ip_type && $ip_type !== 'unspecified') {
        $ip     = ip_compress($ip);
        $ip_key = $ip;
    } else {
        $ip_key = '__';
    }

    return $ip_key;
}

/**
 * Check if a given IPv4 address (and prefix length) is valid.
 *
 * @param string $ipv4_address   IPv4 Address
 * @param string $ipv4_prefixlen IPv4 Prefix length (optional, either 24 or 255.255.255.0)
 *
 * @return bool Returns TRUE if address is valid, FALSE if not valid.
 */
// TESTME needs unit testing
function is_ipv4_valid($ipv4_address, $ipv4_prefixlen = NULL) {

    if (str_contains($ipv4_address, '/')) {
        [$ipv4_address, $ipv4_prefixlen] = explode('/', $ipv4_address);
    }
    $ip_full = $ipv4_address . '/' . $ipv4_prefixlen;

    // False if invalid IPv4 syntax
    if (!safe_empty((string)$ipv4_prefixlen) &&
        !preg_match('%^' . OBS_PATTERN_IPV4_NET . '$%', $ip_full)) {
        // Address with prefix
        return FALSE;
    }
    if (!preg_match('%^' . OBS_PATTERN_IPV4 . '$%i', $ipv4_address)) {
        // Address without prefix
        return FALSE;
    }

    $ipv4_type = get_ip_type($ip_full);

    // False if unspecified
    $ignore_type = ['unspecified'];
    // It this address types (broadcast) removed from config by user, allow to discover it too
    // Do not ignore link-local for IPv4, this is not same as for IPv6
    // if (in_array('link-local', $GLOBALS['config']['ip-address']['ignore_type']))
    // {
    //   $ignore_type[] = 'link-local';
    // }
    if (in_array('broadcast', $GLOBALS['config']['ip-address']['ignore_type'])) {
        $ignore_type[] = 'broadcast';
    }
    if (in_array($ipv4_type, $ignore_type)) {
        print_debug("Address $ip_full marked as invalid by type [$ipv4_type].");
        return FALSE;
    }

    return TRUE;
}

/**
 * Check if a given IPv6 address (and prefix length) is valid.
 * Link-local addresses are considered invalid.
 *
 * @param string $ipv6_address   IPv6 Address
 * @param string $ipv6_prefixlen IPv6 Prefix length (optional)
 *
 * @return bool Returns TRUE if address is valid, FALSE if not valid.
 */
// TESTME needs unit testing
function is_ipv6_valid($ipv6_address, $ipv6_prefixlen = NULL)
{
    if (str_contains($ipv6_address, '/')) {
        [ $ipv6_address, $ipv6_prefixlen ] = explode('/', $ipv6_address);
    }
    $ip_full = $ipv6_address . '/' . $ipv6_prefixlen;

    // False if invalid IPv6 syntax
    if (!safe_empty((string)$ipv6_prefixlen) &&
        !preg_match('%^' . OBS_PATTERN_IPV6_NET . '$%i', $ip_full)) {
        // Address with prefix
        return FALSE;
    }
    if (!preg_match('%^' . OBS_PATTERN_IPV6 . '$%i', $ipv6_address)) {
        // Address without prefix
        return FALSE;
    }

    $ipv6_type = get_ip_type($ip_full);

    // False if link-local, unspecified
    $ignore_type = ['unspecified'];
    // It this address types (link-local, broadcast) removed from config by user, allow to discover it too
    if (in_array('link-local', $GLOBALS['config']['ip-address']['ignore_type'])) {
        $ignore_type[] = 'link-local';
    }
    if (in_array('broadcast', $GLOBALS['config']['ip-address']['ignore_type'])) {
        $ignore_type[] = 'broadcast';
    }
    if (in_array($ipv6_type, $ignore_type)) {
        print_debug("Address $ip_full marked as invalid by type [$ipv6_type].");
        return FALSE;
    }

    return TRUE;
}

/**
 * Detect IP type.
 * Based on https://www.ripe.net/manage-ips-and-asns/ipv6/ipv6-address-types/ipv6-address-types
 *
 *  Known types:
 *   - unspecified    : ::/128, 0.0.0.0
 *   - loopback       : ::1/128, 127.0.0.1
 *   - ipv4mapped     : only for IPv6 ::ffff/96 (::ffff:192.0.2.47)
 *   - private        : fc00::/7 (fdf8:f53b:82e4::53),
 *                      10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
 *   - link-local     : fe80::/10 (fe80::200:5aee:feaa:20a2),
 *                      169.254.0.0/16
 *   - teredo         : only for IPv6 2001:0000::/32 (2001:0000:4136:e378:8000:63bf:3fff:fdd2)
 *   - benchmark      : 2001:0002::/48 (2001:0002:6c::430),
 *                      198.18.0.0/15
 *   - orchid         : only for IPv6 2001:0010::/28 (2001:10:240:ab::a)
 *   - 6to4           : 2002::/16 (2002:cb0a:3cdd:1::1),
 *                      192.88.99.0/24
 *   - documentation  : 2001:db8::/32 (2001:db8:8:4::2),
 *                      192.0.2.0/24, 198.51.100.0/24, 203.0.113.0/24
 *   - global-unicast : only for IPv6 2000::/3
 *   - multicast      : ff00::/8 (ff01:0:0:0:0:0:0:2), 224.0.0.0/4
 *   - unicast        : all other
 *
 * @param string $address IPv4 or IPv6 address string
 *
 * @return string IP type
 */
function get_ip_type($address) {
    global $config;

    [ $ip, $bits ] = explode('/', trim($address)); // Remove subnet/mask if exist

    $ip_version = get_ip_version($ip);
    switch ($ip_version) {
        case 4:

            // Detect IPv4 broadcast
            if (!safe_empty($bits)) {
                $ip_parse = Net_IPv4::parseAddress($address);
                if ($ip == $ip_parse->broadcast && $ip_parse->bitmask < 31) { // Do not set /31 and /32 as broadcast!
                    $ip_type = 'broadcast';
                    break;
                }
            }

        // no break here!
        case 6:

            $ip_type = ($ip_version == 4) ? 'unicast' : 'reserved'; // Default for any valid address
            foreach ($config['ip_types'] as $type => $entry) {
                if (isset($entry['networks']) && match_network_first($ip, $entry['networks'])) {
                    // Stop loop if IP founded in networks
                    $ip_type = $type;
                    break;
                }

            }
            break;

        default:
            // Not valid IP address
            return FALSE;
    }

    return $ip_type;
}

/**
 * Parse string for valid IP/Network queries.
 *
 * Valid queries example:
 *    - 10.0.0.0/8  - exactly IPv4 network, matches to 10.255.0.2, 10.0.0.0, 10.255.255.255
 *    - 10.12.0.3/8 - same as previous, NOTE network detected by prefix: 10.0.0.0/8
 *    - 10.12.0.3   - single IPv4 address
 *    - *.12.0.3, 10.*.3   - * matching by any string in address
 *    - ?.12.0.3, 10.?.?.3 - ? matching by single char
 *    - 10.12              - match by part of address, matches to 10.12.2.3, 10.122.3.3, 1.2.110.120
 *    - 1762::b03:1:ae00/119        - exactly IPv6 network, matches to 1762::b03:1:ae00, 1762::B03:1:AF18, 1762::b03:1:afff
 *    - 1762:0:0:0:0:B03:1:AF18/119 - same as previous, NOTE network detected by prefix: 1762::b03:1:ae00/119
 *    - 1762:0:0:0:0:B03:1:AF18     - single IPv6 address
 *    - *::B03:1:AF18, 1762::*:AF18 - * matching by any string in address
 *    - ?::B03:1:AF18, 1762::?:AF18 - ? matching by single char
 *    - 1762:b03                    -  match by part of address, matches to 1:AF18:1762::B03, 1762::b03:1:ae00
 *
 * Return array contain this params:
 *    'query_type' - which query type required.
 *                   Possible: single       (single IP address),
 *                             network      (addresses inside network),
 *                             like, %like% (part of address with masks *, ?)
 *    'ip_version' - numeric IP version (4, 6)
 *    'ip_type'    - ipv4 or ipv6
 *    'address'    - string with passed IP address without prefixes or address part
 *    'prefix'     - detected network prefix
 *    'network'    - detected network with prefix
 *    'network_start' - first address of network
 *    'network_end'   - last address of network (broadcast)
 *
 * @param string $network Network/IP query string
 *
 * @return array|false Array with parsed network params
 */
function parse_network($network) {
    $network = trim($network);

    $array = [
      'query_type' => 'network', // Default query type by valid network with prefix
    ];

    if (preg_match('%^' . OBS_PATTERN_IPV4_NET . '$%', $network, $matches)) {
        // Match by valid IPv4 network
        $array['ip_version'] = 4;
        $array['ip_type']    = 'ipv4';
        $array['address']    = $matches['ipv4']; // Same as IP
        //$array['prefix']     = $matches['ipv4_prefix'];

        // Convert Cisco Inverse netmask to normal mask
        if (isset($matches['ipv4_inverse_mask'])) {
            $matches['ipv4_mask']    = inet_pton($matches['ipv4_inverse_mask']);
            $matches['ipv4_mask']    = inet_ntop(~$matches['ipv4_mask']); // Binary inverse and back to IP string
            $matches['ipv4_network'] = $matches['ipv4'] . '/' . $matches['ipv4_mask'];
        }

        if ($matches['ipv4_prefix'] == '32' || $matches['ipv4_mask'] === '255.255.255.255') {
            $array['prefix']        = '32';
            $array['network_start'] = $array['address'];
            $array['network_end']   = $array['address'];
            $array['network']       = $matches['ipv4_network']; // Network with prefix
            $array['query_type']    = 'single';                 // Single IP query
        } else {
            $address = Net_IPv4::parseAddress($matches['ipv4_network']);
            //print_vars($address);
            $array['prefix']        = $address -> bitmask . '';
            $array['network_start'] = $address -> network;
            $array['network_end']   = $address -> broadcast;
            $array['network']       = $array['network_start'] . '/' . $array['prefix']; // Network with prefix
        }
    } elseif (preg_match('%^' . OBS_PATTERN_IPV6_NET . '$%i', $network, $matches)) {
        // Match by valid IPv6 network
        $array['ip_version'] = 6;
        $array['ip_type']    = 'ipv6';
        $array['address']    = $matches['ipv6']; // Same as IP
        $array['prefix']     = $matches['ipv6_prefix'];
        if ($array['prefix'] == 128) {
            $array['network_start'] = $array['address'];
            $array['network_end']   = $array['address'];
            $array['network']       = $matches['ipv6_network']; // Network with prefix
            $array['query_type']    = 'single';                 // Single IP query
        } else {
            $address = Net_IPv6::parseAddress($array['address'], $array['prefix']);
            //print_vars($address);
            $array['network_start'] = $address['start'];
            $array['network_end']   = $address['end'];
            $array['network']       = $array['network_start'] . '/' . $array['prefix']; // Network with prefix
        }
    } elseif ($ip_version = get_ip_version($network)) {
        // Single IPv4/IPv6
        if ($ip_version === 6 && preg_match('/::ffff:(\d+\.\d+\.\d+\.\d+)/', $network, $matches)) {
            // IPv4 mapped to IPv6, like ::ffff:192.0.2.128
            // See: http://jira.observium.org/browse/OBSERVIUM-1274
            $network    = $matches[1];
            $ip_version = 4;
        }
        $array['ip_version'] = $ip_version;
        $array['address']    = $network;
        //$array['prefix']     = $matches['ipv6_prefix'];
        if ($ip_version === 4) {
            $array['ip_type'] = 'ipv4';
            $array['prefix']  = '32';
        } else {
            $array['ip_type'] = 'ipv6';
            $array['prefix']  = '128';
        }
        $array['network_start'] = $array['address'];
        $array['network_end']   = $array['address'];
        $array['network']       = $network . '/' . $array['prefix']; // Add prefix
        $array['query_type']    = 'single';                          // Single IP query
    } elseif (preg_match('/^[\d\.\?\*]+$/', $network)) {
        // Match IPv4 by mask
        $array['ip_version'] = 4;
        $array['ip_type']    = 'ipv4';
        $array['address']    = $network;
        if (str_contains_array($network, ['?', '*'])) {
            // If network contains * or !
            $array['query_type'] = 'like';
        } else {
            // All other cases
            $array['query_type'] = '%like%';
        }
    } elseif (preg_match('/^[abcdef\d\:\?\*]+$/i', $network)) {
        // Match IPv6 by mask
        $array['ip_version'] = 6;
        $array['ip_type']    = 'ipv6';
        $array['address']    = $network;
        if (str_contains_array($network, ['?', '*'])) {
            // If network contains * or !
            $array['query_type'] = 'like';
        } else {
            // All other cases
            $array['query_type'] = '%like%';
        }
    } else {
        // Not valid network string passed
        return FALSE;
    }

    // Add binary addresses for single and network queries
    switch ($array['query_type']) {
        case 'single':
            $array['address_binary'] = inet_pton($array['address']);
            break;
        case 'network':
            $array['network_start_binary'] = inet_pton($array['network_start']);
            $array['network_end_binary']   = inet_pton($array['network_end']);
            break;
    }

    return $array;
}

/**
 * Determines whether the supplied IP address is within the supplied network (IPv4 or IPv6).
 *
 * @param string $ip    IP Address
 * @param string $nets  IPv4/v6 networks
 * @param bool   $first Stop on first match
 *
 * @return bool Returns TRUE if address is found in supplied network, FALSE if it is not.
 */
// TESTME needs unit testing
function match_network($ip, $nets, $first = FALSE) {

    if ($first) {
        // Use old way for first match
        return match_network_first($ip, $nets);
    }

    $ip_version = get_ip_version($ip);
    if (!$ip_version) {
        return FALSE;
    }

    // Count required matches
    $count = 0;
    // Split match & exclude nets
    $includes = [];
    $excludes = [];
    foreach ((array)$nets as $net) {
        $exclude = str_starts_with($net, '!');
        if (!$exclude) {
            $count++; // Count total include matches IPv4+IPv6
        }

        if ($ip_version === 4 && !str_contains($net, '.')) {
            continue;
        }
        if ($ip_version === 6 && !str_contains($net, ':')) {
            continue;
        }
        if (!str_contains($net, '/')) {
            $net .= $ip_version === 4 ? '/32' : '/128'; // NET without mask as single IP
        }

        if ($exclude) {
            $excludes[] = preg_replace('/^\!/', '', $net);
        } else {
            $includes[] = $net;
        }
    }

    $match = !$count;
    foreach ($includes as $net) {
        if ($match = $ip_version === 4 ? Net_IPv4::ipInNetwork($ip, $net) : Net_IPv6::isInNetmask($ip, $net)) {
            break; // Stop loop if net found in match
        }
    }
    if ($match && !safe_empty($excludes)) {
        foreach ($excludes as $net) {
            if ($ip_version === 4 ? Net_IPv4::ipInNetwork($ip, $net) : Net_IPv6::isInNetmask($ip, $net)) {
                $match = FALSE;
                break;
            }
        }
    }

    return $match;
}

function match_network_first($ip, $nets) {

    $ip_version = get_ip_version($ip);
    if ($ip_version) {
        foreach ((array)$nets as $net) {
            $revert = (bool)preg_match('/^\!/', $net); // NOT match network
            if ($revert) {
                $net = preg_replace('/^\!/', '', $net);
            }

            if ($ip_version === 4) {
                if (!str_contains($net, '.')) {
                    continue; // NOT IPv4 net, skip
                }
                if (!str_contains($net, '/')) {
                    $net .= '/32'; // NET without mask as single IP
                }
                $ip_in_net = Net_IPv4::ipInNetwork($ip, $net);
            } else {
                //print_vars($ip); echo(' '); print_vars($net); echo(PHP_EOL);
                if (!str_contains($net, ':')) {
                    continue; // NOT IPv6 net, skip
                }
                if (!str_contains($net, '/')) {
                    $net .= '/128'; // NET without mask as single IP
                }
                $ip_in_net = Net_IPv6::isInNetmask($ip, $net);
            }

            if ($ip_in_net) {
                // Return TRUE if IP found in first match
                // Return FALSE if IP found in network where should NOT match
                return !$revert;
            }
        }
    }

    return FALSE;
}

/**
 * Convert binary string to hex, can be used for select binary fields.
 *
 * @param string $str Binary string
 *
 * @return string Hex humber
 */
function binary_to_hex($str) {
    $hex = '';
    foreach (str_split($str) as $char) {
        $hex .= str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
    }
    if (!empty($hex)) {
        $hex = '0x' . $hex;
    }
    return $hex;
}

/**
 * Convert HEX encoded IP value to pretty IP string
 *
 * Examples:
 *  IPv4 "C1 9C 5A 26" => "193.156.90.38"
 *  IPv4 "J}4:"        => "74.125.52.58"
 *  IPv6 "20 01 07 F8 00 12 00 01 00 00 00 00 00 05 02 72" => "2001:07f8:0012:0001:0000:0000:0005:0272"
 *  IPv6 "20:01:07:F8:00:12:00:01:00:00:00:00:00:05:02:72" => "2001:07f8:0012:0001:0000:0000:0005:0272"
 *
 * @param string $ip_hex HEX encoded IP address
 *
 * @return string IP address or original input string if not contains IP address
 */
function hex2ip($ip_hex) {
    //$ip = trim($ip_hex, "\"\t\n\r\0\x0B"); // Strange case, cleaned incorrectly
    $ip = trim($ip_hex, "\"\t\n\r\0");

    // IPv6z, ie: 2a:02:a0:10:80:03:00:00:00:00:00:00:00:00:00:01%503316482
    if (str_contains($ip, '%')) {
        $ip = explode('%', $ip)[0];
    }

    $len = strlen($ip);
    if ($len === 5 && $ip[0] === ' ') {
        $ip  = substr($ip, 1);
        $len = 4;
    }
    if ($len === 4) {
        // IPv4 hex string converted to SNMP string
        $ip  = str2hex($ip);
        $len = strlen($ip);
    }

    $ip = str_replace(' ', '', $ip);

    if ($len > 8) {
        // For IPv6
        $ip  = str_replace(':', '', $ip);
        $len = strlen($ip);
    }

    if (!ctype_xdigit($ip)) {
        return $ip_hex;
    }

    if ($len === 6) {
        // '90 7F 8A ', should be '90 7F 8A 00 ' ?
        $ip  .= '00';
        $len = 8;
    }

    //print_cli("IP: '$ip', LEN: $len\n");
    switch ($len) {
        case 8:
            // IPv4
            $ip_array = [];
            foreach (str_split($ip, 2) as $entry) {
                $ip_array[] = hexdec($entry);
            }
            $separator = '.';
            break;

        case 16:
            // Cisco incorrect IPv4 (54 2E 68 02 FF FF FF FF)
            $ip_array = [];
            foreach (str_split($ip, 2) as $i => $entry) {
                if ($i == 4) {
                    break;
                }
                $ip_array[] = hexdec($entry);
            }
            $separator = '.';
            break;

        case 32:
            // IPv6
            $ip_array  = str_split(strtolower($ip), 4);
            $separator = ':';
            break;

        default:
            // Try to convert hex string
            $ip = snmp_hexstring($ip_hex);
            if (get_ip_version($ip)) {
                return $ip;
            }
            return $ip_hex;
    }

    return implode($separator, $ip_array);
}

/**
 * Convert IP string to HEX encoded value.
 *
 * Examples:
 *  IPv4 "193.156.90.38" => "C1 9C 5A 26"
 *  IPv6 "2001:07f8:0012:0001:0000:0000:0005:0272" => "20 01 07 f8 00 12 00 01 00 00 00 00 00 05 02 72"
 *  IPv6 "2001:7f8:12:1::5:0272" => "20 01 07 f8 00 12 00 01 00 00 00 00 00 05 02 72"
 *
 * @param string $ip        IP address string
 * @param string $separator Separator for HEX parts
 *
 * @return string HEX encoded address
 */
function ip2hex($ip, $separator = ' ') {
    $ip_hex = trim($ip, " \"\t\n\r\0\x0B");

    switch (get_ip_version($ip_hex)) {
        case 4: // IPv4
            $ip_array = [];
            foreach (explode('.', $ip_hex) as $entry) {
                $ip_array[] = zeropad(dechex($entry));
            }
            break;

        case 6: // IPv6
            $ip_hex   = str_replace(':', '', ip_uncompress($ip_hex));
            $ip_array = str_split($ip_hex, 2);
            break;

        default: // Unknown
            return $ip;
    }

    return implode($separator, $ip_array);
}

function ip_whois($ip) {
    global $config;

    // Here common WHOIS info
    $response = '';
    if (safe_empty($config['http_proxy']) && is_executable($config['whois'])) {
        // Use direct whois cmd query (preferred)
        // NOTE, for now not tested and not supported for KRNIC, ie: 202.30.50.0, 2001:02B8:00A2::
        $cmd   = $config['whois'] . ' ' . escapeshellarg($ip);
        $whois = external_exec($cmd);

        if (str_contains($whois, '# start')) {
            // Some time whois return multiple (ie: whois 8.8.8.8), then use last
            $multi_whois = explode('# start', $whois);
            $whois = array_pop($multi_whois);
        }

        //$response .= "API: WHOIS CMD\n";
        $org = 0;
        foreach (explode("\n", $whois) as $line) {
            if (str_starts_with($line, '# end')) {
                break;
            }
            if (preg_match('/^(\w[\w\s\-\/]+):.*$/', $line, $matches)) {
                if (in_array($matches[1], [ 'Ref', 'source', 'nic-hdl-br' ])) {
                    if ($org === 1) {
                        $response .= PHP_EOL;
                        $org++;
                        continue;
                    }
                    break;
                }
                if (in_array($matches[1], [ 'Organization', 'org', 'mnt-irt' ])) {
                    $org++; // has org info
                } elseif (in_array($matches[1], [ 'Comment', 'ResourceLink' ])) {
                    continue; // skip comments
                }
                $response .= $line . PHP_EOL;
            }
        }

        return $response;
    }

    // Use RIPE whois API query
    $tags = [ 'app' => OBSERVIUM_PRODUCT . '-' . get_unique_id(), 'ip' => $ip ];
    if (($request = get_http_def('ripe_whois', $tags)) && safe_count($request['data']['records'])) {
        $whois_parts = [];
        foreach ($request['data']['records'] as $parts) {
            $key = $parts[0]['key'];

            if (in_array($key, [ 'NetRange', 'inetnum', 'inet6num' ])) {
                $org = 0;

                $whois_parts[0] = '';
                foreach ($parts as $part) {
                    if (in_array($part['key'], [ 'Ref', 'source', 'nic-hdl-br' ])) {
                        break;
                    }
                    if (in_array($part['key'], [ 'Organization', 'org', 'mnt-irt' ])) {
                        $org      = 1; // has org info
                        $org_name = $part['value'];
                    } elseif ($part['key'] === 'Comment') {
                        continue; // skip comments
                    }
                    $whois_parts[0] .= sprintf('%-16s %s' . PHP_EOL, $part['key'] . ':', $part['value']);
                }

            } elseif ($org === 1 && $key === 'OrgName' && strpos($org_name, $parts[0]['value']) === 0) {

                $whois_parts[1] = '';
                foreach ($parts as $part) {
                    if (in_array($part['key'], [ 'Ref', 'source', 'nic-hdl-br' ])) {
                        break;
                    }
                    if ($part['key'] === 'Comment') {
                        continue; // skip comments
                    }
                    $whois_parts[1] .= sprintf('%-16s %s' . PHP_EOL, $part['key'] . ':', $part['value']);
                }

                break;
            }
        }
        //array_unshift($whois_parts, "API: HTTP RIPE\n");
        $response .= implode(PHP_EOL, $whois_parts);

        //print_vars($request['data']['records']);
    }

    return $response;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function snmp2ipv6($ipv6_snmp) {
    $ipv6 = explode('.', $ipv6_snmp);

    // Workaround stupid Microsoft bug in Windows 2008 -- this is fixed length!
    // < fenestro> "because whoever implemented this mib for Microsoft was ignorant of RFC 2578 section 7.7 (2)"
    if (count($ipv6) === 17 && $ipv6[0] == 16) {
        array_shift($ipv6);
    }

    $ipv6_2 = [];
    for ($i = 0; $i <= 15; $i += 2) {
        $ipv6_2[] = zeropad(dechex($ipv6[$i])) . zeropad(dechex($ipv6[$i + 1]));
    }

    return implode(':', $ipv6_2);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function ipv62snmp($ipv6) {
    $ipv6_ex = explode(':', ip_uncompress($ipv6));
    for ($i = 0; $i < 8; $i++) {
        $ipv6_ex[$i] = zeropad($ipv6_ex[$i], 4);
    }

    $ipv6_ip    = implode('', $ipv6_ex);
    $ipv6_split = [];
    for ($i = 0; $i < 32; $i += 2) {
        $ipv6_split[] = hexdec(substr($ipv6_ip, $i, 2));
    }

    return implode('.', $ipv6_split);
}


// EOF
