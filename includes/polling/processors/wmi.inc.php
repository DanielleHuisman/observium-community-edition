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

/* Processor - Fixes "Unknown Processor Type" and "Intel" values
   TODO:
   - Need to add support for systems with multiple processor models
*/

$db_cpu_names = dbFetchRows('SELECT `processor_descr` FROM `processors` WHERE `device_id` = ?', [$device['device_id']]);

if (count(array_unique((array)$db_cpu_names)) === 1 &&
    ($db_cpu_names[0]['processor_descr'] === 'Unknown Processor Type' || $db_cpu_names[0]['processor_descr'] === 'Intel')) {
    $logical_proc_count = 0;
    $cpu_count          = 0;
    $cpu_names          = [];

    foreach ($wmi['processors'] as $cpu) {
        $cpu_count++;
        $logical_proc_count += $cpu['NumberOfLogicalProcessors'];
        array_push($cpu_names, $cpu['Name']);
    }

    if (count(array_unique($cpu_names)) === 1 && $cpu_names[0] === $wmi['processors'][0]['Name']) {
        dbUpdate(['processor_descr' => $wmi['processors'][0]['Name']], 'processors', '`device_id` = ? AND (`processor_descr` = ? OR `processor_descr` = ?)', [$device['device_id'], 'Unknown Processor Type', 'Intel']);
        echo(' Processor Name Updated:');
    }
}

echo(' ' . $wmi['processors'][0]['Name'] . "\n");

// EOF