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

if (is_device_mib($device, 'DES3026-L2MGMT-MIB')) {
    $desmib      = 'DES3026-L2MGMT-MIB';
    $copperports = 24;
} elseif (is_device_mib($device, 'DES3018-L2MGMT-MIB')) {
    $desmib      = 'DES3018-L2MGMT-MIB';
    $copperports = 16;
} else {
    return;
}

echo($desmib);
$revision                 = snmp_get_oid($device, 'probeHardwareRev.0', 'RMON2-MIB');
$system_index             = 1;
$inventory[$system_index] = [
  'entPhysicalDescr'        => $device['sysDescr'],
  'entPhysicalClass'        => 'chassis',
  'entPhysicalName'         => $device['hardware'],
  'entPhysicalHardwareRev'  => $revision,
  'entPhysicalSoftwareRev'  => $device['version'],
  'entPhysicalIsFRU'        => 'true',
  'entPhysicalModelName'    => $device['hardware'],
  'entPhysicalSerialNum'    => $device['serial'],
  'entPhysicalContainedIn'  => 0,
  'entPhysicalParentRelPos' => 1,
  'entPhysicalMfgName'      => 'D-Link',
];
discover_inventory($device, $system_index, $inventory[$system_index], $desmib);

for ($i = 1; $i <= $copperports; $i++) {
    $system_index             = 100 + $i;
    $inventory[$system_index] = [
      'entPhysicalDescr'        => '100Base-T Copper Port',
      'entPhysicalClass'        => 'port',
      'entPhysicalName'         => 'Port ' . $i,
      'entPhysicalIsFRU'        => 'false',
      'entPhysicalModelName'    => 'Copper Port',
      'entPhysicalContainedIn'  => 1,
      'entPhysicalParentRelPos' => $i,
      'ifIndex'                 => $i,
    ];
    discover_inventory($device, $system_index, $inventory[$system_index], $desmib);
}

for ($slot = 1; $slot <= 2; $slot++) {
    // Slot 1
    $system_index             = 100 + $i;
    $inventory[$system_index] = [
      'entPhysicalDescr'        => 'DES-3018/3026 extended Slot',
      'entPhysicalClass'        => 'container',
      'entPhysicalName'         => 'Slot ' . $slot,
      'entPhysicalIsFRU'        => 'false',
      'entPhysicalModelName'    => 'Extended Slot',
      'entPhysicalContainedIn'  => 1,
      'entPhysicalParentRelPos' => $i,
    ];
    discover_inventory($device, $system_index, $inventory[$system_index], $desmib);

    if ($slot == 1) {
        $des30xxswL2ModuleXType = snmp_get_oid($device, 'swL2Module-1-Type.0', $desmib);
    } else {
        $des30xxswL2ModuleXType = snmp_get_oid($device, 'swL2Module-2-Type.0', $desmib);
    }
    $system2_index             = 200 + $i;
    $inventory[$system2_index] = [
      'entPhysicalDescr'        => $des30xxswL2ModuleXType,
      'entPhysicalClass'        => 'module',
      'entPhysicalName'         => 'Module ' . $slot,
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalContainedIn'  => $system_index,
      'entPhysicalParentRelPos' => 0,
      'entPhysicalMfgName'      => 'D-Link',
    ];
    discover_inventory($device, $system2_index, $inventory[$system2_index], $desmib);

    $system3_index             = 300 + $i;
    $inventory[$system3_index] = [
      'entPhysicalDescr'        => 'Gigabit Ethernet',
      'entPhysicalClass'        => 'port',
      'entPhysicalIsFRU'        => 'false',
      'entPhysicalContainedIn'  => $system2_index,
      'entPhysicalParentRelPos' => 0,
      'ifIndex'                 => $i,
    ];
    discover_inventory($device, $system3_index, $inventory[$system3_index], $desmib);
    // Slot 2
    $i++;
}
print_debug_vars($inventory);

// EOF
