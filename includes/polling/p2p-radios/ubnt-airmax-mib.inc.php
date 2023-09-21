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

//echo(" UBNT-AirMAX-MIB ");
//FIXME, I complete not sure, that this is correct module for this stats, will improved when I get "own airmax" device

// Getting Radios

//ubntRadioIndex.1 = 5
//ubntRadioMode.1 = apwds
//ubntRadioCCode.1 = 840
//ubntRadioFreq.1 = 5840
//ubntRadioDfsEnabled.1 = false
//ubntRadioTxPower.1 = 0
//ubntRadioDistance.1 = 0
//ubntRadioChainmask.1 = 3
//ubntRadioAntenna.1 = Built in - 19 dBi
$radio_snmp = snmpwalk_cache_oid($device, "ubntRadioEntry", [], "UBNT-AirMAX-MIB");
//ubntWlStatIndex.1 = 5
//ubntWlStatSsid.1 = 801to817
//ubntWlStatHideSsid.1 = true
//ubntWlStatApMac.1 = 24:a4:3c:fa:d5:73
//ubntWlStatSignal.1 = -49
//ubntWlStatRssi.1 = 47
//ubntWlStatCcq.1 = 99
//ubntWlStatNoiseFloor.1 = -102
//ubntWlStatTxRate.1 = 300000000
//ubntWlStatRxRate.1 = 300000000
//ubntWlStatSecurity.1 = WPA
//ubntWlStatWdsEnabled.1 = true
//ubntWlStatApRepeater.1 = false
//ubntWlStatChanWidth.1 = 40
//ubntWlStatStaCount.1 = 1
//$radio_snmp = snmpwalk_cache_oid($device, "ubntWlStatEntry", $radio_snmp, "UBNT-AirMAX-MIB");

//ubntStaMac.1.'$.<...' = 24:a4:3c:fa:d5:a0
//ubntStaName.1.'$.<...' = 817blding1_NanoBeamM5
//ubntStaSignal.1.'$.<...' = -49
//ubntStaNoiseFloor.1.'$.<...' = -102
//ubntStaDistance.1.'$.<...' = 0
//ubntStaCcq.1.'$.<...' = 99
//ubntStaAmp.1.'$.<...' = 3
//ubntStaAmq.1.'$.<...' = 99
//ubntStaAmc.1.'$.<...' = 98
//ubntStaLastIp.1.'$.<...' = 107.150.184.164
//ubntStaTxRate.1.'$.<...' = 300000000
//ubntStaRxRate.1.'$.<...' = 300000000
//ubntStaTxBytes.1.'$.<...' = 5926208922
//ubntStaRxBytes.1.'$.<...' = 1255471073
//ubntStaConnTime.1.'$.<...' = 1:19:44:22.00
$radio_stations = snmpwalk_cache_twopart_oid($device, 'ubntStaEntry', [], "UBNT-AirMAX-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

// Goes through the SNMP radio data
foreach ($radio_snmp as $index => $radio) {

    foreach ($radio_stations[$index] as $station_id => $station) {
        $radio['radio_name']           = $station['ubntStaName'];
        $radio['radio_status']         = 'ok';
        $radio['radio_loopback']       = ['NULL'];
        $radio['radio_tx_mute']        = ['NULL'];
        $radio['radio_tx_freq']        = $radio['ubntRadioFreq'] * 1000;
        $radio['radio_rx_freq']        = $radio['ubntRadioFreq'] * 1000;
        $radio['radio_tx_power']       = $radio['ubntRadioTxPower'];
        $radio['radio_rx_level']       = $station['ubntStaSignal'];
        $radio['radio_e1t1_channels']  = ['NULL'];
        $radio['radio_bandwidth']      = ['NULL'];
        $radio['radio_modulation']     = ['NULL'];
        $radio['radio_total_capacity'] = $station['ubntStaTxRate'];
        $radio['radio_eth_capacity']   = ['NULL'];
        $radio['radio_rmse']           = ['NULL'];       // Convert to units
        $radio['radio_gain_text']      = $radio['ubntRadioAntenna'];
        $radio['radio_carrier_offset'] = ['NULL'];
        $radio['radio_sym_rate_tx']    = ['NULL'];
        $radio['radio_sym_rate_rx']    = ['NULL'];
        $radio['radio_standard']       = ['NULL'];
        $radio['radio_cur_capacity']   = $station['ubntStaTxRate'];

        //print_r($radio);

        poll_p2p_radio($device, 'ubnt-airmax-mib', $index . '.' . $station_id, $radio);
    }
}

// EOF
