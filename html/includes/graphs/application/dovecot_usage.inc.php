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

include_once($config['html_dir']."/includes/graphs/common.inc.php");

$scale_min    = 0;
$colours      = "mixed";
$nototal      = (($width < 550) ? 1 : 0);
$rrd_filename = get_rrd_path($device, "app-dovecot.rrd");
$array        = array(
			'vol_cs' => array('descr' => 'Voluntary Context Switchess'),
			'invol_cs' => array('descr' => 'Involuntary Context Switches')
                     );

$i            = 0;
$x            = 0;

if (rrd_is_file($rrd_filename))
{
  $max_colours = safe_count($config['graph_colours'][$colours]);
  foreach ($array as $ds => $data)
  {
    $x = (($x<=$max_colours) ? $x : 0);
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = $data['descr'];
    $rrd_list[$i]['ds']       = $ds;
    $rrd_list[$i]['colour']   = $config['graph_colours'][$colours][$x];
    $i++;
    $x++;
  }
}

include($config['html_dir']."/includes/graphs/generic_multi.inc.php");

// EOF
