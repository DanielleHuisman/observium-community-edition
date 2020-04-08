<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$fws = array();

$fws = snmpwalk_cache_threepart_oid($device, "jnxFWCounterPacketCount", $fws, "JUNIPER-FIREWALL-MIB");
if (count($fws))
{
  $fws = snmpwalk_cache_threepart_oid($device, "jnxFWCounterByteCount", $fws, "JUNIPER-FIREWALL-MIB");

  $pkts  = 'jnxFWCounterPacketCount';
  $bytes = 'jnxFWCounterByteCount';
}

print_r($fws);

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

if (count($fws))
{
  foreach ($fws as $filter => $counters)
  {
    foreach ($counters AS $counter => $types)
    {
      foreach ($types as $type => $data)
      {
        rrdtool_update_ng($device, 'juniper-firewall', array(
          'pkts'  => $data[$pkts],
          'bytes' => $data[$bytes],
        ), $filter . '-' . $counter .'-'.$type
        );
      }
    }
  }
}

echo("\n");

unset($fws, $filter, $counters, $counter, $data);

// EOF
