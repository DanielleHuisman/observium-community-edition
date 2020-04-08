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

set_dev_attrib($device, 'poll_storage', 0);

echo(" Storage WMI: ");
foreach ($wmi['disk']['logical'] as $disk)
{
  echo(".");

  $storage_name = $disk['DeviceID'] . "\\\\ Label:" . $disk['VolumeName'] . "  Serial Number " . ltrim(strtolower($disk['VolumeSerialNumber']), '0');
  $storage_id = dbFetchCell("SELECT `storage_id` FROM `storage` WHERE `storage_descr`= ?", array($storage_name));
  $used = $disk['Size'] - $disk['FreeSpace'];
  $percent = round($used / $disk['Size'] * 100);

  rrdtool_update_ng($device, 'storage', array('used' => $used, 'free' => $disk['FreeSpace']), "host-resources-mib-" . $storage_name);
  dbUpdate(array('storage_polled' => time(), 'storage_used' => $used, 'storage_free' => $disk['FreeSpace'], 'storage_size' => $disk['Size'],
    'storage_perc' => $percent), 'storage', '`storage_id` = ?', array($storage_id));
}

echo(PHP_EOL);

// EOF
