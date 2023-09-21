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

$version  = snmp_get($device, '.1.3.6.1.4.1.4413.1.1.1.1.1.13.0', '-OQv');
$hardware = snmp_get($device, '.1.3.6.1.4.1.4413.1.1.1.1.1.2.0', '-OQv');
$model    = snmp_get($device, '.1.3.6.1.4.1.4413.1.1.1.1.1.3.0', '-OQv');
$serial   = snmp_get($device, '.1.3.6.1.4.1.4413.1.1.1.1.1.4.0', '-OQv');
$features = snmp_get($device, '.1.3.6.1.4.1.4413.1.1.1.1.1.10.0', '-OQv');

// EOF

