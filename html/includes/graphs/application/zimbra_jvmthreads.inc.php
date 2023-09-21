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

$rrd_filename = get_rrd_path($device, "app-zimbra-threads.rrd");

$array = [
  'ImapSSLServer'      => ['descr' => 'IMAP SSL Server'],
  'ImapServer'         => ['descr' => 'IMAP Server'],
  'LmtpServer'         => ['descr' => 'LMTP Server'],
  'Pop3SSLServer'      => ['descr' => 'POP3 SSL Server'],
  'Pop3Server'         => ['descr' => 'POP3 Server'],
  'GC'                 => ['descr' => 'Garbage Collection'],
  'AnonymousIoService' => ['descr' => 'Anonymous I/O Service'],
  'CloudRoutingReader' => ['descr' => 'Cloud Routing Reader'],
  'ScheduledTask'      => ['descr' => 'Scheduled Task'],
  'SocketAcceptor'     => ['descr' => 'Socket Acceptor'],
  'Thread'             => ['descr' => 'Thread'],
  'Timer'              => ['descr' => 'Timer'],
  'btpool'             => ['descr' => 'BT Pool'],
  'pool'               => ['descr' => 'Pool'],
  'other'              => ['descr' => 'Other'],
];

$nototal   = 1;
$colours   = "mixed";
$unit_text = "Threads";

$i = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $variables) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $variables['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = ($variables['colour'] ? $variables['colour'] : $config['graph_colours'][$colours][$i]);
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

unset($rrd_list);

$noheader = 1;

$array = [
  'total' => ['descr' => 'Total', 'colour' => '000000'],
];

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $variables) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $variables['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = ($variables['colour'] ? $variables['colour'] : $config['graph_colours'][$colours][$i]);
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
