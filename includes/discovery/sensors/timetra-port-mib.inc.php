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

// This MIB use not trivial scales, that why not possible use definitions.

// Multi Lane DDM

// TIMETRA-PORT-MIB::tmnxDDMLaneTemperature.1.102793216.2 = INTEGER: 12756
// TIMETRA-PORT-MIB::tmnxDDMLaneTempLowWarn.1.102793216.2 = INTEGER: 10240
// TIMETRA-PORT-MIB::tmnxDDMLaneTempLowAlarm.1.102793216.2 = INTEGER: 8960
// TIMETRA-PORT-MIB::tmnxDDMLaneTempHiWarn.1.102793216.2 = INTEGER: 15872
// TIMETRA-PORT-MIB::tmnxDDMLaneTempHiAlarm.1.102793216.2 = INTEGER: 17152

// TIMETRA-PORT-MIB::tmnxDDMLaneTxBiasCurrent.1.102793216.2 = INTEGER: 27033
// TIMETRA-PORT-MIB::tmnxDDMLaneTxBiasCurrentLowWarn.1.102793216.2 = INTEGER: 10000
// TIMETRA-PORT-MIB::tmnxDDMLaneTxBiasCurrentLowAlarm.1.102793216.2 = INTEGER: 7500
// TIMETRA-PORT-MIB::tmnxDDMLaneTxBiasCurrentHiWarn.1.102793216.2 = INTEGER: 45000
// TIMETRA-PORT-MIB::tmnxDDMLaneTxBiasCurrentHiAlarm.1.102793216.2 = INTEGER: 50000

// TIMETRA-PORT-MIB::tmnxDDMLaneTxOutputPower.1.102793216.2 = INTEGER: 11419
// TIMETRA-PORT-MIB::tmnxDDMLaneTxOutputPowerLowWarn.1.102793216.2 = INTEGER: 3311
// TIMETRA-PORT-MIB::tmnxDDMLaneTxOutputPowerLowAlarm.1.102793216.2 = INTEGER: 2951
// TIMETRA-PORT-MIB::tmnxDDMLaneTxOutputPowerHiWarn.1.102793216.2 = INTEGER: 31622
// TIMETRA-PORT-MIB::tmnxDDMLaneTxOutputPowerHiAlarm.1.102793216.2 = INTEGER: 35481

// TIMETRA-PORT-MIB::tmnxDDMLaneRxOpticalPower.1.102793216.2 = INTEGER: 10691
// TIMETRA-PORT-MIB::tmnxDDMLaneRxOpticalPwrLowWarn.1.102793216.2 = INTEGER: 616
// TIMETRA-PORT-MIB::tmnxDDMLaneRxOpticalPwrLowAlarm.1.102793216.2 = INTEGER: 549
// TIMETRA-PORT-MIB::tmnxDDMLaneRxOpticalPwrHiWarn.1.102793216.2 = INTEGER: 31622
// TIMETRA-PORT-MIB::tmnxDDMLaneRxOpticalPwrHiAlarm.1.102793216.2 = INTEGER: 35481

// TIMETRA-PORT-MIB::tmnxDDMLaneRxOpticalPowerType.1.102793216.2 = INTEGER: average(1)
// TIMETRA-PORT-MIB::tmnxDDMLaneFailedThresholds.1.102793216.2 = BITS: 00 00 00 00

$oids = snmpwalk_cache_oid($device, 'tmnxDDMLaneTable', [], 'TIMETRA-PORT-MIB');
print_debug_vars($oids);

