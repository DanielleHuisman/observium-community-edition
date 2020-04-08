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

if (is_numeric($vars['id']))
{
  $snatpool = dbFetchRow("SELECT * FROM `lb_snatpools` AS I, `devices` AS D WHERE I.snatpool_id = ? AND I.device_id = D.device_id", array($vars['id']));

  if (is_numeric($snatpool['device_id']) && ($auth || device_permitted($snatpool['device_id'])))
  {
    $device = device_by_id_cache($snatpool['device_id']);

    $rrd_filename = get_rrd_path($device, "lb-snatpool-" . $snatpool['snatpool_name']);

    $title_array   = array();
    $title_array[] = array('text' => $device['hostname'], 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'])));
    $title_array[] = array('text' => 'F5 SNAT Pool', 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'lb_snatpools')));
    $title_array[] = array('text' => $snatpool['snatpool_name']   , 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'lb_snatpools', 'snatpool' => $snatpool['snatpool_id'])));

    $auth = TRUE;

  }
}

// EOF
