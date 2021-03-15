<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage syslog
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

// FIXME use db functions properly

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_cache($host, $value)
{
  global $dev_cache;

  if (empty($host)) { return NULL; }

  // Check cache expiration
  $now = time();
  $expired = TRUE;
  if (isset($dev_cache[$host]['lastchecked']))
  {
    if (($now - $dev_cache[$host]['lastchecked']) < 600) { $expired = FALSE; } // will expire after 10 min
  }
  if ($expired) { $dev_cache[$host]['lastchecked'] = $now; }

  if (!isset($dev_cache[$host][$value]) || $expired)
  {
    switch($value)
    {
      case 'device_id':
        // Try by map in config
        if (isset($GLOBALS['config']['syslog']['host_map'][$host]))
        {
          $new_host = $GLOBALS['config']['syslog']['host_map'][$host];
          if (is_numeric($new_host))
          {
            // Check if device id exist
            $dev_cache[$host]['device_id'] = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `device_id` = ?', array($new_host));
          } else {
            $dev_cache[$host]['device_id'] = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `hostname` = ? OR `sysName` = ?', array($new_host, $new_host));
          }
          // If syslog host map correct, return device id or try onward
          if ($dev_cache[$host]['device_id'])
          {
            return $dev_cache[$host]['device_id'];
          }
        }

        // Localhost IPs, try detect as local system
        if (in_array($host, array('127.0.0.1', '::1')))
        {
          if ($localhost_id = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `hostname` = ?', array(get_localhost())))
          {
            $dev_cache[$host]['device_id'] = $localhost_id;
          }
          elseif ($localhost_id = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `sysName` = ?', array(get_localhost())))
          {
            $dev_cache[$host]['device_id'] = $localhost_id;
          }
          // NOTE in other cases localhost IPs associated with random device
        } else {
          // Try by hostname
          $dev_cache[$host]['device_id'] = dbFetchCell('SELECT `device_id` FROM `devices` WHERE `hostname` = ? OR `sysName` = ?', array($host, $host));
        }

        // If failed, try by IP
        if (!is_numeric($dev_cache[$host]['device_id']))
        {
          $ip = $host;

          $ip_version = get_ip_version($ip);
          if ($ip_version !== FALSE)
          {
            if ($ip_version === 6 && preg_match('/::ffff:(\d+\.\d+\.\d+\.\d+)/', $ip, $matches))
            {
              // IPv4 mapped to IPv6, like ::ffff:192.0.2.128
              // See: http://jira.observium.org/browse/OBSERVIUM-1274
              $ip = $matches[1];
              $ip_version = 4;
            }
            elseif ($ip_version === 6)
            {
              $ip = Net_IPv6::uncompress($ip, TRUE);
            }

            // Detect associated device by IP address, exclude deleted ports
            // IS NULL allow to search addresses without associated port
            $query = 'SELECT * FROM `ipv'.$ip_version.'_addresses` LEFT JOIN `ports` USING (`device_id`, `port_id`) WHERE `ipv'.$ip_version.'_address` = ? AND (`ports`.`deleted` = ? OR `ports`.`deleted` IS NULL);';
            $addresses = dbFetchRows($query, [ $ip, 0 ]);
            $address_count = count($addresses);

            if ($address_count)
            {
              $dev_cache[$host]['device_id'] = $addresses[0]['device_id'];

              // Additional checks if multiple addresses found
              if ($address_count > 1)
              {
                foreach ($addresses as $entry)
                {
                  $device_tmp = device_by_id_cache($entry['device_id']);
                  if ($device_tmp['disabled'] || !$device_tmp['status'])                     { continue; } // Skip disabled and down devices
                  elseif ($entry['ifAdminStatus'] === 'down' ||                                            // Skip disabled ports
                          in_array($entry['ifOperStatus'], array('down', 'lowerLayerDown'))) { continue; } // Skip down ports

                  // Override cached host device_id
                  $dev_cache[$host]['device_id'] = $entry['device_id'];
                  break; // End loop on first founded entry
                }
                unset($device_tmp);
              }

            }
          }
        }
        break;
      case 'os':
      case 'version':
        if ($device_id = get_cache($host, 'device_id'))
        {
          $dev_cache[$host][$value] = dbFetchCell('SELECT `'.$value.'` FROM `devices` WHERE `device_id` = ?', array($device_id));
        } else {
          return NULL;
        }
        break;
      case 'os_group':
        $os = get_cache($host, 'os');
        $dev_cache[$host]['os_group'] = (isset($GLOBALS['config']['os'][$os]['group']) ? $GLOBALS['config']['os'][$os]['group'] : '');
        break;
      default:
        return NULL;
    }
  }

  return $dev_cache[$host][$value];
}

function cache_syslog_rules()
{

  $rules = array();
  foreach(dbFetchRows("SELECT * FROM `syslog_rules` WHERE `la_disable` = ?", array('0')) as $lat)
  {
    $rules[$lat['la_id']] = $lat;
  }

  return $rules;

}

function cache_syslog_rules_assoc()
{
  $device_rules = array();
  foreach (dbFetchRows("SELECT * FROM `syslog_rules_assoc`") as $laa)
  {

    //print_r($laa);

    if ($laa['entity_type'] == 'group')
    {
      $devices = get_group_entities($laa['entity_id']);
      foreach($devices as $dev_id)
      {
        $device_rules[$dev_id][$laa['la_id']] = TRUE;
      }
    }
    elseif ($laa['entity_type'] == 'device')
    {
      $device_rules[$laa['entity_id']][$laa['la_id']] = TRUE;
    }
  }
  return $device_rules;
}


// DOCME needs phpdoc block
// TESTME needs unit testing
function process_syslog($line, $update)
{
  global $config;
  global $rules;
  global $device_rules;
  global $maint;

  $entry = process_syslog_line($line);

  if ($entry === FALSE)
  {
    // Incorrect/filtered syslog entry
    return FALSE;
  }
  elseif ($entry['device_id'])
  {
    // Main syslog entry with detected device
    if ($update)
    {
      // Accurate timestamps

      $log_id = dbInsert(
        array(
          'device_id' => $entry['device_id'],
          'host'      => $entry['host'],
          'program'   => $entry['program'],
          'facility'  => $entry['facility'],
          'priority'  => $entry['priority'],
          'level'     => $entry['level'],
          'tag'       => $entry['tag'],
          'msg'       => $entry['msg'],
          // Do not pass parsed timestamp
          //'timestamp' => $entry['timestamp']
        ),
        'syslog'
      );
    }

//$req_dump = print_r(array($entry, $rules, $device_rules), TRUE);
//$fp = fopen('/tmp/syslog.log', 'a');
//fwrite($fp, $req_dump);
//fclose($fp);

      // Add syslog alert into notification queue
      $notification_type = 'syslog';

      /// FIXME, I not know how 'syslog_rules_assoc' is filled, I pass rules to all devices
      /// FIXME, this is copy-pasted from above, while not have WUI for syslog_rules_assoc
      foreach ($rules as $la_id => $rule)
      {
        // Skip processing syslog rule if device rule not cached (see: cache_syslog_rules_assoc() )
        if (!empty($device_rules) && !isset($device_rules[$entry['device_id']][$la_id]))
        {
          continue;
        }

        if (preg_match($rule['la_rule'], $entry['msg_orig'], $matches)) // Match syslog by rule pattern
        {

          // Mark no notification during maintenance
          if (isset($maint['device'][$entry['device_id']]) || (isset($maint['global']) && $maint['global'] > 0))
          {
            $notified = '-1';
          } else {
            $notified = '0';
          }

          // Detect some common entities patterns in syslog message

          $log_id = dbInsert(array('device_id' => $entry['device_id'],
                                   'la_id'     => $la_id,
                                   'syslog_id' => $log_id,
                                   'timestamp' => $entry['timestamp'],
                                   'program'   => $entry['program'],
                                   'message'   => $entry['msg'], // Use cleared msg instead original (see process_syslog_line() tests)
                                   'notified'  => $notified), 'syslog_alerts');

          // Add notification to queue
          if ($notified != '-1')
          {
            $message_tags = syslog_generate_tags($entry, $rule);

            // Get contacts for $la_id
            $contacts = get_alert_contacts($entry['device_id'], $la_id, $notification_type);

            foreach($contacts as $contact)
            {

              $notification = [
                'device_id'             => $entry['device_id'],
                'log_id'                => $log_id,
                'aca_type'              => $notification_type,
                'severity'              => $entry['priority'],
                'endpoints'             => json_encode($contact),
                //'message_graphs'        => $message_tags['ENTITY_GRAPHS_ARRAY'],
                'notification_added'    => time(),
                'notification_lifetime' => 300,                   // Lifetime in seconds
                'notification_entry'    => json_encode($entry),   // Store full alert entry for use later if required (not sure that this needed)
              ];
              //unset($message_tags['ENTITY_GRAPHS_ARRAY']);
              $notification['message_tags'] = json_encode($message_tags);
              $notification_id = dbInsert($notification, 'notifications_queue');
            } // End foreach($contacts)
          } // End if($notified)
        }  // End if syslog rule matches
      } // End foreach($rules)

    unset($os);
  }
  elseif ($config['syslog']['unknown_hosts'])
  {
    // EXPERIMENTAL. Host not known, currently not used.
    if ($update)
    {
      // Store entries for unknown hosts with NULL device_id
      $log_id = dbInsert(
        array(
          //'device_id' => $entry['device_id'], // Default is NULL
          'host'      => $entry['host'],
          'program'   => $entry['program'],
          'facility'  => $entry['facility'],
          'priority'  => $entry['priority'],
          'level'     => $entry['level'],
          'tag'       => $entry['tag'],
          'msg'       => $entry['msg'],
          'timestamp' => $entry['timestamp']
        ),
        'syslog'
      );
      //var_dump($entry);
    }
  }

  return $entry;
}

function syslog_generate_tags($entry, $rule)
{
  $alert_unixtime = strtotime($entry['timestamp']);

  $la_id = $rule['la_id'];

  // -1 mean set severity based on syslog priority
  //$severity = ($rule['la_severity'] >= 0) ? $rule['la_severity'] : $entry['priority'];
  $severity = $entry['priority'];
  $severity_def = $GLOBALS['config']['syslog']['priorities'][$severity];
  $alert_emoji = $severity_def['emoji'];
  $alert_color = $severity_def['color'];

  $device = device_by_id_cache($entry['device_id']);

  $message_tags = array(
    'ALERT_STATE'         => "SYSLOG",
    'ALERT_EMOJI'         => get_icon_emoji($alert_emoji),   // https://unicodey.com/emoji-data/table.htm
    'ALERT_EMOJI_NAME'    => $alert_emoji,
    'ALERT_STATUS'        => 0,                              // Tag for templates (0 - ALERT, 1 - RECOVERY, 2 - DELAYED, 3 - SUPPRESSED)
    'ALERT_SEVERITY'      => ucfirst($severity_def['name']), // Critical, Warning, Informational, Other
    'ALERT_COLOR'         => $alert_color,
    'ALERT_URL'           => generate_url([ 'page'        => 'device',
                                            'device'      => $device['device_id'],
                                            'tab'         => 'alert',
                                            'entity_type' => 'syslog' ]),

    'ALERT_UNIXTIME'          => $alert_unixtime,                        // Standard unixtime
    'ALERT_TIMESTAMP'         => date('Y-m-d H:i:s P', $alert_unixtime), //           ie: 2000-12-21 16:01:07 +02:00
    'ALERT_TIMESTAMP_RFC2822' => date('r', $alert_unixtime),             // RFC 2822, ie: Thu, 21 Dec 2000 16:01:07 +0200
    'ALERT_TIMESTAMP_RFC3339' => date(DATE_RFC3339, $alert_unixtime),    // RFC 3339, ie: 2005-08-15T15:52:01+00:00
    'ALERT_ID'            => $la_id,
    'ALERT_MESSAGE'       => $rule['la_descr'],
    'CONDITIONS'          => $rule['la_rule'],
    'METRICS'             => $entry['msg'],

    // Syslog TAGs
    'SYSLOG_RULE'         => $rule['la_rule'],
    'SYSLOG_MESSAGE'      => $entry['msg'],
    'SYSLOG_PROGRAM'      => $entry['program'],
    'SYSLOG_TAG'          => $entry['tag'],
    'SYSLOG_FACILITY'     => $entry['facility'],

    // Device TAGs
    'DEVICE_HOSTNAME'     => $device['hostname'],
    'DEVICE_SYSNAME'      => $device['sysName'],
    //'DEVICE_SYSDESCR'     => $device['sysDescr'],
    'DEVICE_DESCRIPTION'  => $device['purpose'],
    'DEVICE_ID'           => $device['device_id'],
    'DEVICE_URL'          => generate_device_url($device),
    'DEVICE_LINK'         => generate_device_link($device, NULL, array('tab' => 'alerts', 'entity_type' => 'syslog')),
    'DEVICE_HARDWARE'     => $device['hardware'],
    'DEVICE_OS'           => $device['os_text'] . ' ' . $device['version'] . ($device['features'] ? ' (' . $device['features'] . ')' : ''),
    'DEVICE_TYPE'         => $device['type'],
    'DEVICE_LOCATION'     => $device['location'],
    'DEVICE_UPTIME'       => deviceUptime($device),
    'DEVICE_REBOOTED'     => format_unixtime($device['last_rebooted']),
  );

  $message_tags['TITLE'] = alert_generate_subject($device, 'SYSLOG', $message_tags);

  return $message_tags;
}

/**
 * Process syslog line. Convert raw syslog line (with observium format) into array.
 * Also rewrite some entries by device os.
 *
 * Observium template:
 * host||facility||priority||level||tag||timestamp||msg||program
 *
 * @param string $line Raw syslog line by observium template
 * @return array|false Array with processed syslog entry, or FALSE if incorrect/filtered msg.
 */
function process_syslog_line($line)
{
  global $config;

  // Compatibility with old param as array
  if (is_array($line)) { return $line; }

  $start_time = time();
  $entry = array(); // Init

  $entry_array = explode('||', trim($line));
  $entry['host']      = array_shift($entry_array);
  $entry['facility']  = array_shift($entry_array);
  $entry['priority']  = array_shift($entry_array);
  $entry['level']     = array_shift($entry_array);
  $entry['tag']       = array_shift($entry_array);
  $entry['timestamp'] = array_shift($entry_array);
  if (count($entry_array) > 2)
  {
    // Some time message have || inside:
    // 127.0.0.1||9||6||6||CRON[3196]:||2018-03-13 06:25:01|| (root) CMD (test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.daily ))||CRON
    $entry['program'] = array_pop($entry_array);
    $entry['msg']     = implode('||', $entry_array); // reimplode msg string with "||" inside
  } else {
    $entry['msg']     = array_shift($entry_array);
    $entry['program'] = array_shift($entry_array);
  }

  // Restore escaped quotes
  $entry['msg'] = str_replace([ '\"', "\'" ], [ '"', "'" ], $entry['msg']);

  // Filter by msg string
  if (str_exists($entry['msg'], $config['syslog']['filter']))
  {
    return FALSE;
  }

  // Accurate timestamp
  $entry['unixtime'] = strtotime($entry['timestamp']);
  if (abs($start_time - $entry['unixtime']) >= 60)
  {
    // Seems as wrong time synchronization on device/server or something else.
    // Use self time in this case
    $entry['timestamp'] = date('Y-m-d H:i:s', $start_time);
    $entry['unixtime']  = $start_time;
  }

  $entry['msg_orig'] = $entry['msg'];

  // Initial rewrites
  $entry['host']      = strtolower(trim($entry['host']));

  if (isset($config['syslog']['debug']) && $config['syslog']['debug'] &&
      !defined('__PHPUNIT_PHAR__')) // Skip on Unit tests
  {
    // Store RAW syslog line into debug.log
    logfile('debug.'.$entry['host'].'.syslog', $line);
  }

  // Rewrite priority/level/facility from strings to numbers
  $entry['priority']  = priority_string_to_numeric($entry['priority']);
  $entry['level']     = priority_string_to_numeric($entry['level']);
  if (isset($config['syslog']['facilities'][$entry['facility']]))
  {
    // Convert numeric facility to string
    $entry['facility'] = $config['syslog']['facilities'][$entry['facility']]['name'];
  }
  //$entry['facility']  = facility_string_to_numeric($entry['facility']);

  $entry['device_id'] = get_cache($entry['host'], 'device_id');
  //print_vars($entry);
  //print_vars($GLOBALS['dev_cache']);
  if ($entry['device_id'])
  {
    // Process msg/program for known os/os_group
    $os       = get_cache($entry['host'], 'os');
    $os_group = get_cache($entry['host'], 'os_group');

    // Detect message repeated
    if (strpos($entry['msg'], 'repeated ') !== FALSE && preg_match('/repeated \d+ times(?:\:\ +\[\s*(?<msg>.+)\])?\s*$/', $entry['msg'], $matches))
    {
      //var_dump($matches);
      if (isset($matches['msg']))
      {
        $entry['msg'] = $matches['msg'];
      } else {
        // Always skip unusefull entries 'message repeated X times' (without any message)
        return FALSE;
      }
    }

    // OS definition based syslog msg format
    if (isset($config['os'][$os]['syslog_msg']))
    {
      foreach ($config['os'][$os]['syslog_msg'] as $pattern)
      {
        if (preg_match($pattern, $entry['msg'], $matches))
        {
          if (OBS_DEBUG)
          {
            print_cli_table(array(array('syslog msg', $entry['msg']), array('matched pattern', $pattern)), NULL);
          }
          // Override founded msg/tag/program references
          if (isset($matches['msg']))     { $entry['msg']     = $matches['msg']; }
          if (isset($matches['program'])) { $entry['program'] = $matches['program']; }
          if (isset($matches['tag']))     { $entry['tag']     = $matches['tag']; }
          // Tags, also allowed multiple tagsX (0-9), started from 0
          $i = 0;
          while (isset($matches['tag'.$i]) && $matches['tag'.$i])
          {
            $entry['tag']  = rtrim($entry['tag'], ':'); // remove last :
            $entry['tag'] .= ','.$matches['tag'.$i];
            $i++;
          }
          break; // Stop other loop if pattern found
        }
      }
    }
    // OS definition based syslog program format
    if (isset($config['os'][$os]['syslog_program']) && strlen($entry['program']))
    {
      foreach ($config['os'][$os]['syslog_program'] as $pattern)
      {
        if (preg_match($pattern, $entry['program'], $matches))
        {
          if (OBS_DEBUG)
          {
            print_cli_table(array(array('syslog program', $entry['program']), array('matched pattern', $pattern)), NULL);
          }
          // Override founded tag/program references
          if (isset($matches['program'])) { $entry['program'] = $matches['program']; }
          if (isset($matches['tag']))     { $entry['tag']     = $matches['tag']; }
          /*
          // Tags, also allowed multiple tagsX (0-9), started from 0
          $i = 0;
          while (isset($matches['tag'.$i]) && $matches['tag'.$i])
          {
            $entry['tag']  = rtrim($entry['tag'], ':'); // remove last :
            $entry['tag'] .= ','.$matches['tag'.$i];
            $i++;
          }
          */
          break; // Stop other loop if pattern found
        }
      }
    }

    // Additional syslog cases, when regex from definition not possible
    if ($os_group == 'cisco')
    {
      // Cisco by default store in tag/program syslog fields just seq no,
      // this not useful for this fields
      if ($entry['priority'] > 6 && (is_numeric($entry['program']) || empty($entry['program'])))
      {
        $entry['program'] = 'debug';
        $entry['tag']     = 'debug';
        // Remove prior seqno and timestamp from msg
        $entry['msg'] = preg_replace('/^\s*(?<seq>\d+:)*\s*(?<timestamp>.*?\d+\:\d+\:\d+(?:\.\d+)?(?:\ [\w\-\+]+)?): /', '', $entry['msg']);
      }
    }
    elseif (in_array($os, array('junos', 'junose')))
    {
      //1.1.1.1||9||6||6||/usr/sbin/cron[1305]:||2015-04-08 14:30:01|| (root) CMD (   /usr/libexec/atrun)||
      if (str_exists($entry['tag'], '/'))
      {
        $entry['tag']     = end(explode('/', $entry['tag'])); // /usr/sbin/cron[1305]: -> cron[1305]:
      }
      if (empty($entry['program']))
      {
        list($entry['program']) = explode('[', rtrim($entry['tag'], ':')); // cron[1305]: -> cron
      }
      //1.1.1.1||3||4||4||mib2d[1230]:||2015-04-08 14:30:11|| SNMP_TRAP_LINK_DOWN: ifIndex 602, ifAdminStatus up(1), ifOperStatus down(2), ifName ge-0/1/0||mib2d
      //1.1.1.1||3||6||6||chassism[1210]:||2015-04-08 14:30:16|| ethswitch_eth_devstop: called for port ge-0/1/1||chassism
      //1.1.1.1||3||3||3||chassism[1210]:||2015-04-08 14:30:22|| ETH:if_ethgetinfo() returns error||chassism
    }
    elseif ($os == 'linux' && get_cache($entry['host'], 'version') == 'Point')
    {
      // Cisco WAP200 and similar
      $matches = array();
      if (preg_match('#Log: \[(?P<program>.*)\] - (?P<msg>.*)#', $entry['msg'], $matches))
      {
        $entry['msg']     = $matches['msg'];
        $entry['program'] = $matches['program'];
      }
      unset($matches);

    }
    elseif ($os_group == 'unix')
    {
      //1.1.1.1||9||6||6||/usr/sbin/cron[1305]:||2015-04-08 14:30:01|| (root) CMD (   /usr/libexec/atrun)||
      if (str_exists($entry['tag'], '/'))
      {
        $entry['tag']     = end(explode('/', $entry['tag'])); // /usr/sbin/cron[1305]: -> cron[1305]:
        // And same for program if it based on tag (from os definitions)
        if (str_exists($entry['program'], '/'))
        {
          $entry['program'] = end(explode('/', $entry['program']));
        }
      }
      if (empty($entry['program']))
      {
        list($entry['program']) = explode('[', rtrim($entry['tag'], ':')); // cron[1305]: -> cron
      }

      // User_CommonName/123.213.132.231:39872 VERIFY OK: depth=1, /C=PL/ST=Malopolska/O=VLO/CN=v-lo.krakow.pl/emailAddress=root@v-lo.krakow.pl
      if ($entry['facility'] == 'daemon' && preg_match('#/([0-9]{1,3}\.) {3}[0-9]{1,3}:[0-9]{4,} ([A-Z]([A-Za-z])+( ?)) {2,}:#', $entry['msg']))
      {
        $entry['program'] = 'OpenVPN';
      }
      // pop3-login: Login: user=<username>, method=PLAIN, rip=123.213.132.231, lip=123.213.132.231, TLS
      // POP3(username): Disconnected: Logged out top=0/0, retr=0/0, del=0/1, size=2802
      elseif ($entry['facility'] == 'mail' && preg_match('/^(((pop3|imap)\-login)|((POP3|IMAP)\(.*\))):/', $entry['msg']))
      {
        $entry['program'] = 'Dovecot';
      }
      // SYSLOG CONNECTION BROKEN; FD='6', SERVER='AF_INET(123.213.132.231:514)', time_reopen='60'
      // 1.1.1.1||5||3||3||rsyslogd-2039:||2016-10-06 23:03:27|| Could no open output pipe '/dev/xconsole': No such file or directory [try http://www.rsyslog.com/e/2039 ]||rsyslogd-2039
      $entry['program'] = preg_replace('/\-\d+$/', '', $entry['program']);
      $entry['program'] = str_replace('rsyslogd0', 'rsyslogd', $entry['program']);
      unset($matches);
      if (str_exists($entry['program'], '/'))
      {
        // postfix/smtp
        list($entry['program'], $tag) = explode('/', $entry['program'], 2);
        $entry['tag'] .= ','.$tag;
      }
    }
    elseif ($os == 'netscaler')
    {
      //10/03/2013:16:49:07 GMT dk-lb001a PPE-4 : UI CMD_EXECUTED 10367926 : User so_readonly - Remote_ip 10.70.66.56 - Command "stat lb vserver" - Status "Success"
      list(,,,$entry['msg']) = explode(' ', $entry['msg'], 4);
      list($entry['program'], $entry['msg']) = explode(' : ', $entry['msg'], 3);
    }
    elseif (str_starts($entry['tag'], '('))
    {
      // Ubiquiti Unifi devices
      // Wtf is BZ2LR and BZ@..
      /**
       *Old:  10.10.34.10||3||6||6||hostapd:||2014-07-18 11:29:35|| ath2: STA c8:dd:c9:d1:d4:aa IEEE 802.11: associated||hostapd
       *New:  10.10.34.10||3||6||6||(BZ2LR,00272250c1cd,v3.2.5.2791)||2014-12-12 09:36:39|| hostapd: ath2: STA dc:a9:71:1b:d6:c7 IEEE 802.11: associated||(BZ2LR,00272250c1cd,v3.2.5.2791)
       *New2: 10.10.34.11||1||6||6||("BZ2LR,00272250c119,v3.7.8.5016")||2016-10-06 18:20:25|| syslog: wevent.ubnt_custom_event(): EVENT_STA_LEAVE ath0: dc:a9:71:1b:d6:c7 / 3||("BZ2LR,00272250c119,v3.7.8.5016")
       *      10.10.34.7||1||6||6||("U7LR,44d9e7f618f2,v3.7.17.5220")||2016-10-06 18:21:22|| libubnt[16915]: wevent.ubnt_custom_event(): EVENT_STA_JOIN ath0: fc:64:ba:c1:7d:28 / 1||("U7LR,44d9e7f618f2,v3.7.17.5220")
       */
      if (preg_match('/^\s*(?<tag>(?<program>\S+?)(\[\d+\])?): +(?<msg>.*)/', $entry['msg'], $matches))
      {
        $entry['msg']     = $matches['msg'];
        $entry['program'] = $matches['program'];
        $entry['tag']     = $matches['tag'];
      }

    }
    elseif (str_exists($entry['program'], ','))
    {
      // Mikrotik (and some other)
      // mikrotik||user||5||notice||0d||2018-03-23 07:48:39||dhcp105 assigned 192.168.58.84 to 80:BE:05:7A:73:6E||dhcp,info
      list($entry['program'], $entry['tag']) = explode(',', $entry['program'], 2);

      // Mikrotik report all syslog with single priority 5 (but with tags). ie: system,info
      foreach (explode(',', $entry['tag']) as $tag)
      {
        $tag_priority = priority_string_to_numeric($tag);
        if ($tag_priority < 8)
        {
          // Detected new priority from tags
          $entry['priority'] = $tag_priority;
          break;
        }
      }
    }

    // Always clear timestamp from beginig of message (if still leaved), test strings:
    //2018-10-16T18:13:03+02:00 hostname
    $pettern_timestamp_rfc3339 = '/^\s*\*?(?<year>[0-9]{4})\-(?<month>[0-9]{2})\-(?<day>[0-9]{2})(?:[Tt](?<hour>[0-9]{2}):(?<minute>[0-9]{2}):(?<second>(?:[0-9]{2})(?:\.[0-9]+)?)?)(?<tz>(?:[Zz]|[+\-](?:[0-9]{2}):(?:[0-9]{2})))?/';
    //Wed Mar 26 12:54:17 2014 :
    //May 30 15:33:20.636 UTC :
    //May 30 15:33:20.636 2014 UTC :
    //Mar 19 06:48:12.692:
    //Mar 19 15:12:23 MSK/MSD:
    //Apr 24 2013 16:00:28 INT-FW01 :
    //LC/0/0/CPU0:Oct 19 09:17:07.433 :
    //oly-er-01 LC/0/0/CPU0:Jan 14 07:29:45.556 CET:
    //003174: Jan 26 04:27:09.174 MSK:
    //001743: *Apr 25 04:16:54.749:
    //033884: Nov  8 07:19:23.993:
    // Should be false:
    //CompDHCP assigned 10.0.0.222 to 4C:32:75:90:69:33
    $pattern_timestamp = '/^(?<max2words>\s*(?:\S+\s+)?\S+?:)?\s*\*?(?<wmd>(?<week>[a-z]{3,} +)?(?<month>[a-z]{3,} +)(?<day>\d{1,2} +)(?<year0>[12]\d{3} +)?)?(?<hms>\d{1,2}\:\d{1,2}\:\d{1,2}(?:\.\d+)?)(?<year>\s+[12]\d{3})?(?<tz>\s+[a-z][\w\/\-]+)?\s*:\s/i';
    // without TZ, example:
    //Mar 21 13:07:05 netflow syslogd:
    $pattern_timestamp_wo_tz = '/^\s*\*?(?<wmd>(?<week>[a-z]{3,} +)?(?<month>[a-z]{3,} +)(?<date>\d{1,2} +)(?<year0>[12]\d{3} +)?)?(?<hms>\d{1,2}\:\d{1,2}\:\d{1,2}(?:\.\d+)?)(?<year>\s+[12]\d{3})?/i';
    $entry['msg']      = preg_replace(array($pattern_timestamp, $pattern_timestamp_wo_tz, $pettern_timestamp_rfc3339), '', $entry['msg']);

    if (!strlen($entry['msg']))
    {
      // Something wrong, msg empty
      return FALSE;
    }

    // Wed Mar 26 12:54:17 2014 : Auth: Login incorrect (mschap: External script says Logon failure (0xc000006d)): [username] (from client 10.100.1.3 port 0 cli a4c3612a4077 via TLS tunnel)
    if (strlen($entry['program']))
    {
      // Always clear program from begining of message, ie Auth:, blabla[27346]:
      $pattern_program = '/^\s*'.preg_quote($entry['program'], '/').'(\[\d+\])?\s*:/i';
      $entry['msg']    = preg_replace($pattern_program, '', $entry['msg']);
    }
    elseif (strlen($entry['facility']))
    {
      // fallback, better than nothing...
      $entry['program'] = $entry['facility'];
    } else {
      $entry['program'] = 'generic'; // Derp, do not leave empty program
    }

    // Last point clear
    $entry['program'] = strtoupper($entry['program']);
    $entry['tag']     = trim($entry['tag'], ',: ');

  }

  //array_walk($entry, 'trim');
  return array_map('trim', $entry);
}

// EOF