$multilane = [];
foreach ($oids as $index => $entry) {
    [$chassis, $ifIndex, $lane] = explode('.', $index);

    if ($chassis > 1) {
        continue;
    }

    $entry['ifIndex'] = $ifIndex;
    $entry['index']   = $index;
    $match            = ['measured_match' => ['entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%ifIndex%']];
    $options          = entity_measured_match_definition($device, $match, $entry);
    //print_debug_vars($options);

    $name = $options['port_label'] . ' Lane ' . $lane;

    // Temperature
    $descr    = $name . ' Temperature';
    $class    = 'temperature';
    $oid_name = 'tmnxDDMLaneTemperature';
    $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.4.66.1.2.' . $index;
    $value    = $entry[$oid_name];
    $ok       = $value != 0;

    $sensor_options = $options;
    // Scale
    //        "The value of tmnxDDMLaneTemperature indicates the current temperature
    //          of the multi-lane optic in 1/256th degrees Celsius.
    //
    //          The formula for translating between the value of tmnxDDMLaneTemperature
    //          and degrees Celsius is:
    //                 tmnxDDMLaneTemperature / 256
    //
    //          For example: The SNMP value 5734 is 22.4 degrees Celsius."
    $scale = 1 / 256;

    // Limits
    $sensor_options['limit_high']      = $entry['tmnxDDMLaneTempHiAlarm'] * $scale;
    $sensor_options['limit_high_warn'] = $entry['tmnxDDMLaneTempHiWarn'] * $scale;
    $sensor_options['limit_low']       = $entry['tmnxDDMLaneTempLowAlarm'] * $scale;
    $sensor_options['limit_low_warn']  = $entry['tmnxDDMLaneTempLowWarn'] * $scale;

    if ($ok) {
        $multilane[$chassis][$ifIndex][$class] = 1;

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $sensor_options);
    }

    // Tx Bias
    $descr    = $name . ' Tx Bias';
    $class    = 'current';
    $oid_name = 'tmnxDDMLaneTxBiasCurrent';
    $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.4.66.1.7.' . $index;
    $value    = $entry[$oid_name];
    $ok       = $ok || ($value != 0); // Override ok, because multiline temperature can be 0

    $sensor_options = $options;
    // Scale
    //        "The value of tmnxDDMLaneTxBiasCurrent indicates the current Transmit
    //          Bias Current of the multi-lane optic in 1/500 milliamperes (mA).
    //
    //          The formula for translating between the value of
    //          tmnxDDMLaneTxBiasCurrent and amperes is:
    //                 tmnxDDMLaneTxBiasCurrent / 500
    //
    //          For example: The SNMP value 2565 is 5.1 milliamperes (mA)."
    $scale = 1 / 500000;   // 500 * 1000

    // Limits
    $sensor_options['limit_high']      = $entry['tmnxDDMLaneTxBiasCurrentHiAlarm'] * $scale;
    $sensor_options['limit_high_warn'] = $entry['tmnxDDMLaneTxBiasCurrentHiWarn'] * $scale;
    $sensor_options['limit_low']       = $entry['tmnxDDMLaneTxBiasCurrentLowAlarm'] * $scale;
    $sensor_options['limit_low_warn']  = $entry['tmnxDDMLaneTxBiasCurrentLowWarn'] * $scale;

    if ($ok) {
        $multilane[$chassis][$ifIndex][$class] = 1;

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $sensor_options);
    }

    // Tx Power
    $descr    = $name . ' Tx Power';
    $class    = 'power';
    $oid_name = 'tmnxDDMLaneTxOutputPower';
    $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.4.66.1.12.' . $index;
    $value    = $entry[$oid_name];

    $sensor_options = $options;
    // Scale
    //        "The value of tmnxDDMLaneTxOutputPower indicates the current Output
    //          Power of the multi-lane optic in one tenths of a microwatt (uW).
    //
    //          For example:
    //          Using the SNMP value of 790, and using units of tenths of microwatt,
    //          790 becomes 79 microwatts or 0.079 milliwatts. Converting to dBm:
    //                10 x log10(0.079) = -11.0 dBm"
    $scale = 1 / 1000000;  // 10 * 1000 * 1000
    // Limits
    $sensor_options['limit_high']      = $entry['tmnxDDMLaneTxOutputPowerHiAlarm'] * $scale;
    $sensor_options['limit_high_warn'] = $entry['tmnxDDMLaneTxOutputPowerHiWarn'] * $scale;
    $sensor_options['limit_low']       = $entry['tmnxDDMLaneTxOutputPowerLowAlarm'] * $scale;
    $sensor_options['limit_low_warn']  = $entry['tmnxDDMLaneTxOutputPowerLowWarn'] * $scale;

    if ($ok) {
        $multilane[$chassis][$ifIndex][$class] = 1;

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $sensor_options);
    }

    // Rx Power
    $descr    = $name . ' Rx Power';
    $class    = 'power';
    $oid_name = 'tmnxDDMLaneRxOpticalPower';
    $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.4.66.1.17.' . $index;
    $value    = $entry[$oid_name];

    $sensor_options = $options;
    // Scale
    //        "The value of tmnxDDMLaneRxOpticalPower indicates the current Received
    //          Optical Power of the multi-lane optic in one tenths of a microwatt
    //          (uW).
    //
    //          For example:
    //          Using the SNMP value of 790, and using units of tenths of microwatt,
    //          790 becomes 79 microwatts or 0.079 milliwatts. Converting to dBm:
    //                10 x log10(0.079) = -11.0 dBm"
    $scale = 1 / 10000000; // 10 * 1000 * 1000
    // Limits
    $sensor_options['limit_high']      = $entry['tmnxDDMLaneRxOpticalPwrHiAlarm'] * $scale;
    $sensor_options['limit_high_warn'] = $entry['tmnxDDMLaneRxOpticalPwrHiWarn'] * $scale;
    $sensor_options['limit_low']       = $entry['tmnxDDMLaneRxOpticalPwrLowAlarm'] * $scale;
    $sensor_options['limit_low_warn']  = $entry['tmnxDDMLaneRxOpticalPwrLowWarn'] * $scale;

    if ($ok) {
        $multilane[$chassis][$ifIndex][$class] = 1;

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $sensor_options);
    }

    //$multilane[$chassis][$ifIndex] = 1;
}

