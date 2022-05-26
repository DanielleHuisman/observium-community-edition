<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

$table_defs['NS-ROOT-MIB']['nsAAAStatsGroup'] = array(
  'table'     => 'nsAAAStatsGroup',
  'numeric'   => '.1.3.6.1.4.1.5951.4.1.1.67',
  'mib'       => 'NS-ROOT-MIB',
  'mib_dir'   => 'citrix',
  'file'      => 'nsAAAStatsGroup.rrd',
  'descr'     => 'Netscaler AAA Statistics',
  'graphs'    => array('nsAAAAuths', 'nsAAASessions', 'nsAAAICAConnections'),
  'ds_rename' => array('aaa' => ''),
  'oids'      => array(
    'aaaAuthFail'                => array('numeric' => '1',  'descr' => 'Count of authentication failures.',                           'ds_min' => '0'),
    'aaaAuthSuccess'             => array('numeric' => '2',  'descr' => 'Count of authentication successes.',                          'ds_min' => '0'),
    'aaaAuthNonHttpFail'         => array('numeric' => '3',  'descr' => 'Count of non HTTP connections that failed authorization.',    'ds_min' => '0'),
    'aaaAuthOnlyHttpFail'        => array('numeric' => '4',  'descr' => 'Count of HTTP connections that failed authorization.',        'ds_min' => '0'),
    'aaaAuthNonHttpSuccess'      => array('numeric' => '5',  'descr' => 'Count of non HTTP connections that succeeded authorization.', 'ds_min' => '0'),
    'aaaAuthOnlyHttpSuccess'     => array('numeric' => '6',  'descr' => 'Count of HTTP connections that succeeded authorization.',     'ds_min' => '0'),
    'aaaTotSessions'             => array('numeric' => '7',  'descr' => 'Count of all SmartAccess AAA sessions.',                      'ds_min' => '0'),
    'aaaTotSessionTimeout'       => array('numeric' => '8',  'descr' => 'Count of AAA sessions that have timed out.',                  'ds_min' => '0'),
    'aaaCurSessions'             => array('numeric' => '9',  'descr' => 'Count of current SmartAccess AAA sessions.',                  'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'aaaCurICASessions'          => array('numeric' => '10', 'descr' => 'Count of current Basic ICA only sessions.',                   'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'aaaCurTMSessions'           => array('numeric' => '11', 'descr' => 'Count of current AAATM sessions.',                            'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'aaaTotTMSessions'           => array('numeric' => '12', 'descr' => 'Count of all AAATM sessions.',                                'ds_min' => '0'),
    'aaaCurICAOnlyConn'          => array('numeric' => '13', 'descr' => 'Count of current Basic ICA only connections.',                'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'aaaCurICAConn'              => array('numeric' => '14', 'descr' => 'Count of current SmartAccess ICA connections.',               'ds_type' => 'GAUGE', 'ds_min' => '0')
  )
);

// EOF
