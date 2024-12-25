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

if (is_numeric($vars['device']) && ($auth || device_permitted($vars['device']))) {
    $device      = device_by_id_cache($vars['device']);
    $auth        = TRUE;

    $graph_title = device_name($device, TRUE);
}

// EOF
