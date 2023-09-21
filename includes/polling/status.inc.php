<?php

/* Observium Network Management and Monitoring System
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

global $graphs;

$count = dbFetchCell('SELECT COUNT(*) FROM `status` WHERE `device_id` = ? AND `status_deleted` = ?;', [$device['device_id'], '0']);

print_cli_data("Status Count", $count);

if ($count > 0) {

    poll_cache_oids($device, 'status', $oid_cache);

    global $table_rows;
    $table_rows = [];

    global $multi_update_db;
    $multi_update_db = [];

    poll_status($device, $oid_cache);

    if (count($multi_update_db)) {
        print_debug("MultiUpdate status DB.");
        // Multiupdate required all UNIQUE keys!
        dbUpdateMulti($multi_update_db, 'status');
    }

    $headers = ['%WDescr%n', '%WType%n', '%WIndex%n', '%WOrigin%n', '%WValue%n', '%WStatus%n', '%WLast Changed%n'];
    print_cli_table($table_rows, $headers);

}

// EOF
