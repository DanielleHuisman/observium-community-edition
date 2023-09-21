<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$productModel = snmp_get_oid($device, 'productModel.0', 'NETAPP-MIB');
if (safe_empty($productModel)) { $productModel = 'Cluster'; }

$productSerialNum = snmp_get_oid($device, 'productSerialNum.0', 'NETAPP-MIB');

// Insert chassis as index 1, everything hangs off of this.
$system_index = 1;
$inventory[$system_index] = [
  'entPhysicalDescr'        => $productModel,
  'entPhysicalClass'        => 'chassis',
  'entPhysicalName'         => 'Chassis',
  'entPhysicalSerialNum'    => $productSerialNum,
  'entPhysicalIsFRU'        => 'true',
  'entPhysicalContainedIn'  => 0,
  'entPhysicalParentRelPos' => -1,
  'entPhysicalMfgName'      => 'NetApp'
];

discover_inventory($device, $system_index, $inventory[$system_index], $mib);

// Now fetch data for the rest of the hardware in the chassis
$data = snmpwalk_cache_oid($device, 'enclTable', [], 'NETAPP-MIB');

foreach ($data as $index => $part) {
  $node_index = string_to_id($part['enclNodeName']);
  while ($node_index > 2147483647) {
    $node_index /= 1024;
    $node_index = round($node_index);
    print_vars($node_index);
  }
  // NETAPP-MIB::enclChannelShelfAddr.1 = STRING: 0b.shelf0
  // NETAPP-MIB::enclProductLogicalID.1 = STRING: 5:00a:09800e:56ea77
  // NETAPP-MIB::enclProductID.1 = STRING: DS224-12
  // NETAPP-MIB::enclProductVendor.1 = STRING: NETAPP
  // NETAPP-MIB::enclProductModel.1 = STRING: DS224-12
  // NETAPP-MIB::enclProductRevision.1 = STRING: 0220
  // NETAPP-MIB::enclProductSerialNo.1 = STRING: SHFGB2051000413
  // NETAPP-MIB::enclNodeName.1 = STRING: WSLNetapp02-01
  $inventory[$node_index] = [
    'entPhysicalDescr'        => 'Disk Enclosure',
    'entPhysicalHardwareRev'  => $part['enclProductRevision'],
    'entPhysicalClass'        => 'storageDrive', // 'container'
    'entPhysicalName'         => 'Disk Enclosure',
    'entPhysicalSerialNum'    => $part['enclProductSerialNo'],
    'entPhysicalModelName'    => $part['enclProductModel'],
    //'entPhysicalVendorType'   => $part['jnxContentsType'], //$part['jnxContentsModel'],
    'entPhysicalIsFRU'        => 'false',
    'entPhysicalContainedIn'  => 1,
    'entPhysicalParentRelPos' => $index,
    'entPhysicalMfgName'      => $part['enclProductVendor'],
  ];

  discover_inventory($device, $node_index, $inventory[$node_index], $mib);

  // NETAPP-MIB::enclPowerSuppliesMaximum.1 = INTEGER: 2
  // NETAPP-MIB::enclPowerSuppliesPresent.1 = STRING: 1, 2
  // NETAPP-MIB::enclPowerSuppliesSerialNos.1 = STRING: PSD094204221062, PSD094204221059
  // NETAPP-MIB::enclPowerSuppliesFailed.1 = STRING:
  // NETAPP-MIB::enclPowerSuppliesPartNos.1 = STRING: 114-00148+F1, 114-00148+F1
  $parts   = explode(', ', $part['enclPowerSuppliesPresent']);
  $serials = explode(', ', $part['enclPowerSuppliesSerialNos']);
  $nos     = explode(', ', $part['enclPowerSuppliesPartNos']);
  foreach ($parts as $i => $num) {
    $part_index = $node_index + 256 + $num;
    $inventory[$part_index] = [
      'entPhysicalDescr'        => "Power Supply $num",
      //'entPhysicalHardwareRev'  => '',
      'entPhysicalClass'        => 'powerSupply',
      'entPhysicalName'         => "Power Supply $num",
      'entPhysicalSerialNum'    => $serials[$i],
      'entPhysicalModelName'    => $nos[$i],
      //'entPhysicalVendorType'   => $part['jnxContentsType'], //$part['jnxContentsModel'],
      'entPhysicalIsFRU'        => 'false',
      'entPhysicalContainedIn'  => $node_index,
      'entPhysicalParentRelPos' => $num,
      'entPhysicalMfgName'      => $part['enclProductVendor'],
    ];
    discover_inventory($device, $part_index, $inventory[$part_index], $mib);
  }

  // NETAPP-MIB::enclFansMaximum.1 = INTEGER: 4
  // NETAPP-MIB::enclFansPresent.1 = STRING: 1, 2, 3, 4
  // NETAPP-MIB::enclFansFailed.1 = STRING:
  //

  // NETAPP-MIB::enclElectronicsMaximum.1 = INTEGER: 2
  // NETAPP-MIB::enclElectronicsPresent.1 = STRING: 1, 2
  // NETAPP-MIB::enclElectronicsSerialNos.1 = STRING: 952040001613, 952040001698
  // NETAPP-MIB::enclElectronicsFailed.1 = STRING:
  // NETAPP-MIB::enclElectronicsPartNos.1 = STRING: 111-04333+C0, 111-04333+C0
  // NETAPP-MIB::enclElectronicsCPLDVers.1 = STRING: 24, 24
}

// EOF
