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

$mib          = 'RAISECOM-FANMONITOR-MIB';
$serials      = snmp_get_oid($device, 'raisecomFanCardSerialNumber.0', $mib); // STRING: "serial1;serial2"
$system_index = 101;
$i            = 1;
foreach (explode(';', $serials) as $fan_card_serial) {
    if ($fan_card_serial != 'NULL') // the serial number is string 'NULL' when the fan card is absent
    {
        $inventory[$system_index] = [
          'entPhysicalDescr'        => '',
          'entPhysicalClass'        => 'module',
          'entPhysicalName'         => "Fan Card $i",
          'entPhysicalSerialNum'    => $fan_card_serial,
          'entPhysicalAssetID'      => '',
          'entPhysicalIsFRU'        => 'true',
          'entPhysicalContainedIn'  => 1, // ENTITY-MIB exposes the chassis with index 1.
          'entPhysicalParentRelPos' => -1,
          'entPhysicalMfgName'      => 'Raisecom'
        ];
        discover_inventory($device, $system_index, $inventory[$system_index], $mib);
    }
    $system_index++;
    $i++;
}

unset($mib, $serials, $system_index, $i, $fan_card_serial);

// EOF
