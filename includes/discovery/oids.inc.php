<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$table_rows = [];

$sql = "SELECT *";
$sql .= " FROM  `oids_entries`";
$sql .= " LEFT JOIN `oids` USING(`oid_id`)";
$sql .= " WHERE `device_id` = ?";

//print_vars($sql);

$entries_db = dbFetchRows($sql, [$device['device_id']]);

foreach ($entries_db as $entry_db) {
    $entries[$entry_db['oid_id']] = $entry_db['oid_entry_id'];
}

$oids_db = dbFetchRows("SELECT * FROM `oids` WHERE `oid_autodiscover` = '1'");

// FIXME - removal and blacklisting

foreach ($oids_db as $oid) {
    $value = snmp_get($device, $oid['oid'], "-OUQtnv");
    if (is_numeric($value) && $value != '4294967295' && $value != '2147483647' && $value != '-2147483647') // Don't discover stuff which is returning min/max 32 bit values
    {
        if (!isset($entries[$oid['oid_id']])) {
            // Auto-add this OID.
            if ($oid_entry_id = dbInsert(['oid_id' => $oid['oid_id'], 'device_id' => $device['device_id']], 'oids_entries')) {
                print_debug("SUCCESS: Added OID entry (id: $oid_entry_id)");
            } else {
                print_warning("ERROR: Unable to add OID entry for " . $oid['oid_name']);
            }
        }
    } else {
        if (isset($entries[$oid['oid_id']])) {
            // Mark this OID as deleted from the host.
            dbUpdate(['deleted' => '1'], 'oids_entries', '`oid_entry_id` = ?', [$oid['oid_entry_id']]);
        }

    }
}
