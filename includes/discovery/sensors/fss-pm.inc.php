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

// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".temperature.nearEnd.na.a15-min.0 = STRING: 24.0
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".temperature.nearEnd.na.a1-day.0 = STRING: 24.0
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".temperatureMin.nearEnd.na.a15-min.0 = STRING: 23.0
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".temperatureMin.nearEnd.na.a1-day.0 = STRING: 23.0
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".temperatureMax.nearEnd.na.a15-min.0 = STRING: 26.0
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".temperatureMax.nearEnd.na.a1-day.0 = STRING: 27.0
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".temperatureAvg.nearEnd.na.a15-min.0 = STRING: 23.8
// FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".temperatureAvg.nearEnd.na.a1-day.0 = STRING: 25.21

// Cache this walk because it's shared between several modules
if(isset($_GLOBALS['snmpwalk_cache'][$device['device_id']]['pmEqptShelfPm-recordTime-period-indexPm-data-value'])) {
    $oids = $_GLOBALS['snmpwalk_cache'][$device['device_id']]['pmEqptShelfPm-recordTime-period-indexPm-data-value'];
} else {
    $oids = snmpwalk_cache_twopart_oid($device, 'pmEqptShelfPm-recordTime-period-indexPm-data-value', [], 'FSS-PM');
    $oids = snmpwalk_cache_twopart_oid($device, 'pmEqptShelfPm-recordTime-period-indexPm-validity', $oids, 'FSS-PM');

    $_GLOBALS['snmpwalk_cache'][$device['device_id']]['pmEqptShelfPm-recordTime-period-indexPm-data-value'] = $oids;
}

foreach ($oids as $shelf => $array) {
    if (!isset($shelf_array[$shelf])) {
        continue;
    }
    print_debug_vars($shelf_array[$shelf]);

    $shelf_name = $shelf_array[$shelf]['eqptShelfPiVendorName'] . ' ' . $shelf_array[$shelf]['eqptShelfType'] . " #$shelf";
    $shelf_sensor = 0;

    foreach ($array as $index_parts => $entry) {

        //$entry = array_merge($entry, $shelf_array[$shelf]);

        // FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value."1".temperature.nearEnd.na.a15-min.0 = STRING: 24.0
        // FSS-PM::pmEqptShelfPm-recordTime-period-indexPm-data-value.1.49.1.0.2.1.0 = STRING: 24.00
        [ $recordMontype, $recordLocn, $recordDirn, $recordTime, $recordIndex ] = explode('.', $index_parts);

        // Process only Sensors here and only first valid entry for 15 min samples
        if ($recordMontype !== 'temperature' || $recordTime !== 'a15-min' || $recordIndex !== 0 ||
            $entry['pmEqptShelfPm-recordTime-period-indexPm-validity'] === 'false') {
            continue;
        }

        print_debug_vars($entry);

        $shelf_sensor++;

        // Convert named index to numeric
        // FCMonType from FSS-COMMON-PM-TC
        //         temperature                     (1),
        //         temperatureMin                  (2),
        //         temperatureMax                  (3),
        //         temperatureAvg                  (4),
        $index = snmp_string_to_oid($shelf) . '.1';

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

        $descr = nicecase($recordMontype) . " ($shelf_name)";
        $value = $entry[$oid_name];

        discover_sensor_ng($device, $recordMontype, $mib, $oid_name, $oid_num, $index, $descr, 1, $value);
    }
}

// DOM sensors

/// THIS IS MOSTLY DERP DEVICE, MIB, OIDS WHICH I SEEN!!!
/// I DO NOT KNOW HOW ASSOCIATE WITH PORTS,
/// I DO NOT KNOW WHAT SENSORS MEANS

/*
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'C1' = STRING: Client
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'C2' = STRING: Client
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'C3' = STRING: Client
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'C4' = STRING: Client
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'C5' = STRING: Client
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'C6' = STRING: Client
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'C7' = STRING: Client
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'C8' = STRING: Client
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'C9' = STRING: Client
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'E-SC-E1' = STRING: 100ME
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'E1' = STRING: Edge
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'L1' = STRING: Logical
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'LCN1' = STRING: 1GEBT
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'LCN3' = STRING: 1GEBT
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'LCN4' = STRING: 1GEBT
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'LMP' = STRING: LMP
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."1"."0"."0".'OSC' = STRING: OSC

FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."2"."0"."0".'C1' = STRING: Client
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."2"."0"."0".'C9' = STRING: Client
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."2"."0"."0".'E-SC-E1' = STRING: 100ME
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."2"."0"."0".'E1' = STRING: Edge
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."2"."0"."0".'L1' = STRING: Logical
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."2"."0"."0".'LCN3' = STRING: 1GEBT
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."2"."0"."0".'LCN4' = STRING: 1GEBT
FSS-EQPT::eqptShelfSlotSubslotPortPluggableInterfaceType."2"."0"."0".'OSC' = STRING: OSC
 */

