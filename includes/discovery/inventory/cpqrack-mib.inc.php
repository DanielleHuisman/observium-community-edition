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

$oids = snmp_cache_table($device, 'cpqRackServerBladeTable', NULL, 'CPQRACK-MIB'); // This table also used in statuses

// Chassis
$index             = 1;
$inventory[$index] = [
  'entPhysicalName'         => $device['hardware'] . ' Chassis',
  'entPhysicalDescr'        => $device['hostname'],
  'entPhysicalClass'        => 'chassis',
  'entPhysicalIsFRU'        => 'true',
  'entPhysicalModelName'    => $device['hardware'],
  'entPhysicalSerialNum'    => $device['serial'],
  'entPhysicalFirmwareRev'  => $device['version'],
  'entPhysicalAssetID'      => $device['asset_tag'],
  'entPhysicalContainedIn'  => 0,
  'entPhysicalParentRelPos' => -1,
  'entPhysicalMfgName'      => 'HP'
];
discover_inventory($device, $index, $inventory[$index], $mib);

// Blades
foreach ($oids as $tmp => $entry) {
    if ($entry['cpqRackServerBladeEntry'] == "0") {
        continue;
    }
    if ($entry['cpqRackServerBladeSlotsUsed'] == "0") {
        continue;
    }

    $index += 2;

    $inventory[$index] = [
      'entPhysicalName'         => 'Slot ' . $entry['cpqRackServerBladePosition'],
      'entPhysicalClass'        => 'container',
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalContainedIn'  => 1,
      'entPhysicalParentRelPos' => $entry['cpqRackServerBladePosition'],
      'entPhysicalMfgName'      => 'HP'
    ];
    $model             = $entry['cpqRackServerBladeProductId'];

    if ($entry['cpqRackServerBladePowered'] === "off") {
        $model .= ' (OFF)';
    }
    $inventory[$index + 1] = [
      'entPhysicalName'         => $entry['cpqRackServerBladeName'],
      'entPhysicalDescr'        => $entry['cpqRackServerBladeName'],
      'entPhysicalClass'        => 'module',
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalModelName'    => $model,
      'entPhysicalSerialNum'    => $entry['cpqRackServerBladeSerialNum'],
      'entPhysicalContainedIn'  => $index,
      'entPhysicalParentRelPos' => 1,
      'entPhysicalMfgName'      => 'HP'
    ];
    discover_inventory($device, $index, $inventory[$index], $mib);
    discover_inventory($device, $index + 1, $inventory[$index + 1], $mib);
    unset($model);
}

$oids = snmp_cache_table($device, 'cpqRackPowerSupplyTable', NULL, 'CPQRACK-MIB'); // This table also used in sensors

foreach ($oids as $pwr => $entry) {
    $index += 2;

    $inventory[$index]     = [
      'entPhysicalName'         => 'Power Supply Module ' . $pwr,
      'entPhysicalClass'        => 'container',
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalContainedIn'  => 1,
      'entPhysicalParentRelPos' => $pwr,
      'entPhysicalMfgName'      => 'HP'
    ];
    $inventory[$index + 1] = [
      'entPhysicalName'         => 'Power Supply ' . $pwr,
      'entPhysicalDescr'        => 'Power Supply ' . $pwr,
      'entPhysicalClass'        => 'power',
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalModelName'    => $entry['cpqRackPowerSupplyPartNumber'],
      'entPhysicalSerialNum'    => $entry['cpqRackPowerSupplySerialNum'],
      'entPhysicalContainedIn'  => $index,
      'entPhysicalParentRelPos' => 1,
      'entPhysicalMfgName'      => 'HP'
    ];
    discover_inventory($device, $index, $inventory[$index], $mib);
    discover_inventory($device, $index + 1, $inventory[$index + 1], $mib);
}

$nets = snmpwalk_cache_oid($device, 'cpqRackNetConnectorTable', [], 'CPQRACK-MIB');

foreach ($nets as $net => $entry) {
    if ($entry['cpqRackNetConnectorPresent'] == "absent") {
        continue;
    }
    $index += 2;

    $inventory[$index]     = [
      'entPhysicalName'         => 'Network Connector Module ' . $net,
      'entPhysicalClass'        => 'container',
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalContainedIn'  => 1,
      'entPhysicalParentRelPos' => $net,
      'entPhysicalMfgName'      => 'HP'
    ];
    $inventory[$index + 1] = [
      'entPhysicalName'         => 'Network Connector ' . $net,
      'entPhysicalDescr'        => 'Network Connector ' . $net,
      'entPhysicalClass'        => 'port',
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalModelName'    => $entry['cpqRackNetConnectorModel'],
      'entPhysicalSerialNum'    => $entry['cpqRackNetConnectorSerialNum'],
      'entPhysicalContainedIn'  => $index,
      'entPhysicalParentRelPos' => 1,
      'entPhysicalMfgName'      => 'HP'
    ];
    discover_inventory($device, $index, $inventory[$index], $mib);
    discover_inventory($device, $index + 1, $inventory[$index + 1], $mib);
}

$oas = snmpwalk_cache_oid($device, 'cpqRackCommonEnclosureManagerTable', [], 'CPQRACK-MIB');

foreach ($oas as $oa => $entry) {
    if ($entry['cpqRackCommonEnclosureManagerPresent'] == "absent") {
        continue;
    }
    $index += 2;

    $inventory[$index]     = [
      'entPhysicalName'         => 'Onboard Administrator Module ' . $oa,
      'entPhysicalClass'        => 'container',
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalContainedIn'  => 1,
      'entPhysicalParentRelPos' => $oa,
      'entPhysicalMfgName'      => 'HP'
    ];
    $oa_state              = $entry['cpqRackCommonEnclosureManagerRole'];
    $inventory[$index + 1] = [
      'entPhysicalName'         => 'Onboard Administrator ' . $oa . ' [' . $oa_state . ']',
      'entPhysicalDescr'        => 'Onboard Administrator ' . $oa,
      'entPhysicalClass'        => 'module',
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalModelName'    => $entry['cpqRackCommonEnclosureManagerEnclosureName'],
      'entPhysicalSerialNum'    => $entry['cpqRackCommonEnclosureManagerSerialNum'],
      'entPhysicalFirmwareRev'  => $entry['cpqRackCommonEnclosureManagerFWRev'],
      'entPhysicalContainedIn'  => $index,
      'entPhysicalParentRelPos' => 1,
      'entPhysicalMfgName'      => 'HP'
    ];
    discover_inventory($device, $index, $inventory[$index], $mib);
    discover_inventory($device, $index + 1, $inventory[$index + 1], $mib);
}

unset($power, $net, $oa, $oa_state);

// EOF
