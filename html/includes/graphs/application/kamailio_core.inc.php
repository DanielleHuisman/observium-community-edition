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
  DS:corebadURIsrcvd:COUNTER:600:0:125000000000 \
  DS:corebadmsghdr:COUNTER:600:0:125000000000 \
  DS:coredropreplies:COUNTER:600:0:125000000000 \
  DS:coredroprequests:COUNTER:600:0:125000000000 \
  DS:coreerrreplies:COUNTER:600:0:125000000000 \
  DS:coreerrrequests:COUNTER:600:0:125000000000 \
  DS:corefwdreplies:COUNTER:600:0:125000000000 \
  DS:corefwdrequests:COUNTER:600:0:125000000000 \
  DS:corercvreplies:COUNTER:600:0:125000000000 \
  DS:corercvrequests:COUNTER:600:0:125000000000 \
  DS:coreunsupportedmeth:COUNTER:600:0:125000000000 \
*/

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_filename = get_rrd_path($device, "app-kamailio-" . $app['app_id'] . ".rrd");

$array = ['corebadURIsrcvd'     => ['descr' => 'Bad URIs Recieved'],
          'corebadmsghdr'       => ['descr' => 'Bad Msg Header'],
          'coredropreplies'     => ['descr' => 'Dropped Replies'],
          'coredroprequests'    => ['descr' => 'Drop Requests'],
          'coreerrreplies'      => ['descr' => 'Error Replies'],
          'coreerrrequests'     => ['descr' => 'Error Requests'],
          'corefwdreplies'      => ['descr' => 'Forward Replies'],
          'corefwdrequests'     => ['descr' => 'Forward Requests'],
          'corercvrequests'     => ['descr' => 'Recieved Replies'],
          'corercvreplies'      => ['descr' => 'Recieved Requests'],
          'coreunsupportedmeth' => ['descr' => 'Unsupported Methods'],
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