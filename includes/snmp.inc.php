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

## If anybody has again the idea to implement the PHP internal library calls,
## be aware that it was tried and banned by lead dev Adam
##
## TRUE STORY. THAT SHIT IS WHACK. -- adama.

//CLEANME:
// parse_oid2()               - (deprecated) used in poller/discovery module mac-accounting, need rewrite

// `egrep -r 'snmpwalk_cache_oid *\( *\$' .       | grep -v snmp.inc.php | wc -l` => 519
// `egrep -r 'snmpwalk_cache_multi_oid *\( *\$' . | grep -v snmp.inc.php | wc -l` => 387
// snmpwalk_cache_multi_oid() - (duplicate) call to snmpwalk_cache_oid()

/**
 * MIB dirs generate functions
 */

/**
 * Generates a list of mibdirs in the correct format for net-snmp
 *
 * @param array  $mibs An array of MIB dirs or a string containing a single MIB dir
 *
 * @return string
 * @global array $config
 */
function mib_dirs($mibs = [])
{
    global $config;

    $dirs = [ $config['mib_dir'] . '/rfc', $config['mib_dir'] . '/net-snmp' ];

    foreach ((array)$mibs as $mib) {
        if (ctype_alnum(str_replace([ '-', '_' ], '', $mib))) {
            // If mib name equals 'mibs' just add root mib_dir to list
            $dirs[] = $mib === 'mibs' ? $config['mib_dir'] : $config['mib_dir'] . '/' . $mib;
        }
    }

    return implode(':', array_unique($dirs));
}


/**
 * Finds directories for requested MIBs as defined by the MIB definitions.
 *
 * @param string $mib One or more MIBs (separated by ':') to return the MIB dir for
 *
 * @return string Directories for requested MIBs, separated by ':' (for net-snmp)
 *
 */
function snmp_mib2mibdirs($mib)
{
    global $config;

    $def_mibdirs = [];

    // As we accept multiple MIBs separated by :, process them all for definition entries
    foreach (explode(':', $mib) as $xmib) {
        if (!empty($config['mibs'][$xmib]['mib_dir'])) // Array or non-empty string
        {
            // Add definition based MIB dir. Don't worry about deduplication, mib_dirs() sorts that out for us
            $def_mibdirs[] = (array)$config['mibs'][$xmib]['mib_dir'];
        }
    }

    // Always return set of mib dirs (prevent Cannot find module (LM-SENSORS-MIB): At line 1 in (none))
    return mib_dirs(array_merge([], ...$def_mibdirs));
}

/**
 * Expand ENTITY mib by vendor type MIB
 *
 * @param array  $device Device array
 * @param string $mib    List of MIBs, separated by ':'
 *
 * @return string New list of MIBs expanded by additional MIBs, separated by ':'
 */
function snmp_mib_entity_vendortype($device, $mib)
{
    global $config;

    $mibs = explode(':', $mib);

    if (!in_array('ENTITY-MIB', $mibs)) {
        // No entity mib in list, return original
        return $mib;
    }

    if (isset($config['os'][$device['os']]['vendortype_mib'])) {
        foreach (explode(':', $config['os'][$device['os']]['vendortype_mib']) as $vmib) {
            $mibs[] = $vmib;
        }
    } elseif (isset($config['os_group']['default']['vendortype_mib'])) {
        $mibs[] = $config['os_group']['default']['vendortype_mib'];
    }

    // Reimplode mibs list
    return implode(':', array_unique($mibs));
}

/**
 * Convert/parse/validate oids & values
 */

/**
 * De-wrap 32bit counters
 * Crappy function to get workaround 32bit counter wrapping in HOST-RESOURCES-MIB
 * See: http://blog.logicmonitor.com/2011/06/11/linux-monitoring-net-snmp-and-terabyte-file-systems/
 *
 * @param integer $value
 *
 * @return integer
 */
function snmp_dewrap32bit($value)
{
    if (is_numeric($value) && $value < 0) {
        return ($value + 4294967296);
    }

    return $value;
}

/**
 * Combine High and Low sizes into full 64bit size
 * Used in UCD-SNMP-MIB and NIMBLE-MIB
 * Note, this function required 64bit system!
 *
 * @param integer $high High bits value
 * @param integer $low  Low bits value
 *
 * @return integer|null Result sum 64bit
 */
function snmp_size64_high_low($high, $low)
{
    if (is_numeric($high) && is_numeric($low)) {
        return $high * 4294967296 + $low;
        //return $high << 32 + $low;
    }
    return NULL;
}

/**
 * Clean returned numeric data from snmp output
 * Supports only non-scientific numbers
 * Examples: "  20,4" -> 20.4
 *
 * @param string|int|float $value
 * @param string           $unit Value unit, for possible numeric conversion, currently known only timeticks
 *
 * @return mixed $numeric
 */
function snmp_fix_numeric($value, $unit = NULL) {
    if (is_numeric($value)) {
        return $value + 0; // If already numeric just return value
    }
    if (!is_string($value)) {
        return $value;     // Non string values just return as is
    }

    $value = trim($value, " \t\n\r\0\x0B\"");

    switch ($unit) {
        case 'timeticks':
            // Timeticks unit need convert before scale
            return timeticks_to_sec($value, TRUE);

        case 'bytes':
        case 'bits':
            // Additionally, try to convert bytes/bits
            return unit_string_to_numeric($value);

        case 'hex':
            return hexdec($value);

        case 'bin':
            return bindec($value);

        case 'oct':
            return octdec($value);
    }

    // Case for numeric and split:
    // 20% (cpu1: 28%   cpu2: 13%)
    if ($brackets = preg_match('/^(?<value>[+\-]?\d+[^\(\)]*)\((?<bracket>[^\(\)]+)\)$/', $value, $brackets_matches)) {
        $value = $brackets_matches['value'];
    }

    if ($unit && str_starts_with($unit, 'split')) {
        $values = [];
        if (str_contains($unit, 'lane')) {
            // TROPIC-OPTICALPORT-MIB::tnOtPortRxLanePowers.16843520 = STRING:
            // Lane  1:  1.01 dBm
            // Lane  2:  1.29 dBm
            // Lane  3:   2.1 dBm
            // Lane  4:  2.71 dBm
            $split_lane_pattern = '/Lane\s+1\s*:\s*(?<split_lane1>.*?)\s*Lane\s+2\s*:\s*(?<split_lane2>.*?)\s*Lane\s+3\s*:\s*(?<split_lane3>.*?)\s*Lane\s+4\s*:\s*(?<split_lane4>.*?)$/s';
            preg_match($split_lane_pattern, $value, $values);
            // print_vars($unit);
            // print_vars($value);
        } else {
            if (str_contains($value, ':')) {
                // Ie: CPU Load (100ms, 1s, 10s) : 0%, 2%, 3%
                $value = trim(explode(':', $value)[1]);
            }
            if ($brackets && preg_match_all('/\w+\:\s*([\+\-]*\d\S+)/', $brackets_matches['bracket'], $matches)) {
                // 20% (cpu1: 28%   cpu2: 13%)
                $split = $matches[1];
            } elseif (str_contains($value, '(') &&
                preg_match_all('/(\d+\s*(?:sec|min))s? \(\s*([\d\.\+\-]+).*?\),?/i', $value, $matches)) {
                // 5 Secs (  6.510%)   60 Secs (  7.724%)  300 Secs (  6.3812%)
                //echo PHP_EOL; print_vars($matches);
                $split = $matches[2];
            } else {
                // Another hack for FIBERSTORE-MIB/FS-SWITCH-MIB multi lane DOM sensors
                // Also see NETAPP-MIB sensors
                $split = explode(',', $value);
            }
            foreach ($split as $i => $v) {
                $key = 'split' . ($i + 1);
                $values[$key] = trim($v);
            }
        }
        print_debug_vars($values);
        if (is_numeric($values[$unit])) {
            // no need extra fixates
            return $values[$unit] + 0;
        }
        $value = $values[$unit];
    }

    // Possible more derp case:
    // CPU Temperature-Ctlr B: 58 C 136.40F
    foreach (array_reverse(explode(': ', $value)) as $numeric) {
        // Clean prepend texts, ie: Spinning at 5160 RPM
        $numeric = preg_replace('/^([a-z]+ )+/i', '', $numeric);
        $numeric = explode(' ', $numeric)[0];
        // 3.09(W-)
        $numeric = explode('(', $numeric)[0];
        $numeric = preg_replace('/[^0-9a-z\-,\.]/i', '', $numeric);
        // Some retarded devices report data with spaces and commas: STRING: "  20,4"
        $numeric = str_replace(',', '.', $numeric);
        if (is_numeric($numeric)) {
            // If cleaned data is numeric return number
            return $numeric + 0;
        }
        if (preg_match('/^(\d+(?:\.\d+)?)[a-z]+$/i', $numeric, $matches)) {
            // Number with unit, ie "8232W"
            return $matches[1] + 0;
        }
    }

    // Else return original value
    return $value;
}

/**
 * Fixed ascii coded chars in snmp string as correct UTF-8 chars.
 * Convert all Mac/Windows newline chars (\r\n, \r) to Unix char (\n)
 *
 * NOTE, currently support only one-byte unicode
 *
 * Examples: "This is a &#269;&#x5d0; test&#39; &#250;" -> "This is a čא test' ú"
 *           "P<FA>lt stj<F3>rnst<F6><F0>"              -> "Púlt stjórnstöð"
 *
 * @param string $string
 *
 * @return string $string
 */
function snmp_fix_string($string)
{
    if (!preg_match('/^[[:print:]\p{L}]*$/mu', $string)) {
        // find unprintable and all unicode chars, because old pcre library not always detect orb
        $debug_msg = '>>> Non-printable characters found in string:' . PHP_EOL . $string;
        $string    = preg_replace_callback('/[^[:print:]\x00-\x1F\x80-\x9F]/m', 'convert_ord_char', $string);
        print_debug($debug_msg . PHP_EOL . '>>> Converted to:' . PHP_EOL . $string . PHP_EOL);
    }

    // Convert all Mac/Windows newline chars (\r\n, \r) to Unix char (\n)
    return nl2nl($string);
}

/**
 * Convert an SNMP hex string to regular string
 *
 * @param string $string HEX string
 * @param string $eol    Symbol used as EOL (hex 00), default is \n, but last EOL removed
 *
 * @return string
 */
function snmp_hexstring($string, $eol = "\n")
{
    if (is_hex_string($string) &&
        !preg_match('/^[a-f\d]{2}$/i', $string)) {// Exclude just values (10..99, A1, b3)
        $ascii = hex2str($string, $eol);
        //snmp_fix_string($ascii);
        // clear last EOL CHAR
        return rtrim($ascii, $eol);
    }
    return $string;
}

/**
 * Clean SNMP value, ie: trim quotes, spaces, remove "wrong type", fix incorrect UTF8 strings, etc
 *
 * @param string  $value Value
 * @param integer $flags OBS_SNMP_* flags
 *
 * @return    string            Cleaned value
 */
function snmp_value_clean($value, $flags = OBS_SNMP_ALL)
{
    // For null just return NULL
    if (NULL === $value) {
        return NULL;
    }

    // Clean quotes and trim
    $value = trim_quotes($value, $flags);

    if (str_starts($value, 'Wrong Type')) {
        // Remove Wrong Type string
        $value = preg_replace('/Wrong Type .*?: (.*)/s', '\1', $value);
    } elseif (is_flag_set(OBS_SNMP_HEX | OBS_SNMP_ASCII, $flags, TRUE)) {
        // NOTE. This required only because net-snmp 5.7 have UTF8 issue,
        // which not exist in 5.4 and 5.8+, see:
        // https://sourceforge.net/p/net-snmp/bugs/2815/
        // Convert HEX strings to ASCII string (with possible UTF8)
        $hex   = $value;
        $value = snmp_hexstring($hex);
        if ($hex !== $value) {
            // When HEX converted, trim again
            $debug_msg = "SNMP Hex string converted..\n   HEX: $hex\nSTRING: $value\n";
            $value     = trim_quotes($value, $flags);
        }
    }

    // Fix incorrect UTF8 strings
    if (is_flag_set(OBS_DECODE_UTF8, $flags)) {
        $old   = $value;
        $value = snmp_fix_string($value);
        if (OBS_DEBUG && $old !== $value) {
            if (!isset($debug_msg)) {
                $debug_msg = "Incorrect UTF8 string converted..\n   OLD: $old\n";
            }
            $debug_msg .= "  UTF8: $value\n";
        }
    }
    if (isset($debug_msg)) {
        print_debug($debug_msg);
    }

    return $value;
}

/**
 * Convert an SNMP index string (with len!) to regular string
 * Opposite function for snmp_string_to_oid()
 * Example:
 *  9.79.98.115.101.114.118.105.117.109 -> Observium
 *
 * @param string $index
 *
 * @return string
 */
function snmp_oid_to_string($index)
{
    $index = (string)$index;
    if ($index === '0') {
        return '';
    } // This is just empty string!

    if (preg_match(OBS_PATTERN_SNMP_OID_NUM, $index, $matches)) {
        $str_parts = explode('.', $matches[1]);
        $str_len   = array_shift($str_parts);
        if ($str_len != count($str_parts)) {
            // break, incorrect index string (str len not match)
            return $index;
        }
        $string = '';
        foreach ($str_parts as $char) {
            if ($char > 255) {
                // break, incorrect index string
                return $index;
            }
            $string .= zeropad(dechex($char));
        }
        return hex2str($string);
    }

    return $index;
}

