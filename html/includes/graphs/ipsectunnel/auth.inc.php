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
  $tunnel = dbFetchRow("SELECT * FROM `ipsec_tunnels`WHERE `tunnel_id` = ?", array($vars['id']));

  if (is_numeric($tunnel['device_id']) && ($auth || device_permitted($tunnel['device_id'])))
  {
    $device = device_by_id_cache($tunnel['device_id']);

    if ($tunnel['tunnel_endhash'])
    {
      // New index
      $rrd_index = $tunnel['local_addr'] . '-' . $tunnel['peer_addr'] . '-' . $tunnel['tunnel_endhash'];
    } else {
      $rrd_index = $tunnel['peer_addr'];
    }
    $rrd_filename = get_rrd_path($device, "ipsectunnel-".$rrd_index.".rrd");

    $title  = generate_device_link($device);
    $title .= " :: IPSEC Tunnel :: " . escape_html($tunnel['peer_addr']);
    $auth = TRUE;
  }
}

// EOF
