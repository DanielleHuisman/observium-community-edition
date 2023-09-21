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

$version  = snmp_get_oid($device, 'ceAssetSoftwareRevision.1', 'CISCO-ENTITY-ASSET-MIB');
$hardware = snmp_get_oid($device, 'entPhysicalDescr.1', 'ENTITY-MIB');

// EOF
