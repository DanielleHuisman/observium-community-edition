<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

$table_defs['RAISECOM-SYSTEM-MIB']['raisecomCpuTotalProcNum'] = [
  'file'          => 'hr_processes.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'RAISECOM-SYSTEM-MIB',
  'graphs'        => ['hr_processes'],
  'ds_rename'     => ['raisecomCpuTotalProcNum' => 'procs'], // Just named procs
  'oids'          => [
    'raisecomCpuTotalProcNum' => ['descr' => 'Running Processes', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
  ]
];

// EOF
