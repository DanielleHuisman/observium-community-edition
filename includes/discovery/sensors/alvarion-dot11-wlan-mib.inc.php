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

$oids = snmpwalk_cache_oid($device, 'brzaccVLNewAdbUnitName', [], 'ALVARION-DOT11-WLAN-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
/// NOTE. New table prefer, because old use weird indexes
if ($oids) {
    //ALVARION-DOT11-WLAN-MIB::brzaccVLNewAdbUnitName.0.16.231.20.145.216 = "Kern Waste Tehachapi"
    //ALVARION-DOT11-WLAN-MIB::brzaccVLNewAdbSNR.0.16.231.20.145.216 = 25
    //ALVARION-DOT11-WLAN-MIB::brzaccVLNewAdbRSSI.0.16.231.20.145.216 = -76
    $oids = snmpwalk_cache_oid($device, 'brzaccVLNewAdbSNR', $oids, 'ALVARION-DOT11-WLAN-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    $oids = snmpwalk_cache_oid($device, 'brzaccVLNewAdbRSSI', $oids, 'ALVARION-DOT11-WLAN-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

    foreach ($oids as $index => $entry) {
        $descr = $entry['brzaccVLNewAdbUnitName'];

        // Signal-to-Noise Ratio
        if (is_numeric($entry['brzaccVLNewAdbSNR'])) {
            $oid   = ".1.3.6.1.4.1.12394.1.1.11.5.1.3.1.26.$index";
            $value = $entry['brzaccVLNewAdbSNR'];
            discover_sensor_ng($device, 'snr', $mib, 'brzaccVLNewAdbSNR', $oid, $index, NULL, "$descr (SNR)", 1, $value, ['rename_rrd' => "alvarion-dot11.$index"]);

        }

        // Received signal strength indication
        if (is_numeric($entry['brzaccVLNewAdbRSSI'])) {
            $oid   = ".1.3.6.1.4.1.12394.1.1.11.5.1.3.1.54.$index";
            $value = $entry['brzaccVLNewAdbRSSI'];
            discover_sensor_ng($device, 'dbm', $mib, 'brzaccVLNewAdbRSSI', $oid, $index, NULL, "$descr (RSSI)", 1, $value, ['rename_rrd' => "alvarion-dot11.$index"]);
        }
    }
} else {
    //ALVARION-DOT11-WLAN-MIB::brzaccVLAdbUnitName.1 = STRING: "Kern Waste Tehachapi"
    //ALVARION-DOT11-WLAN-MIB::brzaccVLAdbSNR.1 = INTEGER: 28
    //ALVARION-DOT11-WLAN-MIB::brzaccVLAdbRSSI.1 = INTEGER: -75
    $oids = snmpwalk_cache_oid($device, 'brzaccVLAdbUnitName', [], 'ALVARION-DOT11-WLAN-MIB');
    $oids = snmpwalk_cache_oid($device, 'brzaccVLAdbSNR', $oids, 'ALVARION-DOT11-WLAN-MIB');
    $oids = snmpwalk_cache_oid($device, 'brzaccVLAdbRSSI', $oids, 'ALVARION-DOT11-WLAN-MIB');

    foreach ($oids as $index => $entry) {
        $descr = $entry['brzaccVLAdbUnitName'];

        // Signal-to-Noise Ratio
        if (is_numeric($entry['brzaccVLAdbSNR'])) {
            $oid   = ".1.3.6.1.4.1.12394.1.1.11.5.1.2.1.5.$index";
            $value = $entry['brzaccVLAdbSNR'];
            discover_sensor_ng($device, 'snr', $mib, 'brzaccVLAdbSNR', $oid, $index, NULL, "$descr (SNR)", 1, $value, ['rename_rrd' => "alvarion-dot11.$index"]);
        }

        // Received signal strength indication
        if (is_numeric($entry['brzaccVLAdbRSSI'])) {
            $oid   = ".1.3.6.1.4.1.12394.1.1.11.5.1.2.1.46.$index";
            $value = $entry['brzaccVLAdbRSSI'];
            discover_sensor_ng($device, 'dbm', $mib, 'brzaccVLAdbRSSI', $oid, $index, NULL, "$descr (RSSI)", 1, $value, ['rename_rrd' => "alvarion-dot11.$index"]);

        }
    }
}

//ALVARION-DOT11-WLAN-MIB::brzaccVLAverageReceiveSNR.0 = INTEGER: 23
//ALVARION-DOT11-WLAN-TST-MIB::brzLighteShowAuAvgSNR.0 = INTEGER: 23
$average_snr = snmp_get($device, 'brzaccVLAverageReceiveSNR.0', '-OUqnv', 'ALVARION-DOT11-WLAN-MIB');
if (is_numeric($average_snr)) {
    $oid = '.1.3.6.1.4.1.12394.1.1.11.1.0';
    discover_sensor_ng($device, 'snr', $mib, 'brzaccVLAverageReceiveSNR', $oid, 0, NULL, "Average SNR", 1, $value, ['rename_rrd' => "alvarion-dot11-average.0"]);

}

//ALVARION-DOT11-WLAN-TST-MIB::brzLighteAvgRssiRecieved.0 = INTEGER: 0
$average_rssi = snmp_get($device, 'brzLighteAvgRssiRecieved.0', '-OUqnv', 'ALVARION-DOT11-WLAN-TST-MIB');
if (is_numeric($average_rssi) && $average_rssi) {
    $oid = '.1.3.6.1.4.1.12394.3.2.3.2.1.0';
    discover_sensor_ng($device, 'dbm', $mib, 'brzLighteAvgRssiRecieved', $oid, 0, NULL, "Average RSSI", 1, $value, ['rename_rrd' => "alvarion-dot11-average.0"]);
}

unset($oids, $oid, $value, $average_snr, $average_rssi, $descr);

// EOF
