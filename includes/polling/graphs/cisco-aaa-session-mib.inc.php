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
 * enterprises.cisco.ciscoMgmt.ciscoAAASessionMIB.casnMIBObjects.casnGeneral.casnTotalSessions.0 = Counter32: 10069504
 * enterprises.cisco.ciscoMgmt.ciscoAAASessionMIB.casnMIBObjects.casnGeneral.casnDisconnectedSessions.0 = Counter32: 0
 */

$table_defs['CISCO-AAA-SESSION-MIB']['casnActive'] = [
  'table'         => 'casnActive',
  'numeric'       => '.1.3.6.1.4.1.9.9.150.1.1',
  'call_function' => 'snmp_get_multi',
  'descr'         => 'Cisco AAA Active Statistics',
  'graphs'        => ['casnActive-sessions'],
  'mib'           => 'CISCO-AAA-SESSION-MIB',
  'mib_dir'       => 'cisco',
  'ds_rename'     => ['casn' => ''],
  'oids'          => [
    'casnActiveTableEntries' => ['numeric' => '1.0', 'descr' => 'Active Sessions', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
  ]
];

$table_defs['CISCO-AAA-SESSION-MIB']['casnGeneral'] = [
  'table'         => 'casnGeneral',
  'numeric'       => '.1.3.6.1.4.1.9.9.150.1.2',
  'call_function' => 'snmp_get_multi',
  'mib'           => 'CISCO-AAA-SESSION-MIB',
  'mib_dir'       => 'cisco',
  'descr'         => 'Cisco AAA General Statistics',
  'graphs'        => ['casnGeneral-total', 'casnGeneral-disconnected'],
  'ds_rename'     => ['casn' => ''],
  'oids'          => [
    'casnTotalSessions'        => ['numeric' => '1.0', 'descr' => 'Total Sessions', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'casnDisconnectedSessions' => ['numeric' => '1.0', 'descr' => 'Disconnected Sessions', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
  ]
];

// EOF
