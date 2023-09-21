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

$colours      = "mixed";
$nototal      = (($width < 224) ? 1 : 0);
$unit_text    = "Requests";
$rrd_filename = get_rrd_path($device, "app-bind-" . $app['app_id'] . "-query-out-default.rrd");

#$rrtypes = array('A', 'AAAA', 'PTR', 'ANY', 'IXFR', 'AXFR');
$rrtypes  = ['SOA', 'A', 'AAAA', 'NS', 'MX', 'CNAME', 'DNAME', 'TXT', 'SPF', 'SRV', 'SSHFP', 'TLSA', 'IPSECKEY', 'PTR', 'DNSKEY', 'RRSIG', 'NSEC', 'NSEC3', 'NSEC3PARAM', 'DS', 'DLV', 'ANY', 'IXFR', 'AXFR'];
$inverted = ['ANY', 'IXFR', 'AXFR'];
$array    = [];
foreach ($rrtypes as $rrtype) {
    $array[$rrtype] = ['descr' => $rrtype];
}
$i = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['invert']   = in_array($data['descr'], $inverted);
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

#include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");
include($config['html_dir'] . "/includes/graphs/generic_multi.inc.php");

// EOF
