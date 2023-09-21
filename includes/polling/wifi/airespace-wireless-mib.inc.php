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

/**
 * AIRESPACE-WIRELESS-MIB
 *
 * Discovery SysObjectID has to start either with 1.3.6.1.4.1.14179. or 1.3.6.1.4.1.9. and SysDescription contains the string "Cisco Controller".
 *
 * Controller Dot11gSupport 1.3.6.1.4.1.14179.2.3.2.1.20
 *
 * Thin AP             Table               1.3.6.1.4.1.14179.2.2.1.1     enterprises.airespace.bsnWireless.bsnAP.bsnAPTable.bsnAPEntry
 *            ##poll## RadioMacAddress     1.3.6.1.4.1.14179.2.2.1.1.1   bsnAPDot3MacAddress
 *          ##poll## NumRadioSlots       1.3.6.1.4.1.14179.2.2.1.1.2   bsnAPNumOfSlots                      INTEGER ( 0 .. 24)   Number of Radio Interfaces on
 *          the Airespace AP.
 *            ##poll## IPAddress           1.3.6.1.4.1.14179.2.2.1.1.19  bsnApIpAddress
 *            ##poll## Name                1.3.6.1.4.1.14179.2.2.1.1.3   bsnAPName                            OCTET STRING ( SIZE ( 0 .. 32))
 *            ##poll## Location            1.3.6.1.4.1.14179.2.2.1.1.4   bsnAPLocation                        OCTET STRING ( SIZE ( 0 .. 80))
 *            ##poll## Model               1.3.6.1.4.1.14179.2.2.1.1.16  bsnAPModel
 *            ##poll## Serial              1.3.6.1.4.1.14179.2.2.1.1.17  bsnAPSerialNumber
 *            ##poll## OperStatus          1.3.6.1.4.1.14179.2.2.1.1.6   bsnAPOperationStatus                 INTEGER { associated ( 1), disassociating ( 2),
 *            downloading ( 3) }
 *            ##poll## AdminStatus         1.3.6.1.4.1.14179.2.2.1.1.37  bsnAPAdminStatus                     INTEGER { enable ( 1), disable ( 2) }
 *
 * Thin AP Members     Table               1.3.6.1.4.1.14179.2.2.2       enterprises.airespace.bsnWireless.bsnAP.bsnAPIfTable.bsnAPIfEntry
 *            ##poll## RadioSlot           1.3.6.1.4.1.14179.2.2.2.1.1   bsnAPIfSlotId                    Unsigned32 ( 0 .. 2)
 *            ##poll## OperStatus          1.3.6.1.4.1.14179.2.2.2.1.12  bsnAPIfOperStatus                INTEGER { down ( 1), up ( 2) }
 *            ##poll## AdminStatus         1.3.6.1.4.1.14179.2.2.2.1.34  bsnAPIfAdminStatus               INTEGER { disable ( 2), enable ( 1) }
 *                     TxPowerControl      1.3.6.1.4.1.14179.2.2.2.1.5   bsnAPIfPhyTxPowerControl         INTEGER { automatic ( 1), customized ( 2) }
 *                     TxPowerLevel        1.3.6.1.4.1.14179.2.2.2.1.6   bsnAPIfPhyTxPowerLevel           INTEGER ( 1 .. 8)  Valid values are between 1 to
 *                     8,depnding on what radio, and this attribute can be set only if bsnAPIfPhyTxPowerControl is set to customized.
 *            ##poll## CurrentChannel      1.3.6.1.4.1.14179.2.2.2.1.4   bsnAPIfPhyChannelNumber
 *            ##poll## RadioType           1.3.6.1.4.1.14179.2.2.2.1.2   bsnAPIfType                      INTEGER { dot11b ( 1), dot11a ( 2), uwb ( 4) }
 *            ##poll## ConnUsers           1.3.6.1.4.1.14179.2.2.2.1.15  bsnApIfNoOfUsers                 Counter32
 *
 * Controller WLANs    Table               1.3.6.1.4.1.14179.2.1.1       enterprises.airespace.bsnWireless.bsnEss.bsnDot11EssTable.bsnDot11EssEntry
 *               SSID Index       1.3.6.1.4.1.14179.2.1.1.1.1   bsnDot11EssIndex                   **** also on CISCO-LWAPP-AP-MIB
 *               1.3.6.1.4.1.9.9.512.1.1.1.1.1  cLWlanIndex **** SSID Name           1.3.6.1.4.1.14179.2.1.1.1.2   bsnDot11EssSsid                    **** also
 *               on CISCO-LWAPP-AP-MIB 1.3.6.1.4.1.9.9.512.1.1.1.1.4  cLWlanSsid  **** CISCO-LWAPP-AP-MIB  WLAN profile        1.3.6.1.4.1.9.9.512.1.1.1.1.3
 *               cLWlanProfileName                  Must correlate bsnDot11EssIndex to cLWlanIndex from both MIB files AdminStatus
 *               1.3.6.1.4.1.14179.2.1.1.1.6   bsnDot11EssAdminStatus             INTEGER { disable ( 0), enable ( 1) } SSID QoS
 *               1.3.6.1.4.1.14179.2.1.1.1.31     bsnDot11EssQualityOfService        INTEGER { bronze ( 0), silver ( 1), gold ( 2), platinum ( 3) }  Quality of
 *               Service for a WLAN. Connected clients   1.3.6.1.4.1.14179.2.1.1.1.38     bsnDot11EssNumberOfMobileStations  Counter32  No of Mobile Stations
 *               currently associated with the WLAN. SSID interfname     1.3.6.1.4.1.14179.2.1.1.1.42     bsnDot11EssInterfaceName           DisplayString (
 *               SIZE ( 1 .. 32))  Name of the interface used by this WLAN. RowStatus           1.3.6.1.4.1.14179.2.1.1.1.60  bsnDot11EssRowStatus
 *                **** also on CISCO-LWAPP-AP-MIB 1.3.6.1.4.1.9.9.512.1.1.1.1.2  cLWlanRowStatus  ****
 *
 * Controller WLANs Security
 *
 * on CISCO-LWAPP-WLAN-SECURITY-MIB
 *
 *                     Table               1.3.6.1.4.1.9.9.521.1.1.1
 *                     enterprises.cisco.ciscoMgmt.ciscoLwappWlanSecurityMIB.ciscoLwappWlanSecurityMIBObjects.clwsCckmConfig.cLWSecDot11EssCckmTable.cLWSecDot11EssCckmEntry
 *                     SSID Index          1.3.6.1.4.1.9.9.512.1.1.1.1.1 cLWlanIndex
 *
 * Client              Table               1.3.6.1.4.1.14179.2.1.4       enterprises.airespace.bsnWireless.bsnEss.bsnMobileStationTable.bsnMobileStationEntry
 *                     IPAddress           1.3.6.1.4.1.14179.2.1.4.1.2   bsnMobileStationIpAddress
 *                     Name                1.3.6.1.4.1.14179.2.1.4.1.3   bsnMobileStationUserName
 *                     SSID                1.3.6.1.4.1.14179.2.1.4.1.7   bsnMobileStationSsid
 *                     Status              1.3.6.1.4.1.14179.2.1.4.1.9   bsnMobileStationStatus     INTEGER { idle ( 0), aaaPending ( 1), authenticated ( 2),
 *                     associated ( 3), powersave ( 4), disassociated ( 5), tobedeleted ( 6), probing ( 7), blacklisted ( 8) } APMAC
 *                     1.3.6.1.4.1.14179.2.1.4.1.4   bsnMobileStationAPMacAddr InterfaceID         1.3.6.1.4.1.14179.2.1.4.1.5   bsnMobileStationAPIfSlotId
 *                     INTEGER ( 0 .. 15)
 *
 *                     Table               1.3.6.1.4.1.14179.2.1.6
 *                     enterprises.airespace.bsnWireless.bsnEss.bsnMobileStationStatsTable.bsnMobileStationStatsEntry SignalStrength
 *                     1.3.6.1.4.1.14179.2.1.6.1.1   bsnMobileStationRSSI             Integer32 InTotalBytes        1.3.6.1.4.1.14179.2.1.6.1.2
 *                     bsnMobileStationBytesReceived    Counter64 OutTotalBytes       1.3.6.1.4.1.14179.2.1.6.1.3   bsnMobileStationBytesSent        Counter64
 *                     InTotalPackets      1.3.6.1.4.1.14179.2.1.6.1.5   bsnMobileStationPacketsReceived  Counter64 OutTotalPackets
 *                     1.3.6.1.4.1.14179.2.1.6.1.6   bsnMobileStationPacketsSent      Counter64 MobileStationSnr    1.3.6.1.4.1.14179.2.1.6.1.26
 *                     bsnMobileStationSnr              Integer32
 *
 */

