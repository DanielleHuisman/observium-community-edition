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

if ($data = snmp_get_oid($device, 'entPhysicalSoftwareRev.1', 'ENTITY-MIB')) {
    // OP-MEN99216BC (standalone) v7.10.2297
    $data = explode(' ', $data);

    $hardware = array_shift($data);
    $version  = array_pop($data);
    $version  = ltrim($version, 'v');

    if (!$vendor && str_starts($hardware, ['OP-', 'OL-'])) {
        $vendor = 'Optilink';
    }
}

// EOF
