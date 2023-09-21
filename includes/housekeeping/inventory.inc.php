<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     housekeeping
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Minimum allowed age for deleted inventory entries times is 24h
$cutoff = age_to_unixtime($config['housekeeping']['inventory']['age'], age_to_seconds('24h'));

if ($cutoff) {
    $where = "`deleted` < FROM_UNIXTIME($cutoff)";
    $count = dbFetchCell("SELECT COUNT(*) FROM `entPhysical` WHERE $where");
    if ($count) {
        if ($prompt) {
            $answer = print_prompt("$count deleted inventory entries older than " . format_unixtime($cutoff) . " will be deleted");
        }

        if ($answer) {
            $rows = dbDelete('entPhysical', "$where");
            if ($rows === FALSE) {
                // Use LIMIT with big tables
                print_debug("entPhysical table is too big, using LIMIT for delete entries");
                $rows = 0;
                $i    = 1000;
                while ($i && $rows < $count) {
                    $iter = dbDelete('entPhysical', $where . ' LIMIT 1000000');
                    if ($iter === FALSE) {
                        break;
                    }
                    $rows += $iter;
                    $i--;
                }
            }
            print_debug("Inventory housekeeping: deleted $rows entries");
            logfile("housekeeping.log", "Inventory: deleted $rows entries older than " . format_unixtime($cutoff));
        }
    } elseif ($prompt) {
        print_message("No inventory entries found older than " . format_unixtime($cutoff));
    }
} else {
    print_message("Inventory housekeeping disabled in configuration or incorrectly configured to less than 24h.");
}

// EOF
