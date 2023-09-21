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

/// FIXME. Full rewrite and move to inventory stuff

$hrDevice_oids = ['hrDeviceEntry', 'hrProcessorEntry'];

$hrDevices = [];
foreach ($hrDevice_oids as $oid) {
    $hrDevices = snmpwalk_cache_oid($device, $oid, $hrDevices, "HOST-RESOURCES-MIB:HOST-RESOURCES-TYPES");
}
print_debug_vars($hrDevices);

if (is_array($hrDevices)) {
    foreach ($hrDevices as $hrDevice) {
        if (!is_numeric($hrDevice['hrDeviceIndex'])) {
            print_debug("Empty hrDevice entry skipped:");
            print_debug_vars($hrDevice, 1);
            continue;
        }

        if ($hrDevice['hrDeviceType'] === 'hrDevicePrinter' &&
            $hrDevice['hrDeviceStatus'] === 'unknown' &&
            $hrDevice['hrPrinterStatus'] === 'unknown') {
            print_debug("Broken hrDevice entry skipped:");
            print_debug_vars($hrDevice, 1);
            continue;
        }
        if (!is_numeric($hrDevice['hrDeviceErrors'])) {
            $hrDevice['hrDeviceErrors'] = 0;
        }

        //if (dbFetchCell("SELECT COUNT(*) FROM `hrDevice` WHERE device_id = ? AND hrDeviceIndex = ?",array($device['device_id'], $hrDevice['hrDeviceIndex'])))
        if (dbExist('hrDevice', '`device_id` = ? AND `hrDeviceIndex` = ?', [$device['device_id'], $hrDevice['hrDeviceIndex']])) {
            if (($hrDevice['hrDeviceType'] === "hrDeviceProcessor") && empty($hrDevice['hrDeviceDescr'])) {
                $hrDevice['hrDeviceDescr'] = "Processor";
            }
            $update_array = [
              'hrDeviceType'   => $hrDevice['hrDeviceType'],
              'hrDeviceDescr'  => $hrDevice['hrDeviceDescr'],
              'hrDeviceStatus' => $hrDevice['hrDeviceStatus'],
              'hrDeviceErrors' => $hrDevice['hrDeviceErrors']
            ];

            if ($hrDevice['hrDeviceType'] === "hrDeviceProcessor") {
                $update_array['hrProcessorLoad'] = $hrDevice['hrProcessorLoad'];
            } else {
                $update_array['hrProcessorLoad'] = ['NULL'];
            }
            dbUpdate($update_array, 'hrDevice', 'device_id = ? AND hrDeviceIndex = ?', [$device['device_id'], $hrDevice['hrDeviceIndex']]);
            // FIXME -- check if it has updated, and print a U instead of a .
            echo(".");
        } else {
            $inserted = dbInsert(['hrDeviceIndex' => $hrDevice['hrDeviceIndex'], 'device_id' => $device['device_id'], 'hrDeviceType' => $hrDevice['hrDeviceType'], 'hrDeviceDescr' => $hrDevice['hrDeviceDescr'], 'hrDeviceStatus' => $hrDevice['hrDeviceStatus'], 'hrDeviceErrors' => $hrDevice['hrDeviceErrors']], 'hrDevice');
            echo("+");
        }
        $valid_hrDevice[$hrDevice['hrDeviceIndex']] = 1;
    }
}

foreach (dbFetchRows('SELECT * FROM `hrDevice` WHERE `device_id`  = ?', [$device['device_id']]) as $test_hrDevice) {
    if (!$valid_hrDevice[$test_hrDevice['hrDeviceIndex']]) {
        $deleted = dbDelete('hrDevice', '`hrDevice_id` = ?', [$test_hrDevice['hrDevice_id']]);
        echo("-");
        if (OBS_DEBUG > 1) {
            print_vars($test_hrDevice);
            echo($deleted . " deleted");
        }
    }
}

unset($valid_hrDevice);

// EOF
