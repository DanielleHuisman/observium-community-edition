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

#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasNumSessions.0 = Gauge32: 7 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasNumPrevSessions.0 = Counter32: 22 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasNumUsers.0 = Gauge32: 7 Users
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasNumGroups.0 = Gauge32: 0 Groups
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasGlobalInPkts.0 = Counter64: 0 Packets
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasGlobalOutPkts.0 = Counter64: 0 Packets
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasGlobalInOctets.0 = Counter64: 0 Octets
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasGlobalInDecompOctets.0 = Counter64: 0 Octets
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasGlobalOutOctets.0 = Counter64: 0 Octets
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasGlobalOutUncompOctets.0 = Counter64: 0 Octets
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasGlobalInDropPkts.0 = Counter64: 0 Packets
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasGlobalOutDropPkts.0 = Counter64: 0 Packets
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasEmailNumSessions.0 = Gauge32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasEmailCumulateSessions.0 = Counter32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasEmailPeakConcurrentSessions.0 = Gauge32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasIPSecNumSessions.0 = Gauge32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasIPSecCumulateSessions.0 = Counter32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasIPSecPeakConcurrentSessions.0 = Gauge32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasL2LNumSessions.0 = Gauge32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasL2LCumulateSessions.0 = Counter32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasL2LPeakConcurrentSessions.0 = Gauge32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasLBNumSessions.0 = Gauge32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasLBCumulateSessions.0 = Counter32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasLBPeakConcurrentSessions.0 = Gauge32: 0 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasSVCNumSessions.0 = Gauge32: 7 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasSVCCumulateSessions.0 = Counter32: 53 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasSVCPeakConcurrentSessions.0 = Gauge32: 9 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasWebvpnNumSessions.0 = Gauge32: 7 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasWebvpnCumulateSessions.0 = Counter32: 29 Sessions
#CISCO-REMOTE-ACCESS-MONITOR-MIB::crasWebvpnPeakConcurrentSessions.0 = Gauge32: 9 Sessions

// FIXME. Candidate for migrate to graphs module with table_collect()
if (is_device_mib($device, 'CISCO-REMOTE-ACCESS-MONITOR-MIB')) {
    $oid_list = "crasEmailNumSessions.0 crasIPSecNumSessions.0 crasL2LNumSessions.0 crasLBNumSessions.0 crasSVCNumSessions.0 crasWebvpnNumSessions.0";
    $data     = snmp_get_multi_oid($device, $oid_list, [], "CISCO-REMOTE-ACCESS-MONITOR-MIB");
    $data     = $data[0];

    if ($data['crasEmailNumSessions'] || $data['crasIPSecNumSessions'] || $data['crasL2LNumSessions'] || $data['crasLBNumSessions'] || $data['crasSVCNumSessions'] || $data['crasWebvpnSessions']) {
        rrdtool_update_ng($device, 'cisco-cras-sessions', [
          'email'  => $data['crasEmailNumSessions'],
          'ipsec'  => $data['crasIPSecNumSessions'],
          'l2l'    => $data['crasL2LNumSessions'],
          'lb'     => $data['crasLBNumSessions'],
          'svc'    => $data['crasSVCNumSessions'],
          'webvpn' => $data['crasWebvpnNumSessions'],
        ]);

        $graphs['cras_sessions'] = TRUE;
        echo(" CRAS Sessions");
    }

    unset($data);
}

// EOF
