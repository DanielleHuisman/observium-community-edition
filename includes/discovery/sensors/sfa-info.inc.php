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

// SFA-INFO::physDiskIndex.1 = INTEGER: 1
// SFA-INFO::physDiskPoolId.1 = INTEGER: 1
// SFA-INFO::physDiskId.1 = INTEGER: 24
// SFA-INFO::physDiskWWN.1 = STRING: "5000cca234c16910"
// SFA-INFO::physDiskEnc.1 = STRING: "50001ff212ba6000"
// SFA-INFO::physDiskSlot.1 = INTEGER: 1
// SFA-INFO::physDiskState.1 = INTEGER: normal(1)
$cache['ddn']['physicalDiskTable'] = snmpwalk_cache_oid($device, "physicalDiskTable", [], "SFA-INFO");

foreach ($cache['ddn']['physicalDiskTable'] as $index => $entry) {
    $descr = "Disk " . $index . ": " . $entry['physDiskWWN'];

    $value = $entry['physDiskState'];
    $oid   = '.1.3.6.1.4.1.6894.2.9.1.7.' . $index;

    if ($value != '') {
        discover_status($device, $oid, 'physicalDisk.' . $index, 'sfa-disk-state', $descr, $value, ['entPhysicalClass' => 'hrDeviceDiskStorage']);
    }
}

// SFA-INFO::tempIndex.1 = INTEGER: 1
// SFA-INFO::tempEncId.1 = STRING: "50000000"
// SFA-INFO::tempEncPos.1 = INTEGER: 1
// SFA-INFO::tempStatus.1 = INTEGER: normal(1)
$cache['ddn']['tempTable'] = snmpwalk_cache_oid($device, "tempTable", [], "SFA-INFO");

foreach ($cache['ddn']['tempTable'] as $index => $entry) {
    $descr = "Temperature " . $entry['tempIndex'];

    $value = $entry['tempStatus'];
    $oid   = '.1.3.6.1.4.1.6894.2.2.1.4.' . $index;

    if ($value != '') {
        discover_status($device, $oid, 'temp.' . $index, 'sfa-temp-state', $descr, $value, ['entPhysicalClass' => 'temperature']);
    }
}

// SFA-INFO::powerIndex.1 = INTEGER: 1
// SFA-INFO::powerEncId.1 = STRING: "50000000"
// SFA-INFO::powerEncPos.1 = INTEGER: 1
// SFA-INFO::powerStatus.1 = INTEGER: healthy(1)
$cache['ddn']['powerTable'] = snmpwalk_cache_oid($device, "powerTable", [], "SFA-INFO");

foreach ($cache['ddn']['powerTable'] as $index => $entry) {
    $descr = "PowerSupply " . $entry['powerIndex'];

    $value = $entry['powerStatus'];
    $oid   = '.1.3.6.1.4.1.6894.2.6.1.4.' . $index;

    if ($value != '') {
        discover_status($device, $oid, 'power.' . $index, 'sfa-power-state', $descr, $value, ['entPhysicalClass' => 'powerSupply']);
    }
}

// SFA-INFO::fanIndex.1 = INTEGER: 1
// SFA-INFO::fanEncId.1 = STRING: "50000000"
// SFA-INFO::fanEncPos.1 = INTEGER: 1
// SFA-INFO::fanStatus.1 = INTEGER: healthy(1)
$cache['ddn']['fanTable'] = snmpwalk_cache_oid($device, "fanTable", [], "SFA-INFO");

foreach ($cache['ddn']['fanTable'] as $index => $entry) {
    $descr = "Fan " . $entry['fanIndex'];

    $value = $entry['fanStatus'];
    $oid   = '.1.3.6.1.4.1.6894.2.4.1.4.' . $index;

    if ($value != '') {
        discover_status($device, $oid, 'fan.' . $index, 'sfa-fan-state', $descr, $value, ['entPhysicalClass' => 'fan']);
    }
}

// EOF
