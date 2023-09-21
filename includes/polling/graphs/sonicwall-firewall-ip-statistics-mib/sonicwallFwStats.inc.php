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

//SONICWALL-FIREWALL-IP-STATISTICS-MIB::sonicMaxConnCacheEntries.0 = Counter32: 7500
//SONICWALL-FIREWALL-IP-STATISTICS-MIB::sonicCurrentConnCacheEntries.0 = Counter32: 21

$table_defs['SONICWALL-FIREWALL-IP-STATISTICS-MIB']['sonicwallFwStats'] = [
  'call_function' => 'snmp_get_multi',
  'mib'           => 'SONICWALL-FIREWALL-IP-STATISTICS-MIB',
  'table'         => 'sonicwallFwStats', // Table sonicwallFwStats have already polled RAM and CPU oids, use snmp_get_multi_oid()
  'ds_rename'     => ['sonic' => '', 'Entries' => ''],
  'graphs'        => ['sonicwall_sessions'],
  'oids'          => [
    'sonicMaxConnCacheEntries'     => ['descr' => 'Maximum number of connection cache entries allowed through the firewall', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'sonicCurrentConnCacheEntries' => ['descr' => 'Number of active connection cache entries through the firewall', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
  ]
];

// EOF