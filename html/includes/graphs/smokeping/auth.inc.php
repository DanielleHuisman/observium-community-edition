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

if (is_numeric($vars['device']) && ($auth || device_permitted($vars['device']))) {
    $device      = device_by_id_cache($vars['device']);
    $auth        = TRUE;

    $graph_title = device_name($device, TRUE);
}

// EOF
