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

// lldpRemoteSystemsData: lldpRemTable + lldpRemManAddrTable + lldpRemUnknownTLVTable + lldpRemOrgDefInfoTable
$lldp_array = snmpwalk_cache_oid($device, 'lldpRemChassisIdSubtype', [], "LLDP-MIB");
if (empty($lldp_array)) {
    return;
}
$lldp_array = snmpwalk_cache_oid($device, 'lldpRemChassisId', $lldp_array, "LLDP-MIB");
$lldp_array = snmpwalk_cache_oid($device, 'lldpRemPortIdSubtype', $lldp_array, "LLDP-MIB");
$lldp_array = snmpwalk_cache_oid($device, 'lldpRemPortId', $lldp_array, "LLDP-MIB");
$lldp_array = snmpwalk_cache_oid($device, 'lldpRemSysName', $lldp_array, "LLDP-MIB");

// lldpRemSysDesc can be multiline
$lldp_array = snmpwalk_cache_oid($device, 'lldpRemPortDesc', $lldp_array, "LLDP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
$lldp_array = snmpwalk_cache_oid($device, 'lldpRemSysDesc', $lldp_array, "LLDP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);

if (is_device_mib($device, 'LLDP-EXT-MED-MIB')) {
    // See Cumulus Linux
    // not exist lldpRemSysName, lldpRemSysDesc
    $lldp_array = snmpwalk_cache_oid($device, 'lldpXMedRemSoftwareRev', $lldp_array, "LLDP-EXT-MED-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
    $lldp_array = snmpwalk_cache_oid($device, 'lldpXMedRemMfgName', $lldp_array, "LLDP-EXT-MED-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
    $lldp_array = snmpwalk_cache_oid($device, 'lldpXMedRemModelNam', $lldp_array, "LLDP-EXT-MED-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
}

// lldpRemManAddrTable
fetch_oids_lldp_addr($device, $lldp_array);

print_debug_vars($lldp_array, 1);

$dot1d_array = snmp_cache_table($device, "dot1dBasePortIfIndex", [], "BRIDGE-MIB");
//$lldp_local_array = snmpwalk_cache_oid($device, "lldpLocalSystemData", [], "LLDP-MIB");
$lldp_local_array = snmpwalk_cache_oid($device, "lldpLocPortEntry", [], "LLDP-MIB");

foreach ($lldp_array as $index => $lldp) {
    if (str_contains_array($index, '.')) {
        // This is correct RFC case:
        // LLDP-MIB::lldpRemChassisIdSubtype.0.0.1 = INTEGER: macAddress(4)
        [$lldpRemTimeMark, $lldpRemLocalPortNum, $lldpRemIndex] = explode('.', $index);
    } else {
        // Incorrect case (ie on old RouterOS):
        // LLDP-MIB::lldpRemChassisIdSubtype.1495 = INTEGER: macAddress(4)
        $lldpRemTimeMark     = 0;
        $lldpRemLocalPortNum = 0;
        $lldpRemIndex        = $index;
    }

    // Detect local device port
    $port = NULL;

    // Prefer by LLDP-MIB
    if (!$port && !empty($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortDesc'])) {
        //lldpLocPortIdSubtype.15 = interfaceName
        //lldpLocPortIdSubtype.16 = interfaceName
        //lldpLocPortId.15 = "Te1/15"
        //lldpLocPortId.16 = "Te1/16"
        //lldpLocPortDesc.15 = TenGigabitEthernet1/15
        //lldpLocPortDesc.16 = TenGigabitEthernet1/16
        $ifName = $lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortDesc'];
        if ($ifName === 'OOBM') {
            // procurve:
            // ifDescr.5481 = Out Of Band Management Port
            // ifDescr.5492 = Out Of Band Management loopback Interface
            // ifName.5481 = oobm0
            // ifName.5492 = lo0
            // lldpLocPortIdSubtype.4000 = local
            // lldpLocPortId.4000 = "4000"
            // lldpLocPortDesc.4000 = OOBM
            $ifName .= '0';
        }
        $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND (`ifName`= ? OR `ifDescr` = ? OR `port_label_short` = ?)", [$device['device_id'], 0, $ifName, $ifName, $ifName]);
    }

    // By BRIDGE-MIB (Warning, seems as more hard on multiple platforms not correctly association with ifIndex for LLDP)
    if (!$port && is_numeric($dot1d_array[$lldpRemLocalPortNum]['dot1dBasePortIfIndex']) &&
        !in_array($device['os'], [ 'junos', 'junos-evo', 'dell-os10' ])) { // Incorrect association on these platforms
        // Gets the port using BRIDGE-MIB
        $ifIndex = $dot1d_array[$lldpRemLocalPortNum]['dot1dBasePortIfIndex'];

        $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND `ifIndex` = ? AND `ifDescr` NOT LIKE 'Vlan%'", [$device['device_id'], 0, $ifIndex]);
    }

    // last try by lldpLocPortId, also see below for remote port
    if (!$port) {
        switch ($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortIdSubtype']) {
            case 'interfaceName':
                $ifName = snmp_hexstring($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']);
                $port   = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?)", [$device['device_id'], 0, $ifName, $ifName, $ifName]);
                break;

            case 'interfaceAlias':
                $ifAlias = snmp_hexstring($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']);
                $port    = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND `ifAlias` = ?", [$device['device_id'], 0, $ifAlias]);
                break;

            case 'macAddress':
                $ifPhysAddress = strtolower(str_replace(' ', '', $lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']));
                $port          = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND `ifPhysAddress` = ?", [$device['device_id'], 0, $ifPhysAddress]);
                break;

            case 'networkAddress':
                $ip = snmp_hexstring($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']);
                /*
                $ip_version = get_ip_version($id);
                if ($ip_version)
                {
                  $ip = ($ip_version === 6 ? Net_IPv6::uncompress($id, TRUE) : $id);
                  $port = dbFetchRow("SELECT * FROM `ipv".$ip_version."_addresses` LEFT JOIN `ports` USING (`port_id`) WHERE `ipv".$ip_version."_address` = ? AND `device_id` = ?", array($ip, $device['device_id']));
                }
                unset($id, $ip);
                */
                $peer_where = generate_query_values_and($device['device_id'], 'device_id'); // Additional filter for include self IPs
                // Fetch all devices with peer IP and filter by UP
                if ($ids = get_entity_ids_ip_by_network('port', $ip, $peer_where)) {
                    $port = get_port_by_id_cache($ids[0]);
                }

                break;
        }
    }

    // Ohh still unknown port? this is not should happen, but this derp LLDP implementation on your device
    if (!$port && is_numeric($lldpRemLocalPortNum)) {
        print_debug('WARNING. Local port for neighbour not found, used incorrect lldpRemLocalPortNum as ifIndex.');
        $ifIndex = $lldpRemLocalPortNum; // This is incorrect, not really ifIndex, but seems sometime this numbers same
        $port    = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND `ifIndex` = ? AND `ifDescr` NOT LIKE 'Vlan%'", [$device['device_id'], 0, $ifIndex]);
        if (!$port && $device['os'] === 'ciena-waveserveros') {
            $ifName = 'PORT-' . snmp_hexstring($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']);
            $port   = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?)", [$device['device_id'], 0, $ifName, $ifName, $ifName]);
        }
    }

    $remote_device_id = FALSE;
    $remote_port_id   = NULL;

    // Sometime lldpRemPortDesc is not set
    if (!isset($lldp['lldpRemPortDesc'])) {
        $lldp['lldpRemPortDesc'] = '';
    }

    $remote_mac = $lldp['lldpRemChassisIdSubtype'] === 'macAddress' ? $lldp['lldpRemChassisId'] : NULL;

    // lldpRemPortId can be hex string
    if ($lldp['lldpRemPortIdSubtype'] !== 'macAddress') {
        // On Extreme platforms, they remove the leading 1: from ports. Put it back if there isn't a :.
        if (preg_match('/^ExtremeXOS.*$/', $lldp['lldpRemSysDesc'])) {
            if (!preg_match('/\:/', $lldp['lldpRemPortId'])) {
                $lldp['lldpRemPortId'] = '1:' . $lldp['lldpRemPortId'];
            }
        } else {
            //$lldp['lldpRemPortId'] = snmp_hexstring($lldp['lldpRemPortId']);
        }
    } elseif (safe_empty($remote_mac)) {
        $remote_mac = $lldp['lldpRemPortId'];
    }

    // Cumulus Linux have empty lldpRemSysDesc
    if (!isset($lldp['lldpRemSysDesc']) && isset($lldp['lldpXMedRemModelName'])) {
        $lldp['lldpRemSysDesc'] = trim($lldp['lldpXMedRemMfgName'] . ' ' . $lldp['lldpXMedRemModelName']);
    }
    $lldp['lldpRemSysVersion'] = safe_empty($lldp['lldpXMedRemSoftwareRev']) ? NULL : $lldp['lldpXMedRemSoftwareRev'];

    // Remote sysname is MAC in some cases
    // lldpRemChassisIdSubtype.0.16.1 = macAddress
    // lldpRemChassisId.0.16.1 = "40 E3 D6 CE 8A 9A "
    // lldpRemPortIdSubtype.0.16.1 = macAddress
    // lldpRemPortId.0.16.1 = "40 E3 D6 CE 8A 9A "
    // lldpRemSysName.0.16.1 = 40:e3:d6:ce:8a:9a
    // lldpRemManAddrIfId.0.16.1.1.4.10.10.14.132 = 8
    if (preg_match('/^[a-f\d]{2}([: ][a-f\d]{2}){5}$/', $lldp['lldpRemSysName']) && get_ip_version($lldp['lldpRemManAddr'])) {
        // Replace by IP address for better discovery
        print_debug("LLDP hostname replaced: " . $lldp['lldpRemSysName'] . " -> " . $lldp['lldpRemManAddr']);
        $lldp['lldpRemSysName'] = $lldp['lldpRemManAddr'];
    } elseif (safe_empty($lldp['lldpRemSysName']) && !safe_empty($lldp['lldpRemSysDesc'])) {
        if ($lldp['lldpRemChassisIdSubtype'] === 'macAddress') {
            // use mac address instead empty hostname:
            // lldpRemChassisIdSubtype.89.3.1 = macAddress
            // lldpRemChassisId.89.3.1 = "6C B0 CE 12 A9 A8 "
            // lldpRemPortIdSubtype.89.3.1 = local
            // lldpRemPortId.89.3.1 = "g25"
            // lldpRemPortDesc.89.3.1 = g25
            // lldpRemSysName.89.3.1 =
            // lldpRemSysDesc.89.3.1 = GS724Tv4 ProSafe 24-port Gigabit Ethernet Smart Switch, 6.3.1.4, B1.0.0.4
            // lldpRemManAddrIfId.89.3.1.1.4.192.168.0.239 = 51
            $lldp['lldpRemSysName'] = str_replace([' ', '-'], '', strtolower($lldp['lldpRemChassisId']));
        } elseif ($lldp['lldpRemChassisIdSubtype'] === 'networkAddress' &&
                  preg_match('/^01 (?<ip>([A-F\d]{2}\s?){4})$/', $lldp['lldpRemChassisId'], $matches)) {
            $lldp['lldpRemSysName'] = hex2ip($matches['ip']);
        }
    }

    // Try find remote device and check if already cached
    $remote_device_id = get_autodiscovery_device_id($device, $lldp['lldpRemSysName'], $lldp['lldpRemManAddr'], $remote_mac);
    if (is_null($remote_device_id) &&                                            // NULL - never cached in other rounds
        check_autodiscovery($lldp['lldpRemSysName'], $lldp['lldpRemManAddr'])) { // Check all previous autodiscovery rounds
        // Neighbour never checked, try autodiscovery
        $remote_device_id = autodiscovery_device($lldp['lldpRemSysName'], $lldp['lldpRemManAddr'], 'LLDP', $lldp['lldpRemSysDesc'], $device, $port);
    }

    if ($remote_device_id) {
        $if = $lldp['lldpRemPortDesc'];
        $id = trim($lldp['lldpRemPortId']);

        // lldpPortIdSubtype   -> lldpPortId
        //  interfaceAlias(1), ->  ifAlias
        //  portComponent(2),  ->  entPhysicalAlias
        //  macAddress(3),     ->  ifPhysAddress
        //  networkAddress(4), ->  IP address
        //  interfaceName(5),  ->  ifName
        //  agentCircuitId(6), ->  agent-local identifier of the circuit (defined in RFC 3046) (FIXME, not know)
        //  local(7)           ->  ifIndex
        switch ($lldp['lldpRemPortIdSubtype']) {
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

        if (!$remote_port_id && is_numeric($id)) {
            // Not found despite our attempts above - fall back to try matching with ifDescr/ifIndex
            $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE (`ifIndex`= ? OR `ifDescr` = ?) AND `device_id` = ? AND `deleted` = ?", [$id, $if, $remote_device_id, 0]);
        }

        if (!$remote_port_id) { // Still not found?
            if ($lldp['lldpRemChassisIdSubtype'] === 'macAddress') {
                // Find the port by chassis MAC address, only use this if exactly 1 match is returned, otherwise we'd link wrongly - think switches with 1 global MAC on all ports.
                $remote_port_id = get_port_id_by_mac($remote_device_id, $lldp['lldpRemChassisId']);
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

    // FIXME. We can use lldpRemSysCapEnabled as platform, but they use BITS textual conversion:
    // LLDP-MIB::lldpRemSysCapEnabled.0.5.3 = BITS: 20 00 bridge(2)
    // LLDP-MIB::lldpRemSysCapEnabled.0.5.3 = "20 00 "
    $neighbour = [
        'remote_device_id' => $remote_device_id,
        'remote_port_id'   => $remote_port_id,
        'remote_hostname'  => $lldp['lldpRemSysName'],
        'remote_port'      => $lldp['lldpRemPortId'],
        'remote_platform'  => $lldp['lldpRemSysDesc'],
        'remote_version'   => $lldp['lldpRemSysVersion'], //NULL,
        'remote_address'   => $lldp['lldpRemManAddr']
    ];
    discover_neighbour($port, 'lldp', $neighbour);
}

// EOF
