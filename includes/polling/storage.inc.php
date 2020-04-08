<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$table_rows = array();

if (!isset($cache_storage)) { $cache_storage = array(); } // This cache used also in storage module

$sql  = "SELECT `storage`.*";
$sql .= " FROM  `storage`";
//$sql .= " LEFT JOIN `storage-state` ON `storage`.storage_id = `storage-state`.storage_id";
$sql .= " WHERE `device_id` = ?";

foreach (dbFetchRows($sql, array($device['device_id'])) as $storage)
{
  $storage_size = $storage['storage_size']; // Memo old size
  $file = $config['install_dir']."/includes/polling/storage/".strtolower($storage['storage_mib']).".inc.php";
  if (is_file($file))
  {
    include($file);
  } else {
     // Check if we can poll the device ourselves with generic code using definitions.
     // Table is always set when defintions add storages.
     if ($storage['storage_type'] != '' && is_array($config['mibs'][$storage['storage_mib']]['storage'][$storage['storage_type']]))
     {
        $table_def = $config['mibs'][$storage['storage_mib']]['storage'][$storage['storage_type']];

        print_r($config['mibs'][$storage['storage_mib']]['storage']);

        if ($table_def['type'] == 'static')
        {

           if      (isset($table_def['oid_perc_num']))  { $storage['perc'] = snmp_get_oid($device, $table_def['oid_perc_num']); }
           else if (isset($table_def['oid_perc']))      { $storage['perc'] = snmp_get_oid($device, $table_def['oid_perc'], $storage['storage_mib']); }

           if      (isset($table_def['oid_free_num']))  { $storage['free'] = snmp_get_oid($device, $table_def['oid_free_num']); }
           else if (isset($table_def['oid_free']))      { $storage['free'] = snmp_get_oid($device, $table_def['oid_free'], $storage['storage_mib']); }

           if      (isset($table_def['oid_used_num']))  { $storage['used'] = snmp_get_oid($device, $table_def['oid_used_num']); }
           else if (isset($table_def['oid_used']))      { $storage['used'] = snmp_get_oid($device, $table_def['oid_used'], $storage['storage_mib']); }

           if      (isset($table_def['total']))         { $storage['total'] = $table_def['total']; }
           else if (isset($table_def['oid_total_num'])) { $storage['total'] = snmp_get_oid($device, $table_def['oid_total_num']); }
           else if (isset($table_def['oid_total']))     { $storage['total'] = snmp_get_oid($device, $table_def['oid_total'], $storage['storage_mib']); }

        } else {

           if      (isset($table_def['oid_perc_num']))  { $storage['perc'] = snmp_get_oid($device, $table_def['oid_perc_num'].'.'.$storage['storage_index']); }
           else if (isset($table_def['oid_perc']))      { $storage['perc'] = snmp_get_oid($device, $table_def['oid_perc'].'.'.$storage['storage_index'], $storage['storage_mib']); }

           if      (isset($table_def['oid_free_num']))  { $storage['free'] = snmp_get_oid($device, $table_def['oid_free_num'].'.'.$storage['storage_index']); }
           else if (isset($table_def['oid_free']))      { $storage['free'] = snmp_get_oid($device, $table_def['oid_free'].'.'.$storage['storage_index'], $storage['storage_mib']); }

           if      (isset($table_def['oid_used_num']))  { $storage['used'] = snmp_get_oid($device, $table_def['oid_used_num'].'.'.$storage['storage_index']); }
           else if (isset($table_def['oid_used']))      { $storage['used'] = snmp_get_oid($device, $table_def['oid_used'].'.'.$storage['storage_index'], $storage['storage_mib']); }

           if      (isset($table_def['total']))         { $storage['total'] = $table_def['total']; }
           else if (isset($table_def['oid_total_num'])) { $storage['total'] = snmp_get_oid($device, $table_def['oid_total_num'].'.'.$storage['storage_index']); }
           else if (isset($table_def['oid_total']))     { $storage['total'] = snmp_get_oid($device, $table_def['oid_total'].'.'.$storage['storage_index'], $storage['storage_mib']); }

        }
        // Clean not numeric symbols from snmp output
        foreach (array('perc', 'free', 'used', 'total') as $param)
        {
          if (isset($storage[$param])) { $storage[$param] = snmp_fix_numeric($storage[$param]); }
        }

        // Merge calculated used/total/free/perc array keys into $storage variable (with additional options)
        $storage = array_merge($storage, calculate_mempool_properties($storage['storage_multiplier'], $storage['used'], $storage['total'], $storage['free'], $storage['perc'], $table_def));
        $storage['size'] = $storage['total'];
     } else {
        // Unknown, so force rediscovery as there's a broken storage
        force_discovery($device, 'storage');
     }
  }

  if (OBS_DEBUG && count($storage)) { print_vars($storage); }

  if ($storage['size'])
  {
    $percent = round($storage['used'] / $storage['size'] * 100, 2);
  } else {
    $percent = 0;
  }

  $hc = ($storage['storage_hc'] ? ' (HC)' : '');

  // Update StatsD/Carbon
  if ($config['statsd']['enable'] == TRUE)
  {
    StatsD::gauge(str_replace(".", "_", $device['hostname']).'.'.'storage'.'.' .$storage['storage_mib'] . "-" . safename($storage['storage_descr']).".used", $storage['used']);
    StatsD::gauge(str_replace(".", "_", $device['hostname']).'.'.'storage'.'.' .$storage['storage_mib'] . "-" . safename($storage['storage_descr']).".free", $storage['free']);
  }

  // Update RRD


  rrdtool_update_ng($device, 'storage', array('used' => $storage['used'], 'free' => $storage['free']), strtolower($storage['storage_mib']) . "-" . $storage['storage_descr']);

  //if (!is_numeric($storage['storage_polled']))
  //{
  //  dbInsert(array('storage_id'     => $storage['storage_id'],
  //                 'storage_polled' => time(),
  //                 'storage_used'   => $storage['used'],
  //                 'storage_free'   => $storage['free'],
  //                 'storage_size'   => $storage['size'],
  //                 'storage_units'  => $storage['units'],
  //                 'storage_perc'   => $percent), 'storage-state');
  //} else {
    $update = dbUpdate(array('storage_polled' => time(),
                             'storage_used'   => $storage['used'],
                             'storage_free'   => $storage['free'],
                             'storage_size'   => $storage['size'],
                             'storage_units'  => $storage['units'],
                             'storage_perc'   => $percent), 'storage', '`storage_id` = ?', array($storage['storage_id']));
    if (formatStorage($storage_size) != formatStorage($storage['size']))
        //&& (abs($storage_size - $storage['size']) / max($storage_size, $storage['size'])) > 0.0001 ) // Log only if size diff more than 0.01%
    {
      log_event('Storage size changed: '.formatStorage($storage_size).' -> '.formatStorage($storage['size']).' ('.$storage['storage_descr'].')', $device, 'storage', $storage['storage_id']);
    }
  //}
  $graphs['storage'] = TRUE;

  // Check alerts
  check_entity('storage', $storage, array('storage_perc' => $percent, 'storage_free' => $storage['free'], 'storage_used' => $storage['used']));

  $table_row = array();
  $table_row[] = $storage['storage_descr'];
  $table_row[] = $storage['storage_mib'];
  $table_row[] = $storage['storage_index'];
  $table_row[] = formatStorage($storage['size']);
  $table_row[] = formatStorage($storage['used']);
  $table_row[] = formatStorage($storage['free']);
  $table_row[] = $percent.'%';
  $table_rows[] = $table_row;
  unset($table_row);

}

$headers = array('%WLabel%n', '%WType%n', '%WIndex%n', '%WTotal%n', '%WUsed%n', '%WFree%n', '%WPerc%n');
print_cli_table($table_rows, $headers);

unset($storage, $table, $table_row, $table_rows);

// EOF
