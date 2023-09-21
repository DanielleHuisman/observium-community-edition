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

// RAD-GEN-MIB::physicalConnector.1 = INTEGER: sfpIn(64)
// RAD-GEN-MIB::physicalConnector.2 = INTEGER: sfpIn(64)
// RAD-GEN-MIB::portOptWaveLength.1 = INTEGER: notApplicable(1)
// RAD-GEN-MIB::portOptWaveLength.2 = INTEGER: nm850(2)
// RAD-GEN-MIB::portOptMode.1 = INTEGER: notApplicable(1)
// RAD-GEN-MIB::portOptMode.2 = INTEGER: multiMode(3)
// RAD-GEN-MIB::portBalance.1 = INTEGER: notApplicable(1)
// RAD-GEN-MIB::portBalance.2 = INTEGER: notApplicable(1)
// RAD-GEN-MIB::portDdmSupport.1 = INTEGER: no(2)
// RAD-GEN-MIB::portDdmSupport.2 = INTEGER: yes(3)
// RAD-GEN-MIB::portMfgName.1 = STRING: RAD Data Comm.
// RAD-GEN-MIB::portMfgName.2 = STRING: UPI
// RAD-GEN-MIB::portTypicalMaxRange.1 = Gauge32: 100
// RAD-GEN-MIB::portTypicalMaxRange.2 = Gauge32: 550
// RAD-GEN-MIB::physicalConnectorString.1 = STRING: RJ-45
// RAD-GEN-MIB::physicalConnectorString.2 = STRING: LC
// RAD-GEN-MIB::portVendorPartNo.1 = STRING: SFP-30
// RAD-GEN-MIB::portVendorPartNo.2 = STRING: EX-SFP-1GE-SX-U
// RAD-GEN-MIB::physicalConnectorSfpWaveLength.1 = Gauge32: 0 hundredths of nm
// RAD-GEN-MIB::physicalConnectorSfpWaveLength.2 = Gauge32: 85000 hundredths of nm

// RAD-GEN-MIB::optPrtMonitorTxPower.1.actual = INTEGER: 0
// RAD-GEN-MIB::optPrtMonitorTxPower.1.minimum = INTEGER: 2147483647
// RAD-GEN-MIB::optPrtMonitorTxPower.1.maximum = INTEGER: -2147483648
// RAD-GEN-MIB::optPrtMonitorTxPower.2.actual = INTEGER: -630
// RAD-GEN-MIB::optPrtMonitorTxPower.2.minimum = INTEGER: -650
// RAD-GEN-MIB::optPrtMonitorTxPower.2.maximum = INTEGER: -621

// RAD-GEN-MIB::optPrtMonitorLaserBias.1.actual = INTEGER: 0
// RAD-GEN-MIB::optPrtMonitorLaserBias.1.minimum = INTEGER: 2147483647
// RAD-GEN-MIB::optPrtMonitorLaserBias.1.maximum = INTEGER: -2147483648
// RAD-GEN-MIB::optPrtMonitorLaserBias.2.actual = INTEGER: 1196
// RAD-GEN-MIB::optPrtMonitorLaserBias.2.minimum = INTEGER: 1146
// RAD-GEN-MIB::optPrtMonitorLaserBias.2.maximum = INTEGER: 1243

// RAD-GEN-MIB::optPrtMonitorLaserTemp.1.actual = INTEGER: 0
// RAD-GEN-MIB::optPrtMonitorLaserTemp.1.minimum = INTEGER: 2147483647
// RAD-GEN-MIB::optPrtMonitorLaserTemp.1.maximum = INTEGER: -2147483648
// RAD-GEN-MIB::optPrtMonitorLaserTemp.2.actual = INTEGER: 6100
// RAD-GEN-MIB::optPrtMonitorLaserTemp.2.minimum = INTEGER: 6000
// RAD-GEN-MIB::optPrtMonitorLaserTemp.2.maximum = INTEGER: 6600

