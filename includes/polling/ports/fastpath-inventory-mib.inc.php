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

// Get stack ports
$stackports = snmpwalk_cache_oid($device, 'agentInventoryStackPortTable', [], 'FASTPATH-INVENTORY-MIB', mib_dirs(['dell', 'broadcom']));

//            [agentInventoryStackPortUnit] => 1
//            [agentInventoryStackPortTag] => 0/17
//            [agentInventoryStackPortConfiguredStackMode] => stack
//            [agentInventoryStackPortRunningStackMode] => stack
//            [agentInventoryStackPortLinkStatus] => up
//            [agentInventoryStackPortLinkSpeed] => 10
//            [agentInventoryStackPortDataRate] => 11
//            [agentInventoryStackPortErrorRate] => 0
//            [agentInventoryStackPortTotalErrors] => 94

//print_r($stackports);
foreach ($stackports as $index => $port) {
    if ($port['agentInventoryStackPortRunningStackMode'] !== "stack") {
        continue;
    }
    $port_stats[$index]['ifName']        = 'Te' . $port['agentInventoryStackPortUnit'] . '/' . $port['agentInventoryStackPortTag'];
    $port_stats[$index]['ifDescr']       = 'Stack Port';
    $port_stats[$index]['ifType']        = 'propVirtual';
    $port_stats[$index]['ifSpeed']       = intval($port['agentInventoryStackPortLinkSpeed']) * 1000000000;
    $port_stats[$index]['ifOperStatus']  = $port['agentInventoryStackPortLinkStatus'];
    $port_stats[$index]['ifAdminStatus'] = 'up';
    $port_stats[$index]['ifInErrors']    = $port['agentInventoryStackPortTotalErrors'];
}

unset($stackports, $port, $index);

// EOF
