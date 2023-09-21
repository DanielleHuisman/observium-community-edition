<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$wmi_found = FALSE;
if (isset($wmi['disk']['logical']) && safe_count($wmi['disk']['logical'])) {
    echo(" Storage WMI: ");

    //print_debug_vars($storage);
    //print_debug_vars($wmi['disk']['logical']);
    /*
     * storage_descr => "C:\\ Label:  Serial Number d0122308"
     *
     * DeviceID => "C:"
     * Name => "C:"
     * VolumeName => ""
     * VolumeSerialNumber => "D0122308"
     */
    foreach ($wmi['disk']['logical'] as $disk) {
        //$storage_name = $disk['DeviceID'] . "\\\\ Label:" . $disk['VolumeName'] . "  Serial Number " . ltrim(strtolower($disk['VolumeSerialNumber']), '0');
        $disk_name   = $disk['DeviceID'] . '\\\\';
        $disk_label  = 'Label:' . $disk['VolumeName'];
        $disk_serial = 'Serial Number ' . ltrim(strtolower($disk['VolumeSerialNumber']), '0');
        $wmi_found   = str_starts($storage['storage_descr'], $disk_name) &&
                       str_contains_array($storage['storage_descr'], $disk_label) &&
                       str_ends($storage['storage_descr'], $disk_serial);
        if ($wmi_found) {
            // Found
            //$storage['units'] = 1;
            $storage['free'] = $disk['FreeSpace'];
            $storage['used'] = $disk['Size'] - $disk['FreeSpace'];
            $storage['size'] = $disk['Size'];
            break;
        }
    }
}

if (!$wmi_found) {

    $hrStorage = snmp_cache_table($device, "hrStorageEntry", [], "HOST-RESOURCES-MIB:HOST-RESOURCES-TYPES");

    $entry = $hrStorage[$storage['storage_index']];

    $storage['units'] = $entry['hrStorageAllocationUnits'];
    $storage['used']  = snmp_dewrap32bit($entry['hrStorageUsed']) * $storage['units'];
    $storage['size']  = snmp_dewrap32bit($entry['hrStorageSize']) * $storage['units'];

    $storage['free'] = $storage['size'] - $storage['used'];
}

// EOF
