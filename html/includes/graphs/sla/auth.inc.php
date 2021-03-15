<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

if (is_numeric($vars['id']))
{
  $sla = dbFetchRow("SELECT * FROM `slas` WHERE `sla_id` = ?", array($vars['id']));

  if (is_numeric($sla['device_id']) && ($auth || device_permitted($sla['device_id'])))
  {
    $device = device_by_id_cache($sla['device_id']);
    $title  = generate_device_link($device);
    $title .= " :: IP SLA :: " . escape_html($sla['sla_index']);

    $auth = TRUE;

    $mib_lower = strtolower($sla['sla_mib']);
    $index        = $mib_lower . '-' . $sla['sla_index'];
    $unit_text    = 'SLA '.$sla['sla_index'];
    if ($sla['sla_tag'])
    {
      $unit_text .= ' - '.$sla['sla_tag'];
    }
    if ($sla['sla_owner'])
    {
      $unit_text .= " (Owner: ". $sla['sla_owner'] .")";
      $index     .= '-' . $sla['sla_owner'];
    }

  $title_array   = array();
  $title_array[] = array('text' => $device['hostname'], 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'])));
  $title_array[] = array('text' => 'SLAs', 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'slas')));
  //echo(print_r($vars()));
  //echo($vars['id']);
  $title_array[] = array('text' => $config['sla_type_labels'][$sla['rtt_type']] . ' - ' . $sla['sla_tag'], 'url' => generate_url(array('page' => 'graphs', 'type' => 'sla_sla', 'id' => $vars['id'], 'device' => $device['device_id'])));

    $graph_title  = $device['hostname'] . ' :: ' . $unit_text; // hostname :: SLA XX
  }
}

// EOF
