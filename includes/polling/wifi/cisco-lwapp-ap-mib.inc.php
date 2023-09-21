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
 *
 * CISCO-LWAPP-AP-MIB
 *
 * Discovery SysObjectID has to start either with 1.3.6.1.4.1.14179. or 1.3.6.1.4.1.9. and SysDescription contains the string "Cisco Controller".
 * This module is meant to work together with AIRESPACE-WIRELESS-MIB; part of the below OIDs are already polled from AIRESPACE-WIRELESS-MIB, therefore,
 * duplicated from this MIB.
 *
 * Controller ciscoLwappAp 1.3.6.1.4.1.9.9.513
 * Controller Dot11gSupport 1.3.6.1.4.1.9.9.513.1.1.1.1.1
 *
 * Thin AP                 Table                      1.3.6.1.4.1.9.9.513.1.1.1
 * enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable
 *               ##poll##  cLApSysMacAddress          1.3.6.1.4.1.9.9.513.1.1.1.1.1
 *               enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApSysMacAddress  [ap_index]
 *               AIRESPACE-WIRELESS-MIB  cLApIfMacAddress           1.3.6.1.4.1.9.9.513.1.1.1.1.2
 *               enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApIfMacAddress AIRESPACE-WIRELESS-MIB
 *               cLApMaxNumberOfDot11Slots  1.3.6.1.4.1.9.9.513.1.1.1.1.3
 *               enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApMaxNumberOfDot11Slots
 *               AIRESPACE-WIRELESS-MIB  cLApName                   1.3.6.1.4.1.9.9.513.1.1.1.1.5
 *               enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApName
 *               ##poll##  cLApUpTime                 1.3.6.1.4.1.9.9.513.1.1.1.1.6
 *               enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApUpTime
 *               ##poll##  cLLwappUpTime              1.3.6.1.4.1.9.9.513.1.1.1.1.7
 *               enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLLwappUpTime
 *               ##poll##  cLLwappJoinTakenTime       1.3.6.1.4.1.9.9.513.1.1.1.1.8
 *               enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLLwappJoinTakenTime
 *               AIRESPACE-WIRELESS-MIB  cLApAdminStatus            1.3.6.1.4.1.9.9.513.1.1.1.1.38
 *               enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApAdminStatus
 *
 */

if (safe_empty($aps_db)) {
    // APs not discovered
    return;
}

//echo('CISCO-LWAPP-AP-MIB' . PHP_EOL);

//Thin AP Get Uptimes
print_cli_data("Collecting", "CISCO-LWAPP-AP-MIB Access Points Uptimes", 3);

// AccessPoints Attributes
$lwapps = snmpwalk_cache_oid($device, 'cLApUpTime', [], 'CISCO-LWAPP-AP-MIB', NULL, OBS_SNMP_ALL_TABLE);                 //ap_uptime
$lwapps = snmpwalk_cache_oid($device, 'cLLwappUpTime', $lwapps, 'CISCO-LWAPP-AP-MIB', NULL, OBS_SNMP_ALL_TABLE);         //ap_control_uptime
$lwapps = snmpwalk_cache_oid($device, 'cLLwappJoinTakenTime', $lwapps, 'CISCO-LWAPP-AP-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_control_latency

//print_vars($lwapps);

foreach ($lwapps as $ap_index => $aps) {
    $index = format_mac(mac_zeropad($ap_index));

    print_debug_vars($aps);

    if (is_array($aps_poll[$index])) {
        //echo("AP Index exists: $index\n");
        $uptime          = uptime_to_seconds($aps['cLApUpTime']);
        $control_uptime  = uptime_to_seconds($aps['cLLwappUpTime']);
        $control_latency = uptime_to_seconds($aps['cLLwappJoinTakenTime']);

        $aps_poll[$index]['ap_uptime']          = $uptime;
        $aps_poll[$index]['ap_control_uptime']  = $control_uptime;
        $aps_poll[$index]['ap_control_latency'] = $control_latency;
    } else {
        print_warning("New AP Index: $index - Need to wait for AIRESPACE-WIRELESS-MIB to add it in the DB");
    }
}

unset($lwapps, $ap_index, $index, $aps);

// EOF