/**
 * Convert ASCII string to an SNMP index (with len!)
 * Opposite function for snmp_oid_to_string()
 * Example:
 *  Observium -> 9.79.98.115.101.114.118.105.117.109
 *
 * @param string $string
 *
 * @return string
 */
function snmp_string_to_oid($string)
{
    // uses the first octet of index as length
    $index = strlen((string)$string);
    if ($index === 0) {
        // Empty string
        return (string)$index;
    }

    // converts the index as string to decimal ascii codes
    foreach (str_split($string) as $char) {
        $index .= '.' . ord($char);
    }

    return $index;
}

/**
 * Check if returned snmp value is valid
 *
 * @param string $value
 *
 * @return bool
 */
function is_valid_snmp_value($value)
{
    return !str_contains_array($value, [ 'at this OID', 'No more variables left' ]) &&
           $value !== 'NULL' && $value !== 'null' && $value !== NULL;
}

/**
 * Parse each line in output from snmpwalk into:
 *   oid (raw), oid_name, index, index_parts, index_count, value
 *
 * This parser always used snmpwalk with base options: -OQUs
 * and allowed to use additional options: bnexX
 *
 * Value always cleaned from unnecessary data by snmp_value_clean()
 *
 * @param string  $line  snmpwalk output line
 * @param integer $flags Common snmp flags
 *
 * @return array Array with parsed values
 */
function snmp_parse_line($line, $flags = OBS_SNMP_ALL)
{
    /**
     * Note, this is parse line only for -OQUs (and additionally: bnexX)
     *  Q - Removes the type information when displaying varbind values: SNMPv2-MIB::sysUpTime.0 = 1:15:09:27.63
     *  U - Do not print the UNITS suffix at the end of the value
     *  s - Do not display the name of the MIB
     *  b - Display table indexes numerically: SNMP-VIEW-BASED-ACM-MIB::vacmSecurityModel.0.3.119.101.115 = xxx
     *  n - Displays the OID numerically: .1.3.6.1.2.1.1.3.0 = Timeticks: (14096763) 1 day, 15:09:27.63
     *  e - Removes the symbolic labels from enumeration values: forwarding(1) -> 1
     *  x - Force display string values as Hex strings
     *  X - Display table indexes in a more "program like" output: IPv6-MIB::ipv6RouteIfIndex[3ffe:100:ff00:0:0:0:0:0][64][1] = 2
     */

    [$r_oid, $value] = explode(' =', $line, 2);
    $r_oid = trim($r_oid);
    $value = snmp_value_clean($value, $flags);

    $array = ['oid'   => $r_oid,
              'value' => $value];

    if (is_flag_set(OBS_SNMP_NUMERIC, $flags)) {
        // For numeric, just return raw oid and value
        // Example: .1.3.6.1.2.1.1.3.0 = 15:09:27.63
        if (isset($r_oid[0])) {
            // I think not possible, but I leave this here, just in case --mike
            //if ($r_oid[0] !== '.')
            //{
            //  $array['index'] = '.' . $array['index'];
            //}
            $array['index_count'] = 1;
        } else {
            $array['index_count'] = 0;
        }
        $array['index'] = $r_oid;
        return $array;
    }

    if ($is_table = is_flag_set(OBS_SNMP_TABLE, $flags)) {
        // For table use another parse rules
        // Example: ipv6RouteIfIndex[3ffe:100:ff00:0:0:0:0:0][64][1]
        // also mixed index, ie wcAccessPointMac[6:20:c:c8:39:b].96
        //                      wcAccessPointMac[6:20:c:c8:39:b]."sdf sdkfps"
        //if (preg_match('/^(\S+?)((?:\[.+?\])+)((?:\.\d+)+)?/', $r_oid, $matches))
        if (preg_match('/^(\S+?)((?:\[.+?\])+)((?:\.(?:\d+|"[^"]*"))+)?/', $r_oid, $matches)) {
            $oid_parts = explode('][', trim($matches[2], '[]')); // square brackets part
            array_unshift($oid_parts, $matches[1]);              // Oid name
            if (isset($matches[3])) {                            // Numeric part (if exist)
                $oid_parts = array_merge($oid_parts, explode('.', ltrim($matches[3], '.')));
            }
            // Clean paired quotes from index parts (foreach always faster)
            //array_walk($oid_parts, static function(&$val) { $val = trim_quotes($val, OBS_QUOTES_TRIM); });
            foreach ($oid_parts as &$val) {
                $val = trim_quotes($val, OBS_QUOTES_TRIM);
            }
        } else {
            // Incorrect?
            $oid_parts = [];
        }
        //foreach (explode('[', $r_oid) as $oid_part)
        //{
        //  $oid_parts[] = rtrim($oid_part, ']');
        //}
    } elseif (($double = str_contains($r_oid, '"')) ||
              ($single = str_contains($r_oid, "'"))) {
        // Example: jnxVpnPwLocalSiteId.l2Circuit."ge-0/1/1.0".621
        $oid_part  = $r_oid;
        $oid_parts = [];
        do {
            if ($double && preg_match('/^"([^"]*)"(?:\.(.+))?/', $oid_part, $matches)) {
                // Part with stripes
                $oid_parts[] = $matches[1];
                $oid_part    = $matches[2]; // Next part
            } elseif ($single && preg_match("/^'([^']*)'(?:\.(.+))?/", $oid_part, $matches)) {
                // Part with stripes
                $oid_parts[] = $matches[1];
                $oid_part    = $matches[2]; // Next part
            } else {
                $matches     = explode('.', $oid_part, 2);
                $oid_parts[] = $matches[0];
                $oid_part    = $matches[1]; // Next part
            }
            // print_vars($matches);
        } while (strlen($oid_part) > 0);
        // print_vars($oid_parts);
    } else {
        // Simple, not always correct
        // Example: vacmSecurityModel.0.3.119.101.115
        $oid_parts = explode('.', $r_oid);
    }
    $array['oid_name'] = array_shift($oid_parts);
    //$array['oid_name']    = end(explode('::', $array['oid_name'], 2)); // We use -Os
    $array['index_parts'] = $oid_parts;
    $array['index_count'] = count($oid_parts);
    if (is_flag_set(OBS_SNMP_INDEX_PARTS, $flags)) {
        // Implode index parts as parsable string, ie:
        // hwIfIpAddrEntVpn[56][10.0.43.86] to 56->10.0.43.86
        $array['index'] = implode('->', $oid_parts);
    } else {
        $array['index'] = implode('.', $oid_parts);
    }

    if (OBS_DEBUG && $array['index_count'] == 0 && strlen($array['oid_name']) &&
        !is_flag_set(OBS_SNMP_NOINDEX, $flags)) {
        print_debug("Warning. SNMP line '$line' not have index part");
        print_debug_vars($array);
        //var_dump($array);
    }

    return $array;
}

// Translate OID string to numeric:
//'BGP4-V2-MIB-JUNIPER::jnxBgpM2PeerRemoteAs' -> '.1.3.6.1.4.1.2636.5.1.1.2.1.1.1.13'
// or numeric OID to string:
// '.1.3.6.1.4.1.9.1.685' -> 'ciscoAIRAP1240'
// DOCME needs phpdoc block
// TESTME needs unit testing
function snmp_translate($oid, $mib = NULL, $mibdir = NULL)
{
    // $rewrite_oids set in rewrites.inc.php
    global $config;

    if (empty($mib) && str_contains($oid, '::')) {
        // Split Oid names passed as full (ie SNMPv2-MIB::sysUpTime) into MIB name (SNMPv2-MIB) and Oid (sysUpTime)
        [$mib, $oid] = explode('::', $oid);
    }

    if (preg_match(OBS_PATTERN_SNMP_OID_NUM, $oid)) {
        // Numeric OID to Named
        $options = '-Os';
    } elseif ($mib) {
        // Named to Numeric
        if (isset($config['mibs'][$mib]['translate'][$oid])) {
            print_debug("SNMP TRANSLATE (REWRITE): '$mib::$oid' -> '" . $config['mibs'][$mib]['translate'][$oid] . "'");
            return $config['mibs'][$mib]['translate'][$oid];
        }

        $oid = $mib . '::' . $oid;
    }

    $cmd = $config['snmptranslate'];
    if (isset($options)) {
        $cmd .= ' ' . $options;
    } else {
        $cmd .= ' -On';
    }

    // Hardcode ignoring underscore parsing errors because net-snmp is dumb as a bag of rocks
    // -Pu    Toggles whether to allow the underline character in MIB object names and other symbols.
    //        Strictly speaking, this is not valid SMI syntax, but some vendor MIB files define such names.
    $cmd .= ' -Pu';

    if ($mib) {
        $cmd .= ' -m ' . $mib;
    }

    // Set correct MIB directories based on passed dirs and OS definition
    // If $mibdir variable is passed to the function, we use it directly
    if ($mibdir) {
        $cmd .= " -M $mibdir";
    } else {
        $cmd .= ' -M ' . snmp_mib2mibdirs($mib);
    }

    $cmd .= ' \'' . $oid . '\'';
    if (!OBS_DEBUG) {
        $cmd .= ' 2>/dev/null';
    }

    $data = trim(external_exec($cmd, $exec_status));

    $GLOBALS['snmp_stats']['snmptranslate']['count']++;
    $GLOBALS['snmp_stats']['snmptranslate']['time'] += $exec_status['runtime'];


    if ($data && !str_contains($data, 'Unknown')) {
        print_debug("SNMP TRANSLATE (CMD): '$oid' -> '" . $data . "'");
        return $data;
    }
    return '';
}


/**
 * Common SNMP functions for generate cmd and log errors
 */

/**
 * Build a commandline string for net-snmp commands.
 *
 * @param string  $command
 * @param array   $device
 * @param string  $oids
 * @param string  $options
 * @param string  $mib
 * @param string  $mibdir Optional, correct path should be set in the MIB definition
 * @param integer $flags
 *
 * @return string
 * @global array $config
 * @global array $cache
 */
// TESTME needs unit testing
function snmp_command($command, $device, $oids, $options, $mib = NULL, &$mibdir = NULL, $flags = OBS_SNMP_ALL) {
    global $config, $cache;

    get_model_array($device); // Pre-cache model options (if required)

    // Get the full command path from the config. Choice between bulkwalk and walk. Add max-reps if needed.
    switch ($command) {
        case 'snmpwalk':
            if ($nobulk = snmp_nobulk($device, $mib)) {
                $cmd = $config['snmpwalk'];
                // Append end oid (can speedup snmpwalk in some cases)
                if (isset($GLOBALS['snmp_oid_end'])) {
                    $options .= ' -CE ' . escapeshellarg($GLOBALS['snmp_oid_end']);
                    print_debug("Added Oid END '{$GLOBALS['snmp_oid_end']}' for snmpwalk '$oids'.");
                    unset($GLOBALS['snmp_oid_end']);
                }
            } else {
                $cmd = $config['snmpbulkwalk'] . snmp_gen_maxrep($device, $mib);

                // This option no effect on snmpbulkwalk
                if (isset($GLOBALS['snmp_oid_end'])) {
                    unset($GLOBALS['snmp_oid_end']);
                }
            }

            $cmd .= snmp_gen_noincrease($device, $mib, $flags);

            break;
        case 'snmpget':
        case 'snmpgetnext':
            $nobulk = TRUE; // reset bulk here
            $cmd    = $config[$command];
            break;
        case 'snmpbulkget':
            // NOTE. Currently, not used by us
            if ($nobulk = snmp_nobulk($device, $mib)) {
                $cmd = $config['snmpget'];
            } else {
                $cmd = $config['snmpbulkget'] . snmp_gen_maxrep($device, $mib);
            }
            break;
        default:
            print_error("Unknown command $command passed to snmp_command(). THIS SHOULD NOT HAPPEN. PLEASE REPORT TO DEVELOPERS.");
            return FALSE;
    }

    // Set timeout values if set in the database, otherwise set to configured defaults
    if (is_numeric($device['snmp_timeout']) && $device['snmp_timeout'] > 0) {
        $snmp_timeout = $device['snmp_timeout'];
    } elseif (isset($config['snmp']['timeout'])) {
        $snmp_timeout = $config['snmp']['timeout'];
    }
    if (isset($snmp_timeout)) {
        $cmd .= ' -t ' . escapeshellarg($snmp_timeout);
    }

    // Set retries if set in the database, otherwise set to configured defaults
    if (is_numeric($device['snmp_retries']) && $device['snmp_retries'] >= 0) {
        $snmp_retries = $device['snmp_retries'];
    } elseif (isset($config['snmp']['retries'])) {
        $snmp_retries = $config['snmp']['retries'];
    }
    if (isset($snmp_retries)) {
        $cmd .= ' -r ' . escapeshellarg($snmp_retries);
    }

    // If no specific transport is set for the device, default to UDP.
    if (empty($device['snmp_transport'])) {
        $device['snmp_transport'] = 'udp';
    }

    // If no specific port is set for the device, default to 161.
    if (!$device['snmp_port']) {
        $device['snmp_port'] = 161;
    }

    // Add the SNMP authentication settings for the device
    $cmd .= snmp_gen_auth($device);

    // Hardcode ignoring underscore parsing errors because net-snmp is dumb as a bag of rocks
    // -Pu    Toggles whether to allow the underline character in MIB object names and other symbols.
    //        Strictly speaking, this is not valid SMI syntax, but some vendor MIB files define such names.
    // -Pd    Disables the loading of MIB object DESCRIPTIONs when parsing MIB files.
    //        This reduces the amount of memory used by the running application.
    $cmd .= ' -Pud';

    // Disables the use of DISPLAY-HINT information when assigning values.
    if (is_flag_set(OBS_SNMP_HEX | OBS_SNMP_DISPLAY_HINT, $flags)) {
        $cmd .= ' -Ih';
    }

    if ($options) {
        $cmd .= ' ' . $options;
    }
    if ($mib) {
        $cmd .= ' -m ' . $mib;
    }

    // Set correct MIB directories based on passed dirs and OS definition
    // If $mibdir variable is passed, we use it directly
    if ($mibdir !== FALSE) {
        // Do not pass mibdirs when false (for snmp_dump())
        if (empty($mibdir)) {
            // Change to correct mibdir, required for store in snmp_errors
            $mibdir = snmp_mib2mibdirs($mib);
        }
        $cmd .= " -M $mibdir";
    }

    // Add the device URI to the string
    $cmd .= ' ' . escapeshellarg($device['snmp_transport']) . ':' . escapeshellarg(device_host($device, TRUE)) . ':' . escapeshellarg($device['snmp_port']);

    // Add the OID(s) to the string
    $oids = trim($oids);
    if ($oids === '') {
        print_error("Empty oids passed to snmp_command(). THIS SHOULD NOT HAPPEN. PLEASE REPORT TO DEVELOPERS.");
        $GLOBALS['snmp_command'] = $cmd;
        return FALSE;
    }
    $cmd                     .= ' ' . addslashes($oids); // Quote slashes for string indexes
    $GLOBALS['snmp_command'] = $cmd;

    // Set global snmpbulk status
    $GLOBALS['snmp_bulk'] = !$nobulk;

    // If we're not debugging, direct errors to /dev/null.
    if (!OBS_DEBUG) {
        $cmd .= ' 2>/dev/null';
    }

    return $cmd;
}

