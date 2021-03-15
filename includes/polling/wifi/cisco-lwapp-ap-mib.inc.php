<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 *
 * CISCO-LWAPP-AP-MIB
 *
 * Discovery SysObjectID has to start either with 1.3.6.1.4.1.14179. or 1.3.6.1.4.1.9. and SysDescription contains the string "Cisco Controller".
 * This module is meant to work together with AIRESPACE-WIRELESS-MIB; part of the below OIDs are already polled from AIRESPACE-WIRELESS-MIB, therefore, duplicated from this MIB.
 *
 * Controller ciscoLwappAp 1.3.6.1.4.1.9.9.513
 * Controller Dot11gSupport 1.3.6.1.4.1.9.9.513.1.1.1.1.1
 *
 * Thin AP                 Table                      1.3.6.1.4.1.9.9.513.1.1.1         enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable
 *               ##poll##  cLApSysMacAddress          1.3.6.1.4.1.9.9.513.1.1.1.1.1     enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApSysMacAddress  [ap_index]
 * AIRESPACE-WIRELESS-MIB  cLApIfMacAddress           1.3.6.1.4.1.9.9.513.1.1.1.1.2     enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApIfMacAddress
 * AIRESPACE-WIRELESS-MIB  cLApMaxNumberOfDot11Slots  1.3.6.1.4.1.9.9.513.1.1.1.1.3     enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApMaxNumberOfDot11Slots
 * AIRESPACE-WIRELESS-MIB  cLApName                   1.3.6.1.4.1.9.9.513.1.1.1.1.5     enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApName
 *               ##poll##  cLApUpTime                 1.3.6.1.4.1.9.9.513.1.1.1.1.6     enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApUpTime
 *               ##poll##  cLLwappUpTime              1.3.6.1.4.1.9.9.513.1.1.1.1.7     enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLLwappUpTime
 *               ##poll##  cLLwappJoinTakenTime       1.3.6.1.4.1.9.9.513.1.1.1.1.8     enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLLwappJoinTakenTime
 * AIRESPACE-WIRELESS-MIB  cLApAdminStatus            1.3.6.1.4.1.9.9.513.1.1.1.1.38    enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappAp.cLApTable.cLApEntry.cLApAdminStatus
 *
 */

 echo('CISCO-LWAPP-AP-MIB' . PHP_EOL);

 //Thin AP Get Uptimes
 print_cli_data("Collecting", "CISCO-LWAPP-AP-MIB Access Points Uptimes", 3);

 $table_rows = array();
 $table_headers = array('%WAP MacAddress%n', '%WAP Uptime%n', '%WAP Controller Uptime%n', '%WAP Controller Latency%n');

 $ap_db = dbFetchRows('SELECT * FROM `wifi_aps` WHERE `device_id` = ?', array($device['device_id']));
 foreach ($ap_db as $aps)
 {
 	$aps_db[$aps['ap_index']] = $aps;
	$aps_exist[$aps['ap_index']] = $aps['wifi_ap_id'];
 }

 // AccessPoints Attributes
 $lwapps = snmpwalk_cache_oid($device, 'cLApUpTime',    	array(), 'CISCO-LWAPP-AP-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_uptime
 $lwapps = snmpwalk_cache_oid($device, 'cLLwappUpTime',     	$lwapps, 'CISCO-LWAPP-AP-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_control_uptime
 $lwapps = snmpwalk_cache_oid($device, 'cLLwappJoinTakenTime',	$lwapps, 'CISCO-LWAPP-AP-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_control_latency

 //print_vars($lwapps);

 foreach ($lwapps as $index => $aps)
 {
	$mac_padded = mac_zeropad($index);
	$mac_index = format_mac($mac_padded);

	if (OBS_DEBUG) { print_r($aps); }

	$uptime = uptime_to_seconds($aps['cLApUpTime']);
	$uptime_show = format_uptime($uptime, "long");

	$control_uptime = uptime_to_seconds($aps['cLLwappUpTime']);
        $control_uptime_show = format_uptime($control_uptime, "long");

	$control_latency = uptime_to_seconds($aps['cLLwappJoinTakenTime']);
        $control_latency_show = format_uptime($control_latency, "long");

	if (is_array($aps_db[$mac_index]))
	{
		//echo("AP Index exists: $index\n");
		dbUpdate(array('ap_uptime'          => $uptime,
                   'ap_control_uptime'  => $control_uptime,
			             'ap_control_latency' => $control_latency,
		               ),'wifi_aps', '`device_id` = ? AND `ap_index` = ?',
			  	   array($device['device_id'], $mac_index));
		}else {
			echo("New AP Index: $mac_index - Need to wait for AIRESPACE-WIRELESS-MIB to add it in the DB\n");
		}

		$table_row = array();
		$table_row[] = $mac_index;
		$table_row[] = $uptime_show;
		$table_row[] = $control_uptime_show;
		$table_row[] = $control_latency_show;
		$table_rows[] = $table_row;
		unset($table_row);
 }

 print_cli_table($table_rows, $table_headers);
 unset($table_rows, $table_headers);

 if (OBS_DEBUG) { print_r($aps_exist); }

 unset($ap_db, $aps_db, $aps_exist, $mac_index, $wifi_ap_id, $aps, $lwapps, $uptime, $uptime_show, $control_uptime, $control_uptime_show, $control_latency, $control_latency_show, $index, $mac_padded);

// EOF
