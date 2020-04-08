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

echo("Juniper Firewall Counters");


$fws = array();
$fws = snmpwalk_cache_threepart_oid($device, "jnxFWCounterDisplayType", $fws, "JUNIPER-FIREWALL-MIB");
if (count($fws))
{
  $oid = 'jnxFWCounterDisplayType';
}
/*
else
{
  $fws = snmpwalk_cache_twopart_oid($device, "jnxFWType", $fws, "JUNIPER-FIREWALL-MIB");
  if (count($fws))
  {
    $oid = 'jnxFWType';
  }
}
*/

$array = array();

foreach ($fws as $filter => $counters)
{
  foreach ($counters AS $counter => $types)
  {
    foreach($types as $type => $data)
    {
      $array[$filter][$counter][$type] = 1;
    }
  }
}

echo("\n");

if (count($array))
{
  set_entity_attrib('device', $device['device_id'], 'juniper-firewall-mib', json_encode($array));
}

unset($fws, $filter, $counters, $counter, $data);

// EOF