function snmp_nobulk($device, $mib = NULL) {
    global $config, $cache;

    if ($device['snmp_version'] === 'v1') {
        print_debug("SNMP v1 does not support bulk.");
        return TRUE;
    }
    if (isset($device['snmp_nobulk']) && $device['snmp_nobulk']) {
        print_debug("Device-specific no-bulk option.");
        return TRUE;
    }
    if (is_numeric($device['snmp_maxrep'])) {
        $nobulk = $device['snmp_maxrep'] <= 0;
        print_debug("Device-specific maxrep ".($nobulk ? "0 or less" : $device['snmp_maxrep']) . ".");
        return $nobulk;
    }
    if (isset($cache['devices']['model'][$device['device_id']]['snmp']['nobulk']) &&
              $cache['devices']['model'][$device['device_id']]['snmp']['nobulk']) {
        print_debug("Device model-specific no-bulk definition.");
        return TRUE;
    }
    if (isset($config['os'][$device['os']]['snmp']['nobulk']) &&
              $config['os'][$device['os']]['snmp']['nobulk']) {
        print_debug("OS-specific no-bulk definition.");
        return TRUE;
    }
    if ($mib && isset($config['mibs'][$mib]['snmp']['nobulk']) &&
        $config['mibs'][$mib]['snmp']['nobulk']) {
        print_debug("MIB-specific no-bulk definition.");
        return TRUE;
    }

    return FALSE;
}

function snmp_gen_maxrep($device, $mib = NULL) {
    global $config, $cache;

    // NOTE, for snmpbulkget max-repetitions work different from for snmpbulkwalk,
    // it's returned exactly number (max as possible) _next_ Oid entries.
    // Default in net-snmp is 10, this can cause troubles if passed oids more than 10
    if (is_numeric($device['snmp_maxrep'])) {
        // Device specific
        return ' -Cr' . escapeshellarg($device['snmp_maxrep']);
    }
    if ($config['snmp']['max-rep']) {
        // Device model specific
        if (isset($cache['devices']['model'][$device['device_id']]['snmp']['max-rep']) &&
            is_numeric($cache['devices']['model'][$device['device_id']]['snmp']['max-rep'])) {
            // Model specific can be FALSE
            return ' -Cr' . escapeshellarg($cache['devices']['model'][$device['device_id']]['snmp']['max-rep']);
        }
        if (isset($config['os'][$device['os']]['snmp']['max-rep']) &&
            is_numeric($config['os'][$device['os']]['snmp']['max-rep'])) {
            // OS specific
            return ' -Cr' . escapeshellarg($config['os'][$device['os']]['snmp']['max-rep']);
        }
        if (isset($config['mibs'][$mib]['snmp']['max-rep']) &&
            is_numeric($config['mibs'][$mib]['snmp']['max-rep'])) {
            // mib specific
            return ' -Cr' . escapeshellarg($config['mibs'][$mib]['snmp']['max-rep']);
        }
    }

    return '';
}

function snmp_gen_noincrease($device, $mib = NULL, $flags = OBS_SNMP_ALL) {
    global $config, $cache;

    // do not check returned OIDs are increasing
    if (is_flag_set(OBS_SNMP_NOINCREASE, $flags)) {
        return ' -Cc';
    }
    if (isset($cache['devices']['model'][$device['device_id']]['snmp']['noincrease']) &&
         $cache['devices']['model'][$device['device_id']]['snmp']['noincrease']) {
        // device model specific definition
        return ' -Cc';
    }
    if (isset($config['os'][$device['os']]['snmp']['noincrease']) &&
        $config['os'][$device['os']]['snmp']['noincrease']) {
        // os specific definition
        return ' -Cc';
    }
    if (isset($config['mibs'][$mib]['snmp']['noincrease']) &&
        $config['mibs'][$mib]['snmp']['noincrease']) {
        // mib specific definition
        return ' -Cc';
    }

    return '';
}

/**
 * Build authentication for net-snmp commands using device array
 *
 * @param array $device
 *
 * @return string
 */
// TESTME needs unit testing
function snmp_gen_auth($device)
{
    $cmd = '';

    switch ($device['snmp_version']) {
        case 'v3':
            $cmd = ' -v3 -l ' . escapeshellarg($device['snmp_authlevel']);
            /* NOTE.
             * For proper work of 'vlan-' context on cisco, it is necessary to add 'match prefix' in snmp-server config --mike
             * example: snmp-server group MONITOR v3 auth match prefix access SNMP-MONITOR
             */
            $cmd .= ' -n ' . escapeshellarg($device['snmp_context']); // Some devices, like HP, always require option '-n'

            switch ($device['snmp_authlevel']) {
                case 'authPriv':
                    $cmd .= ' -x ' . escapeshellarg($device['snmp_cryptoalgo']);
                    $cmd .= ' -X ' . escapeshellarg($device['snmp_cryptopass']);
                // no break here
                case 'authNoPriv':
                    $cmd .= ' -a ' . escapeshellarg($device['snmp_authalgo']);
                    $cmd .= ' -A ' . escapeshellarg($device['snmp_authpass']);
                    $cmd .= ' -u ' . escapeshellarg($device['snmp_authname']);
                    break;
                case 'noAuthNoPriv':
                    // We have to provide a username anyway (see Net-SNMP doc)
                    if (!safe_empty($device['snmp_authname'])) {
                        $cmd .= ' -u ' . escapeshellarg($device['snmp_authname']);
                    } else {
                        $cmd .= ' -u observium';
                    }
                    break;
                default:
                    print_error('ERROR: Unsupported SNMPv3 snmp_authlevel (' . $device['snmp_authlevel'] . ')');
            }
            break;

        case 'v2c':
        case 'v1':
            $cmd = ' -' . $device['snmp_version'];

            if (isset($device['snmp_context']) && !safe_empty($device['snmp_context'])) {
                // Community based context
                $cmd .= ' -c ' . escapeshellarg($device['snmp_community'] . '@' . $device['snmp_context']);
            } else {
                $cmd .= ' -c ' . escapeshellarg($device['snmp_community']);
            }

            break;
        default:
            print_error('ERROR: ' . $device['snmp_version'] . ' : Unsupported SNMP Version.');
    }

    if (OBS_DEBUG === 1 && !$GLOBALS['config']['snmp']['hide_auth']) {
        $debug_auth = "DEBUG: SNMP Auth options = $cmd";
        print_debug($debug_auth);
    }

    return $cmd;
}

/**
 * Generate common snmp output options (-O)
 *
 * @param string  $command SNMP command
 * @param integer $flags   SNMP flags
 *
 * @return string Options string -Oxxx
 */
function snmp_gen_options($command = 'snmpwalk', $flags = 0)
{

    // Basic options,
    // NOTE: 's' has no effect with 'v',
    //       'Q' better than 'q' (no more Wrong Type):
    switch ($command) {
        case 'snmpget':
            // get need only varbind values (without Oid)
            $output = 'QUv';
            break;
        case 'snmpdump':
            return '--hexOutputLength=0 -Ih -ObentxU';
        case 'snmpwalk':
        default:
            // walk require output with Oid.index = value
            $output = 'QUs';
    }

    if (is_flag_set(OBS_SNMP_NUMERIC_INDEX, $flags)) {
        $output .= 'b';
    }
    if (is_flag_set(OBS_SNMP_NUMERIC, $flags)) {
        $output .= 'n';
    }
    if (is_flag_set(OBS_SNMP_ENUM, $flags)) {
        $output .= 'e';
    }
    // if both flags set (OBS_SNMP_HEX & OBS_SNMP_ASCII), net-snmp prefer ASCII
    // but we use this trick for fix issue in net-snmp with UTF8, see snmp_value_clean()
    if (is_flag_set(OBS_SNMP_HEX, $flags)) {
        $output .= 'x';
        $extra  = '--hexOutputLength=0';
    } elseif (is_flag_set(OBS_SNMP_ASCII, $flags)) {
        $output .= 'a';
    }
    if (is_flag_set(OBS_SNMP_TABLE, $flags)) {
        $output .= 'X';
    } // Seems as numeric index and table index not compatible
    if (is_flag_set(OBS_SNMP_TIMETICKS, $flags)) {
        $output .= 't';
    } // Display TimeTicks values as raw numbers: SNMPv2-MIB::sysUpTime.0 = 14096763

    $output = "-O$output";
    if (isset($extra)) {
        $output .= " $extra";
    }

    return $output;
}

/**
 * Detect SNMP errors and log it in DB.
 * Error logged in poller modules only, all other just return error code
 *
 * @param string       $command Used snmp command (ie: snmpget, snmpwalk)
 * @param array        $device  Device array (device_id not allowed)
 * @param string|array $oid     SNMP oid string
 * @param string       $options SNMP options
 * @param string       $mib     SNMP MIBs list
 * @param string       $mibdir  SNMP MIB dirs list
 * @param array        $exec_status external_exec() status array
 *
 * @return int              Numeric error code. Full list error codes see in definitions: $config['snmp']['errorcodes']
 */
