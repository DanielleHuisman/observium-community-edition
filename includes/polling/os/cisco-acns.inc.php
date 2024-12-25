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

$version  = snmp_get_oid($device, 'ceAssetSoftwareRevision.1', 'CISCO-ENTITY-ASSET-MIB');
$hardware = snmp_get_oid($device, 'entPhysicalDescr.1', 'ENTITY-MIB');

// EOF
