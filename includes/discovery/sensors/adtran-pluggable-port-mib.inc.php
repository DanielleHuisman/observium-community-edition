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


// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortPluggableType.25200001 = INTEGER: sfp(2)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortConnectorType.25200001 = INTEGER: fiberLC(2)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortCapabilities.25200001 = BITS: BC pluggable(0) voltageReadable(2) wavelengthReadable(3) diagnosticsAvailable(4) wavelengthProvisionable(5)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortState.25200001 = BITS: 40 portUp(1)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortStatusBits.25200001 = BITS: E8 isPresent(0) isValid(1) isSupported(2) isTxEnabled(4)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortAlarmBits.25200001 = BITS:
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortAlarmsSuppressed.25200001 = BITS:
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortBer.25200001 = Gauge32: 0
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortVendorName.25200001 = STRING: "Hisense"
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortVendorPartNumber.25200001 = STRING: "LTF2305-BH-AD"
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortVendorSerialNumber.25200001 = STRING: "LBADTN2112AJ693"
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortAdtranName.25200001 = STRING: "ADTRAN 10GBASE-LR10"
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortAdtranPartNumber.25200001 = STRING: "1442412F2"
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortAdtranClei.25200001 = STRING: "BVL3AYFDAA"
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortWavelength.25200001 = Gauge32: 1270
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortMinBitRate.25200001 = Gauge32: 9270
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortMaxBitRate.25200001 = Gauge32: 11330
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortReachLength.25200001 = Gauge32: 10
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortReachUnits.25200001 = INTEGER: reachUnit1Kmfor09um(1)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortRxPower.25200001 = INTEGER: -35
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortRxPowerUnits.25200001 = INTEGER: tenthsDBm(2)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTxPower.25200001 = INTEGER: -24
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTxPowerUnits.25200001 = INTEGER: tenthsDBm(2)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTxBias.25200001 = INTEGER: 20
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTxBiasUnits.25200001 = INTEGER: milliAmps(1)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTemperature.25200001 = INTEGER: 39
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTemperatureUnits.25200001 = INTEGER: celsius(1)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortVoltage.25200001 = INTEGER: 3310
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortVendorRevision.25200001 = STRING: "A"
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortWavelengthPicoMeter.25200001 = Gauge32: 0
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggableNumberOfXcvrChannels.25200001 = INTEGER: 1


// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortPluggableType.1100001 = INTEGER: sfp(2)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortConnectorType.1100001 = INTEGER: fiberSC(3)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortCapabilities.1100001 = BITS: B8 pluggable(0) voltageReadable(2) wavelengthReadable(3) diagnosticsAvailable(4)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortState.1100001 = BITS: 40 portUp(1)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortStatusBits.1100001 = BITS: E8 isPresent(0) isValid(1) isSupported(2) isTxEnabled(4)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortAlarmBits.1100001 = BITS:
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortAlarmsSuppressed.1100001 = BITS:
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortBer.1100001 = Gauge32: 0
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortVendorName.1100001 = STRING: "ADTRAN"
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortVendorPartNumber.1100001 = STRING: "1442543F2"
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortVendorSerialNumber.1100001 = STRING: "LBADTN2202AY538 "
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortAdtranName.1100001 = STRING: " "
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortAdtranPartNumber.1100001 = STRING: "1442543F2"
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortAdtranClei.1100001 = STRING: "BVL3BBSDAA"
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortWavelength.1100001 = Gauge32: 0
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortMinBitRate.1100001 = Gauge32: 0
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortMaxBitRate.1100001 = Gauge32: 0
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortReachLength.1100001 = Gauge32: 0
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortReachUnits.1100001 = INTEGER: reachUnit1Kmfor09um(1)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortRxPower.1100001 = INTEGER: 0
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortRxPowerUnits.1100001 = INTEGER: tenthsDBm(2)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTxPower.1100001 = INTEGER: 53
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTxPowerUnits.1100001 = INTEGER: tenthsDBm(2)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTxBias.1100001 = INTEGER: 4
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTxBiasUnits.1100001 = INTEGER: milliAmps(1)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTemperature.1100001 = INTEGER: 42
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortTemperatureUnits.1100001 = INTEGER: celsius(1)
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortVoltage.1100001 = INTEGER: 3320
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortVendorRevision.1100001 = ""
// ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortWavelengthPicoMeter.1100001 = Gauge32: 0

