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

//snAgGblDynMemUtil OBJECT-TYPE
//        STATUS  deprecated
//        DESCRIPTION
//                'The system dynamic memory utilization, in unit of percentage.
//                Deprecated: Refer to snAgSystemDRAMUtil.
//                For NI platforms, refer to snAgentBrdMemoryUtil100thPercent'

$percent = snmp_get($device, 'snAgSystemDRAMUtil.0', '-OvQ', $mib);
$total   = snmp_get($device, 'snAgSystemDRAMTotal.0', '-OvQ', $mib);

// This device some time have negative Total
// FOUNDRY-SN-AGENT-MIB::snAgSystemDRAMTotal.0 = -2147483648
if ($total < -1) { $total = abs($total); }

if (is_numeric($percent) && $total > 0)
{
  // Use new OIDs
  $hc = 1; // This is fake HC bit.
} else {
  // Use old deprecated OIDs
  $hc = 0;
  $percent = snmp_get($device, 'snAgGblDynMemUtil.0', '-OvQ', $mib);
  $total   = snmp_get($device, 'snAgGblDynMemTotal.0', '-OvQ', $mib);
  if ($total == -1) { $total = 100; }
}

if (is_numeric($percent) && $total > 0)
{
  $used = $total * $percent / 100;
  discover_mempool($valid['mempool'], $device, 0, 'FOUNDRY-SN-AGENT-MIB', 'Memory', 1, $total, $used, $hc);
}

unset ($total, $used, $percent, $hc);

// EOF
