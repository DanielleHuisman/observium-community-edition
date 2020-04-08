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

// DNOS-SWITCHING-MIB::agentSwitchCpuProcessTotalUtilization.0 = STRING: "    5 Secs (  6.510%)   60 Secs (  7.724%)  300 Secs (  6.3812%)"

$data = snmp_get($device, 'agentSwitchCpuProcessTotalUtilization.0', '-OQUvs', $mib);

if (preg_match('/300 Secs \(\s*(?<proc>[\d\.]+)%\)/', $data, $matches))
{
  discover_processor($valid['processor'], $device, '.1.3.6.1.4.1.674.10895.5000.2.6132.1.1.1.1.4.9.0', '0', 'dnos-switching-mib', 'Processor', 1, $matches['proc']);
}

unset($data);

// EOF
