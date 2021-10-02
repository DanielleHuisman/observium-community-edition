<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage functions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

/**
 * Process strings to give them a nicer capitalisation format
 *
 * This function does rewrites from the lowercase identifiers we use to the
 * standard capitalisation. UK English style plurals, please.
 * This uses $config['nicecase']
 *
 * @param string $item
 * @return string
*/
function nicecase($item) {
  if (is_numeric($item)) {
    return (string) $item;
  }
  if (!is_string($item)) {
    return '';
  }

  $mappings = $GLOBALS['config']['nicecase'];
  if (isset($mappings[$item])) { return $mappings[$item]; }
  //$item = preg_replace('/([a-z])([A-Z]{2,})/', '$1 $2', $item); // turn "fixedAC" into "fixed AC"

  return ucfirst((string)$item);
}

/**
 * Trim string and remove paired and/or escaped quotes from string
 *
 * @param string $string Input string
 * @param int    $flags
 *
 * @return string Cleaned string
 */
function trim_quotes($string, $flags = OBS_QUOTES_TRIM) {
  $string = trim($string); // basic string clean

  if (is_flag_set(OBS_QUOTES_STRIP, $flags) && str_contains($string, '"')) {
    // Just remove all (double) quotes from string
    return str_replace([ '\"', '"' ], '', $string);
  }

  if (is_flag_set(OBS_QUOTES_TRIM, $flags)) {
    if (str_contains($string, '\"')) {
      // replace escaped quotes
      $string = str_replace('\"', '"', $string);
    }

    if (str_starts($string, [ '"', "'" ])) {
      $quotes = '["\']'; // remove double and single quotes
      $pattern = '/^(' . $quotes . ')(?<value>.*?)(\1)$/s';
      while (preg_match($pattern, $string, $matches)) {
        $string = $matches['value'];
      }
    }
  }

  return $string;
}

/**
  * Humanize User
  *
  *   Process an array containing user info to add/modify elements.
  *
  * @param array $user
  */
// TESTME needs unit testing
function humanize_user(&$user)
{
  $level_permissions = auth_user_level_permissions($user['level']);
  $level_real        = $level_permissions['level'];
  if (isset($GLOBALS['config']['user_level'][$level_real]))
  {
    $def        = $GLOBALS['config']['user_level'][$level_real];
    $user['level_label'] = $def['name'];
    $user['level_name']  = $def['name'];
    $user['level_real']  = $level_real;
    unset($def['name'], $level_permissions['level']);
    $user = array_merge($user, $def, $level_permissions);
    // Add label class
    $user['label_class']  = ($user['row_class'] == 'disabled' ? 'inverse' : $user['row_class']);
  }
  //r($user);
}

/**
 * Humanize Scheduled Maintanance
 *
 *   Process an array containing a row from `alert_maintenance` and in-place add/modify elements for use in the UI
 *
 *
 */
function humanize_maintenance(&$maint)
{

  $maint['duration'] = $maint['maint_end'] - $maint['maint_start'];

  if ($maint['maint_global'] == 1)
  {
    $maint['entities_text'] = '<span class="label label-info">Global Maintenance</span>';
  } else {
    $entities = dbFetchRows("SELECT * FROM `alerts_maint_assoc` WHERE `maint_id` = ?", array($maint['maint_id']));
    if (is_array($entities) && count($entities))
    {
      foreach ($entities as $entity)
      {
        // FIXME, what here should be?
      }
    } else {
      $maint['entities_text'] = '<span class="label label-error">Maintenance is not associated with any entities.</span>';
    }
  }

  $maint['row_class'] = '';

  if ($maint['maint_start'] > $GLOBALS['config']['time']['now'])
  {
    $maint['start_text'] = "+".format_uptime($maint['maint_start'] - $GLOBALS['config']['time']['now']);
  } else {
    $maint['start_text'] = "-".format_uptime($GLOBALS['config']['time']['now'] - $maint['maint_start']);
    $maint['row_class']  = "warning";
    $maint['active_text'] = '<span class="label label-warning pull-right">active</span>';
  }

  if ($maint['maint_end'] > $GLOBALS['config']['time']['now'])
  {
    $maint['end_text'] = "+".format_uptime($maint['maint_end'] - $GLOBALS['config']['time']['now']);
  } else {
    $maint['end_text'] = "-".format_uptime($GLOBALS['config']['time']['now'] - $maint['maint_end']);
    $maint['row_class']  = "disabled";
    $maint['active_text'] = '<span class="label label-disabled pull-right">ended</span>';
  }

}

/**
 * Humanize Alert Check
 *
 *   Process an array containing a row from `alert_checks` and in place to add/modify elements.
 *
 * @param array $check
 */
// TESTME needs unit testing
function humanize_alert_check(&$check)
{
  // Fetch the queries to build the alert table.
  list($query, $param, $query_count) = build_alert_table_query(array('alert_test_id' => $check['alert_test_id']));

  // Fetch a quick set of alert_status values to build the alert check status text
  $query = str_replace(" * ", " `alert_status` ", $query);
  $check['entities'] = dbFetchRows($query, $param);

  $check['entity_status'] = array('up' => 0, 'down' => 0, 'unknown' => 0, 'delay' => 0, 'suppress' => 0);
  foreach ($check['entities'] as $alert_table_id => $alert_table_entry)
  {
    if     ($alert_table_entry['alert_status'] == '1') { ++$check['entity_status']['up']; }
    elseif ($alert_table_entry['alert_status'] == '0') { ++$check['entity_status']['down']; }
    elseif ($alert_table_entry['alert_status'] == '2') { ++$check['entity_status']['delay']; }
    elseif ($alert_table_entry['alert_status'] == '3') { ++$check['entity_status']['suppress']; }
    else                                               { ++$check['entity_status']['unknown']; }
  }

  $check['num_entities'] = count($check['entities']);

  if ($check['entity_status']['up'] == $check['num_entities'])
  {
    $check['class']  = "green";
    $check['html_row_class'] = "up";
  }
  elseif ($check['entity_status']['down'] > 0)
  {
    if ($check['severity'] === 'warn')
    {
      $check['class']          = "olive";
      $check['html_row_class'] = "warning";
    } else {
      $check['class']          = "red";
      $check['html_row_class'] = "error";
    }
  }
  elseif ($check['entity_status']['delay'] > 0)
  {
    $check['class']  = "orange";
    $check['html_row_class'] = "warning";
  }
  elseif ($check['entity_status']['suppress'] > 0)
  {
    $check['class']  = "purple";
    $check['html_row_class'] = "suppressed";
  }
  elseif ($check['entity_status']['up'] > 0)
  {
    $check['class']  = "green";
    $check['html_row_class'] = "success";
  } else {
    $check['entity_status']['class']  = "gray";
    $check['table_tab_colour'] = "#555555";
    $check['html_row_class'] = "disabled";
  }

  $check['status_numbers'] = '<span class="label label-success">'. $check['entity_status']['up']. '</span><span class="label label-suppressed">'. $check['entity_status']['suppress'].
         '</span><span class="label label-error">'. $check['entity_status']['down']. '</span><span class="label label-warning">'. $check['entity_status']['delay'].
         '</span><span class="label">'. $check['entity_status']['unknown']. '</span>';

  // We return nothing, $check is modified in place.
}

 /**
  * Humanize Alert
  *
  *   Process an array containing a row from `alert_entry` and `alert_entry-state` in place to add/modify elements.
  *
  * @param array $entry
  */
