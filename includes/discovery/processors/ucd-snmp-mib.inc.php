<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) Adam Armstrong
 *
 */

// FIXME. UCD-CPU already polled by ucd-mib poller
//$count_processors = dbFetchCell("SELECT COUNT(*) FROM `processors` WHERE `device_id` = ? AND `processor_type` != ?", array($device['device_id'], 'ucd-old'));

//if ($device['os_group'] == 'unix' && $count == 0)
//if ($count_processors == 0)
if (dbExist('processors', '`device_id` = ? AND `processor_type` NOT IN (?, ?)', [ $device['device_id'], 'ucd-cpu', 'ucd-raw' ])) {
    print_debug("Skip UCD CPU. Already exist better processor(s)");
    return;
}

if ($ss = snmp_get_multi_oid($device, 'ssCpuIdle.0 ssCpuSystem.0 ssCpuUser.0 ssCpuRawIdle.0 ssCpuRawSystem.0', [], $mib)) {
    $ss = $ss[0];

    // Note. ssCpuIdle is deprecated, needs to use ssCpuRawIdle, but it's a COUNTER
    if (is_numeric($ss['ssCpuIdle']) &&
        ($ss['ssCpuIdle'] + $ss['ssCpuSystem'] + $ss['ssCpuUser']) > 0) {
        //$percent = $system + $user + $idle;
        discover_processor($valid['processor'], $device, 0, 0, 'ucd-old', 'CPU', 1, $ss['ssCpuIdle'], NULL, NULL, 1);
    } elseif (is_numeric($ss['ssCpuRawIdle']) &&
              ($ss['ssCpuRawIdle'] + $ss['ssCpuRawSystem']) > 0) {

        print_debug_vars($ss);
        // Warning. This is counter, please do not pass raw value. Poller calculates value from previous
        // Still required for a device who does not support HOST-RESOURCES-MIB but ignores simple ssCpuIdle
        if (($processor_id = discover_processor($valid['processor'], $device, 0, 0, 'ucd-raw', 'CPU', 1, 100, NULL, NULL, 1)) &&
            empty(get_entity_attrib('processor', $processor_id, 'value-raw'))) {

            // store initial raw value for next poll
            set_entity_attrib('processor', $processor_id, 'value-raw', $ss['ssCpuRawIdle']);
        }
    }
}

// EOF
