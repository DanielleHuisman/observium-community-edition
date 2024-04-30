<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Like sensors/status definitions, but not possible convert to definitions
$sensors_def = [];
$inputs_def  = [];
$outputs_def = [];

//ServersCheck::sensor1name.0 = STRING: "Temp env01"
//ServersCheck::sensor1Value.0 = STRING: "18.28"
//ServersCheck::sensor1LastErrMsg.0 = STRING: "WARNING"
//ServersCheck::sensor1LastErrTime.0 = STRING: "23 May 2017,21:02:27"
//ServersCheck::sensor2name.0 = STRING: "Ping"
//ServersCheck::sensor2Value.0 = STRING: "1.00"
//ServersCheck::sensor2LastErrMsg.0 = STRING: "-"
//ServersCheck::sensor2LastErrTime.0 = STRING: "-"

$sensors_def[] = ['oid' => 'sensor1Value', 'oid_descr' => 'sensor1name', 'oid_num' => '.1.3.6.1.4.1.17095.3.2', 'rename_rrd' => 'serverscheck_sensor-1'];
$sensors_def[] = ['oid' => 'sensor2Value', 'oid_descr' => 'sensor2name', 'oid_num' => '.1.3.6.1.4.1.17095.3.6', 'rename_rrd' => 'serverscheck_sensor-2'];
$sensors_def[] = ['oid' => 'sensor3Value', 'oid_descr' => 'sensor3name', 'oid_num' => '.1.3.6.1.4.1.17095.3.10', 'rename_rrd' => 'serverscheck_sensor-3'];
$sensors_def[] = ['oid' => 'sensor4Value', 'oid_descr' => 'sensor4name', 'oid_num' => '.1.3.6.1.4.1.17095.3.14', 'rename_rrd' => 'serverscheck_sensor-4'];
$sensors_def[] = ['oid' => 'sensor5Value', 'oid_descr' => 'sensor5name', 'oid_num' => '.1.3.6.1.4.1.17095.3.18', 'rename_rrd' => 'serverscheck_sensor-5'];
$sensors_def[] = ['oid' => 'sensor6Value', 'oid_descr' => 'sensor6name', 'oid_num' => '.1.3.6.1.4.1.17095.3.22'];
$sensors_def[] = ['oid' => 'sensor7Value', 'oid_descr' => 'sensor7name', 'oid_num' => '.1.3.6.1.4.1.17095.3.26'];
$sensors_def[] = ['oid' => 'sensor8Value', 'oid_descr' => 'sensor8name', 'oid_num' => '.1.3.6.1.4.1.17095.3.30'];

// ServersCheck::sensor1Name.0 = STRING: "NCCS Power"
// ServersCheck::sensor1value.0 = STRING: "PWR OK"
// ServersCheck::sensor1ErrState.0 = STRING: "-"
// ServersCheck::sensor1lastErrTime.0 = STRING: "-"
// ServersCheck::sensor1lastErrMsg.0 = STRING: "-"
// ServersCheck::sensor4Name.0 = STRING: "NCCS Temperature"
// ServersCheck::sensor4value.0 = STRING: "63.05"
// ServersCheck::sensor4ErrState.0 = STRING: "-"
// ServersCheck::sensor4lastErrTime.0 = STRING: "-"
// ServersCheck::sensor4lastErrMsg.0 = STRING: "-"
// ServersCheck::sensor5Name.0 = STRING: "NCCS Humidity"
// ServersCheck::sensor5value.0 = STRING: "39.71"
// ServersCheck::sensor5ErrState.0 = STRING: "-"
// ServersCheck::sensor5lastErrTime.0 = STRING: "-"
// ServersCheck::sensor5lastErrMsg.0 = STRING: "-"
// ServersCheck::sensor16Name.0 = STRING: "HPDC Power"
// ServersCheck::sensor16value.0 = STRING: "PWR FAIL"
// ServersCheck::sensor16ErrState.0 = STRING: "DOWN"
// ServersCheck::sensor16lastErrTime.0 = STRING: "04 December 2019,14:23:44"
// ServersCheck::sensor16lastErrMsg.0 = STRING: "HPDC Power,PWR FAIL,DOWN,04 December 2019,14:23:44"

