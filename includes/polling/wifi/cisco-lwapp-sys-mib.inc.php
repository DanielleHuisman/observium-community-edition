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

// CISCO-LWAPP-SYS-MIB::clsSysMaxClients.0 = Gauge32: 7000
// CISCO-LWAPP-SYS-MIB::clsMaxClientsCount.0 = Gauge32: 53
// CISCO-LWAPP-SYS-MIB::clsSysApConnectCount.0 = Gauge32: 22

$wifi_ap_count = snmp_get($device, 'clsSysApConnectCount.0', '-OUqnv', 'CISCO-LWAPP-SYS-MIB'); // This is AP count
$wificlients1  = snmp_get($device, 'clsMaxClientsCount.0', '-OUqnv', 'CISCO-LWAPP-SYS-MIB');

if (!is_numeric($wificlients1))
{
  unset($wificlients1);
}

// EOF