function snmp_log_errors($command, $device, $oid, $options, $mib, $mibdir, $exec_status)
{
    global $config;
    
    /** constants from definitions
     * OBS_SNMP_ERROR_CACHED : -1
     * OBS_SNMP_ERROR_OK : 0
     * OBS_SNMP_ERROR_EMPTY_RESPONSE : 1
     * OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED : 2
     * OBS_SNMP_ERROR_TOO_LONG_RESPONSE : 3
     * OBS_SNMP_ERROR_TOO_BIG_MAX_REPETITION_IN_GETBULK : 4
     * OBS_SNMP_ERROR_ISSNMPABLE : 900
     * OBS_SNMP_ERROR_AUTHENTICATION_FAILURE : 991
     * OBS_SNMP_ERROR_OID_NOT_INCREASING : 993
     * OBS_SNMP_ERROR_UNKNOWN_HOST : 994
     * OBS_SNMP_ERROR_INCORRECT_ARGUMENTS : 995
     * OBS_SNMP_ERROR_MIB_OR_OID_NOT_FOUND : 996
     * OBS_SNMP_ERROR_WRONG_INDEX_IN_MIBS_DIR : 997
     * OBS_SNMP_ERROR_MIB_OR_OID_DISABLED : 998
     * OBS_SNMP_ERROR_UNKNOWN : 999
     * OBS_SNMP_ERROR_FAILED_RESPONSE : 1000
     * OBS_SNMP_ERROR_REQUEST_TIMEOUT : 1002
     * OBS_SNMP_ERROR_BULK_REQUEST_TIMEOUT : 1004
     */
    $error_timestamp = time(); // current timestamp
    $error_codes     = $config['snmp']['errorcodes'];
    $error_code      = OBS_SNMP_ERROR_OK; // 0; // By default - OK

    // Early clear global variable $snmp_error_log passed from snmp_log_error()
    $error_codes_log = [];
    if (isset($GLOBALS['snmp_error_log'])) {
        $error_codes_log = $GLOBALS['snmp_error_log'];
        unset($GLOBALS['snmp_error_log']);
    }

    if (snmp_status()) {
        // SNMP ok
        $GLOBALS['snmp_error_code'] = $error_code;
        return $error_code;
    }

    $error_code = OBS_SNMP_ERROR_UNKNOWN; // 999; // Default Unknown error
    if (is_array($oid)) {
        $oid = implode(' ', $oid);
    }
    if ($mib === 'SNMPv2-MIB') {
        // Pre-check for net-snmp errors
        if ($exec_status['exitcode'] === 1) {
            if (str_contains($exec_status['stderr'], '.index are too large')) {
                $error_code = OBS_SNMP_ERROR_WRONG_INDEX_IN_MIBS_DIR; // 997;
                if (!isset($error_codes_log[$error_code])) {
                    // Force eventlog for this error
                    $error_codes_log[$error_code] = TRUE;
                }
            } elseif (str_contains_array($exec_status['stderr'], [ 'Cannot find module', 'Unknown Object Identifier' ])) {
                $error_code = OBS_SNMP_ERROR_MIB_OR_OID_NOT_FOUND; // 996;
            } elseif (str_contains_array($exec_status['stderr'], [ 'Invalid authentication protocol', 'Invalid privacy protocol' ])) {
                // SNMP v3 unsupported algo
                $error_code = OBS_SNMP_ERROR_UNSUPPORTED_ALGO; // 992;
                if (!isset($error_codes_log[$error_code])) {
                    // by default log in poller and discovery
                    $error_codes_log[$error_code] = TRUE;
                }
            } elseif (str_contains_array($exec_status['stderr'], [ 'Unknown user name', 'Error: passphrase', 'Unsupported security level' ])) {
                // SNMP v3 auth error
                $error_code = OBS_SNMP_ERROR_AUTHENTICATION_FAILURE; // 991;
            }
        }
        if ($error_code === OBS_SNMP_ERROR_UNKNOWN) {
            if ($oid === '.1.3.6.1.2.1.1.2.0 .1.3.6.1.2.1.1.3.0' ||
                $oid === 'sysObjectID.0 sysUpTime.0') {
                // this is is_snmpable() test, ignore
                $error_code = OBS_SNMP_ERROR_ISSNMPABLE; // 900;
            } elseif (isset($device['snmpable']) && !empty($device['snmpable']) &&
                      ($oid === $device['snmpable'])) {
                $error_code = OBS_SNMP_ERROR_ISSNMPABLE; // 900; // This is also is_snmpable(), ignore
            } elseif (isset($config['os'][$device['os']]['snmpable']) &&
                      in_array($oid, $config['os'][$device['os']]['snmpable'], TRUE)) {
                $error_code = OBS_SNMP_ERROR_ISSNMPABLE; // 900; // This is also is_snmpable(), ignore
            }
        }
    }

    if ($error_code === OBS_SNMP_ERROR_UNKNOWN &&
        safe_empty(trim($exec_status['stdout'], " \t\n\r\0\x0B\""))) { // Empty or ""
        $error_code = OBS_SNMP_ERROR_EMPTY_RESPONSE;                                // 1;  // Empty output non critical
        if ($exec_status['exitcode'] === 1 || $exec_status['exitcode'] === -1) {
            $error_code = OBS_SNMP_ERROR_REQUEST_TIMEOUT; // 1002;
            if (str_contains_array($exec_status['stderr'], '.index are too large')) {
                $error_code = OBS_SNMP_ERROR_WRONG_INDEX_IN_MIBS_DIR; // 997;
            } elseif (str_contains_array($exec_status['stderr'], [ 'Cannot find module', 'Unknown Object Identifier' ])) {
                $error_code = OBS_SNMP_ERROR_MIB_OR_OID_NOT_FOUND; // 996;
            } elseif (str_contains_array($exec_status['stderr'], 'Empty command passed')) {
                $error_code = OBS_SNMP_ERROR_INCORRECT_ARGUMENTS; // 995;
            } elseif (str_contains_array($exec_status['stderr'], 'Unknown host')) {
                $error_code = OBS_SNMP_ERROR_UNKNOWN_HOST; // 994;
            } elseif (str_contains_array($exec_status['stderr'], [ 'Invalid authentication protocol', 'Invalid privacy protocol' ])) {
                // SNMP v3 unsupported algo
                $error_code = OBS_SNMP_ERROR_UNSUPPORTED_ALGO; // 992;
                if (!isset($error_codes_log[$error_code])) {
                    // by default log in poller and discovery
                    $error_codes_log[$error_code] = TRUE;
                }
            } elseif (str_contains_array($exec_status['stderr'], [ 'Unknown user name', 'Error: passphrase', 'Unsupported security level' ])) {
                // SNMP v3 auth error
                $error_code = OBS_SNMP_ERROR_AUTHENTICATION_FAILURE; // 991;
            } elseif ($GLOBALS['snmp_bulk'] && empty($device['snmp_context']) &&
                      str_starts($exec_status['stderr'], 'Timeout: No Response')) {
                // snmpbulkwalk trouble, probably need disable snmpbulkwalk for device
                // except in snmp context, this is just unsupported context
                $error_code = OBS_SNMP_ERROR_BULK_REQUEST_TIMEOUT; // 1004;
                //if (OBS_PROCESS_NAME !== 'poller' && !isset($error_codes_log[$error_code])) {
                if (!isset($error_codes_log[$error_code])) {
                    // by default log in poller and discovery
                    $error_codes_log[$error_code] = TRUE;
                }
                // if ((OBS_PROCESS_NAME !== 'poller' && !isset($error_codes_log[$error_code])) || $error_codes_log[$error_code]) {
                //   log_event_cache('ERROR! snmpbulkwalk exit by timeout. Try to decrease SNMP Max Repetitions on the device properties page or set 0 for disable snmpbulkwalk.', $device, 'device', $device['device_id'], 7);
                //   $error_codes_log[$error_code] = FALSE;
                // }
            }
        } elseif ($exec_status['exitcode'] === 2) {
            if (str_contains($exec_status['stderr'], 'authorizationError')) {
                // Error in packet
                // Reason: authorizationError (access denied to that object)
                $error_code = OBS_SNMP_ERROR_AUTHORIZATION_ERROR; // 990;
            } else {
                // Reason: (noSuchName) There is no such variable name in this MIB.
                // This is an incorrect snmp version used for MIB/oid (mostly snmp v1)
                $error_code = OBS_SNMP_ERROR_FAILED_RESPONSE; // 1000;
            }
        }
    } elseif ($error_code === OBS_SNMP_ERROR_UNKNOWN) {
        if ($exec_status['exitcode'] === 2 &&
            str_contains_array($exec_status['stderr'], [ 'Response message would have been too large', 'Reason: (genError)' ])) {
            // "Reason: (tooBig) Response message would have been too large."
            // "Reason: (genError) A general failure occured"
            // Too big max-rep definition used,
            // this is not exactly device or net-snmp error, just need to set less max-rep in os definition
            $error_code = OBS_SNMP_ERROR_TOO_BIG_MAX_REPETITION_IN_GETBULK; // 4;
            if (OBS_PROCESS_NAME !== 'poller' && !isset($error_codes_log[$error_code])) {
                // by default log in discovery (only)
                $error_codes_log[$error_code] = TRUE;
            }
            // if ((OBS_PROCESS_NAME !== 'poller' && !isset($error_codes_log[$error_code])) || $error_codes_log[$error_code]) {
            //   log_event_cache('WARNING! snmpbulkwalk did not complete. Try to increase SNMP timeout or decrease SNMP Max Repetitions on the device properties page or set 0 for disable snmpbulkwalk.', $device, 'device', $device['device_id'], 7);
            //   $error_codes_log[$error_code] = FALSE;
            // }
        } // Non empty output, some errors can be ignored
        elseif ($exec_status['stdout'] === 'NULL' ||
                preg_match('/(?:No Such Instance|No Such Object|There is no such variable|No more variables left|Wrong Type)/i', $exec_status['stdout'])) {
            $error_code = OBS_SNMP_ERROR_FAILED_RESPONSE; // 1000;
        } elseif (str_icontains_array($exec_status['stdout'], 'Authentication failure')) {
            $error_code = OBS_SNMP_ERROR_AUTHENTICATION_FAILURE; // 991;
        } elseif ($exec_status['exitcode'] === 2 || str_icontains_array($exec_status['stderr'], 'Timeout')) {
            // non critical
            $error_code = OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED; // 2;
        } elseif ($command === 'snmpgetnext' && $exec_status['exitcode'] === 0 && empty($exec_status['stderr'])) {
            // SNMPGETNEXT returned different Oid
            $error_code = OBS_SNMP_ERROR_GETNEXT_EMPTY_RESPONSE; // 5
        } elseif ($exec_status['exitcode'] === 1) {

            //$error_code = 2; // All other is incomplete request or timeout?
            if (str_contains($exec_status['stderr'], '.index are too large')) {
                $error_code = OBS_SNMP_ERROR_WRONG_INDEX_IN_MIBS_DIR; // 997;
            } elseif (str_contains_array($exec_status['stderr'], [ 'Cannot find module', 'Unknown Object Identifier' ])) {
                $error_code = OBS_SNMP_ERROR_MIB_OR_OID_NOT_FOUND; // 996;
            } elseif (str_contains($exec_status['stderr'], 'OID not increasing:')) {
                $error_code = OBS_SNMP_ERROR_OID_NOT_INCREASING; // 993;
                if (OBS_PROCESS_NAME !== 'poller' && !isset($error_codes_log[$error_code])) {
                    // by default log in discovery (only)
                    $error_codes_log[$error_code] = TRUE;
                }
                // if ((OBS_PROCESS_NAME !== 'poller' && !isset($error_codes_log[$error_code])) || $error_codes_log[$error_code]) {
                //   log_event_cache('WARNING! '.$command.' ended prematurely due to an error: [OID not increasing] on MIB::Oid ['.$mib.'::'.$oid.']. Try to use -Cc option for '.$command.' command.', $device, 'device', $device['device_id'], 7);
                //   $error_codes_log[$error_code] = FALSE;
                // }
            } elseif (preg_match('/ NULL\Z/', $exec_status['stdout'])) {
                // NULL as value at end of walk output
                $error_code = OBS_SNMP_ERROR_FAILED_RESPONSE; // 1000;
            } else {
                // Calculate current snmp timeout
                if (is_numeric($device['snmp_timeout']) && $device['snmp_timeout'] > 0) {
                    $snmp_timeout = $device['snmp_timeout'];
                } elseif (isset($config['snmp']['timeout'])) {
                    $snmp_timeout = $config['snmp']['timeout'];
                } else {
                    $snmp_timeout = 1;
                }
                if (is_numeric($device['snmp_retries']) && $device['snmp_retries'] >= 0) {
                    $snmp_retries = $device['snmp_retries'];
                } elseif (isset($config['snmp']['retries'])) {
                    $snmp_retries = $config['snmp']['retries'];
                } else {
                    $snmp_retries = 5;
                }
                $runtime_timeout = $snmp_timeout * (1 + $snmp_retries);

                if ($exec_status['runtime'] >= $runtime_timeout) {
                    $error_code = OBS_SNMP_ERROR_TOO_LONG_RESPONSE; // 3;
                }
            }
        }
    }

    // Count errors stats
    $GLOBALS['snmp_stats']['errors'][$command]['count']++;
    $GLOBALS['snmp_stats']['errors'][$command]['time'] += $exec_status['runtime'];

    $msg = 'device: ' . $device['device_id'] . ', cmd: ' . $command . ', options: ' . $options;
    $msg .= ', mib: \'' . $mib . '\', oid: \'' . $oid . '\'';
    $msg .= ', cmd exitcode: ' . $exec_status['exitcode'] . ',' . PHP_EOL;
    $msg .= '             snmp error code: #' . $error_code . ', reason: \'' . $error_codes[$error_code]['reason'] . '\', runtime: ' . $exec_status['runtime'];

    if (OBS_DEBUG > 0) {
        if (OBS_DEBUG > 1) {
            // Show full error
            print_debug('SNMP ERROR - ' . $msg);
        } elseif ($error_code != 0 && $error_code != 900) {
            // Show only common error info
            print_message('SNMP ERROR[%r#' . $error_code . ' - ' . $error_codes[$error_code]['reason'] . '%n]', 'color');
        }
    }

    // Log error to eventlog
    if (isset($error_codes_log[$error_code]) && $error_codes_log[$error_code] && !empty($error_codes[$error_code]['msg'])) {
        $log_tags = ['command' => $command, 'mib' => $mib, 'oid' => $oid,
                     'reason'  => $error_codes[$error_code]['reason'],
                     'error'   => $error_codes[$error_code]['name']];
        if ($GLOBALS['snmp_bulk']) {
            $log_tags['command'] = str_replace([ 'snmpwalk', 'snmpget' ], [ 'snmpbulkwalk', 'snmpbulkget' ], $command);
        }
        $log_msg = array_tag_replace($log_tags, $error_codes[$error_code]['msg']);
        log_event_cache($log_msg, $device, 'device', $device['device_id'], 7);
    }

    // Log error into DB, but only in poller modules, all other just return error code
    snmp_log_error_db([
        'device_id'         => $device['device_id'],
        'error_count'       => 1,
        'error_code'        => $error_code,
        'error_reason'      => $error_codes[$error_code]['reason'],
        'snmp_cmd_exitcode' => $exec_status['exitcode'],
        'snmp_cmd'          => $command,
        'snmp_options'      => $options,
        'mib'               => $mib,
        'mib_dir'           => $mibdir,
        'oid'               => $oid,
        'added'             => $error_timestamp,
        'updated'           => $error_timestamp]);

    $GLOBALS['snmp_error_code'] = $error_code; // Set global variable $snmp_error_code

    return $error_code;
}

