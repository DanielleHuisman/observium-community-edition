<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$graph_type = "printersupply_usage";

$supplies = dbFetchRows("SELECT * FROM `printersupplies` WHERE `device_id` = ? ORDER BY `supply_type`", [$device['device_id']]);

if (count($supplies)) {
    $box_args = ['title' => 'Printer Supplies',
                 'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'printing']),
                 'icon'  => $config['icon']['printersupply'],
    ];

    echo generate_box_open($box_args);

    echo('<table class="table table-condensed table-striped">');

    foreach ($supplies as $supply) {
        $percent = round($supply['supply_value'], 0);

        if ($supply['supply_colour'] != '') {
            $background = toner_to_colour($supply['supply_colour'], $percent);
        } else {
            $background = toner_to_colour($supply['supply_descr'], $percent);
        }
        $background_percent = get_percentage_colours($percent - 100);

        $graph_array           = [];
        $graph_array['height'] = "100";
        $graph_array['width']  = "210";
        $graph_array['to']     = get_time();
        $graph_array['id']     = $supply['supply_id'];
        $graph_array['type']   = $graph_type;
        $graph_array['from']   = get_time('day');
        $graph_array['legend'] = "no";

        $link_array         = $graph_array;
        $link_array['page'] = "graphs";
        unset($link_array['height'], $link_array['width'], $link_array['legend']);
        $link = generate_url($link_array);

        $overlib_content = generate_overlib_content($graph_array, $device['hostname'] . " - " . $supply['supply_descr']);

        $graph_array['width']  = 80;
        $graph_array['height'] = 20;
        $graph_array['bg']     = 'ffffff00';
        //$graph_array['style'][] = 'margin-top: -6px';

        $minigraph = generate_graph_tag($graph_array);

        if ($percent < 0) {
            $percent_text = 'Unknown';
        } elseif ($percent == 1 && $supply['supply_capacity'] < 0) {
            $percent_text = 'Some space';
        } elseif ($percent <= 0 && $supply['supply_capacity'] <= 0) {
            $percent_text = 'Unknown';
        } else {
            $percent_text = $percent . '%';
        }

        echo('<tr class="' . $background_percent['class'] . '">
           <td class="state-marker"></td>
           <td class="entity">' . overlib_link($link, $supply['supply_descr'], $overlib_content) . "</td>
           <td style='width: 90px;'>" . overlib_link($link, $minigraph, $overlib_content) . "</td>
           <td style='width: 200px;'>" . overlib_link($link, print_percentage_bar(400, 20, $percent, $percent_text, 'ffffff', $background['right'], NULL, "ffffff", $background['left']), $overlib_content) . "</td>
         </tr>");
    }

    echo("</table>");
    echo generate_box_close();
}

unset ($supply_rows);

// EOF
