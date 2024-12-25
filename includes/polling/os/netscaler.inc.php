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

#NS-ROOT-MIB::sysBuildVersion.0 = STRING: "NetScaler NS8.1: Build 69.4, Date: Jan 28 2010, 02:00:43  "

$version = snmp_get($device, 'sysBuildVersion.0', '-Osqv', 'SNMPv2-MIB:NS-ROOT-MIB');

[$version, $features] = explode(':', $version);
[, $version] = explode(' ', $version);
[$features] = explode(',', trim($features));

// EOF
