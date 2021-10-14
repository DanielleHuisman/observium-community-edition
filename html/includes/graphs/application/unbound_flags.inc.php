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
$nototal      = 1;
$unit_text    = "Queries/sec";
$rrd_filename = get_rrd_path($device, "app-unbound-".$app['app_id']."-queries.rrd");

$i            = 0;
$array        = array();

$colours = $config['graph_colours']['mixed']; # needs moar colours!

$array['flagQR'] = array('descr' => "QR (query reply) flag", 'colour' => $colours[(safe_count($array) % safe_count($colours))]);
$array['flagAA'] = array('descr' => "AA (auth answer) flag", 'colour' => $colours[(safe_count($array) % safe_count($colours))]);
$array['flagTC'] = array('descr' => "TC (truncated) flag"  , 'colour' => $colours[(safe_count($array) % safe_count($colours))]);
$array['flagRD'] = array('descr' => "RD (recursion desired) flag", 'colour' => $colours[(safe_count($array) % safe_count($colours))]);
$array['flagRA'] = array('descr' => "RA (rec avail) flag", 'colour' => $colours[(safe_count($array) % safe_count($colours))]);
$array['flagZ']  = array('descr' =>  "Z (zero) flag", 'colour' => $colours[(safe_count($array) % safe_count($colours))]);
$array['flagAD'] = array('descr' => "AD (auth data) flag", 'colour' => $colours[(safe_count($array) % safe_count($colours))]);
$array['flagCD'] = array('descr' => "CD (check disabled) flag", 'colour' => $colours[(safe_count($array) % safe_count($colours))]);
$array['ednsPresent'] = array('descr' => "EDNS OPT present", 'colour' => $colours[(safe_count($array) % safe_count($colours))]);
$array['ednsDO'] = array('descr' => "DO (DNSSEC OK) flag", 'colour' => $colours[(safe_count($array) % safe_count($colours))]);

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