// TESTME needs unit testing
function humanize_alert_entry(&$entry)
{
  // Exit if already humanized
  if (isset($entry['humanized']) && $entry['humanized']) { return; }

  // Set colours and classes based on the status of the alert
  if ($entry['alert_status'] == '1')
  {
    // 1 means ok. Set blue text and disable row class
    $entry['class']  = "green";
    $entry['html_row_class'] = "up";
    $entry['status'] = "OK";
  }
  elseif ($entry['alert_status'] == '0')
  {
    // 0 means down. Set red text and error class
    //r($entry);
    if ($entry['severity'] === 'warn')
    {
      $entry['class']  = "olive";
      $entry['html_row_class'] = "warning";
      $entry['status'] = "WARNING";
    } else {
      $entry['class']  = "red";
      $entry['html_row_class'] = "error";
      $entry['status'] = "FAILED";
    }
  }
  elseif ($entry['alert_status'] == '2')
  {
    // 2 means the checks failed but we're waiting for x repetitions. set colour to orange and class to warning
    $entry['class']  = "orange";
    $entry['html_row_class'] = "warning";
    $entry['status'] = "DELAYED";
  }
  elseif ($entry['alert_status'] == '3')
  {
    // 3 means the checks failed but the alert is suppressed. set the colour to purple and the row class to suppressed
    $entry['class']  = "purple";
    $entry['html_row_class'] = "suppressed";
    $entry['status'] = "SUPPRESSED";
  } else {
    // Anything else set the colour to grey and the class to disabled.
    $entry['class']  = "gray";
    $entry['html_row_class'] = "disabled";
    $entry['status'] = "Unknown";
  }

  // Set the checked/changed/alerted entries to formatted date strings if they exist, else set them to never
  if (!isset($entry['last_checked']) || $entry['last_checked'] == '0') { $entry['checked'] = "<i>Never</i>"; } else { $entry['checked'] = format_uptime(time()-$entry['last_checked'], 'short-3'); }
  if (!isset($entry['last_changed']) || $entry['last_changed'] == '0') { $entry['changed'] = "<i>Never</i>"; } else { $entry['changed'] = format_uptime(time()-$entry['last_changed'], 'short-3'); }
  if (!isset($entry['last_alerted']) || $entry['last_alerted'] == '0') { $entry['alerted'] = "<i>Never</i>"; } else { $entry['alerted'] = format_uptime(time()-$entry['last_alerted'], 'short-3'); }
  if (!isset($entry['last_recovered']) || $entry['last_recovered'] == '0') { $entry['recovered'] = "<i>Never</i>"; } else { $entry['recovered'] = format_uptime(time()-$entry['last_recovered'], 'short-3'); }

  if (!isset($entry['ignore_until']) || $entry['ignore_until'] == '0') { $entry['ignore_until_text'] = "<i>Disabled</i>"; } else { $entry['ignore_until_text'] = format_timestamp($entry['ignore_until']); }
  if (!isset($entry['ignore_until_ok']) || $entry['ignore_until_ok'] == '0') { $entry['ignore_until_ok_text'] = "<i>Disabled</i>"; } else { $entry['ignore_until_ok_text'] = '<span class="purple">Yes</span>'; }

  // Set humanized so we can check for it later.
  $entry['humanized'] = TRUE;

  // We return nothing as we're working on a reference.
}

/**
 * Humanize Device
 *
 *   Process an array containing a row from `devices` to add/modify elements.
 *
 * @param array $device
 * @return none
 */
// TESTME needs unit testing
function humanize_device(&$device)
{
  global $config;

  // Exit if already humanized
  if (isset($device['humanized']) && $device['humanized']) { return; }

  // Expand the device state array from the php serialized string
  $device['state'] = safe_unserialize($device['device_state']);

  // Set the HTML class and Tab color for the device based on status
  if ($device['status'] == '0')
  {
    $device['row_class'] = "danger";
    $device['html_row_class'] = "error";
  } else {
    $device['row_class'] = "";
    $device['html_row_class'] = "up";
  }
  if ($device['ignore'] == '1' || (!is_null($device['ignore_until']) && strtotime($device['ignore_until']) > time()) )
  {
    $device['html_row_class'] = "suppressed";
    if ($device['status'] == '1')
    {
      $device['html_row_class'] = "success";  // Why green for ignore? Confusing!
                                              // I chose this purely because using green for up and blue for up/ignore was uglier.
    } else {
      $device['row_class'] = "suppressed";
    }
  }
  if ($device['disabled'] == '1')
  {
    $device['row_class'] = "disabled";
    $device['html_row_class'] = "disabled";
  }

  // Set country code always lowercase
  if (isset($device['location_country']))
  {
    $device['location_country'] = strtolower($device['location_country']);
  }

  // Set the name we print for the OS
  $device['os_text'] = $config['os'][$device['os']]['text'];

  // Format ASN as asdot if configured
  $device['human_local_as'] = $config['web_show_bgp_asdot'] ? bgp_asplain_to_asdot($device['bgpLocalAs']) : $device['bgpLocalAs'];

  // Mark this device as being humanized
  $device['humanized'] = TRUE;
}

/**
 * Humanize BGP Peer
 *
 * Returns a the $peer array with processed information:
 * row_class, table_tab_colour, state_class, admin_class
 *
 * @param array $peer
 * @return array|void $peer
 *
 */
// TESTME needs unit testing
function humanize_bgp(&$peer)
{
  global $config;

  // Exit if already humanized
  if (isset($peer['humanized']) && $peer['humanized']) { return; }

  // Set colours and classes based on the status of the peer
  if ($peer['bgpPeerAdminStatus'] === 'stop' || $peer['bgpPeerAdminStatus'] === 'halted')
  {
    // Peer is disabled, set row to warning and text classes to muted.
    $peer['html_row_class'] = "warning";
    $peer['state_class']    = "muted";
    $peer['admin_class']    = "muted";
    $peer['alert']          = 0;
    $peer['disabled']       = 1;
  }
  elseif ($peer['bgpPeerAdminStatus'] === "start" || $peer['bgpPeerAdminStatus'] === "running" )
  {
    // Peer is enabled, set state green and check other things
    $peer['admin_class'] = "success";
    if ($peer['bgpPeerState'] === "established")
    {
      // Peer is up, set colour to blue and disable row class
      $peer['state_class'] = "success";
      $peer['html_row_class'] = "up";
    } else {
      // Peer is down, set colour to red and row class to error.
      $peer['state_class'] = "danger";
      $peer['html_row_class'] = "error";
    }
  }

  // Set text and colour if peer is same AS, private AS or external.
  if ($peer['bgpPeerRemoteAs'] == $peer['local_as'])
  {
    $peer['peer_type_class'] = "info";
    $peer['peer_type'] = "iBGP";
  } else {
    $peer['peer_type_class'] = "primary";
    $peer['peer_type'] = "eBGP";
  }

  // Private AS numbers, see: https://tools.ietf.org/html/rfc6996
  if (is_bgp_as_private($peer['bgpPeerRemoteAs']))
  {
    $peer['peer_type_class'] = "warning";
    $peer['peer_type'] = "Priv ".$peer['peer_type'];
  }
  if (is_bgp_as_private($peer['local_as']))
  {
    $peer['peer_local_class'] = "warning";
    $peer['peer_local_type']  = "private";
  } else {
    $peer['peer_local_class'] = "";
    $peer['peer_local_type']  = "public";
  }

  // Format (compress) the local/remote IPs if they're IPv6
  $peer['human_localip']  = ip_compress($peer['bgpPeerLocalAddr']);
  $peer['human_remoteip'] = ip_compress($peer['bgpPeerRemoteAddr']);

  // Format ASN as asdot if configured
  $peer['human_local_as']  = $config['web_show_bgp_asdot'] ? bgp_asplain_to_asdot($peer['local_as']) : $peer['local_as'];
  $peer['human_remote_as'] = $config['web_show_bgp_asdot'] ? bgp_asplain_to_asdot($peer['bgpPeerRemoteAs']) : $peer['bgpPeerRemoteAs'];
  
  // Set humanized entry in the array so we can tell later
  $peer['humanized'] = TRUE;
}

