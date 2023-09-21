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

$infomcs[0]  = 'auto';
$infomcs[4]  = 'mcs-6m(4)';
$infomcs[5]  = 'mcs-9M(5)';
$infomcs[6]  = 'mcs-12M(6)';
$infomcs[7]  = 'mcs-18M(7)';
$infomcs[8]  = 'mcs-24M(8)';
$infomcs[9]  = 'mcs-36M(9)';
$infomcs[10] = 'mcs-48M(10)';
$infomcs[11] = 'mcs-54M(11)';
$infomcs[12] = 'mcs0(12)';
$infomcs[13] = 'mcs1(13)';
$infomcs[14] = 'mcs2(14)';
$infomcs[15] = 'mcs3(15)';
$infomcs[16] = 'mcs4(16)';
$infomcs[17] = 'mcs5(17)';
$infomcs[18] = 'mcs6(18)';
$infomcs[19] = 'mcs7(19)';
$infomcs[20] = 'mcs8(20)';
$infomcs[21] = 'mcs9(21)';
$infomcs[22] = 'mcs10(22)';
$infomcs[23] = 'mcs11(23)';
$infomcs[24] = 'mcs12(24)';
$infomcs[25] = 'mcs13(25)';
$infomcs[26] = 'mcs14(26)';
$infomcs[27] = 'mcs15(27)';
$infomcs[30] = 'nss1-mcs0(30)';
$infomcs[31] = 'nss1-mcs1(31)';
$infomcs[32] = 'nss1-mcs2(32)';
$infomcs[33] = 'nss1-mcs3(33)';
$infomcs[34] = 'nss1-mcs4(34)';
$infomcs[35] = 'nss1-mcs5(35)';
$infomcs[36] = 'nss1-mcs6(36)';
$infomcs[37] = 'nss1-mcs7(37)';
$infomcs[38] = 'nss1-mcs8(38)';
$infomcs[39] = 'nss1-mcs9(39)';
$infomcs[40] = 'nss2-mcs1(40)';
$infomcs[41] = 'nss2-mcs2(41)';
$infomcs[42] = 'nss2-mcs3(42)';
$infomcs[43] = 'nss2-mcs4(43)';
$infomcs[44] = 'nss2-mcs5(44)';
$infomcs[45] = 'nss2-mcs6(45)';

echo(" IGNITENET-MIB P2P-MIB ");

// Get radio data
$data_info = snmpwalk_cache_oid($device, '1.3.6.1.4.1.47307.1.4.2', [], "IGNITENET-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

$i = 0;
// Goes through the SNMP radio data
foreach ($data_info as $key => $value) {
    if ($data_info[$key]['mlRadioInfoEnabled'] == 'enabled') {
        $radio['radio_name']           = $data_info[$key]['mlRadioInfoFrequency'] / 1000 . "GHz";
        $radio['radio_status']         = $data_info[$key]['mlRadioInfoEnabled'];
        $radio['radio_loopback']       = ['NULL'];
        $radio['radio_tx_mute']        = ['NULL'];
        $radio['radio_tx_freq']        = $data_info[$key]['mlRadioInfoFrequency'] * 1000;
        $radio['radio_rx_freq']        = $data_info[$key]['mlRadioInfoFrequency'] * 1000;
        $radio['radio_tx_power']       = $data_info[$key]['mlRadioInfoTxPower'];
        $radio['radio_rx_level']       = $data_info[$key]['mlRadioInfoRSSILocal'];
        $radio['radio_e1t1_channels']  = ['NULL'];
        $radio['radio_bandwidth']      = ['NULL'];
        $radio['radio_modulation']     = $infomcs[$data_info[$key]['mlRadioInfoMCS']];
        $radio['radio_total_capacity'] = ['NULL'];
        $radio['radio_eth_capacity']   = ['NULL'];
        $radio['radio_rmse']           = ['NULL'];       // Convert to units
        $radio['radio_gain_text']      = ['NULL'];
        $radio['radio_carrier_offset'] = ['NULL'];
        $radio['radio_sym_rate_tx']    = ['NULL'];
        $radio['radio_sym_rate_rx']    = ['NULL'];
        $radio['radio_standard']       = ['NULL'];
        $radio['radio_cur_capacity']   = ['NULL'];


        print_debug_vars($radio);

        poll_p2p_radio($device, 'IGNITENET-MIB', $key, $radio);
    }
}

