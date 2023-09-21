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
 * GBOS-MIB::gbStatsCurConns.0 = Gauge32: 3046
 * GBOS-MIB::gbStatsPeakConns.0 = Gauge32: 87625
 * GBOS-MIB::gbStatsCurInConns.0 = Gauge32: 65
 * GBOS-MIB::gbStatsCurOutConns.0 = Gauge32: 2977
 */

$table_defs['GBOS-MIB']['gbStatistics'] = [
  'table'         => 'gbStatistics',
  'numeric'       => '.1.3.6.1.4.1.13559.1.3',
  'call_function' => 'snmp_get_multi',
  'mib'           => 'GBOS-MIB',
  'mib_dir'       => 'gta',
  'descr'         => 'GB-OS Connection Statistics',
  'graphs'        => ['gbStatistics-conns', 'gbStatistics-conns-inout'],
  'ds_rename'     => ['gbStats' => ''],
  'oids'          => [
    'gbStatsCurConns'    => ['numeric' => '1', 'descr' => 'Current Connections', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'gbStatsCurInConns'  => ['numeric' => '3', 'descr' => 'Current Inbound Connections', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'gbStatsCurOutConns' => ['numeric' => '4', 'descr' => 'Current Outbound Connections', 'ds_type' => 'GAUGE', 'ds_min' => '0']
  ]
];

// EOF