function snmp_log_error_db($db_insert) {
    if (OBS_PROCESS_NAME !== 'poller' || !$GLOBALS['config']['snmp']['errors']) {
        return;
    }
    
    $error_code = $db_insert['error_code'];
    if ($error_code > 999 || $error_code < 900) {
        // Count critical errors into DB (only for poller)
        $sql = 'SELECT * FROM `snmp_errors` ';
        // Note, snmp_options not in unique db index
        //$sql .= 'WHERE `device_id` = ? AND `error_code` = ? AND `snmp_cmd` = ? AND `snmp_options` = ? AND `mib` = ? AND `oid` = ?;';
        //$error_db = dbFetchRow($sql, array($device['device_id'], $error_code, $command, $options, $mib, $oid));
        $sql .= 'WHERE `device_id` = ? AND `error_code` = ? AND `snmp_cmd` = ? AND `mib` = ? AND `oid` = ?;';

        // Note. Oid column have len limitation for 512 chars, use compression for it
        $error_oid = strlen($db_insert['oid']) > 512 ? str_compress($db_insert['oid']) : $db_insert['oid'];

        $error_db = dbFetchRow($sql, [ $db_insert['device_id'], $error_code, $db_insert['snmp_cmd'], $db_insert['mib'], $error_oid ]);
        if (isset($error_db['error_id'])) {
            $error_db['error_count']++;

            // DEBUG, error rate, if error rate >= 0.95, than error appears in each poll run
            //$poll_count = round(($error_timestamp - $error_db['added']) / $poll_period) + 1;
            //$error_db['error_rate'] = $error_db['error_count'] / $poll_count;
            //$msg .= ', rate: ' . $error_db['error_rate'] . ' err/poll';
            //logfile('snmp.log', $msg);

            // Update count
            $update_array = [
                'error_count' => $error_db['error_count'],
                'updated'     => $db_insert['updated']
            ];
            if ($error_db['mib_dir'] != $db_insert['mib_dir']) {
                $update_array['mib_dir'] = $db_insert['mib_dir'];
            }
            if ($error_db['snmp_options'] != $db_insert['snmp_options']) {
                $update_array['snmp_options'] = $db_insert['snmp_options'];
            }
            dbUpdate($update_array, 'snmp_errors', '`error_id` = ?', [ $error_db['error_id'] ]);
        } else {
            $db_insert['oid'] = $error_oid; // compressed
            dbInsert($db_insert, 'snmp_errors');
        }
    } else {
        // DEBUG
        //logfile('snmp.log', $msg);
    }
}

/**
 * Enable or disable logging for specific snmp error code.
 * Can force logging in some special cases.
 *
 * @param int  $error_code
 * @param bool $log_enable
 */
function snmp_log_error($error_code, $log_enable = TRUE)
{
    if (!isset($GLOBALS['config']['snmp']['errorcodes'][$error_code])) {
        return;
    }

    $GLOBALS['snmp_error_log'][$error_code] = $log_enable;
}

/**
 * Return SNMP status for last snmp get/walk function
 *
 * @return boolean SNMP status
 */
function snmp_status()
{
    return $GLOBALS['snmp_status'];
}

/**
 * Return SNMP error code for last snmp get/walk function
 *
 * @return integer SNMP error code
 */
function snmp_error_code()
{
    return $GLOBALS['snmp_error_code'];
}

/**
 * Return last SNMP command end unixtime. Mostly want for accurate polled time.
 *
 * @return float Last SNMP command end time with microseconds
 */
function snmp_endtime()
{
    return $GLOBALS['snmp_endtime'];
}

/**
 * Return last SNMP command runtime.
 *
 * @return float Last SNMP command runtime with microseconds
 */
function snmp_runtime()
{
    return $GLOBALS['snmp_runtime'];
}

/**
 * Common SNMP get/walk functions
 */

/**
 * Uses snmpget to fetch a single OID and returns a string.
 *
 * @param array   $device
 * @param string  $oid
 * @param string  $options
 * @param string  $mib
 * @param string  $mibdir Optional, correct path should be set in the MIB definition
 * @param integer $flags
 *
 * @return string
 */
