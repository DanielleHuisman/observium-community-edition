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

foreach (dbFetchRows("SELECT * FROM `devices`") AS $device)
{

  $device['state'] = safe_unserialize($device['device_state']);

  $devices[$device['device_id']] = $device;

  foreach($device['state']['poller_mod_perf'] AS $mod => $time)
  {
    $mods[$mod]['time'] += $time;
    $mods[$mod]['count']++;
    $mod_total += $time;
  }
}

$mods = array_sort_by($mods, 'time', SORT_DESC, SORT_NUMERIC);


foreach($mods as $mod => $mod_data)
{

  $groups[$mod]['descr'] = $mod;

  foreach ($devices AS $device_id => $device)
  {

    $rrd_filename = get_rrd_path($device, 'perf-pollermodule-'.$mod.'.rrd');

    if (rrd_is_file($rrd_filename))
    {
      $groups[$mod]['list'][] = array ('filename' => $rrd_filename,
                                       'descr'    => str_pad($device['hostname'],25) ." (".$device['os'].")",
                                       'ds'       => "val");
    }
  }
}

$units       = 'Seconds';
$total_units = 'Sec';
$colours     = 'mixed';

#$scale_min = "0";
#$scale_max = "100";

#$divider = $i;
#$text_orig = 1;
$nototal = 1;

include($config['html_dir']."/includes/graphs/generic_multi_group_simplex_separated.inc.php");

// EOF
