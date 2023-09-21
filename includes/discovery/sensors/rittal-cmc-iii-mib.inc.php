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

// RITTAL-CMC-III-MIB::cmcIIISetTempUnit.0 = INTEGER: celsius(1)
$temp_unit = snmp_get_oid($device, 'cmcIIISetTempUnit.0', $mib);

//// Aggggrrrrr, this is very "logical"..

//RITTAL-CMC-III-MIB::cmcIIIVarName.1.1 = STRING: Temperature.DescName
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.2 = STRING: Temperature.Value
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.3 = STRING: Temperature.Offset
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.4 = STRING: Temperature.SetPtHighAlarm
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.5 = STRING: Temperature.SetPtHighWarning
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.6 = STRING: Temperature.SetPtLowWarning
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.7 = STRING: Temperature.SetPtLowAlarm
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.8 = STRING: Temperature.Hysteresis
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.9 = STRING: Temperature.Status
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.10 = STRING: Temperature.Category
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.11 = STRING: Access.DescName
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.12 = STRING: Access.Value
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.13 = STRING: Access.Sensitivity
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.14 = STRING: Access.Delay
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.15 = STRING: Access.Status
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.16 = STRING: Access.Category
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.17 = STRING: Input 1.DescName
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.18 = STRING: Input 1.Value
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.19 = STRING: Input 1.Logic
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.20 = STRING: Input 1.Delay
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.21 = STRING: Input 1.Status
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.22 = STRING: Input 1.Category
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.23 = STRING: Input 2.DescName
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.24 = STRING: Input 2.Value
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.25 = STRING: Input 2.Logic
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.26 = STRING: Input 2.Delay
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.27 = STRING: Input 2.Status
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.28 = STRING: Input 2.Category
$oids = snmpwalk_cache_oid($device, "cmcIIIVarTable", [], $mib);
//print_debug_vars($oids);

$device_oids = snmpwalk_cache_oid($device, "cmcIIIDevTable", [], $mib);
print_debug_vars($device_oids);

$device_names = [];
foreach ($device_oids as $index => $entry) {
    if ($entry['cmcIIIDevAlias'] !== 'CMCIII-PU') {
        $entry['cmcIIIDevAlias'] = preg_replace('/^CMC\s*III[\s\-]*/', '', $entry['cmcIIIDevAlias']);
    }
    $device_names[$index] = $entry['cmcIIIDevAlias'];

    $name  = $entry['cmcIIIDevAlias'] . ' (' . $entry['cmcIIIDevName'] . ')';
    $descr = "Device - $name Status";

    $oid_name = 'cmcIIIDevStatus';
    $type     = 'cmcIIIDevStatus';
    $value    = $entry['cmcIIIDevStatus'];
    $oid_num  = '.1.3.6.1.4.1.2606.7.4.1.2.1.6.' . $index;

    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'device']);
}

// Rearrage this dumb array as more logic
$device_sensors = [];
foreach ($oids as $index => $entry) {
    $device_index = explode('.', $index)[0];

    $name_parts = explode('.', $entry['cmcIIIVarName']);
    $param      = array_pop($name_parts);
    $param      = $entry['cmcIIIVarType'];
    $name       = implode(' ', $name_parts);

    $device_sensors[$device_index][$name][$param]          = $entry;
    $device_sensors[$device_index][$name][$param]['index'] = $index;
}
print_debug_vars($device_sensors, 1);

// Count devices
$device_count = safe_count($device_sensors);
print_debug("Devices count: $device_count");

