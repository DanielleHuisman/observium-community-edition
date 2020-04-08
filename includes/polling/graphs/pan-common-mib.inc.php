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

// FIXME definite candidate for MIB definition!

$graph = 'panos_sessions';
if (!isset($graphs_db[$graph]) || $graphs_db[$graph] === TRUE)
{
  $session_count = snmp_get($device, 'panSessionActive.0', '-OQUvs', 'PAN-COMMON-MIB');

  if (is_numeric($session_count))
  {
    rrdtool_update_ng($device, 'panos-sessions', array('sessions' => $session_count));

    $graphs['panos_sessions'] = TRUE;
  }
}

$graph = 'panos_gptunnels';
//$graphs[$graph] = FALSE;

if (!isset($graphs_db[$graph]) || $graphs_db[$graph] === TRUE)
{
  $gptunnels = snmp_get($device, 'panGPGWUtilizationActiveTunnels.0', '-OQUvs', 'PAN-COMMON-MIB');

  if (is_numeric($gptunnels))
  {
    $rrd_filename  = 'panos-gptunnels.rrd';

    rrdtool_create($device, $rrd_filename, ' DS:gptunnels:GAUGE:600:0:100000000 ');
    rrdtool_update($device, $rrd_filename, 'N:'.$gptunnels);

    $graphs[$graph] = TRUE;
  }
}

unset($graph, $session_count, $gptunnels);

// EOF
