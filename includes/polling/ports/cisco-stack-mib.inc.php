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

// CISCO-STACK-MIB

$port_stack = snmpwalk_cache_oid($device, "portIfIndex", [], "CISCO-STACK-MIB");
if (!$GLOBALS['snmp_status']) {
    return;
} // Break walk if not exist data from CISCO-STACK-MIB

$port_stack = snmpwalk_cache_oid($device, "portName", $port_stack, "CISCO-STACK-MIB");
$port_stack = snmpwalk_cache_oid($device, "portDuplex", $port_stack, "CISCO-STACK-MIB");

foreach ($port_stack as $key => $data) {
    if (!isset($port_stats[$data['portIfIndex']])) {
        continue;
    } // Unknown ifIndex

    if (empty($port_stats[$data['portIfIndex']]['ifAlias'])) {
        $port_stats[$data['portIfIndex']]['ifAlias'] = $data['portName'];
    }
    if (empty($port_stats[$data['portIfIndex']]['ifDuplex'])) {
        // Use same duplex values as in EtherLike-MIB::dot3StatsDuplexStatus
        switch ($data['portDuplex']) {
            case 'half':
                $port_stats[$data['portIfIndex']]['ifDuplex'] = 'halfDuplex';
                break;
            case 'full':
                $port_stats[$data['portIfIndex']]['ifDuplex'] = 'fullDuplex';
                break;
            default:
                $port_stats[$data['portIfIndex']]['ifDuplex'] = 'unknown';
        }
    }
}

unset($port_stack);

// EOF
