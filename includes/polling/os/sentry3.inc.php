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

// Sentry3-MIB::systemVersion        "Sentry Switched CDU Version 6.0g"

$version = snmp_get($device, 'systemVersion.0', '-Ovq', 'Sentry3-MIB');
list(, $version) = explode('Version ', $version);

// EOF
