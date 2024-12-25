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

// Derp poller for UCD-SNMP-MIB for counter. It's IDLE

if (isset($oid_cache[$processor['processor_oid']])) {
    $proc = $oid_cache[$processor['processor_oid']];
} elseif ($processor['processor_polled'] &&
          $idle_last = get_entity_attrib('processor', $processor['processor_id'], 'value-raw')) { // get last counter value

    $idle = snmp_get_oid($device, 'ssCpuRawIdle.0', 'UCD-SNMP-MIB');
    $proc_polled = snmp_endtime();

    // Calculate idle for counter from previous value
    $proc = float_div($idle - $idle_last, $proc_polled - $processor['processor_polled']);

    // store raw value for next poll
    set_entity_attrib('processor', $processor['processor_id'], 'value-raw', $idle);
} else {
    // In case of error
    $proc = 100;
}

// EOF
