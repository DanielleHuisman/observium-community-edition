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

$hardware       = snmp_get($device, '1.3.6.1.4.1.3417.2.11.1.2.0', '-OQv');
$version_string = snmp_get($device, '1.3.6.1.4.1.3417.2.11.1.3.0', '-OQv');

[, $version] = explode(': ', $version_string);
[$version] = explode(',', $version);
$version = str_replace('SGOS ', '', $version);

$serial = snmp_get($device, '1.3.6.1.4.1.3417.2.11.1.4.0', '-OQv');

// EOF
