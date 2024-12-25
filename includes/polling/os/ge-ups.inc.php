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

$hardware = snmp_get($device, 'upsIdentManufacturer.0', '-OQv', 'GEPARALLELUPS-MIB');
$hardware .= ' ' . snmp_get($device, 'upsIdentModel.0', '-OQv', 'GEPARALLELUPS-MIB');

// EOF
