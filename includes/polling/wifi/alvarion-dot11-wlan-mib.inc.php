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

$wificlients1 = snmp_get($device, 'brzaccVLCurrentNumOfAssociations.0', '-OUqnv', 'ALVARION-DOT11-WLAN-MIB');

if (!is_numeric($wificlients1))
{
  $aus = snmpwalk_cache_multi_oid($device, 'brzaccVLAssociatedAU', array(), 'ALVARION-DOT11-WLAN-MIB');
  if (is_array($aus)) { $wificlients1 = count($aus); }
}

// EOF
