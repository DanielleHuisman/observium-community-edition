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

// Init vars
$identities = [];
$identities_found = [];
$mibs_found = []; // 'MIB' => 'where founded' (sysorid, sysordescr, discovery)
$mibs_rename = [
  'ETHERLIKE-MIB'      => 'EtherLike-MIB', // Fix camel-case mib name
  'Printer-MIB'        => 'SKIP-Printer-MIB', // do not discover it by AGENT-CAPABILITIES, that incorrect
  'Job-Monitoring-MIB' => 'SKIP-Job-Monitoring-MIB', // do not discover it by AGENT-CAPABILITIES, that incorrect
];
// Agent capabilities for the CISCO-SIP-UA-MIB. LAST-UPDATED 200506220000Z ciscoSipUaCapabilityV12R0402T AGENT-CAPABILITIES SUPPORTS CISCO-SIP-UA-MIB File name: sys
$sysordescr_patterns[] = '/AGENT-CAPABILITIES\s+SUPPORTS\s+(?<mib>\S+)/';
//LLDP-V2-MIB, REVISION 200906080000Z
//ENTITY-MIB, RFC 4133
$sysordescr_patterns[] = '/^(?<mib>\S+), (REVISION \d{12}[A-Z]|RFC \d+)$/i';

$device_sysORID = snmpwalk_cache_oid_num2($device, 'sysORID', array(), 'SNMPv2-MIB');
$device_sysORID = snmpwalk_cache_oid($device, 'sysORDescr', $device_sysORID, 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_MULTILINE);
print_debug_vars($device_sysORID);

foreach ($device_sysORID as $entry)
{
  // Collect founded identities
  if (strlen($entry['sysORID']))
  {
    $identities[] = $entry['sysORID'];
  }

  // Collect founded MIBs by sysORDescr
  foreach ($sysordescr_patterns as $pattern)
  {
    if (preg_match($pattern, $entry['sysORDescr'], $matches))
    {
      $mib = array_str_replace($mibs_rename, $matches['mib']);
      if (!isset($mibs_found[$mib]))
      {
        $mibs_found[$mib] = ['source' => 'sysORDescr'];
        // If identity found, append
        if (strlen($entry['sysORID']))
        {
          $mibs_found[$mib]['identity'] = $entry['sysORID'];
        }
      } else {
        print_debug("MIB [$mib] already found");
      }

      break;
    }
  }
}
unset($device_sysORID);

$device_mibs    = get_device_mibs($device, FALSE); // MIBs defined by os/model
$device_mibs_bl = get_device_mibs_blacklist($device); // MIBs blacklisted for os/model

// Loop all known MIBs, discovery by snmp requests and validate founded MIB
$GLOBALS['table_rows'] = [];
foreach ($config['mibs'] as $mib => $mib_def)
{
  if (in_array($mib, $device_mibs_bl)) { continue; } // Skip blacklisted MIB

  // Detect MIB by identities
  if (!empty($mib_def['identity_num']) && !isset($mibs_found[$mib]))
  {
    foreach ((array)$mib_def['identity_num'] as $identity_num)
    {
      if (in_array($identity_num, $identities))
      {
        $mibs_found[$mib] = ['source' => 'sysORID', 'identity' => $identity_num];
        break;
      }
    }
  }

  // Discovery MIB by additional snmp walks
  if (isset($mib_def['discovery']) && !isset($mibs_found[$mib]))
  {
    foreach ($mib_def['discovery'] as $def)
    {
      if (match_discovery_oids($device, $def))
      {
        $mibs_found[$mib] = ['source' => 'Discovery'];
        // If identity found, append
        if (!empty($mib_def['identity_num']))
        {
          $mibs_found[$mib]['identity'] = is_array($mib_def['identity_num']) ? array_shift($mib_def['identity_num']) : $mib_def['identity_num'];
        }

        break;
      }
    }
  }
}

// Just show model specific MIBs
$model = get_model_array($device);
if (isset($model['mibs']))
{
  //print_vars($model);
  foreach ($model['mibs'] as $mib)
  {
    $mibs_found[$mib] = ['source' => 'Model'];
    // If identity found, append
    $mib_def = $config['mibs'][$mib];
    if (!empty($mib_def['identity_num']))
    {
      $mibs_found[$mib]['identity'] = is_array($mib_def['identity_num']) ? array_shift($mib_def['identity_num']) : $mib_def['identity_num'];
    }
  }
}

// Show matched discovery mibs
if (count($GLOBALS['table_rows']))
{
  //$table_opts    = array('max-table-width' => 200);
  $table_headers = array('%WOID%n', '%WMatched definition%n', '%WValue%n');
  print_cli_table($GLOBALS['table_rows'], $table_headers);
}
unset($GLOBALS['table_rows']);

/* Detect correct (new) version of FASTPATH mibs */
$old_fastpath_mibs = array('BROADCOM-POWER-ETHERNET-MIB',
                           'FASTPATH-BOXSERVICES-PRIVATE-MIB',
                           'FASTPATH-SWITCHING-MIB',
                           'FASTPATH-ISDP-MIB');
