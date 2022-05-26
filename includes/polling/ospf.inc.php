<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

/// FIXME. Rewrite (clean), clean stale entries (after disable)
// Pre-polling checks
if (!$config['enable_ospf'] || !is_device_mib($device, 'OSPF-MIB'))
{
  // OSPF not enabled or MIB excluded
  return;
}
/*
OSPF-MIB::ospfRouterId.0 = IpAddress: 1.185.1.113
OSPF-MIB::ospfAdminStat.0 = INTEGER: enabled(1)
OSPF-MIB::ospfVersionNumber.0 = INTEGER: version2(2)
OSPF-MIB::ospfAreaBdrRtrStatus.0 = INTEGER: false(2)
OSPF-MIB::ospfASBdrRtrStatus.0 = INTEGER: true(1)
OSPF-MIB::ospfExternLsaCount.0 = Gauge32: 104
OSPF-MIB::ospfExternLsaCksumSum.0 = INTEGER: 3322892
OSPF-MIB::ospfTOSSupport.0 = INTEGER: false(2)
OSPF-MIB::ospfOriginateNewLsas.0 = Counter32: 11993
OSPF-MIB::ospfRxNewLsas.0 = Counter32: 553365
OSPF-MIB::ospfExtLsdbLimit.0 = INTEGER: -1
OSPF-MIB::ospfMulticastExtensions.0 = INTEGER: 0
OSPF-MIB::ospfExitOverflowInterval.0 = INTEGER: 0
OSPF-MIB::ospfDemandExtensions.0 = INTEGER: false(2)
*/

$ospf_instance_count = 0;
$ospf_port_count     = 0;
$ospf_area_count     = 0;
$ospf_nbr_count      = 0;

$set_ospf            = FALSE;

$ospf_oids_db = array('ospfRouterId', 'ospfAdminStat', 'ospfVersionNumber', 'ospfAreaBdrRtrStatus', 'ospfASBdrRtrStatus',
                      'ospfExternLsaCount', 'ospfExternLsaCksumSum', 'ospfTOSSupport', 'ospfOriginateNewLsas', 'ospfRxNewLsas',
                      'ospfExtLsdbLimit', 'ospfMulticastExtensions', 'ospfExitOverflowInterval', 'ospfDemandExtensions');

// Build array of existing entries
$ospf_instance_db = dbFetchRow('SELECT * FROM `ospf_instances` WHERE `device_id` = ?', array($device['device_id']));

// Pull data from device
$ospf_instance_poll = snmpwalk_cache_oid($device, 'ospfGeneralGroup', array(), 'OSPF-MIB');
if (is_array($ospf_instance_poll[0]))
{
  $ospf_instance_poll = $ospf_instance_poll[0];

  // Don't bother polling everything if we have no enabled or non-defaulted router ids.
  if ($ospf_instance_poll['ospfRouterId']  != '0.0.0.0' ||
      $ospf_instance_poll['ospfAdminStat'] != 'disabled')
  {
    $set_ospf = TRUE;
  }
}


$ospf_areas_poll = array();
$ospf_ports_poll = array();
$ospf_nbrs_poll = array();

if ($set_ospf)
{
  $ospf_areas_poll = snmpwalk_cache_oid($device, 'ospfAreaEntry', $ospf_areas_poll, 'OSPF-MIB');
  $ospf_ports_poll = snmpwalk_cache_oid($device, 'ospfIfEntry',   $ospf_ports_poll, 'OSPF-MIB');
  $ospf_nbrs_poll  = snmpwalk_cache_oid($device, 'ospfNbrEntry',  $ospf_nbrs_poll,  'OSPF-MIB');
}

print_cli_data_field('Processes', 2);

if (empty($ospf_instance_db) && !empty($ospf_instance_poll))
{
  dbInsert(array('device_id' => $device['device_id']), 'ospf_instances');
  $ospf_instance_db = dbFetchRow('SELECT * FROM `ospf_instances` WHERE `device_id` = ?', array($device['device_id']));
  echo('+');
}
else if (!empty($ospf_instance_db) && empty($ospf_instance_poll))
{
  dbDelete('ospf_instances', '`device_id` = ?', array($device['device_id']));
  echo('-');
}

if (OBS_DEBUG && $set_ospf)
{
  echo("\nPolled: ");
  print_vars($ospf_instance_poll);
  echo('Database: ');
  print_vars($ospf_instance_db);
  echo("\n");
}

