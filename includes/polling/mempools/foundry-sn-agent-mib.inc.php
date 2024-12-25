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

if ($mempool['mempool_hc']) {
    $mempool['perc']  = snmp_get($device, "snAgSystemDRAMUtil.0", "-OvQ", 'FOUNDRY-SN-AGENT-MIB');
    $mempool['total'] = snmp_get($device, "snAgSystemDRAMTotal.0", "-OvQ", 'FOUNDRY-SN-AGENT-MIB');
    if ($mempool['total'] < -1) {
        $mempool['total'] = abs($mempool['total']);
    }
} else {
    $mempool['perc']  = snmp_get($device, "snAgGblDynMemUtil.0", "-OvQ", 'FOUNDRY-SN-AGENT-MIB');
    $mempool['total'] = snmp_get($device, "snAgGblDynMemTotal.0", "-OvQ", 'FOUNDRY-SN-AGENT-MIB');
    if ($mempool['total'] == -1) {
        $mempool['total'] = 100;
    }
}

// EOF
