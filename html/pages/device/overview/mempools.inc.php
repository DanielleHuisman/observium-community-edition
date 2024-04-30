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

$graph_type = "mempool_usage";

$sql = "SELECT *, `mempools`.mempool_id as mempool_id";
$sql .= " FROM  `mempools`";
//$sql .= " LEFT JOIN  `mempools-state` USING(`mempool_id`)";
$sql .= " WHERE `device_id` = ?";

$mempools = dbFetchRows($sql, [$device['device_id']]);

if (count($mempools)) {
    $box_args = ['title' => 'Memory',
                 'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => 'mempool']),
                 'icon'  => $config['icon']['mempool'],
    ];
    echo generate_box_open($box_args);

    echo('<table class="table table-condensed table-striped">');

    foreach ($mempools as $mempool) {
        $percent    = round($mempool['mempool_perc'], 0);
        $text_descr = rewrite_entity_name($mempool['mempool_descr'], 'mempool');
        if ($mempool['mempool_total'] != '100') {
            $total = format_bytes($mempool['mempool_total']);
            $used  = format_bytes($mempool['mempool_used']);
            $free  = format_bytes($mempool['mempool_free']);
        } else {
            // If total == 100, than memory not have correct size and uses percents only
            $total = $mempool['mempool_total'] . '%';
            $used  = $mempool['mempool_used'] . '%';
            $free  = $mempool['mempool_free'] . '%';
        }

        $background = get_percentage_colours($percent);

        $graph_array           = [];
        $graph_array['height'] = "100";
        $graph_array['width']  = "210";
        $graph_array['to']     = get_time();
        $graph_array['id']     = $mempool['mempool_id'];
        $graph_array['type']   = $graph_type;
        $graph_array['from']   = get_time('day');
        $graph_array['legend'] = "no";

        $link_array         = $graph_array;
        $link_array['page'] = "graphs";
        unset($link_array['height'], $link_array['width'], $link_array['legend']);
        $link = generate_url($link_array);

        $overlib_content = generate_overlib_content($graph_array, $device['hostname'] . " - " . $text_descr);

        $graph_array['width']  = 80;
        $graph_array['height'] = 20;
        $graph_array['bg']     = 'ffffff00';
//    $graph_array['style'][] = 'margin-top: -6px';

        $minigraph = generate_graph_tag($graph_array);

        echo('<tr class="' . $background['class'] . '">
           <td class="state-marker"></td>
           <td class="entity" style="max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><strong>' . generate_entity_link('mempool', $mempool) . '</strong></td>
           <td style="width: 90px">' . overlib_link($link, $minigraph, $overlib_content) . '</td>
           <td style="width: 200px">' . overlib_link($link, print_percentage_bar(200, 20, $percent, $used . "/" . $total . " (" . $percent . "%)", "ffffff", $background['left'],
                                                                                 $free . " (" . (100 - $percent) . "%)", "ffffff", $background['right']), $overlib_content) . '</td>
         </tr>');
    }

    echo("</table>");
    echo generate_box_close();
}

// EOF
