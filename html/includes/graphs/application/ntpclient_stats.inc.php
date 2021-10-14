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

$colours      = "mixed";
$nototal      = (($width<224) ? 1 : 0);
$unit_text    = "Milliseconds";
$rrd_filename = get_rrd_path($device, "app-ntpclient-".$app['app_id'].".rrd");
$array        = array(
                      'offset' => array('descr' => 'Offset'),
                      'jitter' => array('descr' => 'Jitter'),
                      'noise' => array('descr' => 'Noise'),
                      'stability' => array('descr' => 'Stability')
                     );

if (rrd_is_file($rrd_filename))
{
  $i = 0;

  foreach ($array as $ds => $data)
  {
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = $data['descr'];
    $rrd_list[$i]['ds']       = $ds;
    $rrd_list[$i]['colour']   = $config['graph_colours'][$colours][$i];
    $i++;
  }
} else {
  echo("file missing: $rrd_filename");
}

include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");

// EOF
