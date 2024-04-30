<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!$samehost) {
    if ($bg == OBS_COLOUR_LIST_A) {
        $bg = OBS_COLOUR_LIST_B;
    } else {
        $bg = OBS_COLOUR_LIST_A;
    }
}

$service_type = strtolower($service['service_type']);

if ($service['service_status'] == '0') {
    $status = "<span class=red><b>$service_type</b></span>";
} elseif ($service['service_status'] == '1') {
    $status = "<span class=green><b>$service_type</b></span>";
} elseif ($service['service_status'] == '2') {
    $status = "<span class=grey><b>$service_type</b></span>";
}

$message = trim($service['service_message']);
$message = str_replace("\n", "<br />", $message);

$since = time() - $service['service_changed'];
$since = format_uptime($since);

if ($service['service_checked']) {
    $checked = time() - $service['service_checked'];
    $checked = format_uptime($checked);
} else {
    $checked = "Never";
}

$mini_url = "graph.php?id=" . $service['service_id'] . "&amp;type=service_availability&amp;from=" . get_time('day') . "&amp;to=" . get_time('now') . "&amp;width=80&amp;height=20&amp;bg=efefef";

$popup = "onmouseover=\"return overlib('<div class=entity-title>" . $device['hostname'] . " - " . $service['service_type'];
$popup .= "</div><img src=\'graph.php?id=" . $service['service_id'] . "&amp;type=service_availability&amp;from=" . get_time('day') . "&amp;to=" . get_time('now') . "&amp;width=400&amp;height=125\'>";
$popup .= "', RIGHT" . $config['overlib_defaults'] . ");\" onmouseout=\"return nd();\"";

echo('
       <tr>');

if ($device_id) {
    if (!$samehost) {
        echo("<td valign=top width=250><span style='font-weight:bold;'>" . generate_device_link($device) . "</span></td>");
    } else {
        echo("<td></td>");
    }
}

echo("
         <td valign=top class=strong>
           $status
         </td>
         <td valign=top><a $popup><img src='$mini_url'></a></td>
         <td valign=top width=175>
           $since
         </td>
         <td valign=top>
           <span class=small>$message</span>
         </td>
       </tr>");

$i++;

// EOF
