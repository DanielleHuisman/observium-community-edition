<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!$config['enable_sla']) {
    print_debug("SLA disabled globally.");
    // FIXME. Remove all discovered sla entries?
}

$valid['slas'] = [];

$include_dir = "includes/discovery/slas";
include($config['install_dir'] . "/includes/include-dir-mib.inc.php");

print_debug_vars($sla_table);

// Get existing SLAs
$sla_db = [];
foreach (dbFetchRows("SELECT * FROM `slas` WHERE `device_id` = ?", [ $device['device_id'] ]) as $entry) {
    $index     = get_sla_index($entry);
    $mib_lower = strtolower($entry['sla_mib']);

    $sla_db[$mib_lower][$index] = $entry;
}
print_debug_vars($sla_db);

foreach ($sla_table as $mib => $oids) {
    $mib_lower = strtolower($mib);
    foreach ($oids as $index => $entry) {

        if (!isset($sla_db[$mib_lower][$index])) {
            // Not exist, add
            $sla_id = dbInsert($entry, 'slas');
            $GLOBALS['module_stats'][$module]['added']++;
        } else {
            $sla_id = $sla_db[$mib_lower][$index]['sla_id'];

            $update_db = [];
            foreach ($entry as $key => $value) {
                if ($sla_db[$mib_lower][$index][$key] != $value) {
                    $update_db[$key] = $value;
                }
            }
            if (count($update_db)) {
                print_debug_vars($update_db);

                dbUpdate($update_db, 'slas', "`sla_id` = ?", [$sla_id]);
                if (isset($update_db['deleted'])) {
                    // This is re-added sla
                    $GLOBALS['module_stats'][$module]['added']++;
                } else {
                    $GLOBALS['module_stats'][$module]['updated']++;
                }
            } else {
                $GLOBALS['module_stats'][$module]['unchanged']++;
            }
        }
        $valid['slas'][$mib_lower][$sla_id] = $sla_id;
    }
}

// Mark all remaining SLAs as deleted
foreach ($sla_db as $mib_lower => $data) {
    foreach ($data as $entry) {
        if (isset($valid['slas'][$mib_lower][$entry['sla_id']]) || $entry['deleted'] == 1) {
            // SLA exist or already deleted
            continue;
        }

        if (!$entry['rttMonCtrlAdminStatus']) {
            dbDelete('slas', "`sla_id` = ?", [ $entry['sla_id'] ]);
        } else {
            // Mark as deleted, but keep in db
            dbUpdate([ 'deleted' => 1 ], 'slas', "`sla_id` = ?", [ $entry['sla_id'] ]);
        }
        $GLOBALS['module_stats'][$module]['deleted']++;
    }
}

$GLOBALS['module_stats'][$module]['status'] = safe_count($valid['slas']);
print_debug_vars($valid['slas']);

// Clean
unset($update_db, $sla_db, $sla_table, $sla_ids, $data, $entry, $oids, $mib);

// EOF
