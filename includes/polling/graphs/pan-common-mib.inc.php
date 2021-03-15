<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$table_defs['PAN-COMMON-MIB']['panSessionActive'] = array(
  'file'          => 'panos-sessions.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'PAN-COMMON-MIB',
  'graphs'        => array('panos_sessions'),
  'ds_rename'     => array('panSessionActive' => 'sessions'), // Just named procs
  'oids'          => array(
    'panSessionActive' => array('descr' => 'Active Sessions', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
  )
);

$table_defs['PAN-COMMON-MIB']['panGPGWUtilizationActiveTunnels'] = array(
  'file'          => 'panos-gptunnels.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'PAN-COMMON-MIB',
  'graphs'        => array('panos_gptunnels'),
  'ds_rename'     => array('panGPGWUtilizationActiveTunnels' => 'gptunnels'), // Just named procs
  'oids'          => array(
    'panGPGWUtilizationActiveTunnels' => array('descr' => 'Active GP Tunnels', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
  )
);

// EOF
