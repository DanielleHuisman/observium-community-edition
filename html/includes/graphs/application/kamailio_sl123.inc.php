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
  DS:sl1xxreplies:COUNTER:600:0:125000000000 \
  DS:sl200replies:COUNTER:600:0:125000000000 \
  DS:sl202replies:COUNTER:600:0:125000000000 \
  DS:sl2xxreplies:COUNTER:600:0:125000000000 \
  DS:sl300replies:COUNTER:600:0:125000000000 \
  DS:sl301replies:COUNTER:600:0:125000000000 \
  DS:sl302replies:COUNTER:600:0:125000000000 \
  DS:sl3xxreplies:COUNTER:600:0:125000000000 \
*/

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_filename = get_rrd_path($device, "app-kamailio-" . $app['app_id'] . ".rrd");

$array = ['sl1xxreplies' => ['descr' => '1XX Replies'],
          'sl200replies' => ['descr' => '200 Replies'],
          'sl202replies' => ['descr' => '202 Replies'],
          'sl2xxreplies' => ['descr' => '2XX Replies'],
          'sl300replies' => ['descr' => '300 Replies'],
          'sl301replies' => ['descr' => '301 Replies'],
          'sl302replies' => ['descr' => '302 Replies'],
          'sl3xxreplies' => ['descr' => '3XX Replies'],
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
