<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 *
 * CISCO-LWAPP-WLAN-MIB
 *
 * Discovery SysObjectID has to start either with 1.3.6.1.4.1.14179. or 1.3.6.1.4.1.9. and SysDescription contains the string "Cisco Controller".
 * This module is meant to work together with AIRESPACE-WIRELESS-MIB and the CISCO-LWAPP-WLAN-SECURITY-MIB
 *
 * Controller ciscoLwappWlanMIB 1.3.6.1.4.1.9.9.512
 * Controller ciscoLwappWlanConfig 1.3.6.1.4.1.9.9.512.1.1
 *
 *  Controller Radio WLANs                 Table                 1.3.6.1.4.1.9.9.512.1.1.1.1
 *  enterprises.cisco.ciscoMgmt.ciscoLwappWlanMIB.ciscoLwappWlanMIBObjects.ciscoLwappWlanConfig.cLWlanConfigTable wlan_index
 *  cLWlanIndex
 */

echo('CISCO-LWAPP-WLAN-MIB' . PHP_EOL);

//Thin AP Get Uptimes
print_cli_data("Collecting", "CISCO-LWAPP-WLAN-MIB WLANs", 3);

$table_rows    = [];
$table_headers = ['%WWLAN Index%n', '%WWLAN Profile Name%n', '%WWLAN SSID%n', '%WWLAN Status%n'];

$WLAN_db = dbFetchRows('SELECT * FROM `wifi_radios` WHERE `device_id` = ?', [$device['device_id']]);
foreach ($WLAN_db as $wlans) {
    $WLANs_db[$wlans['radio_number']]    = $wlans;
    $WLANs_exist[$wlans['radio_number']] = $wlans['wifi_radio_id'];
}

// Radio WLAN Attributes
$radwlans = snmpwalk_cache_oid($device, 'cLWlanIndex', [], 'CISCO-LWAPP-WLAN-MIB', NULL, OBS_SNMP_ALL_TABLE);                   //radio_number, wlan_index
$radwlans = snmpwalk_cache_oid($device, 'cLWlanRowStatus', $radwlans, 'CISCO-LWAPP-WLAN-MIB', NULL, OBS_SNMP_ALL_TABLE);        //radio_status, wlan_admin_status
$radwlans = snmpwalk_cache_oid($device, 'cLWlanProfileName', $radwlans, 'CISCO-LWAPP-WLAN-MIB', NULL, OBS_SNMP_ALL_TABLE);      //wlan_name
$radwlans = snmpwalk_cache_oid($device, 'cLWlanSsid', $radwlans, 'CISCO-LWAPP-WLAN-MIB', NULL, OBS_SNMP_ALL_TABLE);             //wlan_ssid
$radwlans = snmpwalk_cache_oid($device, 'cLWlanChdEnable', $radwlans, 'CISCO-LWAPP-WLAN-MIB', NULL, OBS_SNMP_ALL_TABLE);        //wlan_ssid_bcast
$radwlans = snmpwalk_cache_oid($device, 'cLWlan802dot11anDTIM', $radwlans, 'CISCO-LWAPP-WLAN-MIB', NULL, OBS_SNMP_ALL_TABLE);   //wlan_dtim_period
$radwlans = snmpwalk_cache_oid($device, 'cLWlan802dot11bgnDTIM', $radwlans, 'CISCO-LWAPP-WLAN-MIB', NULL, OBS_SNMP_ALL_TABLE);  //wlan_dtim_period
$radwlans = snmpwalk_cache_oid($device, 'cLWlanLanSubType', $radwlans, 'CISCO-LWAPP-WLAN-MIB', NULL, OBS_SNMP_ALL_TABLE);  //radio_type


//print_vars($radwlans);


// EOF
