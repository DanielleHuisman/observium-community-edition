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

$ident = snmp_get($device, 'mdu12Ident.0', '-Oqv', 'TSL-MIB');

[$hardware, $version] = explode(' ', $ident);

// EOF
