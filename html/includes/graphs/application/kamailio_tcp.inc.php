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
  DS:tcpconreset:GAUGE:600:0:125000000000 \
  DS:tcpcontimeout:GAUGE:600:0:125000000000 \
  DS:tcpconnectfailed:GAUGE:600:0:125000000000 \
  DS:tcpconnectsuccess:GAUGE:600:0:125000000000 \
  DS:tcpcurrentopenedcon:GAUGE:600:0:125000000000 \
  DS:tcpcurrentwrqsize:GAUGE:600:0:125000000000 \
  DS:tcpestablished:GAUGE:600:0:125000000000 \
  DS:tcplocalreject:GAUGE:600:0:125000000000 \
  DS:tcppassiveopen:GAUGE:600:0:125000000000 \
  DS:tcpsendtimeout:GAUGE:600:0:125000000000 \
  DS:tcpsendqfull:GAUGE:600:0:125000000000 \
*/


include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_filename = get_rrd_path($device, "app-kamailio-" . $app['app_id'] . ".rrd");

$array = ['tcpconreset'         => ['descr' => 'Connection Reset'],
          'tcpcontimeout'       => ['descr' => 'Connection Timeout'],
          'tcpconnectfailed'    => ['descr' => 'Connection Failed'],
          'tcpconnectsuccess'   => ['descr' => 'Connection Success'],
          'tcpcurrentopenedcon' => ['descr' => 'Open Connections'],
          'tcpcurrentwrqsize'   => ['descr' => 'Write Queue Size'],
          'tcpestablished'      => ['descr' => 'Establiched'],
          'tcplocalreject'      => ['descr' => 'Local Rejected'],
          'tcppassiveopen'      => ['descr' => 'Passiive Open'],
          'tcpsendtimeout'      => ['descr' => 'Send Timeout'],
          'tcpsendqfull'        => ['descr' => 'Send Queue Full'],
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
