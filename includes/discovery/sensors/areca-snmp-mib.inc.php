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

// This is the SATA MIB.
$oids = snmp_walk($device, ".1.3.6.1.4.1.18928.1.1.2.14.1.2", "-Osqn", "");
print_debug($oids);
$oids = trim($oids);
if ($oids) {
    echo("Areca Harddisk ");
}
foreach (explode("\n", $oids) as $data) {
    $data = trim($data);
    if ($data) {
        [$oid, $descr] = explode(" ", $data, 2);
        $split_oid       = explode('.', $oid);
        $temperature_id  = $split_oid[count($split_oid) - 1];
        $temperature_oid = ".1.3.6.1.4.1.18928.1.1.2.14.1.2.$temperature_id";
        $temperature     = snmp_get($device, $temperature_oid, "-Oqv", "");
        $descr           = "Hard disk $temperature_id";
        if ($temperature != -128) # -128 = not measured/present
        {
            discover_sensor_ng($device, 'temperature', $mib, '', $temperature_oid, zeropad($temperature_id), 'areca', $descr, 1, $temperature);
        }
    }
}

// SAS enclosure sensors

// hwEnclosure02Installed.0 = 2
// hwEnclosure02Description.0 = "Areca   ARC-4036-.01.06.0106"
// hwEnclosure02NumberOfPower.0 = 2
// hwEnclosure02NumberOfVol.0 = 2
// hwEnclosure02NumberOfFan.0 = 2
// hwEnclosure02NumberOfTemp.0 = 2
// hwEnclosure02VolIndex.1 = 1
// hwEnclosure02VolDesc.1 = "1V    "
// hwEnclosure02VolValue.1 = 980
// hwEnclosure02FanIndex.1 = 1
// hwEnclosure02FanDesc.1 = "Fan 01"
// hwEnclosure02FanSpeed.1 = 2170
// hwEnclosure02TempIndex.1 = 1
// hwEnclosure02TempDesc.1 = "ENC. Temp  "
// hwEnclosure02TempValue.1 = 30
// hwEnclosure02PowerIndex.1 = INTEGER: 1
// hwEnclosure02PowerDesc.1 = STRING: "PowerSupply01"
// hwEnclosure02PowerState.1 = INTEGER: Ok(1)

for ($encNum = 1; $encNum <= 8; $encNum++) {
    $table      = "hwEnclosure$encNum";
    $enclosures = snmpwalk_cache_oid($device, $table, [], "ARECA-SNMP-MIB");
    if (!isset($enclosures[0]) || !$enclosures[0]["hwEnclosure0{$encNum}Installed"]) {
        // Index 0 is the main enclosure data, we check if the enclosure is connected, but it will
        // not have any sensors of its own, so we skip index 0.
        continue;
    }
    $enclosure = $enclosures[0];
    unset($enclosures[0]);
    $name = $enclosure["hwEnclosure0{$encNum}Description"];

    foreach ($enclosures as $index => $entry) {
        if ($entry["hwEnclosure0{$encNum}VolIndex"]) {
            $descr    = $name . ' (' . $encNum . ') ' . $entry["hwEnclosure0{$encNum}VolDesc"];
            $oid_num  = ".1.3.6.1.4.1.18928.1.2.2." . ($encNum + 1) . ".8.1.3.$index";
            $oid_name = "hwEnclosure0{$encNum}VolValue";
            $value    = $entry[$oid_name];

            //discover_sensor('voltage', $device, $oid_num, "$oid_name.$index", 'areca', $descr, 0.001, $value);
            $options = ['rename_rrd' => "areca-$oid_name.%index%"];
            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }

        if ($entry["hwEnclosure0{$encNum}FanIndex"]) {
            $descr    = $name . ' (' . $encNum . ') ' . $entry["hwEnclosure0{$encNum}FanDesc"];
            $oid_num  = ".1.3.6.1.4.1.18928.1.2.2." . ($encNum + 1) . ".9.1.3.$index";
            $oid_name = "hwEnclosure0{$encNum}FanSpeed";
            $value    = $entry[$oid_name];

            //discover_sensor('fanspeed', $device, $oid_num, "$oid_name.$index", 'areca', $descr, 1, $value);
            $options = ['rename_rrd' => "areca-$oid_name.%index%"];
            discover_sensor_ng($device, 'fanspeed', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }

        if ($entry["hwEnclosure0{$encNum}TempIndex"]) {
            $descr    = $name . ' (' . $encNum . ') ' . $entry["hwEnclosure0{$encNum}TempDesc"];
            $oid_num  = ".1.3.6.1.4.1.18928.1.2.2." . ($encNum + 1) . ".10.1.3.$index";
            $oid_name = "hwEnclosure0{$encNum}TempValue";
            $value    = $entry[$oid_name];

            //discover_sensor('temperature', $device, $oid_num, "$oid_name.$index", 'areca', $descr, 1, $value);
            $options = ['rename_rrd' => "areca-$oid_name.%index%"];
            discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }

        if ($entry["hwEnclosure0{$encNum}PowerIndex"]) {
            $descr    = $name . ' (' . $encNum . ') ' . $entry["hwEnclosure0{$encNum}PowerDesc"];
            $oid_num  = ".1.3.6.1.4.1.18928.1.2.2." . ($encNum + 1) . ".7.1.3.$index";
            $oid_name = "hwEnclosure0{$encNum}PowerState";
            $value    = $entry[$oid_name];

            //discover_status($device, $oid, "hwEnclosure0{$encNum}PowerState.$index", 'areca-power-state', $descr, $value, array('entPhysicalClass' => 'power'));
            discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'areca-power-state', $descr, $value, ['entPhysicalClass' => 'power']);
        }
    }
}

// SAS HDD enclosure statuses

for ($encNum = 1; $encNum <= 8; $encNum++) {
    $table      = "hddEnclosure$encNum";
    $enclosures = snmpwalk_cache_oid($device, $table, [], "ARECA-SNMP-MIB");
    if (!isset($enclosures[0]) || !$enclosures[0]["hddEnclosure0{$encNum}Installed"]) {
        // Index 0 is the main enclosure data, we check if the enclosure is connected, but it will
        // not have any sensors of its own, so we skip index 0.
        continue;
    }
    $enclosure = $enclosures[0];
    unset($enclosures[0]);
    $name = $enclosure["hddEnclosure0{$encNum}Description"];

    foreach ($enclosures as $index => $entry) {
        if ($entry["hddEnclosure0{$encNum}Name"] === 'N.A.') {
            continue;
        }

        $descr    = 'Slot ' . $entry["hddEnclosure0{$encNum}Slots"] . ', ' . trim($entry["hddEnclosure0{$encNum}Name"]) . ' (SN: ' . trim($entry["hddEnclosure0{$encNum}Serial"]) . ", $name)";
        $oid_num  = ".1.3.6.1.4.1.18928.1.2.3." . $encNum . ".4.1.8.$index";
        $oid_name = "hddEnclosure0{$encNum}State";
        $value    = $entry[$oid_name];

        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'areca-hdd-state', $descr, $value, ['entPhysicalClass' => 'storage']);
    }
}

// EOF
