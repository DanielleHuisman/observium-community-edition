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

$data = snmp_get_oid($device, 'alHardwareChassis.0', 'ALTIGA-HARDWARE-STATS-MIB');
if ($data) {
    $hardware = strtoupper($data);
} else {
    $serial = snmp_get_oid($device, 'entPhysicalSerialNum.1', 'ENTITY-MIB');
}

// EOF
