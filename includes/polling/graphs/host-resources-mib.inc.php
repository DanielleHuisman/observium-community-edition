<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// HOST-RESOURCES-MIB::hrSystemNumUsers.0 = Gauge32: 0
// HOST-RESOURCES-MIB::hrSystemProcesses.0 = Gauge32: 69
$table_defs['HOST-RESOURCES-MIB']['hrSystemProcesses'] = array(
  'file'          => 'hr_processes.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'HOST-RESOURCES-MIB',
  'graphs'        => array('hr_processes'),
  'ds_rename'     => array('hrSystemProcesses' => 'procs'), // Just named procs
  'oids'          => array(
    'hrSystemProcesses' => array('descr' => 'Running Processes', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
  )
);

$table_defs['HOST-RESOURCES-MIB']['hrSystemNumUsers'] = array(
  'file'          => 'hr_users.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'HOST-RESOURCES-MIB',
  'graphs'        => array('hr_users'),
  'ds_rename'     => array('hrSystemNumUsers' => 'users'), // Just named users
  'oids'          => array(
    'hrSystemNumUsers' => array('descr' => 'Users Logged In', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
  )
);

// EOF