$sensors_def[] = ['oid' => 'sensor1value', 'oid_descr' => 'sensor1Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.1.2'];
$sensors_def[] = ['oid' => 'sensor2value', 'oid_descr' => 'sensor2Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.2.2'];
$sensors_def[] = ['oid' => 'sensor3value', 'oid_descr' => 'sensor3Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.3.2'];
$sensors_def[] = ['oid' => 'sensor4value', 'oid_descr' => 'sensor4Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.4.2'];
$sensors_def[] = ['oid' => 'sensor5value', 'oid_descr' => 'sensor5Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.5.2'];
$sensors_def[] = ['oid' => 'sensor6value', 'oid_descr' => 'sensor6Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.6.2'];
$sensors_def[] = ['oid' => 'sensor7value', 'oid_descr' => 'sensor7Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.7.2'];
$sensors_def[] = ['oid' => 'sensor8value', 'oid_descr' => 'sensor8Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.8.2'];
$sensors_def[] = ['oid' => 'sensor9value', 'oid_descr' => 'sensor9Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.9.2'];
$sensors_def[] = ['oid' => 'sensor10value', 'oid_descr' => 'sensor10Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.10.2'];
$sensors_def[] = ['oid' => 'sensor11value', 'oid_descr' => 'sensor11Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.11.2'];
$sensors_def[] = ['oid' => 'sensor12value', 'oid_descr' => 'sensor12Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.12.2'];
$sensors_def[] = ['oid' => 'sensor13value', 'oid_descr' => 'sensor13Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.13.2'];
$sensors_def[] = ['oid' => 'sensor14value', 'oid_descr' => 'sensor14Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.14.2'];
$sensors_def[] = ['oid' => 'sensor15value', 'oid_descr' => 'sensor15Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.15.2'];
$sensors_def[] = ['oid' => 'sensor16value', 'oid_descr' => 'sensor16Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.16.2'];
$sensors_def[] = ['oid' => 'sensor17value', 'oid_descr' => 'sensor17Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.17.2'];
$sensors_def[] = ['oid' => 'sensor18value', 'oid_descr' => 'sensor18Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.18.2'];
$sensors_def[] = ['oid' => 'sensor19value', 'oid_descr' => 'sensor19Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.19.2'];
$sensors_def[] = ['oid' => 'sensor20value', 'oid_descr' => 'sensor20Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.20.2'];
$sensors_def[] = ['oid' => 'sensor21value', 'oid_descr' => 'sensor21Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.21.2'];
$sensors_def[] = ['oid' => 'sensor22value', 'oid_descr' => 'sensor22Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.22.2'];
$sensors_def[] = ['oid' => 'sensor23value', 'oid_descr' => 'sensor23Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.23.2'];
$sensors_def[] = ['oid' => 'sensor24value', 'oid_descr' => 'sensor24Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.24.2'];
$sensors_def[] = ['oid' => 'sensor25value', 'oid_descr' => 'sensor25Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.25.2'];
$sensors_def[] = ['oid' => 'sensor26value', 'oid_descr' => 'sensor26Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.26.2'];
$sensors_def[] = ['oid' => 'sensor27value', 'oid_descr' => 'sensor27Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.27.2'];
$sensors_def[] = ['oid' => 'sensor28value', 'oid_descr' => 'sensor28Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.28.2'];
$sensors_def[] = ['oid' => 'sensor29value', 'oid_descr' => 'sensor29Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.29.2'];
$sensors_def[] = ['oid' => 'sensor30value', 'oid_descr' => 'sensor30Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.30.2'];
$sensors_def[] = ['oid' => 'sensor31value', 'oid_descr' => 'sensor31Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.31.2'];
$sensors_def[] = ['oid' => 'sensor32value', 'oid_descr' => 'sensor32Name', 'oid_num' => '.1.3.6.1.4.1.17095.11.32.2'];

// daisySensorTable (nottested)

