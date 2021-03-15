<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// EdgeSwitch-SWITCHING-MIB::agentSwitchCpuProcessTotalUtilization.0 = STRING: "    5 Secs ( 99.9999%)   60 Secs ( 99.6358%)  300 Secs ( 99.2401%)"

$data = snmp_get_oid($device, 'agentSwitchCpuProcessTotalUtilization.0', $mib);

if (preg_match('/300 Secs \(\s*(?<proc>[\d\.]+)%\)/', $data, $matches))
{
  discover_processor($valid['processor'], $device, '.1.3.6.1.4.1.4413.1.1.1.1.4.9.0', '0', 'edgeswitch-switching-mib', 'Processor', 1, $matches['proc']);
}

unset($data, $matches);

// EOF
