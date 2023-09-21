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

$oids = snmpwalk_cache_oid($device, 'analogInputEntry', [], 'PACKETFLUX-SITEMONITOR');

foreach ($oids as $index => $data) {

    $match   = [];
    $match[] = ['string' => '(0.1C)', 'class' => 'temperature', 'scale' => 0.1];
    $match[] = ['string' => '(0.1mV)', 'class' => 'voltage', 'scale' => 0.0001];
    $match[] = ['string' => '(C)', 'class' => 'temperature', 'scale' => 1];
    $match[] = ['string' => '(0.1V)', 'class' => 'voltage', 'scale' => 0.1];
    $match[] = ['string' => '(mA)', 'class' => 'current', 'scale' => 0.001];
    $match[] = ['string' => 'AH*10', 'class' => 'charge', 'scale' => 0.1];
    $match[] = ['string' => 'KwH*10', 'class' => 'energy', 'scale' => 0.0001];
    $match[] = ['string' => 'V*100', 'class' => 'voltage', 'scale' => 0.01];
    $match[] = ['string' => 'Volts*100', 'class' => 'voltage', 'scale' => 0.01];
    $match[] = ['string' => 'Amps*100', 'class' => 'current', 'scale' => 0.01];
    $match[] = ['string' => 'Watts*100', 'class' => 'power', 'scale' => 0.01];
    $match[] = ['string' => 'W*100', 'class' => 'power', 'scale' => 0.01];
    $match[] = ['string' => 'Temperature', 'class' => 'temperature', 'scale' => 0.1]; // This seems common.


    foreach ($match as $m) {

        if (strpos($data['analogInputDescr'], $m['string'])) {
            $data['class']            = $m['class'];
            $data['scale']            = $m['scale'];
            $data['analogInputDescr'] = str_replace($m['string'], "", $data['analogInputDescr']);
            $data['analogInputDescr'] = str_replace('()', "", $data['analogInputDescr']);
            $data['analogInputDescr'] = trim($data['analogInputDescr']);
            continue;
        }

    }

    foreach (['Min', 'Max', 'LdLow', 'LdHi', 'Avg', 'ChgHi', 'Tdy'] as $m) {
        if (strpos($data['analogInputDescr'], $m) !== FALSE) {
            unset($data);
        }
    }

    if (is_array($data) && !isset($data['class'])) {
        print_r($data);
        unset($data);
    }

    if ($data['analogInputPowerOnValue'] != '0') {
        unset($data);
    } // Ignore these, I think they're thresholds or something odd. }

    $data['oid'] = '.1.3.6.1.4.1.32050.2.1.27.5.' . $index;

    if (is_array($data)) {
        // energy and charge will forced to discover_counter()
        discover_sensor_ng($device, $data['class'], $mib, 'analogInputValue', $data['oid'], $index, NULL, $data['analogInputDescr'], $data['scale'], $data['analogInputValue'], ['rename_rrd' => "packetflux-packetflux-analog-$index"]);
    }

}

unset($data, $match, $m, $index);

// EOF
