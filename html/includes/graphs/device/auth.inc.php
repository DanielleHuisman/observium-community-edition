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

if ($auth || device_permitted($device['device_id'])) {
    $graph_title = device_name($device, TRUE);
    $auth        = TRUE; ///FIXME. Who? --mike
}

//EOL
