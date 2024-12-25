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
 */

/**
 * JUNIPER-IPv6-MIB::jnxIpv6StatsReceives.0 = Counter64: 524792518
 * JUNIPER-IPv6-MIB::jnxIpv6StatsTooShorts.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsTooSmalls.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsBadOptions.0 = Counter64: 217
 * JUNIPER-IPv6-MIB::jnxIpv6StatsBadVersions.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsFragments.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsFragDrops.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsFragTimeOuts.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsFragOverFlows.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsReasmOKs.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsDelivers.0 = Counter64: 522038062
 * JUNIPER-IPv6-MIB::jnxIpv6StatsForwards.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsUnreachables.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsRedirects.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsOutRequests.0 = Counter64: 525551974
 * JUNIPER-IPv6-MIB::jnxIpv6StatsRawOuts.0 = Counter64: 956
 * JUNIPER-IPv6-MIB::jnxIpv6StatsOutDiscards.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsOutNoRoutes.0 = Counter64: 164
 * JUNIPER-IPv6-MIB::jnxIpv6StatsOutFragOKs.0 = Counter64: 305
 * JUNIPER-IPv6-MIB::jnxIpv6StatsOutFragCreates.0 = Counter64: 610
 * JUNIPER-IPv6-MIB::jnxIpv6StatsOutFragFails.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsBadScopes.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsNotMcastMembers.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsHdrNotContinuous.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsNoGifs.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsTooManyHdrs.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsForwCacheHits.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsForwCacheMisses.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsOutDeadNextHops.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsOptRateDrops.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsMCNoDests.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInHopByHops.0 = Counter64: 5602
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInIcmps.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInIgmps.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInIps.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInTcps.0 = Counter64: 411996877
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInUdps.0 = Counter64: 2516803
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInIdps.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInTps.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInIpv6s.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInRoutings.0 = Counter64: 373
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInFrags.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInEsps.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInAhs.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInIcmpv6s.0 = Counter64: 107929073
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInNoNhs.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInDestOpts.0 = Counter64: 90
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInIsoIps.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInOspfs.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInEths.0 = Counter64: 0
 * JUNIPER-IPv6-MIB::jnxIpv6StatsInPims.0 = Counter64: 0
 */

