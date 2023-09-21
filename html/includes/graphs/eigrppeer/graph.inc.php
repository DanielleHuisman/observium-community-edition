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

// 'cEigrpHoldTime', 'cEigrpUpTime', 'cEigrpSrtt', 'cEigrpRto', 'cEigrpPktsEnqueued', 'cEigrpLastSeq', 'cEigrpRetrans', 'cEigrpRetries'

$array = ['HoldTime'     => ['descr' => 'Holdtime', 'colour' => '22FF22'],
          'UpTime'       => ['descr' => 'Uptime', 'colour' => '0022FF'],
          'Srtt'         => ['descr' => 'SRTT', 'colour' => 'FF0000'],
          'Rto'          => ['descr' => 'RTO', 'colour' => '00AAAA'],
          'PktsEnqueued' => ['descr' => 'Packets Enqueued', 'colour' => 'FF00FF'],
          'LastSeq'      => ['descr' => 'Last Seq', 'colour' => 'FFA500'],
          'Retrans'      => ['descr' => 'Retransmissions', 'colour' => 'CC0000'],
          'Retries'      => ['descr' => 'Retries', 'colour' => '0000CC'],
];

$i = 0;
if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $entry) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $entry['descr'];
        $rrd_list[$i]['ds']       = $ds;
#    $rrd_list[$i]['colour'] = $entry['colour'];
        $i++;
    }
} else {
    echo("file missing: $file");
}

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Packets";

$log_y = TRUE;

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