$sensors_def[] = ['oid' => 'daisySensor1value', 'oid_descr' => 'daisySensor1Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.1.2'];
$sensors_def[] = ['oid' => 'daisySensor2value', 'oid_descr' => 'daisySensor2Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.2.2'];
$sensors_def[] = ['oid' => 'daisySensor3value', 'oid_descr' => 'daisySensor3Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.3.2'];
$sensors_def[] = ['oid' => 'daisySensor4value', 'oid_descr' => 'daisySensor4Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.4.2'];
$sensors_def[] = ['oid' => 'daisySensor5value', 'oid_descr' => 'daisySensor5Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.5.2'];
$sensors_def[] = ['oid' => 'daisySensor6value', 'oid_descr' => 'daisySensor6Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.6.2'];
$sensors_def[] = ['oid' => 'daisySensor7value', 'oid_descr' => 'daisySensor7Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.7.2'];
$sensors_def[] = ['oid' => 'daisySensor8value', 'oid_descr' => 'daisySensor8Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.8.2'];
$sensors_def[] = ['oid' => 'daisySensor9value', 'oid_descr' => 'daisySensor9Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.9.2'];
$sensors_def[] = ['oid' => 'daisySensor10value', 'oid_descr' => 'daisySensor10Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.10.2'];
$sensors_def[] = ['oid' => 'daisySensor11value', 'oid_descr' => 'daisySensor11Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.11.2'];
$sensors_def[] = ['oid' => 'daisySensor12value', 'oid_descr' => 'daisySensor12Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.12.2'];
$sensors_def[] = ['oid' => 'daisySensor13value', 'oid_descr' => 'daisySensor13Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.13.2'];
$sensors_def[] = ['oid' => 'daisySensor14value', 'oid_descr' => 'daisySensor14Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.14.2'];
$sensors_def[] = ['oid' => 'daisySensor15value', 'oid_descr' => 'daisySensor15Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.15.2'];
$sensors_def[] = ['oid' => 'daisySensor16value', 'oid_descr' => 'daisySensor16Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.16.2'];
$sensors_def[] = ['oid' => 'daisySensor17value', 'oid_descr' => 'daisySensor17Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.17.2'];
$sensors_def[] = ['oid' => 'daisySensor18value', 'oid_descr' => 'daisySensor18Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.18.2'];
$sensors_def[] = ['oid' => 'daisySensor19value', 'oid_descr' => 'daisySensor19Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.19.2'];
$sensors_def[] = ['oid' => 'daisySensor20value', 'oid_descr' => 'daisySensor20Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.20.2'];
$sensors_def[] = ['oid' => 'daisySensor21value', 'oid_descr' => 'daisySensor21Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.21.2'];
$sensors_def[] = ['oid' => 'daisySensor22value', 'oid_descr' => 'daisySensor22Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.22.2'];
$sensors_def[] = ['oid' => 'daisySensor23value', 'oid_descr' => 'daisySensor23Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.23.2'];
$sensors_def[] = ['oid' => 'daisySensor24value', 'oid_descr' => 'daisySensor24Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.24.2'];
$sensors_def[] = ['oid' => 'daisySensor25value', 'oid_descr' => 'daisySensor25Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.25.2'];
$sensors_def[] = ['oid' => 'daisySensor26value', 'oid_descr' => 'daisySensor26Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.26.2'];
$sensors_def[] = ['oid' => 'daisySensor27value', 'oid_descr' => 'daisySensor27Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.27.2'];
$sensors_def[] = ['oid' => 'daisySensor28value', 'oid_descr' => 'daisySensor28Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.28.2'];
$sensors_def[] = ['oid' => 'daisySensor29value', 'oid_descr' => 'daisySensor29Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.29.2'];
$sensors_def[] = ['oid' => 'daisySensor30value', 'oid_descr' => 'daisySensor30Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.30.2'];
$sensors_def[] = ['oid' => 'daisySensor31value', 'oid_descr' => 'daisySensor31Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.31.2'];
$sensors_def[] = ['oid' => 'daisySensor32value', 'oid_descr' => 'daisySensor32Name', 'oid_num' => '.1.3.6.1.4.1.17095.13.32.2'];

