<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$mib = 'NIMBLE-MIB';

//$cache_discovery[$mib]
$oids = snmpwalk_cache_oid($device, 'volName', array(), $mib);
if (count($oids))
{
  foreach (array('volSizeLow', 'volSizeHigh', 'volUsageLow', 'volUsageHigh', 'volOnline') as $oid)
  {
    $oids = snmpwalk_cache_oid($device, $oid, $oids, $mib);
  }
  if (OBS_DEBUG > 1) { print_vars($oids); }

  foreach ($oids as $index => $storage)
  {
    $hc     = 1;
    $fstype = 'volume';
    $descr  = $storage['volName'];
    $units  = 1048576; // Hardcode units. In MIB is written that bytes, but really Mbytes
    // FIXME, probably need additional field for storages like OperStatus up/down
    $ignore = in_array($storage['volOnline'], array('0', 'false')) ? 1 : 0;
    $deny   = FALSE;

    $size   = snmp_size64_high_low($storage['volSizeHigh'],  $storage['volSizeLow'])  * $units;
    $used   = snmp_size64_high_low($storage['volUsageHigh'], $storage['volUsageLow']) * $units;

    if (!$deny && is_numeric($index))
    {
      discover_storage($valid['storage'], $device, $index, $fstype, $mib, $descr, $units, $size, $used, array('storage_hc' => $hc, 'storage_ignore' => $ignore));
    }
  }

}

unset($oids, $deny, $fstype, $descr, $size, $used, $units, $hc);

// EOF