foreach ($device_sensors as $device_index => $sensors) {
    foreach ($sensors as $name => $sensor) {

        if ($device_count > 1 && $device_index > 1) {
            $descr = $device_names[$device_index] . ' - ' . $name;
        } else {
            $descr = $name;
        }

        if (strlen($sensor['description']['cmcIIIVarValueStr'])) {
            $tmp = str_replace(['_', 'Sys '],
                               [' ', 'System '], $sensor['description']['cmcIIIVarValueStr']);
            if (!str_contains_array($name, $tmp)) {
                $descr .= ' - ' . $sensor['description']['cmcIIIVarValueStr'];
            }
        }

        if (isset($sensor['status'])) {
            $entry = $sensor['status'];

            $index    = $entry['index'];
            $oid_name = 'cmcIIIVarValueInt';
            $datatype = $entry['cmcIIIVarDataType'];
            $type     = $entry['cmcIIIVarType'];
            //$name  = $entry['cmcIIIVarName'];
            $value   = $entry['cmcIIIVarValueInt']; // $sensor['status']['cmcIIIVarValueInt']
            $oid_num = '.1.3.6.1.4.1.2606.7.4.2.2.1.11.' . $index;

            if ($datatype === 'enum') {
                discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'cmcIIIMsgStatus', $descr, $value, ['entPhysicalClass' => 'other']);
            }

            // Ignore numeric sensors for inactive,
            // see https://jira.observium.org/browse/OBS-3620
            if (str_icontains_array($entry['cmcIIIVarValueStr'], ['Inactive', 'notAvail'])) {
                print_debug("Numeric sensors for '$descr' ignored by inactive status.");
                continue;
            }
        }

        if (isset($sensor['value'])) {
            $entry = $sensor['value'];

            switch ($entry['cmcIIIVarScale'][0]) {
                case '-':
                    $scale = float_div(1, (int)substr($entry['cmcIIIVarScale'], 1));
                    break;
                case '+':
                    $scale = (int)substr($entry['cmcIIIVarScale'], 1);
                    break;
                default:
                    $scale = 1;
            }
            $scale_unit = 1;

            $index = $entry['index'];
            $unit  = $entry['cmcIIIVarUnit'];
            //$type  = $entry['cmcIIIVarType'];
            //$name  = $entry['cmcIIIVarName'];
            $value   = $entry['cmcIIIVarValueInt'];
            $oid_num = '.1.3.6.1.4.1.2606.7.4.2.2.1.11.' . $index;

            $options = [];
            /*
            if ($type == 'outputPWM')
            {
              $t = "power";
            }
            else if ($type == 'rotation')
            {
              $t = "fanspeed";
            }
            else
            */
            $type = NULL;
            if (str_contains_array($unit, 'degree') || str_ends($unit, ['C', 'F'])) {
                if (str_icontains_array($name, ['DewPoint', 'Dew Point'])) {
                    $type = "dewpoint";
                } else {
                    $type = "temperature";
                }
                if ($temp_unit === 'fahrenheit') {
                    $options['sensor_unit'] = 'F';
                }
            } elseif (str_ends($unit, 'V')) {
                $type = "voltage";
            } elseif ($unit === "%") {
                if (str_icontains_array($descr, ['RPM', ' Fan', 'Valve', 'Airflow']) || str_iends($entry['cmcIIIVarName'], 'Rpm')) {
                    $type = "load";
                } elseif (str_icontains_array($name, 'Humidity')) {
                    $type = "humidity";
                }
            } elseif ($unit === '' && str_icontains_array($name, 'Power Factor')) {
                $type       = "powerfactor";
                $scale_unit = -1; // why negative value?
            } elseif (str_contains_array($unit, 'l/min')) {
                $type                   = "waterflow";
                $options['sensor_unit'] = 'l/min';
            } elseif (str_ends($unit, 'Hz') && $value != 0) {
                $type = 'frequency';
            } elseif (str_ends($unit, 'VA')) {
                $type = 'apower';
            } elseif (str_ends($unit, 'var')) {
                $type = 'rpower';
            } elseif (str_ends($unit, 'W')) {
                $type = "power";
            } elseif (str_ends($unit, 'A')) {
                $type = "current";
                if ($unit === 'mA') {
                    $scale_unit = 0.001;
                }
            } elseif (str_ends($unit, 'Wh') && !str_contains($name, 'Custom')) {
                $type         = 'counter';
                $counter_type = 'energy';
                if ($unit === 'kWh') {
                    $scale_unit = 1000;
                }
            } elseif (str_ends($unit, 'VAh') && !str_contains($name, 'Custom')) {
                $type         = 'counter';
                $counter_type = 'aenergy';
                if ($unit === 'kVAh') {
                    $scale_unit = 1000;
                }
            }
            $scale *= $scale_unit;

            if ($type) {
                // Limits
                foreach (['limit_high' => 'setHigh', 'limit_high_warn' => 'setWarn', 'limit_low_warn' => 'setWarnLow', 'limit_low' => 'setLow'] as $limit => $param) {
                    if (isset($sensor[$param]) && is_numeric($sensor[$param]['cmcIIIVarValueInt'])) {
                        $entry = $sensor[$param];

                        switch ($entry['cmcIIIVarScale'][0]) {
                            case '-':
                                $scale_limit = float_div(1, (int)substr($entry['cmcIIIVarScale'], 1));
                                break;
                            case '+':
                                $scale_limit = (int)substr($entry['cmcIIIVarScale'], 1);
                                break;
                            default:
                                $scale_limit = 1;
                        }

                        $options[$limit] = $entry['cmcIIIVarValueInt'] * $scale_limit * $scale_unit;
                    }
                }
                if (isset($options['limit_high'], $options['limit_high_warn'], $options['limit_low_warn'], $options['limit_low']) &&
                    $options['limit_high'] == 0 && $options['limit_high_warn'] == 0 && $options['limit_low_warn'] == 0 && $options['limit_low'] == 0) {
                    // unset all zero limits
                    unset($options['limit_high'], $options['limit_high_warn'], $options['limit_low_warn'], $options['limit_low']);
                    $options['limit_auto'] = FALSE;
                }

                $options['rename_rrd'] = "Rittal-CMC-III-cmcIIIVarTable.$index";
                $object                = 'cmcIIIVarValueInt';

                if ($type === 'counter') {
                    if ($value != 0) {
                        discover_counter($device, $counter_type, $mib, $object, $oid_num, $index, $descr, $scale, $value, $options);
                    }
                } else {
                    discover_sensor_ng($device, $type, $mib, $object, $oid_num, $index, NULL, $descr, $scale, $value, $options);
                }
            } elseif (!(isset($sensor['status']) || isset($sensor['rotation']))) {
                print_debug("[DEBUG] Unknown sensor detected:");
                print_debug_vars($sensor, 1);
            }
        }

        // Not sure about this sensor, converted from old
        if (isset($sensor['rotation'])) {
            $entry = $sensor['rotation'];

            switch ($entry['cmcIIIVarScale'][0]) {
                case '-':
                    $scale = float_div(1, (int)substr($entry['cmcIIIVarScale'], 1));
                    break;
                case '+':
                    $scale = (int)substr($entry['cmcIIIVarScale'], 1);
                    break;
                default:
                    $scale = 1;
            }

            $index = $entry['index'];
            $unit  = $entry['cmcIIIVarUnit'];
            //$type  = $entry['cmcIIIVarType'];
            //$name  = $entry['cmcIIIVarName'];
            $value   = $entry['cmcIIIVarValueInt'];
            $oid_num = '.1.3.6.1.4.1.2606.7.4.2.2.1.11.' . $index;

            $object = 'cmcIIIVarValueInt';

            discover_sensor_ng($device, 'fanspeed', $mib, $object, $oid_num, $index, NULL, $descr, $scale, $value, ['rename_rrd' => "Rittal-CMC-III-cmcIIIVarTable.$index"]);
        }
    }
}

// EOF
