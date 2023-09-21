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

// IEEE802dot11-MIB::dot11MACAddress[5] = STRING: 74:83:c2:e8:2a:60
// IEEE802dot11-MIB::dot11manufacturerName[5] = STRING: Ubiquiti Networks, Inc.
// IEEE802dot11-MIB::dot11manufacturerProductName[5] = STRING: PowerBeam M5
// IEEE802dot11-MIB::dot11manufacturerProductVersion[5] = STRING: XW.ar934x.v6.1.9.32918.190108.1737

if ($hw = snmp_getnext_oid($device, 'dot11manufacturerProductName', 'IEEE802dot11-MIB')) {
    $hardware = $hw;

    if ($ver = snmp_getnext_oid($device, 'dot11manufacturerProductVersion', 'IEEE802dot11-MIB')) {
        [, $version] = explode(".v", $ver, 2);
        $version = implode('.', array_slice(explode('.', $version), 0, 4)); // Leave only first 4 numbers: 8.7.0.42152.200203.1256 -> 8.7.0.42152
    }

    if (safe_empty($serial) && ($mac = snmp_getnext_oid($device, 'dot11MACAddress', 'IEEE802dot11-MIB')) &&
        is_valid_param($mac, 'serial')) {
        // Not real hardware serial, but use mac as serial
        $serial = str_replace(':', '', $mac);
    }
}

// EOF
