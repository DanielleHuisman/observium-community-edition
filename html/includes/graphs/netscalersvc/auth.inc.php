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

  $svc = dbFetchRow("SELECT * FROM `netscaler_services` AS I, `devices` AS D WHERE I.svc_id = ? AND I.device_id = D.device_id", array($vars['id']));

  if (is_numeric($svc['device_id']) && ($auth || device_permitted($svc['device_id'])))
  {
    $device = device_by_id_cache($svc['device_id']);

    $rrd_filename = get_rrd_path($device, "nscaler-svc-".$svc['svc_name'].".rrd");

    $title_array   = array();
    $title_array[] = array('text' => $device['hostname'], 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'])));
    $title_array[] = array('text' => 'Netscaler Service', 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_services')));
    $title_array[] = array('text' => $svc['svc_label']   , 'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_services', 'svc' => $svc['svc_id'])));

    //$title  = generate_device_link($device);
    //$title .= " :: Netscaler VServer :: " . escape_html($svc['svc_name']);
    $auth = TRUE;
  }
}

// EOF
