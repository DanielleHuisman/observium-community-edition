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

$scale_min = 0;

include($config['html_dir']."/includes/graphs/common.inc.php");

$rrd_filename = get_rrd_path($device, "app-lighttpd-".$app['app_id']);

$array = array('connectionsp' => array('descr' => 'Connect', 'colour' => '750F7DFF'),
               'connectionsC' => array('descr' => 'Close', 'colour' => '00FF00FF'),
               'connectionsE' => array('descr' => 'Hard Error', 'colour' => '4444FFFF'),
               'connectionsk' => array('descr' => 'Keep-alive', 'colour' => '157419FF'),
               'connectionsr' => array('descr' => 'Read', 'colour' => 'FF0000FF'),
               'connectionsR' => array('descr' => 'Read-POST', 'colour' => '6DC8FEFF'),
               'connectionsW' => array('descr' => 'Write', 'colour' => 'FFAB00FF'),
               'connectionsh' => array('descr' => 'Handle-request', 'colour' => 'FFFF00FF'),
               'connectionsq' => array('descr' => 'Request-start', 'colour' => 'FF5576FF'),
               'connectionsQ' => array('descr' => 'Request-end', 'colour' => 'FF3005FF'),
               'connectionss' => array('descr' => 'Response-start', 'colour' => '800080'),
               'connectionsS' => array('descr' => 'Response-end', 'colour' => '959868'),
);

$i = 0;
if (is_file($rrd_filename))
{
  foreach ($array as $ds => $data)
  {
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr'] = $data['descr'];
    $rrd_list[$i]['ds'] = $ds;
    $rrd_list[$i]['colour'] = $data['colour'];
    $i++;
  }
} else { echo("file missing: $rrd_filename");  }

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Workers";

include($config['html_dir']."/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
