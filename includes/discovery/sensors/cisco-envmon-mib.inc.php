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

// Old CISCO-ENVMON-MIB

$sensor_type       = 'cisco-envmon';
$sensor_state_type = 'cisco-envmon-state';

// Temperatures:
$oids = snmpwalk_cache_oid($device, 'ciscoEnvMonTemperatureStatusEntry', [], 'CISCO-ENVMON-MIB');

foreach ($oids as $index => $entry) {
    $descr = $entry['ciscoEnvMonTemperatureStatusDescr'];
    if ($descr == '') {
        continue;
    } // Skip sensors with empty description, seems like Cisco bug

    if (isset($entry['ciscoEnvMonTemperatureStatusValue'])) {

        // Skip if this is a duplicate of an entSensor entry.
        if (isset($GLOBALS['valid']['sensor']['temperature']['CISCO-ENTITY-SENSOR-MIB-entSensorValue'][$index])) {
            print_debug("entSensor exists. Skipping.");
            continue;
        }

        // Clean descr:
        // SW#1, Sensor#1, GREEN
        $descr_replace = [
          ', GREEN'  => '', ' GREEN,' => ',',
          ', YELLOW' => '', ' YELLOW,' => ',',
          ', RED'    => '', ' RED,' => ',',
        ];
        $descr         = array_str_replace($descr_replace, $descr);

        $oid = '.1.3.6.1.4.1.9.9.13.1.3.1.3.' . $index;
        // Exclude duplicated entries from CISCO-ENTITY-SENSOR
        //$ent_exist = dbFetchCell('SELECT COUNT(*) FROM `sensors` WHERE `device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?) AND CONCAT(`sensor_limit`) = ?;',
        $ent_exist = dbExist('sensors', '`device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?) AND CONCAT(`sensor_limit`) = ?',
                             [$device['device_id'], 'CISCO-ENTITY-SENSOR-MIB-entSensorValue', 'temperature', $descr . '%', '%- ' . $descr, $entry['ciscoEnvMonTemperatureThreshold']]);
        if (!$ent_exist && $entry['ciscoEnvMonTemperatureStatusValue'] != 0) {
            $options               = [];
            $options['limit_high'] = $entry['ciscoEnvMonTemperatureThreshold'];
            $options['rename_rrd'] = 'cisco-envmon-' . $index;
            discover_sensor_ng($device, 'temperature', $mib, 'ciscoEnvMonTemperatureStatusValue', $oid, $index, NULL, $descr, 1, $entry['ciscoEnvMonTemperatureStatusValue'], $options);
        }
    } elseif (isset($entry['ciscoEnvMonTemperatureState'])) {
        $oid = '.1.3.6.1.4.1.9.9.13.1.3.1.6.' . $index;
        // Exclude duplicated entries from CISCO-ENTITY-SENSOR
        //$ent_exist = dbFetchCell('SELECT COUNT(*) FROM `sensors` WHERE `device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?);',
        $ent_exist = dbExist('sensors', '`device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?)',
                             [$device['device_id'], 'cisco-entity-state', 'state', $descr . '%', '%- ' . $descr]);
        // Not numerical values, only states
        if (!$ent_exist) {
            $options['rename_rrd'] = $sensor_state_type . '-temp-' . $index;
            discover_status_ng($device, $mib, 'ciscoEnvMonTemperatureState', $oid, $index, $sensor_state_type, $descr, $entry['ciscoEnvMonTemperatureState'], ['entPhysicalClass' => 'chassis']);
        }
    }
}

// Voltages
$scale = si_to_scale('milli');

$oids = snmpwalk_cache_oid($device, 'ciscoEnvMonVoltageStatusEntry', [], 'CISCO-ENVMON-MIB');

