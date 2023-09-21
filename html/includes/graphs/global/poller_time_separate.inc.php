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

$i = 0;

$query   = "SELECT * FROM `devices`";
$devices = dbFetchRows($query, $sql_param);

foreach ($devices as $device) {
    $rrd_filename = get_rrd_path($device, "perf-poller.rrd");

    if (device_permitted($device) && rrd_is_file($rrd_filename, TRUE)) {

        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = str_pad($device['hostname'], 25) . " (" . $device['os'] . ")";
        $rrd_list[$i]['ds']       = "val";
        $i++;
    }
}

$unit_text = "Load %";

$units       = 'Seconds';
$total_units = 'Sec';
$colours     = 'mixed-q12';

#$scale_min = "0";
#$scale_max = "100";

#$divider = $i;
#$text_orig = 1;
$nototal = 1;

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
