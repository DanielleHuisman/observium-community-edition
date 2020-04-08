<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Generate a list of ports and then call the multi_bits grapher to generate from the list

$ds_in  = "INOCTETS";
$ds_out = "OUTOCTETS";

$graph_return = array('descr' => 'Device total traffic in bits/sec.');

foreach (dbFetchRows("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` != ? ORDER BY (ifInOctets_rate + ifOutOctets_rate) DESC", array($device['device_id'], '1')) as $port)
{
    $ignore    = FALSE;
    $debug_msg = '[Port (id=' . $port['port_id'] . ', ifIndex=' . $port['ifIndex'] . ') ignored by ';
    if (is_array($config['device_traffic_iftype']))
    {
        foreach ($config['device_traffic_iftype'] as $iftype)
        {
            if (preg_match($iftype . "i", $port['ifType']))
            {
                print_debug($debug_msg . 'ifType=' . $port['ifType'] . ']');
                $ignore = TRUE;
                break;
            }
        }
    }
    if (!$ignore && !$config['os'][$device['os']]['ports_unignore_descr'] && is_array($config['device_traffic_descr']))
    {
        foreach ($config['device_traffic_descr'] as $ifdescr)
        {
            if (preg_match($ifdescr . "i", $port['ifDescr']))
            {
                print_debug($debug_msg . 'ifDescr=' . $port['ifDescr'] . ']');
                $ignore = TRUE;
                break;
            }
            elseif (preg_match($ifdescr . "i", $port['ifName']))
            {
                print_debug($debug_msg . 'ifName=' . $port['ifName'] . ']');
                $ignore = TRUE;
                break;
            }
            elseif (preg_match($ifdescr . "i", $port['portName']))
            {
                print_debug($debug_msg . 'portName=' . $port['portName'] . ']');
                $ignore = TRUE;
                break;
            }
        }
    }

    $rrd_filename = get_port_rrdfilename($port, NULL, TRUE);
    if (!$ignore && is_file($rrd_filename))
    {

        $rrd_filenames[]           = $rrd_filename;
        $rrd_list[$i]['filename']  = $rrd_filename;
        $rrd_list[$i]['descr']     = $port['port_label_short'];
        $rrd_list[$i]['descr_in']  = $port['port_label_short'];
        $rrd_list[$i]['descr_out'] = $port['ifAlias'];
        $rrd_list[$i]['ds_in']     = $ds_in;
        $rrd_list[$i]['ds_out']    = $ds_out;
        $i++;
    }

    unset($ignore);
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
