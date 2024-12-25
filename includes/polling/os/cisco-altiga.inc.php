<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

$data = snmp_get_oid($device, 'alHardwareChassis.0', 'ALTIGA-HARDWARE-STATS-MIB');
if ($data) {
    $hardware = strtoupper($data);
} else {
    $serial = snmp_get_oid($device, 'entPhysicalSerialNum.1', 'ENTITY-MIB');
}

// EOF
