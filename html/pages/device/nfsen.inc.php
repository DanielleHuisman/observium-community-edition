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

$datas = [
  'Traffic' => 'nfsen_traffic',
  'Packets' => 'nfsen_packets',
  'Flows'   => 'nfsen_flows'
];

// Detect Nfsen-ng
$nfsen_rrd_info = rrdtool_file_info($nfsen_rrd_file);
if (isset($nfsen_rrd_info['DS']['bytes'])) {
    $datas['Traffic'] = 'nfsen_bytes';
}

foreach ($datas as $name => $type) {
    $graph_title           = nicecase($name);
    $graph_array['type']   = "device_" . $type;
    $graph_array['device'] = $device['device_id'];
    #$graph_array['legend'] = 'no';

    $box_args = ['title' => $graph_title, 'header-border' => TRUE];

    echo generate_box_open($box_args);

    print_graph_row($graph_array);

    echo generate_box_close();
}

register_html_title("Netflow");

// EOF