// RAD-GEN-MIB::optPrtMonitorRxPower.1.actual = INTEGER: 0
// RAD-GEN-MIB::optPrtMonitorRxPower.1.minimum = INTEGER: 2147483647
// RAD-GEN-MIB::optPrtMonitorRxPower.1.maximum = INTEGER: -2147483648
// RAD-GEN-MIB::optPrtMonitorRxPower.2.actual = INTEGER: -537
// RAD-GEN-MIB::optPrtMonitorRxPower.2.minimum = INTEGER: -4000
// RAD-GEN-MIB::optPrtMonitorRxPower.2.maximum = INTEGER: -519

// RAD-GEN-MIB::optPrtMonitorSupplyVoltage.1.actual = INTEGER: 0
// RAD-GEN-MIB::optPrtMonitorSupplyVoltage.1.minimum = INTEGER: 2147483647
// RAD-GEN-MIB::optPrtMonitorSupplyVoltage.1.maximum = INTEGER: -2147483648
// RAD-GEN-MIB::optPrtMonitorSupplyVoltage.2.actual = INTEGER: 321
// RAD-GEN-MIB::optPrtMonitorSupplyVoltage.2.minimum = INTEGER: 319
// RAD-GEN-MIB::optPrtMonitorSupplyVoltage.2.maximum = INTEGER: 322

$physicalConnectorEntry = snmpwalk_multipart_oid($device, 'physicalConnectorEntry', [], 'RAD-GEN-MIB');
print_debug_vars($physicalConnectorEntry);
if (!snmp_status()) {
    return;
}

$oids = snmpwalk_multipart_oid($device, 'optPrtMonitorEntry', [], 'RAD-GEN-MIB');
print_debug_vars($oids);

foreach ($physicalConnectorEntry as $ifIndex => $entry) {
    if ($entry['portDdmSupport'] != 'yes' || !isset($oids[$ifIndex]['actual'])) {
        continue;
    }

    $index            = $ifIndex . '.1';
    $entry['ifIndex'] = $ifIndex;
    $entry['index']   = $index;
    if (isset($oids[$ifIndex]['actual'])) {
        $entry = array_merge($entry, $oids[$ifIndex]['actual']);
    }
    print_debug_vars($entry);

    $match   = ['measured_match' => ['entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%ifIndex%']];
    $options = entity_measured_match_definition($device, $match, $entry);
    //print_debug_vars($options);

    $name     = $options['port_label'];
    $name_ext = " ({$entry['portMfgName']} {$entry['portVendorPartNo']} {$entry['physicalConnectorString']})";

    // Temperature
    $descr    = $name . ' Temperature' . $name_ext;
    $class    = 'temperature';
    $oid_name = 'optPrtMonitorLaserTemp';
    $oid_num  = '.1.3.6.1.4.1.164.6.2.15.8.1.1.5.' . $index;
    $scale    = 0.01;
    $value    = $entry[$oid_name];

    // Limits (actually this is min/max)
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

    // Tx Bias
    $descr    = $name . ' Tx Bias' . $name_ext;
    $class    = 'current';
    $oid_name = 'optPrtMonitorLaserBias';
    $oid_num  = '.1.3.6.1.4.1.164.6.2.15.8.1.1.4.' . $index;
    $scale    = 0.000001;
    $value    = $entry[$oid_name];

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

    // Tx Power
    $descr    = $name . ' Tx Power' . $name_ext;
    $class    = 'dbm';
    $oid_name = 'optPrtMonitorTxPower';
    $oid_num  = '.1.3.6.1.4.1.164.6.2.15.8.1.1.3.' . $index;
    $scale    = 0.01;
    $value    = $entry[$oid_name];

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

    // Rx Power
    $descr    = $name . ' Rx Power' . $name_ext;
    $class    = 'dbm';
    $oid_name = 'optPrtMonitorRxPower';
    $oid_num  = '.1.3.6.1.4.1.164.6.2.15.8.1.1.6.' . $index;
    $scale    = 0.01;
    $value    = $entry[$oid_name];

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

    // Voltage
    $descr    = $name . ' Voltage' . $name_ext;
    $class    = 'voltage';
    $oid_name = 'optPrtMonitorSupplyVoltage';
    $oid_num  = '.1.3.6.1.4.1.164.6.2.15.8.1.1.7.' . $index;
    $scale    = 0.01;
    $value    = $entry[$oid_name];

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

}

// EOF