function snmp_get($device, $oid, $options = NULL, $mib = NULL, $mibdir = NULL, $flags = OBS_QUOTES_TRIM)
{

    if (str_contains($oid, ' ')) {
        print_debug("WARNING: snmp_get called for multiple OIDs: $oid");
    } elseif (empty($mib) && str_contains($oid, '::')) {
        // Split Oid names passed as full (ie SNMPv2-MIB::sysUpTime.0) into MIB name (SNMPv2-MIB) and Oid (sysUpTime.0)
        [$mib, $oid] = explode('::', $oid);
    }

    $cmd = snmp_command('snmpget', $device, $oid, $options, $mib, $mibdir, $flags);

    $data = external_exec($cmd, $exec_status);

    $data                    = snmp_value_clean($data, $flags);
    $GLOBALS['snmp_status']  = $exec_status['exitcode'] === 0;
    $GLOBALS['snmp_endtime'] = $exec_status['endtime'];
    $GLOBALS['snmp_runtime'] = $exec_status['runtime'];

    $GLOBALS['snmp_stats']['snmpget']['count']++;
    $GLOBALS['snmp_stats']['snmpget']['time'] += $exec_status['runtime'];

    if (isset($data[0])) { // same as strlen($data) > 0
        if (preg_match('/(?:No Such Instance|No Such Object|There is no such variable|No more variables left|Authentication failure)/i', $data) ||
            $data === 'NULL') {
            $data                   = '';
            $GLOBALS['snmp_status'] = FALSE;
        }
    } else {
        $GLOBALS['snmp_status'] = FALSE;
    }
    if (OBS_DEBUG) {
        print_message('SNMP STATUS[' . ($GLOBALS['snmp_status'] ? '%gTRUE' : '%rFALSE') . '%n]', 'color');
    }
    snmp_log_errors('snmpget', $device, $oid, $options, $mib, $mibdir, $exec_status);

    return $data;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// FIXME, why strip quotes is default? this removes all quotes also in index
function snmp_walk($device, $oid, $options = NULL, $mib = NULL, $mibdir = NULL, $flags = OBS_QUOTES_STRIP)
{

    if (empty($mib) && str_contains($oid, '::')) {
        // Split Oid names passed as full (ie SNMPv2-MIB::sysUpTime) into MIB name (SNMPv2-MIB) and Oid (sysUpTime)
        [$mib, $oid] = explode('::', $oid);
    }

    $cmd = snmp_command('snmpwalk', $device, $oid, $options, $mib, $mibdir, $flags);

    $data = trim(external_exec($cmd, $exec_status));

    $GLOBALS['snmp_status']  = $exec_status['exitcode'] === 0;
    $GLOBALS['snmp_endtime'] = $exec_status['endtime'];
    $GLOBALS['snmp_runtime'] = $exec_status['runtime'];

    if (is_string($data) && (preg_match("/No Such (Object|Instance)/i", $data))) {
        $data                   = '';
        $GLOBALS['snmp_status'] = FALSE;
    } else {
        if (preg_match('/No more variables left in this MIB View \(It is past the end of the MIB tree\)$/', $data)
            || preg_match('/End of MIB$/', $data)) {
            # Bit ugly :-(
            $d_ex       = explode("\n", $data);
            $d_ex_count = count($d_ex);
            if ($d_ex_count > 1) {
                // Remove last line
                unset($d_ex[$d_ex_count - 1]);
                $data = implode("\n", $d_ex);
            } else {
                $data                   = '';
                $GLOBALS['snmp_status'] = FALSE;
            }
        }

        // Concatenate multiline values if not set option -Oq
        if (is_flag_set(OBS_SNMP_CONCAT, $flags) && $data && strpos($options, 'q') === FALSE) {
            $old_data = $data;
            $data     = [];
            foreach (explode("\n", $old_data) as $line) {
                $line = trim($line, " \r");
                if (strpos($line, ' =') !== FALSE) {
                    $data[] = $line;
                } else {
                    $key = count($data) - 1;                      // get previous entry key
                    [, $end] = explode(' =', $data[$key], 2);
                    if ($line !== '' && $end !== '')              // add space if previous value not empty
                    {
                        $data[$key] .= ' ';
                        //var_dump($line);
                    }
                    //$data[count($data)-1] .= '\n' . $line; // here NOT newline char, but two chars!
                    $data[$key] .= $line;
                }
            }
            unset($old_data);
            $data = implode("\n", $data);
        }
    }
    $GLOBALS['snmp_stats']['snmpwalk']['count']++;
    $GLOBALS['snmp_stats']['snmpwalk']['time'] += $exec_status['runtime'];

    if (OBS_DEBUG) {
        print_message('SNMP STATUS[' . ($GLOBALS['snmp_status'] ? '%gTRUE' : '%rFALSE') . '%n]', 'color');
    }
    snmp_log_errors('snmpwalk', $device, $oid, $options, $mib, $mibdir, $exec_status);

    return $data;
}

// Cache snmpEngineID
// DOCME needs phpdoc block
// TESTME needs unit testing
function snmp_cache_snmpEngineID($device)
{
    if ($device['snmp_version'] === 'v1' ||               // snmpEngineID allowed only in v2c/v3
        !is_device_mib($device, 'SNMP-FRAMEWORK-MIB')) { // MIB disabled
        return FALSE;
    }

    // Correctly caching when device not added
    $cache_id = isset($device['device_id']) && $device['device_id'] > 0 ? $device['device_id'] : $device['hostname'];

    if (!isset($GLOBALS['cache_snmp'][$cache_id]['snmpEngineID'])) {
        $snmpEngineID = snmp_get_oid($device, 'snmpEngineID.0', 'SNMP-FRAMEWORK-MIB');
        $snmpEngineID = str_replace([' ', '"', "'", "\n", "\r"], '', $snmpEngineID);

        $GLOBALS['cache_snmp'][$cache_id]['snmpEngineID'] = $snmpEngineID;
    }

    return $GLOBALS['cache_snmp'][$cache_id]['snmpEngineID'];
}

// Cache sysObjectID
// DOCME needs phpdoc block
// TESTME needs unit testing
function snmp_cache_sysObjectID($device)
{
    // Correctly caching when device not added
    $cache_id = isset($device['device_id']) && $device['device_id'] > 0 ? $device['device_id'] : $device['hostname'];

    if (!isset($GLOBALS['cache_snmp'][$cache_id]['sysObjectID'])) {
        $sysObjectID = snmp_get_oid($device, 'sysObjectID.0', 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_NUMERIC);
        if (str_contains($sysObjectID, 'Wrong Type')) {
            // Wrong Type (should be OBJECT IDENTIFIER): "1.3.6.1.4.1.25651.1.2"
            $sysObjectID = explode(':', $sysObjectID)[1];
            $sysObjectID = '.' . trim($sysObjectID, ' ."');
        }

        $GLOBALS['cache_snmp'][$cache_id]['sysObjectID'] = $sysObjectID;
    }

    return $GLOBALS['cache_snmp'][$cache_id]['sysObjectID'];
}

function snmpwalk_oid_end($oid)
{
    if (is_string($oid)) {
        $GLOBALS['snmp_oid_end'] = $oid;
        return;
    }
    unset($GLOBALS['snmp_oid_end']);
}

// Return just an array of values without oids.
// DOCME needs phpdoc block
// TESTME needs unit testing
function snmpwalk_values($device, $oid, $array, $mib = NULL, $mibdir = NULL)
{
    $options = snmp_gen_options('snmpwalk'); // -OQUs
    $data    = snmp_walk($device, $oid, $options, $mib, $mibdir);
    foreach (explode("\n", $data) as $line) {
        $entry = snmp_parse_line($line);

        if (isset($entry['oid_name'][0]) && $entry['index_count'] > 0 && is_valid_snmp_value($entry['value'])) {
            $array[] = $entry['value'];
        }
    }

    return $array;
}

/**
 * Uses snmpget to fetch single OID and return string value.
 * Differences from snmp_get:
 *  - not required raw $options, default is -OQv
 *
 * snmp_get() in-code (r8636) options:
 *    -Oqv    : 252
 *    -OQv    : 149
 *    -OQUsv  : 37
 *    -OUnqv  : 21
 *    -Onqv   : 19
 *    -Oqsv   : 17
 *    -OQUnv  : 14
 *    -OUqv   : 7
 *    -Onqsv  : 3
 *    -OQUs   : 2
 *    -OUqsv  : 1
 *    -OQnv   : 1
 *    -OQUnsv : 1
 *
 * snmp_get() cleaned options:
 *   'U', 's' has no effect with 'v', 'Q' better than 'q' (no more Wrong Type):
 *    -OQv    : 463
 *    -OQnv   : 59
 *    -OQUs   : 2
 *
 *    snmp_get() each option:
 *    v       : 522
 *    q       : 320
 *    Q       : 204
 *    U       : 83
 *    s       : 61
 *    n       : 59
 *
 * @param array   $device
 * @param string  $oid
 * @param string  $mib
 * @param string  $mibdir Optional, correct path should be set in the MIB definition
 * @param integer $flags
 *
 * @return string
 */
function snmp_get_oid($device, $oid, $mib = NULL, $mibdir = NULL, $flags = OBS_QUOTES_TRIM)
{

    $options = snmp_gen_options('snmpget', $flags);

    return snmp_get($device, $oid, $options, $mib, $mibdir, $flags);
}

/**
 * Uses snmpgetnext to fetch single OID and return string value.
 *
 * @param array   $device
 * @param string  $oid
 * @param string  $mib
 * @param string  $mibdir Optional, correct path should be set in the MIB definition
 * @param integer $flags
 *
 * @return string
 */
function snmp_getnext_oid($device, $oid, $mib = NULL, $mibdir = NULL, $flags = OBS_QUOTES_TRIM)
{

    $options = snmp_gen_options('snmpwalk', $flags); // yes, walk 'QUs'

    if (str_contains($oid, ' ')) {
        print_debug("ERROR: snmp_getnext called for multiple OIDs: $oid");
        return NULL;
    } elseif (empty($mib) && str_contains($oid, '::')) {
        // Split Oid names passed as full (ie SNMPv2-MIB::sysUpTime.0) into MIB name (SNMPv2-MIB) and Oid (sysUpTime.0)
        [$mib, $oid] = explode('::', $oid);
    }

    $cmd = snmp_command('snmpgetnext', $device, $oid, $options, $mib, $mibdir, $flags);

    $data = external_exec($cmd, $exec_status);

    $entry                   = snmp_parse_line($data, $flags);
    $GLOBALS['snmp_status']  = $exec_status['exitcode'] === 0;
    $GLOBALS['snmp_endtime'] = $exec_status['endtime'];
    $GLOBALS['snmp_runtime'] = $exec_status['runtime'];

    // For counts use just snmpget
    $GLOBALS['snmp_stats']['snmpget']['count']++;
    $GLOBALS['snmp_stats']['snmpget']['time'] += $exec_status['runtime'];

    if (!is_valid_snmp_value($entry['value'])) {
        $entry['value']         = '';
        $GLOBALS['snmp_status'] = FALSE;
    } elseif (!is_flag_set(OBS_SNMP_NUMERIC, $flags) && $entry['oid_name'] !== $oid) {
        // Validate requested Oid vs Returned (while getnext can return any other next Oid)
        print_debug("SNMPGETNEXT returned different Oid ({$entry['oid_name']}) instead requested ($mib::$oid).");
        $entry['value']         = '';
        $GLOBALS['snmp_status'] = FALSE;
    }

    if (OBS_DEBUG) {
        print_message('SNMP STATUS[' . ($GLOBALS['snmp_status'] ? '%gTRUE' : '%rFALSE') . '%n]', 'color');
    }
    snmp_log_errors('snmpgetnext', $device, $oid, $options, $mib, $mibdir, $exec_status);

    return $entry['value'];
}

/**
 * Uses snmpget to fetch multiple OIDs and returns a parsed array.
 * Differences from snmp_get_multi:
 *  - return same array as in snmpwalk_cache_oid()
 *  - array merges with passed array as in snmpwalk_cache_oid()
 *
 * @param array        $device
 * @param array|string $oids
 * @param array        $array
 * @param string       $mib
 * @param string       $mibdir Optional, correct path should be set in the MIB definition
 * @param integer      $flags
 *
 * @return array
 */
// TESTME needs unit testing
function snmp_get_multi_oid($device, $oids, $array = [], $mib = NULL, $mibdir = NULL, $flags = OBS_QUOTES_TRIM)
{
    global $config, $cache;

    $numeric_oids = is_flag_set(OBS_SNMP_NUMERIC, $flags); // Numeric oids, do not parse oid part
    $options      = snmp_gen_options('snmpwalk', $flags);  // yes, walk 'QUs'

    // Oids passed as string and contain multiple Oids?
    $oids_multiple = is_string($oids) && strpos($oids, ' ') !== FALSE;

    // Detect if snmp max-get param defined for os/model
    get_model_array($device);                              // Pre-cache model options (if required)

    // Split Oids list by $max_get count
    if (isset($cache['devices']['model'][$device['device_id']]['snmp']['max-get']) &&
        $cache['devices']['model'][$device['device_id']]['snmp']['max-get'] >= 1) {
        // Device model specific
        $max_get = (int)$cache['devices']['model'][$device['device_id']]['snmp']['max-get'];

        // Convert Oids passed as string to array, for chunk it by defined max-get
        if ($oids_multiple) {
            $oids = preg_split('/\s+/', $oids);
        }
    } elseif (isset($config['os'][$device['os']]['snmp']['max-get']) &&
              $config['os'][$device['os']]['snmp']['max-get'] >= 1) {
        // OS specific
        $max_get = (int)$config['os'][$device['os']]['snmp']['max-get'];

        // Convert Oids passed as string to array, for chunk it by defined max-get
        if ($oids_multiple) {
            $oids = preg_split('/\s+/', $oids);
        }
    } else {
        // Default
        $max_get = $config['os_group']['default']['snmp']['max-get'];
        //$max_get = 16;

        // NOTE. By default, do not convert Oids passed as string to array!
        // See notes below
    }

    if (is_array($oids)) {

        if (OBS_DEBUG && count($oids) > $max_get) {
            print_warning("Passed to snmp_get_multi_oid() Oids count (" . count($oids) . ") more than max-get ($max_get). Command snmpget splitted to multiple chunks.");
        }

        $data                   = '';
        $oid_chunks             = array_chunk($oids, $max_get);
        $GLOBALS['snmp_status'] = FALSE;
        $runtime = 0;
        foreach ($oid_chunks as $oid_chunk) {
            $oid_text  = implode(' ', $oid_chunk);
            $cmd       = snmp_command('snmpget', $device, $oid_text, $options, $mib, $mibdir, $flags);
            $this_data = trim(external_exec($cmd, $exec_status));

            $GLOBALS['snmp_status'] = ($exec_status['exitcode'] === 0 || $GLOBALS['snmp_status']);
            snmp_log_errors('snmpget', $device, $oid_text, $options, $mib, $mibdir, $exec_status);
            $data .= $this_data . "\n";

            $GLOBALS['snmp_stats']['snmpget']['count']++;
            $GLOBALS['snmp_stats']['snmpget']['time'] += $exec_status['runtime'];
            $runtime += $exec_status['runtime'];
        }
    } else {
        // if Oids passed as string, do not split it by chunks,
        // ie ports use more than 16 Oids in list, split decrease total polling time
        //$oids = explode(' ', trim($oids)); // Convert to array

        $cmd  = snmp_command('snmpget', $device, $oids, $options, $mib, $mibdir, $flags);
        $data = trim(external_exec($cmd, $exec_status));

        $GLOBALS['snmp_status'] = $exec_status['exitcode'] === 0;
        snmp_log_errors('snmpget', $device, $oids, $options, $mib, $mibdir, $exec_status);
        $GLOBALS['snmp_stats']['snmpget']['count']++;
        $GLOBALS['snmp_stats']['snmpget']['time'] += $exec_status['runtime'];
        $runtime = $exec_status['runtime'];
    }
    $GLOBALS['snmp_endtime'] = $exec_status['endtime'];
    $GLOBALS['snmp_runtime'] = $runtime;

    foreach (explode("\n", $data) as $line) {
        $entry = snmp_parse_line($line, $flags);

        // For numeric oids do not split oid and index part
        if ($numeric_oids && $entry['index_count'] > 0 && is_valid_snmp_value($entry['value'])) {
            $array[$entry['index']] = $entry['value'];
            continue;
        }

        //list($oid, $index) = explode('.', $oid, 2);
        if (isset($entry['oid_name'][0]) && $entry['index_count'] > 0 && is_valid_snmp_value($entry['value'])) {
            $array[$entry['index']][$entry['oid_name']] = $entry['value'];
        }
    }

    if (empty($array)) {
        $GLOBALS['snmp_status'] = FALSE;
        snmp_log_errors('snmpget', $device, $oids, $options, $mib, $mibdir, $exec_status);
    }

    if (OBS_DEBUG) {
        print_message('SNMP STATUS[' . ($GLOBALS['snmp_status'] ? '%gTRUE' : '%rFALSE') . '%n]', 'color');
    }

    return $array;
}

/**
 * Uses snmpwalk to fetch a single OID and returns a array.
 *
 * @param array   $device
 * @param string  $oid
 * @param array   $array
 * @param string  $mib
 * @param string  $mibdir Optional, correct path should be set in the MIB definition
 * @param integer $flags
 *
 * @return array
 */
function snmpwalk_cache_oid($device, $oid, $array, $mib = NULL, $mibdir = NULL, $flags = OBS_SNMP_ALL)
{
    $numeric_oids = is_flag_set(OBS_SNMP_NUMERIC, $flags); // Numeric oids, do not parse oid part
    $options      = snmp_gen_options('snmpwalk', $flags);

    $data = snmp_walk($device, $oid, $options, $mib, $mibdir, $flags);
    foreach (explode("\n", $data) as $line) {
        $entry = snmp_parse_line($line, $flags);

        // For numeric oids do not split oid and index part
        if ($numeric_oids && $entry['index_count'] > 0 && is_valid_snmp_value($entry['value'])) {
            $array[$entry['index']] = $entry['value'];
            continue;
        }

        if (isset($entry['oid_name'][0]) && is_valid_snmp_value($entry['value'])) {
            if ($entry['index_count'] > 0) {
                $array[$entry['index']][$entry['oid_name']] = $entry['value'];
            } elseif (is_flag_set(OBS_SNMP_NOINDEX, $flags)) {
                // Allow store non indexed Oid with null string index, Ie:
                // DeltaUPS-MIB::dupsIdentManufacturer = STRING: "Socomec"
                // -> [ '' => [ 'dupsIdentManufacturer' => 'Socomec' ] ]
                $array[''][$entry['oid_name']] = $entry['value'];
            }
        }
    }

    return $array;

}

/**
 * Walk oids & tables with numeric oids. Here cut (numeric) mib part from index (used snmptranslate).
 *
 * @param      $device
 * @param      $oid
 * @param      $array
 * @param null $mib
 * @param null $mibdir
 * @param int  $flags
 *
 * @return mixed
 */
// TESTME needs unit testing
function snmpwalk_oid_num($device, $oid, $array, $mib = NULL, $mibdir = NULL, $flags = OBS_SNMP_ALL_NUMERIC)
{
    $options = snmp_gen_options('snmpwalk', $flags | OBS_SNMP_NUMERIC); // This function always use OBS_SNMP_NUMERIC

    $oid_num = snmp_translate($oid, $mib, $mibdir);
    //$data = snmp_walk($device, $oid, $options, $mib, $mibdir, $flags);
    $data = snmp_walk($device, $oid_num, $options, $mib, $mibdir, $flags);

    $pattern = '/^' . str_replace('.', '\.', $oid_num) . '\./';

    foreach (explode("\n", $data) as $entry) {
        [$oid_num, $value] = explode('=', $entry, 2);
        $oid_num = trim($oid_num);
        $value   = snmp_value_clean($value, $flags);
        $index   = preg_replace($pattern, '', $oid_num);

        if (isset($oid) && isset($index[0]) && is_valid_snmp_value($value)) {
            $array[$index][$oid] = $value;
        }
    }

    return $array;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// RENAMEME snmpwalk_bare_oid()
function snmpwalk_cache_bare_oid($device, $oid, $array, $mib = NULL, $mibdir = NULL, $flags = OBS_SNMP_ALL)
{
    // Always use snmpwalk_cache_oid() for numeric
    if (is_flag_set(OBS_SNMP_NUMERIC, $flags)) {
        return snmpwalk_cache_oid($device, $oid, $array, $mib, $mibdir, $flags);
    }

    $options = snmp_gen_options('snmpwalk', $flags);

    $data = snmp_walk($device, $oid, $options, $mib, $mibdir, $flags);
    foreach (explode("\n", $data) as $line) {
        $entry = snmp_parse_line($line, $flags);

        if (isset($entry['oid']) && is_valid_snmp_value($entry['value'])) {
            $array[$entry['oid']] = $entry['value'];
        }
    }

    return $array;
}


// DOCME needs phpdoc block
// TESTME needs unit testing
// RENAMEME snmpwalk_double_oid()
function snmpwalk_cache_double_oid($device, $oid, $array, $mib = NULL, $mibdir = NULL, $flags = OBS_SNMP_ALL)
{
    // Always use snmpwalk_cache_oid() for numeric
    if (is_flag_set(OBS_SNMP_NUMERIC, $flags)) {
        return snmpwalk_cache_oid($device, $oid, $array, $mib, $mibdir, $flags);
    }

    $options = snmp_gen_options('snmpwalk', $flags);

    $index_count = 2;
    $data        = snmp_walk($device, $oid, $options, $mib, $mibdir, $flags);
    foreach (explode("\n", $data) as $line) {
        $entry = snmp_parse_line($line, $flags);

        // Not know why, but here removed index part more than 2, here old code:
        // list($r_oid, $first, $second) = explode('.', $r_oid);
        if (isset($entry['oid_name'][0]) && $entry['index_count'] >= $index_count && is_valid_snmp_value($entry['value'])) {
            $index                             = implode('.', array_slice($entry['index_parts'], 0, $index_count));
            $array[$index][$entry['oid_name']] = $entry['value'];
        }
    }

    return $array;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// RENAMEME snmpwalk_triple_oid()
function snmpwalk_cache_triple_oid($device, $oid, $array, $mib = NULL, $mibdir = NULL, $flags = OBS_SNMP_ALL)
{
    // Always use snmpwalk_cache_oid() for numeric
    if (is_flag_set(OBS_SNMP_NUMERIC, $flags)) {
        return snmpwalk_cache_oid($device, $oid, $array, $mib, $mibdir, $flags);
    }

    $options = snmp_gen_options('snmpwalk', $flags);

    $index_count = 3; // Not know why, but here removed index part more than 3
    $data        = snmp_walk($device, $oid, $options, $mib, $mibdir, $flags);
    foreach (explode("\n", $data) as $line) {
        $entry = snmp_parse_line($line, $flags);

        // Not know why, but here removed index part more than 3, here old code:
        // list($r_oid, $first, $second, $tried) = explode('.', $r_oid);
        if (isset($entry['oid_name'][0]) && $entry['index_count'] >= $index_count && is_valid_snmp_value($entry['value'])) {
            $index                             = implode('.', array_slice($entry['index_parts'], 0, $index_count));
            $array[$index][$entry['oid_name']] = $entry['value'];
        }
    }

    return $array;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function snmpwalk_cache_twopart_oid($device, $oid, $array, $mib = NULL, $mibdir = NULL, $flags = OBS_SNMP_ALL)
{
    // Always use snmpwalk_cache_oid() for numeric
    if (is_flag_set(OBS_SNMP_NUMERIC, $flags)) {
        return snmpwalk_cache_oid($device, $oid, $array, $mib, $mibdir, $flags);
    }

    $options = snmp_gen_options('snmpwalk', $flags);

    $index_count = 2;
    $data        = snmp_walk($device, $oid, $options, $mib, $mibdir, $flags);
    foreach (explode("\n", $data) as $line) {
        $entry = snmp_parse_line($line, $flags);

        if (isset($entry['oid_name'][0]) && $entry['index_count'] >= $index_count && is_valid_snmp_value($entry['value'])) {
            $first  = array_shift($entry['index_parts']);
            $second = implode('.', $entry['index_parts']);

            $array[$first][$second][$entry['oid_name']] = $entry['value'];
        }
    }

    return $array;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function snmpwalk_cache_threepart_oid($device, $oid, $array, $mib = NULL, $mibdir = NULL, $flags = OBS_SNMP_ALL)
{
    // Always use snmpwalk_cache_oid() for numeric
    if (is_flag_set(OBS_SNMP_NUMERIC, $flags)) {
        return snmpwalk_cache_oid($device, $oid, $array, $mib, $mibdir, $flags);
    }

    $options = snmp_gen_options('snmpwalk', $flags);

    $index_count = 3;
    $data        = snmp_walk($device, $oid, $options, $mib, $mibdir, $flags);

    foreach (explode("\n", $data) as $line) {
        $entry = snmp_parse_line($line, $flags);
        if (isset($entry['oid_name'][0]) && $entry['index_count'] >= $index_count && is_valid_snmp_value($entry['value'])) {
            $first                                              = array_shift($entry['index_parts']);
            $second                                             = array_shift($entry['index_parts']);
            $third                                              = implode('.', $entry['index_parts']);
            $array[$first][$second][$third][$entry['oid_name']] = $entry['value'];
        }
    }

    return $array;
}

/**
 * SNMP walk and parse tables with any (not limited) count of index parts into multilevel array.
 * Array levels same as count index parts. Ie: someOid.1.2.3.4 -> 4 index parts, and result array also will have 4 levels
 *
 * @param array       $device Device array
 * @param string      $oid    Table OID name
 * @param array       $array  Array from previous snmpwalk for merge (or empty)
 * @param string|null $mib    MIB name
 * @param mixed       $mibdir Array or string with MIB dirs list, by default used dir from MIB definitions
 * @param integer     $flags  SNMP walk/parse flags
 *
 * @return array          Parsed array with content from requested Table
 */
function snmpwalk_multipart_oid($device, $oid, $array, $mib = NULL, $mibdir = NULL, $flags = OBS_QUOTES_TRIM)
{
    // Always use snmpwalk_cache_oid() for numeric
    if (is_flag_set(OBS_SNMP_NUMERIC, $flags)) {
        return snmpwalk_cache_oid($device, $oid, $array, $mib, $mibdir, $flags);
    }

    $options = snmp_gen_options('snmpwalk', $flags);

    $index_count = 1;
    $data        = snmp_walk($device, $oid, $options, $mib, $mibdir, $flags);
    foreach (explode("\n", $data) as $line) {
        $entry = snmp_parse_line($line, $flags);
        //print_vars($entry);

        if (isset($entry['oid_name'][0]) && is_valid_snmp_value($entry['value'])) {
            if ($entry['index_count'] >= $index_count) {
                $entry_array = [$entry['oid_name'] => $entry['value']];
                for ($i = $entry['index_count'] - 1; $i >= 0; $i--) {
                    $entry_array = [$entry['index_parts'][$i] => $entry_array];
                }
                $array = array_replace_recursive((array)$array, $entry_array);
            } elseif (is_flag_set(OBS_SNMP_NOINDEX, $flags)) {
                // Allow store non indexed Oid with null string index, Ie:
                // DeltaUPS-MIB::dupsIdentManufacturer = STRING: "Socomec"
                // -> [ '' => [ 'dupsIdentManufacturer' => 'Socomec' ] ]
                $array[''][$entry['oid_name']] = $entry['value'];
            }
        }
    }

    return $array;
}

/**
 * Validate if snmp in Virtual Routing exist on device
 *
 * @param array  $device  Device array
 * @param string $virtual Name of virtual SNMP table
 * @param string $oid     Oid for validate (default dot1dBasePortIfIndex)
 * @param string $mib     MIB for validate (default BRIDGE-MIB)
 *
 * @return bool|null TRUE if valid response with context,
 *                   FALSE when exit with timeout (context unsupported),
 *                   NULL when context not exist or not permitted
 */
function snmp_virtual_exist($device, $virtual, $oid = 'dot1dBasePortIfIndex', $mib = 'BRIDGE-MIB')
{
    global $config;

    // Detect contexts not permitted by os
    if (!isset($config['os'][$device['os']]['snmp']['virtual']) ||
        !$config['os'][$device['os']]['snmp']['virtual']) {
        print_debug("SNMP virtual routing engine not permitted by os definition.");
        return NULL;
    }

    // Ignored virtual contexts
    foreach ($config['snmp']['virtual_ignore'] as $pattern) {
        if (preg_match($pattern, $virtual)) {
            print_debug("SNMP virtual context '$virtual' ignored by config '$pattern'.");
            return NULL;
        }
    }

    // Change defaults when BRIDGE-MIB not permitted
    if ($mib === 'BRIDGE-MIB' && !is_device_mib($device, $mib)) {
        if (isset($GLOBALS['config']['os'][$device['os']]['snmp']['virtual_oid'])) {
            // See Arista EOS definition
            [ $mib, $oid ] = explode('::', $GLOBALS['config']['os'][$device['os']]['snmp']['virtual_oid'], 2);
        } else {
            $mib = 'SNMPv2-MIB';
            $oid = 'sysDescr.0';
        }
    }

    $device = snmp_virtual_device($device, $virtual);

    // Set retries to 0 for speedup walking
    $device['snmp_retries'] = 1;
    //$context_data = snmpwalk_cache_oid($device, "dot1dBasePortIfIndex", array(), "BRIDGE-MIB");
    if (str_contains($oid, '.')) {
        $context_data = snmp_get_oid($device, $oid, $mib);
    } else {
        // snmpbulkwalk -v2c -cpublic@4093 -M ../rfc:../net-snmp:. 177.99.234.206 BRIDGE-MIB::dot1dBasePortIfIndex
        // BRIDGE-MIB::dot1dBasePortIfIndex = No Such Instance currently exists at this OID
        // vs
        // snmpgetnext -v2c -cpublic@4093 -M ../rfc:../net-snmp:. 177.99.234.206 BRIDGE-MIB::dot1dBasePortIfIndex
        // BRIDGE-MIB::dot1dStpProtocolSpecification.0 = INTEGER: unknown(1)
        $context_data = snmp_getnext_oid($device, $oid, $mib);
        if (snmp_error_code() === 5) {
            // Getnext returned non empty output, but different Oid, return NULL instead FALSE
            print_debug("SNMP in virtual routing '$virtual' empty on device for $mib::$oid.");

            return NULL;
        }
        // if (snmp_error_code() === 1 && str_starts_with($virtual, 'vlan-')) {
        //     // empty response for vlan context
        //     print_debug("SNMP in virtual routing '$virtual' empty on device for $mib::$oid.");
        //     return NULL;
        // }
    }
    print_debug_vars($context_data);

    // Detection shit snmpv3 authorization errors for contexts
    if (!snmp_status() && safe_empty($context_data)) {
        ///FIXME. Not sure about this error message
        if ($device['snmp_version'] === 'v3' && $device['os_group'] === 'cisco' && str_starts_with($virtual, 'vlan-')) {
            print_error("ERROR: For VLAN context to work on Cisco devices with SNMPv3, it is necessary to add 'match prefix' in snmp-server config.");
        } else {
            print_debug("SNMP {$device['snmp_version']} context '$virtual' not exist on device for $mib::$oid.");
            //print_error("ERROR: Device does not support per-VLAN community.");
        }
        return FALSE;
    }
    if (!safe_empty($context_data)) {
        // Context data validated
        print_debug("SNMP in virtual routing '$virtual' exist on device for $mib::$oid.");
        return TRUE;
    }
    print_debug("SNMP in virtual routing '$virtual' empty on device for $mib::$oid.");

    return NULL;
}

function snmp_virtual_device($device, $virtual)
{
    $virtual_type = $GLOBALS['config']['os'][$device['os']]['snmp']['virtual_type'] ?? 'context';
    switch ($virtual_type) {
        case 'vdom':
            // See FortiGate os definition
            // VDOMs, use different snmp access
            // SNMPv3: username-vdom
            // SNMPv2: community-vdom
            if ($device['snmp_version'] === 'v3') {
                $device['snmp_authname'] .= '-' . $virtual;
            } else {
                $device['snmp_community'] .= '-' . $virtual;
            }
            break;
        case 'context':
        default:
            // Add vlan context for snmp auth
            $device['snmp_context'] = $virtual;
    }

    /* Set retries to 0 for speedup walking
    if (!$device['snmp_retries']) {
      // force less retries on vrf requests.. if not set in db
      $device['snmp_retries'] = 1;
    }
    */

    return $device;
}

/**
 * Initialize (start) snmpsimd daemon, for tests or other purposes.
 *   Stop daemon not required, because here registered shutdown_function for kill daemon at end of run script(s)
 *
 * @param string  $snmpsimd_data Data DIR, where *.snmprec placed
 * @param string  $snmpsimd_ip   Local IP which used for daemon (default 127.0.0.1)
 * @param integer $snmpsimd_port Local Port which used for daemon (default 16111)
 */
function snmpsimd_init($snmpsimd_data, $snmpsimd_ip = '127.0.0.1', $snmpsimd_port = 16111)
{
    global $config;

    $ip_found = TRUE;
    if (str_contains($snmpsimd_ip, ':')) {
        // IPv6
        $ifconfig_cmd = "ip addr | grep 'inet6 $snmpsimd_ip/' | awk '{print $2}'"; // new
        if (empty(external_exec($ifconfig_cmd))) {
            $ifconfig_cmd = "ifconfig | grep 'inet6 addr:$snmpsimd_ip' | cut -d: -f2 | awk '{print $1}'"; // old
            if (empty(external_exec($ifconfig_cmd))) {
                $ip_found = FALSE;
            }
        }
        $snmpsimd_end = 'udpv6';
    } else {
        $ifconfig_cmd = "ip addr | grep 'inet $snmpsimd_ip/' | awk '{print $2}'"; // new
        if (empty(external_exec($ifconfig_cmd))) {
            $ifconfig_cmd = "ifconfig | grep 'inet addr:$snmpsimd_ip' | cut -d: -f2 | awk '{print $1}'"; // old
            if (empty(external_exec($ifconfig_cmd))) {
                $ip_found = FALSE;
            }
        }
        $snmpsimd_end = 'udpv4';
    }

    if ($ip_found) {
        //$snmpsimd_port = 16111;

        // Detect snmpsimd command path
        $snmpsimd_path = external_exec('which snmpsim-command-responder');
        if (empty($snmpsimd_path)) {
            foreach (['/usr/local/bin/', '/usr/bin/', '/usr/sbin/'] as $path) {
                if (is_executable($path . 'snmpsim-command-responder')) {
                    $snmpsimd_path = $path . 'snmpsim-command-responder';
                    break;
                }
                if (is_executable($path . 'snmpsimd.py')) {
                    $snmpsimd_path = $path . 'snmpsimd.py';
                    break;
                }
                if (is_executable($path . 'snmpsimd')) {
                    $snmpsimd_path = $path . 'snmpsimd';
                    break;
                }
            }
        }
        //var_dump($snmpsimd_path);

        if (empty($snmpsimd_path)) {
            print_warning("snmpsimd not found, please install it first.");
        } else {
            //$snmpsimd_data = dirname(__FILE__) . '/data/os';

            $tmp_path = empty($config['temp_dir']) ? '/tmp' : $config['temp_dir']; // GLOBALS empty in php units

            $snmpsimd_pid = $tmp_path . '/observium_snmpsimd.pid';
            $snmpsimd_log = $tmp_path . '/observium_snmpsimd.log';

            if (is_file($snmpsimd_pid)) {
                // Kill stale snmpsimd process
                $pid  = file_get_contents($snmpsimd_pid);
                $info = get_pid_info($pid);
                //var_dump($info);
                if (str_contains($info['COMMAND'], 'snmpsimd')) {
                    external_exec("kill -9 $pid");
                }
                unlink($snmpsimd_pid);
            }

            $snmpsimd_cmd = "$snmpsimd_path --daemonize --data-dir=$snmpsimd_data --agent-$snmpsimd_end-endpoint=$snmpsimd_ip:$snmpsimd_port --pid-file=$snmpsimd_pid --logging-method=file:$snmpsimd_log";
            //var_dump($snmpsimd_cmd);

            external_exec($snmpsimd_cmd);
            $pid = file_get_contents($snmpsimd_pid);
            if ($pid) {
                define('OBS_SNMPSIMD', TRUE);
                register_shutdown_function(function ($snmpsimd_pid) {
                    $pid = file_get_contents($snmpsimd_pid);
                    //echo "KILL'em all! PID: $pid\n";
                    external_exec("kill -9 $pid");
                    unlink($snmpsimd_pid);
                }, $snmpsimd_pid);
            }
        }
        //exit;
    } else {
        print_warning("Local IP $snmpsimd_ip unavailable. SNMP simulator not started.");
    }
    if (!defined('OBS_SNMPSIMD')) {
        define('OBS_SNMPSIMD', FALSE);
    }
}

/**
 * Take -OXqs output and parse it into an array containing OID array and the value
 * Hopefully this is the beginning of more intelligent OID parsing!
 * Thanks to David Farrell <DavidPFarrell@gmail.com> for the parser solution.
 * This function is free for use by all with attribution to David.
 *
 * @param $string
 *
 * @return array
 */
// TESTME needs unit testing
function parse_oid2($string)
{
    $result  = [];
    $matches = [];

    // Match OID - If wrapped in double-quotes ('"'), must escape '"', else must escape ' ' (space) or '[' - Other escaping is optional
    $match_count = preg_match('/^(?:((?!")(?:[^\\\\\\[ ]|(?:\\\\.))+)|(?:"((?:[^\\\\\"]|(?:\\\\.))+)"))/', $string, $matches);
    if (NULL !== $match_count && $match_count > 0) {
        // [1] = unquoted, [2] = quoted
        $value    = strlen($matches[1]) > 0 ? $matches[1] : $matches[2];
        $result[] = stripslashes($value);

        // I do this (vs keeping track of offset) to use ^ in regex
        $string = substr($string, strlen($matches[0]));

        // Match indexes (optional) - If wrapped in double-quotes ('"'), must escape '"', else must escape ']' - Other escaping is optional
        while (TRUE) {
            $match_count = preg_match('/^\\[(?:((?!")(?:[^\\\\\\]]|(?:\\\\.))+)|(?:"((?:[^\\\\\"]|(?:\\\\.))+)"))\\]/', $string, $matches);
            if (NULL !== $match_count && $match_count > 0) {
                // [1] = unquoted, [2] = quoted
                $value    = strlen($matches[1]) > 0 ? $matches[1] : $matches[2];
                $result[] = stripslashes($value);

                // I do this (vs keeping track of offset) to use ^ in regex
                $string = substr($string, strlen($matches[0]));
            } else {
                break;
            }
        } // while

        // Match value - Skips leading ' ' characters - If remainder is wrapped in double-quotes ('"'), must escape '"', other escaping is optional
        $match_count = preg_match('/^\\s+(?:((?!")(?:[^\\\\]|(?:\\\\.))+)|(?:"((?:[^\\\\\"]|(?:\\\\.))+)"))$/', $string, $matches);
        if (NULL !== $match_count && $match_count > 0) {
            // [1] = unquoted, [2] = quoted
            $value = strlen($matches[1]) > 0 ? $matches[1] : $matches[2];

            $result[] = stripslashes($value);

            if (strlen($string) != strlen($matches[0])) {
                echo 'Length error!';
                return NULL;
            }

            return $result;
        }
    }

    // All or nothing
    return NULL;
}

/**
 * Return table from array if already walked, else walk it.
 * Currently overwrites arrays passed as $array, array_merge_indexed didn't like non-numeric indexes?
 *
 * @param array       $device
 * @param string      $table
 * @param array       $array
 * @param string|null $mib
 * @param string|null $mibdir
 * @param int         $flags
 *
 * @return array|null
 */
function snmp_cache_table($device, $table, $array, $mib, $mibdir = NULL, $flags = OBS_SNMP_ALL_MULTILINE)
{

    // We seem to have been passed a MIB::oidName format. Split it.
    if (str_contains($table, '::')) {
        [$mib, $table] = explode("::", $table);
    }

    // Correctly caching when device not added
    $cache_id = isset($device['device_id']) && $device['device_id'] > 0 ? $device['device_id'] : $device['hostname'];

    if (isset($GLOBALS['cache_snmp'][$cache_id][$mib][$table]) &&
        is_array($GLOBALS['cache_snmp'][$cache_id][$mib][$table])) {
        print_debug("Get cached Table OID: $mib::$table");
        $array = array_merge_indexed($GLOBALS['cache_snmp'][$cache_id][$mib][$table], $array);
        //$array = $GLOBALS['cache_snmp'][$cache_id][$mib][$table];

        // Set pseudo snmp status and error code
        $GLOBALS['snmp_status']     = TRUE;
        $GLOBALS['snmp_error_code'] = -1;
    } else {
        $walk = snmpwalk_cache_oid($device, $table, [], $mib, $mibdir, $flags);
        if (!isset($GLOBALS['cache_snmp'][$cache_id][$mib][$table]) && $walk) {
            print_debug("Store in cache Table OID: $mib::$table");
            $GLOBALS['cache_snmp'][$cache_id][$mib][$table] = $walk;
            $array                                          = array_merge_indexed($walk, $array);
        }
        //$array = $walk;
    }
    return $array;
}

/**
 * Return oid from cache if already fetched, else fetch it.
 * Currently, overwrites arrays passed as $array, array_merge_indexed didn't like non-numeric indexes?
 *
 * @param array       $device
 * @param string      $oid
 * @param string|null $mib
 * @param string|null $mibdir
 * @param int         $flags
 *
 * @return string|null
 */
// FIXME -- handle multiple OIDs (as individual cache entries)
function snmp_cache_oid($device, $oid, $mib = NULL, $mibdir = NULL, $flags = OBS_QUOTES_TRIM)
{
    $oid = trim($oid);

    // We seem to have been passed a MIB::oidName format. Split it.
    if (str_contains($oid, '::')) {
        [$mib, $oid] = explode("::", $oid);
    } elseif (preg_match(OBS_PATTERN_SNMP_OID_NUM, $oid)) {
        // Detect if oid numeric
        // .1.3.6.1.4.1.47952.1.2.1
        // 1.3.6.1.4.1.47952.1.2.1
        $mib = '__'; // For caching
    }

    // Correctly caching when device not added
    $cache_id = isset($device['device_id']) && $device['device_id'] > 0 ? $device['device_id'] : $device['hostname'];

    if (array_key_exists($oid, (array)$GLOBALS['cache_snmp'][$cache_id][$mib])) {
        if ($mib === '__') {
            print_debug("Get cached OID: $oid");
        } // Numeric
        else {
            print_debug("Get cached OID: $mib::$oid");
        }
        $value = $GLOBALS['cache_snmp'][$cache_id][$mib][$oid];

        // Set pseudo snmp status and error code
        $GLOBALS['snmp_status']     = TRUE;
        $GLOBALS['snmp_error_code'] = -1;
    } else {
        $value = snmp_get_oid($device, $oid, $mib, $mibdir, $flags);

        if ($mib === '__') {
            print_debug("Store in cache OID: $oid");
        } // Numeric
        else {
            print_debug("Store in cache OID: $mib::$oid");
        }

        $GLOBALS['cache_snmp'][$cache_id][$mib][$oid] = $value;
    }

    return $value;
}

// snmpwalk -v2c -c <community> -t 3 -Cc --hexOutputLength=0 -Ih -ObentxU <hostname> . > myagent.snmpwalk
// snmpwalk -v2c -c <community> -t 3 -Cc --hexOutputLength=0 -Ih -ObentxU <hostname> .1.3.6.1.4.1 >> myagent.snmpwalk
function snmp_dump($device, $filename = NULL, $oid_start = NULL) {
    if (empty($filename) || in_array(strtolower($filename), [ 'stdout', 'cmd' ])) {
        $stdout = TRUE;
    } elseif (is_valid_param('path', $filename)) {
        if (is_file($filename) && filesize($filename)) {
            print_error('File "' . $filename . '" already exist.'.PHP_EOL);
            return FALSE;
        }
        if (!is_writable(dirname($filename))) {
            //print_vars(dirname($filename));
            print_error('File "' . $filename . '" is not writable.'.PHP_EOL);
            return FALSE;
        }
        $stdout = FALSE;
    } else {
        print_error('Incorrect the filename "' . $filename . '"' .PHP_EOL);
        return FALSE;
    }
    if (!empty($oid_start) && !preg_match(OBS_PATTERN_SNMP_OID_NUM, $oid_start)) {
        print_error('Incorrect starting Oid ' . $oid_start.PHP_EOL);
        return FALSE;
    }

    if (empty($oid_start)) {
        $oid_start = '.';
    }

    // Generate snmp options
    if (empty($device['snmp_timeout'])) {
        $device['snmp_timeout'] = 3;
        $device['snmp_retries'] = 2;
    }
    //print_vars($device);
    $flags = OBS_SNMP_NOINCREASE;
    $options = snmp_gen_options('snmpdump', $flags);
    $mib_dir = FALSE;

    $cmd = snmp_command('snmpwalk', $device, $oid_start, $options, NULL, $mib_dir, $flags);
    if (!$stdout) {
        $cmd = str_replace('2>/dev/null', '> '.$filename, $cmd);
    }
    if (!$stdout) {
        print_cli("Started dump '$filename' for device {$device['hostname']} from Oid: $oid_start".PHP_EOL);
        print_cli("WARNING. THIS MAY TAKE A WHILE, PLEASE BE PATIENT WHILE IT RUNNING...".PHP_EOL);
        print_cli("Check snmpdump running by cmd: wc -l $filename".PHP_EOL);
    }
    if ($filename === 'cmd') {
        $filename = $device['hostname'] . '.snmpwalk';
        if (!is_writable(dirname($filename))) {
            $filename = $GLOBALS['config']['temp_dir'] . '/' . $filename;
        }
        echo(str_replace('2>/dev/null', '> '.$filename, $cmd) . PHP_EOL);
        if ($oid_start === '.') {
            $cmd = snmp_command('snmpwalk', $device, '.1.3.6.1.4.1', $options, NULL, $mib_dir, $flags);
            echo(str_replace('2>/dev/null', '>> '.$filename, $cmd) . PHP_EOL);
        }

        return $filename;
    }

    print_debug_vars($cmd);
    // FIXME. Add shell progress: https://stackoverflow.com/questions/12498304/using-bash-to-display-a-progress-indicator-spinner
    //external_exec($cmd, $exec_status);
    system($cmd, $result_code);
    //print_vars($result_code);

    if ($oid_start === '.' && $result_code === 0) {
        // Append ent oids
        $cmd = snmp_command('snmpwalk', $device, '.1.3.6.1.4.1', $options, NULL, $mib_dir, $flags);
        if (!$stdout) {
            $cmd = str_replace('2>/dev/null', '>> '.$filename, $cmd);
        }
        print_debug_vars($cmd);
        if (!$stdout) {
            print_cli("Append ent oids (.1.3.6.1.4.1) for device {$device['hostname']}".PHP_EOL);
        }
        //external_exec($cmd, $exec_status);
        system($cmd, $result_code_ent);
        if (!$stdout && $result_code_ent !== 0) {
            print_warning("Snmpdump for ent oids not exist.");
        }
    }
    if (!$stdout && $result_code !== 0) {
        print_warning("Snmpdump not completed or exit by timeout.");
        return FALSE;
    }

    return $stdout ?: $filename;
}

// EOF
