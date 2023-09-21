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

if ($device['os'] == 'arista_eos') // FIXME mib based! => graphs dir?
{
    echo('ARISTA-SW-IP-FORWARDING');

    $data = snmpwalk_cache_oid($device, 'aristaSwFwdIpStatsTable', [], 'ARISTA-SW-IP-FORWARDING-MIB');

    // Not doing "$data as $ipver => $data" to be sure we don't get unexpected AFs other than what we know
    foreach (['ipv4', 'ipv6'] as $ipver) {
        if (isset($data[$ipver])) {
            rrdtool_update_ng($device, "arista-netstats-sw-$ipver", [
              'InReceives'       => $data[$ipver]['aristaSwFwdIpStatsHCInReceives'],
              'InHdrErrors'      => $data[$ipver]['aristaSwFwdIpStatsInHdrErrors'],
              'InNoRoutes'       => $data[$ipver]['aristaSwFwdIpStatsInNoRoutes'],
              'InAddrErrors'     => $data[$ipver]['aristaSwFwdIpStatsInAddrErrors'],
              'InUnknownProtos'  => $data[$ipver]['aristaSwFwdIpStatsInUnknownProtos'],
              'InTruncatedPkts'  => $data[$ipver]['aristaSwFwdIpStatsInTruncatedPkts'],
              'InForwDatagrams'  => $data[$ipver]['aristaSwFwdIpStatsHCInForwDatagrams'],
              'ReasmReqds'       => $data[$ipver]['aristaSwFwdIpStatsReasmReqds'],
              'ReasmOKs'         => $data[$ipver]['aristaSwFwdIpStatsReasmOKs'],
              'ReasmFails'       => $data[$ipver]['aristaSwFwdIpStatsReasmFails'],
              'OutNoRoutes'      => $data[$ipver]['aristaSwFwdIpStatsOutNoRoutes'],
              'OutForwDatagrams' => $data[$ipver]['aristaSwFwdIpStatsHCOutForwDatagrams'],
              'OutDiscards'      => $data[$ipver]['aristaSwFwdIpStatsOutDiscards'],
              'OutFragReqds'     => $data[$ipver]['aristaSwFwdIpStatsOutFragReqds'],
              'OutFragOKs'       => $data[$ipver]['aristaSwFwdIpStatsOutFragOKs'],
              'OutFragFails'     => $data[$ipver]['aristaSwFwdIpStatsOutFragFails'],
              'OutFragCreates'   => $data[$ipver]['aristaSwFwdIpStatsOutFragCreates'],
              'OutTransmits'     => $data[$ipver]['aristaSwFwdIpStatsHCOutTransmits'],
            ]);
        }
    }

    if (isset($data['ipv4'])) {
        $graphs['netstat_arista_sw_ip']      = TRUE;
        $graphs['netstat_arista_sw_ip_frag'] = TRUE;
    }

    if (isset($data['ipv6'])) {
        $graphs['netstat_arista_sw_ip6']      = TRUE;
        $graphs['netstat_arista_sw_ip6_frag'] = TRUE;
    }

    unset($data, $ipver);
}

// EOF