/*
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."8".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: -17.2
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."8".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: 4.7
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."8".laserBiasCurrent.nearEnd.transmit.a15-min.0 = STRING: 9.2

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."9".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: 4.5

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."11".opticalPowerReceiveOts.nearEnd.receive.a15-min.0 = STRING: -8.5
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."11".opticalPowerTransmitOts.nearEnd.transmit.a15-min.0 = STRING: 9.2
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."11".opticalPowerReceiveOms.nearEnd.receive.a15-min.0 = STRING: -9.4
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."11".opticalPowerTransmitOms.nearEnd.transmit.a15-min.0 = STRING: 8.0
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."11".opticalPowerReceiveOsc.nearEnd.receive.a15-min.0 = STRING: -16.1
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."11".opticalPowerTransmitOsc.nearEnd.transmit.a15-min.0 = STRING: 2.9
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."11".spanLoss.nearEnd.receive.a15-min.0 = STRING: 18.2
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."11".spanLossVariation.nearEnd.receive.a15-min.0 = STRING: -0.4
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."11".reflectionPower.nearEnd.receive.a15-min.0 = STRING: 32.6

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."12".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: 3.3
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."12".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: 3.2

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."13".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: -99.9
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."13".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: -99.9

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."14".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: -99.9
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."14".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: -99.9

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."15".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: -99.9
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."15".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: -99.9

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."16".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: -99.9
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."16".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: -99.9

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."17".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: -99.9
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."17".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: -99.9

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."18".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: -99.9
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."18".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: -99.9

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."19".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: -99.9
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."19".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: -99.9

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."20".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: 0.3
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."1"."0"."0"."20".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: -9.0

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."8".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: -24.5
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."8".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: 4.3
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."8".laserBiasCurrent.nearEnd.transmit.a15-min.0 = STRING: 9.4

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."9".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: 3.9

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."11".opticalPowerReceiveOts.nearEnd.receive.a15-min.0 = STRING: -13.0
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."11".opticalPowerTransmitOts.nearEnd.transmit.a15-min.0 = STRING: 12.3
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."11".opticalPowerReceiveOms.nearEnd.receive.a15-min.0 = STRING: -13.4
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."11".opticalPowerTransmitOms.nearEnd.transmit.a15-min.0 = STRING: 11.8
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."11".opticalPowerReceiveOsc.nearEnd.receive.a15-min.0 = STRING: -23.4
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."11".opticalPowerTransmitOsc.nearEnd.transmit.a15-min.0 = STRING: 2.9
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."11".spanLoss.nearEnd.receive.a15-min.0 = STRING: 25.1
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."11".spanLossVariation.nearEnd.receive.a15-min.0 = STRING: -0.5
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."11".reflectionPower.nearEnd.receive.a15-min.0 = STRING: 33.0

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."12".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: 2.9
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."12".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: 3.2

FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."20".opticalPowerReceive.nearEnd.receive.a15-min.0 = STRING: -0.2
FSS-PM::pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value."2"."0"."0"."20".opticalPowerTransmit.nearEnd.transmit.a15-min.0 = STRING: -9.0
 */

// This walk is only done once in code at present, so no cache.
$oids = snmpwalk_cache_oid($device, 'pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value', [], 'FSS-PM');
$oids = snmpwalk_cache_oid($device, 'pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-validity', $oids, 'FSS-PM');
print_debug_vars($oids);

