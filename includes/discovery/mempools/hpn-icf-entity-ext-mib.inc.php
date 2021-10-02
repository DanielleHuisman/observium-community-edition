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

// HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtMemUsage.30 = INTEGER: 58
// HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtMemUsage.36 = INTEGER: 59
// HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtMemUsage.42 = INTEGER: 58
// HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtMemUsage.48 = INTEGER: 58

$oids = array('hpnicfEntityExtMemUsage', 'hpnicfEntityExtMemSize');
$mempool_array = array();
foreach ($oids as $oid)
{
  $mempool_array = snmpwalk_cache_oid($device, $oid, $mempool_array, 'ENTITY-MIB:HPN-ICF-ENTITY-EXT-MIB');
  if (!$GLOBALS['snmp_status']) { break; }
}

if (is_array($mempool_array))
{
  $mempool_array = snmpwalk_cache_oid($device, 'entPhysicalName', $mempool_array, 'ENTITY-MIB:HPN-ICF-ENTITY-EXT-MIB');

  foreach ($mempool_array as $index => $entry)
  {
    $entry['hpnicfEntityExtMemSize'] = snmp_dewrap32bit($entry['hpnicfEntityExtMemSize']);
    if (is_numeric($entry['hpnicfEntityExtMemUsage']) && $entry['hpnicfEntityExtMemSize'] > 0)
    {
      $descr   = $entry['entPhysicalName'];
      $percent = $entry['hpnicfEntityExtMemUsage'];
      $total   = $entry['hpnicfEntityExtMemSize'];
      $used    = $total * $percent / 100;

      discover_mempool($valid['mempool'], $device, $index, $mib, $descr, 1, $total, $used);
    }
  }
}

unset ($mempool_array, $index, $descr, $total, $used, $chassis_count, $percent);

// EOF
