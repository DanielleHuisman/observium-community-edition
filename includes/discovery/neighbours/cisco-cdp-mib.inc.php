<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

/*
CISCO-CDP-MIB::cdpCacheAddressType.1.0 = INTEGER: ip(1)
CISCO-CDP-MIB::cdpCacheAddress.1.0 = Hex-STRING: CD EB 52 C2
CISCO-CDP-MIB::cdpCacheVersion.1.0 = STRING: 2007656304
CISCO-CDP-MIB::cdpCacheDeviceId.1.0 = STRING: ASW10.SPN.WPA1.imdc.com
CISCO-CDP-MIB::cdpCacheDevicePort.1.0 = STRING: GigabitEthernet1/27
CISCO-CDP-MIB::cdpCachePlatform.1.0 = STRING: cisco WS-C4948-10GE
CISCO-CDP-MIB::cdpCacheCapabilities.1.0 = Hex-STRING: 00 00 00 29
CISCO-CDP-MIB::cdpCacheVTPMgmtDomain.1.0 = STRING: IMDC-1
CISCO-CDP-MIB::cdpCacheNativeVLAN.1.0 = INTEGER: 0
CISCO-CDP-MIB::cdpCacheDuplex.1.0 = INTEGER: unknown(1)
CISCO-CDP-MIB::cdpCacheApplianceID.1.0 = Gauge32: 0
CISCO-CDP-MIB::cdpCacheVlanID.1.0 = Gauge32: 0
CISCO-CDP-MIB::cdpCachePowerConsumption.1.0 = Gauge32: 0 milliwatts
CISCO-CDP-MIB::cdpCacheMTU.1.0 = Gauge32: 0
CISCO-CDP-MIB::cdpCacheSysName.1.0 = STRING: ASW10.SPN.WPA1.imdc.com
CISCO-CDP-MIB::cdpCacheSysObjectID.1.0 = OID: SNMPv2-SMI::zeroDotZero.0
CISCO-CDP-MIB::cdpCachePrimaryMgmtAddrType.1.0 = INTEGER: ip(1)
CISCO-CDP-MIB::cdpCachePrimaryMgmtAddr.1.0 = STRING: "205.235.82.194"
CISCO-CDP-MIB::cdpCacheSecondaryMgmtAddrType.1.0 = INTEGER: 0
CISCO-CDP-MIB::cdpCacheSecondaryMgmtAddr.1.0 = ""
CISCO-CDP-MIB::cdpCachePhysLocation.1.0 = STRING:
CISCO-CDP-MIB::cdpCacheLastChange.1.0 = Timeticks: (120) 0:00:01.20
CISCO-CDP-MIB::cdpGlobalLastChange.0 = Timeticks: (600708689) 69 days, 12:38:06.89
 */

