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

# DeltaUPS-MIB::dupsIdentManufacturer.0 = STRING: "Socomec"
# DeltaUPS-MIB::dupsIdentModel.0 = STRING: "NETYS RT 1/1 UPS"
$hardware = snmp_get($device, 'dupsIdentManufacturer.0', '-OQv', 'DeltaUPS-MIB');
$hardware .= ' ' . snmp_get($device, 'dupsIdentModel.0', '-OQv', 'DeltaUPS-MIB');

# DeltaUPS-MIB::dupsIdentAgentSoftwareVersion.0 = STRING: "2.0h "

// EOF
