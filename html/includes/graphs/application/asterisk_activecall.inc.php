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

$scale_min = 0;

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_filename = get_rrd_path($device, "app-asterisk-" . $app['app_id'] . ".rrd");

$array = ['activechan'  => ['descr' => 'Active Channels', 'colour' => '750F7DFF'],
          'activecall'  => ['descr' => 'Active Calls', 'colour' => '00FF00FF'],
          'iaxchannels' => ['descr' => 'IAX Channels', 'colour' => '4444FFFF'],
          'sipchannels' => ['descr' => 'SIP Channels', 'colour' => '157419FF'],
];

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

$colours   = "mixed";
$nototal   = 0;
$unit_text = "Channels";

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

#include($config['html_dir']."/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
