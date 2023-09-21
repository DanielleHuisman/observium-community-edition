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

// F5-BIGIP-SYSTEM-MIB sysGlobalClientSslStat
//
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatCurConns.0 = Counter64: 2376
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatMaxConns.0 = Counter64: 10360
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatCurNativeConns.0 = Counter64: 2351
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatMaxNativeConns.0 = Counter64: 10252
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatTotNativeConns.0 = Counter64: 241684205
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatCurCompatConns.0 = Counter64: 0
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatMaxCompatConns.0 = Counter64: 0
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatTotCompatConns.0 = Counter64: 0
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatSslv2.0 = Counter64: 0
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatSslv3.0 = Counter64: 81589
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatTlsv1.0 = Counter64: 53907948
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatTlsv11.0 = Counter64: 2800169
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatTlsv12.0 = Counter64: 184894499
// F5-BIGIP-SYSTEM-MIB::sysClientsslStatDtlsv1.0 = Counter64: 0

$table_defs['F5-BIGIP-SYSTEM-MIB']['clientssl'] = [
  'file'          => 'clientssl.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'F5-BIGIP-SYSTEM-MIB',
  'mib_dir'       => 'f5',
  'table'         => 'sysGlobalClientSslStat',
  'ds_rename'     => [
    'sysClientsslStat' => '',
  ],
  'graphs'        => ['f5_clientssl_conns', 'f5_clientssl_vers'],
  'oids'          => [
    'sysClientsslStatCurConns'       => ['descr' => 'Current Connections', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'sysClientsslStatCurNativeConns' => ['descr' => 'Current Native Connections', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'sysClientsslStatCurCompatConns' => ['descr' => 'Current Compat Connections', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'sysClientsslStatSslv2'          => ['descr' => 'Current SSLv2 Connections', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'sysClientsslStatSslv3'          => ['descr' => 'Current SSLv3 Connections', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'sysClientsslStatTlsv1'          => ['descr' => 'Current TLSv1.0 Connections', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'sysClientsslStatTlsv11'         => ['descr' => 'Current TLSv1.1 Connections', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'sysClientsslStatTlsv12'         => ['descr' => 'Current TLSv1.2 Connections', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'sysClientsslStatDtlsv1'         => ['descr' => 'Current DTLSv1 Connections', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'sysClientsslStatTotNativeConns' => ['descr' => 'Current Native Connections', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'sysClientsslStatTotCompatConns' => ['descr' => 'Current Compat Connections', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
  ]
];

// EOF
