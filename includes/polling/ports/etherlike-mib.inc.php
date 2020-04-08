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

// EtherLike-MIB stats

$port_module = 'etherlike';
// If etherlike extended error statistics are enabled, walk dot3StatsEntry else only dot3StatsDuplexStatus.
if ($ports_modules[$port_module])
{
  echo("dot3Stats ");
  $start = microtime(TRUE);

  $port_stats = snmpwalk_cache_oid($device, "dot3StatsEntry", $port_stats, "EtherLike-MIB");
  $process_port_functions[$port_module] = snmp_status();

  $device_state['poller_ports_perf'][$port_module] += microtime(TRUE) - $start; // Module timing
}
else if ($has_ifEntry)
{
  echo("dot3StatsDuplexStatus ");
  $port_stats = snmpwalk_cache_oid($device, "dot3StatsDuplexStatus", $port_stats, "EtherLike-MIB");
  $process_port_functions[$port_module] = snmp_status();
}

// Additional db fields for update
//$process_port_db[$port_module][] = 'ifDuplex'; // this field used in main data fields

// EOF
