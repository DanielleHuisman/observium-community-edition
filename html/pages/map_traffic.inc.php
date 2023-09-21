<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

register_html_resource("js", "popper.core.js");
register_html_resource("js", "tippy.js");
register_html_resource("js", "cytoscape.min.js");
//register_html_resource("js", "cola.min.js");
//register_html_resource("js", "cytoscape-cola.js");
register_html_resource("js", "shim.min.js");
register_html_resource("js", "layout-base.js");
register_html_resource("js", "cose-base.js");
//register_html_resource("js", "cytoscape-cose-bilkent.js");
register_html_resource("js", "cytoscape-fcose.js");
register_html_resource("js", "cytoscape-layout-utilities.js");
//register_html_resource("js", "cytoscape-dagre.js");
register_html_resource("js", "cytoscape-popper.js");
//register_html_resource("js", "cytoscape-qtip.js");
//register_html_resource("css", "tippy-translucent.css");

$navbar['class'] = 'navbar-narrow';
$navbar['brand'] = 'Traffic Map';

$options = ['port_labels' => 'Port Labels'];

foreach ($options as $option => $label) {
    if (isset($vars[$option]) && $vars[$option]) {
        $navbar['options'][$option]['class'] = 'active';
        $navbar['options'][$option]['url']   = generate_url($vars, [$option => NULL]);
    } else {
        $navbar['options'][$option]['url'] = generate_url($vars, [$option => 'YES']);
    }
    $navbar['options'][$option]['text'] = $label;
    $navbar['options'][$option]['icon'] = $config['icon']['cef'];
}

// 'Devices' navbar menu
$navbar['options']['devices']['text']  = 'Devices';
$navbar['options']['devices']['class'] = 'dropdown-scrollable';
$navbar['options']['devices']['icon']  = $config['icon']['device'];
foreach (generate_form_values('device') as $device_id => $device) {
    $navbar['options']['devices']['suboptions'][$device_id]['text'] = $device['name'];
    $navbar['options']['devices']['suboptions'][$device_id]['url']  = generate_url($vars, ['group' => NULL, 'device_id' => $device_id]);
    if ($vars['device_id'] == $device_id) {
        $navbar['options']['devices']['text']                            .= ' (' . $device['name'] . ')';
        $navbar['options']['devices']['suboptions'][$device_id]['class'] = 'active';
    }
}

// 'Groups' navbar menu
$navbar['options']['groups']['text']  = 'Groups';
$navbar['options']['groups']['class'] = 'dropdown-scrollable';
$navbar['options']['groups']['icon']  = $config['icon']['group'];
$groups                               = get_groups_by_type();

$groups['device'] = isset($groups['device']) && is_array($groups['device']) ? $groups['device'] : [];
$groups['port']   = isset($groups['port']) && is_array($groups['port']) ? $groups['port'] : [];

foreach (array_merge($groups['device'], $groups['port']) as $group) {
    $group_id                                                     = $group['group_id'];
    $navbar['options']['groups']['suboptions'][$group_id]['text'] = $group['group_name'];
    $navbar['options']['groups']['suboptions'][$group_id]['icon'] = ($group['entity_type'] == "device" ? $config['icon']['device'] : $config['icon']['port']);
    $navbar['options']['groups']['suboptions'][$group_id]['url']  = generate_url($vars, ['group_id' => $group_id, 'device_id' => NULL]);
    if ($vars['group_id'] == $group_id) {
        $navbar['options']['groups']['text']  .= ' (' . $group['group_name'] . ')';
        $navbar['options']['groups']['class'] = 'active';
    }
}

print_navbar($navbar);
unset($navbar);

