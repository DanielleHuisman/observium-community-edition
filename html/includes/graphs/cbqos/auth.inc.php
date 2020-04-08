<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// $cbqos = dbFetchRows("SELECT * FROM `ports_cbqos` WHERE `port_id` = ?", array($port['port_id']));

if (is_numeric($vars['id']))
{
  $cbqos = dbFetchRow("SELECT * FROM `ports_cbqos` WHERE `cbqos_id` = ?", array($vars['id']));

  if (is_numeric($cbqos['device_id']) && ($auth || device_permitted($cbqos['device_id'])))
  {
    $device = device_by_id_cache($cbqos['device_id']);
    $port   = get_port_by_id_cache($cbqos['port_id']);

    $rrd_filename = get_rrd_path($device, "cbqos-".$cbqos['policy_index']."-".$cbqos['object_index']);
    $title  = generate_device_link($device);
    $title .= " :: CBQoS :: " . $cbqos['policy_index']."-".$cbqos['object_index'];
    $auth = TRUE;
  }
}

$title_array   = array();
$title_array[] = array('text' => $device['hostname'], 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'])));
$title_array[] = array('text' => $port['port_label'], 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'])));
$title_array[] = array('text' => 'CBQoS', 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'port', 'port' => $port['port_id'], 'view' => 'cbqos')));
$title_array[] = array('text' => $cbqos['policy_name'] . '/' . $cbqos['object_name']   , 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'port', 'port' => $port['port_id'], 'view' => 'cbqos')));

// EOF

