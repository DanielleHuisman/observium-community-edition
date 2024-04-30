<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Getting Radios

// FIXME. Currently I do not know, how correctly do radio polling!
print_debug("WiP. Polling Radio by UI-AF60-MIB skipped.");
return;

// UI-AF60-MIB::af60Role.1 = INTEGER: ap(0)
// UI-AF60-MIB::af60Frequency.1 = INTEGER: 69120 MHz
// UI-AF60-MIB::af60Bandwidth.1 = INTEGER: 1080 MHz
// UI-AF60-MIB::af60Ssid.1 = STRING: core-kski
// UI-AF60-MIB::af60LastIp.1 = IpAddress: 172.16.0.50
// UI-AF60-MIB::af60Mac.1 = Hex-STRING: F4 92 BF E2 A1 AD
$radio_snmp = snmpwalk_cache_oid($device, "af60Frequency", [],          "UI-AF60-MIB");
$radio_snmp = snmpwalk_cache_oid($device, "af60Bandwidth", $radio_snmp, "UI-AF60-MIB");
$radio_snmp = snmpwalk_cache_oid($device, "af60Ssid",      $radio_snmp, "UI-AF60-MIB");

// UI-AF60-MIB::af60StaMac.'.....Z'.1 = Hex-STRING: F4 92 BF E2 A1 5A
// UI-AF60-MIB::af60StaActiveLink.'.....Z'.1 = STRING: main
// UI-AF60-MIB::af60StaRSSI.'.....Z'.1 = INTEGER: -47
// UI-AF60-MIB::af60StaSNR.'.....Z'.1 = INTEGER: 20
// UI-AF60-MIB::af60StaTxMCS.'.....Z'.1 = INTEGER: 10 X
// UI-AF60-MIB::af60StaRxMCS.'.....Z'.1 = INTEGER: 10 X
// UI-AF60-MIB::af60StaTxCapacity.'.....Z'.1 = INTEGER: 1201000 Kbps
// UI-AF60-MIB::af60StaRxCapacity.'.....Z'.1 = INTEGER: 1201000 Kbps
// UI-AF60-MIB::af60StaTxBytes.'.....Z'.1 = Counter64: 46344962
// UI-AF60-MIB::af60StaRxBytes.'.....Z'.1 = Counter64: 40018720
// UI-AF60-MIB::af60StaTxThroughput.'.....Z'.1 = INTEGER: 0 Kbps
// UI-AF60-MIB::af60StaRxThroughput.'.....Z'.1 = INTEGER: 0 Kbps
// UI-AF60-MIB::af60StaRemoteDevModel.'.....Z'.1 = STRING: AF60-LR
// UI-AF60-MIB::af60StaRemoteDevName.'.....Z'.1 = STRING: airFiber 60 LR
// UI-AF60-MIB::af60StaRemoteDistance.'.....Z'.1 = INTEGER: 2198 m
// UI-AF60-MIB::af60StaRemoteDistanceFeet.'.....Z'.1 = INTEGER: 7211 ft
// UI-AF60-MIB::af60StaRemoteConnectionTime.'.....Z'.1 = Timeticks: (8634900) 23:59:09.00
// UI-AF60-MIB::af60StaRemoteRSSI.'.....Z'.1 = INTEGER: -48 dBm
// UI-AF60-MIB::af60StaRemoteSNR.'.....Z'.1 = INTEGER: 19
// UI-AF60-MIB::af60StaRemoteLastIp.'.....Z'.1 = IpAddress: 172.16.4.18

$radio_stations = snmpwalk_cache_oid($device, 'af60StationEntry', [], "UI-AF60-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
// Goes through the SNMP radio data
foreach ($radio_stations as $sta_index => $station) {

    $index_array = explode('.', $sta_index);
    $index = array_pop($index_array);
    $sta_id = implode('.', $index_array);

    $radio = [];
    $radio['radio_name']           = $station['af60StaActiveLink'];
    $radio['radio_status']         = 'ok';
    $radio['radio_loopback']       = ['NULL'];
    $radio['radio_tx_mute']        = ['NULL'];
    $radio['radio_tx_freq']        = $radio_snmp[$index]['af60Frequency'] * 1000;
    $radio['radio_rx_freq']        = $radio_snmp[$index]['af60Frequency'] * 1000;
    $radio['radio_tx_power']       = $radio['ubntRadioTxPower'];
    $radio['radio_rx_level']       = $station['ubntStaSignal'];
    $radio['radio_e1t1_channels']  = ['NULL'];
    $radio['radio_bandwidth']      = $radio_snmp[$index]['af60Bandwidth'];
    $radio['radio_modulation']     = ['NULL'];
    $radio['radio_total_capacity'] = $station['af60StaTxCapacity'];
    $radio['radio_eth_capacity']   = ['NULL'];
    $radio['radio_rmse']           = ['NULL'];       // Convert to units
    $radio['radio_gain_text']      = $radio['ubntRadioAntenna'];
    $radio['radio_carrier_offset'] = ['NULL'];
    $radio['radio_sym_rate_tx']    = ['NULL'];
    $radio['radio_sym_rate_rx']    = ['NULL'];
    $radio['radio_standard']       = ['NULL'];
    $radio['radio_cur_capacity']   = $station['ubntStaTxRate'];

    //print_r($radio);

    //poll_p2p_radio($device, 'UI-AF60-MIB', $sta_index, $radio);
}
// EOF
