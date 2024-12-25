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

// Cisco TrustSec OIDs
// CISCO-TRUSTSEC-INTERFACE-MIB::ctsiIfControllerState.27 = INTEGER: open(6)

// Get TrustSec port status
$trustsec_statuses = snmpwalk_cache_oid($device, "ctsiIfControllerState", [], "CISCO-TRUSTSEC-INTERFACE-MIB");

// print_r($trustsec_statuses);
foreach ($trustsec_statuses as $ts_index => $ts) {
    if ($ts['ctsiIfControllerState'] === 'open' && isset($port_stats[$ts_index])) {
        // set port at encrypted
        $port_stats[$ts_index]['encrypted'] = '1';
    }
}

unset($trustsec_statuses, $ts, $ts_index);

// EOF
