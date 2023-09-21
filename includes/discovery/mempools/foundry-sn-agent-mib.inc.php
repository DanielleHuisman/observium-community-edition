<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

//snAgGblDynMemUtil OBJECT-TYPE
//        STATUS  deprecated
//        DESCRIPTION
//                'The system dynamic memory utilization, in unit of percentage.
//                Deprecated: Refer to snAgSystemDRAMUtil.
//                For NI platforms, refer to snAgentBrdMemoryUtil100thPercent'

$percent = snmp_get_oid($device, 'snAgSystemDRAMUtil.0', $mib);
$total   = snmp_get_oid($device, 'snAgSystemDRAMTotal.0', $mib);

// This device some time have negative Total
// FOUNDRY-SN-AGENT-MIB::snAgSystemDRAMTotal.0 = -2147483648
if ($total < -1 && is_numeric($total)) {
    $total = abs($total);
}

if (is_numeric($percent) && $total > 0) {
    // Use new OIDs
    $hc = 1; // This is fake HC bit.
} else {
    // Use old deprecated OIDs
    $hc      = 0;
    $percent = snmp_get_oid($device, 'snAgGblDynMemUtil.0', $mib);
    $total   = snmp_get_oid($device, 'snAgGblDynMemTotal.0', $mib);
    if ($total == -1 && is_numeric($total)) {
        $total = 100;
    }
}

if (is_numeric($percent) && is_numeric($total) && $total > 0) {
    $used = $total * $percent / 100;
    discover_mempool($valid['mempool'], $device, 0, 'FOUNDRY-SN-AGENT-MIB', 'Memory', 1, $total, $used, $hc);
}

unset ($total, $used, $percent, $hc);

// EOF
