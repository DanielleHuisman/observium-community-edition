<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

// Note. $cache_discovery['ucd-snmp-mib'] - is cached 'UCD-SNMP-MIB::dskEntry' (see ucd-snmp-mib.inc.php in current directory)

$mib = 'HOST-RESOURCES-MIB';

if (!isset($cache_discovery['host-resources-mib']))
{
  $cache_discovery['host-resources-mib'] = snmpwalk_cache_oid($device, "hrStorageEntry", array(), "HOST-RESOURCES-MIB:HOST-RESOURCES-TYPES");
  print_debug_vars($cache_discovery['host-resources-mib']);
}

$dsk_done = [];
if (safe_count($cache_discovery['host-resources-mib'])) {
  foreach ($cache_discovery['host-resources-mib'] as $index => $storage)
  {
    $hc = 0;
    $mib = 'HOST-RESOURCES-MIB';
    $fstype = $storage['hrStorageType'];
    $descr = $storage['hrStorageDescr'];
    $units = $storage['hrStorageAllocationUnits'];
    $deny = FALSE;

    switch($fstype)
    {
      case 'hrStorageVirtualMemory':
      case 'hrStorageRam':
      case 'hrStorageOther':
      case 'hrStorageTypes.20':
      case 'nwhrStorageDOSMemory':
      case 'nwhrStorageMemoryAlloc':
      case 'nwhrStorageMemoryPermanent':
      case 'nwhrStorageCacheBuffers':
      case 'nwhrStorageCacheMovable':
      case 'nwhrStorageCacheNonMovable':
      case 'nwhrStorageCodeAndDataMemory':
      case 'nwhrStorageIOEngineMemory':
      case 'nwhrStorageMSEngineMemory':
      case 'nwhrStorageUnclaimedMemory':
        $deny = TRUE;
        break;
      case 'hrStorageRemovableDisk':
        if (isset($config['ignore_mount_removable']) && $config['ignore_mount_removable'])
        {
          $deny = TRUE;
          print_debug("skip(removable)");
        }
        break;
      case 'hrStorageNetworkDisk':
        if (isset($config['ignore_mount_network'])   && $config['ignore_mount_network'])
        {
          $deny = TRUE;
          print_debug("skip(network)");
        }
        break;
      case 'hrStorageCompactDisc':
        if (isset($config['ignore_mount_optical'])   && $config['ignore_mount_optical'])
        {
          $deny = TRUE;
          print_debug("skip(cd)");
        }
        break;

    }

    // Another 'hack' for isilon devices with very big array size
    if ($descr == '/ifs' && is_device_mib($device, 'ISILON-MIB'))
    {
      $deny = TRUE; // Remove from polling by HOST-RESOURCES-MIB
    }

    if (!$deny)
    {
      //32bit counters
      $size = snmp_dewrap32bit($storage['hrStorageSize']) * $units;
      $used = snmp_dewrap32bit($storage['hrStorageUsed']) * $units;

      $path = rewrite_storage($descr);

      // Find index from 'UCD-SNMP-MIB::dskTable'
      foreach ($cache_discovery['ucd-snmp-mib'] as $dsk)
      {
        if ($dsk['dskPath'] === $path)
        {
          // Using 64bit counters if available
          if (isset($dsk['dskTotalLow']))
          {
            $dsk['units'] = 1024;
            $dsk['size'] = snmp_size64_high_low($dsk['dskTotalHigh'], $dsk['dskTotalLow']);
            $dsk['size'] *= $dsk['units'];
            if (($dsk['size'] - $size) > $units)
            {
              // Use 64bit counters only if dskTotal bigger then hrStorageSize
              // This is try.. if, if, if and more if
              $hc = 1;
              $mib    = 'UCD-SNMP-MIB';
              $index  = $dsk['dskIndex'];
              $fstype = $dsk['dskDevice'];
              $descr  = $dsk['dskPath'];
              $units  = $dsk['units'];
              $size   = $dsk['size'];
              $used   = snmp_size64_high_low($dsk['dskUsedHigh'], $dsk['dskUsedLow']);
              $used  *= $units;
            }
          }
          break;
        }
      }
    }

    if (!$deny && is_numeric($index))
    {
      discover_storage($valid['storage'], $device, $index, $fstype, $mib, $descr, $units, $size, $used, array('storage_hc' => $hc));

      $dsk_done[$descr] = $descr;

    }

    unset($deny, $fstype, $descr, $size, $used, $units, $path, $dsk, $hc);
  }
}

if (safe_count($cache_discovery['ucd-snmp-mib'])) {
  // Discover 'UCD-SNMP-MIB' if 'HOST-RESOURCES-MIB' empty.
  $mib = 'UCD-SNMP-MIB';

  foreach ($cache_discovery['ucd-snmp-mib'] as $index => $dsk)
  {
    $hc = 0;
    $fstype = $dsk['dskDevice'];
    $descr  = $dsk['dskPath'];
    $units  = 1024;
    $deny   = FALSE;

    // Using 64bit counters if available
    if (isset($dsk['dskTotalLow']))
    {
      $hc = 1;
      $size  = snmp_size64_high_low($dsk['dskTotalHigh'], $dsk['dskTotalLow']);
      $size *= $units;
      $used  = snmp_size64_high_low($dsk['dskUsedHigh'], $dsk['dskUsedLow']);
      $used *= $units;
    } else {
      $size  = $dsk['dskTotal'] * $units;
      $used  = $dsk['dskUsed'] * $units;
    }

    if (!$deny && is_numeric($index) && !in_array($descr, $dsk_done))
    {
      discover_storage($valid['storage'], $device, $index, $fstype, $mib, $descr, $units, $size, $used, array('storage_hc' => $hc));
    }
    unset($deny, $fstype, $descr, $size, $used, $units, $hc);
  }
}

// EOF