foreach ($oids as $index => $entry) {
    $descr = str_replace(' in mV', '', $entry['ciscoEnvMonVoltageStatusDescr']);
    if ($descr == '') {
        continue;
    } // Skip sensors with empty description, seems like Cisco bug

    if (isset($entry['ciscoEnvMonVoltageStatusValue'])) {
        $oid = '.1.3.6.1.4.1.9.9.13.1.2.1.3.' . $index;
        // Exclude duplicated entries from CISCO-ENTITY-SENSOR
        //$query = 'SELECT COUNT(*) FROM `sensors` WHERE `device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?) ';
        $where = '`device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?) ';
        $where .= ($entry['ciscoEnvMonVoltageThresholdHigh'] > $entry['ciscoEnvMonVoltageThresholdLow']) ? 'AND CONCAT(`sensor_limit`) = ? AND CONCAT(`sensor_limit_low`) = ?' : 'AND CONCAT(`sensor_limit_low`) = ? AND CONCAT(`sensor_limit`) = ?'; //swich negative numbers
        //$ent_exist = dbFetchCell($query, array($device['device_id'], 'CISCO-ENTITY-SENSOR-MIB-entSensorValue', 'voltage', $descr.'%', '%- '.$descr, $entry['ciscoEnvMonVoltageThresholdHigh'] * $scale, $entry['ciscoEnvMonVoltageThresholdLow'] * $scale));
        $ent_exist = dbExist('sensors', $where, [$device['device_id'], 'CISCO-ENTITY-SENSOR-MIB-entSensorValue', 'voltage', $descr . '%', '%- ' . $descr, $entry['ciscoEnvMonVoltageThresholdHigh'] * $scale, $entry['ciscoEnvMonVoltageThresholdLow'] * $scale]);
        if (!$ent_exist) {
            $options = ['limit_high' => $entry['ciscoEnvMonVoltageThresholdLow'] * $scale,
                        'limit_low'  => $entry['ciscoEnvMonVoltageThresholdHigh'] * $scale];


            $options['rename_rrd'] = 'cisco-envmon-' . $index;
            discover_sensor_ng($device, 'voltage', $mib, 'ciscoEnvMonVoltageStatusValue', $oid, $index, NULL, $descr, $scale, $entry['ciscoEnvMonVoltageStatusValue'], $options);
        }
    } elseif (isset($entry['ciscoEnvMonVoltageState'])) {
        $oid = '.1.3.6.1.4.1.9.9.13.1.2.1.7.' . $index;
        //$query = 'SELECT COUNT(*) FROM `status` WHERE `device_id` = ? AND `status_type` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?);';
        //$ent_exist = dbFetchCell($query, array($device['device_id'], 'cisco-entity-state', $descr.'%', '%- '.$descr));
        $where     = '`device_id` = ? AND `status_type` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?)';
        $ent_exist = dbExist('status', $where, [$device['device_id'], 'cisco-entity-state', $descr . '%', '%- ' . $descr]);
        if (!$ent_exist) {
            $options['rename_rrd'] = $sensor_state_type . '-voltage-' . $index;
            discover_status_ng($device, $mib, 'ciscoEnvMonVoltageState', $oid, $index, $sensor_state_type, $descr, $entry['ciscoEnvMonVoltageState'], ['entPhysicalClass' => 'chassis']);
        }
    }
}

// Supply
$oids = snmpwalk_cache_oid($device, 'ciscoEnvMonSupplyStatusEntry', [], 'CISCO-ENVMON-MIB');