$oids = snmp_cache_table($device, 'adGenPluggablePortTable', [], $mib); // also in inventory
print_debug_vars($oids);

if (!snmp_status()) {
    return;
}

$oids_lane = snmpwalk_cache_twopart_oid($device, 'adGenPluggablePortChannelTable', [], $mib);
print_debug_vars($oids_lane);

foreach ($oids as $index => $entry) {
    if ($entry['adGenPluggablePortConnectorType'] === 'none') {
        // Not exist DDM
        continue;
    }

    $entry['index'] = $index;
    $match          = [ 'measured_match' => [ 'entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%index%' ] ];
    $options        = entity_measured_match_definition($device, $match, $entry);

    $name = $options['port_label'];
    $vendor_name = trim($entry['adGenPluggablePortVendorName'] . ' ' . $entry['adGenPluggablePortVendorPartNumber']);
    if ($entry['adGenPluggablePortVendorSerialNumber']) {
        $vendor_name .= ', SN:' . trim($entry['adGenPluggablePortVendorSerialNumber']);
    }
    if ($entry['adGenPluggablePortWavelength'] && $entry['adGenPluggablePortWavelength'] > 1) {
        $vendor_name .= ', ' . $entry['adGenPluggablePortWavelength'] . 'nm';
    }

    // Temperature
    $descr    = $name . ' Temperature';
    if ($vendor_name) {
        $descr .= ' (' . $vendor_name . ')';
    }
    $class    = 'temperature';
    $oid_name = 'adGenPluggablePortTemperature';
    $oid_num  = '.1.3.6.1.4.1.664.5.70.4.1.1.1.27.' . $index;
    $scale    = str_starts_with($entry['adGenPluggablePortTemperatureUnits'], 'tenths') ? 0.1 : 1;
    $value    = $entry[$oid_name];

    $opt      = [];
    if (str_ends_with($entry['adGenPluggablePortTemperatureUnits'], 'ahrenheit')) {
        // fahrenheit, tenthsFahrenheit
        $opt['sensor_unit'] = 'F';
    }

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, array_merge($options, $opt));

    // Voltage
    $descr    = $name . ' Voltage';
    if ($vendor_name) {
        $descr .= ' (' . $vendor_name . ')';
    }
    $class    = 'voltage';
    $oid_name = 'adGenPluggablePortVoltage';
    $oid_num  = '.1.3.6.1.4.1.664.5.70.4.1.1.1.29.' . $index;
    $scale    = 0.001;
    $value    = $entry[$oid_name];

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, $options);

    if (isset($entry['adGenPluggableNumberOfXcvrChannels'], $oids_lane[$index]) && $entry['adGenPluggableNumberOfXcvrChannels'] > 1) {
        // Multi Channel sensors

        // ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortChannelRxPower.25200001.1 = INTEGER: -35
        // ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortChannelRxPowerUnits.25200001.1 = INTEGER: tenthsDBm(2)
        // ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortChannelTxPower.25200001.1 = INTEGER: -24
        // ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortChannelTxPowerUnits.25200001.1 = INTEGER: tenthsDBm(2)
        // ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortChannelTxBias.25200001.1 = INTEGER: 20
        // ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortChannelTxBiasUnits.25200001.1 = INTEGER: milliAmps(1)
        // ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortProvWaveLength.25200001 = INTEGER: 0
        // ADTRAN-PLUGGABLE-PORT-MIB::adGenPluggablePortProvChannelNumber.25200001 = INTEGER: 0

        foreach ($oids_lane[$index] as $lane => $lentry) {
            $lname  = $name . ' Lane ' . $lane;
            $lindex = $index . '.' . $lane;
            
            // TX Bias
            $descr    = $lname . ' TX Bias';
            if ($vendor_name) {
                $descr .= ' (' . $vendor_name . ')';
            }
            $class    = 'current';
            $oid_name = 'adGenPluggablePortChannelTxBias';
            $oid_num  = '.1.3.6.1.4.1.664.5.70.4.1.2.1.7.' . $lindex;
            $scale    = 0.001;
            $value    = $lentry[$oid_name];

            discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $lindex, $descr, $scale, $value, $options);

            // TX Power
            $descr    = $lname . ' TX Power';
            if ($vendor_name) {
                $descr .= ' (' . $vendor_name . ')';
            }
            // dBm, tenthsDBm, microWatts, tenthsMicroWatts
            if (str_contains($lentry['adGenPluggablePortChannelTxPowerUnits'], 'Watts')) {
                $class  = 'power';
                $scale    = str_starts_with($lentry['adGenPluggablePortChannelTxPowerUnits'], 'tenths') ? 0.0000001 : 0.000001;
            } else {
                $class  = 'dbm';
                $scale    = str_starts_with($lentry['adGenPluggablePortChannelTxPowerUnits'], 'tenths') ? 0.1 : 1;
            }
            $oid_name = 'adGenPluggablePortChannelTxPower';
            $oid_num  = '.1.3.6.1.4.1.664.5.70.4.1.2.1.5.' . $lindex;
            $value    = $lentry[$oid_name];

            discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $lindex, $descr, $scale, $value, $options);

            // RX Power
            $descr    = $lname . ' RX Power';
            if ($vendor_name) {
                $descr .= ' (' . $vendor_name . ')';
            }
            // dBm, tenthsDBm, microWatts, tenthsMicroWatts
            if (str_contains($lentry['adGenPluggablePortChannelRxPowerUnits'], 'Watts')) {
                $class  = 'power';
                $scale    = str_starts_with($lentry['adGenPluggablePortChannelRxPowerUnits'], 'tenths') ? 0.0000001 : 0.000001;
            } else {
                $class  = 'dbm';
                $scale    = str_starts_with($lentry['adGenPluggablePortChannelRxPowerUnits'], 'tenths') ? 0.1 : 1;
            }
            $oid_name = 'adGenPluggablePortChannelRxPower';
            $oid_num  = '.1.3.6.1.4.1.664.5.70.4.1.2.1.3.' . $lindex;
            $value    = $lentry[$oid_name];

            discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $lindex, $descr, $scale, $value, $options);
        }
    } else {
        // TX Bias
        $descr    = $name . ' TX Bias';
        if ($vendor_name) {
            $descr .= ' (' . $vendor_name . ')';
        }
        $class    = 'current';
        $oid_name = 'adGenPluggablePortTxBias';
        $oid_num  = '.1.3.6.1.4.1.664.5.70.4.1.1.1.25.' . $index;
        $scale    = 0.001;
        $value    = $entry[$oid_name];

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, $options);

        // TX Power
        $descr    = $name . ' TX Power';
        if ($vendor_name) {
            $descr .= ' (' . $vendor_name . ')';
        }
        // dBm, tenthsDBm, microWatts, tenthsMicroWatts
        if (str_contains($entry['adGenPluggablePortTxPowerUnits'], 'Watts')) {
            $class  = 'power';
            $scale    = str_starts_with($entry['adGenPluggablePortTxPowerUnits'], 'tenths') ? 0.0000001 : 0.000001;
        } else {
            $class  = 'dbm';
            $scale    = str_starts_with($entry['adGenPluggablePortTxPowerUnits'], 'tenths') ? 0.1 : 1;
        }
        $oid_name = 'adGenPluggablePortTxPower';
        $oid_num  = '.1.3.6.1.4.1.664.5.70.4.1.1.1.23.' . $index;
        $value    = $entry[$oid_name];

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, $options);

        // RX Power
        $descr    = $name . ' RX Power';
        if ($vendor_name) {
            $descr .= ' (' . $vendor_name . ')';
        }
        // dBm, tenthsDBm, microWatts, tenthsMicroWatts
        if (str_contains($entry['adGenPluggablePortRxPowerUnits'], 'Watts')) {
            $class  = 'power';
            $scale    = str_starts_with($entry['adGenPluggablePortRxPowerUnits'], 'tenths') ? 0.0000001 : 0.000001;
        } else {
            $class  = 'dbm';
            $scale    = str_starts_with($entry['adGenPluggablePortRxPowerUnits'], 'tenths') ? 0.1 : 1;
        }
        $oid_name = 'adGenPluggablePortRxPower';
        $oid_num  = '.1.3.6.1.4.1.664.5.70.4.1.1.1.21.' . $index;
        $value    = $entry[$oid_name];

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, $options);
    }
}

// EOF
