<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) Adam Armstrong
 *
 */

$rrd_filename = get_rrd_path($device, "netstats-icmp.rrd");

$stats = ['icmpInSrcQuenchs'    => [],
          'icmpOutSrcQuenchs'   => [],
          'icmpInRedirects'     => [],
          'icmpOutRedirects'    => [],
          'icmpInAddrMasks'     => [],
          'icmpOutAddrMasks'    => [],
          'icmpInAddrMaskReps'  => [],
          'icmpOutAddrMaskReps' => []];

$i = 0;

foreach ($stats as $stat => $array) {
    $i++;
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = str_replace("icmp", "", $stat);
    $rrd_list[$i]['ds']       = $stat;
    if (strpos($stat, "Out") !== FALSE) {
        $rrd_list[$i]['invert'] = TRUE;
    }
}

$colours = 'mixed';

$scale_min  = "0";
$nototal    = 1;
$simple_rrd = TRUE;

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
