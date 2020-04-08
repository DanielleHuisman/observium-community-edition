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
  //$counter = dbFetchRow("SELECT * FROM `counters` WHERE `counter_id` = ?", array($vars['id']));
  $counter = get_entity_by_id_cache('counter', $vars['id']);

  if (is_numeric($counter['device_id']) && ($auth || is_entity_permitted($counter['counter_id'], 'counter') || device_permitted($counter['device_id'])))
  {

    $device = device_by_id_cache($counter['device_id']);

    //$rrd_filename = get_rrd_path($device, get_counter_rrd($device, $counter));

    $title  = generate_device_link($device);
    $title .= " :: Counters :: ";
    $auth = TRUE;
  }
}

// EOF