/*
TIMETRA-PORT-MIB::tmnxDDMTemperature.1.69369856 = INTEGER: 8958
TIMETRA-PORT-MIB::tmnxDDMTempLowWarning.1.69369856 = INTEGER: -3328
TIMETRA-PORT-MIB::tmnxDDMTempLowAlarm.1.69369856 = INTEGER: -7424
TIMETRA-PORT-MIB::tmnxDDMTempHiWarning.1.69369856 = INTEGER: 26368
TIMETRA-PORT-MIB::tmnxDDMTempHiAlarm.1.69369856 = INTEGER: 27904
TIMETRA-PORT-MIB::tmnxDDMExtCalTemperatureSlope.1.69369856 = Gauge32: 256
TIMETRA-PORT-MIB::tmnxDDMExtCalTemperatureOffset.1.69369856 = INTEGER: 0

TIMETRA-PORT-MIB::tmnxDDMSupplyVoltage.1.69369856 = INTEGER: 32944
TIMETRA-PORT-MIB::tmnxDDMSupplyVoltageLowWarning.1.69369856 = INTEGER: 29000
TIMETRA-PORT-MIB::tmnxDDMSupplyVoltageLowAlarm.1.69369856 = INTEGER: 27000
TIMETRA-PORT-MIB::tmnxDDMSupplyVoltageHiWarning.1.69369856 = INTEGER: 37000
TIMETRA-PORT-MIB::tmnxDDMSupplyVoltageHiAlarm.1.69369856 = INTEGER: 39000
TIMETRA-PORT-MIB::tmnxDDMExtCalVoltageSlope.1.69369856 = Gauge32: 256
TIMETRA-PORT-MIB::tmnxDDMExtCalVoltageOffset.1.69369856 = INTEGER: 0

TIMETRA-PORT-MIB::tmnxDDMTxBiasCurrent.1.69369856 = INTEGER: 2268
TIMETRA-PORT-MIB::tmnxDDMTxBiasCurrentLowWarning.1.69369856 = INTEGER: 1000
TIMETRA-PORT-MIB::tmnxDDMTxBiasCurrentLowAlarm.1.69369856 = INTEGER: 500
TIMETRA-PORT-MIB::tmnxDDMTxBiasCurrentHiWarning.1.69369856 = INTEGER: 6000
TIMETRA-PORT-MIB::tmnxDDMTxBiasCurrentHiAlarm.1.69369856 = INTEGER: 7500
TIMETRA-PORT-MIB::tmnxDDMExtCalTxLaserBiasSlope.1.69369856 = Gauge32: 256
TIMETRA-PORT-MIB::tmnxDDMExtCalTxLaserBiasOffset.1.69369856 = INTEGER: 0

TIMETRA-PORT-MIB::tmnxDDMTxOutputPower.1.69369856 = INTEGER: 3196
TIMETRA-PORT-MIB::tmnxDDMTxOutputPowerLowWarning.1.69369856 = INTEGER: 860
TIMETRA-PORT-MIB::tmnxDDMTxOutputPowerLowAlarm.1.69369856 = INTEGER: 545
TIMETRA-PORT-MIB::tmnxDDMTxOutputPowerHiWarning.1.69369856 = INTEGER: 6872
TIMETRA-PORT-MIB::tmnxDDMTxOutputPowerHiAlarm.1.69369856 = INTEGER: 6872
TIMETRA-PORT-MIB::tmnxDDMExtCalTxPowerSlope.1.69369856 = Gauge32: 235
TIMETRA-PORT-MIB::tmnxDDMExtCalTxPowerOffset.1.69369856 = INTEGER: 0

TIMETRA-PORT-MIB::tmnxDDMRxOpticalPower.1.69369856 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMRxOpticalPowerLowWarning.1.69369856 = INTEGER: 525
TIMETRA-PORT-MIB::tmnxDDMRxOpticalPowerLowAlarm.1.69369856 = INTEGER: 298
TIMETRA-PORT-MIB::tmnxDDMRxOpticalPowerHiWarning.1.69369856 = INTEGER: 30953
TIMETRA-PORT-MIB::tmnxDDMRxOpticalPowerHiAlarm.1.69369856 = INTEGER: 49136
TIMETRA-PORT-MIB::tmnxDDMExtCalRxPower4.1.69369856 = Gauge32: 0
TIMETRA-PORT-MIB::tmnxDDMExtCalRxPower3.1.69369856 = Gauge32: 0
TIMETRA-PORT-MIB::tmnxDDMExtCalRxPower2.1.69369856 = Gauge32: 0
TIMETRA-PORT-MIB::tmnxDDMExtCalRxPower1.1.69369856 = Gauge32: 1048768803
TIMETRA-PORT-MIB::tmnxDDMExtCalRxPower0.1.69369856 = Gauge32: 1103015442

TIMETRA-PORT-MIB::tmnxDDMRxOpticalPowerType.1.69369856 = INTEGER: average(1)
TIMETRA-PORT-MIB::tmnxDDMAux1.1.69369856 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux1LowWarning.1.69369856 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux1LowAlarm.1.69369856 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux1HiWarning.1.69369856 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux1HiAlarm.1.69369856 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux1Type.1.69369856 = INTEGER: none(0)
TIMETRA-PORT-MIB::tmnxDDMAux2.1.69369856 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux2LowWarning.1.69369856 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux2LowAlarm.1.69369856 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux2HiWarning.1.69369856 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux2HiAlarm.1.69369856 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux2Type.1.69369856 = INTEGER: none(0)
TIMETRA-PORT-MIB::tmnxDDMFailedThresholds.1.69369856 = BITS: 00 00 60 00 rxOpticalPower-low-warning(17) rxOpticalPower-low-alarm(18)
TIMETRA-PORT-MIB::tmnxDDMExternallyCalibrated.1.69369856 = INTEGER: true(1)
*/
/*
TIMETRA-PORT-MIB::tmnxDDMTemperature.1.69435392 = INTEGER: 5505
TIMETRA-PORT-MIB::tmnxDDMTempLowWarning.1.69435392 = INTEGER: -5120
TIMETRA-PORT-MIB::tmnxDDMTempLowAlarm.1.69435392 = INTEGER: -6400
TIMETRA-PORT-MIB::tmnxDDMTempHiWarning.1.69435392 = INTEGER: 23040
TIMETRA-PORT-MIB::tmnxDDMTempHiAlarm.1.69435392 = INTEGER: 24320
TIMETRA-PORT-MIB::tmnxDDMExtCalTemperatureSlope.1.69435392 = Gauge32: 256
TIMETRA-PORT-MIB::tmnxDDMExtCalTemperatureOffset.1.69435392 = INTEGER: 0

TIMETRA-PORT-MIB::tmnxDDMSupplyVoltage.1.69435392 = INTEGER: 32950
TIMETRA-PORT-MIB::tmnxDDMSupplyVoltageLowWarning.1.69435392 = INTEGER: 29000
TIMETRA-PORT-MIB::tmnxDDMSupplyVoltageLowAlarm.1.69435392 = INTEGER: 27000
TIMETRA-PORT-MIB::tmnxDDMSupplyVoltageHiWarning.1.69435392 = INTEGER: 37000
TIMETRA-PORT-MIB::tmnxDDMSupplyVoltageHiAlarm.1.69435392 = INTEGER: 39000
TIMETRA-PORT-MIB::tmnxDDMExtCalVoltageSlope.1.69435392 = Gauge32: 256
TIMETRA-PORT-MIB::tmnxDDMExtCalVoltageOffset.1.69435392 = INTEGER: 0

TIMETRA-PORT-MIB::tmnxDDMTxBiasCurrent.1.69435392 = INTEGER: 4200
TIMETRA-PORT-MIB::tmnxDDMTxBiasCurrentLowWarning.1.69435392 = INTEGER: 1000
TIMETRA-PORT-MIB::tmnxDDMTxBiasCurrentLowAlarm.1.69435392 = INTEGER: 500
TIMETRA-PORT-MIB::tmnxDDMTxBiasCurrentHiWarning.1.69435392 = INTEGER: 7000
TIMETRA-PORT-MIB::tmnxDDMTxBiasCurrentHiAlarm.1.69435392 = INTEGER: 8500
TIMETRA-PORT-MIB::tmnxDDMExtCalTxLaserBiasSlope.1.69435392 = Gauge32: 256
TIMETRA-PORT-MIB::tmnxDDMExtCalTxLaserBiasOffset.1.69435392 = INTEGER: 0

TIMETRA-PORT-MIB::tmnxDDMTxOutputPower.1.69435392 = INTEGER: 3618
TIMETRA-PORT-MIB::tmnxDDMTxOutputPowerLowWarning.1.69435392 = INTEGER: 790
TIMETRA-PORT-MIB::tmnxDDMTxOutputPowerLowAlarm.1.69435392 = INTEGER: 670
TIMETRA-PORT-MIB::tmnxDDMTxOutputPowerHiWarning.1.69435392 = INTEGER: 6310
TIMETRA-PORT-MIB::tmnxDDMTxOutputPowerHiAlarm.1.69435392 = INTEGER: 6310
TIMETRA-PORT-MIB::tmnxDDMExtCalTxPowerSlope.1.69435392 = Gauge32: 256
TIMETRA-PORT-MIB::tmnxDDMExtCalTxPowerOffset.1.69435392 = INTEGER: 0

TIMETRA-PORT-MIB::tmnxDDMRxOpticalPower.1.69435392 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMRxOpticalPowerLowWarning.1.69435392 = INTEGER: 158
TIMETRA-PORT-MIB::tmnxDDMRxOpticalPowerLowAlarm.1.69435392 = INTEGER: 100
TIMETRA-PORT-MIB::tmnxDDMRxOpticalPowerHiWarning.1.69435392 = INTEGER: 7940
TIMETRA-PORT-MIB::tmnxDDMRxOpticalPowerHiAlarm.1.69435392 = INTEGER: 12590
TIMETRA-PORT-MIB::tmnxDDMExtCalRxPower4.1.69435392 = Gauge32: 0
TIMETRA-PORT-MIB::tmnxDDMExtCalRxPower3.1.69435392 = Gauge32: 0
TIMETRA-PORT-MIB::tmnxDDMExtCalRxPower2.1.69435392 = Gauge32: 0
TIMETRA-PORT-MIB::tmnxDDMExtCalRxPower1.1.69435392 = Gauge32: 1065353216
TIMETRA-PORT-MIB::tmnxDDMExtCalRxPower0.1.69435392 = Gauge32: 0

TIMETRA-PORT-MIB::tmnxDDMRxOpticalPowerType.1.69435392 = INTEGER: average(1)
TIMETRA-PORT-MIB::tmnxDDMAux1.1.69435392 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux1LowWarning.1.69435392 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux1LowAlarm.1.69435392 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux1HiWarning.1.69435392 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux1HiAlarm.1.69435392 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux1Type.1.69435392 = INTEGER: none(0)
TIMETRA-PORT-MIB::tmnxDDMAux2.1.69435392 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux2LowWarning.1.69435392 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux2LowAlarm.1.69435392 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux2HiWarning.1.69435392 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux2HiAlarm.1.69435392 = INTEGER: 0
TIMETRA-PORT-MIB::tmnxDDMAux2Type.1.69435392 = INTEGER: none(0)
TIMETRA-PORT-MIB::tmnxDDMFailedThresholds.1.69435392 = BITS: 00 00 60 00 rxOpticalPower-low-warning(17) rxOpticalPower-low-alarm(18)
TIMETRA-PORT-MIB::tmnxDDMExternallyCalibrated.1.69435392 = INTEGER: false(2)
 */

