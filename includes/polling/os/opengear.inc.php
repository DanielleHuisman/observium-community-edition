<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// OG-STATUSv2-MIB::ogFirmwareVersion = STRING: "3.12.1 (Fri Sep 26 16:16:16 EST 2014)"
$tmpver   = snmp_get($device, 'ogFirmwareVersion', '-OQv', 'OG-STATUSv2-MIB');
$verarray = explode(' ', $tmpver);
$version  = $verarray[0];

// EOF
