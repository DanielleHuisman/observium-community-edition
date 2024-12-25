<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) Adam Armstrong
 *
 */

// Simple poller for UCD old style CPU.

if (isset($oid_cache[$processor['processor_oid']])) {
    $idle = $oid_cache[$processor['processor_oid']];
} else {
    $idle = snmp_get_oid($device, 'ssCpuIdle.0', 'UCD-SNMP-MIB');
}

if (isset($processor['processor_returns_idle'])) {
    // Just compat before the processor isn't updated
    $processor['processor_returns_idle'] = 1;

}

$proc = $idle;

// EOF