// FIXME. Move to html/includes/print/neighbours.inc.php
function get_neighbour_map($vars)
{
    global $cache;

    $where_array   = [];
    $where_array[] = generate_query_permitted('device');

    if (isset($vars['group_id'])) {

        $group = get_group_by_id($vars['group_id']);

        //r($group);

        $title = $group['group_name'];

        if ($group['entity_type'] == "port") {
            $port_id_list = get_group_entities($vars['group_id'], 'port');
            if (count($port_id_list) > 0) {
                $where_array[] = '(' . generate_query_values_ng($port_id_list, 'port_id') . ' OR ' . generate_query_values_ng($port_id_list, 'remote_port_id') . ')';
            } else {
                print_error("Group contains no entities.");
            }
        } elseif ($group['entity_type'] == "device") {
            $device_id_list = get_group_entities($vars['group_id'], 'device');
            if (count($device_id_list) > 0) {
                $where_array[] = '(' . generate_query_values_ng($device_id_list, 'device_id') . ' OR ' . generate_query_values_ng($device_id_list, 'remote_device_id') . ')';
            } else {
                print_error("Group contains no entities.");
            }
        }
    } elseif (isset($vars['device_id']) && $vars['device_id']) {
        //$where_array[] = "(`device_id` = '".dbEscape($vars['device_id'])."' OR `remote_device_id` = '".dbEscape($vars['device_id'])."')";
        $where_array[] = '(' . generate_query_values_ng($vars['device_id'], 'device_id') . ' OR ' . generate_query_values_ng($vars['device_id'], 'remote_device_id') . ')';
    }

    //r($where_array);
    
    $query = "SELECT * FROM `neighbours` WHERE `active` = '1' " . generate_where_clause($where_array);

    //r($query);

    $neighbours = dbFetchRows($query);

    $device_list = [];
    $port_list   = [];
    $nodes = [];
    $edges = [];

    // Build device_id and port_id lists for cache
    foreach ($neighbours as $neighbour) {
        $device_list[$neighbour['device_id']] = $neighbour['device_id'];
        if (is_numeric($neighbour['remote_device_id'])) {
            $device_list[$neighbour['remote_device_id']] = $neighbour['remote_device_id'];
        }
        $port_list[$neighbour['port_id']] = $neighbour['port_id'];
        if (is_numeric($neighbour['remote_port_id'])) {
            $port_list[$neighbour['remote_port_id']] = $neighbour['remote_port_id'];
        }
    }
    // Pre-populate cache with device and port info
    cache_entities_by_id('device', $device_list);
    cache_entities_by_id('port', $port_list);

    //r(count($cache['device']));
    //r(count($cache['port']));
    foreach ($neighbours as $neighbour) {

        // Is this a link to a known device? Do we have it already?
        if (is_numeric($neighbour['remote_port_id']) && $neighbour['remote_port_id']) {

            $remote_port = get_port_by_id_cache($neighbour['remote_port_id']);

            // Do we have this source device already?
            if (!isset($devices[$neighbour['device_id']])) {
                $devices[$neighbour['device_id']] = device_by_id_cache($neighbour['device_id']);
            }

            // Suppress links to unknown devices
            //$neighbour['remote_device_id'] = get_device_id_by_port_id($neighbour['remote_port_id']);
            if (!is_numeric($neighbour['remote_device_id'])) {
                continue;
            }

            // Suppress links to self
            if ($neighbour['remote_device_id'] == $neighbour['device_id']) {
                continue;
            }

            // Suppress duplicate links from other protocols and from other end of link
            if (isset($link_exists[$neighbour['remote_port_id'] . '-' . $neighbour['port_id']]) ||
                isset($link_exists[$neighbour['port_id'] . '-' . $neighbour['remote_port_id']])) {
                continue;
            }

            if (!isset($devices[$neighbour['remote_device_id']])) {
                $devices[$neighbour['remote_device_id']] = device_by_id_cache($neighbour['remote_device_id']);
            }

            $port = get_port_by_id_cache($neighbour['port_id']);

            $port['percent'] = max($port['ifInOctets_perc'], $port['ifOutOctets_perc']);

            $in_colour  = percent_colour($port['ifInOctets_perc'], 160);
            $out_colour = percent_colour($port['ifOutOctets_perc'], 160);

            // Out is from perspective of local node
            $gradient = $out_colour . ' ' . $out_colour . ' ' . $in_colour . ' ' . $in_colour;

            $edges[$neighbour['neighbour_id']] = [
                'source'     => 'd' . $neighbour['device_id'],
                'target'     => 'd' . $neighbour['remote_device_id'],
                'percent'    => $port['percent'],
                'percentin'  => $port['ifInOctets_perc'],
                'percentout' => $port['ifOutOctets_perc'],
                'gradient'   => $gradient,
                'colourin'   => $in_colour,
                'colourout'  => $out_colour,
                'popupurl'   => 'ajax/entity_popup.php?entity_type=port&entity_id=' . $port['port_id'],
                //'label' => $port['percent'].'%'
            ];

            if (isset($vars['port_labels'])) {
                // Labels as in/out traffic
                //$in_label  = format_number($port['ifInOctets_rate'] * 8);
                //$out_label = format_number($port['ifOutOctets_rate'] * 8);

                // labels as port_label_short
                $out_label = $port['port_label_short'];
                $in_label  = $remote_port['port_label_short'];

                $edges[$neighbour['neighbour_id']]['labelin']  = $in_label;
                $edges[$neighbour['neighbour_id']]['labelout'] = $out_label;
            }

            if (is_numeric($neighbour['remote_port_id'])) {
                $link_exists[$port['port_id'] . '-' . $neighbour['remote_port_id']] = TRUE;
            }

        } else {
            $external_id             = string_to_id($neighbour['remote_hostname']);
            $externals[$external_id] = ['id'    => $external_id,
                                        'label' => $neighbour['remote_hostname']];
        }
    }

    foreach ($devices as $device) {
        $id         = 'd' . $device['device_id'];
        $parent_id  = 'l' . string_to_id($device['location']);
        $nodes[$id] = ['id'       => $id,
                       'label'    => short_hostname($device['hostname']),
                       'popupurl' => 'ajax/entity_popup.php?entity_type=device&entity_id=' . $device['device_id'],
                       'parent'   => $parent_id];

        // Add parent nodes
        if (!isset($parents[$parent_id])) {
            $nodes[$parent_id] = ['id'    => $parent_id,
                                  'label' => $device['location']];
        }
    }

    return [ 'nodes' => $nodes, 'edges' => $edges, 'external_nodes' => $externals ];

}

