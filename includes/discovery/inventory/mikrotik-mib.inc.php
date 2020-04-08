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

$mtxrSerialNumber = snmp_get_oid($device, 'mtxrSerialNumber.0', 'MIKROTIK-MIB');

$system_index = 1;
if ($mtxrSerialNumber)
{
  $inventory[$system_index] = array(
    'entPhysicalDescr'        => 'MikroTik RouterBoard',
    'entPhysicalClass'        => 'chassis',
    'entPhysicalName'         => '',
    'entPhysicalSerialNum'    => $mtxrSerialNumber,
    'entPhysicalAssetID'      => '',
    'entPhysicalIsFRU'        => 'false',
    'entPhysicalContainedIn'  => 0,
    'entPhysicalParentRelPos' => 0,
    'entPhysicalMfgName'      => 'MikroTik'
  );
  discover_inventory($device, $system_index, $inventory[$system_index], "MIKROTIK-MIB");

  print_debug_vars($inventory);
}

// EOF
