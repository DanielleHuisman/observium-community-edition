<?php
/*
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$oids = snmpwalk_cache_oid($device, 'enclTable', [], 'NETAPP-MIB');

foreach ($oids as $index => $entry) {
    // NETAPP-MIB::enclNumber.0 = INTEGER: 1
    // NETAPP-MIB::enclIndex.1 = INTEGER: 1
    // NETAPP-MIB::enclContactState.1 = INTEGER: active(3)
    // NETAPP-MIB::enclChannelShelfAddr.1 = STRING: 0b.shelf0
    // NETAPP-MIB::enclProductLogicalID.1 = STRING: 5:00a:09800e:56ea77
    // NETAPP-MIB::enclProductID.1 = STRING: DS224-12
    // NETAPP-MIB::enclProductVendor.1 = STRING: NETAPP
    // NETAPP-MIB::enclProductModel.1 = STRING: DS224-12
    // NETAPP-MIB::enclProductRevision.1 = STRING: 0220
    // NETAPP-MIB::enclProductSerialNo.1 = STRING: SHFGB2051000413
    $enclosure = $entry['enclProductModel'];
    if ($entry['enclProductModel'] && !str_icontains_array($entry['enclProductVendor'], 'NETAPP')) {
        $enclosure = $entry['enclProductVendor'] . ' ' . $enclosure;
    }

    $descr = "Enclosure $index Contact ($enclosure)";

    $oid      = ".1.3.6.1.4.1.789.1.21.1.2.1.2.$index";
    $oid_name = 'enclContactState';
    $value    = $entry[$oid_name];

    discover_status_ng($device, $mib, $oid_name, $oid, $index, $oid_name, $descr, $value, ['entPhysicalClass' => 'enclosure']);

    // NETAPP-MIB::enclTempSensorsMaximum.1 = INTEGER: 15
    // NETAPP-MIB::enclTempSensorsPresent.1 = STRING: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15
    // NETAPP-MIB::enclTempSensorsOverTempFail.1 = STRING:
    // NETAPP-MIB::enclTempSensorsOverTempWarn.1 = STRING:
    // NETAPP-MIB::enclTempSensorsUnderTempFail.1 = STRING:
    // NETAPP-MIB::enclTempSensorsUnderTempWarn.1 = STRING:
    // NETAPP-MIB::enclTempSensorsCurrentTemp.1 = STRING: 19C (66F) ambient, 22C (71F), 22C (71F), 22C (71F), 22C (71F), 24C (75F), 41C (105F), 24C (75F), 42C (107F), 30C (86F), 41C (105F), 25C (77F), 31C (87F), 42C (107F), 26C (78F)
    // NETAPP-MIB::enclTempSensorsOverTempFailThr.1 = STRING: 52C (125F), 60C (140F), 60C (140F), 60C (140F), 60C (140F), 62C (143F), 100C (212F), 62C (143F), 100C (212F), 90C (194F), 105C (221F), 75C (167F), 90C (194F), 105C (221F), 75C (167F)
    // NETAPP-MIB::enclTempSensorsOverTempWarnThr.1 = STRING: 47C (116F), 47C (116F), 47C (116F), 47C (116F), 47C (116F), 57C (134F), 95C (203F), 57C (134F), 95C (203F), 80C (176F), 100C (212F), 60C (140F), 80C (176F), 100C (212F), 60C (140F)
    // NETAPP-MIB::enclTempSensorsUnderTempFailThr.1 = STRING: 0C (32F), 0C (32F), 0C (32F), 0C (32F), 0C (32F), 5C (41F), 5C (41F), 5C (41F), 5C (41F), 0C (32F), 0C (32F), 0C (32F), 0C (32F), 0C (32F), 0C (32F)
    // NETAPP-MIB::enclTempSensorsUnderTempWarnThr.1 = STRING: 5C (41F), 5C (41F), 5C (41F), 5C (41F), 5C (41F), 10C (50F), 10C (50F), 10C (50F), 10C (50F), 5C (41F), 5C (41F), 5C (41F), 5C (41F), 5C (41F), 5C (41F)

    $oid      = ".1.3.6.1.4.1.789.1.21.1.2.1.25.$index";
    $oid_name = 'enclTempSensorsCurrentTemp';
    $value    = $entry[$oid_name];

    $items  = explode(', ', $entry['enclTempSensorsPresent']);
    $values = explode(', ', $value);

    foreach ($items as $i => $item) {

        [, , $extra] = explode(' ', $values[$i]);
        if (safe_empty($extra)) {
            $descr = "Enclosure $index Sensor $item";
        } else {
            $descr = "Enclosure $index " . nicecase($extra);
        }
        $descr .= " ($enclosure)";

        $unit    = 'split' . ($i + 1);
        $options = [
          'entPhysicalIndex_measured' => $index,
          'entPhysicalIndex'          => $item,
          'sensor_unit'               => $unit,
          'limit_high'                => snmp_fix_numeric($entry['enclTempSensorsOverTempFailThr'], $unit),
          'limit_high_warn'           => snmp_fix_numeric($entry['enclTempSensorsOverTempWarnThr'], $unit),
          'limit_low'                 => snmp_fix_numeric($entry['enclTempSensorsUnderTempFailThr'], $unit),
          'limit_low_warn'            => snmp_fix_numeric($entry['enclTempSensorsUnderTempWarnThr'], $unit),
        ];

        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $value, $options);
    }

    // NETAPP-MIB::enclVoltSensorsMaximum.1 = INTEGER: 4
    // NETAPP-MIB::enclVoltSensorsPresent.1 = STRING: 1, 2, 3, 4
    // NETAPP-MIB::enclVoltSensorsOverVoltFail.1 = STRING:
    // NETAPP-MIB::enclVoltSensorsOverVoltWarn.1 = STRING:
    // NETAPP-MIB::enclVoltSensorsUnderVoltFail.1 = STRING:
    // NETAPP-MIB::enclVoltSensorsUnderVoltWarn.1 = STRING:
    // NETAPP-MIB::enclVoltSensorsCurrentVolt.1 = STRING: 507 mV, 1226 mV, 507 mV, 1226 mV
    // NETAPP-MIB::enclVoltSensorsOverVoltFailThr.1 = STRING:
    // NETAPP-MIB::enclVoltSensorsOverVoltWarnThr.1 = STRING:
    // NETAPP-MIB::enclVoltSensorsUnderVoltFailThr.1 = STRING:
    // NETAPP-MIB::enclVoltSensorsUnderVoltWarnThr.1 = STRING:

    $oid      = ".1.3.6.1.4.1.789.1.21.1.2.1.40.$index";
    $oid_name = 'enclVoltSensorsCurrentVolt';
    $value    = $entry[$oid_name];

    $items  = explode(', ', $entry['enclVoltSensorsPresent']);
    $values = explode(', ', $value);

    foreach ($items as $i => $item) {

        [, , $extra] = explode(' ', $values[$i]);
        if (safe_empty($extra)) {
            $descr = "Enclosure $index Sensor $item";
        } else {
            $descr = "Enclosure $index " . nicecase($extra);
        }
        $descr .= " ($enclosure)";

        $unit    = 'split' . ($i + 1);
        $options = [
          'entPhysicalIndex_measured' => $index,
          'entPhysicalIndex'          => $item,
          'sensor_unit'               => $unit,
          'limit_high'                => snmp_fix_numeric($entry['enclVoltSensorsOverVoltFailThr'], $unit),
          'limit_high_warn'           => snmp_fix_numeric($entry['enclVoltSensorsOverVoltWarnThr'], $unit),
          'limit_low'                 => snmp_fix_numeric($entry['enclVoltSensorsUnderVoltFailThr'], $unit),
          'limit_low_warn'            => snmp_fix_numeric($entry['enclVoltSensorsUnderVoltWarnThr'], $unit),
        ];

        discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid, $index, NULL, $descr, 0.001, $value, $options);
    }

    // NETAPP-MIB::enclCurSensorsMaximum.1 = INTEGER: 4
    // NETAPP-MIB::enclCurSensorsPresent.1 = STRING: 1, 2, 3, 4
    // NETAPP-MIB::enclCurSensorsOverCurFail.1 = STRING:
    // NETAPP-MIB::enclCurSensorsOverCurWarn.1 = STRING:
    // NETAPP-MIB::enclCurSensorsCurrentCur.1 = STRING: 6090 mA, 8710 mA, 6670 mA, 8280 mA
    // NETAPP-MIB::enclCurSensorsOverCurFailThr.1 = STRING:
    // NETAPP-MIB::enclCurSensorsOverCurWarnThr.1 = STRING:

    $oid      = ".1.3.6.1.4.1.789.1.21.1.2.1.49.$index";
    $oid_name = 'enclCurSensorsCurrentCur';
    $value    = $entry[$oid_name];

    $items  = explode(', ', $entry['enclCurSensorsPresent']);
    $values = explode(', ', $value);

    foreach ($items as $i => $item) {

        [, , $extra] = explode(' ', $values[$i]);
        if (safe_empty($extra)) {
            $descr = "Enclosure $index Sensor $item";
        } else {
            $descr = "Enclosure $index " . nicecase($extra);
        }
        $descr .= " ($enclosure)";

        $unit    = 'split' . ($i + 1);
        $options = [
          'entPhysicalIndex_measured' => $index,
          'entPhysicalIndex'          => $item,
          'sensor_unit'               => $unit,
          'limit_high'                => snmp_fix_numeric($entry['enclCurSensorsOverCurFailThr'], $unit),
          'limit_high_warn'           => snmp_fix_numeric($entry['enclCurSensorsOverCurWarnThr'], $unit),
        ];

        discover_sensor_ng($device, 'current', $mib, $oid_name, $oid, $index, NULL, $descr, 0.001, $value, $options);
    }

    // NETAPP-MIB::enclFansMaximum.1 = INTEGER: 4
    // NETAPP-MIB::enclFansPresent.1 = STRING: 1, 2, 3, 4
    // NETAPP-MIB::enclFansFailed.1 = STRING:
    // NETAPP-MIB::enclFansSpeed.1 = STRING: 2970 RPM, 3000 RPM, 3030 RPM, 3030 RPM

    $oid      = ".1.3.6.1.4.1.789.1.21.1.2.1.62.$index";
    $oid_name = 'enclFansSpeed';
    $value    = $entry[$oid_name];

    $items  = explode(', ', $entry['enclFansPresent']);
    $values = explode(', ', $value);

    foreach ($items as $i => $item) {

        [, , $extra] = explode(' ', $values[$i]);
        if (safe_empty($extra)) {
            $descr = "Enclosure $index Fan $item";
        } else {
            $descr = "Enclosure $index " . nicecase($extra);
        }
        $descr .= " ($enclosure)";

        $unit    = 'split' . ($i + 1);
        $options = [
          'entPhysicalIndex_measured' => $index,
          'entPhysicalIndex'          => $item,
          'sensor_unit'               => $unit,
        ];

        discover_sensor_ng($device, 'fanspeed', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $value, $options);
    }
}

// EOF
