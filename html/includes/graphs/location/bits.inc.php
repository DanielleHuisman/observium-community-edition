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

// init
$i             = 0;
$rrd_list      = [];
$rrd_filenames = [];

foreach ($devices as $device) {
    foreach (dbFetchRows("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` != ?", [$device['device_id'], 1]) as $port) {

        if (is_array($config['device_traffic_iftype'])) {
            foreach ($config['device_traffic_iftype'] as $iftype) {
                if (preg_match($iftype . "i", $port['ifType'])) {
                    continue 2;
                }
            }
        }
        if (!$config['os'][$device['os']]['ports_unignore_descr'] && is_array($config['device_traffic_descr'])) {
            foreach ($config['device_traffic_descr'] as $ifdescr) {
                if (preg_match($ifdescr . "i", $port['ifDescr']) ||
                    preg_match($ifdescr . "i", $port['ifName']) ||
                    preg_match($ifdescr . "i", $port['port_label'])) {
                    continue 2;
                }
            }
        }

        $rrdfile = get_port_rrdfilename($port, NULL, TRUE);
        if (rrd_is_file($rrdfile)) {
            $rrd_filenames[]           = $rrdfile;
            $rrd_list[$i]['filename']  = $rrdfile;
            $rrd_list[$i]['descr']     = $port['port_label_short']; // Options sets for skip htmlentities
            $rrd_list[$i]['descr_in']  = $device['hostname'];
            $rrd_list[$i]['descr_out'] = $port['ifAlias'];
            $rrd_list[$i]['ds_in']     = $ds_in;
            $rrd_list[$i]['ds_out']    = $ds_out;
            $i++;
        }

    }
}

$units       = 'b';
$total_units = 'B';
$colours_in  = 'greens';
$multiplier  = "8";
$colours_out = 'blues';

$nototal = 1;

$graph_title .= "::bits";

$colour_line_in  = "006600";
$colour_line_out = "000099";
$colour_area_in  = "CDEB8B";
$colour_area_out = "C3D9FF";

include($config['html_dir'] . "/includes/graphs/generic_multi_bits_separated.inc.php");

// EOF
