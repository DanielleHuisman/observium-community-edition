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

$netscaler_devices = dbFetchRows("SELECT * FROM `devices` WHERE `os` = 'netscaler';");

if (count($netscaler_devices))
{
  echo 'Updating RRD ds names for Netscaler HTTP graphs: ';

  $ds_list = array('spdyv2TotStreams:COUNTER:600:U:100000000000', 'spdyv3TotStreams:COUNTER:600:U:100000000000', 'TotRequestsRate:GAUGE:600:U:100000000000', 'TotResponsesRate:GAUGE:600:U:100000000000');

  foreach ($netscaler_devices as $device)
  {
    $oldname = substr($newname, 0, 18);
    $status   = rrdtool_rename_ds($device, 'nsHttpStatsGroup.rrd', "TotResposesRate", "TotResponsesRate");
    $status   = rrdtool_rename_ds($device, 'nsHttpStatsGroup.rrd', "spdy2TotStreams", "spdyTotStreams");
    foreach ($ds_list as $ds)
    {
      $status_b = rrdtool_add_ds($device, 'nsHttpStatsGroup.rrd', $ds);
    }
    echo('.');
  }
}

unset($status, $netscaler_devices, $ds_rename);

// EOF

