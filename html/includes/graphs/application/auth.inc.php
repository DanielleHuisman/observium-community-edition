<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) Adam Armstrong
 *
 */

if (is_intnum($vars['id']) && ($auth || application_permitted($vars['id']))) {
    $app    = get_application_by_id($vars['id']);
    $device = device_by_id_cache($app['device_id']);

    $auth   = TRUE;

    $graph_title   = device_name($device, TRUE);
}

// EOF