$map = get_neighbour_map($vars);

?>

<style>
    .tippy-tooltip.translucent-theme {
        background-color: tomato;
        color: yellow;
    }
</style>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        var settings = {
            name: 'fcose',
            animate: 'end',
            animationEasing: 'ease-in-out',
            animationDuration: 250,
            //randomize: true,
            quality: "proof",
            fit: true,
            padding: 30,
            nodeDimensionsIncludeLabels: true,
            uniformNodeDimensions: false,
            packComponents: true,
            step: "all",
            samplingType: true,
            sampleSize: 25,
            nodeSeparation: 100,
            piTol: 0.0000001,
            nodeRepulsion: node => 4500,
            idealEdgeLength: edge => 45,
            edgeElasticity: edge => 0.45,
            nestingFactor: 0.1,
            numIter: 4500,
            tile: true,
            tilingPaddingVertical: 10,
            tilingPaddingHorizontal: 10,
            gravity: 0.25,
            gravityRangeCompound: 1.5,
            gravityCompound: 1.0,
            gravityRange: 1.8,
            initialEnergyOnIncremental: 0.3,
        };

        var cy = window.cy = cytoscape({
            container: document.getElementById('cy'),

            layout: settings,

            wheelSensitivity: 0.25,
            pixelRatio: 'auto',

            style: [
                {
                    selector: 'node',
                    style: {
                        'label': 'data(id)',
                    }
                },
                {
                    selector: 'node:parent',
                    style: {
                        'padding': 10,
                        'shape': 'roundrectangle',
                        'border-width': 0,
                        'background-color': '#c5c5c5',
                    },
                },
                {
                    selector: 'node:parent',
                    css: {
                        'background-opacity': 0.333
                    }
                },
                {
                    selector: 'edge',
                    'style': {
                        'target-arrow-shape': 'square',
                        'source-arrow-shape': 'square',
                        'curve-style': 'bezier',
                        'edge-text-rotation': "autorotate",
                        'source-text-rotation': "autorotate",
                        'target-text-rotation': "autorotate",
                        'width': 4,
                    }
                },
                {
                    selector: '[colourin]',
                    style: {
                        'source-arrow-color': 'data(colourout)',
                        'target-arrow-color': 'data(colourin)',
                    }
                },
                {
                    selector: '[name]',
                    style: {
                        'label': 'data(name)'
                    }
                },
                {
                    selector: '[label]',
                    style: {
                        'label': 'data(label)',
                        'text-background-color': '#c5c5c5',
                        'edge-text-rotation': "autorotate"
                    }
                },
                {
                    selector: '[label]',
                    css: {
                        'text-background-opacity': '0.333',
                        'text-background-padding': '2px',
                        'text-background-shape': 'roundrectangle',
                    }
                },
                {
                    selector: "node:childless",
                    style: {
                        'background-fit': 'cover',
                        'background-image': '/img/router.png',
                        //'background-image-opacity': 0.25,
                        //'background-color': '#FF0000',
                        //'border-width': 2,
                        //'border-color': '#c5c5c5',
                        //'border-opacity': 0.5,
                    }
                },
                {
                    selector: "[labelin]",
                    style: {
                        'target-label': 'data(labelin)',
                        'target-text-offset': '30px',
                        'text-halign': 'right',
                        'text-valign': 'center',
                        //'text-margin-x': '-10px',
                        //'text-margin-y': '-10px',
                        //'text-background-color': '#c5c5c5',
                        //'text-background-opacity': '0.5',
                        //'text-background-padding': '2px',
                        //'text-background-shape': 'roundrectangle',
                    }
                },
                {
                    selector: "[labelout]",
                    style: {
                        'source-label': 'data(labelout)',
                        'source-text-offset': '30px',
                        'text-halign': 'right',
                        'text-valign': 'center',
                        //'text-margin-x': '-10px',
                        //'text-margin-y': '-10px',
                        //'text-background-color': '#c5c5c5',
                        //'text-background-opacity': '0.5',
                        //'text-background-padding': '2px',
                        //'text-background-shape': 'roundrectangle',
                    }
                },
                {
                    selector: 'edge',
                    style: {
                        'line-fill': 'linear-gradient',
                        'line-gradient-stop-colors': 'data(gradient)',
                        'line-gradient-stop-positions': '0% 40% 50% 100%',
                        //'text-background-opacity': '0.5',
                        //'text-background-color': '#555555',
                        'curve-style': 'bezier',
                    }
                }
            ],


            <?php

            echo 'elements: {' . PHP_EOL;
            echo "  'nodes': [ " . PHP_EOL;
            foreach ($map['nodes'] as $node) {
                echo "{ data: " . safe_json_encode($node) . " }," . PHP_EOL;
            }
            echo "]," . PHP_EOL;

            echo "  'edges': [ " . PHP_EOL;
            foreach ($map['edges'] as $edge) {
                echo "{ data: " . safe_json_encode($edge) . " }," . PHP_EOL;
            }
            echo '],' . PHP_EOL;
            echo '}' . PHP_EOL;

            ?>

        });

        document.getElementById("rearrangeButton").addEventListener("click", function () {
            var layout = cy.layout(settings);
            layout.run();
        });

        function makePopper(ele) {
            let ref = ele.popperRef();

            // FIXME - need some delay, otherwise it floods requests when moving mouse over large network
            if (ele.data('popupurl')) {
                ele.tippy = tippy(document.createElement('div'), {
                    // popperInstance will be available onCreate
                    //theme: 'translucent',
                    allowHTML: true,
                    followCursor: 'true',
                    hideOnClick: false,
                    maxWidth: 'none',
                    //trigger: 'click',
                    onShow(instance) {
                        fetch(ele.data('popupurl'), {
                            method: 'post'
                        })
                            .then((response) => response.text())
                            .then((text) => {
                                instance.setContent(text);
                            })
                            .catch((error) => {
                                // Fallback if the network request failed
                                instance.setContent(`Request failed. ${error}`);
                            });
                    },
                });
                ele.tippy.setContent('Node ' + ele.id());
            }
        }

        cy.ready(function () {
            cy.elements().forEach(function (ele) {
                makePopper(ele);
            });
        });

        cy.elements().unbind('mouseover');
        cy.elements().bind('mouseover', (event) => event.target.tippy.show());

        cy.elements().unbind('mouseout');
        cy.elements().bind('mouseout', (event) => event.target.tippy.hide());

        cy.elements().unbind('drag');
        cy.elements().bind('drag', (event) => event.target.tippy.popperInstance.update());

    });
</script>

<button class="btn" id="rearrangeButton" type="button">Re-arrange</button>

<div id="cy" style="width: 100%; height: 1000px;"></div>

