<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/* Canceled converting

$sql = "SELECT * FROM `device_graphs` WHERE 1";
$sql .= generate_query_values_and(['hr_processes', 'hr_users'], 'graph');
$device_graphs = dbFetchRows($sql);

if (count($device_graphs))
{
  echo 'Converting HOST-RESOURCES-MIB counter RRDs: ';

  $counter_ds = 'counter:DERIVE:600:-20000:U';

  foreach ($device_graphs as $entry)
  {

    $device = device_by_id_cache($entry['device_id']);
    //print_vars($entry);

    if ($entry['graph'] == 'hr_processes')
    {
      $old_ds  = 'procs';
      $new_ds  = 'sensor';
      $old_rrd = 'hr_processes.rrd';
      $new_rrd = 'counter-counter-HOST-RESOURCES-MIB-hrSystemProcesses-0.rrd';
    }
    elseif ($entry['graph'] == 'hr_users')
    {
      $old_ds  = 'users';
      $new_ds  = 'sensor';
      $old_rrd = 'hr_users.rrd';
      $new_rrd = 'counter-counter-HOST-RESOURCES-MIB-hrSystemNumUsers-0.rrd';
    } else {
      continue;
    }

    // Now try to rename rrd and add counter DS
    if (rename_rrd($device, $old_rrd, $new_rrd))
    {
      $filename = get_rrd_path($device, $new_rrd);
      rrdtool_rename_ds($device, $filename, $old_ds, $new_ds);
      rrdtool_add_ds($device, $filename, $counter_ds);
      echo('.');
    }
  }
}
*/

// EOF

