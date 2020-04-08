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

if ($device['type'] == 'wireless')
{
  $wificlients1 = snmp_get($device, 'cDot11ActiveWirelessClients.1', '-OUqnv', 'CISCO-DOT11-ASSOCIATION-MIB');
  $wificlients2 = snmp_get($device, 'cDot11ActiveWirelessClients.2', '-OUqnv', 'CISCO-DOT11-ASSOCIATION-MIB');
}

// EOF
