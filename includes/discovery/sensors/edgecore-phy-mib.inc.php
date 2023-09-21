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

/* Not tested with real data!
EDGECORE-PHY-MIB::rlPhyTestGetResult.9.rlPhyTestTableTransceiverTemp = INTEGER: 0
EDGECORE-PHY-MIB::rlPhyTestGetResult.9.rlPhyTestTableTransceiverSupply = INTEGER: 0
EDGECORE-PHY-MIB::rlPhyTestGetResult.9.rlPhyTestTableTxBias = INTEGER: 0
EDGECORE-PHY-MIB::rlPhyTestGetResult.9.rlPhyTestTableTxOutput = INTEGER: 0
EDGECORE-PHY-MIB::rlPhyTestGetResult.9.rlPhyTestTableRxOpticalPower = INTEGER: 0
EDGECORE-PHY-MIB::rlPhyTestGetResult.9.rlPhyTestTableDataReady = INTEGER: 0
EDGECORE-PHY-MIB::rlPhyTestGetResult.9.rlPhyTestTableLOS = INTEGER: 0
EDGECORE-PHY-MIB::rlPhyTestGetResult.9.rlPhyTestTableTxFault = INTEGER: 0

EDGECORE-PHY-MIB::rlPhyTestGetUnits.9.rlPhyTestTableTransceiverTemp = INTEGER: degree(7)
EDGECORE-PHY-MIB::rlPhyTestGetUnits.9.rlPhyTestTableTransceiverSupply = INTEGER: microVolt(8)
EDGECORE-PHY-MIB::rlPhyTestGetUnits.9.rlPhyTestTableTxBias = INTEGER: microAmper(10)
EDGECORE-PHY-MIB::rlPhyTestGetUnits.9.rlPhyTestTableTxOutput = INTEGER: milidbm(17)
EDGECORE-PHY-MIB::rlPhyTestGetUnits.9.rlPhyTestTableRxOpticalPower = INTEGER: milidbm(17)
EDGECORE-PHY-MIB::rlPhyTestGetUnits.9.rlPhyTestTableDataReady = INTEGER: boolean(2)
EDGECORE-PHY-MIB::rlPhyTestGetUnits.9.rlPhyTestTableLOS = INTEGER: boolean(2)
EDGECORE-PHY-MIB::rlPhyTestGetUnits.9.rlPhyTestTableTxFault = INTEGER: boolean(2)

EDGECORE-PHY-MIB::rlPhyTestGetStatus.9.rlPhyTestTableTransceiverTemp = INTEGER: success(2)
EDGECORE-PHY-MIB::rlPhyTestGetStatus.9.rlPhyTestTableTransceiverSupply = INTEGER: success(2)
EDGECORE-PHY-MIB::rlPhyTestGetStatus.9.rlPhyTestTableTxBias = INTEGER: success(2)
EDGECORE-PHY-MIB::rlPhyTestGetStatus.9.rlPhyTestTableTxOutput = INTEGER: success(2)
EDGECORE-PHY-MIB::rlPhyTestGetStatus.9.rlPhyTestTableRxOpticalPower = INTEGER: success(2)
EDGECORE-PHY-MIB::rlPhyTestGetStatus.9.rlPhyTestTableDataReady = INTEGER: success(2)
EDGECORE-PHY-MIB::rlPhyTestGetStatus.9.rlPhyTestTableLOS = INTEGER: success(2)
EDGECORE-PHY-MIB::rlPhyTestGetStatus.9.rlPhyTestTableTxFault = INTEGER: success(2)
*/

//$oid_base = $config['mibs'][$mib]['identity_num'];
$oid_base = '.1.3.6.1.4.1.259.10.1.14.89.90';
$oids     = snmpwalk_cache_twopart_oid($device, 'rlPhyTestGetResult', [], $mib);
$new_oids = [];
foreach ($oids as $index => $entry) {
    // Skip all non-dom entries
    if (!isset($entry['rlPhyTestTableTransceiverTemp']) || $entry['rlPhyTestTableTransceiverTemp']['rlPhyTestGetResult'] == 0) {
        continue;
    }

    $new_oids[$index] = $entry;
}

if (count($new_oids) === 0) {
    // Stop walk if not exist DOM sensors
    return;
}

// Get additional OIDs
$oids = snmpwalk_cache_twopart_oid($device, 'rlPhyTestGetUnits', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'rlPhyTestGetStatus', $oids, $mib);

print_debug_vars($oids);

foreach ($new_oids as $ifIndex => $entry1) {
    foreach ($oids[$ifIndex] as $oid_name => $entry) {
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

        $entry['ifIndex'] = $ifIndex;
        $match            = ['measured_match' => ['entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%ifIndex%']];
        $options          = entity_measured_match_definition($device, $match, $entry);
        //print_debug_vars($options);

        $name = $options['port_label'];

        switch ($oid_name) {
            case 'rlPhyTestTableTransceiverTemp':
                $descr = $name . " Temperature";
                $index = $ifIndex . '.5';
                $oid   = "$oid_base.1.2.1.3.$index";
                $class = 'temperature';

                break;

            case 'rlPhyTestTableTransceiverSupply':
                $descr = $name . " Voltage";
                $index = $ifIndex . '.6';
                $oid   = "$oid_base.1.2.1.3.$index";
                $class = 'voltage';

                break;

            case 'rlPhyTestTableTxBias':
                $descr = $name . " Tx Bias";
                $index = $ifIndex . '.7';
                $oid   = "$oid_base.1.2.1.3.$index";
                $class = 'current';

                break;

            case 'rlPhyTestTableTxOutput':
                $descr = $name . " TX Power";
                $index = $ifIndex . '.8';
                $oid   = "$oid_base.1.2.1.3.$index";
                $class = 'dbm';

                break;

            case 'rlPhyTestTableRxOpticalPower':
                $descr = $name . " RX Power";
                $index = $ifIndex . '.9';
                $oid   = "$oid_base.1.2.1.3.$index";
                $class = 'dbm';

                break;

            default:
                continue 2;
        }
        $value = $entry['rlPhyTestGetResult'];
        discover_sensor_ng($device, $class, $mib, 'rlPhyTestGetResult', $oid, $index, NULL, $descr, $scale, $value, $options);
    }
}

// EOF