$table_defs['JUNIPER-IPv6-MIB']['jnxIpv6GlobalStats'] = array(
  'table'     => 'jnxIpv6GlobalStats',
  'numeric'   => '.1.3.6.1.4.1.2636.3.11.1.1',
  'mib'       => 'JUNIPER-IPv6-MIB',
  'descr'     => 'Juniper IPv6 Global Statistics',
  'graphs'    => array('jnxIpv6GlobalPkts'),
  'ds_rename' => array('jnxIpv6Stats' => ''),
  'oids'      => array(
    'jnxIpv6StatsReceives'         => array('numeric' => '1', 'descr' => ''),
    'jnxIpv6StatsTooShorts'        => array('numeric' => '2', 'descr' => ''),
    'jnxIpv6StatsTooSmalls'        => array('numeric' => '3', 'descr' => ''),
    'jnxIpv6StatsBadOptions'       => array('numeric' => '4', 'descr' => ''),
    'jnxIpv6StatsBadVersions'      => array('numeric' => '5', 'descr' => ''),
    'jnxIpv6StatsFragments'        => array('numeric' => '6', 'descr' => ''),
    'jnxIpv6StatsFragDrops'        => array('numeric' => '7', 'descr' => ''),
    'jnxIpv6StatsFragTimeOuts'     => array('numeric' => '8', 'descr' => ''),
    'jnxIpv6StatsFragOverFlows'    => array('numeric' => '9', 'descr' => ''),
    'jnxIpv6StatsReasmOKs'         => array('numeric' => '10', 'descr' => ''),
    'jnxIpv6StatsDelivers'         => array('numeric' => '11', 'descr' => ''),
    'jnxIpv6StatsForwards'         => array('numeric' => '12', 'descr' => ''),
    'jnxIpv6StatsUnreachables'     => array('numeric' => '13', 'descr' => ''),
    'jnxIpv6StatsRedirects'        => array('numeric' => '14', 'descr' => ''),
    'jnxIpv6StatsOutRequests'      => array('numeric' => '15', 'descr' => ''),
    'jnxIpv6StatsRawOuts'          => array('numeric' => '16', 'descr' => ''),
    'jnxIpv6StatsOutDiscards'      => array('numeric' => '17', 'descr' => ''),
    'jnxIpv6StatsOutNoRoutes'      => array('numeric' => '18', 'descr' => ''),
    'jnxIpv6StatsOutFragOKs'       => array('numeric' => '19', 'descr' => ''),
    'jnxIpv6StatsOutFragCreates'   => array('numeric' => '20', 'descr' => ''),
    'jnxIpv6StatsOutFragFails'     => array('numeric' => '21', 'descr' => ''),
    'jnxIpv6StatsBadScopes'        => array('numeric' => '22', 'descr' => ''),
    'jnxIpv6StatsNotMcastMembers'  => array('numeric' => '23', 'descr' => ''),
    'jnxIpv6StatsHdrNotContinuous' => array('numeric' => '24', 'descr' => ''),
    'jnxIpv6StatsNoGifs'           => array('numeric' => '25', 'descr' => ''),
    'jnxIpv6StatsTooManyHdrs'      => array('numeric' => '26', 'descr' => ''),
    'jnxIpv6StatsForwCacheHits'    => array('numeric' => '27', 'descr' => ''),
    'jnxIpv6StatsForwCacheMisses'  => array('numeric' => '28', 'descr' => ''),
    'jnxIpv6StatsOutDeadNextHops'  => array('numeric' => '29', 'descr' => ''),
    'jnxIpv6StatsOptRateDrops'     => array('numeric' => '30', 'descr' => ''),
    'jnxIpv6StatsMCNoDests'        => array('numeric' => '31', 'descr' => ''),
    'jnxIpv6StatsInHopByHops'      => array('numeric' => '32', 'descr' => ''),
    'jnxIpv6StatsInIcmps'          => array('numeric' => '33', 'descr' => ''),
    'jnxIpv6StatsInIgmps'          => array('numeric' => '34', 'descr' => ''),
    'jnxIpv6StatsInIps'            => array('numeric' => '35', 'descr' => ''),
    'jnxIpv6StatsInTcps'           => array('numeric' => '36', 'descr' => ''),
    'jnxIpv6StatsInUdps'           => array('numeric' => '37', 'descr' => ''),
    'jnxIpv6StatsInIdps'           => array('numeric' => '38', 'descr' => ''),
    'jnxIpv6StatsInTps'            => array('numeric' => '39', 'descr' => ''),
    'jnxIpv6StatsInIpv6s'          => array('numeric' => '40', 'descr' => ''),
    'jnxIpv6StatsInRoutings'       => array('numeric' => '41', 'descr' => ''),
    'jnxIpv6StatsInFrags'          => array('numeric' => '42', 'descr' => ''),
    'jnxIpv6StatsInEsps'           => array('numeric' => '43', 'descr' => ''),
    'jnxIpv6StatsInAhs'            => array('numeric' => '44', 'descr' => ''),
    'jnxIpv6StatsInIcmpv6s'        => array('numeric' => '45', 'descr' => ''),
    'jnxIpv6StatsInNoNhs'          => array('numeric' => '46', 'descr' => ''),
    'jnxIpv6StatsInDestOpts'       => array('numeric' => '47', 'descr' => ''),
    'jnxIpv6StatsInIsoIps'         => array('numeric' => '48', 'descr' => ''),
    'jnxIpv6StatsInOspfs'          => array('numeric' => '49', 'descr' => ''),
    'jnxIpv6StatsInEths'           => array('numeric' => '50', 'descr' => ''),
    'jnxIpv6StatsInPims'           => array('numeric' => '51', 'descr' => ''),
  )
);