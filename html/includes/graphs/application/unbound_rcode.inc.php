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
$colours      = "mixed";
$nototal      = 1;
$unit_text    = "Answer packets/sec";
$rrd_filename = get_rrd_path($device, "app-unbound-" . $app['app_id'] . "-queries.rrd");

$i     = 0;
$array = [];

$dns_rcode = ['FORMERR', 'NOERROR', 'NOTAUTH', 'NOTIMPL', 'NOTZONE', 'NXDOMAIN', 'NXRRSET', 'REFUSED', 'SERVFAIL', 'YXDOMAIN', 'YXRRSET', 'nodata'];

$colours = $config['graph_colours']['mixed']; // needs moar colours!

foreach ($dns_rcode as $rcode) {
    $array["rcode$rcode"] = ['descr' => strtoupper($rcode), 'colour' => $colours[(safe_count($array) % safe_count($colours))]];
}

// FIXME maybe these need their own DNSSEC graph? In munin, they are on this graph.
foreach (['numAnswerSecure' => 'Secure answers', 'numAnswerBogus' => 'Bogus answers', 'numRRSetBogus' => "RRsets marked bogus"] as $key => $descr) {
    $array[$key] = ['descr' => $descr, 'colour' => $colours[(safe_count($array) % safe_count($colours))]];
}

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