// TIMETRA-PORT-MIB::tmnxPortDescription.1.69435392 = STRING: "10/100/Gig Ethernet SFP"
// TIMETRA-PORT-MIB::tmnxPortName.1.69435392 = STRING: "2/1/7"
// TIMETRA-PORT-MIB::tmnxPortAlias.1.69435392 = ""
// TIMETRA-PORT-MIB::tmnxPortTransceiverType.1.69435392 = INTEGER: sfpTransceiver(3)
// TIMETRA-PORT-MIB::tmnxPortTransceiverLaserWaveLen.1.69435392 = Gauge32: 850
// TIMETRA-PORT-MIB::tmnxPortTransceiverModelNumber.1.69435392 = STRING: "3HE00027AAAA02  ALA  IPUIAELDAB"
// TIMETRA-PORT-MIB::tmnxPortSFPConnectorCode.1.69435392 = INTEGER: lc(7)
// TIMETRA-PORT-MIB::tmnxPortSFPVendorOUI.1.69435392 = Gauge32: 36965
// TIMETRA-PORT-MIB::tmnxPortSFPVendorSerialNum.1.69435392 = STRING: "PG93Q3X         "
// TIMETRA-PORT-MIB::tmnxPortSFPVendorPartNum.1.69435392 = STRING: "FTRJ8519P2BNL-A5"

