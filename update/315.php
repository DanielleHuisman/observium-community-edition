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

// Drop old index if exist
$indexes = dbShowIndexes('oids_assoc', 'oids_cache');
if (!empty($indexes))
{
  dbQuery('ALTER TABLE `oids_assoc` DROP INDEX `oids_cache`;');
  echo('.');
}

// Remove duplicates if exist in table oids_assoc
$entries = array();
foreach (dbFetchRows("SELECT * FROM `oids_assoc`;") as $entry)
{
  if (isset($entries[$entry['device_id']][$entry['oid_id']]))
  {
    dbDelete('oids_assoc', '`oid_assoc_id` = ?;', array($entry['oid_assoc_id']));
    echo('.');
    continue;
  }
  $entries[$entry['device_id']][$entry['oid_id']] = 1;
}

// Add table index again
dbQuery('ALTER TABLE `oids_assoc` ADD UNIQUE `oids_cache` (`oid_id`, `device_id`);');
echo('.');

unset($indexes, $entries, $entry);

// EOF
