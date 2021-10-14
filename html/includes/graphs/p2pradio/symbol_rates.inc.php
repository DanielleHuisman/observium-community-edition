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

$colours = "greens";
$nototal = 0;
$unit_text = "Symbols/sec";
$units = "Symbols/sec";

$array = array(
        'sym_rate_rx'          => array('descr' => 'Rx Symbol Rate'),
        'sym_rate_tx'          => array('descr' => 'Tx Symbol Rate'),
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
