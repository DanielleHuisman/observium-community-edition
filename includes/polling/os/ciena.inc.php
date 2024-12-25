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

$version  = str_replace('"', '', snmp_get($device, '.1.3.6.1.4.1.6141.2.60.10.1.1.3.1.2.1', '-Ovq'));
$serial   = str_replace('"', '', snmp_get($device, '.1.3.6.1.4.1.6141.2.60.11.1.1.1.52.0', '-Ovq'));
$hardware = str_replace('"', '', snmp_get($device, '.1.3.6.1.2.1.1.1.0', '-Ovq'));

// EOF