// FCMonType from FSS-COMMON-PM-TC
// temperature                     (1),
// opticalPowerReceive             (6),
// opticalPowerTransmit            (13),
// laserBiasCurrent                (17),
// opticalPowerReceiveLane1        (18),
// opticalPowerTransmitLane1       (21),
// laserBiasCurrentLane1           (24),
// opticalPowerReceiveLane2        (25),
// opticalPowerTransmitLane2       (28),
// laserBiasCurrentLane2           (31),
// opticalPowerReceiveLane3        (32),
// opticalPowerTransmitLane3       (35),
// laserBiasCurrentLane3           (38),
// opticalPowerReceiveLane4        (39),
// opticalPowerTransmitLane4       (42),
// laserBiasCurrentLane4           (45),
// opticalSignalNoiseRatio         (49),
$record_montypes = [
    'temperature'               => [ 'index' => 1,  'descr' => 'Temperature', 'class' => 'temperature' ], // not seen
    'opticalPowerReceive'       => [ 'index' => 6,  'descr' => 'RX Power', 'class' => 'dbm' ],
    'opticalPowerTransmit'      => [ 'index' => 13, 'descr' => 'TX Power', 'class' => 'dbm' ],
    'laserBiasCurrent'          => [ 'index' => 17, 'descr' => 'TX Bias',  'class' => 'current' ],
    'opticalPowerReceiveLane1'  => [ 'index' => 18, 'descr' => 'RX Power Lane 1', 'class' => 'dbm', 'unit' => 'split_lane1' ],
    'opticalPowerTransmitLane1' => [ 'index' => 21, 'descr' => 'TX Power Lane 1', 'class' => 'dbm', 'unit' => 'split_lane1' ],
    'laserBiasCurrentLane1'     => [ 'index' => 24, 'descr' => 'TX Bias Lane 1',  'class' => 'current', 'unit' => 'split_lane1' ],
    'opticalPowerReceiveLane2'  => [ 'index' => 25, 'descr' => 'RX Power Lane 2', 'class' => 'dbm', 'unit' => 'split_lane2' ],
    'opticalPowerTransmitLane2' => [ 'index' => 28, 'descr' => 'TX Power Lane 2', 'class' => 'dbm', 'unit' => 'split_lane2' ],
    'laserBiasCurrentLane2'     => [ 'index' => 31, 'descr' => 'TX Bias Lane 2',  'class' => 'current', 'unit' => 'split_lane2' ],
    'opticalPowerReceiveLane3'  => [ 'index' => 32, 'descr' => 'RX Power Lane 3', 'class' => 'dbm', 'unit' => 'split_lane3' ],
    'opticalPowerTransmitLane3' => [ 'index' => 35, 'descr' => 'TX Power Lane 3', 'class' => 'dbm', 'unit' => 'split_lane3' ],
    'laserBiasCurrentLane3'     => [ 'index' => 38, 'descr' => 'TX Bias Lane 3',  'class' => 'current', 'unit' => 'split_lane3' ],
    'opticalPowerReceiveLane4'  => [ 'index' => 39, 'descr' => 'RX Power Lane 4', 'class' => 'dbm', 'unit' => 'split_lane4' ],
    'opticalPowerTransmitLane4' => [ 'index' => 42, 'descr' => 'TX Power Lane 4', 'class' => 'dbm', 'unit' => 'split_lane4' ],
    'laserBiasCurrentLane4'     => [ 'index' => 45, 'descr' => 'TX Bias Lane 4',  'class' => 'current', 'unit' => 'split_lane4' ],
    'opticalSignalNoiseRatio'   => [ 'index' => 49, 'descr' => 'SNR', 'class' => 'snr' ],
];

foreach ($oids as $index_parts => $entry) {

    [ $shelf, $shelf_slot, $shelf_subslot, $shelf_port,
      $recordMontype, $recordLocn, $recordDirn, $recordTime, $recordIndex ] = explode('.', $index_parts);

    if (!isset($shelf_array[$shelf])) {
        continue;
    }
    //print_debug_vars($shelf_array[$shelf]);

    $shelf_name = $shelf_array[$shelf]['eqptShelfPiVendorName'] . ' ' . $shelf_array[$shelf]['eqptShelfType'] . " #$shelf";
    $shelf_sensor = 0;

    // Process only Sensors here
    print_debug_vars($entry);
    if ($recordTime !== 'a15-min' || $entry['pmEqptShelfPm-recordTime-period-indexPm-validity'] === 'false' || $recordIndex !== 0 ||
        !isset($record_montypes[$recordMontype])) {
        continue;
    }
    //print_debug_vars($entry);

    $index = snmp_string_to_oid($shelf) . '.' . snmp_string_to_oid($shelf_slot) . '.' .
             snmp_string_to_oid($shelf_subslot) . '.' . snmp_string_to_oid($shelf_port);
    $index .= '.' . $record_montypes[$recordMontype]['index'];

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
    print_debug("Index converted: '$index_parts' = '$index'");

    $port_label = "port-$shelf/$shelf_slot/$shelf_subslot/$shelf_port";
    $descr      = $port_label . ' ' . $record_montypes[$recordMontype]['descr'];
    $options = [
        'measured_class' => 'fiber',
        'measured_entity_label' => $port_label,
    ];
    if (isset($record_montypes[$recordMontype]['unit'])) {
        $options['sensor_unit'] = $record_montypes[$recordMontype]['unit'];
    }

    $oid_name = 'pmEqptShelfSlotSubslotPortPm-recordTime-period-indexPm-data-value';
    $oid_num  = ".1.3.6.1.4.1.211.1.24.12.800.8.1.3.$index";
    $value    = $entry[$oid_name];

    if ($record_montypes[$recordMontype]['class'] === 'dbm' && $value === '-99.9') {
        // Not sure, seems as not exist port
        continue;
    }
    discover_sensor_ng($device, $record_montypes[$recordMontype]['class'], $mib, $oid_name, $oid_num, $index, $descr, 1, $entry[$oid_name], $options);
}

// EOF
