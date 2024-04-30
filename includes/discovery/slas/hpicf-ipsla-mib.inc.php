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

// HPICF-IPSLA-MIB::hpicfIpSlaType.1 = INTEGER: dhcp(6)
// HPICF-IPSLA-MIB::hpicfIpSlaType.2 = INTEGER: dns(7)
// HPICF-IPSLA-MIB::hpicfIpSlaAdminState.1 = INTEGER: enable(1)
// HPICF-IPSLA-MIB::hpicfIpSlaAdminState.2 = INTEGER: enable(1)
// HPICF-IPSLA-MIB::hpicfIpSlaSourceAddressType.1 = INTEGER: unknown(0)
// HPICF-IPSLA-MIB::hpicfIpSlaSourceAddressType.2 = INTEGER: ipv4(1)
// HPICF-IPSLA-MIB::hpicfIpSlaSourceAddress.1 = ""
// HPICF-IPSLA-MIB::hpicfIpSlaSourceAddress.2 = Hex-STRING: AC 11 CC 0A
// HPICF-IPSLA-MIB::hpicfIpSlaDestAddressType.1 = INTEGER: unknown(0)
// HPICF-IPSLA-MIB::hpicfIpSlaDestAddressType.2 = INTEGER: dns(16)
// HPICF-IPSLA-MIB::hpicfIpSlaDestAddress.1 = ""
// HPICF-IPSLA-MIB::hpicfIpSlaDestAddress.2 = Hex-STRING: 67 6F 6F 67 6C 65 2E 73 65 00 6E 61 6D 65 2D 73
// 65 72 76 65 72 00 31 37 32 2E 31 37 2E 32 30 34
// 2E 31 30 00 00 3F 00 00 00 D8 B5 65 1F 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
// HPICF-IPSLA-MIB::hpicfIpSlaRowStatus.1 = INTEGER: active(1)
// HPICF-IPSLA-MIB::hpicfIpSlaRowStatus.2 = INTEGER: active(1)

$oids = snmpwalk_cache_oid($device, "hpicfIpSlaTable", [], 'HPICF-IPSLA-MIB', NULL, OBS_SNMP_ALL_MULTILINE);

// Add extended source/target info
// HPICF-IPSLA-MIB::hpicfIpSlaMsgResTimeout.1 = Gauge32: 0
// HPICF-IPSLA-MIB::hpicfIpSlaMsgResTimeout.2 = Gauge32: 736

$oids = snmpwalk_cache_oid($device, "hpicfIpSlaMsgResTimeout", $oids, 'HPICF-IPSLA-MIB');

print_debug_vars($oids);

foreach ($oids as $sla_index => $entry) {

    $data = [
      'device_id'  => $device['device_id'],
      'sla_mib'    => 'HPICF-IPSLA-MIB',
      'sla_index'  => $sla_index,
      //'sla_owner'  => $entry['rttMonCtrlAdminOwner'],
      //'sla_tag'    => $entry['rttMonCtrlAdminTag'],
      'rtt_type'   => $entry['hpicfIpSlaType'],         // Possible: icmpEcho(1), udpEcho(2), udpJitter(3), udpJitterVoIP(4),
      //           tcpConnect(5), dhcp(6), dns(7)
      'sla_status' => $entry['hpicfIpSlaRowStatus'],  // Possible: active, notInService, notReady, createAndGo, createAndWait, destroy
      'deleted'    => 0,
    ];

    // Use jitter or simple echo graph for SLA
    if (str_icontains_array($data['rtt_type'], 'jitter')) {
        $data['sla_graph'] = 'jitter';
    } else {
        $data['sla_graph'] = 'echo';
    }

    // Target
    switch ($entry['hpicfIpSlaDestAddressType']) {
        case 'ipv4':
        case 'ipv6':
            $data['sla_target'] = hex2ip($entry['hpicfIpSlaDestAddress']);
            break;
        case 'ipv4z':
        case 'ipv6z':
            // Not tested
            $data['sla_target'] = hex2ip($entry['hpicfIpSlaDestAddress']);
            break;
        case 'dns':
            [$data['sla_tag'], , $data['sla_target']] = explode("\n", snmp_hexstring($entry['hpicfIpSlaDestAddress']));
            break;
        default:
            if ($entry['hpicfIpSlaType'] == 'dhcp') {
                $data['sla_target'] = 'Interface ' . $entry['hpicfIpSlaSourceInterface'];
            } else {
                $data['sla_target'] = snmp_hexstring($entry['hpicfIpSlaDestAddress']);
            }
            break;
    }

    // Some fallbacks for when the tag is empty
    if (!$data['sla_tag']) {
        $data['sla_tag'] = $data['sla_target'];
    }

    // Limits
    $data['sla_limit_high']      = ($entry['hpicfIpSlaMsgResTimeout'] > 0 ? $entry['hpicfIpSlaMsgResTimeout'] : 5000);
    $data['sla_limit_high_warn'] = (int)($data['sla_limit_high'] / 5);

    $sla_table['HPICF-IPSLA-MIB'][$sla_index] = $data; // Pull to array for main processing
}

// EOF
