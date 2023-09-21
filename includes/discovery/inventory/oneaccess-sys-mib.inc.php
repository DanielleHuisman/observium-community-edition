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

$oids = snmpwalk_cache_oid($device, 'oacExpIMSysHwComponentsTable', [], 'ONEACCESS-SYS-MIB');

foreach ($oids as $index => $entry) {
    //print_r($entry);
    $index             = (int)$entry['oacExpIMSysHwcIndex'] + 1;
    $inventory[$index] = [
      'entPhysicalDescr'        => $entry['oacExpIMSysHwcDescription'],
      'entPhysicalName'         => $entry['oacExpIMSysHwcProductName'],
      'entPhysicalClass'        => $entry['oacExpIMSysHwcClass'],
      'entPhysicalModelName'    => $entry['oacExpIMSysHwcType'],
      //'entPhysicalAssetID'      => $entry['oacExpIMSysHwcManufacturer'],
      'entPhysicalSerialNum'    => $entry['oacExpIMSysHwcSerialNumber'],
      'entPhysicalIsFRU'        => 'false',
      'entPhysicalMfgName'      => 'OneAccess',
      'entPhysicalContainedIn'  => ($entry['oacExpIMSysHwcIndex'] == 0 ? 0 : 1),
      'entPhysicalParentRelPos' => ($entry['oacExpIMSysHwcIndex'] == 0 ? -1 : (int)$entry['oacExpIMSysHwcIndex']),
    ];

    discover_inventory($device, $index, $inventory[$index], $mib);
}

// EOF
