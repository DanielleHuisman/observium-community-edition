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

// RADLAN-HWENVIROMENT::rlEnvMonFanStatusDescr.67109249 = STRING: "fan1"
// RADLAN-HWENVIROMENT::rlEnvMonFanStatusDescr.67109250 = STRING: "fan2"
// RADLAN-HWENVIROMENT::rlEnvMonFanState.67109249 = INTEGER: normal(1)
// RADLAN-HWENVIROMENT::rlEnvMonFanState.67109250 = INTEGER: normal(1)

$oids = snmpwalk_cache_oid($device, 'rlEnvMonFanStatusTable', [], 'RADLAN-HWENVIROMENT');

foreach ($oids as $index => $entry) {
    $descr = str_replace('_', ' ', $entry['rlEnvMonFanStatusDescr']);
    $descr = preg_replace('/fan(\d+)/i', "Fan $1", $descr);
    $descr = preg_replace('/unit(\d+)/i', "Unit $1", $descr);
    $oid   = ".1.3.6.1.4.1.89.83.1.1.1.3.$index";
    $value = $entry['rlEnvMonFanState'];

    if (isset($valid['status']['Dell-Vendor-MIB']['dell-vendor-state']) &&
        dbExist('status', '`device_id` = ? AND `status_object` = ?', [$device['device_id'], 'envMonFanState'])) {
        // Skip statuses when already by Dell-Vendor-MIB
        continue;
    }
    if ($entry['rlEnvMonFanState'] !== 'notPresent') {
        //discover_status($device, $oid, "rlEnvMonFanState.$index", 'radlan-hwenvironment-state', $descr, $value, array('entPhysicalClass' => 'fan'));
        discover_status_ng($device, $mib, 'rlEnvMonFanState', $oid, $index, 'radlan-hwenvironment-state', $descr, $value, ['entPhysicalClass' => 'fan']);
    }
}

// RADLAN-HWENVIROMENT::rlEnvMonSupplyStatusDescr.67109185 = STRING: "ps1"
// RADLAN-HWENVIROMENT::rlEnvMonSupplyStatusDescr.67109186 = STRING: "ps2"
// RADLAN-HWENVIROMENT::rlEnvMonSupplyState.67109185 = INTEGER: normal(1)
// RADLAN-HWENVIROMENT::rlEnvMonSupplyState.67109186 = INTEGER: notPresent(5)
// RADLAN-HWENVIROMENT::rlEnvMonSupplySource.67109185 = INTEGER: ac(2)
// RADLAN-HWENVIROMENT::rlEnvMonSupplySource.67109186 = INTEGER: unknown(1)

$oids = snmpwalk_cache_oid($device, 'rlEnvMonSupplyStatusTable', [], 'RADLAN-HWENVIROMENT');

foreach ($oids as $index => $entry) {
    $descr = str_replace('_', ' ', $entry['rlEnvMonSupplyStatusDescr']);
    $descr = preg_replace('/ps(\d+)/i', "Power Supply $1", $descr);
    $descr = preg_replace('/unit(\d+)/i', "Unit $1", $descr);
    if (in_array($entry['rlEnvMonSupplySource'], ['ac', 'dc'])) {
        $descr .= ' ' . strtoupper($entry['envMonSupplySource']);
    }
    $oid   = ".1.3.6.1.4.1.89.83.1.2.1.3.$index";
    $value = $entry['rlEnvMonSupplyState'];

    if (isset($valid['status']['Dell-Vendor-MIB']['dell-vendor-state']) &&
        dbExist('status', '`device_id` = ? AND `status_object` = ?', [$device['device_id'], 'envMonSupplyState'])) {
        // Skip statuses when already by Dell-Vendor-MIB
        continue;
    }
    if ($entry['rlEnvMonSupplyState'] !== 'notPresent') {
        //discover_status($device, $oid, "rlEnvMonSupplyState.$index", 'radlan-hwenvironment-state', $descr, $value, array('entPhysicalClass' => 'powerSupply'));
        discover_status_ng($device, $mib, 'rlEnvMonSupplyState', $oid, $index, 'radlan-hwenvironment-state', $descr, $value, ['entPhysicalClass' => 'powerSupply']);
    }
}

// EOF
