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


$stats['bits'] = ['FwdInProfOcts'  => 'Fwd In-Profile Traffic',
                  'FwdOutProfOcts' => 'Fwd Out-Profile Traffic',
                  'DroInProfOcts'  => 'Drop In-Profile Traffic',
                  'DroOutProfOcts' => 'Drop Out-Profile Traffic',
];

$stats['pkts'] = [
  'FwdInProfPkts'  => 'Forwarded In-Profile Packets',
  'FwdOutProfPkts' => 'Forwarded Out-Profile Packets',
  'DroInProfPkts'  => 'Dropped In-Profile Packets',
  'DroOutProfPkts' => 'Dropped Out-Profile Packets'
];


$queue  = $vars['queue'];
$metric = $vars['metric'];
$dir    = $vars['dir'];

$rrd_filename = $config['rrd_dir'] . '/' . $device['hostname'] . '/' . 'port-' . get_port_rrdindex($port) . '-' . $queue . '-sros_' . $dir . '_qstat.rrd';

$i = 0;
foreach ($stats[$metric] as $stat => $descr) {
    $i++;
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = $descr;
    $rrd_list[$i]['ds']       = $stat;
    if (strpos($stat, "Dro") !== FALSE) {
        $rrd_list[$i]['invert'] = TRUE;
        $rrd_list[$i]['colour'] = $config['graph_colours']['reds'][$i];
    }
}

if ($metric == 'bits') {
    $colours    = 'greens';
    $unit_text  = 'Bits/sec';
    $multiplier = 8;
} else {
    $colours   = 'purples';
    $unit_text = 'Pkts/sec';
}

$scale_min = "0";
$nototal   = 1;

include($config['html_dir'] . "/includes/graphs/generic_multi.inc.php");
