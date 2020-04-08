<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$domain = snmpwalk_cache_oid($device, 'vcDomain', NULL, 'HPVC-MIB');
$domain = $domain[0];

// Domain
$array = array(
  'entPhysicalName'         => $domain['vcDomainName'],
  'entPhysicalClass'        => 'domain',
  'entPhysicalIsFRU'        => 'true',
  'entPhysicalDescr'        => $domain['vcDomainPrimaryAddressType'],
  'entPhysicalAssetID'      => $domain['vcDomainPrimaryAddress'],
  'entPhysicalContainedIn'  => 0,
  'entPhysicalParentRelPos' => -1,
  'entPhysicalMfgName'      => 'HPE'
);
discover_inventory($device, '-1', $array, $mib);

$entries = snmpwalk_cache_oid($device, 'vcEnclosureTable', NULL, 'HPVC-MIB');

foreach ($entries as $index => $entry)
{
  $array = array(
    'entPhysicalName'         => $entry['vcEnclosureName'],
    'entPhysicalClass'        => 'chassis',
    'entPhysicalIsFRU'        => 'true',
    'entPhysicalContainedIn'  => 0,
    'entPhysicalDescr'    => $entry['vcEnclosureAddressType'],
    'entPhysicalSerialNum'    => $entry['vcEnclosureUUID'],
    'entPhysicalParentRelPos' => 1,
    'entPhysicalMfgName'      => 'HPE'
  );
  if(isset($entry['vcEnclosureIndex']))
  {
    discover_inventory($device, $index, $array, $mib);
  }
}

unset($entries, $array, $domain);

$entries = snmpwalk_cache_oid($device, 'vcPhysicalServerTable', NULL, 'HPVC-MIB');

// Blades
foreach ($entries as $index => $entry)
{

  $array = array(
    'entPhysicalName'         => 'Slot '.$entry['vcPhysicalServerLocation.'],
    'entPhysicalClass'        => 'container',
    'entPhysicalIsFRU'        => 'true',
    'entPhysicalContainedIn'  => 1,
    'entPhysicalParentRelPos' => $entry['vcPhysicalServerLocation.'],
    'entPhysicalContainedIn'  => $entry['vcPhysicalServerEnclosureIndex'],
    'entPhysicalMfgName'      => 'HPE'
  );
  //discover_inventory($device, '999'.$index, $array, $mib);

  $array = array(
    'entPhysicalName'         => $entry['vcPhysicalServerProductName'],
    //'entPhysicalDescr'        => $entry['vcPhysicalServerProductName'],
    'entPhysicalClass'        => 'module',
    'entPhysicalIsFRU'        => 'true',
    'entPhysicalModelName'    => $entry['vcPhysicalServerPartNumber'],
    'entPhysicalSerialNum'    => $entry['vcPhysicalServerSerialNumber'],
    'entPhysicalContainedIn'  => $entry['vcPhysicalServerEnclosureIndex'],
    'entPhysicalParentRelPos' => 1,
    'entPhysicalMfgName'      => 'HPE'
  );
  if(isset($entry['vcPhysicalServerEnclosureIndex']))
  {
    discover_inventory($device, $index, $array, $mib);
  }

}

$entries = snmpwalk_cache_oid($device, 'vcModuleTable', NULL, 'HPVC-MIB');

// Modules
foreach ($entries as $index => $entry)
{

  list($entry['type'], $entry['index']) = explode('.', $entry['vcModuleEnclosurePointer']);

  $array = array(
    'entPhysicalName'         => $entry['vcModuleProductName'],
    //'entPhysicalDescr'        => $entry['vcPhysicalServerProductName'],
    'entPhysicalClass'        => 'module',
    'entPhysicalIsFRU'        => 'true',
    'entPhysicalModelName'    => $entry['vcModulePartNumber'],
    'entPhysicalSerialNum'    => $entry['vcModuleSerialNumber'],
    'entPhysicalContainedIn'  => $entry['index'],
    'entPhysicalParentRelPos' => $entry['vcModuleLocation'],
    'entPhysicalMfgName'      => 'HPE'
  );
  if(isset($entry['vcModuleLocation']))
  {
    discover_inventory($device, '999'.$index, $array, $mib);
  }

}
