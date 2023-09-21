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

$table_defs['A10-AX-CGN-MIB']['axIpNatLsnStats'] = [
  'table'     => 'axIpNatLsnGlobalStats',
  'numeric'   => '.1.3.6.1.4.1.22610.2.4.3.18.4.1',
  'mib'       => 'A10-AX-CGN-MIB',
  'mib_dir'   => 'a10',
  //  'file'       => 'axAppGlobalStats.rrd',
  'descr'     => 'A10 CGN LSN Global Statistics',
  'graphs'    => ['axIpNatLsnTotalUserQuotaSessions', 'axIpNatLsnTotalIpAddrTranslated', 'axIpNatLsnTotalFullConeSessions',
                  'axIpNatLsnTrafficStatsFC', 'axIpNatLsnTrafficStatsHP', 'axIpNatLsnTrafficStatsEPI', 'axIpNatLsnTrafficStatsUQ',
                  'axIpNatLsnTrafficStatsUQex', 'axIpNatLsnNatPortUsageStats'],
  'ds_rename' => ['axIpNatLsn' => '', 'Endpoint' => 'Ep', 'Extended' => 'Ex', 'UserQuotas' => 'UQ', 'Total' => 'Tot', 'Traffic' => 'Tr', 'FullCone' => 'FC', 'Sessions' => 'Se', 'Session' => 'Se',
                  'TcpNatPort' => 'TcpNP', 'UdpNatPort' => 'UdpNP', 'Match' => 'M'],
  'oids'      => [
    'axIpNatLsnTotalUserQuotaSessions'                => ['numeric' => '1', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'axIpNatLsnTotalIpAddrTranslated'                 => ['numeric' => '2', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'axIpNatLsnTotalFullConeSessions'                 => ['numeric' => '3', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'axIpNatLsnTrafficFullConeSessionCreated'         => ['numeric' => '4', 'descr' => ''],
    'axIpNatLsnTrafficFullConeSessionFreed'           => ['numeric' => '5', 'descr' => ''],
    'axIpNatLsnTrafficFailsInFullConeSessionCreation' => ['numeric' => '6', 'descr' => ''],
    'axIpNatLsnTrafficHairpinSessionCreated'          => ['numeric' => '7', 'descr' => ''],
    'axIpNatLsnTrafficEndpointIndepMapMatch'          => ['numeric' => '8', 'descr' => ''],
    'axIpNatLsnTrafficEndpointIndepFilterMatch'       => ['numeric' => '9', 'descr' => ''],
    'axIpNatLsnTrafficUserQuotasCreated'              => ['numeric' => '10', 'descr' => ''],
    'axIpNatLsnTrafficUserQuotasFreed'                => ['numeric' => '11', 'descr' => ''],
    'axIpNatLsnTrafficFailsInUserQuotasCreation'      => ['numeric' => '12', 'descr' => ''],
    'axIpNatLsnTrafficIcmpUserQuotasExceeded'         => ['numeric' => '13', 'descr' => ''],
    'axIpNatLsnTrafficUdpUserQuotasExceeded'          => ['numeric' => '14', 'descr' => ''],
    'axIpNatLsnTrafficTcpUserQuotasExceeded'          => ['numeric' => '15', 'descr' => ''],
    'axIpNatLsnTrafficExtendedUserQuotasMatch'        => ['numeric' => '16', 'descr' => ''],
    'axIpNatLsnTrafficExtendedUserQuotasExceeded'     => ['numeric' => '17', 'descr' => ''],
    'axIpNatLsnTrafficNatPortUnavailable'             => ['numeric' => '18', 'descr' => ''],
    'axIpNatLsnTrafficNewUserResourceUnavailable'     => ['numeric' => '19', 'descr' => ''],
    'axIpNatLsnSessionDataSessionsUsed'               => ['numeric' => '20', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'axIpNatLsnSessionDataSessionsFree'               => ['numeric' => '21', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'axIpNatLsnSessionSmpSessionsUsed'                => ['numeric' => '22', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'axIpNatLsnSessionSmpSessionsFree'                => ['numeric' => '23', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'axIpNatLsnNatPortTcpNatPortUsed'                 => ['numeric' => '24', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'axIpNatLsnNatPortTcpNatPortFree'                 => ['numeric' => '25', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'axIpNatLsnNatPortUdpNatPortUsed'                 => ['numeric' => '26', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'axIpNatLsnNatPortUdpNatPortFree'                 => ['numeric' => '27', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'],

  ]
];

?>


