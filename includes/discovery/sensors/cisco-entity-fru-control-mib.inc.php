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

// Skip statuses if we have any status from CISCO-ENVMON-MIB (for exclude duplicates)
$skip_status = dbExist('status', '`device_id` = ? AND `status_type` = ? AND `status_deleted` = ?', [$device['device_id'], 'cisco-envmon-state', 0]);

// Walk CISCO-ENTITY-FRU-CONTROL-MIB oids
$entity_array = [];
$oids         = ['cefcFRUPowerStatusEntry', 'cefcFanTrayStatusEntry', 'cefcFanEntry', 'cefcModuleEntry'];
foreach ($oids as $oid) {
    $entity_array = snmpwalk_cache_oid($device, $oid, $entity_array, 'CISCO-ENTITY-FRU-CONTROL-MIB');
}
// split PowerSupplyGroup from common walk array
$cefcFRUPowerSupplyGroupEntry = snmpwalk_cache_oid($device, 'cefcFRUPowerSupplyGroupEntry', [], 'CISCO-ENTITY-FRU-CONTROL-MIB');

if (!safe_empty($entity_array)) {
    // Pre-cache entity mib (if not cached in inventory module)
    if (is_array($GLOBALS['cache']['snmp']['ENTITY-MIB'][$device['device_id']])) {
        // If this already received in inventory module, skip walking
        $entity_mib = $GLOBALS['cache']['snmp']['ENTITY-MIB'][$device['device_id']];
        print_debug("ENTITY-MIB already cached");
    } else {
        $entity_mibs = snmp_mib_entity_vendortype($device, 'ENTITY-MIB');

        $snmp_flags = OBS_SNMP_ALL;
        snmp_log_error(OBS_SNMP_ERROR_OID_NOT_INCREASING, FALSE); // disable log error for next snmpwalk
        $entity_mib = snmpwalk_cache_oid($device, "entPhysicalDescr", [], $entity_mibs);
        if (!snmp_status() && snmp_error_code() === OBS_SNMP_ERROR_OID_NOT_INCREASING) {

            // Try refetch with NOINCREASE
            $snmp_flags |= OBS_SNMP_NOINCREASE;
            print_debug("WARNING! snmpwalk error 'OID not increasing' detected, try snmpwalk with -Cc option.");

            $entity_mib = snmpwalk_cache_oid($device, "entPhysicalDescr", $entity_mib, $entity_mibs, NULL, $snmp_flags);
        }

        if (snmp_status()) {
            $oids = ['entPhysicalName', 'entPhysicalClass', 'entPhysicalContainedIn', 'entPhysicalParentRelPos', 'entPhysicalVendorType'];
            foreach ($oids as $oid) {
                $entity_mib = snmpwalk_cache_oid($device, $oid, $entity_mib, $entity_mibs, NULL, $snmp_flags);
                if (!snmp_status()) {
                    break;
                }
            }
            //$entity_mib = snmpwalk_cache_twopart_oid($device, 'entAliasMappingIdentifier', $entity_array, 'ENTITY-MIB:IF-MIB', NULL, $snmp_flags);
            //$GLOBALS['cache']['snmp']['ENTITY-MIB'][$device['device_id']] = $entity_mib;
        }
    }

    // Merge with ENTITY-MIB
    if (!safe_empty($entity_mib)) {
        // Power & Fan
        foreach ($entity_array as $index => $entry) {
            if (isset($entity_mib[$index])) {
                $entity_array[$index] = array_merge($entity_mib[$index], $entry);
            }
        }
        // PowerSupplyGroup
        foreach ($cefcFRUPowerSupplyGroupEntry as $index => $entry) {
            if (isset($entity_mib[$index])) {
                $cefcFRUPowerSupplyGroupEntry[$index] = array_merge($entity_mib[$index], $entry);
            }
        }
    }
    unset($entity_mib);

    print_debug_vars($cefcFRUPowerSupplyGroupEntry);
    print_debug_vars($entity_array);

    foreach ($cefcFRUPowerSupplyGroupEntry as $index => $entry) {
        $descr = $entry['entPhysicalDescr'];

        $oid_name = 'cefcTotalDrawnCurrent';
        $oid_num  = '.1.3.6.1.4.1.9.9.117.1.1.1.1.4.' . $index;
        $type     = $mib . '-' . $oid_name;

        if (str_istarts($entry['cefcPowerUnits'], 'centi')) {
            $scale = 0.01; // cefcPowerUnits.100000470 = STRING: CentiAmps @ 12V
        } elseif (str_istarts($entry['cefcPowerUnits'], 'milli')) {
            $scale = 0.001; // cefcPowerUnits.18 = STRING: milliAmps12v
        } else {
            // FIXME. Other?
            $scale = 1;
        }
        $value = $entry[$oid_name];
        if ($value > 0) {
            // Limits
            $options = [];
            if ($entry['cefcTotalAvailableCurrent'] > 0) {
                if (str_ends($entry['cefcTotalAvailableCurrent'], '00')) {
                    // Cisco 4900M:
                    // cefcPowerUnits.9 = centiAmpsAt12V
                    // cefcTotalAvailableCurrent.9 = 8000
                    // cefcTotalDrawnCurrent.9 = 4883
                    $options['limit_high'] = $entry['cefcTotalAvailableCurrent'] * $scale;
                } else {
                    // Cisco 2901:
                    // cefcPowerUnits.18 = milliAmps12v
                    // cefcTotalAvailableCurrent.18 = 1659
                    // cefcTotalDrawnCurrent.18 = 9641
                    $options['limit_high'] = ($value + $entry['cefcTotalAvailableCurrent']) * $scale;
                }
                $options['limit_high_warn'] = $options['limit_high'] * 0.8; // 80%
            }
            discover_sensor_ng($device, 'current', $mib, 'cefcTotalDrawnCurrent', $oid_num, $index, NULL, $descr, $scale, $value, $options);
        }

        $oid_name = 'cefcPowerRedundancyOperMode';
        $oid_num  = '.1.3.6.1.4.1.9.9.117.1.1.1.1.5.' . $index;
        $type     = 'cefcPowerRedundancyType';
        $value    = $entry[$oid_name];

        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'powersupply']);
    }

    foreach ($entity_array as $index => $entry) {
        if (!is_numeric($index)) {
            continue;
        }

        $descr = $entry['entPhysicalDescr'];

        // Power Supplies
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcFRUPowerAdminStatus.470 = INTEGER: on(1)
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcFRUPowerOperStatus.470 = INTEGER: on(2)
        if (!$skip_status && $entry['entPhysicalClass'] == 'powerSupply' && $entry['cefcFRUPowerAdminStatus'] != 'off') {
            $oid_name = 'cefcFRUPowerOperStatus';
            $oid_num  = '.1.3.6.1.4.1.9.9.117.1.1.2.1.2.' . $index;
            $type     = 'PowerOperType';
            $value    = $entry[$oid_name];

            discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'powersupply']);
        }

        // A negative value expresses current used by the FRU.
        // A positive value expresses current supplied by the FRU.
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcFRUCurrent.470 = INTEGER: 2810
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcFRUActualInputCurrent.470 = INTEGER: 45558
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcFRUActualOutputCurrent.470 = INTEGER: 61000

        // Fans
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcFanTrayOperStatus.534 = INTEGER: up(2)
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcFanTrayDirection.534 = INTEGER: backToFront(3)
        if (!$skip_status && $entry['entPhysicalClass'] === 'fan') {
            $oid_name = 'cefcFanTrayOperStatus';
            $oid_num  = '.1.3.6.1.4.1.9.9.117.1.4.1.1.1.' . $index;
            $type     = 'cefcFanTrayOperStatus';
            $value    = $entry[$oid_name];

            discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'fan']);
        }

        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcFanSpeed.119000 = Gauge32: 9326 rpm
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcFanSpeedPercent.119000 = INTEGER: 57 percent
        if (isset($entry['cefcFanSpeed']) && $entry['cefcFanSpeed'] > 0) {
            $options = [];
            // Detect limits based on speed percent
            if (isset($entry['cefcFanSpeedPercent']) && $entry['cefcFanSpeedPercent'] > 0) {
                $max     = ($entry['cefcFanSpeed'] * 100) / $entry['cefcFanSpeedPercent'];
                $options = ['limit_high' => (int)($max * 0.95), 'limit_high_warn' => (int)($max * 0.8), 'limit_low' => 0];
            }

            $oid_name = 'cefcFanSpeed';
            $oid_num  = '.1.3.6.1.4.1.9.9.117.1.4.2.1.1.' . $index;
            $value    = $entry[$oid_name];
            discover_sensor_ng($device, 'fanspeed', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }

        // Modules
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcModuleAdminStatus.22 = INTEGER: enabled(1)
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcModuleOperStatus.22 = INTEGER: ok(2)
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcModuleResetReason.22 = INTEGER: unknown(1)
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcModuleStatusLastChangeTime.22 = Timeticks: (0) 0:00:00.00
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcModuleLastClearConfigTime.22 = Timeticks: (158892673) 18 days, 9:22:06.73
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcModuleResetReasonDescription.22 = STRING:
        // CISCO-ENTITY-FRU-CONTROL-MIB::cefcModuleUpTime.22 = Gauge32: 1988824
        if (isset($entry['cefcModuleOperStatus']) && $entry['cefcModuleAdminStatus'] === 'enabled') {
            $oid_name = 'cefcModuleOperStatus';
            $oid_num  = '.1.3.6.1.4.1.9.9.117.1.2.1.1.2.' . $index;
            $type     = 'cefcModuleOperStatus';
            $value    = $entry[$oid_name];

            discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => $entry['entPhysicalClass']]);
        }
    }
}

unset($cefcFRUPowerSupplyGroupEntry, $entity_array, $skip_status);

// EOF
