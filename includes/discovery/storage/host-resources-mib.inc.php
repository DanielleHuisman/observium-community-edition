<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Note. $cache_discovery['ucd-snmp-mib'] - is cached 'UCD-SNMP-MIB::dskEntry' (see ucd-snmp-mib.inc.php in current directory)

$hrStorage = snmp_cache_table($device, "hrStorageEntry", [], "HOST-RESOURCES-MIB:HOST-RESOURCES-TYPES");

$dsk_done = [];
if (!safe_empty($hrStorage)) {
    foreach ($hrStorage as $index => $storage) {
        $hc     = 0;
        $mib    = 'HOST-RESOURCES-MIB';
        $fstype = $storage['hrStorageType'];
        $descr  = $storage['hrStorageDescr'];
        $units  = $storage['hrStorageAllocationUnits'];

        switch ($fstype) {
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
                print_debug("skip(memory)");
                continue 2;

            case 'hrStorageRemovableDisk':
                if (isset($config['ignore_mount_removable']) && $config['ignore_mount_removable']) {
                    print_debug("skip(removable)");
                    continue 2;
                }
                break;

            case 'hrStorageNetworkDisk':
                if (isset($config['ignore_mount_network']) && $config['ignore_mount_network']) {
                    print_debug("skip(network)");
                    continue 2;
                }
                break;
            case 'hrStorageCompactDisc':
                if (isset($config['ignore_mount_optical']) && $config['ignore_mount_optical']) {
                    print_debug("skip(cd)");
                    continue 2;
                }
                break;

        }

        // Another 'hack' for isilon devices with very big array size
        if ($descr === '/ifs' && is_device_mib($device, 'ISILON-MIB')) {
            // Remove from polling by HOST-RESOURCES-MIB
            continue;
        }

        //32bit counters
        $size = snmp_dewrap32bit($storage['hrStorageSize']) * $units;
        $used = snmp_dewrap32bit($storage['hrStorageUsed']) * $units;

        $path = rewrite_storage($descr);

        // Find index from 'UCD-SNMP-MIB::dskTable'
        foreach ($cache_discovery['ucd-snmp-mib'] as $dsk) {
            if ($dsk['dskPath'] === $path) {
                // Using 64bit counters if available
                if (isset($dsk['dskTotalLow'])) {
                    $dsk['units'] = 1024;
                    $dsk['size']  = snmp_size64_high_low($dsk['dskTotalHigh'], $dsk['dskTotalLow']);
                    $dsk['size']  *= $dsk['units'];
                    if (($dsk['size'] - $size) > $units) {
                        // Use 64bit counters only if dskTotal bigger then hrStorageSize
                        // This is try.. if, if, if and more if
                        $hc     = 1;
                        $mib    = 'UCD-SNMP-MIB';
                        $index  = $dsk['dskIndex'];
                        $fstype = $dsk['dskDevice'];
                        $descr  = $dsk['dskPath'];
                        $units  = $dsk['units'];
                        $size   = $dsk['size'];
                        $used   = snmp_size64_high_low($dsk['dskUsedHigh'], $dsk['dskUsedLow']);
                        $used   *= $units;
                    }
                }
                break;
            }
        }

        if (is_numeric($index) && $size != 0) {
            discover_storage($valid['storage'], $device, $index, $fstype, $mib, $descr, $units, $size, $used, ['storage_hc' => $hc]);

            $dsk_done[$descr] = $path;
        }

        unset($fstype, $descr, $size, $used, $units, $path, $dsk, $hc);
    }
}

if (!safe_empty($cache_discovery['ucd-snmp-mib'])) {
    // Discover 'UCD-SNMP-MIB' if 'HOST-RESOURCES-MIB' empty.
    $mib = 'UCD-SNMP-MIB';

    foreach ($cache_discovery['ucd-snmp-mib'] as $index => $dsk) {
        // Skip already discovered
        if (in_array($dsk['dskPath'], $dsk_done, TRUE)) {
            continue;
        }

        $hc     = 0;
        $fstype = $dsk['dskDevice'];
        $descr  = $dsk['dskPath'];
        $units  = 1024;

        // Using 64bit counters if available
        if (isset($dsk['dskTotalLow'])) {
            $hc   = 1;
            $size = snmp_size64_high_low($dsk['dskTotalHigh'], $dsk['dskTotalLow']);
            $size *= $units;
            $used = snmp_size64_high_low($dsk['dskUsedHigh'], $dsk['dskUsedLow']);
            $used *= $units;
        } else {
            $size = $dsk['dskTotal'] * $units;
            $used = $dsk['dskUsed'] * $units;
        }

        if (is_numeric($index) && $size != 0) {
            discover_storage($valid['storage'], $device, $index, $fstype, $mib, $descr, $units, $size, $used, ['storage_hc' => $hc]);
        }
        unset($fstype, $descr, $size, $used, $units, $hc);
    }
}

// EOF
