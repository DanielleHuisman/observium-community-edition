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

$ip_data = array('ipv4' => array(),
                 'ipv6' => array());
//$valid['ip-addresses'] = array();

$include_dir   = 'includes/discovery/ip-addresses';
$include_order = 'default'; // Use MIBs from default os definitions by first!

include($config['install_dir']."/includes/include-dir-mib.inc.php");

// Process IP Addresses
$table_rows = array();
$check_networks = array();
foreach (array('ipv4', 'ipv6') as $ip_version)
{
  print_debug_vars($ip_data[$ip_version]);

  // Caching old IP addresses table
  $query = 'SELECT * FROM `'.$ip_version.'_addresses`
            WHERE `device_id` = ?';
  foreach (dbFetchRows($query, array($device['device_id'])) as $entry)
  {
    if (empty($entry['ifIndex']))
    {
      // Compatibility
      $ifIndex = dbFetchCell('SELECT `ifIndex` FROM `ports` WHERE `port_id` = ? AND `deleted` = ?', array($entry['port_id'], 0));
    } else {
      $ifIndex = $entry['ifIndex'];
    }
    $old_table[$ip_version][$ifIndex][$entry[$ip_version.'_address']] = $entry;
  }
  if (!count($ip_data[$ip_version]) && !count($old_table[$ip_version]))
  {
    // Skip if walk and DB empty
    continue;
  }

  // Process founded IP addresses
  foreach ($ip_data[$ip_version] as $ifIndex => $addresses)
  {
    $port = get_port_by_index_cache($device, $ifIndex);
    if (is_array($port) && !$port['deleted'])
    {
      $port_id = $port['port_id'];
    } else {
      // Allow to store IP addresses without associated port, but ifIndex available
      // ie, Nortel/Avaya devices have hidden vlan ifIndexes
      $port_id = '0';
    }

    print_debug_vars($port);
    print_debug_vars($addresses);

    foreach ($addresses as $ip_address => $entry)
    {

      if ($ip_version == 'ipv4')
      {
        // IPv4
        if (isset($entry['prefix']))
        {
          if (!is_numeric($entry['prefix']))
          {
            if (strlen($entry['mask']))
            {
              // Yeah, passed empty prefix, but with correct mask
              $entry['prefix'] = $entry['mask'];
            } else {
              $entry['prefix'] = '32';
            }
          }
        } else {
          $entry['prefix'] = $entry['mask'];
        }
        $ip_prefix = $entry['prefix'];
        $ip_origin = $entry['origin'];
        $ip_compressed = $ip_address; // just for compat with IPv6

        if (is_ipv4_valid($ip_address, $ip_prefix) === FALSE)
        {
          print_debug("Address '$ip_compressed/$ip_prefix' skipped as invalid.");
          continue;
        }

        if (empty($entry['type']))
        {
          $entry['type'] = get_ip_type("$ip_compressed/$ip_prefix");
        }
        $addr = Net_IPv4::parseAddress($ip_address.'/'.$ip_prefix);
        $ip_cidr = $addr->bitmask;
        $ip_network = $addr->network . '/' . $ip_cidr;
        $full_address = $ip_address . '/' . $ip_cidr;

        $new_address = array('port_id'         => $port_id,
                             'ifIndex'         => $ifIndex,
                             'device_id'       => $device['device_id'],
                             'ipv4_address'    => $ip_address,
                             'ipv4_binary'     => inet_pton($ip_address),
                             'ipv4_prefixlen'  => $ip_cidr);
      } else {
        // IPv6
        if (!is_numeric($entry['prefix'])) { $entry['prefix'] = '128'; }
        $ip_prefix = $entry['prefix'];
        $ip_origin = $entry['origin'];
        $ip_compressed = Net_IPv6::compress($ip_address, TRUE);
        if (is_ipv6_valid($ip_address, $ip_prefix) === FALSE)
        {
          print_debug("Address '$ip_compressed/$ip_prefix' skipped as invalid.");
          continue;
        }
        $full_address = $ip_compressed.'/'.$ip_prefix;
        if (empty($entry['type']))
        {
          $entry['type'] = get_ip_type($full_address);
        }
        $ip_network = Net_IPv6::getNetmask($full_address) . '/' . $ip_prefix;
        //$full_compressed = $ip_compressed.'/'.$ipv6_prefixlen;
        $new_address = array('port_id'         => $port_id,
                             'ifIndex'         => $ifIndex,
                             'device_id'       => $device['device_id'],
                             'ipv6_address'    => $ip_address,
                             'ipv6_binary'     => inet_pton($ip_address),
                             'ipv6_compressed' => $ip_compressed,
                             'ipv6_prefixlen'  => $ip_prefix,
                             'ipv6_origin'     => $ip_origin);
      }

      // Check network
      $ip_network_id = dbFetchCell('SELECT `'.$ip_version.'_network_id` FROM `'.$ip_version.'_networks` WHERE `'.$ip_version.'_network` = ?', array($ip_network));
      if (empty($ip_network_id))
      {
        // Add new network
        $ip_network_id = dbInsert(array($ip_version.'_network' => $ip_network), $ip_version.'_networks');
        //echo('N');
      }
      $new_address[$ip_version.'_network_id'] = $ip_network_id;

      // Add to display table
      $table_rows[] = array($ifIndex, truncate($port['ifDescr'], 30), nicecase($ip_version), $full_address, $ip_network, $ip_origin);

      // Check IP in DB
      $update_array = array();
      if (isset($old_table[$ip_version][$ifIndex][$ip_address]))
      {
        $old_address = &$old_table[$ip_version][$ifIndex][$ip_address];
        $ip_address_id = $old_address[$ip_version.'_address_id'];
        foreach (array_keys($new_address) as $param)
        {
          if ($param == 'ipv6_binary')
          {
            // Compare decoded binary IPv6 address
            if (inet_ntop($old_address[$param]) != $new_address['ipv6_compressed']) { $update_array[$param] = $new_address[$param]; }
          }
          else if ($param == 'ipv4_binary')
          {
            // Compare decoded binary IPv4 address
            if (inet_ntop($old_address[$param]) != $new_address['ipv4_address'])    { $update_array[$param] = $new_address[$param]; }
          } else {
            // All other params as string
            if ($old_address[$param] != $new_address[$param])
            {
              $update_array[$param] = $new_address[$param];
            }
          }
        }

        $update_count = count($update_array);
        if ($update_count === 1 && (isset($update_array['ipv4_binary']) || isset($update_array['ipv6_binary'])))
        {
          // Silent update binary address after upgrade
          dbUpdate($update_array, $ip_version.'_addresses', '`'.$ip_version.'_address_id` = ?', array($old_address[$ip_version.'_address_id']));
          $GLOBALS['module_stats'][$module]['unchanged']++;
        }
        else if ($update_count)
        {
          // Updated
          dbUpdate($update_array, $ip_version.'_addresses', '`'.$ip_version.'_address_id` = ?', array($old_address[$ip_version.'_address_id']));
          if (!$port_id)
          {
            log_event("IP address changed: $ip_compressed/".$old_address[$ip_version.'_prefixlen']." -> $full_address", $device, 'device', $device['device_id']);
          }
          else if (isset($update_array['port_id']))
          {
            // Address moved to another port
            log_event("IP address removed: $ip_compressed/".$old_address[$ip_version.'_prefixlen'], $device, 'port', $old_address['port_id']);
            log_event("IP address added: $full_address", $device, 'port', $port_id);
          } else {
            // Changed prefix/cidr
            log_event("IP address changed: $ip_compressed/".$old_address[$ip_version.'_prefixlen']." -> $full_address", $device, 'port', $port_id);
          }
          $GLOBALS['module_stats'][$module]['updated']++; //echo "U";
          $check_networks[$ip_version][$ip_network_id] = 1;
        } else {
          // Not changed
          $GLOBALS['module_stats'][$module]['unchanged']++; //echo ".";
        }
      } else {
        // New IP
        $update_array = $new_address;
        $ip_address_id = dbInsert($update_array, $ip_version.'_addresses');
        if ($port_id)
        {
          log_event("IP address added: $full_address", $device, 'port', $port_id);
        } else {
          log_event("IP address added: $full_address", $device, 'device', $device['device_id']);
        }
        $GLOBALS['module_stats'][$module]['added']++; //echo "+";
      }

      $valid[$ip_version][$ip_address_id] = $full_address . ':' . $port_id;
    }
  }

  // Refetch and clean IP addresses from DB
  foreach (dbFetchRows($query, array($device['device_id'])) as $entry)
  {
    $ip_address_id = $entry[$ip_version.'_address_id'];
    if (!isset($valid[$ip_version][$ip_address_id]))
    {
      $full_address = ($ip_version == 'ipv4' ? $entry['ipv4_address'] : $entry['ipv6_compressed']);
      $full_address .= '/' . $entry[$ip_version.'_prefixlen'];

      // Delete IP
      dbDelete($ip_version.'_addresses', '`'.$ip_version.'_address_id` = ?', array($ip_address_id));
      if ($port_id)
      {
        log_event("IP address removed: $full_address", $device, 'port', $entry['port_id']);
      } else {
        log_event("IP address removed: $full_address", $device, 'device', $entry['device_id']);
      }
      $GLOBALS['module_stats'][$module]['deleted']++; //echo "-";

      $check_networks[$ip_version][$entry[$ip_version.'_network_id']] = 1;
    }
  }

  // Clean networks
  foreach ($check_networks[$ip_version] as $ip_network_id => $n)
  {
    //$count = dbFetchCell('SELECT COUNT(*) FROM `'.$ip_version.'_addresses` WHERE `'.$ip_version.'_network_id` = ?', array($ip_network_id));
    //if (empty($count))
    if (!dbExist($ip_version.'_addresses', '`'.$ip_version.'_network_id` = ?', array($ip_network_id)))
    {
      dbDelete($ip_version.'_networks', '`'.$ip_version.'_network_id` = ?', array($ip_network_id));
      //echo('n');
    }
  }
}

$table_headers = array('%WifIndex%n', '%WifDescr%n', '%WIP: Version%n', '%WAddress%n', '%WNetwork%n', '%WOrigin%n');
print_cli_table($table_rows, $table_headers);

// Clean
unset($ip_data, $check_networks, $check_ipv6_mib, $update_array, $old_table, $table_rows, $table_headers);

// EOF
