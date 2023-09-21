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

$oids = snmp_cache_table($device, 'tmnxHwTable', NULL, 'TIMETRA-CHASSIS-MIB');

foreach ($oids as $index => $entry) {
    [$chassis, $system_index] = explode('.', $index);

    $inventory[$system_index] = [
      'entPhysicalDescr'        => $entry['tmnxHwName'],
      'entPhysicalClass'        => $entry['tmnxHwClass'],
      'entPhysicalName'         => $entry['tmnxHwName'],
      'entPhysicalAlias'        => $entry['tmnxHwAlias'],
      'entPhysicalAssetID'      => $entry['tmnxHwAssetID'],
      'entPhysicalIsFRU'        => $entry['tmnxHwIsFRU'],
      'entPhysicalSerialNum'    => $entry['tmnxHwSerialNumber'],
      'entPhysicalContainedIn'  => $entry['tmnxHwContainedIn'],
      'entPhysicalParentRelPos' => $entry['tmnxHwParentRelPos'],
      'entPhysicalMfgName'      => $entry['tmnxHwMfgString'] // 'Alcatel-Lucent'
    ];
    if ($entry['tmnxHwContainedIn'] === '0' && $entry['tmnxHwParentRelPos'] === '-1') {
        $inventory[$system_index]['entPhysicalName'] .= ' ' . $chassis;
    }

    discover_inventory($device, $system_index, $inventory[$system_index], $mib);
}

// EOF