// Loop array of entries and update
if (is_array($ospf_instance_db))
{
  $ospf_instance_id     = $ospf_instance_db['ospf_instance_id'];
  $ospf_instance_update = array();
  foreach ($ospf_oids_db as $oid)
  { // Loop the OIDs
    if ($ospf_instance_db[$oid] != $ospf_instance_poll[$oid])
    { // If data has changed, build a query
      $ospf_instance_update[$oid] = $ospf_instance_poll[$oid];
      // log_event("$oid -> ".$this_port[$oid], $device, 'ospf', $port['port_id']); // FIXME
    }
  }

  if ($ospf_instance_update)
  {
    dbUpdate($ospf_instance_update, 'ospf_instances', '`device_id` = ? AND `ospf_instance_id` = ?', array($device['device_id'], $ospf_instance_id));
    echo('U');
    unset($ospf_instance_update);
  } else {
    echo('.');
  }

  $ospf_instance_count++; // Really in OSPF-MIB always only 1 instance (process)
}

unset($ospf_instance_poll);
unset($ospf_instance_db);

echo(PHP_EOL);

print_cli_data_field('Areas', 2);

$ospf_area_oids = array('ospfAuthType', 'ospfImportAsExtern', 'ospfSpfRuns', 'ospfAreaBdrRtrCount', 'ospfAsBdrRtrCount',
                        'ospfAreaLsaCount', 'ospfAreaLsaCksumSum', 'ospfAreaSummary', 'ospfAreaStatus');

// Build array of existing entries
foreach (dbFetchRows('SELECT * FROM `ospf_areas` WHERE `device_id` = ?', array($device['device_id'])) as $entry)
{
  $ospf_areas_db[$entry['ospfAreaId']] = $entry;
}

foreach ($ospf_areas_poll as $ospf_area_id => $ospf_area)
{
  // If the entry doesn't already exist in the prebuilt array, insert into the database and put into the array
  if (!isset($ospf_areas_db[$ospf_area_id]))
  {
    $insert = array();
    $insert['device_id']    = $device['device_id'];
    $insert['ospfAreaId'] = $ospf_area_id;

    foreach ($ospf_area_oids as $oid)
    { // Loop the OIDs
      $insert[$oid] = $ospf_area[$oid];
    }

    dbInsert($insert, 'ospf_areas');
    echo('+');
    $ospf_areas_db[$ospf_area_id] = dbFetchRow('SELECT * FROM `ospf_areas` WHERE `device_id` = ? AND `ospfAreaId` = ?', array($device['device_id'], $ospf_area_id));
    unset($insert);
  }
}

if (OBS_DEBUG && $set_ospf)
{
  echo("\nPolled: ");
  print_vars($ospf_areas_poll);
  echo('Database: ');
  print_vars($ospf_areas_db);
  echo("\n");
}

// Loop array of entries and update
if (is_array($ospf_areas_db))
{
  foreach ($ospf_areas_db as $ospf_area_id => $ospf_area_db)
  {

    if (is_array($ospf_areas_poll[$ospf_area_id]))
    {
      $ospf_area_poll = $ospf_areas_poll[$ospf_area_db['ospfAreaId']];
      foreach ($ospf_area_oids as $oid)
      { // Loop the OIDs
        if ($ospf_area_db[$oid] != $ospf_area_poll[$oid])
        { // If data has changed, build a query
          $ospf_area_update[$oid] = $ospf_area_poll[$oid];
          // log_event("$oid -> ".$this_port[$oid], $device, 'port', $port['port_id']); // FIXME
        } else {
        // echo($ospf_area_db[$oid] . '=' . $ospf_area_poll[$oid]);
        }
      }
      if ($ospf_area_update)
      {
        dbUpdate($ospf_area_update, 'ospf_areas', '`device_id` = ? AND `ospfAreaId` = ?', array($device['device_id'], $ospf_area_id));
        echo('U');
        unset($ospf_area_update);
      } else {
        echo('.');
      }

      $ospf_area_count++;
    } else {
      dbDelete('ospf_areas', '`device_id` = ? AND `ospfAreaId` = ?', array($device['device_id'], $ospf_area_db['ospfAreaId']));
      echo '-';
    }
  }
}

unset($ospf_areas_db);
unset($ospf_areas_poll);

echo PHP_EOL;

print_cli_data_field('Ports', 2);

$ospf_port_oids = array('ospfIfIpAddress','port_id','ospfAddressLessIf','ospfIfAreaId','ospfIfType','ospfIfAdminStat','ospfIfRtrPriority','ospfIfTransitDelay','ospfIfRetransInterval','ospfIfHelloInterval','ospfIfRtrDeadInterval','ospfIfPollInterval','ospfIfState','ospfIfDesignatedRouter','ospfIfBackupDesignatedRouter','ospfIfEvents','ospfIfAuthKey','ospfIfStatus','ospfIfMulticastForwarding','ospfIfDemand','ospfIfAuthType');

