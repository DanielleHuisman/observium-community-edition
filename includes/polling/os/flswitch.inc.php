<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// SNMPv2-MIB::sysObjectID.0 = OID: FL-MGD-INFRASTRUCT-MIB::flComponentsIndex.331
// .1.3.6.1.4.1.4346.11.1.2.1.1.1.331

// FL-MGD-INFRASTRUCT-MIB::flComponentsIndex.331 = INTEGER: 331
// FL-MGD-INFRASTRUCT-MIB::flComponentsName.331 = STRING: FL SWITCH 2008
// FL-MGD-INFRASTRUCT-MIB::flComponentsDescr.331 = STRING: Industrial Ethernet Switch with 8 Fast-Ethernet copper ports.
// FL-MGD-INFRASTRUCT-MIB::flComponentsURL.331 = STRING: http://www.FactoryLine.de
// FL-MGD-INFRASTRUCT-MIB::flComponentsOrderNumber.331 = STRING: 27 02 324

if (match_oid_num($device['sysObjectID'], '.1.3.6.1.4.1.4346.11.1.2.1.1.1')) {
    $hw_array = explode('.', $device['sysObjectID']);
    $hw_index = end($hw_array);
    $hardware = snmp_get_oid($device, 'flComponentsName.' . $hw_index, 'FL-MGD-INFRASTRUCT-MIB');
}

// EOF
