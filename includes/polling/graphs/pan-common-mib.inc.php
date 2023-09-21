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

$table_defs['PAN-COMMON-MIB']['panSessionActive'] = [
  'file'          => 'panos-sessions.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'PAN-COMMON-MIB',
  'graphs'        => ['panos_sessions'],
  'ds_rename'     => ['panSessionActive' => 'sessions'], // Just named procs
  'oids'          => [
    'panSessionActive' => ['descr' => 'Active Sessions', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
  ]
];

$table_defs['PAN-COMMON-MIB']['panGPGWUtilizationActiveTunnels'] = [
  'file'          => 'panos-gptunnels.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'PAN-COMMON-MIB',
  'graphs'        => ['panos_gptunnels'],
  'ds_rename'     => ['panGPGWUtilizationActiveTunnels' => 'gptunnels'], // Just named procs
  'oids'          => [
    'panGPGWUtilizationActiveTunnels' => ['descr' => 'Active GP Tunnels', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
  ]
];

// EOF
