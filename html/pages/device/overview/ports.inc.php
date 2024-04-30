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

$where             = ' WHERE `device_id` = ? AND `deleted` != ?';
$params            = [ $device['device_id'], '1' ];
$ports['total']    = dbFetchCell("SELECT COUNT(*) FROM `ports` $where", $params);

if (!$ports['total']) {
    return;
}

$ports['up']       = dbFetchCell("SELECT COUNT(*) FROM `ports` $where AND `ifAdminStatus` = 'up' AND `ifOperStatus` IN ('up', 'testing', 'monitoring')", $params);
$ports['down']     = dbFetchCell("SELECT COUNT(*) FROM `ports` $where AND `ifAdminStatus` = 'up' AND `ifOperStatus` IN ('lowerLayerDown', 'down')", $params);
$ports['shutdown'] = dbFetchCell("SELECT COUNT(*) FROM `ports` $where AND `ifAdminStatus` = 'down'", $params);
$ports['deleted']  = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE `device_id` = ? AND `deleted` = ?", $params);

if ($ports['down']) {
    $ports_colour_off = OBS_COLOUR_WARN_A;
}

/* My experiments, ifType filtering
$box_controls = [
    'all' => ['text'   => 'Show All',
               'anchor' => TRUE,
               'url'    => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'latency'])],
    'perf' => ['text'   => 'Show Latency',
               'anchor' => TRUE,
               'url'    => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'latency'])],
];
*/

echo generate_box_open([ 'title' => 'Ports',
                         'icon'  => $config['icon']['port'],
                         'url'   => generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'ports' ]), ]);
                         //'header-controls' => [ 'controls' => $box_controls ] ]);

$graph_array['height'] = "100";
$graph_array['width']  = "512";
$graph_array['to']     = get_time();
$graph_array['device'] = $device['device_id'];
$graph_array['type']   = "device_bits";
$graph_array['from']   = get_time('day');
$graph_array['legend'] = "no";
$graph_array['style']  = [ 'width: 100%', 'max-width: 593px' ]; // Override default width
$graph                 = generate_graph_tag($graph_array);
unset($graph_array['style']);

$link_array         = $graph_array;
$link_array['page'] = "graphs";
unset($link_array['height'], $link_array['width']);
$link = generate_url($link_array);

$graph_array['width'] = "210";
$overlib_content      = generate_overlib_content($graph_array, $device['hostname'] . " - Device Traffic");

echo('<table class="table table-condensed table-striped ">
  <tr><td colspan=4>');

echo(overlib_link($link, $graph, $overlib_content));

echo('</td></tr>
    <tr style="background-color: ' . $ports_colour_off . '; align: center;">
      <td style="width: 25%; text-align: center;"><i class="' . $config['icon']['port'] . '" title="Total Ports"></i> ' . $ports['total'] . '</td>
      <td style="width: 25%; text-align: center;" class="green"><i class="' . $config['icon']['up'] . '" title="Up Ports"></i> ' . $ports['up'] . '</td>
      <td style="width: 25%; text-align: center;" class="red">  <i class="' . $config['icon']['down'] . '" title="Down Ports"></i> ' . $ports['down'] . '</td>
      <td style="width: 25%; text-align: center;" class="grey"> <i class="' . $config['icon']['shutdown'] . '" title="Shutdown Ports"></i> ' . $ports['shutdown'] . '</td>
    </tr>');

echo('<tr><td colspan=4 style="padding-left: 10px; font-size: 11px; font-weight: bold;">');

echo(implode(', ', get_ports_links_sorted($device)));
echo('</td></tr>');
echo('</table>');

echo generate_box_close();

unset($port, $ports_links, $all_links);

// EOF
