<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

echo(" UBNT-AirFIBER-MIB ");

// Getting Radios

$radios_snmp = snmpwalk_cache_oid($device, "airFiberConfigIndex", array(), "UBNT-AirFIBER-MIB");

$oids = array('radioLinkMode', 'radioEnable', 'radioDuplex', 'radioLinkDistM', 'radioLinkState',
              'txFrequency', 'txPower', 'txCapacity', 'txFramesOK', 'txOctetsOK', 'txPauseFrames',
              'txErroredFrames', 'txValidUnicastFrames', 'txValidMulticastFrames', 'txValidBroadcastFrames',
              'rxFrequency', 'rxGain', 'rxCapacity', 'rxPower0', 'rxPower1', 'rxFramesOK', 'rxOctetsOK',
              'rxFrameCrcErr', 'rxAlignErr', 'rxPauseFrames', 'rxErroredFrames', 'rxValidUnicastFrames',
              'rxValidMulticastFrames', 'rxValidBroadcastFrames', 'rxDroppedMacErrFrames', 'rxTotalOctets', 'rxTotalFrames',
              'regDomain', 'linkName', 'linkUpTime', 'remoteMAC', 'remoteIP', 'curTXModRate');

// Goes through the SNMP radio data
foreach ($radios_snmp as $index => $radio)
{

  $get_oids = array();
  foreach ($oids as $oid)
  {
    $get_oids[] = $oid . '.' . $index;
  }

  $data = snmp_get_multi_oid($device, $get_oids, array(), 'UBNT-AirFIBER-MIB');
  $data = $data[$index];

  print_r($data);

  $radio['radio_name']           = $data['linkName'];
  $radio['radio_status']         = $data['radioLinkState'];
  $radio['radio_loopback']       = array('NULL');
  $radio['radio_tx_mute']        = array('NULL');
  $radio['radio_tx_freq']        = $data['txFrequency'] * 1000;
  $radio['radio_rx_freq']        = $data['rxFrequency'] * 1000;
  $radio['radio_tx_power']       = $data['txPower'];
  $radio['radio_rx_level']       = $data['rxPower0'];
  $radio['radio_e1t1_channels']  = array('NULL');
  $radio['radio_bandwidth']      = array('NULL');
  $radio['radio_modulation']     = $data['curTXModRate'];
  $radio['radio_total_capacity'] = $data['txCapacity'];
  $radio['radio_eth_capacity']   = array('NULL');
  $radio['radio_rmse']           = array('NULL');       // Convert to units
  $radio['radio_gain_text']      = $data['rxGain'];
  $radio['radio_carrier_offset'] = array('NULL');
  $radio['radio_sym_rate_tx']    = array('NULL');
  $radio['radio_sym_rate_rx']    = array('NULL');
  $radio['radio_standard']       = array('NULL');
  $radio['radio_cur_capacity']   = $data['txCapacity'];

  //print_debug_vars($radio);

  poll_p2p_radio($device, 'ubnt-airfiber-mib', $index, $radio);

}

