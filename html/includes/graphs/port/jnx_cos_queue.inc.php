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


$queue  = $vars['queue'];
$metric = $vars['metric'];

$rrd_filename = $config['rrd_dir'] . '/' . $device['hostname'] . '/' . 'port-' . get_port_rrdindex($port) . '-' . $queue . '-jnx_cos_qstat.rrd';

if ($vars['metric'] == 'pkts') {
    $rrd_list[] = ['filename' => $rrd_filename,
                   'descr'    => 'Queued Packets',
                   'ds'       => 'QedPkts',
                   'colour'   => $config['graph_colours']['purples'][3]];

    $rrd_list[] = ['filename' => $rrd_filename,
                   'descr'    => 'Transmitted Packets',
                   'ds'       => 'TxedPkts',
                   'colour'   => $config['graph_colours']['oranges'][3],
                   'invert'   => TRUE];

    $rrd_list[] = ['filename' => $rrd_filename,
                   'descr'    => 'Tail Dropped Packets',
                   'ds'       => 'TailDropPkts',
                   'colour'   => $config['graph_colours']['reds'][0],
                   'invert'   => TRUE];

    $rrd_list[] = ['filename' => $rrd_filename,
                   'descr'    => 'RED Dropped Packets',
                   'ds'       => 'TotalRedDropPkts',
                   'colour'   => $config['graph_colours']['reds'][5],
                   'invert'   => TRUE];

} else {

    $rrd_list[] = ['filename' => $rrd_filename,
                   'descr'    => 'Queued Bits',
                   'ds'       => 'QedBytes',
                   'colour'   => $config['graph_colours']['greens'][3]];

    $rrd_list[] = ['filename' => $rrd_filename,
                   'descr'    => 'Transmitted Bits',
                   'ds'       => 'TxedBytes',
                   'colour'   => $config['graph_colours']['blues'][3],
                   'invert'   => TRUE];

    $rrd_list[] = ['filename' => $rrd_filename,
                   'descr'    => 'RED Dropped Bits',
                   'ds'       => 'TotalRedDropBytes',
                   'colour'   => $config['graph_colours']['reds'][5],
                   'invert'   => TRUE];


}


/*
$i=0;
foreach ($stats[$metric] as $stat => $descr)
{
  $i++;
  $rrd_list[$i]['filename'] = $rrd_filename;
  $rrd_list[$i]['descr'] = $descr;
  $rrd_list[$i]['ds'] = $stat;
  if (strpos($stat, "Dro") !== FALSE)
  {
    $rrd_list[$i]['invert'] = TRUE;
    $rrd_list[$i]['colour'] = $config['graph_colours']['reds'][$i];
  }
}

*/

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
