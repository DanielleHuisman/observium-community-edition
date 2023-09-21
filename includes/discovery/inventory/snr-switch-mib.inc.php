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

echo("SNR-SWITCH-MIB ");

$ports_array = snmpwalk_cache_oid($device, "priPortTable", [], 'SNR-SWITCH-MIB', NULL, OBS_SNMP_ALL_MULTILINE);
print_debug_vars($ports_array);

if (safe_count($ports_array)) {
    //$portCount   = snmp_get_oid($device, 'portCount.0', 'SNR-SWITCH-MIB');
    $ddminfo_array = snmpwalk_cache_oid($device, "ddmTranscBasicInfoTable", [], 'SNR-SWITCH-MIB');
    print_debug_vars($ddminfo_array);

    foreach ($ports_array as $index => $entry) {

        $local_index             = 100 + $entry['portIndex'];
        $inventory[$local_index] = [
          'entPhysicalDescr'        => $entry['portType'] . " " . $ddminfo_array[$entry['portIndex']]['ddmTransSerialWaveLength'],
          'entPhysicalClass'        => 'port',
          'entPhysicalName'         => $entry['portName'],
          'entPhysicalIsFRU'        => 'false',
          'entPhysicalModelName'    => $ddminfo_array[$entry['portIndex']]['ddmTransSerialModelName'],
          'entPhysicalVendorType'   => $ddminfo_array[$entry['portIndex']]['ddmTransSerialTypeName'],
          'entPhysicalContainedIn'  => 1,
          'entPhysicalParentRelPos' => $entry['portIndex'],
          'entPhysicalSerialNum'    => $entry['transceiverSn'],
          'ifIndex'                 => $entry['portIndex'],
          'entPhysicalMfgName'      => $ddminfo_array[$entry['portIndex']]['ddmTransSerialVendorName'],
        ];
        discover_inventory($device, $local_index, $inventory[$local_index], 'SNR-SWITCH-MIB');
    }

}
print_debug_vars($inventory);

// EOF
