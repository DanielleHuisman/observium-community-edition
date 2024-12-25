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

$rrd_filename = get_rrd_path($device, "netstats-tcp.rrd");

$stats = ['tcpActiveOpens', 'tcpPassiveOpens', 'tcpAttemptFails', 'tcpEstabResets', 'tcpRetransSegs'];

$i = 0;
foreach ($stats as $stat) {
    $i++;
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = str_replace("tcp", "", $stat);
    $rrd_list[$i]['ds']       = $stat;
    if (strpos($stat, "Out") !== FALSE || strpos($stat, "Retrans") !== FALSE || strpos($stat, "Attempt") !== FALSE) {
        $rrd_list[$i]['invert'] = TRUE;
    }
}

$colours = 'mixed';

$nototal    = 1;
$simple_rrd = 1;

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

?>
