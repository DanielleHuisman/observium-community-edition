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

$rrd_filename = get_rrd_path($device, "app-kamailio-" . $app['app_id'] . ".rrd");

$array = ['corebadURIsrcvd'     => ['descr' => 'Bad URIs Recieved'],
          'corebadmsghdr'       => ['descr' => 'Bad Msg Header'],
          'coreerrreplies'      => ['descr' => 'Error Replies'],
          'coreerrrequests'     => ['descr' => 'Error Requests'],
          'coreunsupportedmeth' => ['descr' => 'Unsupported Methods'],
          'dnsfaileddnsrequest' => ['descr' => 'DNS Failed Requests'],
          'mysqldrivererrors'   => ['descr' => 'MySQL Driver Errors'],
          'slfailures'          => ['descr' => 'SL Failures'],
          'registrarrejregs'    => ['descr' => 'Rejected Registrations'],
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