$cdp_array = snmpwalk_cache_twopart_oid($device, "cdpCache", [], "CISCO-CDP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);

// If we get timeout error and device has 'CISCO-FLASH-MIB', sleep and try re-walk
if (snmp_status() === FALSE && snmp_error_code() == 1002 && is_device_mib($device, 'CISCO-FLASH-MIB'))
{
  print_debug('Try to re-walk "CISCO-CDP-MIB"..');
  sleep(5); // Additional sleep, see comments in includes/discovery/storage/cisco-flash-mib-inc.php
  $cdp_array = snmpwalk_cache_twopart_oid($device, "cdpCache", $cdp_array, "CISCO-CDP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
}
print_debug_vars($cdp_array);

if ($cdp_array)
{
  // fetch sysUptime for correct last change
  // $device_sysUptime = timeticks_to_sec(snmp_get_oid($device, "sysUpTime.0", "SNMPv2-MIB"));

  // Force fetch cdpCacheAddress as HEX strings!
  $cdp_array = snmpwalk_cache_twopart_oid($device, "cdpCacheAddress", $cdp_array, "CISCO-CDP-MIB", NULL, OBS_SNMP_ALL_HEX);
  foreach ($cdp_array as $ifIndex => $port_neighbours)
  {
    $port = get_port_by_index_cache($device, $ifIndex);
    //$port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ?", array($device['device_id'], $ifIndex));

    foreach ($port_neighbours as $cdpCacheDeviceIndex => $cdp_entry)
    {
      // Remote hostname
      // NOTE. cdpCacheDeviceId have undocumented limit by 40 chars!
      list($remote_hostname) = explode('(', $cdp_entry['cdpCacheDeviceId']); // Fix for Nexus CDP neighbors: <hostname>(serial number)

      $sysname_len = strlen($cdp_entry['cdpCacheSysName']);
      $hostname_len = strlen($remote_hostname);
      if (is_valid_hostname($cdp_entry['cdpCacheSysName']) &&
          $sysname_len > $hostname_len)
      {
        $remote_hostname = $cdp_entry['cdpCacheSysName'];
      }
      elseif ($sysname_len && preg_match('/^[a-f\d]{12}$/i', $cdp_entry['cdpCacheDeviceId']))
      {
        // DeviceId is mac, prefer sysName
        $remote_hostname = $cdp_entry['cdpCacheSysName'];
      }

      // Remote address
      $remote_address = hex2ip($cdp_entry['cdpCacheAddress']);

      // Last change
      /* Derp. Do not use Last change from neighbour, it's not correct for us
       * (seems as changed uptime by remote host, not possible correct calculate unixtime)
      $last_change = timeticks_to_sec($cdp_entry['cdpCacheLastChange']);
      if ($last_change > 0)
      {
        $last_change = get_time() - $device_sysUptime + $last_change;
      }
      */

      // Remote MAC on some devices
      $remote_mac = NULL;
      if (preg_match('/^[a-f\d]{12}$/i', $cdp_entry['cdpCacheDevicePort']))
      {
        $remote_mac = $cdp_entry['cdpCacheDevicePort'];
      }
      elseif (preg_match('/^(?:SEP|SIP|\w+\-)?([a-f\d]{12})$/i', $cdp_entry['cdpCacheDeviceId'], $matches))
      {
        // Meraki report mac instead hostname
        // Axis: axis-<mac>
        // Cisco IP Phone: SEP<mac>
        // SIP-<platform><mac>,
        $remote_mac = $matches[1];
      }

      // Try find remote device and check if already cached
      $remote_device_id = get_autodiscovery_device_id($device, $remote_hostname, $remote_address, $remote_mac);
      if (is_null($remote_device_id) &&                                                         // NULL - never cached in other rounds
          check_autodiscovery($remote_hostname, $remote_address)) // Check all previous autodiscovery rounds
      {
        // Neighbour never checked, try autodiscovery
        $remote_device_id = autodiscovery_device($remote_hostname, $remote_address, 'CDP', $cdp_entry['cdpCachePlatform'], $device, $port);
      }

      $remote_port_id = NULL;
      if ($remote_device_id)
      {
        $if = $cdp_entry['cdpCacheDevicePort'];

        $query = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
        $remote_port_id = dbFetchCell($query, array($if, $if, $if, $remote_device_id, 0));
        if (!$remote_port_id)
        {
          if (!is_null($remote_mac))
          {
            // By MAC
            $remote_port_id = get_port_id_by_mac($remote_device_id, $remote_mac);
          } else {
            // Try by IP
            $peer_where = generate_query_values($remote_device_id, 'device_id'); // Additional filter for include self IPs
            // Fetch all ports with peer IP and filter by UP
            if ($ids = get_entity_ids_ip_by_network('port', $remote_address, $peer_where))
            {
              $remote_port_id = $ids[0];
              //$port = get_port_by_id_cache($ids[0]);
            }
          }
        }
      }

      $neighbour = [
        'remote_port_id'  => $remote_port_id,
        'remote_hostname' => $remote_hostname,
        'remote_port'     => $cdp_entry['cdpCacheDevicePort'],
        'remote_platform' => $cdp_entry['cdpCachePlatform'],
        'remote_version'  => $cdp_entry['cdpCacheVersion'],
        'remote_address'  => $remote_address,
        //'last_change'     => $last_change
      ];
      discover_neighbour($port, 'cdp', $neighbour);
    }
  }
}

// EOF
