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

echo(" UBNT-AirFIBER-MIB ");

// Getting Radios

$radios_snmp = snmpwalk_cache_oid($device, "airFiberConfigIndex", [], "UBNT-AirFIBER-MIB");

$oids = ['radioLinkMode', 'radioEnable', 'radioDuplex', 'radioLinkDistM', 'radioLinkState',
         'txFrequency', 'txPower', 'txCapacity', 'txFramesOK', 'txOctetsOK', 'txPauseFrames',
         'txErroredFrames', 'txValidUnicastFrames', 'txValidMulticastFrames', 'txValidBroadcastFrames',
         'rxFrequency', 'rxGain', 'rxCapacity', 'rxPower0', 'rxPower1', 'rxFramesOK', 'rxOctetsOK',
         'rxFrameCrcErr', 'rxAlignErr', 'rxPauseFrames', 'rxErroredFrames', 'rxValidUnicastFrames',
         'rxValidMulticastFrames', 'rxValidBroadcastFrames', 'rxDroppedMacErrFrames', 'rxTotalOctets', 'rxTotalFrames',
         'regDomain', 'linkName', 'linkUpTime', 'remoteMAC', 'remoteIP', 'curTXModRate'];

// Goes through the SNMP radio data
foreach ($radios_snmp as $index => $radio) {

    $get_oids = [];
    foreach ($oids as $oid) {
        $get_oids[] = $oid . '.' . $index;
    }

    $data = snmp_get_multi_oid($device, $get_oids, [], 'UBNT-AirFIBER-MIB');
    $data = $data[$index];

    //print_r($data);

    $radio['radio_name']           = $data['linkName'];
    $radio['radio_status']         = $data['radioLinkState'];
    $radio['radio_loopback']       = ['NULL'];
    $radio['radio_tx_mute']        = ['NULL'];
    $radio['radio_tx_freq']        = $data['txFrequency'] * 1000;
    $radio['radio_rx_freq']        = $data['rxFrequency'] * 1000;
    $radio['radio_tx_power']       = $data['txPower'];
    $radio['radio_rx_level']       = $data['rxPower0'];
    $radio['radio_e1t1_channels']  = ['NULL'];
    $radio['radio_bandwidth']      = ['NULL'];
    $radio['radio_modulation']     = $data['curTXModRate'];
    $radio['radio_total_capacity'] = $data['txCapacity'];
    $radio['radio_eth_capacity']   = ['NULL'];
    $radio['radio_rmse']           = ['NULL'];       // Convert to units
    $radio['radio_gain_text']      = $data['rxGain'];
    $radio['radio_carrier_offset'] = ['NULL'];
    $radio['radio_sym_rate_tx']    = ['NULL'];
    $radio['radio_sym_rate_rx']    = ['NULL'];
    $radio['radio_standard']       = ['NULL'];
    $radio['radio_cur_capacity']   = $data['txCapacity'];

    //print_debug_vars($radio);

    poll_p2p_radio($device, 'ubnt-airfiber-mib', $index, $radio);

}

