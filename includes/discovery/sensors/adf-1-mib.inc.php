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

$oids = snmpwalk_cache_bare_oid($device, 'bufferinval', [], 'ADF-1-MIB');

$oid_defs     = [];
$oid_defs[1]  = ['name' => 'powerFactor', 'class' => 'powerfactor', 'scale' => '0.0001', 'descr' => ''];
$oid_defs[2]  = ['name' => 'voltageLL', 'class' => 'voltage', 'scale' => '0.1', 'descr' => 'LL'];
$oid_defs[3]  = ['name' => 'voltageLN', 'class' => 'voltage', 'scale' => '0.1', 'descr' => 'LN'];
$oid_defs[4]  = ['name' => 'current', 'class' => 'current', 'scale' => '0.01', 'descr' => ''];
$oid_defs[5]  = ['name' => 'frequency', 'class' => 'frequency', 'scale' => '0.01', 'descr' => ''];
$oid_defs[6]  = ['name' => 'currentL1', 'class' => 'current', 'scale' => '0.01', 'descr' => 'L1'];
$oid_defs[7]  = ['name' => 'currentL2', 'class' => 'current', 'scale' => '0.01', 'descr' => 'L2'];
$oid_defs[8]  = ['name' => 'currentL3', 'class' => 'current', 'scale' => '0.01', 'descr' => 'L3'];
$oid_defs[9]  = ['name' => 'currentN', 'class' => 'current', 'scale' => '0.01', 'descr' => 'N'];
$oid_defs[10] = ['name' => 'voltageL1L2', 'class' => 'voltage', 'scale' => '0.1', 'descr' => 'L1L2'];
$oid_defs[11] = ['name' => 'voltageL2L3', 'class' => 'voltage', 'scale' => '0.1', 'descr' => 'L2L3'];
$oid_defs[12] = ['name' => 'voltageL3L1', 'class' => 'voltage', 'scale' => '0.1', 'descr' => 'L3L1'];
$oid_defs[13] = ['name' => 'voltageL1N', 'class' => 'voltage', 'scale' => '0.1', 'descr' => 'L1N1'];
$oid_defs[14] = ['name' => 'voltageL2N', 'class' => 'voltage', 'scale' => '0.1', 'descr' => 'L2N1'];
$oid_defs[15] = ['name' => 'voltageL3N', 'class' => 'voltage', 'scale' => '0.1', 'descr' => 'L3N1'];

$i = 1;
while ($i < 29) {

    if ($i == 25 || $i == 26) {
    } else {

        foreach ($oid_defs as $n => $x) {
            $x['index'] = $n + (($i - 1) * 15);

            if ($i > 26) {
                $x['index'] -= 10;
            }

            $x['oid_num']  = '.1.3.6.1.4.1.49314.1.1.1.4.9.' . $x['index'];
            $x['oid_text'] = $x['name'] . $i;
            $x['descr']    = 'Feed ' . $i . (strlen($config['custom']['adf-1-mib'][$i]['descr']) ? ' (' . $config['custom']['adf-1-mib'][$i]['descr'] . ')' : '') . ' ' . $x['descr'];
            $x['value']    = $oids[$x['oid_text']] * $x['scale'];

            echo $x['oid_num'] . '  ' . $x['oid_text'] . PHP_EOL;

            //print_r($x);

            if (is_numeric($oids[$x['oid_text']])) {
                discover_sensor_ng($device, $x['class'], $mib, $x['name'], $x['oid_num'], $x['index'], 'adf01', $x['descr'], $x['scale'], $oids[$x['oid_text']]);
            } else {
            }
        }

    }

    $i++;
}

// Note that these multipliers MAY NOT be the same as the multipliers above!

$oid_defsi     = [];
$oid_defsi[1]  = ['name' => 'powerFactor', 'class' => 'powerfactor', 'scale' => '0.001', 'descr' => ''];
$oid_defsi[2]  = ['name' => 'voltageLL', 'class' => 'voltage', 'scale' => '1', 'descr' => 'LL'];
$oid_defsi[3]  = ['name' => 'current', 'class' => 'current', 'scale' => '1', 'descr' => ''];
$oid_defsi[4]  = ['name' => 'frequency', 'class' => 'frequency', 'scale' => '0.01', 'descr' => ''];
$oid_defsi[5]  = ['name' => 'currentL1', 'class' => 'current', 'scale' => '1', 'descr' => 'L1'];
$oid_defsi[6]  = ['name' => 'currentL2', 'class' => 'current', 'scale' => '1', 'descr' => 'L2'];
$oid_defsi[7]  = ['name' => 'currentL3', 'class' => 'current', 'scale' => '1', 'descr' => 'L3'];
$oid_defsi[8]  = ['name' => 'voltageL1L2', 'class' => 'voltage', 'scale' => '1', 'descr' => 'L1L2'];
$oid_defsi[9]  = ['name' => 'voltageL2L3', 'class' => 'voltage', 'scale' => '1', 'descr' => 'L2L3'];
$oid_defsi[10] = ['name' => 'voltageL3L1', 'class' => 'voltage', 'scale' => '1', 'descr' => 'L3L1'];

foreach ([25, 26] as $i) {
    foreach ($oid_defsi as $n => $x) {
        $x['index']    = $n + ((24) * 15) + (($i - 25) * 10);
        $x['oid_num']  = '.1.3.6.1.4.1.49314.1.1.1.4.9.' . $x['index'];
        $x['oid_text'] = $x['name'] . $i;
        $x['descr']    = 'Feed ' . $i . (strlen($config['custom']['adf-1-mib'][$i]['descr']) ? ' (' . $config['custom']['adf-1-mib'][$i]['descr'] . ')' : '') . ' ' . $x['descr'];
        $x['value']    = $oids[$x['oid_text']];

        //print_r($x);

        if (is_numeric($oids[$x['oid_text']])) {
            discover_sensor_ng($device, $x['class'], $mib, $x['name'], $x['oid_num'], $x['index'], 'adf01', $x['descr'], $x['scale'], $oids[$x['oid_text']]);
        } else {
        }
    }
}
