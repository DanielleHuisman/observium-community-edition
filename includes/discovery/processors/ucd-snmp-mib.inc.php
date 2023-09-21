<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// FIXME. UCD-CPU already polled by ucd-mib poller
//$count_processors = dbFetchCell("SELECT COUNT(*) FROM `processors` WHERE `device_id` = ? AND `processor_type` != ?", array($device['device_id'], 'ucd-old'));

//if ($device['os_group'] == 'unix' && $count == 0)
//if ($count_processors == 0)
if (!dbExist('processors', '`device_id` = ? AND `processor_type` != ?', [$device['device_id'], 'ucd-old'])) {
    //$system = snmp_get($device, 'ssCpuSystem.0', '-OvQ', $mib);
    //$user   = snmp_get($device, 'ssCpuUser.0'  , '-OvQ', $mib);
    //$idle   = snmp_get($device, 'ssCpuIdle.0'  , '-OvQ', $mib);
    $idle = snmp_get_oid($device, 'ssCpuIdle.0', $mib);

    if (is_numeric($idle)) {
        //$percent = $system + $user + $idle;
        discover_processor($valid['processor'], $device, 0, 0, 'ucd-old', 'CPU', 1, $idle, NULL, NULL, 1);
    }
}

// EOF
