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
 * AIRESPACE-WIRELESS-MIB
 *
 * Discovery SysObjectID has to start either with 1.3.6.1.4.1.14179. or 1.3.6.1.4.1.9. and SysDescription contains the string "Cisco Controller".
 *
 * Controller Dot11gSupport 1.3.6.1.4.1.14179.2.3.2.1.20
 *
 * Thin AP             Table               1.3.6.1.4.1.14179.2.2.1.1     enterprises.airespace.bsnWireless.bsnAP.bsnAPTable.bsnAPEntry
 *            ##poll## RadioMacAddress     1.3.6.1.4.1.14179.2.2.1.1.1   bsnAPDot3MacAddress
 * 	      ##poll## NumRadioSlots       1.3.6.1.4.1.14179.2.2.1.1.2   bsnAPNumOfSlots                      INTEGER ( 0 .. 24)   Number of Radio Interfaces on the Airespace AP.
 *            ##poll## IPAddress           1.3.6.1.4.1.14179.2.2.1.1.19  bsnApIpAddress
 *            ##poll## Name                1.3.6.1.4.1.14179.2.2.1.1.3   bsnAPName                            OCTET STRING ( SIZE ( 0 .. 32))
 *            ##poll## Location            1.3.6.1.4.1.14179.2.2.1.1.4   bsnAPLocation                        OCTET STRING ( SIZE ( 0 .. 80))
 *            ##poll## Model               1.3.6.1.4.1.14179.2.2.1.1.16  bsnAPModel
 *            ##poll## Serial              1.3.6.1.4.1.14179.2.2.1.1.17  bsnAPSerialNumber
 *            ##poll## OperStatus          1.3.6.1.4.1.14179.2.2.1.1.6   bsnAPOperationStatus                 INTEGER { associated ( 1), disassociating ( 2), downloading ( 3) }
 *            ##poll## AdminStatus         1.3.6.1.4.1.14179.2.2.1.1.37  bsnAPAdminStatus                     INTEGER { enable ( 1), disable ( 2) }
 *
 * Thin AP Members     Table               1.3.6.1.4.1.14179.2.2.2       enterprises.airespace.bsnWireless.bsnAP.bsnAPIfTable.bsnAPIfEntry
 *            ##poll## RadioSlot           1.3.6.1.4.1.14179.2.2.2.1.1   bsnAPIfSlotId                    Unsigned32 ( 0 .. 2)
 *            ##poll## OperStatus          1.3.6.1.4.1.14179.2.2.2.1.12  bsnAPIfOperStatus                INTEGER { down ( 1), up ( 2) }
 *            ##poll## AdminStatus         1.3.6.1.4.1.14179.2.2.2.1.34  bsnAPIfAdminStatus               INTEGER { disable ( 2), enable ( 1) }
 *                     TxPowerControl      1.3.6.1.4.1.14179.2.2.2.1.5   bsnAPIfPhyTxPowerControl         INTEGER { automatic ( 1), customized ( 2) }
 *                     TxPowerLevel        1.3.6.1.4.1.14179.2.2.2.1.6   bsnAPIfPhyTxPowerLevel           INTEGER ( 1 .. 8)  Valid values are between 1 to 8,depnding on what radio, and this attribute can be set only if bsnAPIfPhyTxPowerControl is set to customized.
 *            ##poll## CurrentChannel      1.3.6.1.4.1.14179.2.2.2.1.4   bsnAPIfPhyChannelNumber
 *            ##poll## RadioType           1.3.6.1.4.1.14179.2.2.2.1.2   bsnAPIfType                      INTEGER { dot11b ( 1), dot11a ( 2), uwb ( 4) }
 *            ##poll## ConnUsers           1.3.6.1.4.1.14179.2.2.2.1.15  bsnApIfNoOfUsers                 Counter32
 *
 * Controller WLANs    Table               1.3.6.1.4.1.14179.2.1.1       enterprises.airespace.bsnWireless.bsnEss.bsnDot11EssTable.bsnDot11EssEntry
 *		       SSID Index	   1.3.6.1.4.1.14179.2.1.1.1.1   bsnDot11EssIndex                   **** also on CISCO-LWAPP-AP-MIB 1.3.6.1.4.1.9.9.512.1.1.1.1.1  cLWlanIndex ****
 *                     SSID Name           1.3.6.1.4.1.14179.2.1.1.1.2   bsnDot11EssSsid                    **** also on CISCO-LWAPP-AP-MIB 1.3.6.1.4.1.9.9.512.1.1.1.1.4  cLWlanSsid  ****
 * CISCO-LWAPP-AP-MIB  WLAN profile        1.3.6.1.4.1.9.9.512.1.1.1.1.3 cLWlanProfileName                  Must correlate bsnDot11EssIndex to cLWlanIndex from both MIB files
 *                     AdminStatus         1.3.6.1.4.1.14179.2.1.1.1.6   bsnDot11EssAdminStatus             INTEGER { disable ( 0), enable ( 1) }
 *		       SSID QoS		   1.3.6.1.4.1.14179.2.1.1.1.31	 bsnDot11EssQualityOfService        INTEGER { bronze ( 0), silver ( 1), gold ( 2), platinum ( 3) }  Quality of Service for a WLAN.
 *                     Connected clients   1.3.6.1.4.1.14179.2.1.1.1.38	 bsnDot11EssNumberOfMobileStations  Counter32  No of Mobile Stations currently associated with the WLAN.
 *		       SSID interfname     1.3.6.1.4.1.14179.2.1.1.1.42	 bsnDot11EssInterfaceName           DisplayString ( SIZE ( 1 .. 32))  Name of the interface used by this WLAN.
 *                     RowStatus           1.3.6.1.4.1.14179.2.1.1.1.60  bsnDot11EssRowStatus               **** also on CISCO-LWAPP-AP-MIB 1.3.6.1.4.1.9.9.512.1.1.1.1.2  cLWlanRowStatus  ****
 *
 * Controller WLANs Security
 *
 * on CISCO-LWAPP-WLAN-SECURITY-MIB
 *
 *                     Table               1.3.6.1.4.1.9.9.521.1.1.1     enterprises.cisco.ciscoMgmt.ciscoLwappWlanSecurityMIB.ciscoLwappWlanSecurityMIBObjects.clwsCckmConfig.cLWSecDot11EssCckmTable.cLWSecDot11EssCckmEntry
 *                     SSID Index          1.3.6.1.4.1.9.9.512.1.1.1.1.1 cLWlanIndex
 *
 * Client              Table               1.3.6.1.4.1.14179.2.1.4       enterprises.airespace.bsnWireless.bsnEss.bsnMobileStationTable.bsnMobileStationEntry
 *                     IPAddress           1.3.6.1.4.1.14179.2.1.4.1.2   bsnMobileStationIpAddress
 *                     Name                1.3.6.1.4.1.14179.2.1.4.1.3   bsnMobileStationUserName
 *                     SSID                1.3.6.1.4.1.14179.2.1.4.1.7   bsnMobileStationSsid
 *                     Status              1.3.6.1.4.1.14179.2.1.4.1.9   bsnMobileStationStatus     INTEGER { idle ( 0), aaaPending ( 1), authenticated ( 2), associated ( 3), powersave ( 4), disassociated ( 5), tobedeleted ( 6), probing ( 7), blacklisted ( 8) }
 *                     APMAC               1.3.6.1.4.1.14179.2.1.4.1.4   bsnMobileStationAPMacAddr
 *                     InterfaceID         1.3.6.1.4.1.14179.2.1.4.1.5   bsnMobileStationAPIfSlotId  INTEGER ( 0 .. 15)
 *
 *                     Table               1.3.6.1.4.1.14179.2.1.6       enterprises.airespace.bsnWireless.bsnEss.bsnMobileStationStatsTable.bsnMobileStationStatsEntry
 *                     SignalStrength      1.3.6.1.4.1.14179.2.1.6.1.1   bsnMobileStationRSSI             Integer32
 *                     InTotalBytes        1.3.6.1.4.1.14179.2.1.6.1.2   bsnMobileStationBytesReceived    Counter64
 *                     OutTotalBytes       1.3.6.1.4.1.14179.2.1.6.1.3   bsnMobileStationBytesSent        Counter64
 *                     InTotalPackets      1.3.6.1.4.1.14179.2.1.6.1.5   bsnMobileStationPacketsReceived  Counter64
 *                     OutTotalPackets     1.3.6.1.4.1.14179.2.1.6.1.6   bsnMobileStationPacketsSent      Counter64
 *                     MobileStationSnr    1.3.6.1.4.1.14179.2.1.6.1.26  bsnMobileStationSnr              Integer32
 *
 */

        echo('AIRESPACE-WIRELESS-MIB' . PHP_EOL);

	//Thin AP
        print_cli_data("Collecting", "AIRESPACE-WIRELESS-MIB Access Points ", 3);

        $table_rows = array();
	$table_headers = array('%WAP MacAddress%n', '%WAP Name%n', '%WAP Address%n', '%WAP Serial%n', '%WAP Model%n', '%WAP Location%n', '%WAP OperStatus%n', '%WAP AdminStatus');

        $AP_db = dbFetchRows('SELECT * FROM `wifi_aps` WHERE `device_id` = ?', array($device['device_id']));
	foreach ($AP_db as $aps)
	{
	    $APs_db[$aps['ap_index']] = $aps;
	    $APs_exist[$aps['ap_index']] = $aps['wifi_ap_id'];
	}

	// AccessPoints Attributes
	$lwapps = snmpwalk_cache_oid($device, 'bsnAPDot3MacAddress',        	array(), 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_index
	if(count($lwapps))
	{
		$lwapps = snmpwalk_cache_oid($device, 'bsnAPName',    		$lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_name
		$lwapps = snmpwalk_cache_oid($device, 'bsnAPNumOfSlots',        $lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_number
		$lwapps = snmpwalk_cache_oid($device, 'bsnApIpAddress',	        $lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_address
		$lwapps = snmpwalk_cache_oid($device, 'bsnAPSerialNumber',    	$lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_serial
		$lwapps = snmpwalk_cache_oid($device, 'bsnAPModel',  		$lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_model
		$lwapps = snmpwalk_cache_oid($device, 'bsnAPLocation',     	$lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_location
		$lwapps = snmpwalk_cache_oid($device, 'bsnAPAdminStatus',	$lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_admin_status
		$lwapps = snmpwalk_cache_oid($device, 'bsnAPOperationStatus',   $lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_status
	}

	//print_vars($lwapps);

	foreach ($lwapps as $aps)
	{
		$ap_mib = 'AIRESPACE-WIRELESS-MIB';
		$mibindex = $aps['bsnAPDot3MacAddress'];
		$macformated = mac_zeropad($mibindex);
		$index = format_mac($macformated);

		if (OBS_DEBUG) { print_r($aps); }

		if($aps['bsnAPAdminStatus'] == 'enable')
		{
			switch($aps['bsnAPOperationStatus'])
			{
				case 'associated': // AP is Up and running on the WLC.
            			  $apstatus  = "up";
            			  break;
				case 'disassociating': //AP is Unreachable and will be removed from WLC.
                                  $apstatus  = "down";
                                  break;
				case 'downloading': //AP is now being Added to WLC.
                                  $apstatus  = "init";
                                  break;
			}
		}

		if (is_array($APs_db[$index]))
	        {
			//echo("AP Index exists: $index\n");
			dbUpdate(array('ap_serial'   => $aps['bsnAPSerialNumber'],
                                       'ap_name'     => $aps['bsnAPName'],
				       'ap_number'   => $aps['bsnAPNumOfSlots'],
                                       'ap_address'  => $aps['bsnApIpAddress'],
				       'ap_model'    => $aps['bsnAPModel'],
				       'ap_location' => $aps['bsnAPLocation'],
				       'ap_status'   => $apstatus,
				       'ap_admin_status'   => $aps['bsnAPAdminStatus'],
				      ),'wifi_aps', '`device_id` = ? AND `ap_index` = ?',
				 array($device['device_id'], $index));

			unset($APs_exist[$index]);
		}else {
			//echo("New AP Index: $index\n");
			dbInsert(array('device_id'   => $device['device_id'],
				       'ap_mib'      => $ap_mib,
				       'ap_index'    => $index,
  				       'ap_number'   => $aps['bsnAPNumOfSlots'],
				       'ap_name'     => $aps['bsnAPName'],
                                       'ap_address'  => $aps['bsnApIpAddress'],
				       'ap_serial'   => $aps['bsnAPSerialNumber'],
				       'ap_model'    => $aps['bsnAPModel'],
				       'ap_location' => $aps['bsnAPLocation'],
				       'ap_status'   => $apstatus,
				       'ap_admin_status'   => $aps['bsnAPAdminStatus'],
				      ), 'wifi_aps');
		}

		$table_row = array();
		$table_row[] = $index;
		$table_row[] = $aps['bsnAPName'];
		$table_row[] = $aps['bsnApIpAddress'];
		$table_row[] = $aps['bsnAPSerialNumber'];
		$table_row[] = $aps['bsnAPModel'];
		$table_row[] = $aps['bsnAPLocation'];
		$table_row[] = $aps['bsnAPOperationStatus'];
		$table_row[] = $aps['bsnAPAdminStatus'];
		$table_rows[] = $table_row;
		unset($table_row);
		unset($ap_mib);

	}

       	print_cli_table($table_rows, $table_headers);
        unset($table_rows, $table_headers);

	if (OBS_DEBUG) { print_r($APs_exist); }

	foreach ($APs_exist as $ap_index => $wifi_ap_id)
	{
	  if($ap_index['deleted'] == 1){
	       echo("AP will delete AP:$ap_index with id:$wifi_ap_id");
	       dbDelete('wifi_aps', '`wifi_ap_id` =  ?', array($wifi_ap_id));
	  }else{
          	echo("AP don't exists in WLC anymore, but it's not marked to be deleted (considering Down): $ap_index with id:$wifi_ap_id\n");
                dbUpdate(array('ap_status'   => "down",
                               ),'wifi_aps', '`device_id` = ? AND `wifi_ap_id` = ?',
                                 array($device['device_id'], $wifi_ap_id));
	  }
	}

	unset($AP_db, $APs_db, $APs_exist, $ap_mib, $apstatus, $index, $wifi_ap_id, $aps, $lwapps, $mibindex, $macformated);



	//Thin AP Members
        //In creation process - Sergio
	print_cli_data("Collecting", "AIRESPACE-WIRELESS-MIB Access Points Interfaces", 3);

	$table_rows = array();
        $table_headers = array('%WAP MacAddress%n', '%WAP Name%n', '%WRadio Type%n', '%WChannel Number%n', '%WConnected Users%n', '%WOper Status%n', '%WAdmin Status%n');

	$AP_members_db = dbFetchRows('SELECT * FROM `wifi_aps_members` WHERE `device_id` = ?', array($device['device_id']));
	foreach ($AP_members_db as $member)
	{
	    $APs_members_db[$member['ap_index_member']] = $member;
	    $AP_member_exist[$member['ap_index_member']] = $member['wifi_ap_member_id'];
	}

	// AccessPoints Member Attributes
 	// NOTE, ap_member_index: bsnAPDot3MacAddress.bsnAPIfSlotId
	$lwappsmemb = snmpwalk_cache_twopart_oid($device, 'bsnAPIfOperStatus',       	array(),     'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_member_state
	$lwappsmemb = snmpwalk_cache_twopart_oid($device, 'bsnAPIfAdminStatus',         $lwappsmemb, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_member_admin_state
	$lwappsmemb = snmpwalk_cache_twopart_oid($device, 'bsnApIfNoOfUsers',    	$lwappsmemb, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_member_conns
	$lwappsmemb = snmpwalk_cache_twopart_oid($device, 'bsnAPIfPhyChannelNumber',	$lwappsmemb, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_member_channel
	$lwappsmemb = snmpwalk_cache_twopart_oid($device, 'bsnAPIfType',     		$lwappsmemb, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_member_radiotype

	$lwapps = snmpwalk_cache_oid($device, 'bsnAPDot3MacAddress',        		array(), 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_index
	$lwapps = snmpwalk_cache_oid($device, 'bsnAPName',    				$lwapps, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_name

	//print_vars($lwappsmemb);
	//print_vars($lwapps);

	$oids = array('bsnApIfNoOfUsers');
	$rrd_create = $config['rrd_rra'];

	foreach ($oids as $oid)
	{
		$rrd_create .= " DS:$oid:GAUGE:600:U:100000000000";
	}

	foreach ($lwappsmemb as $mac_index => $entry)
	{
	   foreach ($entry as $slot_index => $member)
           {
		foreach ($lwapps as $aps_name)
           	{
                	if($mac_index == $aps_name['bsnAPDot3MacAddress'])
                        	$ap_name = $aps_name['bsnAPName'];
		}

		if (OBS_DEBUG) { print_r($member); }

                $macformated = mac_zeropad($mac_index);
                $index = format_mac($macformated);

		$member_index = $index.'.'.$slot_index;

		switch($member['bsnAPIfType'])
		{
			case 'dot11b':
            		  $interfaceType  = "802.11b/g/n";
            		  break;
			case 'dot11a':
                          $interfaceType  = "802.11a/n/ac";
                          break;
			case 'uwb':
                          $interfaceType  = "Dual-Band Radios";
                          break;
		}

		$channel = substr($member['bsnAPIfPhyChannelNumber'], 2);
		$channel_number = (int)$channel;

		if (is_array($APs_members_db[$member_index]))
		{
			//echo("AP member exists: $member_index\n");
			dbUpdate(array('ap_name'               => $ap_name,
                                       'ap_member_state'       => $member['bsnAPIfOperStatus'],
        			       'ap_member_admin_state' => $member['bsnAPIfAdminStatus'],
                                       'ap_member_conns'       => $member['bsnApIfNoOfUsers'],
                                       'ap_member_channel'     => $channel_number,
                                       'ap_member_radiotype'   => $interfaceType,
                                      ),'wifi_aps_members', '`device_id` = ? AND `ap_index_member` = ?',
                                 array($device['device_id'], $member_index));

                        unset($AP_member_exist[$member_index]);
		}else{
			//echo("New AP member: $member_index\n");
			dbInsert(array('device_id'   => $device['device_id'],
				       'ap_index_member'       => $member_index,
				       'ap_name'               => $ap_name,
  				       'ap_member_state'       => $member['bsnAPIfOperStatus'],
				       'ap_member_admin_state' => $member['bsnAPIfAdminStatus'],
                                       'ap_member_conns'       => $member['bsnApIfNoOfUsers'],
				       'ap_member_channel'     => $channel_number,
				       'ap_member_radiotype'   => $interfaceType,
				      ), 'wifi_aps_members');
		}

		$table_row = array();
                $table_row[] = $member_index;
                $table_row[] = $ap_name;
                $table_row[] = $interfaceType;
                $table_row[] = $channel_number;
                $table_row[] = $member['bsnApIfNoOfUsers'];
                $table_row[] = $member['bsnAPIfOperStatus'];
		$table_row[] = $member['bsnAPIfAdminStatus'];
                $table_rows[] = $table_row;
                unset($table_row);

		$rrd_file = 'lwappmember-'.safename($member_index).'.rrd';
		$rrdupdate = 'N';

		foreach ($oids as $oid)
    		{
			if (is_numeric($member[$oid]))
    			{
      				$rrdupdate .= ':'.$member[$oid];
    			} else {
      				$rrdupdate .= ':U';
    			}
		}

		if (!file_exists($rrd_file)) { rrdtool_create($device, $rrd_file, $rrd_create); }
		rrdtool_update($device, $rrd_file, $rrdupdate);
	    }
         }

	print_cli_table($table_rows, $table_headers);
        unset($table_rows, $table_headers);

	if (OBS_DEBUG) { print_r($AP_member_exist); }

	unset($AP_members_db, $APs_members_db, $AP_member_exist, $member_index, $member, $entry, $mac_index, $slot_index, $lwappsmemb, $interfaceType, $channel_number, $lwapps ,$aps_name, $ap_name, $macformated, $index);

	echo(PHP_EOL);

// EOF
