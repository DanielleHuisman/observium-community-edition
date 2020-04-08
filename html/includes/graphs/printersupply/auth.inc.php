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
  $supply = dbFetchRow("SELECT * FROM `printersupplies` WHERE `supply_id` = ?", array($vars['id']));

  if (is_numeric($supply['device_id']) && ($auth || device_permitted($supply['device_id'])))
  {
    $device = device_by_id_cache($supply['device_id']);
    $rrd_filename  = get_rrd_path($device, "toner-" . $supply['supply_index'] . ".rrd");

    $title  = generate_device_link($device);
    $title .= " :: Toner :: " . escape_html($supply['supply_descr']);
    $auth = TRUE;
  }
}

// EOF
