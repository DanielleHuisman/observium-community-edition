<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Untagged/primary port vlan

$port_module = 'vlan';

if (!$ports_modules[$port_module]) {
    // Module disabled
    return FALSE;  // False for do not collect stats
}

//EXTREME-VLAN-MIB::extremeVlanIfVlanId.1000004 = INTEGER: 1
//EXTREME-VLAN-MIB::extremeVlanIfVlanId.1000005 = INTEGER: 4095
//EXTREME-VLAN-MIB::extremeVlanOpaqueUntaggedPorts.1000005.1 = Hex-STRING: 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
//00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
//EXTREME-VLAN-MIB::extremeVlanOpaqueUntaggedPorts.1000008.1 = Hex-STRING: FF FF FF FF FF FF 00 00 00 00 00 00 00 00 00 00
//00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
//EXTREME-VLAN-MIB::extremeVlanOpaqueUntaggedPorts.1000008.2 = Hex-STRING: FF FF FF FF FF 18 00 00 00 00 00 00 00 00 00 00
//00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
//EXTREME-VLAN-MIB::extremeVlanOpaqueUntaggedPorts.1000008.3 = Hex-STRING: FF FE 80 00 00 00 00 00 00 00 00 00 00 00 00 00
//00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00

// Base vlan IDs
$vlan_oids = snmpwalk_cache_oid($device, 'extremeVlanIfVlanId', [], 'EXTREME-VLAN-MIB');

if (snmp_status()) {
    echo("extremeVlanOpaqueUntaggedPorts ");

    $ports_vlans_oids = snmpwalk_cache_twopart_oid($device, 'extremeVlanOpaqueUntaggedPorts', [], 'EXTREME-VLAN-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);

    print_debug_vars($ports_vlans_oids);

    $vlan_rows = [];
    foreach ($ports_vlans_oids as $index => $tmp) {
        $vlan_num = $vlan_oids[$index]['extremeVlanIfVlanId'];
        foreach ($tmp as $slot => $vlan) {
            $binary = hex2binmap($vlan['extremeVlanOpaqueUntaggedPorts']);

            // Assign binary vlans map to ports
            $length = strlen($binary);
            for ($i = 0; $i < $length; $i++) {
                if ($binary[$i] && $i > 0) {
                    $trunk = 'dot1Q'; // Hardcode all detected ports as trunk, since no way for detect it correctly

                    $port_map = $slot . ':' . ($i + 1);
                    //$ifIndex = dbFetchCell("SELECT `ifIndex` FROM `ports` WHERE `device_id` = ? AND (`ifDescr` LIKE ? OR `ifName` = ?) AND `deleted` = ? LIMIT 1", array($device['device_id'], '% '.$port_map, $port_map, 0));
                    foreach ($port_stats as $ifIndex => $entry) {
                        if ($entry['ifName'] == $port_map || str_ends($entry['ifDescr'], ' ' . $port_map)) {
                            $vlan_rows[] = [$ifIndex, $vlan_num, $trunk];

                            // Set Vlan and Trunk
                            $port_stats[$ifIndex]['ifVlan']  = $vlan_num;
                            $port_stats[$ifIndex]['ifTrunk'] = $trunk;

                            break; // Stop ports loop
                        }
                    }

                }
            }
        }

    }

}

$headers = ['%WifIndex%n', '%WVlan%n', '%WTrunk%n'];
print_cli_table($vlan_rows, $headers);

//$process_port_functions[$port_module] = $GLOBALS['snmp_status'];

// Additional db fields for update
//$process_port_db[$port_module][] = 'ifVlan';
//$process_port_db[$port_module][] = 'ifTrunk';

// EOF
