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
$colours      = "blue";
$nototal      = (($width<224) ? 1 : 0);
$unit_text    = "Packets/sec";
$rrd_filename = get_rrd_path($device, "app-powerdns-".$app['app_id'].".rrd");
$array        = array(
                      'pc_hit' => array('descr' => 'Hits', 'colour' => '008800FF'),
                      'pc_miss' => array('descr' => 'Misses', 'colour' => '880000FF'),
                      'pc_size' => array('descr' => 'Size', 'colour' => '006699FF'),
                     );

$i            = 0;

if (rrd_is_file($rrd_filename))
{
  foreach ($array as $ds => $data)
  {
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = $data['descr'];
    $rrd_list[$i]['ds']       = $ds;
    $rrd_list[$i]['colour']   = $data['colour'];
    $i++;
  }
} else {
  echo("file missing: $rrd_filename");
}

include($config['html_dir']."/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
