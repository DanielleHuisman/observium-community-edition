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

// Sections are printed in the order they exist in $config['graph_sections']
// Graphs are printed in the order they exist in $config['graph_types']

$link_array = ['page'   => 'device',
               'device' => $device['device_id'],
               'tab'    => 'graphs'];

$graphs_sections = [];

foreach ($device['graphs'] as $entry) {
    if (isset($entry['enabled']) && !$entry['enabled']) {
        continue;
    } // Skip disabled graphs

    $section = $config['graph_types']['device'][$entry['graph']]['section'];

    if (in_array($section, $config['graph_sections'])) {
        // Collect only enabled and exists graphs
        //$graphs_sections[$section][$entry['graph']] = $entry['enabled'];
        if (isset($config['graph_types']['device'][$entry['graph']]['order']) && is_numeric($config['graph_types']['device'][$entry['graph']]['order'])) {
            $order = $config['graph_types']['device'][$entry['graph']]['order'];
        } else {
            $order = 999; // Set high order for unordered graphs
        }
        while (isset($graphs_sections[$section][$order])) {
            $order++;
        }
        $graphs_sections[$section][$order] = $entry['graph'];
    }
}

if (OBSERVIUM_EDITION !== 'community') {
    // Custom OIDs
    $sql = "SELECT * FROM `oids_entries`";
    $sql .= " LEFT JOIN `oids` USING(`oid_id`)";
    $sql .= " WHERE `device_id` = ?";

    $custom_graphs = dbFetchRows($sql, [$device['device_id']]);

    if (count($custom_graphs)) {
        $graphs_sections['custom'] = TRUE;
    }
}

$navbar['brand'] = "Graphs";
$navbar['class'] = "navbar-narrow";

// Set sections order
$graphs_sections_sorted = [];
foreach ($config['graph_sections'] as $section) {
    if (isset($graphs_sections[$section])) {
        $graphs_sections_sorted[$section] = $graphs_sections[$section];
        unset($graphs_sections[$section]);
    }
}
$graphs_sections = array_merge($graphs_sections_sorted, $graphs_sections);
//print_vars($graphs_sections);
unset($graphs_sections_sorted);

foreach ($graphs_sections as $section => $graph) {
    $type = strtolower($section);
    if (empty($config['graph_sections'][$section])) {
        $text = nicecase($type);
    } else {
        $text = $config['graph_sections'][$section];
    }
    if (!$vars['group']) {
        $vars['group'] = $type;
    }
    if ($vars['group'] == $type) {
        $navbar['options'][$section]['class'] = "active";
    }
    $navbar['options'][$section]['url']  = generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'graphs', 'group' => $type]);
    $navbar['options'][$section]['text'] = $text;

    //print_vars($graph);
}

print_navbar($navbar);

echo generate_box_open();
echo('<table class="table table-condensed table-striped table-hover ">');

if ($vars['group'] === "custom" && $graphs_sections['custom']) {
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
