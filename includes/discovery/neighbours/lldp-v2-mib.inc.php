<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

$lldp_array = snmpwalk_cache_oid($device, 'lldpV2RemChassisIdSubtype', [], "LLDP-V2-MIB");
if (empty($lldp_array)) {
    return;
}
$lldp_array = snmpwalk_cache_oid($device, 'lldpV2RemChassisId', $lldp_array, "LLDP-V2-MIB");
$lldp_array = snmpwalk_cache_oid($device, 'lldpV2RemPortIdSubtype', $lldp_array, "LLDP-V2-MIB");
$lldp_array = snmpwalk_cache_oid($device, 'lldpV2RemPortId', $lldp_array, "LLDP-V2-MIB");
$lldp_array = snmpwalk_cache_oid($device, 'lldpV2RemSysName', $lldp_array, "LLDP-V2-MIB");

// lldpV2RemSysDesc can be multiline
$lldp_array = snmpwalk_cache_oid($device, 'lldpV2RemPortDesc', $lldp_array, "LLDP-V2-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
$lldp_array = snmpwalk_cache_oid($device, 'lldpV2RemSysDesc', $lldp_array, "LLDP-V2-MIB", NULL, OBS_SNMP_ALL_MULTILINE);

/*
if (is_device_mib($device, 'LLDP-EXT-MED-MIB')) {
  // See Cumulus Linux
  // not exist lldpRemSysName, lldpRemSysDesc
  $lldp_array = snmpwalk_cache_oid($device, 'lldpXMedRemSoftwareRev', $lldp_array, "LLDP-EXT-MED-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
  $lldp_array = snmpwalk_cache_oid($device, 'lldpXMedRemMfgName', $lldp_array, "LLDP-EXT-MED-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
  $lldp_array = snmpwalk_cache_oid($device, 'lldpXMedRemModelNam', $lldp_array, "LLDP-EXT-MED-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
}
*/

// lldpV2RemManAddrTable
$lldp_addr = snmpwalk_cache_oid($device, 'lldpV2RemManAddrIfId', [], "LLDP-V2-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
$lldp_addr = snmpwalk_cache_oid($device, 'lldpV2RemManAddrIfSubtype', $lldp_addr, "LLDP-V2-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
print_debug_vars($lldp_addr);

foreach ($lldp_addr as $index => $entry) {

    /*
    if (isset($lldp_array[$index])) {
      $lldp_array[$index] = array_merge($lldp_array[$index], $entry);
      if (isset($entry['lldpRemManAddr'])) {
        $addr = hex2ip($entry['lldpRemManAddr']);
        $lldp_array[$index]['lldpRemMan'][$addr] = $entry; // For multiple entries
      }
      continue;
    }
    */
    $index_array = explode('.', $index, 7);
    // LLDP index
    [ $lldpV2RemTimeMark, $lldpV2RemLocalIfIndex, $lldpV2RemLocalDestMACAddress, $lldpV2RemIndex, // LLDP index
      $lldpV2RemManAddrIndex, $len, $addr ] = $index_array;                                       // Addr index
    $lldp_index                   = "$lldpV2RemTimeMark.$lldpV2RemLocalIfIndex.$lldpV2RemLocalDestMACAddress.$lldpV2RemIndex";
    if (!isset($lldp_array[$lldp_index])) {
        continue;
    }
    //$lldp_array[$lldp_index] = array_merge($lldp_array[$lldp_index], $entry);

    // Already exist Oid, just merge
    // if (isset($entry['lldpRemManAddr']))
    // {
    //   continue;
    // }

    // Convert from index part
    $entry['lldpV2RemManAddrIndex'] = $lldpV2RemManAddrIndex;
    switch ($len) {
        case 4:
            // IPv4, ie: 4.10.129.2.171
            $lldp_array[$lldp_index]['lldpV2RemManAddr'] = $addr;
            break;
        case 16:
            // IPv6, ie: 16.42.2.32.40.255.0.0.0.0.0.0.1.0.0.1.113
            $addr                                        = snmp2ipv6($addr);
            $lldp_array[$lldp_index]['lldpV2RemManAddr'] = $addr;
            break;
        default:
            // Unknown
    }
    $lldp_array[$lldp_index]['lldpV2RemMan'][$addr] = $entry; // For multiple entries
}

print_debug_vars($lldp_array, 1);

//$dot1d_array = snmp_cache_table($device, "dot1dBasePortIfIndex", [], "BRIDGE-MIB");
//$lldp_local_array = snmpwalk_cache_oid($device, "lldpLocalSystemData", array(), "LLDP-MIB");

// not used?
//$lldp_local_array = snmpwalk_cache_oid($device, "lldpV2LocPortEntry", [], "LLDP-V2-MIB");

foreach ($lldp_array as $index => $lldp) {
    [ $lldpV2RemTimeMark, $lldpV2RemLocalIfIndex, $lldpV2RemLocalDestMACAddress, $lldpV2RemIndex ] = explode('.', $index);

    // Detect local device port
    $port = NULL;

    // Prefer by LLDP-MIB
    //if (!$port && isset($lldp_local_array[$lldpV2RemLocalIfIndex])) {
        $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND `ifIndex` = ? AND `ifDescr` NOT LIKE 'Vlan%'", [$device['device_id'], 0, $lldpV2RemLocalIfIndex]);
    //}

    // Detect remote device and port
    $remote_device_id = FALSE;
    $remote_port_id   = NULL;

    // Sometime lldpV2RemPortDesc is not set
    if (!isset($lldp['lldpV2RemPortDesc'])) {
        $lldp['lldpV2RemPortDesc'] = '';
    }

    $remote_mac = $lldp['lldpV2RemChassisIdSubtype'] === 'macAddress' ? $lldp['lldpV2RemChassisId'] : NULL;

    // lldpV2RemPortId can be hex string
    if ($lldp['lldpV2RemPortIdSubtype'] !== 'macAddress') {
        if (str_contains($lldp['lldpV2RemPortId'], ':')) {
            $tmp         = str_replace(':', ' ', $lldp['lldpV2RemPortId']);
            $rem_port_id = snmp_hexstring($tmp);
            if ($tmp != $rem_port_id) {
                $lldp['lldpV2RemPortId'] = $rem_port_id;
                print_debug("lldpV2RemPortId decoded: '$tmp' -> '$rem_port_id'");
            }
            unset($tmp, $rem_port_id);
        }
        // On Extreme platforms, they remove the leading 1: from ports. Put it back if there isn't a :.
        if (preg_match('/^ExtremeXOS.*$/', $lldp['lldpV2RemSysDesc'])) {
            if (!preg_match('/\:/', $lldp['lldpV2RemPortId'])) {
                $lldp['lldpV2RemPortId'] = '1:' . $lldp['lldpV2RemPortId'];
            }
        } else {
            //$lldp['lldpV2RemPortId'] = snmp_hexstring($lldp['lldpV2RemPortId']);
        }
    } elseif (safe_empty($remote_mac)) {
        $remote_mac = $lldp['lldpV2RemPortId'];
    }

    // Clean MAC & IP
    if (safe_count($lldp['lldpV2RemMan']) > 1) {
        // Multiple IP addresses.. detect best?
        foreach (array_keys($lldp['lldpV2RemMan']) as $addr) {
            $addr_version = get_ip_version($addr);
            $addr_type    = get_ip_type($addr);
            if (in_array($addr_type, ['unspecified', 'loopback', 'reserved', 'multicast'])) {
                continue;
            }
            if ($addr_version == 6 && $addr_type === 'link-local') {
                continue;
            }
            if ($addr_type === 'unicast') {
                // Prefer IPv4/IPv6 unicast
                $lldp['lldpV2RemManAddr'] = $addr;
                break;
            }
            if ($addr_version == 4) {
                // Then prefer IPv4
                $lldp['lldpV2RemManAddr'] = $addr;
                break;
            }
            $lldp['lldpV2RemManAddr'] = $addr;
        }
        print_debug("Multiple remote IP addresses detect, selected: $addr");
        print_debug_vars($lldp);
    }
    if (isset($lldp['lldpV2RemManAddr'])) {
        $lldp['lldpV2RemManAddr'] = hex2ip($lldp['lldpV2RemManAddr']);
    }

    // Remote sysname is MAC in some cases
    if (preg_match('/^[a-f\d]{2}([: ][a-f\d]{2}){5}$/', $lldp['lldpV2RemSysName']) && get_ip_version($lldp['lldpV2RemManAddr'])) {
        // Replace by IP address for better discovery
        print_debug("LLDP hostname replaced: " . $lldp['lldpV2RemSysName'] . " -> " . $lldp['lldpV2RemManAddr']);
        $lldp['lldpV2RemSysName'] = $lldp['lldpV2RemManAddr'];
    } elseif (safe_empty($lldp['lldpV2RemSysName']) && !safe_empty($lldp['lldpV2RemSysDesc'])) {
        if ($lldp['lldpV2RemChassisIdSubtype'] === 'macAddress') {
            // use mac address instead empty hostname:
            $lldp['lldpV2RemSysName'] = str_replace([' ', '-'], '', strtolower($lldp['lldpV2RemChassisId']));
        } elseif ($lldp['lldpV2RemChassisIdSubtype'] === 'networkAddress' &&
                  preg_match('/^01 (?<ip>([A-F\d]{2}\s?){4})$/', $lldp['lldpV2RemChassisId'], $matches)) {
            $lldp['lldpV2RemSysName'] = hex2ip($matches['ip']);
        }
    }

    // Try to find a remote device and check if already cached
    $remote_device_id = get_autodiscovery_device_id($device, $lldp['lldpV2RemSysName'], $lldp['lldpV2RemManAddr'], $remote_mac);
    if (is_null($remote_device_id) &&                                          // NULL - never cached in other rounds
        check_autodiscovery($lldp['lldpV2RemSysName'], $lldp['lldpV2RemManAddr'])) { // Check all previous autodiscovery rounds
        // Neighbour never checked, try autodiscovery
        $remote_device_id = autodiscovery_device($lldp['lldpV2RemSysName'], $lldp['lldpV2RemManAddr'], 'LLDP', $lldp['lldpV2RemSysDesc'], $device, $port);
    }

    if ($remote_device_id) {
        $if = $lldp['lldpV2RemPortDesc'];
        $id = trim($lldp['lldpV2RemPortId']);

        // lldpPortIdSubtype   -> lldpPortId
        //  interfaceAlias(1), ->  ifAlias
        //  portComponent(2),  ->  entPhysicalAlias
        //  macAddress(3),     ->  ifPhysAddress
        //  networkAddress(4), ->  IP address
        //  interfaceName(5),  ->  ifName
        //  agentCircuitId(6), ->  agent-local identifier of the circuit (defined in RFC 3046) (FIXME, not know)
        //  local(7)           ->  ifIndex
        switch ($lldp['lldpV2RemPortIdSubtype']) {
            case 'interfaceAlias':
                $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE (`ifAlias` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?", [ $id, $if, $if, $remote_device_id, 0 ]);
                break;

            case 'interfaceName':
                // Try lldpRemPortId
                $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
                $remote_port_id = dbFetchCell($query, [ $id, $id, $id, $remote_device_id, 0 ]);
                if (!$remote_port_id && !safe_empty($if)) {
                    // Try the same by lldpRemPortDesc
                    $remote_port_id = dbFetchCell($query, [ $if, $if, $if, $remote_device_id, 0 ]);
                }
                break;

            case 'macAddress':
                $remote_port_id = get_port_id_by_mac($remote_device_id, $id);
                if (!$remote_port_id && !safe_empty($if)) {
                    // Try the same by lldpRemPortDesc
                    $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
                    $remote_port_id = dbFetchCell($query, [ $if, $if, $if, $remote_device_id, 0 ]);
                }
                break;

            case 'networkAddress':
                $ip_version = get_ip_version($id);
                if ($ip_version) {
                    // Try by IP
                    $peer_where = generate_query_values_and($remote_device_id, 'device_id'); // Additional filter for include self IPs
                    // Fetch all devices with peer IP and filter by UP
                    if ($ids = get_entity_ids_ip_by_network('port', $id, $peer_where)) {
                        $remote_port_id = $ids[0];
                        //$port = get_port_by_id_cache($ids[0]);
                    }
                }
                break;

            case 'local':
                // local not always ifIndex or FIXME (see: http://jira.observium.org/browse/OBSERVIUM-1716)
                if (!ctype_digit($id)) {
                    // Not sure what should be if $id ifName and it just numeric
                    $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
                    $remote_port_id = dbFetchCell($query, [ $id, $id, $id, $remote_device_id, 0 ]);
                    if (!$remote_port_id) {
                        // Try the same by lldpRemPortDesc
                        $remote_port_id = dbFetchCell($query, [ $if, $if, $if, $remote_device_id, 0 ]);
                    }
                }
            case 'ifIndex':
                // These cases are handled by the ifDescr/ifIndex combination fallback below
                break;

            default:
                break;
        }

        if (!$remote_port_id && is_numeric($id)) { // Not found despite our attempts above - fall back to try matching with ifDescr/ifIndex
            $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE (`ifIndex`= ? OR `ifDescr` = ?) AND `device_id` = ? AND `deleted` = ?", [ $id, $if, $remote_device_id, 0 ]);
        }

        if (!$remote_port_id) { // Still not found?
            if ($lldp['lldpV2RemChassisIdSubtype'] === 'macAddress') {
                // Find the port by chassis MAC address, only use this if exactly 1 match is returned, otherwise we'd link wrongly - think switches with 1 global MAC on all ports.
                $remote_port_id = get_port_id_by_mac($remote_device_id, $lldp['lldpV2RemChassisId']);
            } else {
                // Last chance
                $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` IN (?, ?) OR `ifDescr` IN (?, ?) OR `port_label_short` IN (?, ?)) AND `device_id` = ? AND `deleted` = ?';
                $remote_port_id = dbFetchCell($query, [ $id, $if, $id, $if, $id, $if, $remote_device_id, 0 ]);
            }
        }

        // Still not found? Seems as an incorrect remote device :/
        if (!$remote_port_id) {
            print_debug("WARNING. Remote device found in db, but remote port not found. Seems as incorrect remote device association.");
        }
    }

    $valid_host_key = $lldp['lldpV2RemSysName'];
    if (strlen($lldp['lldpV2RemManAddr'])) {
        $valid_host_key .= '-' . $lldp['lldpV2RemManAddr'];
    }
    if ($GLOBALS['valid']['neighbours'][$port['port_id']][$valid_host_key][$lldp['lldpV2RemPortId']]) {
        print_warning("Neighbour already discovered by LLDP-MIB. Skip..");
        continue;
    }

    // FIXME. We can use lldpRemSysCapEnabled as platform, but they use BITS textual conversion:
    // LLDP-MIB::lldpRemSysCapEnabled.0.5.3 = BITS: 20 00 bridge(2)
    // LLDP-MIB::lldpRemSysCapEnabled.0.5.3 = "20 00 "
    $neighbour = [
        'remote_device_id' => $remote_device_id,
        'remote_port_id'   => $remote_port_id,
        'remote_hostname'  => $lldp['lldpV2RemSysName'],
        'remote_port'      => $lldp['lldpV2RemPortId'],
        'remote_platform'  => $lldp['lldpV2RemSysDesc'],
        'remote_version'   => NULL,
        'remote_address'   => $lldp['lldpV2RemManAddr']
    ];
    discover_neighbour($port, 'lldp', $neighbour);
}

// EOF
