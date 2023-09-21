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

//include_once($config['html_dir']."/includes/graphs/common.inc.php");

$radio1 = get_rrd_path($device, "wificlients-radio1.rrd");
$radio2 = get_rrd_path($device, "wificlients-radio2.rrd");

if (rrd_is_file($radio1, TRUE)) {
    $radio2_exists = rrd_is_file($radio2, TRUE);

    $rrd_list[0]['filename']   = $radio1;
    $rrd_list[0]['descr']      = $radio2_exists ? 'Clients on Radio1' : 'Clients';
    $rrd_list[0]['ds']         = 'wificlients';
    $rrd_list[0]['colour']     = '008C00';
    $rrd_list[0]['areacolour'] = 'CDEB8B';

    if ($radio2_exists) {
        $rrd_list[1]['filename'] = $radio2;
        $rrd_list[1]['descr']    = 'Clients on Radio2';
        $rrd_list[1]['ds']       = 'wificlients';
        $rrd_list[1]['colour']   = 'CC0000';
        //$rrd_list[1]['areacolour'] = 'CDEB8B';
    }
}

//$colours   = "mixed";
$scale_rigid = FALSE;
$scale_min   = 0;
$nototal     = 1;
$unit_text   = "Total";

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");
#include($config['html_dir']."/includes/graphs/generic_multi_simplex_separated.inc.php");
/*
$rrd_options .= " -l 0 -E ";

$radio1 = get_rrd_path($device, "wificlients-radio1.rrd");
$radio2 = get_rrd_path($device, "wificlients-radio2.rrd");

if (rrd_is_file($radio1, TRUE)) {
  $radio2_exists = rrd_is_file($radio2, TRUE);

  $rrd_options .= " COMMENT:'                           Cur   Min  Max\\n'";
  $rrd_options .= " DEF:wificlients1=".$radio1.":wificlients:AVERAGE ";
  if ($radio2_exists) {
    $rrd_options .= " LINE1.5:wificlients1#008C00:'Clients on Radio1    ' ";
  } else {
    $rrd_options .= " LINE1.5:wificlients1#008C00:'Clients              ' ";
  }
  $rrd_options .= " GPRINT:wificlients1:LAST:%3.0lf ";
  $rrd_options .= " GPRINT:wificlients1:MIN:%3.0lf ";
  $rrd_options .= " GPRINT:wificlients1:MAX:%3.0lf\\\l ";
  if ($radio2_exists) {
    $rrd_options .= " DEF:wificlients2=".$radio2.":wificlients:AVERAGE ";
    $rrd_options .= " LINE1.5:wificlients2#CC0000:'Clients on Radio2    ' ";
    $rrd_options .= " GPRINT:wificlients2:LAST:%3.0lf ";
    $rrd_options .= " GPRINT:wificlients2:MIN:%3.0lf ";
    $rrd_options .= " GPRINT:wificlients2:MAX:%3.0lf\\\l ";
  }
}
*/

// EOF
