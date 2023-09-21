<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage db
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Drop old index if exist

if ($indexes = dbShowIndexes('devices', 'hostname')) {
  dbQuery('ALTER TABLE `devices` DROP INDEX `hostname`;');
  echo('-');
}
// unique
// Remove duplicates if exist in table oids_assoc
$entries = [];
foreach (dbFetchRows("SELECT * FROM `devices` ORDER BY `device_id` ASC") as $entry) {
  if (isset($entries[$entry['hostname']])) {
    echo('.');
    // Check if device disabled
    $old = $entries[$entry['hostname']];
    if ($old['disabled']) {
      // delete previous (deleted) device
      delete_device($old['device_id']);
    } else {
      // delete new (duplicate) device
      delete_device($entry['device_id']);
      continue;
    }
  }
  $entries[$entry['hostname']] = $entry;
}

// Add table index again
dbQuery('ALTER TABLE `devices` ADD UNIQUE `hostname` (`hostname`) USING BTREE;');
echo('+');

unset($indexes, $entries, $entry);

// EOF
