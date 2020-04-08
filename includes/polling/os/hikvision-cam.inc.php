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

// HIK-DEVICE-MIB::softwVersion.0 = STRING: "V5.2.0 build 140721"
list($version) = explode(' ', snmp_get($device, 'softwVersion.0', '-Osqv', 'HIK-DEVICE-MIB'));
$version = str_replace('V', '', $version);

// EOF
