<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) Adam Armstrong
 *
 */

echo("Deleting outdated Netscaler SSL RRDs: ");

foreach (dbFetchRows("SELECT * FROM `devices` WHERE `os` = 'netscaler'") as $device)
{
  $filename = get_rrd_path($device, "netscaler-SslStats.rrd");
  unlink($filename);
  echo('.');
}

// EOF
