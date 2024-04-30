<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$table_defs['MSERIES-ALARM-MIB']['alarmGeneral'] = [
  'file'          => 'MSERIES-ALARM-MIB-alarmGeneral.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'MSERIES-ALARM-MIB',
  'mib_dir'       => 'smartoptics',
  'table'         => 'alarmGeneral',
  'ds_rename'     => ['smartAlarmGeneralNumberActiveList' => 'active_alarms',
                      'smartAlarmGeneralNumberLogList'    => 'logged_alarms'],
  'graphs'        => ['mseries_alarms'],
  'oids'          => [
    'smartAlarmGeneralNumberActiveList' => ['descr' => 'Active Alarms', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'smartAlarmGeneralNumberLogList'    => ['descr' => 'Logged Alarms', 'ds_type' => 'GAUGE', 'ds_min' => '0'],

  ]
];

// EOF
