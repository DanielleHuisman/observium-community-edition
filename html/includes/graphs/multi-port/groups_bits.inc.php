<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

//$vars['data'] = json_decode($vars['data'], TRUE);

$multiplier = 8;

//r($vars['data']);

foreach ($vars['data'] as $entry) {
    $rrds = [];
    foreach (get_group_entities($entry['group_id'], 'port') as $port_id) {
        $port     = get_port_by_id_cache($port_id);
        $device   = device_by_id_cache($port['device_id']);
        $filename = get_port_rrdfilename($port, NULL, TRUE);

        if (rrd_is_file($filename)) {
            $rrds[] = ['file'   => $filename,
                       'descr'  => $device['hostname'] . " " . $port['port_label_short'],
                       'ds_in'  => 'INOCTETS',
                       'ds_out' => 'OUTOCTETS'];
        }
    }

    if (safe_count($rrds)) {
        $this_data = ['rrds'       => $rrds,
                      'colour_in'  => $entry['colour'],
                      'colour_out' => adjust_colour_brightness($entry['colour'], 32),
                      'descr'      => $entry['descr']];

        if ($entry['colours']) {
            $this_data['colours'] = $entry['colours'];
        }
        if ($entry['colours_in']) {
            $this_data['colours_in'] = $entry['colours_in'];
        }
        if ($entry['colours_out']) {
            $this_data['colours_out'] = $entry['colours_out'];
        }

        $data[] = $this_data;

    }

}

include($config['html_dir'] . "/includes/graphs/generic_multi_group_bits.inc.php");