if (safe_empty($aps_db)) {
    // APs not discovered
    return;
}

//echo('AIRESPACE-WIRELESS-MIB' . PHP_EOL);

// Thin AP
print_cli_data("Collecting", "AIRESPACE-WIRELESS-MIB Access Points ", 3);

// AccessPoints Attributes
//$lwapps = snmpwalk_cache_oid($device, 'bsnAPDot3MacAddress',         [], 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);  //ap_index
$lwapps = snmpwalk_cache_oid($device, 'bsnAPName', [], 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);                                    //ap_name
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

//print_vars($lwapps);
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
            default: // Fallback
                $apstatus = $aps['bsnAPOperationStatus'];
        }
    } else {
        $apstatus = "shutdown";
    }

    $aps_poll[$index] = [
      'device_id'       => $device['device_id'],
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
}

// Controller stats
$wifi_controller['aps'] = safe_count($aps_poll);

unset($apstatus, $index, $aps, $lwapps);

//Thin AP Members
//In creation process - Sergio
print_cli_data("Collecting", "AIRESPACE-WIRELESS-MIB Access Points Interfaces", 3);

// AccessPoints Member Attributes
// NOTE, ap_member_index: bsnAPDot3MacAddress.bsnAPIfSlotId
$lwappsmemb = snmpwalk_cache_twopart_oid($device, 'bsnAPIfOperStatus', [], 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);                //ap_member_state
$lwappsmemb = snmpwalk_cache_twopart_oid($device, 'bsnAPIfAdminStatus', $lwappsmemb, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);      //ap_member_admin_state
$lwappsmemb = snmpwalk_cache_twopart_oid($device, 'bsnApIfNoOfUsers', $lwappsmemb, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);        //ap_member_conns
$lwappsmemb = snmpwalk_cache_twopart_oid($device, 'bsnAPIfPhyChannelNumber', $lwappsmemb, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE); //ap_member_channel
$lwappsmemb = snmpwalk_cache_twopart_oid($device, 'bsnAPIfType', $lwappsmemb, 'AIRESPACE-WIRELESS-MIB', NULL, OBS_SNMP_ALL_TABLE);             //ap_member_radiotype

