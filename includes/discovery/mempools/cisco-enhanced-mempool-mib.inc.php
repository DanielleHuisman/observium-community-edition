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

$mempool_array = snmpwalk_cache_oid($device, 'cempMemPoolEntry', array(), $mib);

if (is_array($mempool_array))
{
  foreach ($mempool_array as $index => $entry)
  {
    if (is_numeric($entry['cempMemPoolUsed']) && $entry['cempMemPoolValid'] == 'true')
    {
      if (is_numeric($entry['cempMemPoolHCUsed']))
      {
        // Use HC counters
        $hc    = 1;
        print_debug('HC');
        $used  = $entry['cempMemPoolHCUsed'];
        $free  = $entry['cempMemPoolHCFree'];
      } else {
        // Use 32bit counters
        $hc    = 0;
        $used  = $entry['cempMemPoolUsed'];
        $free  = $entry['cempMemPoolFree'];
      }
      $total = $used + $free;

      list($entPhysicalIndex) = explode('.', $index);
      $entPhysicalName = trim(snmp_get($device, "entPhysicalName.$entPhysicalIndex", '-Oqv', 'ENTITY-MIB'));

      $descr = $entPhysicalName.' ('.$entry['cempMemPoolName'].')';
      $descr = str_replace('Cisco ', '', $descr);
      $descr = str_replace('Network Processing Engine', '', $descr);
      $descr = str_replace('CPU of', '', $descr);
      $descr = preg_replace('/Sub-Module ([0-9]+) CFC Card/', "Module \\1 CFC", $descr);

      discover_mempool($valid['mempool'], $device, $index, 'CISCO-ENHANCED-MEMPOOL-MIB', $descr, 1, $total, $used, $hc);
    }
  }
}

unset ($mempool_array, $index, $descr, $total, $used, $free, $entPhysicalIndex, $entPhysicalName);

// EOF
