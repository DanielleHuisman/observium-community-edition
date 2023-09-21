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

$array = ['UMcasts'        => ['descr' => 'Unreliable Mcast', 'colour' => '22FF22'],
          'RMcasts'        => ['descr' => 'Reliable Mcast', 'colour' => '0022FF'],
          'UUcasts'        => ['descr' => 'Unreliable Ucast', 'colour' => 'FF0000'],
          'RUcasts'        => ['descr' => 'Reliable Ucast', 'colour' => '00AAAA'],
          'McastExcepts'   => ['descr' => 'Mcast Excepts', 'colour' => 'FF00FF'],
          'CRpkts'         => ['descr' => 'CR Packets', 'colour' => 'FFA500'],
          'AcksSuppressed' => ['descr' => 'Acks Suppressed', 'colour' => 'CC0000'],
          'RetransSent'    => ['descr' => 'Retransmits Sent', 'colour' => '0000CC'],
          'OOSrvcd'        => ['descr' => 'Out-of-Sequence', 'colour' => '0080C0'],
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

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
