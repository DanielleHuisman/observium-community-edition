<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Sections are printed in the order they exist in $config['graph_sections']
// Graphs are printed in the order they exist in $config['graph_types']

$graphs_sections = get_device_graphs_sections($device);
//print_vars($graphs_sections);

// Graphs navbar
$navbar['brand'] = "Graphs";
$navbar['class'] = "navbar-narrow";
foreach ($graphs_sections as $section => $graph) {
    $type = strtolower($section);
    if (!$vars['group']) {
        $vars['group'] = $type;
    }
    if ($vars['group'] == $type) {
        $navbar['options'][$section]['class'] = "active";
    }
    $navbar['options'][$section]['url']  = generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'graphs', 'group' => $type ]);
    $navbar['options'][$section]['text'] = $config['graph_sections'][$section] ?? nicecase($type);

    //print_vars($graph);
}

print_navbar($navbar);

echo generate_box_open();
echo('<table class="table table-condensed table-striped table-hover ">');

if ($vars['group'] === "custom" && isset($graphs_sections['custom'])) {
    // Custom OIDs
    $sql = "SELECT * FROM `oids_entries`";
    $sql .= " LEFT JOIN `oids` USING(`oid_id`)";
    $sql .= " WHERE `device_id` = ?";

    $custom_graphs = dbFetchRows($sql, [ $device['device_id'] ]);

    foreach ($custom_graphs as $graph) {
        $graph_array         = [];
        $graph_title         = $graph['oid_descr'];
        $graph_array['type'] = "customoid_graph";
        $graph_array['id']   = $graph['oid_entry_id'];

        echo('<tr><td>');

        echo('<h3>' . $graph_title . '</h4>');

        print_graph_row($graph_array);

        echo('</td></tr>');

    }

} elseif (isset($graphs_sections[$vars['group']])) {
    ksort($graphs_sections[$vars['group']], SORT_NUMERIC);
    $graph_enable = $graphs_sections[$vars['group']];

//  print_vars($graph_enable);
    foreach ($graph_enable as $graph) {
        $graph_array = [];
        //if ($graph_enable[$graph])
        //{
        $graph_title           = $config['graph_types']['device'][$graph]['descr'];
        $graph_array['type']   = "device_" . $graph;
        $graph_array['device'] = $device['device_id'];

        echo('<tr><td>');

        echo('<h3>' . $graph_title . '</h4>');

        print_graph_row($graph_array);

        echo('</td></tr>');
        //}
    }
}

echo('</table>');
echo generate_box_close();

register_html_title("Graphs");

// EOF