foreach ($oids as $index => $entry) {
    $descr = $entry['ciscoEnvMonSupplyStatusDescr'];
    if ($descr == '') {
        continue;
    } // Skip sensors with empty description, seems like Cisco bug

    if (isset($entry['ciscoEnvMonSupplyState'])) {
        $oid = '.1.3.6.1.4.1.9.9.13.1.5.1.3.' . $index;
        // Exclude duplicated entries from CISCO-ENTITY-SENSOR
        //$ent_exist = dbFetchCell('SELECT COUNT(*) FROM `status` WHERE `device_id` = ? AND `status_type` = ? AND (`status_descr` LIKE ? OR `status_descr` LIKE ?);',
        $ent_exist = dbExist('status', '`device_id` = ? AND `status_type` = ? AND (`status_descr` LIKE ? OR `status_descr` LIKE ?)',
                             [$device['device_id'], 'cisco-entity-state', $descr . '%', '%- ' . $descr]);
        if (!$ent_exist) {
            // Clean descr:
            // Sw1, PS1 Normal, RPS NotExist
            // Switch 1 - Power Supply A, Normal
            $descr_replace = [
              ', ' . $entry['ciscoEnvMonSupplyState']      => '',
              ' ' . $entry['ciscoEnvMonSupplyState'] . ',' => ',',
              ', RPS NotExist'                             => ''
            ];
            $descr         = array_str_replace($descr_replace, $descr);
            if (in_array($entry['ciscoEnvMonSupplySource'], ['ac', 'dc'])) // Exclude poll Source for just AC/DC
            {
                $descr .= ' (' . strtoupper($entry['ciscoEnvMonSupplySource']) . ')';
            }

            $options['rename_rrd'] = $sensor_state_type . '-supply-' . $index;
            discover_status_ng($device, $mib, 'ciscoEnvMonSupplyState', $oid, $index, $sensor_state_type, $descr, $entry['ciscoEnvMonSupplyState'], ['entPhysicalClass' => 'powersupply']);

            $oid_name = 'ciscoEnvMonSupplySource';
            $oid_num  = '.1.3.6.1.4.1.9.9.13.1.5.1.4.' . $index;
            $type     = 'ciscoEnvMonSupplySource';
            $value    = $entry[$oid_name];

            if (!in_array($value, ['ac', 'dc'])) // Exclude poll Source for just AC/DC, keep Redundant
            {
                $options['rename_rrd'] = 'ciscoEnvMonSupplySource-ciscoEnvMonSupplySource.' . $index;
                discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr . ' Source', $value, ['entPhysicalClass' => 'powersupply']);
            }
        }
    }
}

// Fans
echo(" Fans ");

$oids = snmpwalk_cache_oid($device, 'ciscoEnvMonFanStatusEntry', [], 'CISCO-ENVMON-MIB');

foreach ($oids as $index => $entry) {
    $descr = $entry['ciscoEnvMonFanStatusDescr'];
    if ($descr == '') {
        continue;
    } // Skip sensors with empty description, seems like Cisco bug

    if (isset($entry['ciscoEnvMonFanState'])) {
        $oid = '.1.3.6.1.4.1.9.9.13.1.4.1.3.' . $index;
        // Exclude duplicated entries from CISCO-ENTITY-SENSOR
        //$ent_exist = dbFetchCell('SELECT COUNT(*) FROM `status` WHERE `device_id` = ? AND `status_type` = ? AND (`status_descr` LIKE ? OR `status_descr` LIKE ?);',
        $ent_exist = dbExist('status', '`device_id` = ? AND `status_type` = ? AND (`status_descr` LIKE ? OR `status_descr` LIKE ?)',
                             [$device['device_id'], 'cisco-entity-state', $descr . '%', '%- ' . $descr]);

        if (!$ent_exist) {
            // Clean descr:
            // Switch 1 - FAN - T1 3, Normal
            $descr_replace = [
              ', ' . $entry['ciscoEnvMonFanState']      => '',
              ' ' . $entry['ciscoEnvMonFanState'] . ',' => ','
            ];
            $descr         = array_str_replace($descr_replace, $descr);

            $options['rename_rrd'] = $sensor_state_type . '-fan-' . $index;
            discover_status_ng($device, $mib, 'ciscoEnvMonFanState', $oid, $index, $sensor_state_type, $descr, $entry['ciscoEnvMonFanState'], ['entPhysicalClass' => 'fan']);
        }
    }
}

// EOF