function process_port_label(&$this_port, $device)
{
  global $config;

  //file_put_contents('/tmp/process_port_label_'.$device['hostname'].'_'.$this_port['ifIndex'].'.port', var_export($this_port, TRUE)); ///DEBUG

  // OS Specific rewrites (get your shit together, vendors)
  if ($device['os'] === 'zxr10') {
    $this_port['ifAlias'] = preg_replace("/^" . str_replace("/", "\\/", $this_port['ifName']) . "\s*/", '', $this_port['ifDescr']);
  } elseif ($device['os'] === 'ciscosb' && $this_port['ifType'] === 'propVirtual' && is_numeric($this_port['ifDescr'])) {
    $this_port['ifName'] = 'Vlan'.$this_port['ifDescr'];
  }

  // Will copy ifDescr -> ifAlias if ifDescr != ifName (Actually for Brocade NOS and Allied Telesys devices)
  if (isset($config['os'][$device['os']]['ifDescr_ifAlias']) && $config['os'][$device['os']]['ifDescr_ifAlias'] &&
      $this_port['ifType'] === 'ethernetCsmacd' && $this_port['ifDescr'] !== $this_port['ifName'] &&
      $this_port['ifDescr'] !== '-' && !str_starts($this_port['ifDescr'], 'Allied Teles')) {
    if (empty($this_port['ifAlias']) ||
        $this_port['ifName'] === $this_port['ifAlias']) {
      $this_port['ifAlias'] = $this_port['ifDescr'];
    }
  }

  // Write port_label, port_label_base and port_label_num

  // Here definition override for ifDescr, because Calix switch ifDescr <> ifName since fw 2.2
  // Note, only for 'calix' os now
  if ($device['os'] === 'calix') {
    unset($config['os'][$device['os']]['ifname']);
    $version_parts = explode('.', $device['version']);
    if ($version_parts[0] > 2 || ($version_parts[0] == 2 && $version_parts[1] > 1)) {
      if ($this_port['ifName'] == '') {
        $this_port['port_label'] = $this_port['ifDescr'];
      } else {
        $this_port['port_label'] = $this_port['ifName'];
      }
    }
  }

  // This happen on some liebert UPS devices or when device have memory leak (ie Eaton Powerware)
  if (isset($config['os'][$device['os']]['ifType_ifDescr']) && $config['os'][$device['os']]['ifType_ifDescr'] && $this_port['ifIndex'])
  {
    $len = strlen($this_port['ifDescr']);
    $type = rewrite_iftype($this_port['ifType']);
    if ($type && ($len === 0 || $len > 255 ||
                  isHexString($this_port['ifDescr']) ||
                  preg_match('/(.)\1{4,}/', $this_port['ifDescr'])))
    {
      $this_port['ifDescr'] = $type . ' ' . $this_port['ifIndex'];
      print_debug("Port 'ifDescr' rewritten: '' -> '" . $this_port['ifDescr'] . "'");
    }
  }

  if (isset($config['os'][$device['os']]['ifname']))
  {
    if ($this_port['ifName'] == '')
    {
      $this_port['port_label'] = $this_port['ifDescr'];
    } else {
      $this_port['port_label'] = $this_port['ifName'];
    }
  }
  elseif (isset($config['os'][$device['os']]['ifalias']))
  {
    $this_port['port_label'] = $this_port['ifAlias'];
  } else {
    if ($this_port['ifDescr'] === '' && $this_port['ifName'] !== '')
    {
      // Some new NX-OS have empty ifDescr
      $this_port['port_label'] = $this_port['ifName'];
    } else {
      $this_port['port_label'] = $this_port['ifDescr'];
    }
    if (isset($config['os'][$device['os']]['ifindex']))
    {
      $this_port['port_label'] .= ' ' . $this_port['ifIndex'];
    }
  }

  // Process label by os definition rewrites
  $oid = 'port_label';
  if (isset($config['os'][$device['os']][$oid]))
  {
    $this_port['port_label'] = preg_replace('/\ {2,}/', ' ', $this_port['port_label']); // clear 2 and more spaces

    $oid_base  = $oid.'_base';
    $oid_num   = $oid.'_num';
    $oid_short = $oid.'_short';
    foreach ($config['os'][$device['os']][$oid] as $pattern)
    {
      if (preg_match($pattern, $this_port[$oid], $matches))
      {
        //print_debug_vars($matches);
        if (isset($matches[$oid]))
        {
          // if exist 'port_label' match reference
          $this_port[$oid] = $matches[$oid];
        } else {
          // or just first reference
          $this_port[$oid] = $matches[1];
        }
        print_debug("Port '$oid' rewritten: '" . $this_port[$oid] . "' -> '" . $this_port[$oid] . "'");

        if (isset($matches[$oid_base]))
        {
          $this_port[$oid_base] = $matches[$oid_base];
        }
        if (isset($matches[$oid_num]))
        {
          if ($device['os'] == 'cisco-altiga' && $matches[$oid_num] === '') // This derp only for altiga (I hope so)
          {
            // See cisco-altiga os definition
            // If port_label_num match set, but it empty, use ifIndex as num
            $this_port[$oid_num] = $this_port['ifIndex'];
            $this_port[$oid] .= $this_port['ifIndex'];
          } else {
            $this_port[$oid_num] = $matches[$oid_num];
          }
        }

        // Additionally possible to parse port_label_short
        if (isset($matches[$oid_short]))
        {
          $this_port[$oid_short] = $matches[$oid_short];
        }

        // Additionally possible to parse ifAlias from ifDescr (ie timos)
        if (isset($matches['ifAlias']))
        {
          $this_port['ifAlias'] = $matches['ifAlias'];
        }
        break;
      }
    }
  } else {
    // Common port name rewrites (do not escape)
    $this_port['port_label'] = rewrite_ifname($this_port['port_label'], FALSE);
  }

  if (!isset($this_port['port_label_base'])) // skip when already set by previous processing, ie os definitions
  {
    // Extract bracket part from port label and remove it
    $label_bracket = '';
    if (preg_match('/\s*(\([^\)]+\))$/', $this_port['port_label'], $matches))
    {
      // GigaVUE-212 Port  8/48 (Network Port)
      // rtif(172.20.30.46/28)
      print_debug('Port label ('.$this_port['port_label'].') matched #1'); // Just for find issues
      $label_bracket = $this_port['port_label']; // fallback
      list($this_port['port_label']) = explode($matches[0], $this_port['port_label'], 2);
    }
    elseif (preg_match('!^10*(?:/10*)*\s*[MGT]Bit\s+(.*)!i', $this_port['port_label'], $matches))
    {
      // remove 10/100 Mbit part from beginning, this broke detect label_base/label_num (see hirschmann-switch os)
      // 10/100 MBit Ethernet Switch Interface 6
      // 1 MBit Ethernet Switch Interface 6
      print_debug('Port label ('.$this_port['port_label'].') matched #2'); // Just for find issues
      $label_bracket = $this_port['port_label']; // fallback
      $this_port['port_label'] = $matches[1];
    }
    elseif (preg_match('/^(.+)\s*:\s+(.+)/', $this_port['port_label'], $matches))
    {
      // Another case with colon
      // gigabitEthernet 1/0/24 : copper
      // port 3: Gigabit Fiber
      print_debug('Port label ('.$this_port['port_label'].') matched #3'); // Just for find issues
      $label_bracket = $this_port['port_label']; // fallback
      $this_port['port_label'] = $matches[1];
    }

    // Detect port_label_base and port_label_num
    //if (preg_match('/\d+(?:(?:[\/:](?:[a-z])?[\d\.:]+)+[a-z\d\.\:]*(?:[\-\_][\w\.\:]+)*|\/\w+$)/i', $this_port['port_label'], $matches))
    if (preg_match('/\d+((?<periodic>(?:[\/:]([a-z]*\d+|[a-z]+[a-z0-9\-\_]*)(?:\.\d+)?)+)(?<last>[\-\_\.][\w\.\:]+)*|\/\w+$)/i', $this_port['port_label'], $matches))
    {
      // Multipart numeric
      /*
      1/1/1
      e1-0/0/1.0
      e1-0/2/0:13.0
      dwdm0/1/0/6
      DTI1/1/0
      Cable8/1/4-upstream2
      Cable8/1/4
      16GigabitEthernet1/2/1
      cau4-0/2/0
      dot11radio0/0
      Dialer0/0.1
      Downstream 0/2/0
      ControlEthernet0/RSP0/CPU0/S0/10
      1000BaseTX Port 8/48 Name
      Backplane-GigabitEthernet0/3
      Ethernet1/10
      FC port 0/19
      GigabitEthernet0/0/0/1
      GigabitEthernet0/1.ServiceInstance.206
      Integrated-Cable7/0/0:0
      Logical Upstream Channel 1/0.0/0
      Slot0/1
      sonet_12/1
      GigaVUE-212 Port  8/48 (Network Port)
      Stacking Port 1/StackA
      gigabitEthernet 1/0/24 : copper
      1:38
      1/4/x24, mx480-xe-0-0-0
      1/4/x24
      5/1/lns-net
      */
      $this_port['port_label_num'] = $matches[0];
      list($this_port['port_label_base']) = explode($matches[0], $this_port['port_label'], 2);
      $this_port['port_label'] = $this_port['port_label_base'] . $this_port['port_label_num']; // Remove additional part (after port number)
    }
    elseif (preg_match('/(?<port_label_num>(?:\d+[a-z])?\d[\d\.\:]*(?:[\-\_]\w+)?)(?: [a-z()\[\] ]+)?$/i', $this_port['port_label'], $matches))
    {
      // Simple numeric
      /*
      GigaVUE-212 Port  1 (Network Port)
      MMC-A s3 SW Port
      Atm0_Physical_Interface
      wan1_phys
      fwbr101i0
      Nortel Ethernet Switch 325-24G Module - Port 1
      lo0.32768
      vlan.818
      jsrv.1
      Bundle-Ether1.1701
      Ethernet1
      ethernet_13
      eth0
      eth0.101
      BVI900
      A/1
      e1
      CATV-MAC 1
      16
      */
      $this_port['port_label_num'] = $matches['port_label_num'];
      $this_port['port_label_base'] = substr($this_port['port_label'], 0, 0 - strlen($matches[0]));
      $this_port['port_label'] = $this_port['port_label_base'] . $this_port['port_label_num']; // Remove additional part (after port number)
    } else {
      // All other (non-numeric)
      /*
      UniPing Server Solution v3/SMS Enet Port
      MMC-A s2 SW Port
      Control Plane
      */
      $this_port['port_label_base'] = $this_port['port_label'];
    }

    // When not empty label brackets and empty numeric part, re-add brackets to label
    if (!empty($label_bracket) && $this_port['port_label_num'] == '')
    {
      // rtif(172.20.30.46/28)
      $this_port['port_label'] = $label_bracket;
      $this_port['port_label_base'] = $this_port['port_label'];
      $this_port['port_label_num'] = '';
    }
  }

  // Make short version (do not escape)
  if (isset($this_port['port_label_short']))
  {
    // Short already parsed from definitions (not sure if need additional shorting)
    $this_port['port_label_short'] = short_ifname($this_port['port_label_short'], NULL, FALSE);
  } else {
    $this_port['port_label_short'] = short_ifname($this_port['port_label'], NULL, FALSE);
  }

  // Set entity variables for use by code which uses entities
  // Base label part: TenGigabitEthernet3/3 -> TenGigabitEthernet, GigabitEthernet4/8.722 -> GigabitEthernet, Vlan2603 -> Vlan
  //$port['port_label_base'] = preg_replace('/^([A-Za-z ]*).*/', '$1', $port['port_label']);
  //$port['port_label_num']  = substr($port['port_label'], strlen($port['port_label_base'])); // Second label part
  //
  //  // Index example for TenGigabitEthernet3/10.324:
  //  //  $ports_links['Ethernet'][] = array('label_base' => 'TenGigabitEthernet', 'label_num0' => '3', 'label_num1' => '10', 'label_num2' => '324')
  //  $label_num  = preg_replace('![^\d\.\/]!', '', substr($data['port_label'], strlen($data['port_label_base']))); // Remove base part and all not-numeric chars
  //  preg_match('!^(\d+)(?:\/(\d+)(?:\.(\d+))*)*!', $label_num, $label_nums); // Split by slash and point (1/1.324)
  //  $ports_links[$data['human_type']][$data['ifIndex']] = array(
  //    'label'      => $data['port_label'],
  //    'label_base' => $data['port_label_base'],
  //    'label_num0' => $label_nums[0],
  //    'label_num1' => $label_nums[1],
  //    'label_num2' => $label_nums[2],
  //    'link'       => generate_port_link($data, $data['port_label_short'])
  //  );

  return TRUE;
}