if (count($mibs_found) && !empty(array_intersect(array_keys($mibs_found), $old_fastpath_mibs)))
{
  $use_fastpath_new = FALSE;

  // OID tree: .1.3.6.1.4.1.4413.1.1
  // FASTPATH is old reference BROADCOM mibs

  // First detect by CPU by 'EdgeSwitch-SWITCHING-MIB'
  //FASTPATH-SWITCHING-MIB::agentSwitchCpuProcessGroup.9.0 = STRING: "    5 Secs ( 99.9999%)   60 Secs ( 99.6646%)  300 Secs ( 99.2548%)"
  //EdgeSwitch-SWITCHING-MIB::agentSwitchCpuProcessTotalUtilization.0 = STRING: "    5 Secs ( 99.9999%)   60 Secs ( 99.9224%)  300 Secs ( 99.4892%)"
  $data = snmp_get_oid($device, 'agentSwitchCpuProcessTotalUtilization.0', 'EdgeSwitch-SWITCHING-MIB');
  $use_fastpath_new = preg_match('/300 Secs \(\s*(?<proc>[\d\.]+)%\)/', $data);

  // Second detect by Temperature indexes by 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB'
  if (empty($data))
  {
    $oids = snmpwalk_cache_multi_oid($device, 'boxServicesTempSensorsTable', array(), 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');
    // By first detect if device used old FAST-BOXSERVICES-PRIVATE-MIB, it use single key in boxServicesTempSensorsTable
    $first_key = current(array_keys($oids));
    $use_fastpath_new = count(explode('.', $first_key)) > 1;
  }

  // Rewrite all founded FASTPATH MIBs
  if ($use_fastpath_new)
  {
    foreach ($old_fastpath_mibs as $mib)
    {
      if (isset($mibs_found[$mib]))
      {
        $new_mib = str_replace(array('BROADCOM', 'FASTPATH'), 'EdgeSwitch', $mib);
        $mibs_found[$new_mib] = $mibs_found[$mib];
        $mibs_found[$new_mib]['source'] .= ' (FASTPATH)';
        unset($mibs_found[$mib]);
        print_debug("FASTPATH detect hack, mib renamed: $mib -> $new_mib");
      }
    }
  }
}
unset($new_mib, $use_fastpath_new, $data, $first_key);
/* End of FASTPATH hack */

// Now filter known MIBs and pretty print
print_cli_data_field('MIBs discovered');
$table_rows = [];
foreach ($mibs_found as $mib => $entry)
{
  $identity_num = $entry['identity'];
  $identities_found[] = $identity_num;

  if (in_array($mib, $device_mibs_bl))
  {
    // MIB is in our blacklist, bail out
    $table_rows[] = [$identity_num, $mib, $entry['source'], '%mMIB blacklisted%n'];

    unset($mibs_found[$mib]);
    continue;
  }
  elseif (!isset($config['mibs'][$mib]))
  {
    // MIB is currently unsupported by Observium
    $table_rows[] = [$identity_num, $mib, $entry['source'], 'MIB not used'];

    unset($mibs_found[$mib]);
    continue;
  }
  elseif (isset($config['mibs'][$mib]['enable']) && !$config['mibs'][$mib]['enable'])
  {
    // MIB is currently unsupported by Observium
    $table_rows[] = [$identity_num, $mib, $entry['source'], '%rMIB disabled globally%n'];

    unset($mibs_found[$mib]);
    continue;
  }
  elseif (in_array($mib, $device_mibs))
  {
    // Already mapped
    $table_rows[] = array($identity_num, $mib, $entry['source'], '%yMIB already defined%n');

    unset($mibs_found[$mib]);
    continue;
  }

  // Checks ended, this MIB will add
  echo("$mib ");
  $table_rows[] = array($identity_num, "%g$mib%n", $entry['source'], '%gMIB added%n');
}

// Additionally filter found identities, just for show that exist but unknown
$identities = array_diff((array)$identities, (array)$identities_found);
foreach ($identities as $identity_num)
{
  $table_rows[] = array($identity_num, '-', 'sysORID', '%cUnknown Identity%n');
}

// Set device attribute if we found any new MIBs, else delete the attribute
if (count($mibs_found))
{
  $sysORID_db = json_decode(get_entity_attrib('device', $device, 'sysORID'), TRUE);
  $sysORID_mibs = array_keys($mibs_found);
  $update_array = array_diff($sysORID_mibs, (array)$sysORID_db);
  //print_vars($sysORID_db);
  //print_vars($sysORID_mibs);
  //print_vars($update_array);
  if (count($update_array))
  {
    set_entity_attrib('device', $device, 'sysORID', json_encode($sysORID_mibs));
    log_event("MIBs discovered: '" . implode("', '", $update_array) . "'", $device, 'device', $device['device_id']);

    // reset cache
    if (isset($GLOBALS['cache']['devices']['mibs_sysORID'][$device['device_id']]))
    {
      unset($GLOBALS['cache']['devices']['mibs_sysORID'][$device['device_id']]);
      unset($GLOBALS['cache']['entity_attribs']['device'][$device['device_id']]['sysORID']);
    }
  }
} else {
  echo('<empty>');
  del_entity_attrib('device', $device, 'sysORID');

  // reset cache
  if (isset($GLOBALS['cache']['devices']['mibs_sysORID'][$device['device_id']]))
  {
    unset($GLOBALS['cache']['devices']['mibs_sysORID'][$device['device_id']]);
    unset($GLOBALS['cache']['entity_attribs']['device'][$device['device_id']]['sysORID']);
  }
}

if (count($table_rows))
{
  echo(PHP_EOL);
  $table_headers = array('%WIdentity%n', '%WMIB%n', '%WSource%n', '%WStatus%n');
  print_cli_table($table_rows, $table_headers);
}

// Need to check if module disabled?
if (FALSE)
{
  // sysORID table disabled, delete the attribute
  del_entity_attrib('device', $device, 'sysORID');
}

// EOF
