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

// This is is a fix for incorrect rrd file rename after r7839

foreach (dbFetchRows("SELECT * FROM `devices` WHERE `os` = ? OR `os` = ?;", array('nos', 'fabos')) as $entry)
{
  $status = rename_rrd($entry, 'processor-swCpuOrMemoryUsage-1.rrd', 'processor-swCpuUsage-0.rrd');
  if ($status)
  {
    echo('.');
    force_discovery($entry);
  }
}

// EOF
