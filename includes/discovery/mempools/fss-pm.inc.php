<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) Adam Armstrong
 *
 */

/// Derp MIB and data

// FSS-EQPT::eqptShelfOperational-state.'1' = INTEGER: up(1)
// FSS-EQPT::eqptShelfAdministrative-state.'1' = INTEGER: up(1)
// FSS-EQPT::eqptShelfType.'1' = STRING: BDL1-3R11
// FSS-EQPT::eqptShelfPiVendorName.'1' = STRING: FUJITSU
// FSS-EQPT::eqptShelfPiUnitName.'1' = STRING: BDL1-3R11
// FSS-EQPT::eqptShelfPiVendorUnitCode.'1' = STRING: b111
// FSS-EQPT::eqptShelfPiHwRevision.'1' = STRING: 18
// FSS-EQPT::eqptShelfPiPartNumber.'1' = STRING: FC95453R11
// FSS-EQPT::eqptShelfPiClei.'1' = STRING: WOMS610DRD
// FSS-EQPT::eqptShelfPiDom.'1' = STRING: 21.01
// FSS-EQPT::eqptShelfPiSerialNumber.'1' = STRING: 01303
// FSS-EQPT::eqptShelfPiUsi.'1' = STRING: LBFJTU012115453R111801303
// FSS-EQPT::eqptShelfRowstatus.'1' = INTEGER: active(1)

$shelf_array = snmp_cache_table($device, 'eqptShelfEntry', [], 'FSS-EQPT');
if (!snmp_status()) {
    return;
}
//print_debug_vars($shelf_array);

// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".memoryUsage.nearEnd.na.a15-min.0 = STRING: 18.19
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".memoryUsage.nearEnd.na.a1-day.0 = STRING: 18.19
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".memoryUsageMin.nearEnd.na.a15-min.0 = STRING: 18.12
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".memoryUsageMin.nearEnd.na.a1-day.0 = STRING: 17.88
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".memoryUsageMax.nearEnd.na.a15-min.0 = STRING: 18.22
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".memoryUsageMax.nearEnd.na.a1-day.0 = STRING: 18.48
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".memoryUsageAvg.nearEnd.na.a15-min.0 = STRING: 18.14
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".memoryUsageAvg.nearEnd.na.a1-day.0 = STRING: 18.07


// Cache this walk because it's shared between several modules
if(isset($_GLOBALS['snmpwalk_cache'][$device['device_id']]['pmEqptShelfPm-recordTime-period-indexPm-data-value'])) {
    $walk_array = $_GLOBALS['snmpwalk_cache'][$device['device_id']]['pmEqptShelfPm-recordTime-period-indexPm-data-value'];
} else {
    $walk_array = snmpwalk_cache_twopart_oid($device, 'pmEqptShelfPm-recordTime-period-indexPm-data-value', [], 'FSS-PM');
    $walk_array = snmpwalk_cache_twopart_oid($device, 'pmEqptShelfPm-recordTime-period-indexPm-validity', $walk_array, 'FSS-PM');

    $_GLOBALS['snmpwalk_cache'][$device['device_id']]['pmEqptShelfPm-recordTime-period-indexPm-data-value'] = $walk_array;
}


foreach ($walk_array as $shelf => $array) {
    if (!isset($shelf_array[$shelf])) {
        continue;
    }
    print_debug_vars($shelf_array[$shelf]);

    $shelf_name = $shelf_array[$shelf]['eqptShelfPiVendorName'] . ' ' . $shelf_array[$shelf]['eqptShelfType'] . " #$shelf";
    $shelf_mempool = 0;

    foreach ($array as $index_parts => $entry) {

        //$entry = array_merge($entry, $shelf_array[$shelf]);

        // FSS-PM::FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".memoryUsage.nearEnd.na.a15-min.0 = STRING: 18.19
        // FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value.1.49.69.0.2.1.0 = STRING: 18.19
        [ $recordMontype, $recordLocn, $recordDirn, $recordTime, $recordIndex ] = explode('.', $index_parts);

        // Process only Memory Usage here
        if ($recordMontype !== 'memoryUsage' || $recordTime !== 'a15-min' || $recordIndex !== 0 ||
            $entry['pmEqptShelfPm-recordTime-period-indexPm-validity'] === 'false') {
            continue;
        }
        print_debug_vars($entry);

        $shelf_mempool++;

        // Convert named index to numeric
        // FCMonType from FSS-COMMON-PM-TC
        //         memoryUsage                     (69),
        //         memoryUsageMin                  (70),
        //         memoryUsageMax                  (71),
        //         memoryUsageAvg                  (72),
        $index = snmp_string_to_oid($shelf) . '.69';

        // pmEqptShelfPm-recordLocn OBJECT-TYPE
        //     SYNTAX      INTEGER {nearEnd(0),farEnd(1)}
        switch ($recordLocn) {
            case 'nearEnd':    $index .= '.0'; break;
            case 'farEnd':     $index .= '.1'; break;
        }

        // pmEqptShelfPm-recordDirn OBJECT-TYPE
        //     SYNTAX      INTEGER {transmit(0),receive(1),na(2)}
        switch ($recordDirn) {
            case 'transmit':   $index .= '.0'; break;
            case 'receive':    $index .= '.1'; break;
            case 'na':         $index .= '.2'; break;
        }

        // pmEqptShelfPm-recordTime-period-indexTime-period OBJECT-TYPE
        //     SYNTAX      INTEGER {cumulative(0),a15-min(1),a1-day(2),a1-week(3),a1-month(4)}
        switch ($recordTime) {
            case 'cumulative': $index .= '.0'; break;
            case 'a15-min':    $index .= '.1'; break;
            case 'a1-day':     $index .= '.2'; break;
            case 'a1-week':    $index .= '.3'; break;
            case 'a1-month':   $index .= '.4'; break;
        }

        $index .= ".$recordIndex";
        print_debug("Index converted: '$shelf.$index_parts' = '$index'");

        $oid_name = 'pmEqptShelfPm-recordTime-period-indexPm-data-value';
        $oid_num = ".1.3.6.1.4.1.211.1.24.12.800.2.1.3.$index";

        $descr = "Memory $shelf_mempool ($shelf_name)";
        $percent = $entry[$oid_name];

        discover_mempool($valid['mempool'], $device, $index, $mib, $descr, 1, 100, $percent);
    }
}

unset($walk_array);

// EOF
