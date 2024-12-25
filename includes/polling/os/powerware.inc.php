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

# XUPS-MIB::xupsIdentManufacturer.0 = STRING: "Powerware Corporation"
# XUPS-MIB::xupsIdentModel.0 = STRING: "T1500 XR  "
$hardware = snmp_get($device, 'xupsIdentManufacturer.0', '-OQv', 'XUPS-MIB', '');
$hardware .= ' ' . snmp_get($device, 'xupsIdentModel.0', '-OQv', 'XUPS-MIB', '');

// EOF