/**
 * Humanize port.
 *
 * Returns a the $port array with processed information:
 * label, humans_speed, human_type, html_class and human_mac
 * row_class, table_tab_colour
 *
 * Escaping should not be done here, since these values are used in the API too.
 *
 * @param array $port
 * @return array $port
 *
 */
// TESTME needs unit testing
function humanize_port(&$port)
{
  global $config, $cache;

  // Exit if already humanized
  if (isset($peer['humanized']) && $port['humanized']) { return; }

  $port['attribs'] = get_entity_attribs('port', $port['port_id']);

  // If we can get the device data from the global cache, do it, else pull it from the db (mostly for external scripts)
  if (is_array($GLOBALS['cache']['devices']['id'][$port['device_id']]))
  {
    $device = &$GLOBALS['cache']['devices']['id'][$port['device_id']];
  } else {
    $device = device_by_id_cache($port['device_id']);
  }

  // Workaround for devices/ports who long time not updated and have empty port_label
  if (empty($port['port_label']) || strlen($port['port_label_base'].$port['port_label_num']) == 0)
  {
    process_port_label($port, $device);
  }

  // Set humanised values for use in UI
  $port['human_speed'] = humanspeed($port['ifSpeed']);
  $port['human_type']  = rewrite_iftype($port['ifType']);
  $port['html_class']  = port_html_class($port['ifOperStatus'], $port['ifAdminStatus'], $port['encrypted']);
  $port['human_mac']   = format_mac($port['ifPhysAddress']);

  // Set entity_* values for code which expects them.
  $port['entity_name']      = $port['port_label'];
  $port['entity_shortname'] = $port['port_label_short'];
  $port['entity_descr']     = $port['ifAlias'];

  $port['table_tab_colour'] = "#aaaaaa";
  $port['row_class'] = "";
  $port['icon'] = 'port-ignored'; // Default
  $port['admin_status'] = $port['ifAdminStatus'];
  if     ($port['ifAdminStatus'] == "down")
  {
    $port['admin_status'] = 'disabled';
    $port['row_class'] = "disabled";
    $port['icon'] = 'port-disabled';
    $port['admin_class'] = "disabled";
  }
  elseif ($port['ifAdminStatus'] == "up")
  {
    $port['admin_status'] = 'enabled';
    $port['admin_class']  = 'primary';
    switch ($port['ifOperStatus'])
    {
      case 'up':
        $port['table_tab_colour'] = "#194B7F"; $port['row_class'] = "ok";      $port['icon'] = 'port-up'; $port['oper_class'] = "primary";
        break;
      case 'monitoring':
        // This is monitoring ([e|r]span) ports
        $port['table_tab_colour'] = "#008C00"; $port['row_class'] = "success"; $port['icon'] = 'port-up'; $port['oper_class'] = "success";
        break;
      case 'down':
        $port['table_tab_colour'] = "#cc0000"; $port['row_class'] = "error";   $port['icon'] = 'port-down'; $port['oper_class'] = "error";
        break;
      case 'lowerLayerDown':
        $port['table_tab_colour'] = "#ff6600"; $port['row_class'] = "warning"; $port['icon'] = 'port-down';  $port['oper_class'] = "warning";
        break;
      case 'testing':
      case 'unknown':
      case 'dormant':
      case 'notPresent':
        $port['table_tab_colour'] = "#85004b"; $port['row_class'] = "info";    $port['icon'] = 'port-ignored'; $port['oper_class'] = "info";
        break;
    }
  }

  // If the device is down, colour the row/tab as 'warning' meaning that the entity is down because of something below it.
  if ($device['status'] == '0')
  {
    $port['table_tab_colour'] = "#ff6600";
    $port['row_class'] = "warning";
    $port['icon'] = 'port-ignored';
  }
  if ($port['ignore'] == '1' && $port['row_class'] !== 'ok') {
    $port['row_class'] = "suppressed";
  }
  if ($port['disabled'] == '1') {
    $port['row_class'] = "disabled";
  }
  $port['in_rate'] = $port['ifInOctets_rate'] * 8;
  $port['out_rate'] = $port['ifOutOctets_rate'] * 8;

  // Colour in bps based on speed if > 50, else by UI convention.
  if ($port['ifSpeed'] > 0)
  {
    $in_perc  = round($port['in_rate']/$port['ifSpeed']*100);
    $out_perc = round($port['out_rate']/$port['ifSpeed']*100);
  } else {
    // exclude division by zero error
    $in_perc  = 0;
    $out_perc = 0;
  }
  if ($port['in_rate'] == 0)
  {
    $port['bps_in_style'] = '';
  } elseif ($in_perc < '50') {
    $port['bps_in_style'] = 'color: #008C00;';
  } else {
    $port['bps_in_style'] = 'color: ' . percent_colour($in_perc) . '; ';
  }

  // Colour out bps based on speed if > 50, else by UI convention.
  if ($port['out_rate'] == 0)
  {
    $port['bps_out_style'] = '';
  } elseif ($out_perc < '50') {
    $port['bps_out_style'] = 'color: #394182;';
  } else {
    $port['bps_out_style'] = 'color: ' . percent_colour($out_perc) . '; ';
  }

  // Colour in and out pps based on UI convention
  $port['pps_in_style'] = ($port['ifInUcastPkts_rate'] == 0) ? '' : 'color: #740074;';
  $port['pps_out_style'] = ($port['ifOutUcastPkts_rate'] == 0) ? '' : 'color: #FF7400;';

  $port['humanized'] = TRUE; /// Set this so we can check it later.

}

// Rewrite arrays
/// FIXME. Clean, rename GLOBAL $rewrite_* variables into $config['rewrite'] definition