$index     = '0';
$dot_index = '.0';
foreach ($sensors_def as $entry) {
    $oid     = $entry['oid'] . $dot_index;
    $oid_num = $entry['oid_num'] . $dot_index;
    $value   = snmp_get_oid($device, $oid, 'ServersCheck');

    if ($value == '-') {
        continue;
    }

    // Sensor description
    $descr = snmp_get_oid($device, $entry['oid_descr'] . $dot_index, 'ServersCheck');
    if ($descr == "-" || str_contains($descr, 'Ping')) {
        continue;
    }

    if (is_numeric($value)) {
        // Class based on descr
        if (str_contains($descr, "Temp")) {
            $class = "temperature";
        } elseif (str_contains($descr, "Humidity")) {
            $class = "humidity";
        } elseif (str_contains($descr, "Dew Point")) {
            $class = "dewpoint";
        } elseif (str_contains($descr, "Volt")) {
            $class = "voltage";
        } elseif (str_contains($descr, "Airflow")) {
            $class = "airflow";
        } elseif (str_contains($descr, "Dust")) {
            $class = "dust";
        } elseif (str_contains($descr, "Sound")) {
            $class = "sound";
        } else {
            $class = "temperature";
        }

        $options = [];
        // If the global setting is set telling us all of our serverscheck devices are F, set the unit as F.
        if (in_array($class, ["temperature", "dewpoint"]) && $config['devices']['serverscheck']['temp_f']) {
            $options['sensor_unit'] = 'F';
        } elseif ($type == "airflow") {
            $options['sensor_unit'] = 'CFM';
        }

        if (isset($entry['rename_rrd'])) {
            $options['rename_rrd'] = $entry['rename_rrd'];
        }

        discover_sensor_ng($device, $class, $mib, $entry['oid'], $oid_num, $index, NULL, $descr, 1, $value, $options);

    } else {
        discover_status_ng($device, $mib, $entry['oid'], $oid_num, $index, 'serverscheck-status', $descr, $value, ['entPhysicalClass' => 'other']);
    }
}

// ServersCheck::input1name.0 = STRING: "UndefineIO 1"
// ServersCheck::input1Value.0 = STRING: "Triggered"
// ServersCheck::input1LastErrMsg.0 = STRING: "Triggered,01 January 2012,00:26:44"
// ServersCheck::input2name.0 = STRING: "UndefineIO 2"
// ServersCheck::input2Value.0 = STRING: "OK"
// ServersCheck::input2LastErrMsg.0 = STRING: "-"

$inputs_def[] = ['oid' => 'input1Value', 'oid_descr' => 'input1name', 'oid_num' => '.1.3.6.1.4.1.17095.6.2', 'rename_rrd' => 'serverscheck-input-1'];
$inputs_def[] = ['oid' => 'input2Value', 'oid_descr' => 'input2name', 'oid_num' => '.1.3.6.1.4.1.17095.6.5', 'rename_rrd' => 'serverscheck-input-2'];
$inputs_def[] = ['oid' => 'input3Value', 'oid_descr' => 'input3name', 'oid_num' => '.1.3.6.1.4.1.17095.6.8', 'rename_rrd' => 'serverscheck-input-3'];
$inputs_def[] = ['oid' => 'input4Value', 'oid_descr' => 'input4name', 'oid_num' => '.1.3.6.1.4.1.17095.6.11', 'rename_rrd' => 'serverscheck-input-4'];
$inputs_def[] = ['oid' => 'input5Value', 'oid_descr' => 'input5name', 'oid_num' => '.1.3.6.1.4.1.17095.6.14', 'rename_rrd' => 'serverscheck-input-5'];
$inputs_def[] = ['oid' => 'input6Value', 'oid_descr' => 'input6name', 'oid_num' => '.1.3.6.1.4.1.17095.6.17', 'rename_rrd' => 'serverscheck-input-6'];
$inputs_def[] = ['oid' => 'input7Value', 'oid_descr' => 'input7name', 'oid_num' => '.1.3.6.1.4.1.17095.6.20', 'rename_rrd' => 'serverscheck-input-7'];
$inputs_def[] = ['oid' => 'input8Value', 'oid_descr' => 'input8name', 'oid_num' => '.1.3.6.1.4.1.17095.6.23', 'rename_rrd' => 'serverscheck-input-8'];
$inputs_def[] = ['oid' => 'input9Value', 'oid_descr' => 'input9name', 'oid_num' => '.1.3.6.1.4.1.17095.6.26', 'rename_rrd' => 'serverscheck-input-9'];
$inputs_def[] = ['oid' => 'input10Value', 'oid_descr' => 'input10name', 'oid_num' => '.1.3.6.1.4.1.17095.6.29', 'rename_rrd' => 'serverscheck-input-10'];
$inputs_def[] = ['oid' => 'input11Value', 'oid_descr' => 'input11name', 'oid_num' => '.1.3.6.1.4.1.17095.6.32', 'rename_rrd' => 'serverscheck-input-11'];
$inputs_def[] = ['oid' => 'input12Value', 'oid_descr' => 'input12name', 'oid_num' => '.1.3.6.1.4.1.17095.6.35', 'rename_rrd' => 'serverscheck-input-12'];
$inputs_def[] = ['oid' => 'input13Value', 'oid_descr' => 'input13name', 'oid_num' => '.1.3.6.1.4.1.17095.6.38', 'rename_rrd' => 'serverscheck-input-13'];
$inputs_def[] = ['oid' => 'input14Value', 'oid_descr' => 'input14name', 'oid_num' => '.1.3.6.1.4.1.17095.6.41', 'rename_rrd' => 'serverscheck-input-14'];
$inputs_def[] = ['oid' => 'input15Value', 'oid_descr' => 'input15name', 'oid_num' => '.1.3.6.1.4.1.17095.6.44', 'rename_rrd' => 'serverscheck-input-15'];
$inputs_def[] = ['oid' => 'input16Value', 'oid_descr' => 'input16name', 'oid_num' => '.1.3.6.1.4.1.17095.6.47', 'rename_rrd' => 'serverscheck-input-16'];

