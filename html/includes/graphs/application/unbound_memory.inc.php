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

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$scale_min    = 0;
$colours      = "blue";
$nototal      = 1;
$unit_text    = "Bytes";
$rrd_filename = get_rrd_path($device, "app-unbound-" . $app['app_id'] . "-memory.rrd");
$array        = [
  'memCacheRRset'   => ['descr' => 'RRset cache', 'colour' => '003366FF'],
  'memCacheMessage' => ['descr' => 'Message cache', 'colour' => '336699FF'],
  'memModIterator'  => ['descr' => 'Iterator module', 'colour' => '6699CCFF'],
  'memModValidator' => ['descr' => 'Validator module', 'colour' => '99CCEEFF'],
];

#DS:memTotal:DERIVE:600:0:125000000000 \

$i = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = $data['colour'];
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
