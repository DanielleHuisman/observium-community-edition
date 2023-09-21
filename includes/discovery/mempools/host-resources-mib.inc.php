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

$hrStorage = snmp_cache_table($device, "hrStorageEntry", [], "HOST-RESOURCES-MIB:HOST-RESOURCES-TYPES");

//$debug_stats = array('total' => 0, 'used' => 0);
if (!safe_empty($hrStorage)) {

    if ($device['os'] === "arista_eos" && $hrStorage[1]['hrStorageDescr'] === 'RAM') {
        // Arista EOS derp hack for correct free memory
        // https://eos.arista.com/memory-utilization-on-eos-devices/

        // hrStorageType.1 = hrStorageRam
        // hrStorageType.2 = hrStorageRam
        // hrStorageType.3 = hrStorageRam
        // hrStorageType.100 = hrStorageRam
        // hrStorageDescr.1 = RAM
        // hrStorageDescr.2 = RAM (Buffers)
        // hrStorageDescr.3 = RAM (Cache)
        // hrStorageDescr.100 = RAM (Unavailable)
        // hrStorageAllocationUnits.1 = 1024
        // hrStorageAllocationUnits.2 = 1024
        // hrStorageAllocationUnits.3 = 1024
        // hrStorageAllocationUnits.100 = 1024
        // hrStorageSize.1 = 8152456
        // hrStorageSize.2 = 8152456
        // hrStorageSize.3 = 8152456
        // hrStorageSize.100 = 8152456
        // hrStorageUsed.1 = 7376060
        // hrStorageUsed.2 = 288472
        // hrStorageUsed.3 = 2984372
        // hrStorageUsed.100 = 1811664
        $hrStorage[1]['hrStorageUsed'] = snmp_dewrap32bit($hrStorage[1]['hrStorageUsed']);
        foreach ($hrStorage as $idx => $entry) {
            if ($idx != '1' && $entry['hrStorageType'] === 'hrStorageRam' &&
                str_starts($entry['hrStorageDescr'], 'RAM') && !str_contains($entry['hrStorageDescr'], 'Unavailable')) {
                // Use only Buffers and Cache
                $hrStorage[1]['hrStorageUsed'] -= snmp_dewrap32bit($entry['hrStorageUsed']);
                unset($hrStorage[$idx]);
            }
        }
        unset($idx);
    }

    foreach ($hrStorage as $index => $entry) {
        $descr = $entry['hrStorageDescr'];
        $units = $entry['hrStorageAllocationUnits'];
        $total = snmp_dewrap32bit($entry['hrStorageSize']);
        $used  = snmp_dewrap32bit($entry['hrStorageUsed']);
        $deny  = TRUE;

        switch ($entry['hrStorageType']) {
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
                $deny = FALSE;
                break;
        }


        if ($device['os'] === "routeros" && $descr === "main memory") {
            $deny = FALSE;
        } elseif ($device['os'] === "mcd") {
            // Yes, hardcoded logic for mcd, because they do not use standard
            // See: http://jira.observium.org/browse/OBSERVIUM-1269
            if ($index === 1) {
                // hrStorageType.1 = hrStorageRam
                // hrStorageDescr.1 = System Free Memory
                // hrStorageAllocationUnits.1 = 1
                // hrStorageSize.1 = 160481280
                // hrStorageUsed.1 = 160481280
                $descr = "Memory";
                $free  = $total;
                $total = 536870912; // 512Mb, Really total memory calculates as summary of all memory pools from this mib
                $used  = $total - $free;
                discover_mempool($valid['mempool'], $device, $index, "host-resources-mcd", $descr, $units, $total, $used);
            }
            $deny = TRUE;
            continue;
        }

        if ($deny ||
            str_contains_array($descr, ["MALLOC", "UMA"]) || // Ignore FreeBSD INSANITY
            str_contains_array($descr, ["procfs", "/proc"]) || // Ignore ProcFS
            in_array($descr, ["Cached memory", "Shared memory", "Physical memory"], TRUE)) { // Ignore worthless data on Unix hosts
            continue;
        }

        if (is_numeric($entry['hrStorageSize']) && $total) {
            discover_mempool($valid['mempool'], $device, $index, $mib, $descr, $units, $total, $used);
            //$debug_stats['total'] += $total;
            //$debug_stats['used']  += $used;
        }
    }
}
unset($index, $descr, $total, $used, $units, $deny);

// EOF
