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

if ($auth || device_permitted($device['device_id'])) {
    $graph_title = device_name($device, TRUE);
    $auth        = TRUE; ///FIXME. Who? --mike
}

//EOL