$rewrite_breeze_type = array(
  'aubs'     => 'AU-BS',    // modular access unit
  'ausa'     => 'AU-SA',    // stand-alone access unit
  'su-6-1d'  => 'SU-6-1D',  // subscriber unit supporting 6 Mbps (after 5.0 - deprecated)
  'su-6-bd'  => 'SU-6-BD',  // subscriber unit supporting 6 Mbps
  'su-24-bd' => 'SU-24-BD', // subscriber unit supporting 24 Mbps
  'bu-b14'   => 'BU-B14',   // BreezeNET Base Unit supporting 14 Mbps
  'bu-b28'   => 'BU-B28',   // BreezeNET Base Unit supporting 28 Mbps
  'rb-b14'   => 'RB-B14',   // BreezeNET Remote Bridge supporting 14 Mbps
  'rb-b28'   => 'RB-B28',   // BreezeNET Remote Bridge supporting 28 Mbps
  'su-bd'    => 'SU-BD',    // subscriber unit
  'su-54-bd' => 'SU-54-BD', // subscriber unit supporting 54 Mbps
  'su-3-1d'  => 'SU-3-1D',  // subscriber unit supporting 3 Mbps (after 5.0 - deprecated)
  'su-3-4d'  => 'SU-3-4D',  // subscriber unit supporting 3 Mbps
  'ausbs'    => 'AUS-BS',   // modular access unit supporting maximum 25 subscribers
  'aussa'    => 'AUS-SA',   // stand-alone access unit supporting maximum 25 subscribers
  'aubs4900' => 'AU-BS-4900', // BreezeAccess 4900 modular access unit
  'ausa4900' => 'AU-SA-4900', // BreezeAccess 4900 stand alone access unit
  'subd4900' => 'SU-BD-4900', // BreezeAccess 4900 subscriber unit
  'bu-b100'  => 'BU-B100',  // BreezeNET Base Unit unlimited throughput
  'rb-b100'  => 'BU-B100',  // BreezeNET Remote Bridge unlimited throughput
  'su-i'     => 'SU-I',
  'au-ez'    => 'AU-EZ',
  'su-ez'    => 'SU-EZ',
  'su-v'     => 'SU-V',     // subscriber unit supporting 12 Mbps downlink and 8 Mbps uplink
  'bu-b10'   => 'BU-B10',   // BreezeNET Base Unit supporting 5 Mbps
  'rb-b10'   => 'RB-B10',   // BreezeNET Base Unit supporting 5 Mbps
  'su-8-bd'  => 'SU-8-BD',  // subscriber unit supporting 8 Mbps
  'su-1-bd'  => 'SU-1-BD',  // subscriber unit supporting 1 Mbps
  'su-3-l'   => 'SU-3-L',   // subscriber unit supporting 3 Mbps
  'su-6-l'   => 'SU-6-L',   // subscriber unit supporting 6 Mbps
  'su-12-l'  => 'SU-12-L',  // subscriber unit supporting 12 Mbps
  'au'       => 'AU',       // security access unit
  'su'       => 'SU',       // security subscriber unit
);

$rewrite_cpqida_hardware = array(
  'other'      => 'Other',
  'ida'        => 'IDA',
  'idaExpansion' => 'IDA Expansion',
  'ida-2'      => 'IDA - 2',
  'smart'      => 'SMART',
  'smart-2e'   => 'SMART - 2/E',
  'smart-2p'   => 'SMART - 2/P',
  'smart-2sl'  => 'SMART - 2SL',
  'smart-3100es' => 'Smart - 3100ES',
  'smart-3200' => 'Smart - 3200',
  'smart-2dh'  => 'SMART - 2DH',
  'smart-221'  => 'Smart - 221',
  'sa-4250es'  => 'Smart Array 4250ES',
  'sa-4200'    => 'Smart Array 4200',
  'sa-integrated' => 'Integrated Smart Array',
  'sa-431'     => 'Smart Array 431',
  'sa-5300'    => 'Smart Array 5300',
  'raidLc2'    => 'RAID LC2 Controller',
  'sa-5i'      => 'Smart Array 5i',
  'sa-532'     => 'Smart Array 532',
  'sa-5312'    => 'Smart Array 5312',
  'sa-641'     => 'Smart Array 641',
  'sa-642'     => 'Smart Array 642',
  'sa-6400'    => 'Smart Array 6400',
  'sa-6400em'  => 'Smart Array 6400 EM',
  'sa-6i'      => 'Smart Array 6i',
  'sa-generic' => 'Generic Array',
  'sa-p600'    => 'Smart Array P600',
  'sa-p400'    => 'Smart Array P400',
  'sa-e200'    => 'Smart Array E200',
  'sa-e200i'   => 'Smart Array E200i',
  'sa-p400i'   => 'Smart Array P400i',
  'sa-p800'    => 'Smart Array P800',
  'sa-e500'    => 'Smart Array E500',
  'sa-p700m'   => 'Smart Array P700m',
  'sa-p212'    => 'Smart Array P212',
  'sa-p410'    => 'Smart Array P410',
  'sa-p410i'   => 'Smart Array P410i',
  'sa-p411'    => 'Smart Array P411',
  'sa-b110i'   => 'Smart Array B110i SATA RAID',
  'sa-p712m'   => 'Smart Array P712m',
  'sa-p711m'   => 'Smart Array P711m',
  'sa-p812'    => 'Smart Array P812',
  'sw-1210m'   => 'StorageWorks 1210m',
  'sa-p220i'   => 'Smart Array P220i',
  'sa-p222'    => 'Smart Array P222',
  'sa-p420'    => 'Smart Array P420',
  'sa-p420i'   => 'Smart Array P420i',
  'sa-p421'    => 'Smart Array P421',
  'sa-b320i'   => 'Smart Array B320i',
  'sa-p822'    => 'Smart Array P822',
  'sa-p721m'   => 'Smart Array P721m',
  'sa-b120i'   => 'Smart Array B120i',
  'hps-1224'   => 'HP Storage p1224',
  'hps-1228'   => 'HP Storage p1228',
  'hps-1228m'  => 'HP Storage p1228m',
  'sa-p822se'  => 'Smart Array P822se',
  'hps-1224e'  => 'HP Storage p1224e',
  'hps-1228e'  => 'HP Storage p1228e',
  'hps-1228em' => 'HP Storage p1228em',
  'sa-p230i'   => 'Smart Array P230i',
  'sa-p430i'   => 'Smart Array P430i',
  'sa-p430'    => 'Smart Array P430',
  'sa-p431'    => 'Smart Array P431',
  'sa-p731m'   => 'Smart Array P731m',
  'sa-p830i'   => 'Smart Array P830i',
  'sa-p830'    => 'Smart Array P830',
  'sa-p831'    => 'Smart Array P831',
  'sa-p530'    => 'Smart Array P530',
  'sa-p531'    => 'Smart Array P531',
  'sa-p244br'  => 'Smart Array P244br',
  'sa-p246br'  => 'Smart Array P246br',
  'sa-p440'    => 'Smart Array P440',
  'sa-p440ar'  => 'Smart Array P440ar',
  'sa-p441'    => 'Smart Array P441',
  'sa-p741m'   => 'Smart Array P741m',
  'sa-p840'    => 'Smart Array P840',
  'sa-p841'    => 'Smart Array P841',
  'sh-h240ar'  => 'Smart HBA H240ar',
  'sh-h244br'  => 'Smart HBA H244br',
  'sh-h240'    => 'Smart HBA H240',
  'sh-h241'    => 'Smart HBA H241',
  'sa-b140i'   => 'Smart Array B140i',
  'sh-generic' => 'Smart HBA',
  'sa-p240nr'  => 'Smart Array P240nr',
  'sh-h240nr'  => 'Smart HBA H240nr',
  'sa-p840ar'  => 'Smart Array P840ar',
  'sa-p542d'   => 'Smart Array P542D',
  's100i'      => 'Smart Array S100i',
  'e208i-p'    => 'Smart Array E208i-p',
  'e208i-a'    => 'Smart Array E208i-a',
  'e208i-c'    => 'Smart Array E208i-c',
  'e208e-p'    => 'Smart Array E208e-p',
  'p204i-b'    => 'Smart Array P204i-b',
  'p204i-c'    => 'Smart Array P204i-c',
  'p408i-p'    => 'Smart Array P408i-p',
  'p408i-a'    => 'Smart Array P408i-a',
  'p408e-p'    => 'Smart Array P408e-p',
  'p408i-c'    => 'Smart Array P408i-c',
  'p408e-m'    => 'Smart Array P408e-m',
  'p416ie-m'   => 'Smart Array P416ie-m',
  'p816i-a'    => 'Smart Array P816i-a',
  'p408i-sb'   => 'Smart Array P408i-sb'
);

