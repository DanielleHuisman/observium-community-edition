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

$oids = snmpwalk_cache_oid($device, "rttMonCtrl", [], 'CISCO-RTTMON-MIB', NULL, OBS_SNMP_ALL_HEX);

// Add extended source/target info
//CISCO-RTTMON-IP-EXT-MIB::crttMonIPEchoAdminTargetAddrType.44 = INTEGER: ipv4(1)
//CISCO-RTTMON-IP-EXT-MIB::crttMonIPEchoAdminTargetAddrType.66 = INTEGER: ipv6(2)
//CISCO-RTTMON-IP-EXT-MIB::crttMonIPEchoAdminTargetAddress.44 = Hex-STRING: 55 72 03 FC
//CISCO-RTTMON-IP-EXT-MIB::crttMonIPEchoAdminTargetAddress.66 = Hex-STRING: 2A 02 04 08 77 22 00 41 00 00 00 00 00 00 01 50
//CISCO-RTTMON-IP-EXT-MIB::crttMonIPEchoAdminSourceAddrType.44 = INTEGER: ipv4(1)
//CISCO-RTTMON-IP-EXT-MIB::crttMonIPEchoAdminSourceAddrType.66 = INTEGER: ipv6(2)
//CISCO-RTTMON-IP-EXT-MIB::crttMonIPEchoAdminSourceAddress.44 = Hex-STRING: D9 4F 06 9C
//CISCO-RTTMON-IP-EXT-MIB::crttMonIPEchoAdminSourceAddress.66 = ""
//$oids = snmpwalk_cache_oid($device, "crttMonIPEchoAdminTargetAddrType", $oids, 'CISCO-RTTMON-IP-EXT-MIB');
$oids = snmpwalk_cache_oid($device, "crttMonIPEchoAdminTargetAddress", $oids, 'CISCO-RTTMON-IP-EXT-MIB', NULL, OBS_SNMP_ALL_HEX);
//$oids = snmpwalk_cache_oid($device, "crttMonIPEchoAdminSourceAddrType", $oids, 'CISCO-RTTMON-IP-EXT-MIB');
//$oids = snmpwalk_cache_oid($device, "crttMonIPEchoAdminSourceAddress",  $oids, 'CISCO-RTTMON-IP-EXT-MIB');

foreach ($oids as $sla_index => $entry) {
    if (!isset($entry['rttMonCtrlAdminStatus']) ||      // Skip additional multiindex entries from table
        $entry['rttMonCtrlOperState'] === 'inactive') { // Skip inactive entries
        continue;
    }

    // FIXME. Temporary hack, while this type of Jitter unsupported by Cisco
    switch ($entry['rttMonCtrlAdminRttType']) {
        case '34':
            // See: https://jira.observium.org/browse/OBS-3053
            // https://community.cisco.com/t5/routing/ip-sla-path-jitter-snmp-mib/td-p/2890302
            // https://community.cisco.com/t5/switching/ipsla-path-jitter-monitoring/td-p/2131136
            // CISCO-RTTMON-MIB::rttMonCtrlAdminRttType.200 = INTEGER: 34
            $entry['rttMonCtrlAdminRttType'] = 'pathjitter';
            break;
    }

    $data = [
      'device_id'  => $device['device_id'],
      'sla_mib'    => 'CISCO-RTTMON-MIB',
      'sla_index'  => $sla_index,
      'sla_owner'  => trim(snmp_hexstring($entry['rttMonCtrlAdminOwner'])),
      'sla_tag'    => trim(snmp_hexstring($entry['rttMonCtrlAdminTag'])),
      'rtt_type'   => $entry['rttMonCtrlAdminRttType'], // Possible: echo, pathEcho, fileIO, script, udpEcho, tcpConnect, http, dns, jitter, dlsw, dhcp,
      // ftp, voip, rtp, lspGroup, icmpjitter, lspPing, lspTrace, ethernetPing, ethernetJitter,
      // lspPingPseudowire, video, y1731Delay, y1731Loss, mcastJitter,
      // (currently unsupported by vendor MIBs): pathjitter
      'sla_status' => $entry['rttMonCtrlAdminStatus'],  // Possible: active, notInService, notReady, createAndGo, createAndWait, destroy
      'deleted'    => 0,
    ];

    // Use jitter or simple echo graph for SLA
    if (stripos($data['rtt_type'], 'jitter') !== FALSE) {
        $data['sla_graph'] = 'jitter';
    } else {
        $data['sla_graph'] = 'echo';
    }

    // Target
    switch ($data['rtt_type']) {
        case 'http':
        case 'ftp':
            $data['sla_target'] = trim(snmp_hexstring($entry['rttMonEchoAdminURL']));
            break;
        case 'dns':
            $data['sla_target'] = trim(snmp_hexstring($entry['rttMonEchoAdminTargetAddressString']));
            break;
        case 'echo':
        case 'jitter':
        case 'icmpjitter':
        default:
            if (!empty($entry['crttMonIPEchoAdminTargetAddress'])) {
                $data['sla_target'] = hex2ip($entry['crttMonIPEchoAdminTargetAddress']);
            } else {
                $data['sla_target'] = hex2ip($entry['rttMonEchoAdminTargetAddress']);
            }
            break;
    }

    // Some fallbacks for when the tag is empty
    if (!$data['sla_tag']) {
        $data['sla_tag'] = $data['sla_target'];
    }

    // Limits
    $data['sla_limit_high']      = ($entry['rttMonCtrlAdminTimeout'] > 0 ? $entry['rttMonCtrlAdminTimeout'] : 5000);
    $data['sla_limit_high_warn'] = ($entry['rttMonCtrlAdminThreshold'] > 0 ? $entry['rttMonCtrlAdminThreshold'] : 1000);
    if ($data['sla_limit_high_warn'] >= $data['sla_limit_high']) {
        $data['sla_limit_high_warn'] = (int)($data['sla_limit_high'] / 5);
    }

    $sla_table['CISCO-RTTMON-MIB'][$sla_index] = $data; // Pull to array for main processing
}

// EOF
