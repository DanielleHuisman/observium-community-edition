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
$nototal      = (($width<224) ? 1 : 0);
$unit_text    = "Packets/sec";
$rrd_filename = get_rrd_path($device, "app-powerdns-recursor-".$app['app_id'].".rrd");
$array        = array(
                      'tcpQuestions'    => array('descr' => 'TCP Questions', 'colour' => '0000FFFF'),
                      'tcpUnauthorized' => array('descr' => 'TCP Unauthorized', 'colour' => 'FF0000FF'),
                      'udpUnauthorized' => array('descr' => 'UDP Unauthorized', 'colour' => '00FF00FF'),
                     );

$i            = 0;

if (rrd_is_file($rrd_filename))
{
  foreach ($array as $ds => $data)
  {
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr'] = $data['descr'];
    $rrd_list[$i]['ds'] = $ds;
    $rrd_list[$i]['colour'] = $data['colour'];
    $i++;
  }
} else {
  echo("file missing: $rrd_filename");
}

include($config['html_dir']."/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
