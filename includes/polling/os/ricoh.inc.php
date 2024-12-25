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

// SNMPv2-SMI::enterprises.367.3.2.1.1.1.1.0 = STRING: "Aficio MP 3350"
$hardware = snmp_get($device, '1.3.6.1.4.1.367.3.2.1.1.1.1.0', '-OQv');

// SNMPv2-SMI::enterprises.367.3.2.1.1.1.2.0 = STRING: "1.15"
$version = snmp_get($device, '1.3.6.1.4.1.367.3.2.1.1.1.2.0', '-OQv');

// SNMPv2-SMI::enterprises.367.3.2.1.2.1.4.0 = STRING: "M6394300657"
$serial = snmp_get($device, '1.3.6.1.4.1.367.3.2.1.2.1.4.0', '-OQv');

// EOF
