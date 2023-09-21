<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!is_intnum($vars['id'])) {
  return;
}

$vp = dbFetchRow("SELECT * FROM `juniAtmVp` as J, `ports` AS I WHERE J.juniAtmVp_id = ? AND I.port_id = J.port_id", [ $vars['id'] ]);

if ($auth || port_permitted($vp['port_id'])) {
  $port         = $vp;
  $device       = device_by_id_cache($vp['device_id']);

  $rrd_filename = get_rrd_path($device, "vp-" . $vp['ifIndex'] . "-" . $vp['vp_id'] . ".rrd");

  $auth         = TRUE;

  $graph_title   = device_name($device, TRUE);
  $graph_title   .= " :: Port " . $port['port_label_short'];
  $graph_title   .= " :: VP " . $vp['vp_id'];
}

// EOF
