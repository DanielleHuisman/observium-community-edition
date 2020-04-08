<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

//r($vars);

$i = 0;

foreach (dbFetchRows("SELECT * FROM `devices`") AS $device)
{
  // Reference the cache.
  //$device = &$cache['devices']['id'][$id];

  $device['state'] = unserialize($device['device_state']);

  foreach($device['state']['poller_mod_perf'] AS $mod => $time)
  {
    $mods[$mod]['time'] += $time;
    $mods[$mod]['count']++;
    $mod_total += $time;
    if($mod == $vars['module'])
    {
      $device['mod_time'] = $time;
      $devices[$device['device_id']] = $device;
    }
  }
}

//r($devices);

$devices = array_sort_by($devices, 'mod_time', SORT_DESC, SORT_NUMERIC);

foreach ($devices AS $device_id => $device)
{

  $rrd_filename = get_rrd_path($device, 'perf-pollermodule-'.$vars['module'].'.rrd');

  if (is_file($rrd_filename))
  {

    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr'] = str_pad($device['hostname'],25) ." (".$device['os'].")";
    $rrd_list[$i]['ds'] = "val";
    $i++;
  }
}

$units       = 'Seconds';
$total_units = 'Sec';
$colours     = 'lgreen';

$scale_min = "0";
#$scale_max = "100";

#$divider = $i;
#$text_orig = 1;
$nototal = 1;

include($config['html_dir']."/includes/graphs/generic_multi_simplex_separated.inc.php");



?>
