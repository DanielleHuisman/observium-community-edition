<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package  observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$infomcs[0] = 'BPSK (0)';
$infomcs[1] = 'QPSK (1)';
$infomcs[2] = 'QPSK (2)';
$infomcs[3] = '16-QAM (3)';
$infomcs[4] = '16-QAM (4)';
$infomcs[5] = '64-QAM (5)';
$infomcs[6] = '64-QAM (6)';
$infomcs[7] = '64-QAM (7)';
$infomcs[8] = '256-QAM (8)';
$infomcs[9] = '256-QAM (9)';


echo(" MIMOSA-NETWORKS-BFIVE-MIB P2P-MIB ");

// Get radio data
$data_chain = snmpwalk_cache_oid($device, '1.3.6.1.4.1.43356.2.1.2.6.1.1', array(), "MIMOSA-NETWORKS-BFIVE-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
$data_stream = snmpwalk_cache_oid($device, '1.3.6.1.4.1.43356.2.1.2.6.2', array(), "MIMOSA-NETWORKS-BFIVE-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

$i=0;
// Goes through the SNMP radio data
foreach ($data_chain as $key=>$value) 
{            
  $radio['radio_name']     = $data_chain[$key]['mimosaCenterFreq'].' '.$data_chain[$key]['mimosaPolarization'];
  $radio['radio_status']   = 'ok';
  $radio['radio_loopback']   = array('NULL');
  $radio['radio_tx_mute']  = array('NULL');
  $radio['radio_tx_freq']  = $data_chain[$key]['mimosaCenterFreq']*1000;
  $radio['radio_rx_freq']  = $data_chain[$key]['mimosaCenterFreq']*1000;
  $radio['radio_tx_power']   = $data_chain[$key]['mimosaTxPower'];
  $radio['radio_rx_level']   = $data_chain[$key]['mimosaRxPower'];
  $radio['radio_e1t1_channels']  = array('NULL');
  $radio['radio_bandwidth']  = array('NULL');
  $radio['radio_modulation']   = $infomcs[$data_stream[$key]['mimosaTxMCS']];
  $radio['radio_total_capacity'] = array('NULL');
  $radio['radio_eth_capacity']   = array('NULL');
  $radio['radio_rmse']     = array('NULL');   // Convert to units
  $radio['radio_gain_text']  = array('NULL');
  $radio['radio_carrier_offset'] = array('NULL');
  $radio['radio_sym_rate_tx']  = array('NULL');
  $radio['radio_sym_rate_rx']  = array('NULL');
  $radio['radio_standard']   = array('NULL');
  $radio['radio_cur_capacity']   = $data_stream[$key]['mimosaTxPhy']*1000000;


  print_debug_vars($radio);

  poll_p2p_radio($device, 'IGNITENET-MIB', $key, $radio);
  
}

