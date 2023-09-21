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

/*
BIANCA-BRICK-MIBRES-MIB::memoryType.flash.0 = INTEGER: flash(1)
BIANCA-BRICK-MIBRES-MIB::memoryType.dram.1 = INTEGER: dram(2)
BIANCA-BRICK-MIBRES-MIB::memoryType.dpool.2 = INTEGER: dpool(3)
BIANCA-BRICK-MIBRES-MIB::memoryDescr.flash.0 = Hex-STRING: 4F 6E 62 6F 61 72 64 20 46 6C 61 73 68 00
BIANCA-BRICK-MIBRES-MIB::memoryDescr.dram.1 = Hex-STRING: 4D 61 69 6E 20 4D 65 6D 6F 72 79 00
BIANCA-BRICK-MIBRES-MIB::memoryDescr.dpool.2 = STRING: "STREAMS Class 0"
BIANCA-BRICK-MIBRES-MIB::memoryBlockSize.flash.0 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
BIANCA-BRICK-MIBRES-MIB::memoryBlockSize.dram.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
BIANCA-BRICK-MIBRES-MIB::memoryBlockSize.dpool.2 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
BIANCA-BRICK-MIBRES-MIB::memoryTotal.flash.0 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 4194304
BIANCA-BRICK-MIBRES-MIB::memoryTotal.dram.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 16711680
BIANCA-BRICK-MIBRES-MIB::memoryTotal.dpool.2 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 96
BIANCA-BRICK-MIBRES-MIB::memoryInuse.flash.0 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
BIANCA-BRICK-MIBRES-MIB::memoryInuse.dram.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 13639380
BIANCA-BRICK-MIBRES-MIB::memoryInuse.dpool.2 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
BIANCA-BRICK-MIBRES-MIB::memoryDramUse.flash.0 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
BIANCA-BRICK-MIBRES-MIB::memoryDramUse.dram.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
BIANCA-BRICK-MIBRES-MIB::memoryDramUse.dpool.2 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 6528
BIANCA-BRICK-MIBRES-MIB::memoryNAllocs.flash.0 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
BIANCA-BRICK-MIBRES-MIB::memoryNAllocs.dram.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 3006
BIANCA-BRICK-MIBRES-MIB::memoryNAllocs.dpool.2 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 53354
BIANCA-BRICK-MIBRES-MIB::memoryNFrees.flash.0 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
BIANCA-BRICK-MIBRES-MIB::memoryNFrees.dram.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 89
BIANCA-BRICK-MIBRES-MIB::memoryNFrees.dpool.2 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 53434
BIANCA-BRICK-MIBRES-MIB::memoryNFails.flash.0 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
BIANCA-BRICK-MIBRES-MIB::memoryNFails.dram.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
BIANCA-BRICK-MIBRES-MIB::memoryNFails.dpool.2 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
*/

$mempool_array = snmpwalk_cache_oid($device, 'memoryTable', [], 'BIANCA-BRICK-MIBRES-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

foreach ($mempool_array as $index => $entry) {
    if ($entry['memoryType'] == 'dpool') {
        continue;
    }
    $entry['memoryDescr'] = snmp_hexstring($entry['memoryDescr']);

    discover_mempool($valid['mempool'], $device, $index, 'BIANCA-BRICK-MIBRES-MIB', $entry['memoryDescr'], 1, $entry['memoryTotal'], $entry['memoryInuse']);
}

unset ($mempool_array);

// EOF
