<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Generate a list of ports and then call the multi_bits grapher to generate from the list

$ds_in  = "INOCTETS";
$ds_out = "OUTOCTETS";

$graph_return['descr'] = 'Device total traffic in bits/sec.';

$filtered_ports = [];
$unfiltered_ports = [];

foreach (dbFetchRows("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` != ? ORDER BY (ifInOctets_rate + ifOutOctets_rate) DESC", [$device['device_id'], '1']) as $port) {
    $debug_msg = '[Port (id=' . $port['port_id'] . ', ifIndex=' . $port['ifIndex'] . ') ignored by ';
    $filtered = false;

    if (is_array($config['device_traffic_iftype'])) {
        foreach ($config['device_traffic_iftype'] as $iftype) {
            if (preg_match($iftype . "i", $port['ifType'])) {
                print_debug($debug_msg . 'ifType=' . $port['ifType'] . ']');
                $filtered = true;
                break;
            }
        }
    }
    if (!$filtered && !$config['os'][$device['os']]['ports_unignore_descr'] && is_array($config['device_traffic_descr'])) {
        foreach ($config['device_traffic_descr'] as $ifdescr) {
            if (preg_match($ifdescr . "i", $port['ifDescr']) ||
                preg_match($ifdescr . "i", $port['ifName']) ||
                preg_match($ifdescr . "i", $port['port_label'])) {
                print_debug($debug_msg . 'ifDescr=' . $port['ifDescr'] . ' or ifName=' . $port['ifName'] . ' or port_label=' . $port['port_label'] . ']');
                $filtered = true;
                break;
            }
        }
    }

    if ($filtered) {
        $filtered_ports[] = $port;
    } else {
        $unfiltered_ports[] = $port;
    }
}

$ports_to_use = empty($unfiltered_ports) ? $filtered_ports : $unfiltered_ports;


// init
$i             = 0;
$rrd_list      = [];
$rrd_filenames = [];

foreach ($ports_to_use as $port) {
    $rrd_filename = get_port_rrdfilename($port, NULL, TRUE);
    if (rrd_is_file($rrd_filename)) {
        $rrd_filenames[]           = $rrd_filename;
        $rrd_list[$i]['filename']  = $rrd_filename;
        $rrd_list[$i]['descr']     = $port['port_label_short'];
        $rrd_list[$i]['descr_in']  = $port['port_label_short'];
        $rrd_list[$i]['descr_out'] = $port['ifAlias'];
        $rrd_list[$i]['ds_in']     = $ds_in;
        $rrd_list[$i]['ds_out']    = $ds_out;

        $i++;
    }
}

$units       = 'b';
$total_units = 'B';
$colours_in  = 'greens';
$multiplier  = "8";
$colours_out = 'blues';

#$nototal = 1;

$ds_in  = "INOCTETS";
$ds_out = "OUTOCTETS";

include($config['html_dir'] . "/includes/graphs/generic_multi_separated.inc.php");

#include($config['html_dir']."/includes/graphs/generic_multi_bits_separated.inc.php");
#include($config['html_dir']."/includes/graphs/generic_multi_data_separated.inc.php");

// EOF