// Build array of existing entries
foreach (dbFetchRows('SELECT * FROM `ospf_ports` WHERE `device_id` = ?', array($device['device_id'])) as $entry)
{
  $ospf_ports_db[$entry['ospf_port_id']] = $entry;
}

foreach ($ospf_ports_poll as $ospf_port_id => $ospf_port)
{
  // If the entry doesn't already exist in the prebuilt array, insert into the database and put into the array
  if (!isset($ospf_ports_db[$ospf_port_id]))
  {
    dbInsert(array('device_id' => $device['device_id'], 'ospf_port_id' => $ospf_port_id), 'ospf_ports');
    echo('+');
    $ospf_ports_db[$entry['ospf_port_id']] = dbFetchRow('SELECT * FROM `ospf_ports` WHERE `device_id` = ? AND `ospf_port_id` = ?', array($device['device_id'], $ospf_port_id));
  }
}

if (OBS_DEBUG && $set_ospf)
{
  echo("\nPolled: ");
  print_vars($ospf_ports_poll);
  echo('Database: ');
  print_vars($ospf_ports_db);
  echo("\n");
}

// Loop array of entries and update
if (is_array($ospf_ports_db))
{
  foreach ($ospf_ports_db as $ospf_port_id => $ospf_port_db)
  {
    if (is_array($ospf_ports_poll[$ospf_port_db['ospf_port_id']]))
    {
      $ospf_port_poll = $ospf_ports_poll[$ospf_port_db['ospf_port_id']];

      if ($ospf_port_poll['ospfAddressLessIf']) {
        $ospf_port_poll['port_id'] = @dbFetchCell('SELECT `port_id` FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ?', array($device['device_id'], $ospf_port_poll['ospfAddressLessIf']));
      } else {
        //$ospf_port_poll['port_id'] = @dbFetchCell('SELECT A.`port_id` FROM ipv4_addresses AS A, ports AS I WHERE A.ipv4_address = ? AND I.port_id = A.port_id AND I.device_id = ?', array($ospf_port_poll['ospfIfIpAddress'], $device['device_id']));
        $ids = get_entity_ids_ip_by_network('port', $ospf_port_poll['ospfIfIpAddress'], generate_query_values($device['device_id'], 'device_id'));
        if (safe_count($ids)) {
          $ospf_port_poll['port_id'] = current($ids);
        }
      }

      foreach ($ospf_port_oids as $oid)
      { // Loop the OIDs
        if ($ospf_port_db[$oid] != $ospf_port_poll[$oid])
        { // If data has changed, build a query
          $ospf_port_update[$oid] = $ospf_port_poll[$oid];
          // log_event("$oid -> ".$this_port[$oid], $device, 'ospf', $port['port_id']); // FIXME
        }
      }
      if ($ospf_port_update)
      {
        dbUpdate($ospf_port_update, 'ospf_ports', '`device_id` = ? AND `ospf_port_id` = ?', array($device['device_id'], $ospf_port_id));
        echo('U');
        unset($ospf_port_update);
      } else {
        echo('.');
      }
      unset($ospf_port_poll);
      unset($ospf_port_db);
      $ospf_port_count++;
    } else {
      dbDelete('ospf_ports', '`device_id` = ? AND `ospf_port_id` = ?', array($device['device_id'], $ospf_port_db['ospf_port_id']));
      echo('-');
    }
  }
}

echo PHP_EOL;

// OSPF-MIB::ospfNbrIpAddr.172.22.203.98.0 172.22.203.98
// OSPF-MIB::ospfNbrAddressLessIndex.172.22.203.98.0 0
// OSPF-MIB::ospfNbrRtrId.172.22.203.98.0 172.22.203.128
// OSPF-MIB::ospfNbrOptions.172.22.203.98.0 2
// OSPF-MIB::ospfNbrPriority.172.22.203.98.0 0
// OSPF-MIB::ospfNbrState.172.22.203.98.0 full
// OSPF-MIB::ospfNbrEvents.172.22.203.98.0 6
// OSPF-MIB::ospfNbrLsRetransQLen.172.22.203.98.0 1
// OSPF-MIB::ospfNbmaNbrStatus.172.22.203.98.0 active
// OSPF-MIB::ospfNbmaNbrPermanence.172.22.203.98.0 dynamic
// OSPF-MIB::ospfNbrHelloSuppressed.172.22.203.98.0 false

print_cli_data_field('Neighbours',2);

$ospf_nbr_oids_db  = array('ospfNbrIpAddr', 'ospfNbrAddressLessIndex', 'ospfNbrRtrId', 'ospfNbrOptions', 'ospfNbrPriority', 'ospfNbrState', 'ospfNbrEvents', 'ospfNbrLsRetransQLen', 'ospfNbmaNbrStatus', 'ospfNbmaNbrPermanence', 'ospfNbrHelloSuppressed');
$ospf_nbr_oids_rrd = array();
$ospf_nbr_oids = array_merge($ospf_nbr_oids_db, $ospf_nbr_oids_rrd);

