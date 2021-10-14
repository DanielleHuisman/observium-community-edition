<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

//r($vars);

$i = 0;

foreach (dbFetchRows("SELECT * FROM `devices`") as $device) {
  $devices[$device['device_id']] = $device;
}

$devices = array_sort_by($devices, 'last_polled_timetaken', SORT_DESC, SORT_NUMERIC);

foreach ($devices as $device_id => $device) {

  $rrd_filename = get_rrd_path($device, 'perf-poller.rrd');

  if (rrd_is_file($rrd_filename)) {

    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr'] = str_pad($device['hostname'], 25) ." (".$device['os'].")";
    $rrd_list[$i]['ds'] = "val";
    $i++;
  }
}

$units       = 'Seconds';
$total_units = 'Sec';

$colours     = 'bluegrey';

#$scale_min = "0";
#$scale_max = "100";

#$divider = $i;
#$text_orig = 1;
$nototal = 1;

include($config['html_dir']."/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
