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
 */

/*

* Low-capacity

IP-MIB::ipIfStatsInReceives.ipv6.49 = Counter32: 2714776
IP-MIB::ipIfStatsInOctets.ipv6.49 = Counter32: 215781800
IP-MIB::ipIfStatsInForwDatagrams.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsInDelivers.ipv6.49 = Counter32: 2716785
IP-MIB::ipIfStatsOutRequests.ipv6.49 = Counter32: 92891507
IP-MIB::ipIfStatsOutForwDatagrams.ipv6.49 = Counter32: 48740534
IP-MIB::ipIfStatsOutOctets.ipv6.49 = Counter32: 1301522241
IP-MIB::ipIfStatsInMcastPkts.ipv6.49 = Counter32: 545375
IP-MIB::ipIfStatsInMcastOctets.ipv6.49 = Counter32: 56715392
IP-MIB::ipIfStatsOutMcastPkts.ipv6.49 = Counter32: 10887710
IP-MIB::ipIfStatsOutMcastOctets.ipv6.49 = Counter32: 843158624
IP-MIB::ipIfStatsInBcastPkts.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsOutBcastPkts.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsOutTransmits.ipv6.49 = Counter32: 92911664

* High-capacity

IP-MIB::ipIfStatsHCInOctets.ipv6.49 = Counter64: 215781800
IP-MIB::ipIfStatsHCOutOctets.ipv6.49 = Counter64: 18481391425

IP-MIB::ipIfStatsHCInMcastPkts.ipv6.49 = Counter64: 545375
IP-MIB::ipIfStatsHCInMcastOctets.ipv6.49 = Counter64: 56715392
IP-MIB::ipIfStatsHCOutMcastPkts.ipv6.49 = Counter64: 10887710
IP-MIB::ipIfStatsHCOutMcastOctets.ipv6.49 = Counter64: 843158624
IP-MIB::ipIfStatsHCInBcastPkts.ipv6.49 = Counter64: 0
IP-MIB::ipIfStatsHCOutBcastPkts.ipv6.49 = Counter64: 0

IP-MIB::ipIfStatsHCInReceives.ipv6.49 = Counter64: 2714776
IP-MIB::ipIfStatsHCInForwDatagrams.ipv6.49 = Counter64: 0
IP-MIB::ipIfStatsHCInDelivers.ipv6.49 = Counter64: 2716785
IP-MIB::ipIfStatsHCOutRequests.ipv6.49 = Counter64: 92891507
IP-MIB::ipIfStatsHCOutForwDatagrams.ipv6.49 = Counter64: 48740534
IP-MIB::ipIfStatsHCOutTransmits.ipv6.49 = Counter64: 92911664

IP-MIB::ipIfStatsDiscontinuityTime.ipv6.49 = Timeticks: (0) 0:00:00.00

* Low-capacity ONLY

IP-MIB::ipIfStatsInHdrErrors.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsInNoRoutes.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsInAddrErrors.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsInUnknownProtos.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsInTruncatedPkts.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsReasmReqds.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsReasmOKs.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsReasmFails.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsInDiscards.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsOutDiscards.ipv6.49 = Counter32: 21576
IP-MIB::ipIfStatsOutFragReqds.ipv6.49 = Counter32: 20062
IP-MIB::ipIfStatsOutFragOKs.ipv6.49 = Counter32: 20062
IP-MIB::ipIfStatsOutFragFails.ipv6.49 = Counter32: 0
IP-MIB::ipIfStatsOutFragCreates.ipv6.49 = Counter32: 40124


*/

// IP-MIB ipIfStats

$port_module = 'ipifstats';

if ($ports_modules[$port_module])
{
  echo("IP-MIB ipIfStats ");

  $oids = array('ipIfStatsHCInOctets', 'ipIfStatsHCOutOctets'); // Bits

  if(FALSE) { $oids = array_merge($oids, array('ipIfStatsHCOutTransmits', 'ipIfStatsHCInReceives')); } // Pkts

  if(FALSE) { $oids = array_merge($oids, array('ipIfStatsHCInMcastPkts', 'ipIfStatsHCInMcastOctets', 'ipIfStatsHCOutMcastPkts', 'ipIfStatsHCOutMcastOctets', 'ipIfStatsHCInBcastPkts',
                 'ipIfStatsHCOutBcastPkts', 'ipIfStatsHCInForwDatagrams', 'ipIfStatsHCInDelivers', 'ipIfStatsHCOutRequests',
                 'ipIfStatsHCOutForwDatagrams' )); } // Stats

  $ipIfStats = array();

  foreach($oids as $oid)
  {
    $ipIfStats = snmpwalk_cache_twopart_oid($device, $oid, $ipIfStats, "IP-MIB", NULL, OBS_SNMP_ALL_TABLE);
  }

  foreach ($ipIfStats as $af => $af_ports)
  {
    foreach($af_ports as $af_port_id => $af_port)
    {
      $port_stats[$af_port_id]['ipIfStats'][$af] = $af_port;
    }
  }

  //print_r($port_stats);

  $process_port_functions[$port_module] = $GLOBALS['snmp_status'];
}


