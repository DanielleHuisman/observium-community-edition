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
                      'clientParseErrors' => array('descr' => 'Bad packets from clients', 'colour' => '0000FFFF'),
                      'serverParseErrors' => array('descr' => 'Bad packets from servers', 'colour' => '00FF00FF'),
                      'unexpectedPkts'    => array('descr' => 'Unexpected packets from servers', 'colour' => 'FF0000FF'),
                      /*
                      // Currently not in the RRD file! New field since 3.3?
                      'udpOverruns'       => array('descr' => 'UDP overruns from remotes', 'colour' => 'FF00FFFF'),
                      */
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
