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

// Redetect OS if necessary (controlled by discover_device function)
if ($detect_os)
{
  $os = get_device_os($device);

  if ($os != $device['os'])
  {
    $type = (isset($config['os'][$os]['type']) ? $config['os'][$os]['type'] : 'unknown'); // Also change $type
    print_cli_data('Device OS changed', $device['os']." -> $os", 1);
    log_event('OS changed: '.$device['os'].' -> '.$os, $device, 'device', $device['device_id'], 'warning');

    // Additionally reset icon and type for device if os changed 
    dbUpdate(array('os' => $os, 'icon' => array('NULL'), 'type' => $type), 'devices', '`device_id` = ?', array($device['device_id']));
    if (isset($attribs['override_icon']))
    {
      del_entity_attrib('device', $device, 'override_icon');
    }
    if (isset($attribs['override_type']))
    {
      del_entity_attrib('device', $device, 'override_type');
    }

    $device['os']   = $os;
    $device['type'] = $type;

    // Set device sysObjectID when device os changed
    $sysObjectID = snmp_cache_sysObjectID($device);
    if ($device['sysObjectID'] != $sysObjectID)
    {
      dbUpdate(array('sysObjectID' => $sysObjectID), 'devices', '`device_id` = ?', array($device['device_id']));
      $device['sysObjectID'] = $sysObjectID;
    }
  }
}

