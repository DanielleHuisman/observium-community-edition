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

/*
  DS:tmx2xxtransactions:COUNTER:600:0:125000000000 \
  DS:tmx3xxtransactions:COUNTER:600:0:125000000000 \
  DS:tmx4xxtransactions:COUNTER:600:0:125000000000 \
  DS:tmx5xxtransactions:COUNTER:600:0:125000000000 \
  DS:tmx6xxtransactions:COUNTER:600:0:125000000000 \
  DS:tmxUACtransactions:COUNTER:600:0:125000000000 \
  DS:tmxUAStransactions:COUNTER:600:0:125000000000 \
  DS:tmxinusetransaction:GAUGE:600:0:125000000000 \
  DS:tmxlocalreplies:COUNTER:600:0:125000000000 \
*/

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_filename = get_rrd_path($device, "app-kamailio-" . $app['app_id'] . ".rrd");

$array = ['tmx2xxtransactions'  => ['descr' => '2XX Transactions'],
          'tmx3xxtransactions'  => ['descr' => '3XX Transactions'],
          'tmx4xxtransactions'  => ['descr' => '4XX Transactions'],
          'tmx5xxtransactions'  => ['descr' => '5XX Transactions'],
          'tmx6xxtransactions'  => ['descr' => '6XX Transactions'],
          'tmxUACtransactions'  => ['descr' => 'UAC Transactions'],
          'tmxUAStransactions'  => ['descr' => 'UAS Transactions'],
          'tmxinusetransaction' => ['descr' => 'InUse Transactions'],
          'tmxlocalreplies'     => ['descr' => 'Local Replies'],
];

$i = 0;
if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

$colours = "mixed";

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF