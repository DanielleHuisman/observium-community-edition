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

// FIXME. Seems as possible convert to definitions

$mib = "RITTAL-CMC-TC-MIB";

echo " $mib ";

$sensorElements = ["Index", "Type", "Text", "Value", "SetHigh", "SetLow", "SetWarn", "Status"];

$sensorTables = [
  ['prefix' => 'unit1Sensor', 'table' => 'cmcTcUnit1SensorTable', 'info' => '.1.3.6.1.4.1.2606.4.2.3.2.0', 'valueOID' => '.1.3.6.1.4.1.2606.4.2.3.5.2.1.5.', 'statusOID' => '.1.3.6.1.4.1.2606.4.2.3.5.2.1.4.'],
  ['prefix' => 'unit2Sensor', 'table' => 'cmcTcUnit2SensorTable', 'info' => '.1.3.6.1.4.1.2606.4.2.4.2.0', 'valueOID' => '.1.3.6.1.4.1.2606.4.2.4.5.2.1.5.', 'statusOID' => '.1.3.6.1.4.1.2606.4.2.4.5.2.1.4.'],
  ['prefix' => 'unit3Sensor', 'table' => 'cmcTcUnit3SensorTable', 'info' => '.1.3.6.1.4.1.2606.4.2.5.2.0', 'valueOID' => '.1.3.6.1.4.1.2606.4.2.5.5.2.1.5.', 'statusOID' => '.1.3.6.1.4.1.2606.4.2.5.5.2.1.4.'],
  ['prefix' => 'unit4Sensor', 'table' => 'cmcTcUnit4SensorTable', 'info' => '.1.3.6.1.4.1.2606.4.2.6.2.0', 'valueOID' => '.1.3.6.1.4.1.2606.4.2.6.5.2.1.5.', 'statusOID' => '.1.3.6.1.4.1.2606.4.2.6.5.2.1.4.'],
];

foreach ($sensorTables as $table) {
    $tablename                   = $table['table'];
    $tableprefix                 = $table['prefix'];
    $cache['rittal'][$tablename] = [];

    foreach ($sensorElements as $element) {
        $cache['rittal'][$tablename] = snmpwalk_cache_oid($device, $tableprefix . $element, $cache['rittal'][$tablename], $mib);
    }

    $unit_name = trim(snmp_get($device, $table['info'], "-Ovq", $mib), '"');

    foreach ($cache['rittal'][$tablename] as $index => $entry) {
        $type    = $entry[$tableprefix . 'Type'];
        $name    = $entry[$tableprefix . 'Text'];
        $value   = $entry[$tableprefix . 'Value'];
        $status  = $entry[$tableprefix . 'Status'];
        $options = [];

        if ($type != "notAvail" && $type != NULL) {
            $scale = 1;
            $oid   = $table['valueOID'] . $index;
            $high  = $entry[$tableprefix . 'SetHigh'];
            $low   = $entry[$tableprefix . 'SetLow'];
            $warn  = $entry[$tableprefix . 'SetWarn'];

            switch ($type) {
                case 'humidity':
                case 'humidityWL':
                    $t = 'humidity';
                    break;
                case 'voltage':
                case 'voltagePSM':
                    $t     = 'voltage';
                    $scale = 0.1;
                    break;
                case 'rpm11LCP':
                case 'rpm12LCP':
                case 'rpm21LCP':
                case 'rpm22LCP':
                case 'rpm31LCP':
                case 'rpm32LCP':
                case 'rpm41LCP':
                case 'rpm42LCP':
                case 'fanSpeed':
                    $t = 'fanspeed';
                    break;
                case 'temperature':
                case 'temperatureWL':
                case 'temperature1WL':
                case 'airTemp11LCP':
                case 'airTemp12LCP':
                case 'airTemp21LCP':
                case 'airTemp22LCP':
                case 'airTemp31LCP':
                case 'airTemp32LCP':
                case 'airTemp41LCP':
                case 'airTemp42LCP':
                case 'temp1LCP':
                case 'temp2LCP':
                case 'waterInTemp':
                case 'waterOutTemp':
                    $t = 'temperature';
                    break;
                case 'totalKWPSM':
                case 'totalKWhPSM':
                case 'kWPSM':
                    $t     = 'power';
                    $scale = 100;
                    break;
                case 'amperePSM':
                case 'currentPSM':
                    $t     = 'current';
                    $scale = 0.1;
                    break;
                case 'valve':
                    $t    = 'load';
                    $low  = 0.1;
                    $high = 100;
                    break;
                case 'frequencyPSM':
                    $t     = 'frequency';
                    $scale = 0.1;
                    break;
                case 'waterFlow':
                    $t                      = 'waterflow';
                    $options['sensor_unit'] = 'l/min';
                    break;
                case 'heatflowRCT':
                    $t = 'power';
                    break;
                case 'voltStatusPSM':
                case 'ampStatusPSM':
                case 'statusLCP':
                case 'alarmRCT':
                case 'warningRCT':
                case 'lock':
                case 'access':
                case 'detACfirstAlarm':
                case 'detACmainAlarm':
                case 'detACfault':
                    $t = 'status';
                    break;

                default:
                    continue 2;
            }

            $name = $unit_name . ' ' . $name;

            $limits = [];
            if ($high != 0) {
                $limits['limit_high'] = $high;
            }
            if ($low != 0) {
                $limits['limit_low'] = $low;
            }
            if ($warn != 0) {
                $limits['limit_warn_high'] = $warn;
            }

            if ($t == 'status') {
                $oid                         = $table['statusOID'] . $index;
                $options['entPhysicalClass'] = 'status';
                discover_status($device, $oid, $index, "rittal-cmc-tc-state", $name, $status, $options);
            } else {
                discover_sensor($t, $device, $oid, "$tablename.$index", "Rittal-CMC-TC", $name, $scale, $value, array_merge($limits, $options));
            }
        }
    }
}

// EOF
