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

$graph_type = "processor_usage";

$sql = "SELECT * FROM `processors`";
//$sql .= " LEFT JOIN `processors-state` USING(`processor_id`)";
$sql .= " WHERE `processor_type` != 'hr-average' AND `device_id` = ?";

$processors_db = dbFetchRows($sql, [$device['device_id']]);

if (count($processors_db)) {
    $processors = [];
    // Combinate multiple same processors
    foreach ($processors_db as $proc) {
        $text_descr                              = rewrite_entity_name($proc['processor_descr'], 'processor');
        $processors[$text_descr]['device_id']    = $device['device_id'];
        $processors[$text_descr]['processor_id'] = $proc['processor_id'];
        $processors[$text_descr]['id'][]         = $proc['processor_id'];
        $processors[$text_descr]['usage']        += $proc['processor_usage'];
        $processors[$text_descr]['count']++;
    }

    $box_args = ['title' => 'Processors',
                 'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => 'processor']),
                 'icon'  => $config['icon']['processor'],
    ];

    echo generate_box_open($box_args);

    $graph_array           = [];
    $graph_array['height'] = "100";
    $graph_array['width']  = "512";
    $graph_array['to']     = get_time();
    $graph_array['device'] = $device['device_id'];
    $graph_array['type']   = (isset($device['graphs']['ucd_ss_cpu']) ? 'device_ucd_ss_cpu' : 'device_processor');
    $graph_array['from']   = get_time('day');
    $graph_array['legend'] = "no";
    $graph                 = generate_graph_tag($graph_array);

    $link_array         = $graph_array;
    $link_array['page'] = "graphs";
    unset($link_array['height'], $link_array['width']);
    $graph_link = generate_url($link_array);

    $link = generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => 'processor']);

    $graph_array['width'] = "210";
    $overlib_content      = generate_overlib_content($graph_array, $device['hostname'] . " - Processor Usage");

    //echo(overlib_link($graph_link, $graph, $overlib_content, NULL));

    $graph_array['width']   = 80;
    $graph_array['height']  = 20;
    $graph_array['bg']      = 'ffffff00'; # the 00 at the end makes the area transparent.
    $graph_array['style'][] = 'margin-top: -6px';

    $minigraph = generate_graph_tag($graph_array);

    echo('<table class="table table-condensed table-striped">');

    echo('<tr><td colspan=3>');
    echo(overlib_link($graph_link, $graph, $overlib_content, NULL));
    echo('</td></tr>');

    foreach ($processors as $text_descr => $proc) {
        $percent      = round($proc['usage'] / $proc['count']);
        $background   = get_percentage_colours($percent);
        $graph_colour = str_replace("#", "", $row_colour);

        $graph_array           = [];
        $graph_array['height'] = "100";
        $graph_array['width']  = "210";
        $graph_array['to']     = get_time();
        $graph_array['device'] = $device['device_id'];
        $graph_array['id']     = $proc['id'];

        if (is_array($proc['id'])) {
            $graph_array['type'] = "multi-processor_usage";
        } else {
            $graph_array['type'] = $graph_type;
        }
        $graph_array['from']   = get_time('day');
        $graph_array['legend'] = "no";

        $link_array         = $graph_array;
        $link_array['page'] = "graphs";
        unset($link_array['height'], $link_array['width'], $link_array['legend']);
        $link = generate_url($link_array);

        $overlib_content = generate_overlib_content($graph_array, $device['hostname'] . " - " . $text_descr);

        $graph_array['width']  = 80;
        $graph_array['height'] = 20;
        $graph_array['bg']     = 'ffffff00'; # the 00 at the end makes the area transparent.
//    $graph_array['style'][] = 'margin-top: -6px';

        $count_button = ($proc['count'] > 1 ? '<span class="label pull-right" style="margin-top: 2px;"><i class="icon-remove"></i> ' . $proc['count'] . '</span>' : '');
        echo('<tr class="' . $background['class'] . '">
           <td class="state-marker"></td>
           <td><span class="entity text-nowrap">' . generate_entity_link('processor', $proc, $text_descr) . '</span>' . $count_button . '</td>
           <td style="width: 200px">' . overlib_link($link, print_percentage_bar(200, 20, $percent, NULL, "ffffff", $background['left'], $percent . "%", "ffffff", $background['right']), $overlib_content) . '</td>
         </tr>');
    }
    echo("</table>");
    echo generate_box_close();
}

// EOF
