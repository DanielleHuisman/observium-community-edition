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

if (!$version) {
    //FCMGMT-MIB::connUnitRevsRevId.'................'.1 = STRING: "73.6"
    //FCMGMT-MIB::connUnitRevsRevId.'................'.2 = STRING: "v6.4.2b4"
    $version = snmp_get($device, '.1.3.6.1.3.94.1.7.1.3.16.0.0.5.51.61.220.34.0.0.0.0.0.0.0.0.2', '-Ovq');
}
$version = ltrim($version, 'v');

// EOF
