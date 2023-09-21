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

// Start device panel

print_device_header($device, ['no_graphs' => TRUE]);

include($config['html_dir'] . "/pages/device/overview/information_extended.inc.php");

echo generate_box_open();

// Only show graphs for device_permitted(), don't show device graphs to users who can only see a single entity.

if (isset($config['os'][$device['os']]['graphs'])) {
    $graphs = $config['os'][$device['os']]['graphs'];
} elseif (isset($device['os_group'], $config['os'][$device['os_group']]['graphs'])) {
    $graphs = $config['os'][$device['os_group']]['graphs'];
} else {
    // Default group
    $graphs = $config['os_group']['default']['graphs'];
}

$graph_array           = [];
$graph_array['height'] = "100";
$graph_array['width']  = "213";
$graph_array['to']     = get_time();
$graph_array['device'] = $device['device_id'];
$graph_array['type']   = "device_bits";
$graph_array['from']   = get_time('day');
$graph_array['legend'] = "no";
$graph_array['bg']     = "FFFFFF00";

// Preprocess device graphs array
$graphs_enabled = [];
foreach ($device['graphs'] as $graph) {
    if ($graph['enabled']) {
        $graphs_enabled[] = $graph['graph'];
    }
}
//r($graphs);
//r($graphs_enabled);

echo '<div class="row">';

foreach ($graphs as $entry) {
    if ($entry && in_array(str_replace('device_', '', $entry), $graphs_enabled, TRUE)) {
        $graph_array['type'] = $entry;

        if (preg_match(OBS_PATTERN_GRAPH_TYPE, $entry, $graphtype)) {
            $type    = $graphtype['type'];
            $subtype = $graphtype['subtype'];

            $text = $config['graph_types'][$type][$subtype]['descr'];
        } else {
            $text = nicecase($entry); // Fallback to the type itself as a string, should not happen!
        }

        echo('<div class="col-xl-6">');
        echo("<div style='padding: 0px; text-align: center;'><h4>$text</h4></div>");
        print_graph_popup($graph_array);
        echo("</div>");
    }
}
echo '</div>';

echo generate_box_close();
// End panel

unset($graphs, $graph_array, $graphs_enabled);

// EOF
