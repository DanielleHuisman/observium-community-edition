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

$mib = 'DISMAN-PING-MIB';

$flags = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;

// Additional vendor specific mibs used only for translate vendor specific RTT Types
$vendor_mibs = ['JUNIPER-PING-MIB', 'HH3C-NQA-MIB', 'HUAWEI-DISMAN-PING-MIB', 'ZHONE-DISMAN-PING-MIB']; //, 'H3C-NQA-MIB'];
$mibs        = $mib;
foreach ($vendor_mibs as $vendor_mib) {
    if (is_device_mib($device, $vendor_mib)) {
        echo("$vendor_mib ");
        $mibs .= ':' . $vendor_mib;
        break;
    }
}

$oids = snmpwalk_cache_twopart_oid($device, "pingCtlEntry", [], $mibs, NULL, $flags);
//print_vars($oids);
if (!snmp_status()) {
    return;
}

foreach ($oids as $sla_owner => $entry2) {
    foreach ($entry2 as $sla_name => $entry) {
        if (!isset($entry['pingCtlAdminStatus']) ||        // Skip additional multi-index entries from table
            ($sla_owner == 'imclinktopologypleaseignore')) // Skip this weird SLAs by HH3C-NQA-MIB
        {
            continue;
        }

        // Get full index
        $sla_index = snmp_translate('pingCtlRowStatus."' . $sla_owner . '"."' . $sla_name . '"', $mib);
        $sla_index = str_replace('.1.3.6.1.2.1.80.1.2.1.23.', '', $sla_index);

        $data = [
          'device_id'  => $device['device_id'],
          'sla_mib'    => $mib,
          'sla_index'  => $sla_name, // FIXME. Here must be $sla_index, but migrate too hard
          'sla_owner'  => $sla_owner,
          'sla_target' => $entry['pingCtlTargetAddress'],
          //'rtt_type'   => $entry['pingCtlType'],
          'sla_status' => $entry['pingCtlRowStatus'], // Possible: active, notInService, notReady, createAndGo, createAndWait, destroy
          'sla_graph'  => 'jitter', // Seems as all of this types support jitter graphs
          'deleted'    => 0,
        ];

        if ($entry['pingCtlAdminStatus'] == 'disabled') {
            // If SLA administratively disabled, exclude from polling
            $data['deleted'] = 1;
        }

        // Type conversions
        // Standard types: pingIcmpEcho, pingUdpEcho, pingSnmpQuery, pingTcpConnectionAttempt
        // Juniper types:  jnxPingIcmpTimeStamp, jnxPingHttpGet, jnxPingHttpGetMetadata, jnxPingDnsQuery, jnxPingNtpQuery, jnxPingUdpTimestamp
        // Huawei types:   hwpingUdpEcho, hwpingTcpconnect, hwpingjitter, hwpingHttp, hwpingdlsw, hwpingdhcp, hwpingftp
        // HH3C types:
        $data['rtt_type'] = str_replace(['jnxPing', 'hh3cNqa', 'hwping', 'ping'], '', $entry['pingCtlType']);

        // Tag / Target
        if (is_hex_string($entry['pingCtlTargetAddress']) ||
            stripos($data['rtt_type'], 'Echo') !== FALSE) {
            $data['sla_target'] = hex2ip($data['sla_target']);
        }
        $data['sla_tag'] = $data['sla_target'];                 // FIXME. Here must be $sla_name, but migrate too hard

        // Limits
        $data['sla_limit_high']      = $entry['pingCtlTimeOut'] > 0 ? $entry['pingCtlTimeOut'] * 1000 : 5000;
        $data['sla_limit_high_warn'] = (int)($data['sla_limit_high'] / 5);

        /*
        // Migrate old indexes
        if (isset($sla_db[$mib_lower][$sla_owner.'.'.$name]))
        {
          // Old (non numeric) indexes
          $sla_db[$mib_lower][$sla_index] = $sla_db[$mib_lower][$sla_owner.'.'.$name];
          unset($sla_db[$mib_lower][$sla_owner.'.'.$name]);
          dbUpdate(array('sla_index' => $sla_index, 'sla_mib' => $mib), 'slas', "`sla_id` = ?", array($sla_db[$mib_lower][$sla_index]['sla_id']));
        }
        */
        // Note, here used complex index (owner.index)
        $sla_table[$mib][$sla_owner . '.' . $sla_name] = $data; // Pull to array for main processing
    }
}

// EOF
