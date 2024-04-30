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

$mtxrBoardName       = snmp_get_oid($device, 'mtxrDisplayName.0', 'MIKROTIK-MIB');
//$mtxrBoardName       = snmp_get_oid($device, 'mtxrBoardName.0', 'MIKROTIK-MIB'); // old mib
$mtxrSerialNumber    = snmp_get_oid($device, 'mtxrSerialNumber.0', 'MIKROTIK-MIB');
$mtxrLicSoftwareId   = snmp_get_oid($device, 'mtxrLicSoftwareId.0', 'MIKROTIK-MIB');
$mtxrLicVersion      = snmp_get_oid($device, 'mtxrLicVersion.0', 'MIKROTIK-MIB');
$mtxrFirmwareVersion = snmp_get_oid($device, 'mtxrFirmwareVersion.0', 'MIKROTIK-MIB');

$system_index = 1;
if ($mtxrSerialNumber) {
    $inventory[$system_index] = [
      'entPhysicalDescr'        => 'MikroTik RouterBoard',
      'entPhysicalClass'        => 'chassis',
      'entPhysicalName'         => '',
      'entPhysicalModelName'    => $mtxrBoardName,
      'entPhysicalSerialNum'    => $mtxrSerialNumber,
      'entPhysicalAssetID'      => $mtxrLicSoftwareId,
      'entPhysicalIsFRU'        => 'false',
      'entPhysicalContainedIn'  => 0,
      'entPhysicalParentRelPos' => 0,
      'entPhysicalFirmwareRev'  => $mtxrFirmwareVersion,
      'entPhysicalSoftwareRev'  => $mtxrLicVersion,
      'entPhysicalMfgName'      => 'MikroTik'
    ];
    discover_inventory($device, $system_index, $inventory[$system_index], "MIKROTIK-MIB");

    print_debug_vars($inventory);
}

// EOF
