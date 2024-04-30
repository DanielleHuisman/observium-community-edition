<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$lldp_array = snmpwalk_cache_oid($device, 'tmnxLldpRemChassisIdSubtype', [], "TIMETRA-LLDP-MIB");
if (empty($lldp_array)) {
    return;
}
$lldp_array = snmpwalk_cache_oid($device, 'tmnxLldpRemChassisId', $lldp_array, "TIMETRA-LLDP-MIB");
$lldp_array = snmpwalk_cache_oid($device, 'tmnxLldpRemPortIdSubtype', $lldp_array, "TIMETRA-LLDP-MIB");
$lldp_array = snmpwalk_cache_oid($device, 'tmnxLldpRemPortId', $lldp_array, "TIMETRA-LLDP-MIB");
$lldp_array = snmpwalk_cache_oid($device, 'tmnxLldpRemPortDesc', $lldp_array, "TIMETRA-LLDP-MIB");
$lldp_array = snmpwalk_cache_oid($device, 'tmnxLldpRemSysName', $lldp_array, "TIMETRA-LLDP-MIB");

// tmnxLldpRemSysDesc can be multiline
// index: tmnxLldpRemTimeMark.ifIndex.tmnxLldpRemLocalDestMACAddress.tmnxLldpRemIndex
$lldp_array = snmpwalk_cache_oid($device, 'tmnxLldpRemSysDesc', $lldp_array, "TIMETRA-LLDP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);

// tmnxLldpRemManAddrTable
// index: tmnxLldpRemTimeMark.ifIndex.tmnxLldpRemLocalDestMACAddress.tmnxLldpRemIndex.tmnxLldpRemManAddrSubtype.tmnxLldpRemManAddr
$lldp_addr = snmpwalk_cache_oid($device, 'tmnxLldpRemManAddrIfId', [], "TIMETRA-LLDP-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
$lldp_addr = snmpwalk_cache_oid($device, 'tmnxLldpRemManAddrIfSubtype', $lldp_addr, "TIMETRA-LLDP-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
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
    $tmnxLldpRemTimeMark            = array_shift($index_array);
    $tmnxLocalifIndex               = array_shift($index_array);
    $tmnxLldpRemLocalDestMACAddress = array_shift($index_array);
    $tmnxLldpRemIndex               = array_shift($index_array);
    $lldp_index                     = "$tmnxLldpRemTimeMark.$tmnxLocalifIndex.$tmnxLldpRemLocalDestMACAddress.$tmnxLldpRemIndex";
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
    $type = array_shift($index_array);
    $len  = array_shift($index_array);
    $addr = array_shift($index_array);
    switch ($type) {
        case 4:
            // IPv4, ie: 4.10.129.2.171
            $lldp_array[$lldp_index]['tmnxLldpRemManAddr'] = $addr;
            break;
        case 6:
            // IPv6, ie: 16.42.2.32.40.255.0.0.0.0.0.0.1.0.0.1.113
            $addr                                          = snmp2ipv6($addr);
            $lldp_array[$lldp_index]['tmnxLldpRemManAddr'] = $addr;
            break;
        default:
            // Unknown
    }
    $lldp_array[$lldp_index]['tmnxLldpRemMan'][$addr] = $entry; // For multiple entries
}

print_debug_vars($lldp_array, 1);

// index: ifIndex.tmnxLldpLocPortDestMACAddress
$lldp_local_array = snmpwalk_cache_oid($device, "tmnxLldpLocPortEntry", [], "TIMETRA-LLDP-MIB");

foreach ($lldp_array as $index => $lldp) {
    [$tmnxLldpRemTimeMark, $tmnxLocalifIndex, $tmnxLldpRemLocalDestMACAddress, $tmnxLldpRemIndex] = explode('.', $index);

    // Detect local device port
    $port           = NULL;
    $tmnxLocalIndex = $tmnxLocalifIndex . '.' . $tmnxLldpRemLocalDestMACAddress;

    // Prefer by LLDP-MIB
    if (!$port && isset($lldp_local_array[$tmnxLocalIndex])) {
        $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND `ifIndex` = ? AND `ifDescr` NOT LIKE 'Vlan%'", [$device['device_id'], 0, $tmnxLocalifIndex]);
    }

    // Detect remote device and port
    $remote_device_id = FALSE;
    $remote_port_id   = NULL;

    // Sometime tmnxLldpRemPortDesc is not set
    if (!isset($lldp['tmnxLldpRemPortDesc'])) {
        $lldp['tmnxLldpRemPortDesc'] = '';
    }

    $remote_mac = $lldp['tmnxLldpRemChassisIdSubtype'] === 'macAddress' ? $lldp['tmnxLldpRemChassisId'] : NULL;

    // tmnxLldpRemPortId can be hex string (not sure that this actual on timos platform)
    if ($lldp['tmnxLldpRemPortIdSubtype'] !== 'macAddress') {
        if (str_contains($lldp['tmnxLldpRemPortId'], ':')) {
            $tmp         = str_replace(':', ' ', $lldp['tmnxLldpRemPortId']);
            $rem_port_id = snmp_hexstring($tmp);
            if ($tmp != $rem_port_id) {
                $lldp['tmnxLldpRemPortId'] = $rem_port_id;
                print_debug("tmnxLldpRemPortId decoded: '$tmp' -> '$rem_port_id'");
            }
            unset($tmp, $rem_port_id);
        }
        // On Extreme platforms, they remove the leading 1: from ports. Put it back if there isn't a :.
        if (preg_match('/^ExtremeXOS.*$/', $lldp['tmnxLldpRemSysDesc'])) {
            if (!preg_match('/\:/', $lldp['tmnxLldpRemPortId'])) {
                $lldp['tmnxLldpRemPortId'] = '1:' . $lldp['tmnxLldpRemPortId'];
            }
        } else {
            //$lldp['tmnxLldpRemPortId'] = snmp_hexstring($lldp['tmnxLldpRemPortId']);
        }
    } elseif (safe_empty($remote_mac)) {
        $remote_mac = $lldp['tmnxLldpRemPortId'];
    }

    // Clean MAC & IP
    if (safe_count($lldp['tmnxLldpRemMan']) > 1) {
        // Multiple IP addresses.. detect best?
        foreach (array_keys($lldp['tmnxLldpRemMan']) as $addr) {
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
                $lldp['tmnxLldpRemManAddr'] = $addr;
                break;
            }
            if ($addr_version == 4) {
                // Than prefer IPv4
                $lldp['tmnxLldpRemManAddr'] = $addr;
                break;
            }
            $lldp['tmnxLldpRemManAddr'] = $addr;
        }
        print_debug("Multiple remote IP addresses detect, selected: $addr");
        print_debug_vars($lldp);
    }
    if (isset($lldp['tmnxLldpRemManAddr'])) {
        $lldp['tmnxLldpRemManAddr'] = hex2ip($lldp['tmnxLldpRemManAddr']);
    }

    // Remote sysname is MAC in some cases
    if (preg_match('/^[a-f\d]{2}([: ][a-f\d]{2}){5}$/', $lldp['tmnxLldpRemSysName']) && get_ip_version($lldp['tmnxLldpRemManAddr'])) {
        // Replace by IP address for better discovery
        print_debug("LLDP hostname replaced: " . $lldp['tmnxLldpRemSysName'] . " -> " . $lldp['tmnxLldpRemManAddr']);
        $lldp['tmnxLldpRemSysName'] = $lldp['tmnxLldpRemManAddr'];
    } elseif (safe_empty($lldp['tmnxLldpRemSysName']) && !safe_empty($lldp['tmnxLldpRemSysDesc'])) {
        if ($lldp['tmnxLldpRemChassisIdSubtype'] === 'macAddress') {
            // use mac address instead empty hostname:
            $lldp['tmnxLldpRemSysName'] = str_replace([' ', '-'], '', strtolower($lldp['tmnxLldpRemChassisId']));
        } elseif ($lldp['tmnxLldpRemChassisIdSubtype'] === 'networkAddress' &&
                  preg_match('/^01 (?<ip>([A-F\d]{2}\s?){4})$/', $lldp['tmnxLldpRemChassisId'], $matches)) {
            $lldp['tmnxLldpRemSysName'] = hex2ip($matches['ip']);
        }
    }

    // Try find remote device and check if already cached
    $remote_device_id = get_autodiscovery_device_id($device, $lldp['tmnxLldpRemSysName'], $lldp['tmnxLldpRemManAddr'], $remote_mac);
    if (is_null($remote_device_id) &&                                          // NULL - never cached in other rounds
        check_autodiscovery($lldp['tmnxLldpRemSysName'], $lldp['tmnxLldpRemManAddr'])) { // Check all previous autodiscovery rounds
        // Neighbour never checked, try autodiscovery
        $remote_device_id = autodiscovery_device($lldp['tmnxLldpRemSysName'], $lldp['tmnxLldpRemManAddr'], 'LLDP', $lldp['tmnxLldpRemSysDesc'], $device, $port);
    }

    if ($remote_device_id) {
        $id = $lldp['tmnxLldpRemPortDesc'];
        $if = trim($lldp['tmnxLldpRemPortId']);

        // lldpPortIdSubtype   -> lldpPortId
        //  interfaceAlias(1), ->  ifAlias
        //  portComponent(2),  ->  entPhysicalAlias
        //  macAddress(3),     ->  ifPhysAddress
        //  networkAddress(4), ->  IP address
        //  interfaceName(5),  ->  ifName
        //  agentCircuitId(6), ->  agent-local identifier of the circuit (defined in RFC 3046) (FIXME, not know)
        //  local(7)           ->  ifIndex
        switch ($lldp['tmnxLldpRemPortIdSubtype']) {
            case 'interfaceAlias':
                $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE (`ifAlias` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?", [$id, $if, $if, $remote_device_id, 0]);
                break;

            case 'interfaceName':
                // Try lldpRemPortId
                $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
                $remote_port_id = dbFetchCell($query, [$id, $id, $id, $remote_device_id, 0]);
                if (!$remote_port_id && !safe_empty($if)) {
                    // Try same by lldpRemPortDesc
                    $remote_port_id = dbFetchCell($query, [$if, $if, $if, $remote_device_id, 0]);
                }
                break;

            case 'macAddress':
                $remote_port_id = get_port_id_by_mac($remote_device_id, $id);
                if (!$remote_port_id && !safe_empty($if)) {
                    // Try same by lldpRemPortDesc
                    $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
                    $remote_port_id = dbFetchCell($query, [$if, $if, $if, $remote_device_id, 0]);
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
                    $remote_port_id = dbFetchCell($query, [$id, $id, $id, $remote_device_id, 0]);
                    if (!$remote_port_id) {
                        // Try same by lldpRemPortDesc
                        $remote_port_id = dbFetchCell($query, [$if, $if, $if, $remote_device_id, 0]);
                    }
                }
            case 'ifIndex':
                // These cases are handled by the ifDescr/ifIndex combination fallback below
                break;

            default:
                break;
        }

        if (!$remote_port_id && is_numeric($id)) { // Not found despite our attempts above - fall back to try matching with ifDescr/ifIndex
            $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE (`ifIndex`= ? OR `ifDescr` = ?) AND `device_id` = ? AND `deleted` = ?", [$id, $if, $remote_device_id, 0]);
        }

        if (!$remote_port_id) { // Still not found?
            if ($lldp['tmnxLldpRemChassisIdSubtype'] === 'macAddress') {
                // Find the port by chassis MAC address, only use this if exactly 1 match is returned, otherwise we'd link wrongly - think switches with 1 global MAC on all ports.
                $remote_port_id = get_port_id_by_mac($remote_device_id, $lldp['tmnxLldpRemChassisId']);
            } else {
                // Last chance
                $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` IN (?, ?) OR `ifDescr` IN (?, ?) OR `port_label_short` IN (?, ?)) AND `device_id` = ? AND `deleted` = ?';
                $remote_port_id = dbFetchCell($query, [$id, $if, $id, $if, $id, $if, $remote_device_id, 0]);
            }
        }

        // Still not found? Seems as incorrect remote device :/
        if (!$remote_port_id) {
            print_debug("WARNING. Remote device found in db, but remote port not found. Seems as incorrect remote device association.");
        }
    }

    $valid_host_key = $lldp['tmnxLldpRemSysName'];
    if (strlen($lldp['tmnxLldpRemManAddr'])) {
        $valid_host_key .= '-' . $lldp['tmnxLldpRemManAddr'];
    }
    if ($GLOBALS['valid']['neighbours'][$port['port_id']][$valid_host_key][$lldp['tmnxLldpRemPortId']]) {
        print_warning("Neighbour already discovered by LLDP-MIB. Skip..");
        continue;
    }

    $neighbour = [
        'remote_device_id' => $remote_device_id,
        'remote_port_id'   => $remote_port_id,
        'remote_hostname'  => $lldp['tmnxLldpRemSysName'],
        'remote_port'      => $lldp['tmnxLldpRemPortId'],
        'remote_platform'  => $lldp['tmnxLldpRemSysDesc'],
        'remote_version'   => NULL,
        'remote_address'   => $lldp['tmnxLldpRemManAddr']
    ];
    discover_neighbour($port, 'lldp', $neighbour);
}

// EOF
