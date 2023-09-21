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

// Here only discovery APs for enable polling

//$lwapps = snmpwalk_cache_oid($device, 'bsnAPDot3MacAddress',         [], 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_index
$lwapps = snmpwalk_cache_oid($device, 'bsnAPName', [], 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_name
if (safe_count($lwapps)) {
    $lwapps = snmpwalk_cache_oid($device, 'bsnAPNumOfSlots', $lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);       //ap_number
    $lwapps = snmpwalk_cache_oid($device, 'bsnApIpAddress', $lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);        //ap_address
    $lwapps = snmpwalk_cache_oid($device, 'bsnAPSerialNumber', $lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);     //ap_serial
    $lwapps = snmpwalk_cache_oid($device, 'bsnAPModel', $lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);            //ap_model
    $lwapps = snmpwalk_cache_oid($device, 'bsnAPLocation', $lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);         //ap_location
    $lwapps = snmpwalk_cache_oid($device, 'bsnAPAdminStatus', $lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);      //ap_admin_status
    $lwapps = snmpwalk_cache_oid($device, 'bsnAPOperationStatus', $lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_status
}

$table_rows = [];
foreach ($lwapps as $ap_index => $aps) {
    $index = format_mac(mac_zeropad($ap_index));

    print_debug_vars($aps);

    if ($aps['bsnAPAdminStatus'] === 'enable') {
        switch ($aps['bsnAPOperationStatus']) {
            case 'associated': // AP is Up and running on the WLC.
                $apstatus = "up";
                break;
            case 'disassociating': //AP is Unreachable and will be removed from WLC.
                $apstatus = "down";
                break;
            case 'downloading': //AP is now being Added to WLC.
                $apstatus = "init";
                break;
        }
    } else {
        $apstatus = "shutdown";
    }

    $ap_insert = [
        //'device_id'       => $device['device_id'],
        'ap_mib'          => $mib,
        'ap_index'        => $index,
        'ap_number'       => $aps['bsnAPNumOfSlots'],
        'ap_name'         => $aps['bsnAPName'],
        'ap_address'      => $aps['bsnApIpAddress'],
        'ap_serial'       => $aps['bsnAPSerialNumber'],
        'ap_model'        => $aps['bsnAPModel'],
        'ap_location'     => $aps['bsnAPLocation'],
        'ap_status'       => $apstatus,
        'ap_admin_status' => $aps['bsnAPAdminStatus'],
    ];
    discover_wifi_ap($device, $ap_insert);

    $table_row    = [];
    $table_row[]  = $index;
    $table_row[]  = $aps['bsnAPName'];
    $table_row[]  = $aps['bsnApIpAddress'];
    $table_row[]  = $aps['bsnAPSerialNumber'];
    $table_row[]  = $aps['bsnAPModel'];
    $table_row[]  = $aps['bsnAPLocation'];
    $table_row[]  = $aps['bsnAPOperationStatus'];
    $table_row[]  = $aps['bsnAPAdminStatus'];
    $table_rows[] = $table_row;
}

$table_headers = ['%WAP MacAddress%n', '%WAP Name%n', '%WAP Address%n', '%WAP Serial%n', '%WAP Model%n', '%WAP Location%n', '%WAP OperStatus%n', '%WAP AdminStatus'];
print_cli_table($table_rows, $table_headers);
unset($table_rows, $table_headers, $table_row, $lwapps, $aps);

// EOF
