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

// OG-STATUSv2-MIB::ogFirmwareVersion = STRING: "3.12.1 (Fri Sep 26 16:16:16 EST 2014)"
$tmpver   = snmp_get($device, 'ogFirmwareVersion', '-OQv', 'OG-STATUSv2-MIB');
$verarray = explode(' ', $tmpver);
$version  = $verarray[0];

// EOF
