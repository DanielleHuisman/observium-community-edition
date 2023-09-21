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

include($config['html_dir'] . "/includes/graphs/device/auth.inc.php");

if ($auth && is_numeric($vars['mod']) && is_numeric($vars['chan'])) {

    $entity = dbFetchRow("SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalIndex` = ?", [$device['device_id'], $vars['mod']]);

    $rrd_filename = get_rrd_path($device, "c6kxbar-" . $vars['mod'] . "-" . $vars['chan'] . ".rrd");

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: " . $entity['entPhysicalName'] . " :: Fabric " . $vars['chan'];
}

// EOF
