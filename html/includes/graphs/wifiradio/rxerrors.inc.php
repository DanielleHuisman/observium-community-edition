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

// $scale_min = 0;
$colours   = "reds";
$nototal   = 0;
$unit_text = "Errors";

$log_y = FALSE;

$array = [
  'rx_errors'         => ['descr' => 'Errors'],
  'rx_wep_fail'       => ['descr' => 'WEP Errors'],
  'rx_mic_error'      => ['descr' => 'MIC Errors'],
  'rx_decrypt_crcerr' => ['descr' => 'Decrypt CRC Errors']
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
