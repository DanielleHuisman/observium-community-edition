<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

//UPS-MIB::upsIdentManufacturer.0 = STRING: RPS SpA
//UPS-MIB::upsIdentModel.0 = STRING:
//UPS-MIB::upsIdentUPSSoftwareVersion.0 = STRING: 10.40
//UPS-MIB::upsIdentAgentSoftwareVersion.0 = STRING: NetMan 100 plus
//UPS-MIB::upsIdentName.0 = STRING: UPS1-60K

$hardware = snmp_get_oid($device, 'upsIdentAgentSoftwareVersion.0', 'UPS-MIB');
$version  = snmp_get_oid($device, 'upsIdentUPSSoftwareVersion.0',   'UPS-MIB');

// Uses UPS-MIB, not correct Oids

// EOF