//print_vars($lwappsmemb);

$wifi_controller['clients'] = 0;
foreach ($lwappsmemb as $mac_index => $entry) {
    $index = format_mac(mac_zeropad($mac_index));

    foreach ($entry as $slot_index => $member) {
        print_debug_vars($member);

        $member_index = $index . '.' . $slot_index;

        switch ($member['bsnAPIfType']) {
            case 'dot11b':
                $interfaceType = "802.11b/g/n";
                break;
            case 'dot11a':
                $interfaceType = "802.11a/n/ac";
                break;
            case 'dot11abgn':
                $interfaceType = "802.11a/b/g/n";
                break;
            case 'uwb':
                $interfaceType = "Dual-Band Radios";
                break;
            default: // Fallback
                $interfaceType = $member['bsnAPIfType'];
        }

        $channel_number = substr($member['bsnAPIfPhyChannelNumber'], 2); // ch149 -> 149

        $aps_member_poll[$member_index] = [
          'device_id'             => $device['device_id'],
          //'wifi_ap_id'            => $wifi_ap_id,
          'ap_index'              => $index,
          'ap_index_member'       => $member_index,
          //'ap_name'               => $ap_name,
          'ap_member_state'       => $member['bsnAPIfOperStatus'],
          'ap_member_admin_state' => $member['bsnAPIfAdminStatus'],
          'ap_member_conns'       => $member['bsnApIfNoOfUsers'],
          'ap_member_channel'     => (int)$channel_number,
          'ap_member_radiotype'   => $interfaceType,
        ];

        // Total clients count
        $wifi_controller['clients'] += (int)$member['bsnApIfNoOfUsers'];
    }
}

unset($member_index, $member, $entry, $mac_index, $slot_index, $lwappsmemb, $interfaceType, $channel_number, $index);

echo(PHP_EOL);

// EOF
