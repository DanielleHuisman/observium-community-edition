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

if (safe_count($valid['status']['DNOS-BOXSERVICES-PRIVATE-MIB'])) {
    // Exit from discovery, since already added valid statuses by DNOS-BOXSERVICES-PRIVATE-MIB
    echo 'Skipped by DNOS-BOXSERVICES-PRIVATE-MIB';
    return;
}

if (safe_count($valid['status']['fastpath-boxservices-private-temp-state'])) {
    // Exit from discovery, since already added valid statuses by OLD-DNOS-BOXSERVICES-PRIVATE-MIB
    echo 'Skipped by OLD-DNOS-BOXSERVICES-PRIVATE-MIB';
    return;
}

// Dell-Vendor-MIB::envMonFanStatusDescr.67109249 = STRING: fan1
// Dell-Vendor-MIB::envMonFanStatusDescr.67109250 = STRING: fan2
// Dell-Vendor-MIB::envMonFanState.67109249 = INTEGER: normal(1)
// Dell-Vendor-MIB::envMonFanState.67109250 = INTEGER: normal(1)

$oids = snmpwalk_cache_oid($device, 'envMonFanStatusTable', [], 'Dell-Vendor-MIB');

foreach ($oids as $index => $entry) {
    $descr = str_replace('_', ' ', $entry['envMonFanStatusDescr']);
    $descr = preg_replace('/fan(\d+)/i', "Fan $1", $descr);
    $descr = preg_replace('/unit(\d+)/i', "Unit $1", $descr);
    if (!str_contains($descr, 'Fan')) {
        $descr = nicecase($descr);
    }
    $oid   = ".1.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.3." . $index;
    $value = $entry['envMonFanState'];

    $query = "SELECT sensor_id FROM `sensors` WHERE `device_id` = ? AND `sensor_class` = 'fanspeed'";
    $query .= " AND `sensor_type` IN ('radlan-hwenvironment-state','fastpath-boxservices-private-state')";
    $query .= " AND (`sensor_index` IN (?) OR `sensor_descr` = ?)";

    if ($entry['envMonFanState'] !== 'notPresent' &&
        !safe_count(dbFetchRows($query, [$device['device_id'], 'rlEnvMonFanState.' . $index, $descr]))) {
        discover_status_ng($device, $mib, 'envMonFanState', $oid, $index, 'dell-vendor-state', $descr, $value, ['entPhysicalClass' => 'fan']);
    }
}

// Dell-Vendor-MIB::envMonSupplyStatusDescr.67109185 = STRING: ps1
// Dell-Vendor-MIB::envMonSupplyStatusDescr.67109186 = STRING: ps2
// Dell-Vendor-MIB::envMonSupplyState.67109185 = INTEGER: normal(1)
// Dell-Vendor-MIB::envMonSupplyState.67109186 = INTEGER: notPresent(5)
// Dell-Vendor-MIB::envMonSupplySource.67109185 = INTEGER: ac(2)
// Dell-Vendor-MIB::envMonSupplySource.67109186 = INTEGER: unknown(1)

$oids = snmpwalk_cache_oid($device, 'envMonSupplyStatusTable', [], 'Dell-Vendor-MIB');

foreach ($oids as $index => $entry) {
    $descr = str_replace('_', ' ', $entry['envMonSupplyStatusDescr']);
    $descr = preg_replace('/ps(\d+)/i', "Power Supply $1", $descr);
    $descr = preg_replace('/unit(\d+)/i', "Unit $1", $descr);
    if (!str_contains($descr, 'Supply')) {
        $descr = nicecase($descr) . ' Power Supply';
    }
    if (in_array($entry['envMonSupplySource'], ['ac', 'dc'])) {
        $descr .= ' ' . strtoupper($entry['envMonSupplySource']);
    }
    $oid   = ".1.3.6.1.4.1.674.10895.3000.1.2.110.7.2.1.3." . $index;
    $value = $entry['envMonSupplyState'];

    if ($entry['envMonSupplyState'] !== 'notPresent') {
        // FIXME Is it possible to add stack member number to description?
        discover_status_ng($device, $mib, 'envMonSupplyState', $oid, $index, 'dell-vendor-state', $descr, $value, ['entPhysicalClass' => 'powerSupply']);
    }
}

// EOF
