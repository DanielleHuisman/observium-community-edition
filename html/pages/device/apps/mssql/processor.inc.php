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

$cpu_counter      = $app_data['cpu']['proc'];
$time_counter     = $app_data['cpu']['time'];
$cpu_load_percent = round(float_div($cpu_counter, $time_counter) * 100, 2);
$background       = get_percentage_colours($cpu_load_percent);

$graph_array           = [];
$graph_array['height'] = "100";
$graph_array['width']  = "512";
$graph_array['to']     = get_time();
$graph_array['id']     = $app['app_id'];
$graph_array['type']   = 'application_mssql_cpu_usage';
$graph_array['from']   = get_time('day');
$graph_array['legend'] = "no";
$graph                 = generate_graph_tag($graph_array);

$link_array         = $graph_array;
$link_array['page'] = "graphs";
unset($link_array['height'], $link_array['width'], $link_array['legend']);
$link = generate_url($link_array);

$overlib_content = generate_overlib_content($graph_array, $app['app_instance'] . " - CPU Usage");

$percentage_bar            = [];
$percentage_bar['border']  = "#" . $background['left'];
$percentage_bar['bg']      = "#" . $background['right'];
$percentage_bar['width']   = "100%";
$percentage_bar['text']    = $cpu_load_percent . "%";
$percentage_bar['text_c']  = "#FFFFFF";
$percentage_bar['bars'][0] = ['percent' => $cpu_load_percent];

echo(overlib_link($link, $graph, $overlib_content, NULL));
?>
    <table width="100%" class="table table-striped table-condensed-more ">
        <tr>
            <td class="entity">Current CPU Load</td>
            <td style="width: 90px;"></td>
            <td style="width: 200px;"><?php echo percentage_bar($percentage_bar); ?></td>
        </tr>
    </table>

<?php

unset($percentage_bar, $graph_array, $overlib_content, $graph, $link, $link_array);

// EOF
