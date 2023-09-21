<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$diskio_array = snmpwalk_cache_oid($device, 'diskIOEntry', [], 'UCD-DISKIO-MIB');

// Build array of entries from the database
foreach (dbFetchRows('SELECT * FROM `ucd_diskio` WHERE `device_id` = ?', [$device['device_id']]) as $entry) {
    $entries_db[$entry['diskio_index']] = $entry;
}

// Check if the $diskio_array
if (count($diskio_array) > 0) {
    foreach ($diskio_array as $index => $entry) {
        if ($entry['diskIONRead'] > 0 || $entry['diskIONWritten'] > 0) {
            print_debug('$index ' . $entry['diskIODevice']);
            if (isset($entries_db[$index]) && $entries_db[$index]['diskio_descr'] == $entry['diskIODevice']) {
                // Entries match. Nothing to do here!
                echo('.');
            } elseif (isset($entries_db[$index])) {
                // Index exists, but block device has changed!
                echo('U');
                dbUpdate(['diskio_descr' => $entry['diskIODevice']], 'ucd_diskio', '`diskio_id` = ?', [$entries_db[$index]['diskio_id']]);
            } else {
                // Index doesn't exist in the database. Add it.
                $inserted = dbInsert(['device_id' => $device['device_id'], 'diskio_index' => $index, 'diskio_descr' => $entry['diskIODevice']], 'ucd_diskio');
                echo('+');
            }
            // Remove from the DB array
            unset($entries_db[$index]);
        } // end validity check
    } // end array foreach
} // End array if

// Remove diskio entries which weren't redetected here

foreach ($entries_db as $entry) {
    echo('-');
    dbDelete('ucd_diskio', '`diskio_id` = ?', [$entry['diskio_id']]);
}

echo(PHP_EOL);

// EOF
