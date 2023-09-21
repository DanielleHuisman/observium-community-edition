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

$f5_devs = dbFetchRows("SELECT * FROM `devices` WHERE `os` = 'f5';");

if (count($f5_devs))
{
  echo 'Updating RRD ds names for F5 client SSL graphs: ';

  $ds_list = array('TotNativeConns:COUNTER:600:U:100000000000', 'TotCompatConns:COUNTER:600:U:100000000000');

  foreach ($f5_devs as $device)
  {
    foreach ($ds_list as $ds)
    {
      $status_b = rrdtool_add_ds($device, 'clientssl.rrd', $ds);
    }
    echo('.');
  }
}

unset($status, $netscaler_devices, $ds_rename);

// EOF