$rewrite_liebert_hardware = array(
  // UpsProducts - Liebert UPS Registrations
  'lgpSeries7200'                     => array('name' => 'Series 7200 UPS',                             'type' => 'ups'),
  'lgpUPStationGXT'                   => array('name' => 'UPStationGXT UPS',                            'type' => 'ups'),
  'lgpPowerSureInteractive'           => array('name' => 'PowerSure Interactive UPS',                   'type' => 'ups'),
  'lgpNfinity'                        => array('name' => 'Nfinity UPS',                                 'type' => 'ups'),
  'lgpNpower'                         => array('name' => 'Npower UPS',                                  'type' => 'ups'),
  'lgpGXT2Dual'                       => array('name' => 'GXT2 Dual Inverter',                          'type' => 'ups'),
  'lgpPowerSureInteractive2'          => array('name' => 'PowerSure Interactive 2 UPS',                 'type' => 'ups'),
  'lgpNX'                             => array('name' => 'ENPC Nx UPS',                                 'type' => 'ups'),
  'lgpHiNet'                          => array('name' => 'Hiross HiNet UPS',                            'type' => 'ups'),
  'lgpNXL'                            => array('name' => 'NXL UPS',                                     'type' => 'ups'),
  'lgpSuper400'                       => array('name' => 'Super 400 UPS',                               'type' => 'ups'),
  'lgpSeries600or610'                 => array('name' => 'Series 600/610 UPS',                          'type' => 'ups'),
  'lgpSeries300'                      => array('name' => 'Series 300 UPS',                              'type' => 'ups'),
  'lgpSeries610SMS'                   => array('name' => 'Series 610 Single Module System (SMS) UPS',   'type' => 'ups'),
  'lgpSeries610MMU'                   => array('name' => 'Series 610 Multi Module Unit (MMU) UPS',      'type' => 'ups'),
  'lgpSeries610SCC'                   => array('name' => 'Series 610 System Control Cabinet (SCC) UPS', 'type' => 'ups'),
  'lgpNXr'                            => array('name' => 'APM UPS',                                     'type' => 'ups'),
  // AcProducts - Liebert Environmental Air Conditioning Registrations
  'lgpAdvancedMicroprocessor'         => array('name' => 'Environmental Advanced Microprocessor control',        'type' => 'environment'),
  'lgpStandardMicroprocessor'         => array('name' => 'Environmental Standard Microprocessor control',        'type' => 'environment'),
  'lgpMiniMate2'                      => array('name' => 'Environmental Mini-Mate 2',                            'type' => 'environment'),
  'lgpHimod'                          => array('name' => 'Environmental Himod',                                  'type' => 'environment'),
  'lgpCEMS100orLECS15'                => array('name' => 'Australia Environmental CEMS100 and LECS15 control',   'type' => 'environment'),
  'lgpIcom'                           => array('name' => 'Environmental iCOM control',                           'type' => 'environment'),
  'lgpIcomPA'                         => array('name' => 'iCOM PA (Floor mount) Environmental',                  'type' => 'environment'),
  'lgpIcomXD'                         => array('name' => 'iCOM XD (Rack cooling with compressor) Environmental', 'type' => 'environment'),
  'lgpIcomXP'                         => array('name' => 'iCOM XP (Pumped refrigerant) Environmental',           'type' => 'environment'),
  'lgpIcomSC'                         => array('name' => 'iCOM SC (Chiller) Environmental',                      'type' => 'environment'),
  'lgpIcomCR'                         => array('name' => 'iCOM CR (Computer Row) Environmental',                 'type' => 'environment'),
  // iCOM PA Family - Liebert PA (Floor mount) Environmental Registrations
  'lgpIcomPAtypeDS'                   => array('name' => 'DS Environmental',                            'type' => 'environment'),
  'lgpIcomPAtypeHPM'                  => array('name' => 'HPM Environmental',                           'type' => 'environment'),
  'lgpIcomPAtypeChallenger'           => array('name' => 'Challenger Environmental',                    'type' => 'environment'),
  'lgpIcomPAtypePeX'                  => array('name' => 'PeX Environmental',                           'type' => 'environment'),
  'lgpIcomPAtypeDeluxeSys3'           => array('name' => 'Deluxe System 3 Environmental',               'type' => 'environment'),
  'lgpIcomPAtypeJumboCW'              => array('name' => 'Jumbo CW Environmental',                      'type' => 'environment'),
  'lgpIcomPAtypeDSE'                  => array('name' => 'DSE Environmental',                           'type' => 'environment'),
  'lgpIcomPAtypePEXS'                 => array('name' => 'PEX-S Environmental',                         'type' => 'environment'),
  'lgpIcomPAtypePDX'                  => array('name' => 'PDX - PCW Environmental',                     'type' => 'environment'),
  // iCOM XD Family - Liebert XD Environmental Registrations
  'lgpIcomXDtypeXDF'                  => array('name' => 'XDF Environmental',                           'type' => 'environment'),
  'lgpIcomXDtypeXDFN'                 => array('name' => 'XDFN Environmental',                          'type' => 'environment'),
  'lgpIcomXPtypeXDP'                  => array('name' => 'XDP Environmental',                           'type' => 'environment'),
  'lgpIcomXPtypeXDPCray'              => array('name' => 'XDP Environmental products for Cray',         'type' => 'environment'),
  'lgpIcomXPtypeXDC'                  => array('name' => 'XDC Environmental',                           'type' => 'environment'),
  'lgpIcomXPtypeXDPW'                 => array('name' => 'XDP-W Environmental',                         'type' => 'environment'),
  // iCOM SC Family - Liebert SC (Chillers) Environmental Registrations
  'lgpIcomSCtypeHPC'                  => array('name' => 'HPC Environmental',                           'type' => 'environment'),
  'lgpIcomSCtypeHPCSSmall'            => array('name' => 'HPC-S Small',                                 'type' => 'environment'),
  'lgpIcomSCtypeHPCSLarge'            => array('name' => 'HPC-S Large',                                 'type' => 'environment'),
  'lgpIcomSCtypeHPCR'                 => array('name' => 'HPC-R',                                       'type' => 'environment'),
  'lgpIcomSCtypeHPCM'                 => array('name' => 'HPC-M',                                       'type' => 'environment'),
  'lgpIcomSCtypeHPCL'                 => array('name' => 'HPC-L',                                       'type' => 'environment'),
  'lgpIcomSCtypeHPCW'                 => array('name' => 'HPC-W',                                       'type' => 'environment'),
  // iCOM CR Family - Liebert CR (Computer Row) Environmental Registrations
  'lgpIcomCRtypeCRV'                  => array('name' => 'CRV Environmental',                           'type' => 'environment'),
  // PowerConditioningProducts - Liebert Power Conditioning Registrations
  'lgpPMP'                            => array('name' => 'PMP (Power Monitoring Panel)',                'type' => 'power'),
  'lgpEPMP'                           => array('name' => 'EPMP (Extended Power Monitoring Panel)',      'type' => 'power'),
  // Transfer Switch Products - Liebert Transfer Switch Registrations
  'lgpStaticTransferSwitchEDS'        => array('name' => 'EDS Static Transfer Switch',                  'type' => 'network'),
  'lgpStaticTransferSwitch1'          => array('name' => 'Static Transfer Switch 1',                    'type' => 'network'),
  'lgpStaticTransferSwitch2'          => array('name' => 'Static Transfer Switch 2',                    'type' => 'network'),
  'lgpStaticTransferSwitch2FourPole'  => array('name' => 'Static Transfer Switch 2 - 4Pole',            'type' => 'network'),
  // MultiLink Products - Liebert MultiLink Registrations
  'lgpMultiLinkBasicNotification'     => array('name' => 'MultiLink MLBN device proxy',                 'type' => 'power'),
  // Power Distribution Products - Liebert Power Conditioning Distribution
  'lgpRackPDU'                        => array('name' => 'Rack Power Distribution Products (RPDU)',     'type' => 'pdu'),
  'lgpMPX'                            => array('name' => 'MPX product distribution (PDU)',              'type' => 'pdu'),
  'lgpMPH'                            => array('name' => 'MPH product distribution (PDU)',              'type' => 'pdu'),
  'lgpRackPDU2'                       => array('name' => 'Rack Power Distribution Products 2 (RPDU2)',  'type' => 'pdu'),
  'lgpRPC2kMPX'                       => array('name' => 'MPX product distribution 2 (PDU2)',           'type' => 'pdu'),
  'lgpRPC2kMPH'                       => array('name' => 'MPH product distribution 2 (PDU2)',           'type' => 'pdu'),
  // Combined System Product Registrations
  'lgpPMPandLDMF'                     => array('name' => 'PMP version 4/LDMF',                          'type' => 'power'),
  'lgpPMPgeneric'                     => array('name' => 'PMP version 4',                               'type' => 'power'),
  'lgpPMPonFPC'                       => array('name' => 'PMP version 4 for FPC',                       'type' => 'power'),
  'lgpPMPonPPC'                       => array('name' => 'PMP version 4 for PPC',                       'type' => 'power'),
  'lgpPMPonFDC'                       => array('name' => 'PMP version 4 for FDC',                       'type' => 'power'),
  'lgpPMPonRDC'                       => array('name' => 'PMP version 4 for RDC',                       'type' => 'power'),
  'lgpPMPonEXC'                       => array('name' => 'PMP version 4 for EXC',                       'type' => 'power'),
  'lgpPMPonSTS2'                      => array('name' => 'PMP version 4 for STS2',                      'type' => 'power'),
  'lgpPMPonSTS2PDU'                   => array('name' => 'PMP version 4 for STS2/PDU',                  'type' => 'power'),
);

// Rewrite functions

/**
 * Rewrites device hardware based on device os/sysObjectID and hw definitions
 *
 * @param array $device Device array required keys -> os, sysObjectID
 * @param string $sysObjectID_new If passed, than use "new" sysObjectID instead from device array
 * @return string Device hw name or empty string
 */
function rewrite_definition_hardware($device, $sysObjectID_new = NULL)
{
  $model_array = get_model_array($device, $sysObjectID_new);
  if (is_array($model_array) && isset($model_array['name']))
  {
    return $model_array['name'];
  }
}

/**
 * Rewrites device type based on device os/sysObjectID and hw definitions
 *
 * @param array $device Device array required keys -> os, sysObjectID
 * @param string $sysObjectID_new If passed, than use "new" sysObjectID instead from device array
 * @return string Device type or empty string
 */
