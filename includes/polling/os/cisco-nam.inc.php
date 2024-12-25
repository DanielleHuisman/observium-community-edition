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

$version  = snmp_get_oid($device, 'entPhysicalSoftwareRev.1', 'ENTITY-MIB');
$hardware = snmp_get_oid($device, 'entPhysicalDescr.1', 'ENTITY-MIB');
$serial   = snmp_get_oid($device, 'entPhysicalSerialNum.1', 'ENTITY-MIB');

// EOF