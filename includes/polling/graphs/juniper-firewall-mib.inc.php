<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */


$fws = snmpwalk_cache_threepart_oid($device, "jnxFWCounterPacketCount", [], "JUNIPER-FIREWALL-MIB");
if (!safe_empty($fws)) {
    $fws = snmpwalk_cache_threepart_oid($device, "jnxFWCounterByteCount", $fws, "JUNIPER-FIREWALL-MIB");

    $pkts  = 'jnxFWCounterPacketCount';
    $bytes = 'jnxFWCounterByteCount';
}

print_debug_vars($fws);

/*
else
{
  $fws = snmpwalk_cache_twopart_oid($device, "jnxFWPackets", $fws, "JUNIPER-FIREWALL-MIB");

  if (count($fws))
  {
    $fws = snmpwalk_cache_twopart_oid($device, "jnxFWBytes", $fws, "JUNIPER-FIREWALL-MIB");

    $pkts  = 'jnxFWPackets';
    $bytes = 'jnxFWBytes';

  }
}
*/

echo("Juniper Firewall Counters");

if (!safe_empty($fws)) {
    foreach ($fws as $filter => $counters) {
        foreach ($counters as $counter => $types) {
            foreach ($types as $type => $data) {
                rrdtool_update_ng($device, 'juniper-firewall', [
                  'pkts'  => $data[$pkts],
                  'bytes' => $data[$bytes],
                ],                $filter . '-' . $counter . '-' . $type
                );
            }
        }
    }
}

echo("\n");

unset($fws, $filter, $counters, $counter, $data);

// EOF
