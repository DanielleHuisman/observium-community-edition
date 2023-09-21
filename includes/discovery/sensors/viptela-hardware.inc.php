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
 *
 */

// VIPTELA-HARDWARE::hardwareEnvironmentStatus.temperatureSensors."DRAM".0 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.temperatureSensors."Board".0 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.temperatureSensors."Board".1 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.temperatureSensors."Board".2 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.temperatureSensors."Board".3 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.temperatureSensors."CPU junction".0 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.fans."Tray 0 fan".0 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.fans."Tray 0 fan".1 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.pEM."Power supply".0 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.pEM."Power supply".1 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.pIM."Interface module".0 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.uSB."External USB controller".0 = INTEGER: down(1)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.lED."Status LED".0 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentStatus.lED."System LED".0 = INTEGER: oK(0)
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.temperatureSensors."DRAM".0 = STRING: 35 degrees C/96 degrees F
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.temperatureSensors."Board".0 = STRING: 34 degrees C/93 degrees F
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.temperatureSensors."Board".1 = STRING: 37 degrees C/99 degrees F
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.temperatureSensors."Board".2 = STRING: 35 degrees C/95 degrees F
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.temperatureSensors."Board".3 = STRING: 36 degrees C/96 degrees F
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.temperatureSensors."CPU junction".0 = STRING: 52 degrees C/126 degrees F
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.fans."Tray 0 fan".0 = STRING: Spinning at 5160 RPM
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.fans."Tray 0 fan".1 = STRING: Spinning at 5040 RPM
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.pEM."Power supply".0 = STRING: Powered On: yes; Fault: no
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.pEM."Power supply".1 = STRING: Powered On: yes; Fault: no
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.pIM."Interface module".0 = STRING: Present: yes; Powered On: yes; Fault: no
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.uSB."External USB controller".0 = STRING: In reset
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.lED."Status LED".0 = STRING: Green
// VIPTELA-HARDWARE::hardwareEnvironmentMeasurement.lED."System LED".0 = STRING: Green
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsFanSpeedNormal.board.0 = Gauge32: 64
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsFanSpeedNormal.board.1 = Gauge32: 64
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsFanSpeedNormal.board.2 = Gauge32: 64
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsFanSpeedNormal.board.3 = Gauge32: 64
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsFanSpeedNormal.cPU-Junction.0 = Gauge32: 79
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsFanSpeedNormal.dRAM.0 = Gauge32: 64
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmNormal.board.0 = Gauge32: 65
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmNormal.board.1 = Gauge32: 65
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmNormal.board.2 = Gauge32: 65
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmNormal.board.3 = Gauge32: 65
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmNormal.cPU-Junction.0 = Gauge32: 80
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmNormal.dRAM.0 = Gauge32: 65
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmBadFan.board.0 = Gauge32: 60
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmBadFan.board.1 = Gauge32: 60
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmBadFan.board.2 = Gauge32: 60
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmBadFan.board.3 = Gauge32: 60
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmBadFan.cPU-Junction.0 = Gauge32: 75
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsYellowAlarmBadFan.dRAM.0 = Gauge32: 60
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmNormal.board.0 = Gauge32: 80
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmNormal.board.1 = Gauge32: 80
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmNormal.board.2 = Gauge32: 80
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmNormal.board.3 = Gauge32: 80
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmNormal.cPU-Junction.0 = Gauge32: 95
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmNormal.dRAM.0 = Gauge32: 80
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmBadFan.board.0 = Gauge32: 75
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmBadFan.board.1 = Gauge32: 75
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmBadFan.board.2 = Gauge32: 75
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmBadFan.board.3 = Gauge32: 75
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmBadFan.cPU-Junction.0 = Gauge32: 90
// VIPTELA-HARDWARE::hardwareTemperatureThresholdsRedAlarmBadFan.dRAM.0 = Gauge32: 75

$oids        = snmpwalk_cache_oid($device, "hardwareEnvironmentTable", [], "VIPTELA-HARDWARE");
$oids_limits = snmpwalk_cache_oid($device, "hardwareTemperatureThresholdsTable", [], "VIPTELA-HARDWARE");
print_debug_vars($oids);
print_debug_vars($oids_limits);

foreach ($oids as $named_index => $entry) {
    [$hw_class, $hw_item, $hw_index] = explode('.', $named_index, 3);

    // Convert to numeric index
    $index = '.' . snmp_string_to_oid($hw_item) . '.' . $hw_index;
    // SYNTAX INTEGER {temperatureSensors(0),fans(1),pEM(2),pIM(3),uSB(4),lED(5),nIM(6)}
    switch ($hw_class) {
        case 'temperatureSensors':
            $index = '0' . $index;

            $descr = $hw_item;
            if ($descr === 'Board') {
                $descr .= ' ' . $hw_index;
            }
            $oid_name = 'hardwareEnvironmentMeasurement';
            $oid_num  = '.1.3.6.1.4.1.41916.3.1.2.1.5.' . $index;
            $scale    = 1;
            $value    = $entry[$oid_name];

            // Detect limits
            $hw_index = strtolower(str_replace(' ', '-', $hw_item)) . '.' . $hw_index;
            $limits   = [];
            foreach ($oids_limits as $limit_index => $limit_entry) {
                $limit_index = strtolower($limit_index);

                if ($hw_index == $limit_index) {
                    $limits['limit_high']      = $limit_entry['hardwareTemperatureThresholdsRedAlarmNormal'];
                    $limits['limit_high_warn'] = $limit_entry['hardwareTemperatureThresholdsYellowAlarmNormal'];
                    break;
                }
            }

            discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $limits);
            break;

        case 'fans':
            $index = '1' . $index;

            $descr    = $hw_item . ' ' . $hw_index;
            $oid_name = 'hardwareEnvironmentMeasurement';
            $oid_num  = '.1.3.6.1.4.1.41916.3.1.2.1.5.' . $index;
            $scale    = 1;
            $value    = $entry[$oid_name];

            discover_sensor_ng($device, 'fanspeed', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);
            break;

        case 'pEM':
            $index = '2' . $index;

            $descr    = $hw_item . ' ' . $hw_index;
            $oid_name = 'hardwareEnvironmentStatus';
            $oid_num  = '.1.3.6.1.4.1.41916.3.1.2.1.4.' . $index;
            $type     = 'hardwareEnvironmentStatus';
            $value    = $entry[$oid_name];

            discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'powerSupply']);
            break;

        case 'pIM':
            $index = '3' . $index;

            $descr    = $hw_item;
            $oid_name = 'hardwareEnvironmentStatus';
            $oid_num  = '.1.3.6.1.4.1.41916.3.1.2.1.4.' . $index;
            $type     = 'hardwareEnvironmentStatus';
            $value    = $entry[$oid_name];

            discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
            break;

        case 'uSB':
            $index = '4' . $index;
            break;

        case 'lED':
            $index = '5' . $index;

            $descr    = $hw_item;
            $oid_name = 'hardwareEnvironmentStatus';
            $oid_num  = '.1.3.6.1.4.1.41916.3.1.2.1.4.' . $index;
            $type     = 'hardwareEnvironmentStatus';
            $value    = $entry[$oid_name];

            discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'device']);
            break;
        case 'nIM':
            $index = '6' . $index;
            break;
    }

}

unset($oids, $oids_limits, $index);

// EOF
