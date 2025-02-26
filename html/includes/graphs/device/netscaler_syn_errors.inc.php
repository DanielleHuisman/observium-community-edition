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

//$scale_min = 0;
$colours      = "mixed";
$nototal      = 0;
$unit_text    = "Errors";
$rrd_filename = get_rrd_path($device, "netscaler-stats-tcp.rrd");
$log_y        = TRUE;

$array = [
  'ErrBadCheckSum'   => ['descr' => 'ErrBadCheckSum'],
  'ErrSynInSynRcvd'  => ['descr' => 'ErrSynInSynRcvd'],
  'ErrSynInEst'      => ['descr' => 'ErrSynInEst'],
  'ErrSynGiveUp'     => ['descr' => 'ErrSynGiveUp'],
  'ErrSynSentBadAck' => ['descr' => 'ErrSynSentBadAck'],
  'ErrSynRetry'      => ['descr' => 'ErrSynRetry'],
  'ErrFinRetry'      => ['descr' => 'ErrFinRetry'],
  'ErrFinGiveUp'     => ['descr' => 'ErrFinGiveUp'],
  'ErrFinDup'        => ['descr' => 'ErrFinDup']
];

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = $config['graph_colours'][$colours][$i];
        $i++;
    }
} else {
    echo("file missing: $file");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
