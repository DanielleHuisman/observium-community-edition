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

// Get port ID by ifIndex/ifDescr/ifAlias or customer circuit
// variables: ifindex, ifdescr (or port), ifalias, circuit
if (!is_numeric($vars['id'])) {
    if (is_numeric($device['device_id'])) {
        if (is_numeric($vars['ifindex'])) {
            // Get port by ifIndex
            $port = get_port_by_index_cache($device['device_id'], $vars['ifindex']);
            if ($port) {
                $vars['id'] = $port['port_id'];
            }
        } elseif (!empty($vars['ifdescr'])) {
            // Get port by ifDescr
            $port_id = get_port_id_by_ifDescr($device['device_id'], $vars['ifdescr']);
            if ($port_id) {
                $vars['id'] = $port_id;
            }
        } elseif (!empty($vars['port'])) {
            // Get port by ifDescr (backward compatibility)
            $port_id = get_port_id_by_ifDescr($device['device_id'], $vars['port']);
            if ($port_id) {
                $vars['id'] = $port_id;
            }
        } elseif (!empty($vars['ifalias'])) {
            // Get port by ifAlias
            $port_id = get_port_id_by_ifAlias($device['device_id'], $vars['ifalias']);
            if ($port_id) {
                $vars['id'] = $port_id;
            }
        }
    } elseif (!empty($vars['circuit'])) {
        // Get port by circuit id
        $port_id = get_port_id_by_customer(['circuit' => $vars['circuit']]);
        if ($port_id) {
            $vars['id'] = $port_id;
        }
    }
}

if (is_numeric($vars['id']) && ($auth || port_permitted($vars['id']))) {
    $auth = TRUE;

    $port   = get_port_by_id_cache($vars['id']);
    $device = device_by_id_cache($port['device_id']);

    $title_array   = [];
    $title_array[] = [ 'text' => $device['hostname'],
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'] ]) ];
    $title_array[] = [ 'text' => 'Ports',
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'ports' ]) ];
    $title_array[] = [ 'text' => escape_html($port['port_label']) . (!empty($port['ifAlias']) ? " (" . escape_html($port['ifAlias']) . ")" : ''),
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'port', 'port' => $port['port_id'] ]) ];

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= ":: Port :: " . $port['port_label_short'];

    // check if the port has an alias available and add it to the title
    if (!empty($port['ifAlias'])) {
        $graph_title .= " :: " . $port['ifAlias'];
    }

    $rrd_filename = get_port_rrdfilename($port, NULL, TRUE);

    if ($vars['type'] == 'port_bits') {
        $scale = $vars['scale'] ?? $config['graphs']['ports_scale_default'];

        if ($scale != 'auto') {
            if ($scale == 'speed' && $port['ifSpeed'] > 0) {
                $scale_max = $port['ifSpeed'];
                if ($graph_style != 'mrtg') {
                    $scale_min = -1 * $scale_max;
                }
            } else {
                $scale = (int)unit_string_to_numeric($scale, 1000);
                if ($scale > 0) {
                    $scale_max = $scale;
                    if ($graph_style != 'mrtg') {
                        $scale_min = -1 * $scale_max;
                    }
                }
            }
            $scale_rigid = isset($config['graphs']['ports_scale_force']) && $config['graphs']['ports_scale_force'];
        }
    }
}

// EOF

