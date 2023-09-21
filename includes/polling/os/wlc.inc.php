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

if (empty($hardware) && $poll_device['sysObjectID']) {
    // Try translate instead duplicate get sysObjectID
    $hardware = snmp_translate($poll_device['sysObjectID'], 'SNMPv2-MIB:CISCO-PRODUCTS-MIB:CISCO-ENTITY-VENDORTYPE-OID-MIB');
}
if (empty($hardware)) {
    // If translate false, try get sysObjectID again
    $hardware = snmp_get_oid($device, 'sysObjectID.0', 'SNMPv2-MIB:CISCO-PRODUCTS-MIB:CISCO-ENTITY-VENDORTYPE-OID-MIB');
}

unset($data);

// EOF
