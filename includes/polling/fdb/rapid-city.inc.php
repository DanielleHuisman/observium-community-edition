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

// Exit if already founded fdb entries
if (count($fdbs)) { return; }

/*
RAPID-CITY::rcBridgeNewFdbStatus[0:1:59:2:36:40][6] = learned
RAPID-CITY::rcBridgeNewFdbPort[0:1:59:2:36:40][6] = 662
*/

$entries = snmpwalk_cache_twopart_oid($device, 'rcBridgeNewFdbPort', array(), 'RAPID-CITY',  NULL, OBS_SNMP_ALL_TABLE);

if (snmp_status())
{
  $entries = snmpwalk_cache_twopart_oid($device, 'rcBridgeNewFdbStatus', $entries, 'RAPID-CITY',  NULL, OBS_SNMP_ALL_TABLE);
  print_debug_vars($entries);

  foreach($entries as $mac => $data1)
  {
    foreach ($data1 as $vlan => $entry)
    {

      // Make sure the ifIndex is actually valid
      if ($entry['rcBridgeNewFdbPort'] != 0 && is_array($port_ifIndex_table[$entry['rcBridgeNewFdbPort']]))
      {
        $port = $port_ifIndex_table[$entry['rcBridgeNewFdbPort']];

        $mac = mac_zeropad($mac);

        $data = array();

        $data['port_id']    = $port['port_id'];
        $data['port_index'] = $entry['rcBridgeNewFdbPort'];
        $data['fdb_status'] = $entry['rcBridgeNewFdbStatus'];

        $fdbs[$vlan][$mac] = $data;

      }

    }
  }
}

unset($entries);

// EOF