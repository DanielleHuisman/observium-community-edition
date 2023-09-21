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

// Simple poller for UCD old style CPU. will always poll the same index.

//$system = snmp_get($device, 'ssCpuSystem.0', '-OvQ', 'UCD-SNMP-MIB');
//$user = snmp_get($device, 'ssCpuUser.0', '-OvQ', 'UCD-SNMP-MIB');

// FIXME. move to definitions
if (isset($oid_cache[$processor['processor_oid']])) {
    $idle = $oid_cache[$processor['processor_oid']];
} else {
    $idle = snmp_get_oid($device, 'ssCpuIdle.0', 'UCD-SNMP-MIB');
}

if ($processor['processor_returns_idle'] == 1) {
    // Just compat before processor not updated
    $proc = $idle;
} else {
    $proc = 100 - $idle;
}

// EOF