// If enabled, check the sysORID table for supported MIBs
if ($config['snmp']['snmp_sysorid'])
{
  $sysORID_mibs = array();
  $table_rows = array();
  $advertised_mibs = array();
  $capabilities_mibs = array();

  print_cli_data_field('sysORID table');
  $device_sysORID = snmpwalk_cache_oid_num2($device, 'sysORID', array(), 'SNMPv2-MIB');
  $device_sysORID = snmpwalk_cache_oid($device, 'sysORDescr', $device_sysORID, 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_MULTILINE);
  //print_vars($device_sysORID);

  foreach ($device_sysORID as $entry)
  {
    $advertised_mibs[] = $entry['sysORID'];
    if (preg_match('/AGENT-CAPABILITIES\s+SUPPORTS\s+(?<mib>\S+)/', $entry['sysORDescr'], $matches))
    {
      $mib = str_ireplace('ETHERLIKE-MIB', 'EtherLike-MIB', $matches['mib']); // Fix camel-case mib name
      // Collect AGENT-CAPABILITIES if MIB exist in definitions
      if (isset($config['mibs'][$mib]) && $mib != 'Printer-MIB')
      {
        $capabilities_mibs[$mib][] = $entry['sysORID'];
      } else {
        // This is just for info in output
        $capabilities_unused[$entry['sysORID']] = $mib;
      }
    }
  }

  // Skip walking the entire MIB array if the device has no response for sysORID
  if (count($advertised_mibs))
  {
    if (OBS_DEBUG > 1)
    {
      print_vars($advertised_mibs);
      print_vars($capabilities_mibs);
    }

    unset($use_fastpath_new); // See below

    $device_mibs    = get_device_mibs($device, FALSE);
    $device_mibs_bl = get_device_mibs_blacklist($device);
    // Cycle all known MIBs and see if the device reports supporting any of them
    $found_mibs = array();
    foreach ($config['mibs'] as $mib => $mibdata)
    {
      if (isset($mibdata['identity_num']) && !empty($mibdata['identity_num']))
      {
        // Search sysORID in identies
        $identity_found = FALSE;
        $mibdata['identity_num'] = (array)$mibdata['identity_num']; // Convert to simple array
        foreach ($mibdata['identity_num'] as $identity_num)
        {
          if ($identity_found = in_array($identity_num, $advertised_mibs))
          {
            $found_mibs = array_merge($found_mibs, $mibdata['identity_num']); // Add all oids as found
            break;
          }
        }

        // Search sysORID in capabilities
        if ($capability_found = !$identity_found && isset($capabilities_mibs[$mib]))
        {
          $identity_num = $capabilities_mibs[$mib][0];
          $found_mibs = array_merge($found_mibs, $capabilities_mibs[$mib]); // Add all oids as found
        }

        if ($identity_found || $capability_found)
        {

          // This is special hack for detect better MIB betwen 2 crossed for FASTPATH based:
          if (in_array($mib, array('BROADCOM-POWER-ETHERNET-MIB',
                                   'FASTPATH-BOXSERVICES-PRIVATE-MIB',
                                   'FASTPATH-SWITCHING-MIB',
                                   'FASTPATH-ISDP-MIB')))
          {
            if (!isset($use_fastpath_new))
            {
              // OID tree: .1.3.6.1.4.1.4413.1.1
              // FASTPATH is old reference BROADCOM mibs

              // First detect by CPU by 'EdgeSwitch-SWITCHING-MIB'
              //FASTPATH-SWITCHING-MIB::agentSwitchCpuProcessGroup.9.0 = STRING: "    5 Secs ( 99.9999%)   60 Secs ( 99.6646%)  300 Secs ( 99.2548%)"
              //EdgeSwitch-SWITCHING-MIB::agentSwitchCpuProcessTotalUtilization.0 = STRING: "    5 Secs ( 99.9999%)   60 Secs ( 99.9224%)  300 Secs ( 99.4892%)"
              $data = snmp_get($device, 'agentSwitchCpuProcessTotalUtilization.0', '-OQUvs', 'EdgeSwitch-SWITCHING-MIB');
              if (!empty($data))
              {
                $use_fastpath_new = preg_match('/300 Secs \(\s*(?<proc>[\d\.]+)%\)/', $data);
              }
              // Second detect by Temperature indexes by 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB'
              if (!isset($use_fastpath_new))
              {
                $oids = snmpwalk_cache_multi_oid($device, 'boxServicesTempSensorsTable', array(), 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');
                // By first detect if device used old FAST-BOXSERVICES-PRIVATE-MIB, it use single key in boxServicesTempSensorsTable
                $first_key = current(array_keys($oids));
                $use_fastpath_new = count(explode('.', $first_key)) > 1;
              }
            }

            if ($use_fastpath_new)
            {
              $mib = str_replace(array('BROADCOM', 'FASTPATH'), 'EdgeSwitch', $mib);
              print_debug("FASTPATH detect hack, used NEW mib: $mib");
              //set_entity_attrib('device', $device, 'use_fastpath_new', '1');
            }
          }
          // End of hack

          echo("$mib ");
          // Found this known MIB in the device's listed MIBs
          // Check if we already have this mapped without sysORID
          // We call is_device_mib() specifically without having it check permission settings or the sysORID table
          if (in_array($mib, $device_mibs))
          {
            // Already mapped
            $table_rows[] = array($identity_num, $mib, 'MIB already known through device mapping');
          } else {
            // Check if MIB is enabled globally, else bail out
            if ($config['mibs'][$mib]['enable'])
            {
              // Our local OS/MIB blacklist overrules their claim of support
              if (in_array($mib, $device_mibs_bl))
              {
                // MIB is in our blacklist, bail out
                $table_rows[] = array($identity_num, $mib, 'MIB already blacklisted through device mapping');
              } else {
                // We don't have this mapped, add to the array which will be added later
                $table_rows[] = array($identity_num, $mib, 'MIB added through sysORID'.($capability_found ? ' (AGENT-CAPABILITIES)' : ' (MODULE-IDENTITY)'));
                $sysORID_mibs[] = $mib;
              }
            } else {
              $table_rows[] = array($identity_num, $mib, 'MIB disabled globally');
            }
          }
        }
      }
    }
    unset($use_fastpath_new); // See above

    foreach (array_diff($advertised_mibs, $found_mibs) as $entry)
    {
      $table_rows[] = array($entry, $capabilities_unused[$entry], 'MIB unused');
    }
    /* DEBUG
    $tmp_diff = array_diff($advertised_mibs, $found_mibs);
    if (count($tmp_diff))
    {
      $prev_diff = json_decode(get_obs_attrib('sysOROID_unused'), TRUE);
      if (!is_array($prev_diff))
      {
        $prev_diff = array();
      }
      $tmp_diff = array_unique(array_merge($prev_diff, $tmp_diff));
      sort($tmp_diff);
      set_obs_attrib('sysOROID_unused', json_encode($tmp_diff));
      foreach ($tmp_diff as $entry)
      {
        $tmp_rows[] = array($entry, 'MIB unused');
      }
      echo("\n");
      print_cli_table($tmp_rows, array('%WOID%n', '%WStatus%n'));
    }
    */
  } else {
    echo('<empty>');
  }

  echo(PHP_EOL);

  // Set device attribute if we found any new MIBs, else delete the attribute
  if (count($sysORID_mibs))
  {
    $sysORID_db = json_decode(get_entity_attrib('device', $device, 'sysORID'), TRUE);
    set_entity_attrib('device', $device, 'sysORID', json_encode($sysORID_mibs));
    $update_array = array_diff($sysORID_mibs, (array)$sysORID_db);
    //print_vars($sysORID_db);
    //print_vars($sysORID_mibs);
    //print_vars($update_array);
    if (count($update_array))
    {
      log_event("MIBs discovered through sysORID: '" . implode("', '", $update_array) . "'", $device, 'device', $device['device_id']);
    }
  } else {
    del_entity_attrib('device', $device, 'sysORID');
  }

  unset($sysORID_mibs, $device_sysORID, $device_mibs, $device_mibs_bl, $advertised_mibs,
        $capabilities_mibs, $capabilities_unused, $found_mibs, $identity_found, $update_array);

  if (count($table_rows))
  {
    echo(PHP_EOL);
    $table_headers = array('%WOID%n', '%WMIB%n', '%WStatus%n');
    print_cli_table($table_rows, $table_headers);
  }
} else {
  // sysORID table disabled, delete the attribute
  del_entity_attrib('device', $device, 'sysORID');
}

// EOF
