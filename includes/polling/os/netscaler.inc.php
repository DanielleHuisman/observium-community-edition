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

#NS-ROOT-MIB::sysBuildVersion.0 = STRING: "NetScaler NS8.1: Build 69.4, Date: Jan 28 2010, 02:00:43  "

$version = snmp_get($device, 'sysBuildVersion.0', '-Osqv', 'SNMPv2-MIB:NS-ROOT-MIB');

list($version,$features) = explode(':', $version);
list(,$version) = explode(' ', $version);
list($features) = explode(',', trim($features));

// EOF
