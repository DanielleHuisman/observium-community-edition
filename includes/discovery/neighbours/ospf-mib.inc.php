<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// FIXME - this doesn't really belong here, it's not an NDP, it's :)

## OSPF-MIB::ospfNbrIpAddr.172.22.203.98.0

if ($config['autodiscovery']['ospf'] != FALSE)
{

  $ips = snmpwalk_values($device, "ospfNbrRtrId", array(), "OSPF-MIB");

  foreach ($ips as $ip)
  {
    discover_new_device($ip, 'ospf', 'OSPF', $device);
  }
} else {
}

// EOF

