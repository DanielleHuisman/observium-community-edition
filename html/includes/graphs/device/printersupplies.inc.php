<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_options .= " -l 0 -E ";

$iter        = 0;
$rrd_options .= " COMMENT:'Supply level           Cur     Min      Max\\n'";
$colours     = "mixed";

// $supply_type will be set if we are included from a more specific graph
if (isset($supply_type)) {
    $rows = dbFetchRows("SELECT * FROM `printersupplies` where `device_id` = ? AND `supply_type` = ?", [$device['device_id'], $supply_type]);
} else {
    $rows = dbFetchRows("SELECT * FROM `printersupplies` where `device_id` = ?", [$device['device_id']]);
}

foreach ($rows as $supply) {
    // If colour was supplied by the device, pass it to the function, otherwise pass the description
    // and have the function try and figure it out from there.
    if ($supply['supply_colour'] != '') {
        $colour = toner_to_colour($supply['supply_colour'], $perc);
    } else {
        $colour = toner_to_colour($supply['supply_descr'], $perc);
    }

    // If no colour found by the toner to colour function, get one from the configured palette.
    if (!$colour['found']) {
        if (!$config['graph_colours'][$colours][$iter]) {
            $iter = 0;
        }
        $colour['left'] = $config['graph_colours'][$colours][$iter];
    }

    // Not sure for what this used
    //$hostname = get_device_hostname_by_device_id($supply['device_id']);
    $hostname = $device['hostname'];

    $descr        = rrdtool_escape($supply['supply_descr'], 16);
    $rrd_filename = get_rrd_path($device, "toner-" . $supply['supply_index'] . ".rrd");

    $rrd_filename_escape = rrdtool_escape($rrd_filename);

    $supply_id = $supply['supply_id'];

    $rrd_options .= " DEF:level$supply_id=$rrd_filename_escape:level:AVERAGE";
    $rrd_options .= " LINE2:level$supply_id#" . $colour['left'] . ":'" . $descr . "'";
    $rrd_options .= " GPRINT:level$supply_id:LAST:'%5.0lf%%'";
    $rrd_options .= " GPRINT:level$supply_id:MIN:'%5.0lf%%'";
    $rrd_options .= " GPRINT:level$supply_id:MAX:%5.0lf%%\\l";

    $iter++;
    $colour['left'] = NULL;
}

// EOF
