<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     housekeeping
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$cutoff = age_to_unixtime($config['housekeeping']['deleted_ports']['age']);

if ($cutoff) {
    $where = "`deleted` = 1 AND `ifLastChange` < FROM_UNIXTIME($cutoff)";

    // Prevent delete billed ports
    if ($bill_ports = dbFetchColumn('SELECT `entity_id` FROM `bill_entities` WHERE `entity_type` = ?', ['port'])) {
        $where .= generate_query_values_and($bill_ports, 'port_id', '!=');
    }

    $ports = dbFetchRows("SELECT `port_id` FROM `ports` WHERE $where");
    $count = count($ports);
    if ($count) {
        if ($prompt) {
            $answer = print_prompt("$count ports marked as deleted before " . format_unixtime($cutoff) . " will be deleted");
        }
        if ($answer) {
            foreach ($ports as $entry) {
                delete_port($entry['port_id']);
            }
            print_debug("Deleted ports housekeeping: deleted $count entries");
            logfile("housekeeping.log", "Deleted ports: deleted $count entries older than " . format_unixtime($cutoff));
        }
    } elseif ($prompt) {
        print_message("No ports found marked as deleted before " . format_unixtime($cutoff));
    }
} else {
    print_message("Deleted ports housekeeping disabled in configuration.");
}

// EOF