$oids = snmpwalk_multipart_oid($device, 'tmnxDigitalDiagMonitorTable', [], 'TIMETRA-PORT-MIB');
if (snmp_status()) {
    $oids = snmpwalk_multipart_oid($device, 'tmnxPortTransceiverType', $oids, 'TIMETRA-PORT-MIB');
}
print_debug_vars($oids);

foreach ($oids as $chassis => $transeiver) {
    if ($chassis > 1) {
        continue;
    }

    foreach ($transeiver as $ifIndex => $entry) {
        $index = $chassis . '.' . $ifIndex;

        $entry['ifIndex'] = $ifIndex;
        $entry['index']   = $index;
        $match            = [ 'measured_match' => [ 'entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%ifIndex%' ] ];
        $options          = entity_measured_match_definition($device, $match, $entry);
        //print_debug_vars($options);

        $name = $options['port_label'];

        // Temperature
        $descr    = $name . ' Temperature';
        $class    = 'temperature';
        $oid_name = 'tmnxDDMTemperature';
        $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.4.31.1.1.' . $index;
        $value    = $entry[$oid_name];
        $ok       = $value != 0;

        $sensor_options = $options;
        // Scale
        //         "The value of tmnxDDMTemperature indicates the current temperature of
        //          the SFF in 1/256th degrees Celsius.
        //
        //          If the SFF is externally calibrated, the objects
        //          tmnxDDMExtCalTemperatureSlope and tmnxDDMExtCalTemperatureOffset
        //          affect the temperature calculation.
        //
        //          The formula for translating between the value of tmnxDDMTemperature and
        //          degrees Celsius is:
        //              Internally Calibrated only:
        //                 tmnxDDMTemperature / 256
        //              Externally Calibrated:
        //                 (tmnxDDMTemperature * (tmnxDDMExtCalTemperatureSlope / 256)
        //                       + tmnxDDMExtCalTemperatureOffset) / 256
        //
        //          For example (internally calibrated SFF): The SNMP value 5734 is 22.4
        //          degrees Celsius."
        $scale = 1 / 256;
        if ($entry['tmnxDDMExternallyCalibrated'] === 'true') {
            $scale = ($entry['tmnxDDMExtCalTemperatureSlope'] / 256) / 256;
            if ($entry['tmnxDDMExtCalTemperatureOffset'] != 0) {
                $sensor_options['sensor_addition'] = $entry['tmnxDDMExtCalTemperatureOffset'] / 256;
            }
        }
        // Limits
        $sensor_options['limit_high']      = $entry['tmnxDDMTempHiAlarm'] * $scale;
        $sensor_options['limit_high_warn'] = $entry['tmnxDDMTempHiWarning'] * $scale;
        $sensor_options['limit_low']       = $entry['tmnxDDMTempLowAlarm'] * $scale;
        $sensor_options['limit_low_warn']  = $entry['tmnxDDMTempLowWarning'] * $scale;
        if ($sensor_options['sensor_addition']) {
            $sensor_options['limit_high']      += $sensor_options['sensor_addition'];
            $sensor_options['limit_high_warn'] += $sensor_options['sensor_addition'];
            $sensor_options['limit_low']       += $sensor_options['sensor_addition'];
            $sensor_options['limit_low_warn']  += $sensor_options['sensor_addition'];
        }

        if ($ok && !isset($multilane[$chassis][$ifIndex][$class])) {
            discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $sensor_options);
        }

        // Voltage
        $descr    = $name . ' Voltage';
        $class    = 'voltage';
        $oid_name = 'tmnxDDMSupplyVoltage';
        $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.4.31.1.6.' . $index;
        $value    = $entry[$oid_name];

        $sensor_options = $options;
        // Scale
        //        "The value of tmnxDDMSupplyVoltage indicates the current supply voltage
        //          of the SFF. For 100G MSA Transponder, the supply voltage is in
        //          millivolts (mV). For all other types the voltage is in deci-millivolts
        //          (1/10th of a millivolt or 100 microvolt units).
        //
        //          If the SFF is externally calibrated, the objects
        //          tmnxDDMExtCalVoltageSlope and tmnxDDMExtCalVoltageOffset affect the
        //          voltage calculation.
        //
        //          The formula for translating between the value of tmnxDDMSupplyVoltage
        //          and Voltage is:
        //              Internally Calibrated only:
        //                 tmnxDDMSupplyVoltage * conversion_factor
        //              Externally Calibrated:
        //                 (tmnxDDMSupplyVoltage * (tmnxDDMExtCalVoltageSlope / 256)
        //                       + tmnxDDMExtCalVoltageOffset) * conversion_factor
        //          where conversion_factor is 1/1000 for 100G MSA transponders and
        //          1/10000 for all the others.
        //
        //          For example (internally calibrated SFF): 1. For 100G MSA transponders,
        //          the SNMP value 32851 is 32.851 Volts (V). 2. For all others, the SNMP
        //          value 32851 is 3.2851 Volts (V)."
        $factor = $entry['tmnxPortTransceiverType'] === 'oifMsa100gLh' ? 1000 : 10000;
        $scale  = 1 / $factor;
        if ($entry['tmnxDDMExternallyCalibrated'] === 'true') {
            $scale = ($entry['tmnxDDMExtCalVoltageSlope'] / 256) / $factor;
            if ($entry['tmnxDDMExtCalVoltageOffset'] != 0) {
                $sensor_options['sensor_addition'] = $entry['tmnxDDMExtCalVoltageOffset'] / $factor;
            }
        }
        // Limits
        $sensor_options['limit_high']      = $entry['tmnxDDMSupplyVoltageHiAlarm'] * $scale;
        $sensor_options['limit_high_warn'] = $entry['tmnxDDMSupplyVoltageHiWarning'] * $scale;
        $sensor_options['limit_low']       = $entry['tmnxDDMSupplyVoltageLowAlarm'] * $scale;
        $sensor_options['limit_low_warn']  = $entry['tmnxDDMSupplyVoltageLowWarning'] * $scale;
        if ($sensor_options['sensor_addition']) {
            $sensor_options['limit_high']      += $sensor_options['sensor_addition'];
            $sensor_options['limit_high_warn'] += $sensor_options['sensor_addition'];
            $sensor_options['limit_low']       += $sensor_options['sensor_addition'];
            $sensor_options['limit_low_warn']  += $sensor_options['sensor_addition'];
        }

        if ($ok && !isset($multilane[$chassis][$ifIndex][$class])) {
            discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $sensor_options);
        }

        if (isset($multilane[$chassis][$ifIndex])) {
            // Skip bias, tx/rx power for already multilane sensors
            continue;
        }

        // Tx Bias
        $descr    = $name . ' Tx Bias';
        $class    = 'current';
        $oid_name = 'tmnxDDMTxBiasCurrent';
        $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.4.31.1.11.' . $index;
        $value    = $entry[$oid_name];

        $sensor_options = $options;
        // Scale
        //        "The value of tmnxDDMTxBiasCurrent indicates the current Transmit Bias
        //          Current of the SFF in 1/500 milliamperes (mA).
        //
        //          If the SFF is externally calibrated, the objects
        //          tmnxDDMExtCalTxLaserBiasSlope and tmnxDDMExtCalTxLaserBiasOffset
        //          affect the ampere calculation.
        //
        //          The formula for translating between the value of tmnxDDMTxBiasCurrent
        //          and milliamperes is:
        //              Internally Calibrated only:
        //                 tmnxDDMTxBiasCurrent / 500
        //              Externally Calibrated:
        //                 (tmnxDDMTxBiasCurrent * (tmnxDDMExtCalTxLaserBiasSlope / 256)
        //                       + tmnxDDMExtCalTxLaserBiasOffset) / 500
        //
        //          For example (internally calibrated SFF): The SNMP value 2565 is 5.1
        //          milliamperes (mA)."
        $factor = 500000;   // 500 * 1000
        $scale  = 1 / $factor;
        if ($entry['tmnxDDMExternallyCalibrated'] === 'true') {
            $scale = ($entry['tmnxDDMExtCalTxLaserBiasSlope'] / 256) / $factor;
            if ($entry['tmnxDDMExtCalTxLaserBiasOffset'] != 0) {
                $sensor_options['sensor_addition'] = $entry['tmnxDDMExtCalTxLaserBiasOffset'] / $factor;
            }
        }
        // Limits
        $sensor_options['limit_high']      = $entry['tmnxDDMTxBiasCurrentHiAlarm'] * $scale;
        $sensor_options['limit_high_warn'] = $entry['tmnxDDMTxBiasCurrentHiWarning'] * $scale;
        $sensor_options['limit_low']       = $entry['tmnxDDMTxBiasCurrentLowAlarm'] * $scale;
        $sensor_options['limit_low_warn']  = $entry['tmnxDDMTxBiasCurrentLowWarning'] * $scale;
        if ($sensor_options['sensor_addition']) {
            $sensor_options['limit_high']      += $sensor_options['sensor_addition'];
            $sensor_options['limit_high_warn'] += $sensor_options['sensor_addition'];
            $sensor_options['limit_low']       += $sensor_options['sensor_addition'];
            $sensor_options['limit_low_warn']  += $sensor_options['sensor_addition'];
        }

        if ($ok) {
            discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $sensor_options);
        }

        // Tx Power
        $descr    = $name . ' Tx Power';
        $class    = 'power';
        $oid_name = 'tmnxDDMTxOutputPower';
        $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.4.31.1.16.' . $index;
        $value    = $entry[$oid_name];

        $sensor_options = $options;
        // Scale
        //        "The value of tmnxDDMTxOutputPower indicates the current Output Power
        //          of the SFF in one tenths of a microwatt (uW).
        //
        //          If the SFF is externally calibrated, the objects
        //          tmnxDDMExtCalTxPowerSlope and tmnxDDMExtCalTxPowerOffset affect the
        //          output power calculation.
        //
        //          For example (internally calibrated SFF):
        //          Using the SNMP value of 790, and using units of tenths of microwatt,
        //          790 becomes 79 microwatts or 0.079 milliwatts. Converting to dBm:
        //                10 x log10(0.079) = -11.0 dBm"
        $factor = 10000000; // 10 * 1000 * 1000
        $scale  = 1 / $factor;
        if ($entry['tmnxDDMExternallyCalibrated'] === 'true') {
            // tmnxDDMTxOutputPower.1.1292402691 = 2792
            // tmnxDDMTxOutputPowerLowWarning.1.1292402691 = 1259
            // tmnxDDMTxOutputPowerLowAlarm.1.1292402691 = 1000
            // tmnxDDMTxOutputPowerHiWarning.1.1292402691 = 5012
            // tmnxDDMTxOutputPowerHiAlarm.1.1292402691 = 6310

            // tmnxDDMExternallyCalibrated.1.1292402691 = true
            // tmnxDDMExtCalTxPowerSlope.1.1292402691 = 256
            // tmnxDDMExtCalTxPowerOffset.1.1292402691 = 0
            $scale = ($entry['tmnxDDMExtCalTxPowerSlope'] / 256) / $factor;
            if ($entry['tmnxDDMExtCalTxPowerOffset'] != 0) {
                $sensor_options['sensor_addition'] = $entry['tmnxDDMExtCalTxPowerOffset'] / $factor;
            }
        }
        // Limits
        $sensor_options['limit_high']      = $entry['tmnxDDMTxOutputPowerHiAlarm'] * $scale;
        $sensor_options['limit_high_warn'] = $entry['tmnxDDMTxOutputPowerHiWarning'] * $scale;
        $sensor_options['limit_low']       = $entry['tmnxDDMTxOutputPowerLowAlarm'] * $scale;
        $sensor_options['limit_low_warn']  = $entry['tmnxDDMTxOutputPowerLowWarning'] * $scale;
        if ($sensor_options['sensor_addition']) {
            $sensor_options['limit_high']      += $sensor_options['sensor_addition'];
            $sensor_options['limit_high_warn'] += $sensor_options['sensor_addition'];
            $sensor_options['limit_low']       += $sensor_options['sensor_addition'];
            $sensor_options['limit_low_warn']  += $sensor_options['sensor_addition'];
        }

        if ($ok) {
            discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $sensor_options);
        }

        // Rx Power
        $descr    = $name . ' Rx Power';
        $class    = 'power';
        $oid_name = 'tmnxDDMRxOpticalPower';
        $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.4.31.1.21.' . $index;
        $value    = $entry[$oid_name];

        $sensor_options = $options;
        // Scale
        //        "The value of tmnxDDMRxOpticalPower indicates the current Received
        //          Optical Power of the SFF in one tenths of a microwatt (uW).
        //
        //          If the SFF is externally calibrated, the objects
        //          tmnxDDMExtCalRxPower4, tmnxDDMExtCalRxPower3, tmnxDDMExtCalRxPower2,
        //          tmnxDDMExtCalRxPower1 and tmnxDDMExtCalRxPower0 affect the output
        //          power calculation.
        //          Table 3.16 in the SFF Committee Standard's document SFF-8472 Rev 10.2.
        //
        //          For example (internally calibrated SFF):
        //          Using the SNMP value of 790, and using units of tenths of microwatt,
        //          790 becomes 79 microwatts or 0.079 milliwatts. Converting to dBm:
        //                10 x log10(0.079) = -11.0 dBm"
        $factor = 10000000; // 10 * 1000 * 1000
        $scale  = 1 / $factor;
        if ($entry['tmnxDDMExternallyCalibrated'] === 'true') {
            // tmnxDDMRxOpticalPower.1.1292402691 = 1936
            // tmnxDDMRxOpticalPowerLowWarning.1.1292402691 = 453
            // tmnxDDMRxOpticalPowerLowAlarm.1.1292402691 = 357
            // tmnxDDMRxOpticalPowerHiWarning.1.1292402691 = 23145
            // tmnxDDMRxOpticalPowerHiAlarm.1.1292402691 = 29158
            // tmnxDDMRxOpticalPowerType.1.1292402691 = average
            // tmnxDDMExternallyCalibrated.1.1292402691 = true
            // tmnxDDMExtCalRxPower4.1.1292402691 = 0
            // tmnxDDMExtCalRxPower3.1.1292402691 = 0
            // tmnxDDMExtCalRxPower2.1.1292402691 = 2998520959
            // tmnxDDMExtCalRxPower1.1.1292402691 = 1046360211
            // tmnxDDMExtCalRxPower0.1.1292402691 = 1070575314
            $sensor_options['sensor_convert'] = 'tmnx_rx_power'; // added extra oids polling and conversion in sensor_value_scale()
            // Dear fucking god.. how this calculated.. how we can poll this????
            //  tmnxDDMExtCalRxPower0 +
            // (tmnxDDMExtCalRxPower1 * tmnxDDMRxOpticalPower^1) +
            // (tmnxDDMExtCalRxPower2 * tmnxDDMRxOpticalPower^2) +
            // (tmnxDDMExtCalRxPower3 * tmnxDDMRxOpticalPower^3) +
            // (tmnxDDMExtCalRxPower4 * tmnxDDMRxOpticalPower^4)
            foreach ([ 'tmnxDDMRxOpticalPower', 'tmnxDDMRxOpticalPowerHiAlarm', 'tmnxDDMRxOpticalPowerHiWarning',
                       'tmnxDDMRxOpticalPowerLowAlarm', 'tmnxDDMRxOpticalPowerLowWarning' ] as $oid) {
                $entry[$oid] = value_unit_tmnx_rx_power($entry[$oid], $entry['tmnxDDMExtCalRxPower0'], $entry['tmnxDDMExtCalRxPower1'],
                                                        $entry['tmnxDDMExtCalRxPower2'], $entry['tmnxDDMExtCalRxPower3'], $entry['tmnxDDMExtCalRxPower4']);
            }
            $value = $entry[$oid_name];
        }
        // Limits
        $sensor_options['limit_high']      = $entry['tmnxDDMRxOpticalPowerHiAlarm'] * $scale;
        $sensor_options['limit_high_warn'] = $entry['tmnxDDMRxOpticalPowerHiWarning'] * $scale;
        $sensor_options['limit_low']       = $entry['tmnxDDMRxOpticalPowerLowAlarm'] * $scale;
        $sensor_options['limit_low_warn']  = $entry['tmnxDDMRxOpticalPowerLowWarning'] * $scale;

        if ($ok) {
            discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $sensor_options);
        }
    }
}

// EOF
