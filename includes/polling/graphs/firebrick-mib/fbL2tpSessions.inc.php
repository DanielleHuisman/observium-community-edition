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

$table_defs['FIREBRICK-MIB']['fbL2tpSessionStats'] = [
  'table'         => 'fbL2tpSessionStats',
  'numeric'       => '3.6.1.4.1.24693.1701.1701.2',
  'call_function' => 'snmp_get_multi',
  'mib'           => 'FIREBRICK-MIB',
  'mib_dir'       => 'firebrick',
  'descr'         => 'Firebrick L2TP Session Statistics',
  'graphs'        => ['fbL2tpSessionStats'],
  'ds_rename'     => ['fbL2tpSessions' => ''],
  'no_index'      => TRUE,
  'oids'          => [
    'fbL2tpSessionsFree'        => ['numeric' => '0', 'descr' => 'Sessions in state FREE', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'fbL2tpSessionsWaiting'     => ['numeric' => '1', 'descr' => 'Sessions in state WAITING', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'fbL2tpSessionsOpening'     => ['numeric' => '2', 'descr' => 'Sessions in state OPENING', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'fbL2tpSessionsNegotiating' => ['numeric' => '3', 'descr' => 'Sessions in state NEGOTIATING', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'fbL2tpSessionsAuthPending' => ['numeric' => '4', 'descr' => 'Sessions in state AUTH-PENDING', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'fbL2tpSessionsStarted'     => ['numeric' => '5', 'descr' => 'Sessions in state STARTED', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'fbL2tpSessionsLive'        => ['numeric' => '6', 'descr' => 'Sessions in state LIVE', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'fbL2tpSessionsAcctPending' => ['numeric' => '7', 'descr' => 'Sessions in state ACCT-PENDING', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'fbL2tpSessionsClosing'     => ['numeric' => '8', 'descr' => 'Sessions in state CLOSING', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'fbL2tpSessionsClosed'      => ['numeric' => '9', 'descr' => 'Sessions in state CLOSED', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
  ]
];

?>