function rewrite_definition_type($device, $sysObjectID_new = NULL)
{
  $model_array = get_model_array($device, $sysObjectID_new);
  if (is_array($model_array) && isset($model_array['type']))
  {
    return $model_array['type'];
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_extreme_hardware($hardware)
{

  $hardware = str_replace('EXTREME-BASE-MIB::', '', $hardware);

  // Common replaces
  $from = array();
  $to   = array();
  $from[] = '/^summit/';       $to[] = 'Summit ';               // summitX440G2-48t-10G4-DC
  $from[] = '/^x/';            $to[] = 'Summit X';              // x690-48x-4q-2c
  $from[] = '/^isw/';          $to[] = 'Industrial Switch isw'; // isw-8GP-G4
  $from[] = '/^one/';          $to[] = 'One';                   // oneC-A-600
  $from[] = '/^aviatCtr/';     $to[] = 'CTR';                   // aviatCtr-8440
  $from[] = '/^e4g/';          $to[] = 'E4G';                   // e4g-200-12x
  $from[] = '/^bdx8/';         $to[] = 'BlackDiamond X';        // bdx8
  $from[] = '/^bd/';           $to[] = 'BlackDiamond ';         // bd20804
  $from[] = '/^blackDiamond/'; $to[] = 'BlackDiamond ';         // blackDiamond6816
  $from[] = '/^ags/';          $to[] = 'AGS';                   // ags150-24p
  $from[] = '/^altitude/';     $to[] = 'Altitude ';             // altitude4700
  $from[] = '/^sentriant/';    $to[] = 'Sentriant ';            // sentriantPS200v1
  $from[] = '/^nwi/';          $to[] = 'NWI';                   // nwi-e450a
  $from[] = '/^enetSwitch/';   $to[] = 'EnetSwitch ';           // enetSwitch24Port
  $hardware = preg_replace($from, $to, $hardware);

  return $hardware;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_cpqida_hardware($hardware)
{
  global $rewrite_cpqida_hardware;

  $hardware = array_str_replace($rewrite_cpqida_hardware, $hardware);

  return ($hardware);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_liebert_hardware($hardware)
{
  global $rewrite_liebert_hardware;

  if (isset($rewrite_liebert_hardware[$hardware]))
  {
    $hardware = $rewrite_liebert_hardware[$hardware]['name'];
  }

  return ($hardware);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_breeze_type($type)
{
  $type = strtolower($type);
  if (isset($GLOBALS['rewrite_breeze_type'][$type]))
  {
    return $GLOBALS['rewrite_breeze_type'][$type];
  } else {
    return strtoupper($type);
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_unix_hardware($descr, $hw = NULL)
{
  $hardware = (!empty($hw) ? trim($hw): 'Generic');

  if     (preg_match('/i[3456]86/i',    $descr)) { $hardware .= ' x86 [32bit]'; }
  elseif (preg_match('/x86_64|amd64/i', $descr)) { $hardware .= ' x86 [64bit]'; }
  elseif (stristr($descr, 'ia64'))    { $hardware .= ' IA [64bit]'; }
  elseif (stristr($descr, 'ppc'))     { $hardware .= ' PPC [32bit]'; }
  elseif (stristr($descr, 'sparc32')) { $hardware .= ' SPARC [32bit]'; }
  elseif (stristr($descr, 'sparc64')) { $hardware .= ' SPARC [64bit]'; }
  elseif (stristr($descr, 'mips64'))  { $hardware .= ' MIPS [64bit]'; }
  elseif (stristr($descr, 'mips'))    { $hardware .= ' MIPS [32bit]'; }
  elseif (preg_match('/armv(\d+)/i', $descr, $matches))
  {
    $hardware .= ' ARMv' . $matches[1];
  }
  //elseif (stristr($descr, 'armv5'))   { $hardware .= ' ARMv5'; }
  //elseif (stristr($descr, 'armv6'))   { $hardware .= ' ARMv6'; }
  //elseif (stristr($descr, 'armv7'))   { $hardware .= ' ARMv7'; }
  elseif (stristr($descr, 'armv'))    { $hardware .= ' ARM'; }

  return ($hardware);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_ftos_vlanid($device, $ifindex)
{
  // damn DELL use them one known indexes
  //dot1qVlanStaticName.1107787777 = Vlan 1
  //dot1qVlanStaticName.1107787998 = mgmt
  $ftos_vlan = dbFetchCell('SELECT ifName FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ?', array($device['device_id'], $ifindex));
  list(,$vlanid) = explode(' ', $ftos_vlan);
  return $vlanid;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_iftype($type)
{
  $type = array_key_replace($GLOBALS['config']['rewrites']['iftype'], $type);
  return $type;
}

// NOTE. For graphs use $escape = FALSE
// TESTME needs unit testing
function rewrite_ifname($inf, $escape = TRUE)
{
  $inf = array_str_replace($GLOBALS['config']['rewrites']['ifname'], $inf);
  //$inf = array_preg_replace($GLOBALS['rewrite_ifname_regexp'], $inf); // use os definitions instead
  $inf = preg_replace('/\ {2,}/', ' ', $inf); // Clean multiple spaces
  if ($escape) { $inf = escape_html($inf); } // By default use htmlentities

  return trim($inf);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_adslLineType($adslLineType)
{
  $rewrite_adslLineType = $GLOBALS['config']['rewrites']['adslLineType'];

  if (isset($rewrite_adslLineType[$adslLineType]))
  {
    $adslLineType = $rewrite_adslLineType[$adslLineType];
  }

  return $adslLineType;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function short_hostname($hostname, $len = NULL, $escape = TRUE)
{
  $len = (is_numeric($len) ? (int)$len : (int)$GLOBALS['config']['short_hostname']['length']);

  if (function_exists('custom_shorthost'))
  {
    $short_hostname = custom_shorthost($hostname, $len);
  }
  else if (function_exists('custom_short_hostname'))
  {
    $short_hostname = custom_short_hostname($hostname, $len);
  } else {

    if (get_ip_version($hostname)) { return $hostname; } // If hostname is IP address, always return full hostname

    $parts = explode('.', $hostname);
    $short_hostname = $parts[0];
    $i = 1;
    while ($i < count($parts) && strlen($short_hostname.'.'.$parts[$i]) < $len)
    {
      $short_hostname = $short_hostname.'.'.$parts[$i];
      $i++;
    }
  }
  if ($escape) { $short_hostname = escape_html($short_hostname); }

  return $short_hostname;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// NOTE, this is shorting for ifAlias! Can be rename to short_ifalias() ?
function short_port_descr($descr, $len = NULL, $escape = TRUE)
{
  $len = (is_numeric($len) ? (int)$len : (int)$GLOBALS['config']['short_port_descr']['length']);

  if (function_exists('custom_short_port_descr'))
  {
    $descr = custom_short_port_descr($descr, $len);
  } else {

    list($descr) = explode("(", $descr);
    list($descr) = explode("[", $descr);
    list($descr) = explode("{", $descr);
    list($descr) = explode("|", $descr);
    list($descr) = explode("<", $descr);
    $descr = truncate(trim($descr), $len, '');
  }
  if ($escape) { $descr = escape_html($descr); }

  return $descr;
}

// NOTE. For graphs use $escape = FALSE
// NOTE. short_ifname() differs from short_port_descr()
// short_ifname('FastEternet0/10') == 'Fa0/10'
// DOCME needs phpdoc block
// TESTME needs unit testing
function short_ifname($if, $len = NULL, $escape = TRUE)
{
  $len = (is_numeric($len) ? (int)$len : FALSE);

  $if = rewrite_ifname($if, $escape);

  $if = array_str_replace($GLOBALS['config']['rewrites']['shortif'], $if);
  $if = array_preg_replace($GLOBALS['config']['rewrites']['shortif_regexp'], $if);
  if ($len) { $if = truncate($if, $len, ''); }

  return $if;
}

/**
 * Rewrite name or description of an entity.
 * Note, not same as entity_rewrite(), since this function impersonally of entity db entry.
 *
 * @param string      $string      Entity name or description
 * @param null|string $entity_type Entity type, by default use common rewrites
 * @param bool        $escape      Escape return string
 *
 * @return string
 */
function rewrite_entity_name($string, $entity_type = NULL, $escape = TRUE) {
  $string = array_str_replace($GLOBALS['config']['rewrites']['entity_name'], $string, TRUE); // case-sensitive
  $string = array_preg_replace($GLOBALS['config']['rewrites']['entity_name_regexp'], $string);

  $string = preg_replace('/\s{2,}/', ' ', $string); // multiple spaces to single space
  $string = preg_replace('/([a-z])([A-Z]{2,})/', '$1 $2', $string); // turn "fixedAC" into "fixed AC"

  // Entity specific rewrites (additionally to common)
  switch ($entity_type) {
    case 'port':
      return rewrite_ifname($string, $escape);
      break;

    case 'processor':
      $string = str_replace([ "Routing Processor", "Route Processor", "Switching Processor" ], [ "RP", "RP", "SP" ], $string);
      break;

    case 'storage':
      $string = rewrite_storage($string);
      break;
  }

  if ($escape) {
    $string = escape_html($string);
  }

  return trim($string);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_storage($string)
{
  $string = preg_replace('/.*mounted on: (.*)/', "\\1", $string);                 // JunOS
  $string = preg_replace("/(.*), type: (.*), dev: (.*)/", "\\1", $string);        // FreeBSD: '/mnt/Media, type: zfs, dev: Media'
  $string = preg_replace("/(.*) Label:(.*) Serial Number (.*)/", "\\1", $string); // Windows: E:\ Label:Large Space Serial Number 26ad0d98

  return trim($string);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_location($location)
{
  global $config, $attribs;

  $location = str_replace(array('\"', '"'), '', $location);

  // Allow override sysLocation from DB.
  if ($attribs['override_sysLocation_bool'])
  {
    $new_location = $attribs['override_sysLocation_string'];
    $by = 'DB override';
  }
  // This will call a user-defineable function to rewrite the location however the user wants.
  if (!isset($new_location) && function_exists('custom_rewrite_location'))
  {
    $new_location = custom_rewrite_location($location);
    $by = 'function custom_rewrite_location()';
  }
  // This uses a statically defined array to map locations.
  if (!isset($new_location))
  {
    if (isset($config['location']['map'][$location]))
    {
      $new_location = $config['location']['map'][$location];
      $by = '$config[\'location\'][\'map\']';
    }
    else if (isset($config['location']['map_regexp']))
    {
      foreach ($config['location']['map_regexp'] as $pattern => $entry)
      {
        if (preg_match($pattern, $location))
        {
          $new_location = $entry;
          $by = '$config[\'location\'][\'map_regexp\']';
          break; // stop foreach
        }
      }
    }
  }

  if (isset($new_location))
  {
    print_debug("sysLocation rewritten from '$location' to '$new_location' by $by.");
    $location = $new_location;
  }
  return $location;
}

/**
 * This function cleanup vendor/manufacturer name and
 * unification multiple same names to single common vendor name.
 */
function rewrite_vendor($string)
{
  global $config;

  $clean_name = $string;

  // By first, clean all additional abbreviations in vendor name
  $clean_array = array(
    '/(?:\s+|,\s*)(?:inc|corp|comm|co|elec|tech|llc)(?![a-z])/i' => '', // abbreviations
    '/(?:\s+|,\s*)(?:Systems|Computer|Corporation|Company|Communications|Networks|Electronics)(?![a-z])/i' => '',
  );
  foreach ($clean_array as $pattern => $replace)
  {
    if (preg_match_all($pattern, $string, $matches))
    {
      foreach($matches[0] as $match)
      {
        $clean_name = str_replace($match, $replace, $clean_name);
      }
    }
  }
  $clean_name = trim($clean_name, " \t\n\r\0\x0B.,;'\"()"); // Clean punctuations after rewrites

  // Remove string duplicates
  $clean_name_array = array_unique(explode(' ', $clean_name));
  $clean_name = implode(' ', $clean_name_array);

  // Now try to find exist vendor definition
  $clean_key = safename(strtolower($clean_name));
  if (isset($config['vendors'][$clean_key]))
  {
    // Founded definition by original string
    return $config['vendors'][$clean_key]['name'];
  }
  $key  = safename(strtolower($string));
  if (isset($config['vendors'][$key]))
  {
    // Founded definition by clean string
    return $config['vendors'][$key]['name'];
  }

  // Now try to find definition by full search in definitions
  foreach ($config['vendors'] as $vendor_key => $entry)
  {
    if (strlen($entry['name']) <= 3)
    {
      // In case, when vendor name too short, that seems as abbr, ie GE
      if (strcasecmp($clean_name, $entry['name']) == 0 || // Cleaned string
          strcasecmp($string, $entry['name']) == 0)       // Original string
      {
        // Founded in definitions
        return $entry['name'];
      }
      $search_array = array();
    } else {
      $search_array = array($entry['name']);
    }

    if (isset($entry['full_name'])) { $search_array[] = $entry['full_name']; } // Full name of vendor
    if (isset($entry['alternatives'])) { $search_array = array_merge($search_array, $entry['alternatives']); } // Alternative (possible) names of vendor

    if (str_istarts($clean_name, $search_array) || // Cleaned string
        str_istarts($string, $search_array))       // Original string
    {
      // Founded in definitions
      return $entry['name'];
    }
  }

  if (strlen($clean_name) < 5 ||
      preg_match('/^([A-Z0-9][a-z]+[\ \-]?[A-Z]+[a-z]*|[A-Z0-9]+[\ \-][A-Za-z]+|[A-Z]{2,}[a-z]+)/', $clean_name))
  {
    // This is MultiCase name or small name, keeps as is
    //echo("\n"); print_error($clean_name . ': MULTICASE ');
    return $clean_name;
  } else {
    // Last, just return cleaned name
    //echo("\n"); print_error($clean_name . ': UCWORDS');
    return ucwords(strtolower($clean_name));
  }
}

// Underlying rewrite functions

/**
 * Replace strings equals to key string with appropriate value from array: key -> replace
 *
 * @param array  $array     Array with string and replace string (key -> replace)
 * @param string $string    String subject where replace
 * @return string           Result string with replaced strings
 */
function array_key_replace($array, $string)
{
  if (array_key_exists($string, $array))
  {
    $string = $array[$string];
  }
  return $string;
}

/**
 * Replace strings matched by key string with appropriate value from array: string -> replace
 * Note, by default CASE INSENSITIVE
 *
 * @param array  $array  Array with string and replace string (string -> replace)
 * @param string $string String subject where replace
 * @param bool   $case_sensitive Case sensitive (default FALSE)
 *
 * @return string           Result string with replaced strings
 */
function array_str_replace($array, $string, $case_sensitive = FALSE)
{
  $search = array();
  $replace = array();

  foreach ($array as $key => $entry)
  {
    $search[] = $key;
    $replace[] = $entry;
  }

  if ($case_sensitive)
  {
    $string = str_replace($search, $replace, $string);
  } else {
    $string = str_ireplace($search, $replace, $string);
  }

  return $string;
}

/**
 * Replace strings matched by pattern key with appropriate value from array: pattern -> replace
 *
 * @param array  $array     Array with pattern and replace string (pattern -> replace)
 * @param string $string    String subject where replace
 * @return string           Result string with replaced patterns
 */
function array_preg_replace($array, $string)
{
  foreach ($array as $search => $replace)
  {
    $string = preg_replace($search, $replace, $string);
  }

  return $string;
}

/**
 * Replace tag(s) inside string with appropriate key from array: %tag% -> $array['tag']
 * Note, not exist tags in array will cleaned from string!
 *
 * @param array        $array     Array with keys appropriate for tags, wich used for replace
 * @param string|array $string    String with tag(s) for replace (between percent sign, ie: %key%)
 * @param string       $tag_scope Scope string for detect tag(s), default: %
 * @return string|array           Result string with replaced tags
 */
function array_tag_replace($array, $string, $tag_scope = '%') {
  // If passed array, do tag replace recursive (for values only)
  if (is_array($string)) {
    foreach ($string as $key => $value) {
      $string[$key] = array_tag_replace($array, $value, $tag_scope);
    }
    return $string;
  }

  // Keep non string values as is
  if (!is_string($string)) {
    return $string;
  }

  $new_array = [];

  // Generate new array of tags including delimiter
  foreach($array as $key => $value) {
    $new_array[$tag_scope.$key.$tag_scope] = $value;
  }

  // Replace tags
  $string = array_str_replace($new_array, $string, TRUE);
  //$string = strtr($string, $new_array); // never use this slowest function in the world

  // Remove unused tags
  if ($tag_scope !== '/') {
    $pattern_clean = '/'.$tag_scope.'\S+?'.$tag_scope.'/';
  } else {
    $pattern_clean = '%/\S+?/%';
  }
  $string = preg_replace($pattern_clean, '', $string);

  return $string;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function country_from_code($code)
{
  global $config;

  $countries = $config['rewrites']['countries'];
  $code = strtoupper(trim($code));
  switch (strlen($code))
  {
    case 0:
    case 1:
      return "Unknown";
    case 2: // ISO 2
    case 3: // ISO 3
      // Return country by code
      if (array_key_exists($code, $countries))
      {
        return $countries[$code];
      }
      return "Unknown";
    default:
      // Try to search by country name
      $names = array_unique(array_values($countries));
      foreach ($names as $country)
      {
        if (str_istarts($country, $code) || str_istarts($code, $country)) { return $country; }
      }
      return "Unknown";
  }
}

// EOF
