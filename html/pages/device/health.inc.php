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


if (!$vars['metric']) {
    $vars['metric'] = "overview";
}
if (!$vars['view']) {
    $vars['view'] = "details";
}

$navbar['brand'] = "Health";
$navbar['class'] = "navbar-narrow";

$navbar['options']['overview']['icon'] = $config['icon']['overview'];
$navbar['options']['overview']['text'] = 'Overview';
$navbar['options']['overview']['url']  = generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'health' ]);
if ($vars['metric'] === 'overview') {
    $navbar['options']['overview']['class'] = 'active';
}

$health_menu = navbar_health_menu($device, $vars);
$navbar['options'] = array_merge($navbar['options'], $health_menu);

//$navbar['options']['graphs']['text']  = 'Graphs';
$navbar['options']['graphs']['icon']  = $config['icon']['graphs'];
$navbar['options']['graphs']['right'] = TRUE;

if ($vars['view'] === "graphs") {
    $navbar['options']['graphs']['class'] = 'active';
    $navbar['options']['graphs']['url']   = generate_url($vars, ['view' => "detail"]);
} else {
    $navbar['options']['graphs']['url'] = generate_url($vars, ['view' => "graphs"]);
}

print_navbar($navbar);
unset($navbar);

if (isset($config['sensor_types'][$vars['metric']]) || $vars['metric'] === "sensors") {
    include($config['html_dir'] . "/pages/device/health/sensors.inc.php");
} elseif (isset($config['counter_types'][$vars['metric']]) || $vars['metric'] === "counter") {
    include($config['html_dir'] . "/pages/device/health/counter.inc.php");
} elseif (is_alpha($vars['metric']) && is_file($config['html_dir'] . "/pages/device/health/" . $vars['metric'] . ".inc.php")) {
    include($config['html_dir'] . "/pages/device/health/" . $vars['metric'] . ".inc.php");
} else {

    // Overview
    echo generate_box_open();

    echo('<table class="table table-condensed table-striped table-hover ">');

    foreach ($health_menu as $type => $options) {
        $graph_title           = $options['text'];
        $graph_array['type']   = "device_" . $type;
        $graph_array['device'] = $device['device_id'];

        echo('<tr><td>');
        echo('<h3>' . $graph_title . '</h3>');
        print_graph_row($graph_array);
        echo('</td></tr>');
    }
    echo('</table>');

    echo generate_box_close();

}

register_html_title("Health");

// EOF
