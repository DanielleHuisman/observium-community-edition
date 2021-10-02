<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

if (!safe_empty($hardware) && !safe_empty($version)) { return; } // Skip if already set by UBNT-UniFi-MIB

// IEEE802dot11-MIB::dot11manufacturerProductName.5 = STRING: UAP-LR
// IEEE802dot11-MIB::dot11manufacturerProductVersion.5 = STRING: BZ.ar7240.v3.1.9.2442.131217.1549

// IEEE802dot11-MIB::dot11manufacturerProductName.4 = STRING: U6-Lite
if ($hw = snmp_getnext_oid($device, 'dot11manufacturerProductName', 'IEEE802dot11-MIB')) {
  // Coordinate hardware name with official naming
  $hardware = preg_replace('/^UAP/', 'UniFi AP', $hw);
  $hardware = preg_replace('/^U6/', 'UniFi6 AP', $hardware);

  if ($ver = snmp_getnext_oid($device, 'dot11manufacturerProductVersion', 'IEEE802dot11-MIB')) {
    list(,$version) = explode(".v", $ver, 2);
    $version = implode('.', array_slice(explode('.', $version), 0, 4)); // Leave only first 4 numbers: 3.7.18.5368.161005.1224 -> 3.7.18.5368
  }
}

// EOF
