<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) Adam Armstrong
 *
 */

// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalSlotNumSensors.11 = INTEGER: 5
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalSlotNumSensors.252 = INTEGER: 10
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalSlotNumSensors.253 = INTEGER: 10
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorName.11.1 = STRING: Board 1
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorName.11.2 = STRING: Board 2
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorName.252.1 = STRING: Board
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorName.252.2 = STRING: Board
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorName.253.1 = STRING: Board
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorName.253.2 = STRING: Board
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorCurrTemp.11.1 = INTEGER: 420 0.1C
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorCurrTemp.11.2 = INTEGER: 520 0.1C
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorCurrTemp.252.1 = INTEGER: 485 0.1C
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorCurrTemp.252.2 = INTEGER: 435 0.1C
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorCurrTemp.253.1 = INTEGER: 495 0.1C
// ADTRAN-TA5000-THERMAL-MGMT-MIB::adTA5kThermalManagementSensorCurrTemp.253.2 = INTEGER: 435 0.1C

$oids = snmpwalk_cache_oid($device, 'adTA5kThermalManagementTable', [], $mib);
print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    [ $slot, $num ] = explode('.', $index);

    $slot_name = snmp_cache_oid($device, 'adGenSlotProdName.'.$slot, 'ADTRAN-GENSLOT-MIB');

    $descr    = 'Slot ' . $slot . ', ' . $num . '. ' . $entry['adTA5kThermalManagementSensorName'] . ' (' . $slot_name . ')';
    $class    = 'temperature';
    $oid_name = 'adTA5kThermalManagementSensorCurrTemp';
    $oid_num  = '.1.3.6.1.4.1.664.5.67.1.39.1.1.2.1.3.' . $index;
    $scale    = 0.1;
    $value    = $entry[$oid_name];

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, $descr, $scale, $value);
}
// EOF
