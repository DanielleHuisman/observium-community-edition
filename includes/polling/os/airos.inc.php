<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

// FIXME. airos != unifi. required device for tests
$data = snmpwalk_cache_oid($device, "dot11manufacturerProductName", array(), "IEEE802dot11-MIB");
if ($data)
{
  $data = snmpwalk_cache_oid($device, "dot11manufacturerProductVersion", $data, "IEEE802dot11-MIB");

  $data = current($data);
  $hardware = $data['dot11manufacturerProductName'];
  // 5.5.10-u2.28005.150723.1358
  // 8.7.0.42152.200203.1256
  list(,$version) = preg_split('/\.v/', $data['dot11manufacturerProductVersion']);
  $version = implode('.', array_slice(explode('.', $version), 0, 4)); // Leave only first 4 numbers: 8.7.0.42152.200203.1256 -> 8.7.0.42152
}

// EOF
