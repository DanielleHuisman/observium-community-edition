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

echo("Deleting outdated Netscaler SSL RRDs: ");

foreach (dbFetchRows("SELECT * FROM `devices` WHERE `os` = 'netscaler'") as $device)
{
  $filename = get_rrd_path($device, "netscaler-SslStats.rrd");
  unlink($filename);
  echo('.');
}

// EOF
