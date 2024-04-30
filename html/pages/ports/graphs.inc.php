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

// Pagination
//echo(pagination($vars, $ports_count));

// Populate ports array (much faster for large systems)
//r($port_ids);
$where = generate_where_clause(generate_query_values($ports_ids, 'ports.port_id'));

$select = "`ports`.*, `ports`.`port_id` AS `port_id`";

include($config['html_dir'] . "/includes/port-sort-select.inc.php");

$sql = "SELECT " . $select;
$sql .= " FROM `ports`";
$sql .= " INNER JOIN `devices` USING (`device_id`)";
$sql .= " " . $where . generate_port_sort($vars); // . generate_query_limit($vars);

unset($ports);

// End populating ports array

echo '<div class="row">';

foreach (dbFetchRows($sql) as $port) {

    $speed = humanspeed($port['ifSpeed']);
    $type  = rewrite_iftype($port['ifType']);

    $port['in_rate']  = format_bps($port['ifInOctets_rate'] * 8);
    $port['out_rate'] = format_bps($port['ifOutOctets_rate'] * 8);

    if ($port['in_errors'] > 0 || $port['out_errors'] > 0) {
        $error_img = generate_port_link($port, get_icon('error'), 'port_errors');

    } else {
        $error_img = '';
    }

    humanize_port($port);

    $device = device_by_id_cache($port['device_id']);

    //r($port);

    $graph_type = "port_" . $vars['graph'];

    $graph_array = [];

    if ($_SESSION['widescreen']) {
        if ($config['graphs']['size'] === 'big') {
            $width_div  = 585;
            $width      = 507;
            $height     = 149;
            $height_div = 220;
        } else {
            $width_div  = 349;
            $width      = 275;
            $height     = 109;
            $height_div = 180;
        }
    } else {
        if ($config['graphs']['size'] === 'big') {
            $width_div  = 611;
            $width      = 528;
            $height     = 159;
            $height_div = 218;
        } else {
            $width_div  = 303;
            $width      = 226;
            $height     = 102;
            $height_div = 158;
        }
    }

    if (isset($vars['from']) && is_numeric($vars['from']) && isset($vars['to']) && is_numeric($vars['to'])) {
        $graph_array['from'] = $vars['from'];
        $graph_array['to']   = $vars['to'];
    } else {
        $graph_array['from'] = get_time('day');
        $graph_array['to']   = get_time();
    }

    $graph_array['height'] = 100;
    $graph_array['width']  = 210;
    $graph_array['id']     = $port['port_id'];
    $graph_array['type']   = $graph_type;
    $graph_array['legend'] = "no";

    $link_array         = $graph_array;
    $link_array['page'] = "graphs";
    unset($link_array['height'], $link_array['width'], $link_array['legend']);
    $link                  = generate_url($link_array);
    $overlib_content       = generate_overlib_content($graph_array, $port['hostname'] . ' - ' . $port['port_label']);
    //$graph_array['title']  = "yes";
    $graph_array['width']  = $width;
    $graph_array['height'] = $height;
    $graph                 = generate_graph_tag($graph_array);

    echo generate_box_open(['title'         => short_hostname($device['hostname']) . ' :: ' . $port['port_label_short'],
                            'url'           => generate_port_url($port),
                            'header-border' => TRUE,
                            'box-style'     => 'float: left; margin-left: 10px; margin-bottom: 10px;  width:' . $width_div . 'px; min-width: ' . $width_div . 'px; max-width:' . $width_div . 'px; min-height:' . $height_div . 'px; max-height:' . $height_div . ';']);

    echo overlib_link($link, $graph, $overlib_content);

    echo generate_box_close();
}


echo '</div>';

// EOF
