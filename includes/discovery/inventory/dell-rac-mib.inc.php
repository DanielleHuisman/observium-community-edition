<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Only run this mib for chassis systems
// DELL-RAC-MIB::drsProductType.0 = INTEGER: cmc(8)
if (snmp_get_oid($device, "drsProductType.0", "DELL-RAC-MIB") !== "cmc") {
    return;
}

$oids = snmp_cache_table($device, 'drsChassisServerGroup', [], 'DELL-RAC-MIB'); // This table also used in statuses

$index             = 1;
$inventory[$index] = [
  'entPhysicalName'         => $device['hardware'] . ' Chassis',
  'entPhysicalDescr'        => $device['hostname'],
  'entPhysicalClass'        => 'chassis',
  'entPhysicalIsFRU'        => 'true',
  'entPhysicalModelName'    => $device['hardware'],
  'entPhysicalSerialNum'    => $device['serial'],
  'entPhysicalHardwareRev'  => snmp_get($device, "drsProductVersion.0", "-Oqv", "DELL-RAC-MIB"),
  'entPhysicalFirmwareRev'  => $device['version'],
  'entPhysicalAssetID'      => $device['asset_tag'],
  'entPhysicalContainedIn'  => 0,
  'entPhysicalParentRelPos' => -1,
  'entPhysicalMfgName'      => 'Dell'
];
discover_inventory($device, $index, $inventory[$index], $mib);

foreach ($oids as $tmp => $entry) {
    if ($entry['drsServerSlotNumber'] === "N/A") {
        continue;
    }
    $index += 2;

    // Full height blades take up two slots and are marked as Extension
    if (!str_contains($entry['drsServerSlotName'], "Extension")) {
        $serial            = $entry['drsServerServiceTag'];
        $inventory[$index] = [
          'entPhysicalName'         => 'Slot ' . $entry['drsServerSlotNumber'],
          'entPhysicalClass'        => 'container',
          'entPhysicalIsFRU'        => 'true',
          'entPhysicalContainedIn'  => 1,
          'entPhysicalParentRelPos' => $entry['drsServerSlotNumber'],
          'entPhysicalMfgName'      => 'Dell'
        ];
        $model             = $entry['drsServerModel'];
        if ($entry['drsServerMonitoringCapable'] === "off") {
            $model .= ' (OFF)';
        }
        $inventory[$index + 1] = [
          'entPhysicalName'         => $entry['drsServerSlotName'],
          'entPhysicalDescr'        => $entry['drsServerSlotName'],
          'entPhysicalClass'        => 'module',
          'entPhysicalIsFRU'        => 'true',
          'entPhysicalModelName'    => $model,
          'entPhysicalSerialNum'    => $serial,
          'entPhysicalContainedIn'  => $index,
          'entPhysicalParentRelPos' => 1,
          'entPhysicalMfgName'      => 'Dell'
        ];
        discover_inventory($device, $index, $inventory[$index], $mib);
        discover_inventory($device, $index + 1, $inventory[$index + 1], $mib);
        unset($serial, $model);

    } else {
        $i                                = $index - 2;
        $inventory[$i]['entPhysicalName'] .= '+' . $entry['drsServerSlotNumber'];
        discover_inventory($device, $i, $inventory[$i], $mib);
    }
}

// EOF
