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

//CISCOSB-PHY-MIB::rlPhyTestGetResult.73.rlPhyTestTableTransceiverTemp = INTEGER: 45
//CISCOSB-PHY-MIB::rlPhyTestGetResult.73.rlPhyTestTableTransceiverSupply = INTEGER: 3348500
//CISCOSB-PHY-MIB::rlPhyTestGetResult.73.rlPhyTestTableTxBias = INTEGER: 19400
//CISCOSB-PHY-MIB::rlPhyTestGetResult.73.rlPhyTestTableTxOutput = INTEGER: -6292
//CISCOSB-PHY-MIB::rlPhyTestGetResult.73.rlPhyTestTableRxOpticalPower = INTEGER: -5653
//CISCOSB-PHY-MIB::rlPhyTestGetResult.73.rlPhyTestTableDataReady = INTEGER: 0
//CISCOSB-PHY-MIB::rlPhyTestGetResult.73.rlPhyTestTableLOS = INTEGER: 0
//CISCOSB-PHY-MIB::rlPhyTestGetResult.73.rlPhyTestTableTxFault = INTEGER: 0
//CISCOSB-PHY-MIB::rlPhyTestGetResult.74.rlPhyTestTableTransceiverTemp = INTEGER: 44
//CISCOSB-PHY-MIB::rlPhyTestGetResult.74.rlPhyTestTableTransceiverSupply = INTEGER: 3348500
//CISCOSB-PHY-MIB::rlPhyTestGetResult.74.rlPhyTestTableTxBias = INTEGER: 17795
//CISCOSB-PHY-MIB::rlPhyTestGetResult.74.rlPhyTestTableTxOutput = INTEGER: -3726
//CISCOSB-PHY-MIB::rlPhyTestGetResult.74.rlPhyTestTableRxOpticalPower = INTEGER: -3715
//CISCOSB-PHY-MIB::rlPhyTestGetResult.74.rlPhyTestTableDataReady = INTEGER: 0
//CISCOSB-PHY-MIB::rlPhyTestGetResult.74.rlPhyTestTableLOS = INTEGER: 0
//CISCOSB-PHY-MIB::rlPhyTestGetResult.74.rlPhyTestTableTxFault = INTEGER: 0

$oids     = snmpwalk_cache_twopart_oid($device, 'rlPhyTestGetResult', [], 'CISCOSB-PHY-MIB');
$new_oids = [];
foreach ($oids as $index => $entry) {
    if (!isset($entry['rlPhyTestTableTransceiverTemp']) || $entry['rlPhyTestTableTransceiverTemp']['rlPhyTestGetResult'] == 0) {
        continue;
    } // Skip all non-dom entries
    $new_oids[$index] = $entry;
}

if (count($new_oids) == 0) {
    // Stop walk if not exist DOM sensors
    return;
}
// Get additional OIDs
$oids = snmpwalk_cache_twopart_oid($device, 'rlPhyTestGetUnits', $oids, 'CISCOSB-PHY-MIB');
$oids = snmpwalk_cache_twopart_oid($device, 'rlPhyTestGetStatus', $oids, 'CISCOSB-PHY-MIB');
//print_vars($oids);

foreach ($new_oids as $index => $entry1) {
    foreach ($oids[$index] as $oid_name => $entry) {
        if (!in_array($entry['rlPhyTestGetStatus'], ['success', 'inProgress'])) {
            continue;
        }

        switch ($entry['rlPhyTestGetUnits']) {
            case 'microVolt':
            case 'microAmper':
            case 'microOham':
            case 'microWatt':
                $scale = si_to_scale('micro');
                break;
            case 'milidbm':
                $scale = si_to_scale('milli');
                break;
            case 'decidbm':
                $scale = si_to_scale('deci');
                break;
            default:
                $scale = 1;
        }

        $options = ['entPhysicalIndex' => $index];
        $port    = get_port_by_index_cache($device['device_id'], $index);
        if (is_array($port)) {
            $entry['ifDescr']           = $port['ifDescr'];
            $options['measured_class']  = 'port';
            $options['measured_entity'] = $port['port_id'];
        } else {
            $entry['ifDescr'] = snmp_get($device, "ifDescr." . $index, "-Oqv", "IF-MIB");
        }

        switch ($oid_name) {
            case 'rlPhyTestTableTransceiverTemp':
                $descr = $entry['ifDescr'] . " Temperature";
                $oid   = ".1.3.6.1.4.1.9.6.1.101.90.1.2.1.3.$index.5";
                $class = 'temperature';
                break;
            case 'rlPhyTestTableTransceiverSupply':
                $descr = $entry['ifDescr'] . " Voltage";
                $oid   = ".1.3.6.1.4.1.9.6.1.101.90.1.2.1.3.$index.6";
                $class = 'voltage';

                break;
            case 'rlPhyTestTableTxBias':
                $descr = $entry['ifDescr'] . " Bias Current";
                $oid   = ".1.3.6.1.4.1.9.6.1.101.90.1.2.1.3.$index.7";
                $class = 'current';
                break;
            case 'rlPhyTestTableTxOutput':
                $descr = $entry['ifDescr'] . " TX Power";
                $oid   = ".1.3.6.1.4.1.9.6.1.101.90.1.2.1.3.$index.8";
                $class = 'dbm';
                break;
            case 'rlPhyTestTableRxOpticalPower':
                $descr = $entry['ifDescr'] . " RX Power";
                $oid   = ".1.3.6.1.4.1.9.6.1.101.90.1.2.1.3.$index.9";
                $class = 'dbm';
                break;
            default:
                continue 2;
        }
        $value = $entry['rlPhyTestGetResult'];
        discover_sensor_ng($device, $class, $mib, 'rlPhyTestGetResult', $oid, "$index.$oid_name", NULL, $descr, $scale, $value, $options); // Note, same rrd index format as in mibs definitions
    }
}

// EOF
