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

// HOST-RESOURCES-MIB::hrSystemNumUsers.0 = Gauge32: 0
// HOST-RESOURCES-MIB::hrSystemProcesses.0 = Gauge32: 69
$table_defs['HOST-RESOURCES-MIB']['hrSystemProcesses'] = [
  'file'          => 'hr_processes.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'HOST-RESOURCES-MIB',
  'graphs'        => ['hr_processes'],
  'ds_rename'     => ['hrSystemProcesses' => 'procs'], // Just named procs
  'oids'          => [
    'hrSystemProcesses' => ['descr' => 'Running Processes', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
  ]
];

$table_defs['HOST-RESOURCES-MIB']['hrSystemNumUsers'] = [
  'file'          => 'hr_users.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'HOST-RESOURCES-MIB',
  'graphs'        => ['hr_users'],
  'ds_rename'     => ['hrSystemNumUsers' => 'users'], // Just named users
  'oids'          => [
    'hrSystemNumUsers' => ['descr' => 'Users Logged In', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
  ]
];

// EOF
