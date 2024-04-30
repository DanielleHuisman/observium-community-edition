<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// MIBs for this os/hardware inaccessible

if ($hw = snmp_get_oid($device, '.1.3.6.1.4.1.49622.4.0')) {
    // Chassis Type:
    // Chassis Part Number:
    // Chassis Serial:
    // Chassis Extra:
    // Board Mfg Date: Tue Apr 19 03:25:00 2022
    // Board Mfg: ASRockRack
    // Board Product: X470D4U
    // Board Serial: 220923140000124
    // Board Part Number:
    // Board Extra:
    // Product Manufacturer:
    // Product Name:
    // Product Part Number:
    // Product Version:
    // Product Serial:
    // Product Asset Tag:
    // Product Extra:
    foreach (explode("\n", $hw) as $line) {
        if (str_starts_with('Board Product', $line)) {
            $hardware = explode(': ', $line, 2)[1];
        } elseif (str_starts_with('Board Serial', $line)) {
            $serial   = explode(': ', $line, 2)[1];
        }
    }
}

// EOF