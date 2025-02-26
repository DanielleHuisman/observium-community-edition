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

if (!isset($rrd_filename)) {
    # We can be included from arista_netstats_sw_ip6, which just sets the filename.
    $rrd_filename = get_rrd_path($device, "arista-netstats-sw-ip.rrd");
    $ipv          = "v4";
}

$stats = [
  'InReceives'       => [],
  'InForwDatagrams'  => [],
  'InNoRoutes'       => [],
  'InAddrErrors'     => [],
  'InUnknownProtos'  => [],
  'InTruncatedPkts'  => [],
  'OutForwDatagrams' => [],
  'OutDiscards'      => [],
  'OutNoRoutes'      => [],
  'OutTransmits'     => []];

$i = 0;
foreach ($stats as $stat => $array) {
    $i++;
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = $stat . " " . $ipv;
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

?>