// Build array of existing entries
foreach (dbFetchRows('SELECT * FROM `ospf_nbrs` WHERE `device_id` = ?', array($device['device_id'])) as $nbr_entry)
{
  $ospf_nbrs_db[$nbr_entry['ospf_nbr_id']] = $nbr_entry;
}

foreach ($ospf_nbrs_poll as $ospf_nbr_id => $ospf_nbr)
{
  // If the entry doesn't already exist in the prebuilt array, insert into the database and put into the array
  if (!isset($ospf_nbrs_db[$ospf_nbr_id]))
  {
    dbInsert(array('device_id' => $device['device_id'], 'ospf_nbr_id' => $ospf_nbr_id), 'ospf_nbrs');
    echo('+');
    $entry = dbFetchRow('SELECT * FROM `ospf_nbrs` WHERE `device_id` = ? AND `ospf_nbr_id` = ?', array($device['device_id'], $ospf_nbr_id));
    $ospf_nbrs_db[$entry['ospf_nbr_id']] = $entry;
  }
}

if (OBS_DEBUG && $set_ospf)
{
  echo("\nPolled: ");
  print_vars($ospf_nbrs_poll);
  echo('Database: ');
  print_vars($ospf_nbrs_db);
  echo("\n");
}

// Loop array of entries and update
if (is_array($ospf_nbrs_db))
{
  foreach ($ospf_nbrs_db as $ospf_nbr_id => $ospf_nbr_db)
  {
    if (is_array($ospf_nbrs_poll[$ospf_nbr_db['ospf_nbr_id']]))
    {
      $ospf_nbr_poll = $ospf_nbrs_poll[$ospf_nbr_db['ospf_nbr_id']];

      //$ospf_nbr_poll['port_id'] = @dbFetchCell('SELECT A.`port_id` FROM `ipv4_addresses` AS A, `ospf_nbrs` AS I WHERE A.`ipv4_address` = ? AND I.`port_id` = A.`port_id` AND I.`device_id` = ?', array($ospf_nbr_poll['ospfNbrIpAddr'], $device['device_id']));
      $ids = get_entity_ids_ip_by_network('port', $ospf_nbr_poll['ospfNbrIpAddr'], generate_query_values($device['device_id'], 'device_id'));
      if (safe_count($ids)) {
        $ospf_nbr_poll['port_id'] = current($ids);
      } else {
        $ospf_nbr_poll['port_id'] = NULL;
      }

      if ($ospf_nbr_db['port_id'] != $ospf_nbr_poll['port_id']) {
        if ($ospf_nbr_poll['port_id']) {
          $ospf_nbr_update = array('port_id' => $ospf_nbr_poll['port_id']);
        } else {
          $ospf_nbr_update = array('port_id' => array('NULL'));
        }
      }

      foreach ($ospf_nbr_oids as $oid)
      { // Loop the OIDs
        print_debug($ospf_nbr_db[$oid].'|'.$ospf_nbr_poll[$oid]);
        if ($ospf_nbr_db[$oid] != $ospf_nbr_poll[$oid])
        { // If data has changed, build a query
          $ospf_nbr_update[$oid] = $ospf_nbr_poll[$oid];
          // log_event("$oid -> ".$this_nbr[$oid], $device, 'ospf', $nbr['port_id']); // FIXME
        }
      }
      if ($ospf_nbr_update)
      {
        dbUpdate($ospf_nbr_update, 'ospf_nbrs', '`device_id` = ? AND `ospf_nbr_id` = ?', array($device['device_id'], $ospf_nbr_id));
        echo('U');
        unset($ospf_nbr_update);
      } else {
        echo('.');
      }

      unset($ospf_nbr_poll);
      unset($ospf_nbr_db);
      $ospf_nbr_count++;
    } else {
      dbDelete('ospf_nbrs', '`device_id` = ? AND `ospf_nbr_id` = ?', array($device['device_id'], $ospf_nbr_db['ospf_nbr_id']));
      echo('-');
    }
  }
}

echo PHP_EOL;

if ($set_ospf)
{
  // Create device-wide statistics RRD
  rrdtool_update_ng($device, 'ospf-statistics', array(
    'instances'  => $ospf_instance_count,
    'areas'      => $ospf_area_count,
    'ports'      => $ospf_port_count,
    'neighbours' => $ospf_nbr_count,
  ));

  $graphs['ospf_neighbours'] = TRUE;
  $graphs['ospf_areas']      = TRUE;
  $graphs['ospf_ports']      = TRUE;

}

unset($ospf_ports_db);
unset($ospf_ports_poll);

// EOF
