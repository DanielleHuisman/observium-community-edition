<?php
/* Observium Network Management and Monitoring System
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

global $graphs;

//$count = dbFetchCell('SELECT COUNT(*) FROM `counters` WHERE `device_id` = ? AND `counter_deleted` = ?;', array($device['device_id'], '0'));
//print_cli_data("Counters Count", $count);

if (dbExist('counters', '`device_id` = ? AND `counter_deleted` = ?', [$device['device_id'], '0']) > 0) {

    poll_cache_oids($device, 'counter', $oid_cache);

    global $table_rows;
    $table_rows = [];

    global $multi_update_db;
    $multi_update_db = [];

    poll_counter($device, $oid_cache);

    if (count($multi_update_db)) {
        print_debug("MultiUpdate counter DB.");
        // Multiupdate required all UNIQUE keys!
        dbUpdateMulti($multi_update_db, 'counters');
    }

    $headers = ['%WDescr%n', '%WClass%n', '%WMIB::Oid.Index%n', '%WValue%n', '%WRate%n', '%WStatus%n', '%WLast Changed%n', '%WOrigin%n'];
    print_cli_table($table_rows, $headers);

}

// EOF
