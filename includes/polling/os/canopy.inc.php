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

$version = snmp_get($device, '1.3.6.1.4.1.161.19.3.3.1.1.0', '-OQv');
$version = preg_replace('/^CANOPY /', '', $version);

$platform = snmp_get($device, 'platformVer.0', '-OQv', 'WHISP-BOX-MIBV2-MIB');
$hardware = snmp_get($device, 'boxDeviceType.0', '-OQv', 'WHISP-BOX-MIBV2-MIB');
$hardware = "$hardware (P$platform)";

// EOF
