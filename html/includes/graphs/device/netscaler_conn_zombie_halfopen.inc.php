<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

//$scale_min = 0;
$colours = "mixed";
$nototal = 0;
$unit_text = "Errors";
$rrd_filename = get_rrd_path($device, "netscaler-stats-tcp.rrd");
$log_y = TRUE;

$array = array(
        'TotZomHalfOpenCltCo' => array('descr' => 'TotZomHalfOpenCltCo'),
        'TotZomHalfOpenSvrCo' => array('descr' => 'TotZomHalfOpenSvrCo')
);

if (rrd_is_file($rrd_filename))
{
  foreach ($array as $ds => $data)
  {
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = $data['descr'];
    $rrd_list[$i]['ds']       = $ds;
    $rrd_list[$i]['colour']   = $config['graph_colours'][$colours][$i];
    $i++;
  }
} else {
  echo("file missing: $file");
}

include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");

// EOF
