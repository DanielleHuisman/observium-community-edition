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

#alpha:/home/observium/dev# snmpbulkwalk -v2c -c XXXXX -M mibs -m CISCO-IPSEC-FLOW-MONITOR-MIB cisco.3925  cipSecGlobalStats
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalActiveTunnels.0 = Gauge32: 10
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalPreviousTunnels.0 = Counter32: 677 Phase-2 Tunnels
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalInOctets.0 = Counter32: 2063116135 Octets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalHcInOctets.0 = Counter64: 135207102311
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalInOctWraps.0 = Counter32: 31 Integral units
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalInDecompOctets.0 = Counter32: 2063116135 Octets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalHcInDecompOctets.0 = Counter64: 135207102311
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalInDecompOctWraps.0 = Counter32: 31 Integral units
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalInPkts.0 = Counter32: 779904964 Packets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalInDrops.0 = Counter32: 5 Packets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalInReplayDrops.0 = Counter32: 32 Packets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalInAuths.0 = Counter32: 779904970 Events
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalInAuthFails.0 = Counter32: 0 Failures
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalInDecrypts.0 = Counter32: 779904970 Packets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalInDecryptFails.0 = Counter32: 5 Packets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalOutOctets.0 = Counter32: 3486168696 Octets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalHcOutOctets.0 = Counter64: 544652047992
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalOutOctWraps.0 = Counter32: 126 Integral units
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalOutUncompOctets.0 = Counter32: 3486168696 Octets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalHcOutUncompOctets.0 = Counter64: 544652047992 Octets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalOutUncompOctWraps.0 = Counter32: 126 Integral units
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalOutPkts.0 = Counter32: 828696339 Packets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalOutDrops.0 = Counter32: 4520 Packets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalOutAuths.0 = Counter32: 828696339 Events
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalOutAuthFails.0 = Counter32: 0 Failures
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalOutEncrypts.0 = Counter32: 828696318 Packets
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalOutEncryptFails.0 = Counter32: 0 Failures
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalProtocolUseFails.0 = Counter32: 0 Failures
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalNoSaFails.0 = Counter32: 5 Failures
#CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecGlobalSysCapFails.0 = Counter32: 0 Failures

// FIXME. Candidate for migrate to graphs module with table_collect()
if (is_device_mib($device, 'CISCO-IPSEC-FLOW-MONITOR-MIB')) {
    $data = snmpwalk_cache_oid($device, "cipSecGlobalStats", NULL, "CISCO-IPSEC-FLOW-MONITOR-MIB");
    $data = $data[0];

    // Use HC Counters if they exist
    if (is_numeric($data['cipSecGlobalHcInOctets'])) {
        $data['cipSecGlobalInOctets'] = $data['cipSecGlobalHcInOctets'];
    }
    if (is_numeric($data['cipSecGlobalHcOutOctets'])) {
        $data['cipSecGlobalOutOctets'] = $data['cipSecGlobalHcOutOctets'];
    }
    if (is_numeric($data['cipSecGlobalHcInDecompOctets'])) {
        $data['cipSecGlobalInDecompOctets'] = $data['cipSecGlobalHcInDecompOctets'];
    }
    if (is_numeric($data['cipSecGlobalHcOutUncompOctets'])) {
        $data['cipSecGlobalOutUncompOctets'] = $data['cipSecGlobalHcOutUncompOctets'];
    }

    if ($data['cipSecGlobalActiveTunnels']) {
        rrdtool_update_ng($device, 'cipsec-flow', [
          'Tunnels'          => $data['cipSecGlobalActiveTunnels'],
          'InOctets'         => $data['cipSecGlobalInOctets'],
          'OutOctets'        => $data['cipSecGlobalOutOctets'],
          'InDecompOctets'   => $data['cipSecGlobalInDecompOctets'],
          'OutUncompOctets'  => $data['cipSecGlobalOutUncompOctets'],
          'InPkts'           => $data['cipSecGlobalInPkts'],
          'OutPkts'          => $data['cipSecGlobalOutPkts'],
          'InDrops'          => $data['cipSecGlobalInDrops'],
          'InReplayDrops'    => $data['cipSecGlobalInReplayDrops'],
          'OutDrops'         => $data['cipSecGlobalOutDrops'],
          'InAuths'          => $data['cipSecGlobalInAuths'],
          'OutAuths'         => $data['cipSecGlobalOutAuths'],
          'InAuthFails'      => $data['cipSecGlobalInAuthFails'],
          'OutAuthFails'     => $data['cipSecGlobalOutAuthFails'],
          'InDecrypts'       => $data['cipSecGlobalInDecrypts'],
          'OutEncrypts'      => $data['cipSecGlobalOutEncrypts'],
          'InDecryptFails'   => $data['cipSecGlobalInDecryptFails'],
          'OutEncryptFails'  => $data['cipSecGlobalOutEncryptFails'],
          'ProtocolUseFails' => $data['cipSecGlobalProtocolUseFails'],
          'NoSaFails'        => $data['cipSecGlobalNoSaFails'],
          'SysCapFails'      => $data['cipSecGlobalSysCapFails'],
        ]);

        $graphs['cipsec_flow_tunnels'] = TRUE;
        $graphs['cipsec_flow_pkts']    = TRUE;
        $graphs['cipsec_flow_bits']    = TRUE;
        $graphs['cipsec_flow_stats']   = TRUE;

        echo(" cipsec_flow");
    }

    unset($data);
}

// EOF
