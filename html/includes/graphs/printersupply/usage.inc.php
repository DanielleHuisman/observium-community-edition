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

$scale_min = "0";
$scale_max = "100";

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_options .= " COMMENT:'                                 Cur     Max\\n'";

if ($supply['supply_colour'] != '') {
    $colour = toner_to_colour($supply['supply_colour'], $perc);
} else {
    $colour = toner_to_colour($supply['supply_descr'], $perc);
}

if ($colour['left'] == NULL) {
    $colour['left'] = "CC0000";
}

$descr = rrdtool_escape($supply['supply_descr'], 26);

$background = get_percentage_colours(100 - $supply['supply_value']);

$rrd_options .= " DEF:level" . $supply['supply_id'] . "=" . $rrd_filename_escape . ":level:AVERAGE ";

$rrd_options .= " LINE1:level" . $supply['supply_id'] . "#" . $colour['left'] . ":'" . $descr . "' ";

$rrd_options .= " AREA:level" . $supply['supply_id'] . "#" . $background['right'] . ":";
$rrd_options .= " GPRINT:level" . $supply['supply_id'] . ":LAST:'%5.0lf%%'";
$rrd_options .= " GPRINT:level" . $supply['supply_id'] . ":MAX:%5.0lf%%\\l";

// EOF
