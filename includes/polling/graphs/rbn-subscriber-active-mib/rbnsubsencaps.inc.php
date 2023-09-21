<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/**
 * .1.3.6.1.4.1.2352.2.27.1.2.2.1.2.1 = Gauge32: 605 subscribers
 * .1.3.6.1.4.1.2352.2.27.1.2.2.1.2.2 = Gauge32: 6047 subscribers
 * .1.3.6.1.4.1.2352.2.27.1.2.2.1.2.3 = Gauge32: 0 subscribers
 * .1.3.6.1.4.1.2352.2.27.1.2.2.1.2.4 = Gauge32: 0 subscribers
 * .1.3.6.1.4.1.2352.2.27.1.2.2.1.2.5 = Gauge32: 0 subscribers
 * .1.3.6.1.4.1.2352.2.27.1.2.2.1.2.6 = Gauge32: 0 subscribers
 * .1.3.6.1.4.1.2352.2.27.1.2.2.1.2.7 = Gauge32: 0 subscribers
 * .1.3.6.1.4.1.2352.2.27.1.2.2.1.2.12 = Gauge32: 0 subscribers
 * .1.3.6.1.4.1.2352.2.27.1.2.2.1.2.255 = Gauge32: 0 subscribers
 *
 * RBN-SUBSCRIBER-ACTIVE-MIB::rbnSubsEncapsCount.ppp = Gauge32: 606 subscribers
 * RBN-SUBSCRIBER-ACTIVE-MIB::rbnSubsEncapsCount.pppoe = Gauge32: 6048 subscribers
 * RBN-SUBSCRIBER-ACTIVE-MIB::rbnSubsEncapsCount.bridged1483 = Gauge32: 0 subscribers
 * RBN-SUBSCRIBER-ACTIVE-MIB::rbnSubsEncapsCount.routed1483 = Gauge32: 0 subscribers
 * RBN-SUBSCRIBER-ACTIVE-MIB::rbnSubsEncapsCount.multi1483 = Gauge32: 0 subscribers
 * RBN-SUBSCRIBER-ACTIVE-MIB::rbnSubsEncapsCount.dot1q1483 = Gauge32: 0 subscribers
 * RBN-SUBSCRIBER-ACTIVE-MIB::rbnSubsEncapsCount.dot1qEnet = Gauge32: 0 subscribers
 * RBN-SUBSCRIBER-ACTIVE-MIB::rbnSubsEncapsCount.clips = Gauge32: 0 subscribers
 * RBN-SUBSCRIBER-ACTIVE-MIB::rbnSubsEncapsCount.other = Gauge32: 0 subscribers
 */

$table_defs['RBN-SUBSCRIBER-ACTIVE-MIB']['rbnSubsEncapsCount'] = array(
  'table'         => 'rbnSubsEncapsCount',
  'numeric'       => '.1.3.6.1.4.1.2352.2.27.1.2.2.1.2',
  'mib'           => 'RBN-SUBSCRIBER-ACTIVE-MIB',
  'mib_dir'       => 'ericsson',
  'call_function' => 'snmpwalk_cache_bare_oid',
  'descr'         => 'Subscriber Encapsulation Count',
  'graphs'        => array('rbnSubsEncapsCount'),
  'ds_rename'     => array('rbnSubsEncapsCount.' => ''),
  'oids'          => array(
    'rbnSubsEncapsCount.ppp'         => array('numeric' => '1', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'rbnSubsEncapsCount.pppoe'       => array('numeric' => '2', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'rbnSubsEncapsCount.bridged1483' => array('numeric' => '3', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'rbnSubsEncapsCount.routed1483'  => array('numeric' => '4', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'rbnSubsEncapsCount.multi1483'   => array('numeric' => '5', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'rbnSubsEncapsCount.dot1q1483'   => array('numeric' => '6', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'rbnSubsEncapsCount.dot1qEnet'   => array('numeric' => '7', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'rbnSubsEncapsCount.clips'       => array('numeric' => '12', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'rbnSubsEncapsCount.other'       => array('numeric' => '255', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
  )
);


// EOF