$index     = '0';
$dot_index = '.0';
foreach ($inputs_def as $entry) {
    $oid     = $entry['oid'] . $dot_index;
    $oid_num = $entry['oid_num'] . $dot_index;
    $value   = snmp_get_oid($device, $oid, 'ServersCheck');

    if ($value != '-') {
        // Sensor description
        $descr = snmp_get_oid($device, $entry['oid_descr'] . $dot_index, 'ServersCheck');
        if ($descr == "-") {
            continue;
        }

        discover_status_ng($device, $mib, $entry['oid'], $oid_num, $index, 'serverscheck-input', $descr, $value, ['entPhysicalClass' => 'input']);
    }
}

// ServersCheck::output1State.0 = Wrong Type (should be OCTET STRING): INTEGER: 0
// ServersCheck::output2State.0 = Wrong Type (should be OCTET STRING): INTEGER: 0
// ServersCheck::output3State.0 = Wrong Type (should be OCTET STRING): INTEGER: 0
// ServersCheck::output4State.0 = Wrong Type (should be OCTET STRING): INTEGER: 0

$outputs_def[] = ['oid' => 'output1State', 'descr' => 'Output 1', 'oid_num' => '.1.3.6.1.4.1.17095.7.1'];
$outputs_def[] = ['oid' => 'output2State', 'descr' => 'Output 2', 'oid_num' => '.1.3.6.1.4.1.17095.7.2'];
$outputs_def[] = ['oid' => 'output3State', 'descr' => 'Output 3', 'oid_num' => '.1.3.6.1.4.1.17095.7.3'];
$outputs_def[] = ['oid' => 'output4State', 'descr' => 'Output 4', 'oid_num' => '.1.3.6.1.4.1.17095.7.4'];

$index     = '0';
$dot_index = '.0';
foreach ($outputs_def as $entry) {
    $oid     = $entry['oid'] . $dot_index;
    $oid_num = $entry['oid_num'] . $dot_index;
    $value   = snmp_get_oid($device, $oid, 'ServersCheck');

    if ($value != '-') {
        // FIXME. Currently skipped
        //discover_status_ng($device, $mib, $entry['oid'], $oid_num, $index, 'serverscheck-output', $entry['oid_descr'], $value, array('entPhysicalClass' => 'output'));
    }
}

unset($sensors_def, $inputs_def, $outputs_def);

// EOF