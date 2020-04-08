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

$ident = snmp_get($device, 'mdu12Ident.0', '-Oqv', 'TSL-MIB');

list($hardware, $version) = explode(' ', $ident);

// EOF
