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

$hrStorage = snmp_cache_table($device, "hrStorageEntry", [], "HOST-RESOURCES-MIB:HOST-RESOURCES-TYPES");

$index = $mempool['mempool_index'];

if ($device['os'] === "arista_eos" && $index == 1) {
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
    print_debug_vars($hrStorage);
}

$mempool['mempool_multiplier'] = $hrStorage[$index]['hrStorageAllocationUnits'];
$mempool['used']               = (int)snmp_dewrap32bit($hrStorage[$index]['hrStorageUsed']); // if hrStorageUsed not set, use 0
$mempool['total']              = snmp_dewrap32bit($hrStorage[$index]['hrStorageSize']);

// EOF
