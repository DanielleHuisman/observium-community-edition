<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

if (strlen($hardware) && strlen($version)) { return; } // Skip if already set by UBNT-UniFi-MIB

// IEEE802dot11-MIB::dot11manufacturerProductName.5 = STRING: UAP-LR
// IEEE802dot11-MIB::dot11manufacturerProductVersion.5 = STRING: BZ.ar7240.v3.1.9.2442.131217.1549

$data = snmpwalk_cache_oid($device, 'dot11manufacturerProductName', array(), 'IEEE802dot11-MIB');
if ($data)
{
  $data = snmpwalk_cache_oid($device, 'dot11manufacturerProductVersion', $data, 'IEEE802dot11-MIB');

  $data = current($data);
  // Coordinate hardware name with official naming
  $hardware = preg_replace('/^UAP/', 'UniFi AP', $data['dot11manufacturerProductName']);
  list(,$version) = preg_split('/\.v/', $data['dot11manufacturerProductVersion']);
  $version = implode('.', array_slice(explode('.', $version), 0, 4)); // Leave only first 4 numbers: 3.7.18.5368.161005.1224 -> 3.7.18.5368
}

// EOF
