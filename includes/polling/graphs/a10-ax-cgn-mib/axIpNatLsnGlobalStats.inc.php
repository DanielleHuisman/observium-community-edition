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

$table_defs['A10-AX-CGN-MIB']['axIpNatLsnStats'] = array (
  'table'      => 'axIpNatLsnGlobalStats',
  'numeric'    => '.1.3.6.1.4.1.22610.2.4.3.18.4.1',
  'mib'        => 'A10-AX-CGN-MIB',
  'mib_dir'    => 'a10',
//  'file'       => 'axAppGlobalStats.rrd',
  'descr'      => 'A10 CGN LSN Global Statistics',
  'graphs'     => array('axIpNatLsnTotalUserQuotaSessions', 'axIpNatLsnTotalIpAddrTranslated', 'axIpNatLsnTotalFullConeSessions',
                        'axIpNatLsnTrafficStatsFC', 'axIpNatLsnTrafficStatsHP', 'axIpNatLsnTrafficStatsEPI', 'axIpNatLsnTrafficStatsUQ',
                        'axIpNatLsnTrafficStatsUQex', 'axIpNatLsnNatPortUsageStats'), 
  'ds_rename'  => array('axIpNatLsn' => '', 'Endpoint' => 'Ep', 'Extended' => 'Ex', 'UserQuotas' => 'UQ', 'Total' => 'Tot', 'Traffic' => 'Tr', 'FullCone' => 'FC', 'Sessions' => 'Se','Session' => 'Se',
                        'TcpNatPort' => 'TcpNP', 'UdpNatPort' => 'UdpNP', 'Match' => 'M'),
  'oids'       => array(
    'axIpNatLsnTotalUserQuotaSessions'                =>  array('numeric' => '1',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'axIpNatLsnTotalIpAddrTranslated'                 =>  array('numeric' => '2',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'axIpNatLsnTotalFullConeSessions'                 =>  array('numeric' => '3',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    
    'axIpNatLsnTrafficFullConeSessionCreated'         =>  array('numeric' => '4',  'descr' => ''),
    'axIpNatLsnTrafficFullConeSessionFreed'           =>  array('numeric' => '5',  'descr' => ''),
    'axIpNatLsnTrafficFailsInFullConeSessionCreation' =>  array('numeric' => '6',  'descr' => ''),
    'axIpNatLsnTrafficHairpinSessionCreated'          =>  array('numeric' => '7',  'descr' => ''),
    'axIpNatLsnTrafficEndpointIndepMapMatch'          =>  array('numeric' => '8',  'descr' => ''),
    'axIpNatLsnTrafficEndpointIndepFilterMatch'       =>  array('numeric' => '9',  'descr' => ''),
    'axIpNatLsnTrafficUserQuotasCreated'              =>  array('numeric' => '10',  'descr' => ''),
    'axIpNatLsnTrafficUserQuotasFreed'                =>  array('numeric' => '11',  'descr' => ''),
    'axIpNatLsnTrafficFailsInUserQuotasCreation'      =>  array('numeric' => '12',  'descr' => ''),
    'axIpNatLsnTrafficIcmpUserQuotasExceeded'         =>  array('numeric' => '13',  'descr' => ''),
    'axIpNatLsnTrafficUdpUserQuotasExceeded'          =>  array('numeric' => '14',  'descr' => ''),
    'axIpNatLsnTrafficTcpUserQuotasExceeded'          =>  array('numeric' => '15',  'descr' => ''),
    'axIpNatLsnTrafficExtendedUserQuotasMatch'        =>  array('numeric' => '16',  'descr' => ''),
    'axIpNatLsnTrafficExtendedUserQuotasExceeded'     =>  array('numeric' => '17',  'descr' => ''),
    'axIpNatLsnTrafficNatPortUnavailable'             =>  array('numeric' => '18',  'descr' => ''),
    'axIpNatLsnTrafficNewUserResourceUnavailable'     =>  array('numeric' => '19',  'descr' => ''),
    'axIpNatLsnSessionDataSessionsUsed'               =>  array('numeric' => '20',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'axIpNatLsnSessionDataSessionsFree'               =>  array('numeric' => '21',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'axIpNatLsnSessionSmpSessionsUsed'                =>  array('numeric' => '22',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'axIpNatLsnSessionSmpSessionsFree'                =>  array('numeric' => '23',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'axIpNatLsnNatPortTcpNatPortUsed'                 =>  array('numeric' => '24',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'axIpNatLsnNatPortTcpNatPortFree'                 =>  array('numeric' => '25',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'axIpNatLsnNatPortUdpNatPortUsed'                 =>  array('numeric' => '26',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'axIpNatLsnNatPortUdpNatPortFree'                 =>  array('numeric' => '27',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),

  )
);

?>


