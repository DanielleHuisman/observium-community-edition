<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage syslog
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// DOCME needs phpdoc block
// TESTME needs unit testing
// FIXME. Use internal caching instead
function get_cache($host, $value)
{
    global $dev_cache;

    if (empty($host)) {
        return NULL;
    }
    $host = strtolower($host);

    // Check cache expiration
    $now     = time();
    $expired = TRUE;
    if (isset($dev_cache[$host]['lastchecked']) && ($now - $dev_cache[$host]['lastchecked']) < 600) {
        $expired = FALSE;
    }
    if ($expired) {
        $dev_cache[$host]['lastchecked'] = $now;
        reset_attribs_cache(); // reset attribs too
    }

    if (!isset($dev_cache[$host][$value]) || $expired) {
        switch ($value) {
            case 'device_id':
                $dev_cache[$host]['device_id'] = get_device_id_by_syslog_host($host);
                break;

            case 'os':
            case 'version':
                if ($device_id = get_cache($host, 'device_id')) {
                    $dev_cache[$host][$value] = dbFetchCell('SELECT `' . $value . '` FROM `devices` WHERE `device_id` = ?', [ $device_id ]);
                } else {
                    return NULL;
                }
                break;

            case 'os_group':
                $os                           = get_cache($host, 'os');
                $dev_cache[$host]['os_group'] = $GLOBALS['config']['os'][$os]['group'] ?? '';
                break;

            default:
                return NULL;
        }
    }

    return $dev_cache[$host][$value];
}

function cache_syslog_rules() {

    $rules = [];
    foreach (dbFetchRows("SELECT * FROM `syslog_rules` WHERE `la_disable` = ?", ['0']) as $lat) {
        $rules[$lat['la_id']] = $lat;
    }

    return $rules;
}

function cache_syslog_rules_assoc() {

    $device_rules = [];
    foreach (dbFetchRows("SELECT * FROM `syslog_rules_assoc`") as $laa) {

        //print_r($laa);

        if ($laa['entity_type'] === 'group') {
            $devices = get_group_entities($laa['entity_id']);
            foreach ($devices as $dev_id) {
                $device_rules[$dev_id][$laa['la_id']] = TRUE;
            }
        } elseif ($laa['entity_type'] === 'device') {
            $device_rules[$laa['entity_id']][$laa['la_id']] = TRUE;
        }
    }

    return $device_rules;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function process_syslog($line, $update) {
    global $config, $rules, $device_rules, $maint;

    $entry = process_syslog_line($line);

    if ($entry === FALSE) {
        // Incorrect/filtered syslog entry
        return FALSE;
    }
    if ($entry['device_id']) {
        // Main syslog entry with detected device
        if ($update) {
            // Accurate timestamps

            $syslog_id = dbInsert([
                                    'device_id' => $entry['device_id'],
                                    'host'      => $entry['host'],
                                    'program'   => $entry['program'],
                                    'facility'  => $entry['facility'],
                                    'priority'  => $entry['priority'],
                                    'level'     => $entry['level'],
                                    'tag'       => $entry['tag'],
                                    'msg'       => $entry['msg'],
                                    'timestamp' => $entry['timestamp']
                                  ], 'syslog');
        }

//$req_dump = print_r(array($entry, $rules, $device_rules), TRUE);
//$fp = fopen('/tmp/syslog.log', 'a');
//fwrite($fp, $req_dump);
//fclose($fp);

        // Add syslog alert into notification queue
        $notification_type = 'syslog';

        /// FIXME, I not know how 'syslog_rules_assoc' is filled, I pass rules to all devices
        /// FIXME, this is copy-pasted from above, while not have WUI for syslog_rules_assoc
        foreach ($rules as $la_id => $rule) {
            // Skip processing syslog rule if device rule not cached (see: cache_syslog_rules_assoc() )
            if (!empty($device_rules) && !isset($device_rules[$entry['device_id']][$la_id])) {
                continue;
            }

            if (preg_match($rule['la_rule'], $entry['msg_orig'], $matches)) { // Match syslog by rule pattern

                // Mark no notification during maintenance
                if (isset($maint['device'][$entry['device_id']]) || (isset($maint['global']) && $maint['global'] > 0)) {
                    $notified = '-1';
                } else {
                    $notified = '0';
                }

                // Detect some common entities patterns in syslog message

                $log_id = dbInsert([
                                     'device_id' => $entry['device_id'],
                                     'la_id'     => $la_id,
                                     'syslog_id' => $syslog_id,
                                     'timestamp' => $entry['timestamp'],
                                     'program'   => $entry['program'],
                                     'message'   => $entry['msg'], // Use cleared msg instead original (see process_syslog_line() tests)
                                     'notified'  => $notified
                                   ], 'syslog_alerts');

                // Add notification to queue
                if ($notified != '-1') {
                    $message_tags = syslog_generate_tags($entry, $rule);

                    // Get contacts for $la_id
                    $contacts = get_alert_contacts($entry['device_id'], $la_id, $notification_type);

                    foreach ($contacts as $contact) {

                        $notification = [
                          'device_id'             => $entry['device_id'],
                          'log_id'                => $log_id,
                          'aca_type'              => $notification_type,
                          'severity'              => $entry['priority'],
                          'endpoints'             => safe_json_encode($contact),
                          //'message_graphs'        => $message_tags['ENTITY_GRAPHS_ARRAY'],
                          'notification_added'    => time(),
                          'notification_lifetime' => 300,                   // Lifetime in seconds
                          'notification_entry'    => safe_json_encode($entry),   // Store full alert entry for use later if required (not sure that this needed)
                        ];
                        //unset($message_tags['ENTITY_GRAPHS_ARRAY']);
                        $notification['message_tags'] = safe_json_encode($message_tags);

                        /// DEVEL
                        //file_put_contents('/tmp/alert_'.$la_id.'_SYSLOG_'.time().'.json', safe_json_encode($notification, JSON_PRETTY_PRINT));

                        $notification_id = dbInsert($notification, 'notifications_queue');
                    } // End foreach($contacts)
                } // End if($notified)
            }  // End if syslog rule matches
        } // End foreach($rules)

        unset($os);
    } elseif ($config['syslog']['unknown_hosts']) {
        // EXPERIMENTAL. Host not known, currently not used.
        if ($update) {
            // Store entries for unknown hosts with NULL device_id
            $log_id = dbInsert(
              [
                  //'device_id' => $entry['device_id'], // Default is NULL
                  'host'      => $entry['host'],
                  'program'   => $entry['program'],
                  'facility'  => $entry['facility'],
                  'priority'  => $entry['priority'],
                  'level'     => $entry['level'],
                  'tag'       => $entry['tag'],
                  'msg'       => $entry['msg'],
                  'timestamp' => $entry['timestamp']
              ],
              'syslog'
            );
            //var_dump($entry);
        }
    }

    return $entry;
}

function syslog_generate_tags($entry, $rule) {
    //$alert_unixtime = strtotime($entry['timestamp']);
    $alert_unixtime = $entry['unixtime'];

    $la_id = $rule['la_id'];

    // -1 mean set severity based on syslog priority
    //$severity = ($rule['la_severity'] >= 0) ? $rule['la_severity'] : $entry['priority'];
    $severity     = $entry['priority'];
    $severity_def = $GLOBALS['config']['syslog']['priorities'][$severity];
    $alert_emoji  = $severity_def['emoji'];
    $alert_color  = $severity_def['color'];
    // Custom alert statuses
    $entry['alert_status'] = 0; // Currently always ALERT
    $alert_status_custom = $GLOBALS['config']['alerts']['status'][$entry['alert_status']] ?? $entry['alert_status'];

    $device = device_by_id_cache($entry['device_id']);

    $message_tags = [
      'ALERT_STATE'      => "SYSLOG",
      'ALERT_EMOJI'      => get_icon_emoji($alert_emoji),   // https://unicodey.com/emoji-data/table.htm
      'ALERT_EMOJI_NAME' => $alert_emoji,
      'ALERT_STATUS'     => $entry['alert_status'],         // Tag for templates (0 - ALERT, 1 - RECOVERY, 2 - DELAYED, 3 - SUPPRESSED)
      'ALERT_STATUS_CUSTOM' => $alert_status_custom,        // Tag for templates (as defined in $config['alerts']['status'] array)
      'ALERT_SEVERITY'   => ucfirst($severity_def['name']), // Critical, Warning, Informational, Other
      'ALERT_COLOR'      => ltrim($alert_color, '#'),
      'ALERT_URL'        => generate_url([ 'page'    => 'device',
                                           'device'  => $device['device_id'],
                                           'tab'     => 'logs',
                                           'section' => 'logalert',
                                           'la_id'   => $la_id ]),
      'ALERT_UNIXTIME'          => $alert_unixtime,                        // Standard unixtime
      'ALERT_TIMESTAMP'         => date('Y-m-d H:i:s P', $alert_unixtime), //           ie: 2000-12-21 16:01:07 +02:00
      'ALERT_TIMESTAMP_RFC2822' => date('r', $alert_unixtime),             // RFC 2822, ie: Thu, 21 Dec 2000 16:01:07 +0200
      'ALERT_TIMESTAMP_RFC3339' => date(DATE_RFC3339, $alert_unixtime),    // RFC 3339, ie: 2005-08-15T15:52:01+00:00
      'ALERT_ID'                => $la_id,
      'ALERT_MESSAGE'           => $rule['la_descr'],
      'CONDITIONS'              => $rule['la_rule'],
      'METRICS'                 => $entry['msg'],

      // Syslog TAGs
      'SYSLOG_RULE'             => $rule['la_rule'],
      'SYSLOG_MESSAGE'          => $entry['msg'],
      'SYSLOG_PROGRAM'          => $entry['program'],
      'SYSLOG_TAG'              => $entry['tag'],
      'SYSLOG_FACILITY'         => $entry['facility'],

      // Device TAGs
      'DEVICE_HOSTNAME'         => $device['hostname'],
      'DEVICE_SYSNAME'          => $device['sysName'],
      //'DEVICE_SYSDESCR'     => $device['sysDescr'],
      'DEVICE_DESCRIPTION'      => $device['purpose'],
      'DEVICE_ID'               => $device['device_id'],
      'DEVICE_URL'              => generate_device_url($device),
      'DEVICE_LINK'             => generate_device_link($device),
      'DEVICE_HARDWARE'         => $device['hardware'],
      'DEVICE_OS'               => $device['os_text'] . ' ' . $device['version'] . ($device['features'] ? ' (' . $device['features'] . ')' : ''),
      'DEVICE_TYPE'             => $device['type'],
      'DEVICE_LOCATION'         => $device['location'],
      'DEVICE_UPTIME'           => device_uptime($device),
      'DEVICE_REBOOTED'         => format_unixtime($device['last_rebooted']),
    ];

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
 * @param string|array $line Raw syslog line by observium template
 *
 * @return array|false Array with processed syslog entry, or FALSE if incorrect/filtered msg.
 */
function process_syslog_line($line)
{
    global $config;

    // Compatibility with old param as array
    if (is_array($line)) {
        return $line;
    }

    $start_time = time();
    $entry      = []; // Init

    $entry_array        = explode('||', trim($line));
    $entry['host']      = array_shift($entry_array);
    $entry['facility']  = array_shift($entry_array);
    $entry['priority']  = array_shift($entry_array);
    $entry['level']     = array_shift($entry_array);
    $entry['tag']       = array_shift($entry_array);
    $entry['timestamp'] = array_shift($entry_array);
    if (safe_count($entry_array) > 2) {
        // Some time message have || inside:
        // 127.0.0.1||9||6||6||CRON[3196]:||2018-03-13 06:25:01|| (root) CMD (test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.daily ))||CRON
        $entry['program'] = array_pop($entry_array);
        $entry['msg']     = implode('||', $entry_array); // reimplode msg string with "||" inside
    } else {
        $entry['msg']     = array_shift($entry_array);
        $entry['program'] = array_shift($entry_array);
    }

    // Restore escaped quotes
    $entry['msg'] = str_replace(['\"', "\'"], ['"', "'"], $entry['msg']);

    // Filter by msg string
    if (str_contains_array($entry['msg'], $config['syslog']['filter'])) {
        return FALSE;
    }
    if (!safe_empty($config['syslog']['filter_regex'])) {
        foreach ((array)$config['syslog']['filter_regex'] as $filter) {
            if (preg_match($filter, $entry['msg'])) {
                return FALSE;
            }
        }
    }

    $entry['msg_orig'] = $entry['msg'];

    // Initial rewrites
    $entry['host'] = strtolower(trim($entry['host']));

    if (isset($config['syslog']['debug']) && $config['syslog']['debug'] &&
        !defined('__PHPUNIT_PHAR__')) { // Skip on Unit tests
        // Store RAW syslog line into debug.log
        logfile('debug.' . $entry['host'] . '.syslog', $line);
    }

    // Rewrite priority/level/facility from strings to numbers
    $entry['priority'] = priority_string_to_numeric($entry['priority']);
    $entry['level']    = priority_string_to_numeric($entry['level']);
    if (isset($config['syslog']['facilities'][$entry['facility']])) {
        // Convert numeric facility to string
        $entry['facility'] = $config['syslog']['facilities'][$entry['facility']]['name'];
    }
    //$entry['facility']  = facility_string_to_numeric($entry['facility']);

    $entry['device_id'] = get_cache($entry['host'], 'device_id');
    //print_vars($entry);
    //print_vars($GLOBALS['dev_cache']);
    if ($entry['device_id']) {
        // Process msg/program for known os/os_group
        $os       = get_cache($entry['host'], 'os');
        $os_group = get_cache($entry['host'], 'os_group');

        // Detect message repeated
        if (str_contains($entry['msg'], 'repeated ') &&
            preg_match('/repeated \d+ times(?:\:\ +\[\s*(?<msg>.+)\])?\s*$/', $entry['msg'], $matches)) {
            //var_dump($matches);
            if (isset($matches['msg'])) {
                $entry['msg'] = $matches['msg'];
            } else {
                // Always skip unuseful entries 'message repeated X times' (without any message)
                return FALSE;
            }
        }

        if (!safe_empty($entry['tag'])) {
            $entry['tag'] = rtrim($entry['tag'], ':'); // remove last :

            // OS definition based syslog tag format
            if (isset($config['os'][$os]['syslog_tag'])) {
                foreach ($config['os'][$os]['syslog_tag'] as $pattern) {
                    if (preg_match($pattern, $entry['tag'], $matches)) {
                        if (OBS_DEBUG) {
                            print_cli_table([['syslog tag', $entry['tag']], ['matched pattern', $pattern]]);
                            //print_vars($matches);
                        }
                        // Override founded tag/program references
                        if (isset($matches['program'])) {
                            $entry['program'] = $matches['program'];
                        }
                        if (isset($matches['tag'])) {
                            $entry['tag'] = $matches['tag'];
                        }
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
        }

        // OS definition based syslog msg format
        if (isset($config['os'][$os]['syslog_msg'])) {
            foreach ($config['os'][$os]['syslog_msg'] as $pattern) {
                if (preg_match($pattern, $entry['msg'], $matches)) {
                    if (OBS_DEBUG) {
                        print_cli_table([['syslog msg', $entry['msg']], ['matched pattern', $pattern]]);
                    }
                    // Override founded msg/tag/program references
                    if (isset($matches['msg'])) {
                        $entry['msg'] = $matches['msg'];
                    }
                    if (isset($matches['program'])) {
                        $entry['program'] = $matches['program'];
                    }
                    if (isset($matches['tag'])) {
                        $entry['tag'] = $matches['tag'];
                    }
                    // Tags, also allowed multiple tagsX (0-9), started from 0
                    $i = 0;
                    while (isset($matches['tag' . $i]) && $matches['tag' . $i]) {
                        $entry['tag'] = rtrim($entry['tag'], ':'); // remove last :
                        $entry['tag'] .= ',' . $matches['tag' . $i];
                        $i++;
                    }
                    break; // Stop other loop if pattern found
                }
            }
        }

        // OS definition based syslog program format
        if (isset($config['os'][$os]['syslog_program']) && !safe_empty($entry['program'])) {
            foreach ($config['os'][$os]['syslog_program'] as $pattern) {
                if (preg_match($pattern, $entry['program'], $matches)) {
                    if (OBS_DEBUG) {
                        print_cli_table([['syslog program', $entry['program']], ['matched pattern', $pattern]]);
                    }
                    // Override founded tag/program references
                    if (isset($matches['program'])) {
                        $entry['program'] = $matches['program'];
                    }
                    if (isset($matches['tag'])) {
                        $entry['tag'] = $matches['tag'];
                    }
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
        if ($os_group === 'cisco') {
            // Cisco by default store in tag/program syslog fields just seq no,
            // this not useful for this fields
            if ($entry['priority'] > 6 && (is_numeric($entry['program']) || empty($entry['program']))) {
                $entry['program'] = 'debug';
                $entry['tag']     = 'debug';
                // Remove prior seqno and timestamp from msg
                $entry['msg'] = preg_replace('/^\s*(?<seq>\d+:)*\s*(?<timestamp>.*?\d+\:\d+\:\d+(?:\.\d+)?(?:\ [\w\-\+]+)?): /', '', $entry['msg']);
            }
        } elseif ($os_group === 'juniper') {
            //1.1.1.1||9||6||6||/usr/sbin/cron[1305]:||2015-04-08 14:30:01|| (root) CMD (   /usr/libexec/atrun)||
            if (str_contains($entry['tag'], '/')) {
                $tmp          = explode('/', $entry['tag']);
                $entry['tag'] = end($tmp); // /usr/sbin/cron[1305]: -> cron[1305]:
            }
            if (empty($entry['program'])) {
                $entry['program'] = explode('[', rtrim($entry['tag'], ':'))[0]; // cron[1305]: -> cron
            }
            //1.1.1.1||3||4||4||mib2d[1230]:||2015-04-08 14:30:11|| SNMP_TRAP_LINK_DOWN: ifIndex 602, ifAdminStatus up(1), ifOperStatus down(2), ifName ge-0/1/0||mib2d
            //1.1.1.1||3||6||6||chassism[1210]:||2015-04-08 14:30:16|| ethswitch_eth_devstop: called for port ge-0/1/1||chassism
            //1.1.1.1||3||3||3||chassism[1210]:||2015-04-08 14:30:22|| ETH:if_ethgetinfo() returns error||chassism
        } elseif ($os === 'linux' && get_cache($entry['host'], 'version') === 'Point') {
            // Cisco WAP200 and similar
            $matches = [];
            if (preg_match('#Log: \[(?P<program>.*)\] - (?P<msg>.*)#', $entry['msg'], $matches)) {
                $entry['msg']     = $matches['msg'];
                $entry['program'] = $matches['program'];
            }
            unset($matches);

        } elseif ($os_group === 'unix') {
            if (str_contains($entry['tag'], '/')) {
                if (preg_match('/^([\w\-]+)\(([\w\.\/]+)\)/', $entry['tag'], $matches)) {
                    // 0.0.0.0||9||5||5||run-parts(/etc/cron.hourly)[2654||2021-11-26 08:01:01|| starting 0anacron||run-parts(
                    // 0.0.0.0||9||5||5||run-parts(/etc/cron.hourly)[2655||2021-11-26 08:01:01|| finished 0anacron||run-parts(
                    $entry['tag'] = $matches[1] . ',' . $matches[2];
                    if (preg_match('/(?:starting|finished) (\w+)/', $entry['msg'], $matches)) {
                        $entry['program'] = $matches[1];
                    }
                } else {
                    // 0.0.0.0||9||6||6||/usr/sbin/cron[1305]:||2015-04-08 14:30:01|| (root) CMD (   /usr/libexec/atrun)||
                    $tmp          = explode('/', $entry['tag']);
                    $entry['tag'] = end($tmp); // /usr/sbin/cron[1305]: -> cron[1305]:
                    // And same for program if it based on tag (from os definitions)
                    if (str_contains($entry['program'], '/')) {
                        $tmp              = explode('/', $entry['program']);
                        $entry['program'] = end($tmp);
                    }
                }
            }
            if (empty($entry['program'])) {
                [$entry['program']] = explode('[', rtrim($entry['tag'], ':')); // cron[1305]: -> cron
            }

            // User_CommonName/123.213.132.231:39872 VERIFY OK: depth=1, /C=PL/ST=Malopolska/O=VLO/CN=v-lo.krakow.pl/emailAddress=root@v-lo.krakow.pl
            if ($entry['facility'] === 'daemon' && preg_match('#/(\d{1,3}\.) {3}\d{1,3}:\d{4,} ([A-Z]([A-Za-z])+( ?)) {2,}:#', $entry['msg'])) {
                $entry['program'] = 'OpenVPN';
            }
            // pop3-login: Login: user=<username>, method=PLAIN, rip=123.213.132.231, lip=123.213.132.231, TLS
            // POP3(username): Disconnected: Logged out top=0/0, retr=0/0, del=0/1, size=2802
            elseif ($entry['facility'] === 'mail' && preg_match('/^(((pop3|imap)\-login)|((POP3|IMAP)\(.*\))):/', $entry['msg'])) {
                $entry['program'] = 'Dovecot';
            } elseif (preg_match('/^fail2ban[\.\-](\w\S+)/', $entry['program'], $matches)) {
                // Fail2ban specific
                $entry['program'] = 'fail2ban';
                $entry['tag']     = $matches[1];
                if (preg_match('/^\s*(?:\d.*?\[\d+\]: +)?([A-Z]{4,})\s+(.*)/', $entry['msg'], $matches)) {
                    $entry['tag'] .= ',' . $matches[1];
                    $entry['msg'] = $matches[2];
                }
                //print_vars($entry);
                //print_vars($matches);
            }
            // SYSLOG CONNECTION BROKEN; FD='6', SERVER='AF_INET(123.213.132.231:514)', time_reopen='60'
            // 1.1.1.1||5||3||3||rsyslogd-2039:||2016-10-06 23:03:27|| Could no open output pipe '/dev/xconsole': No such file or directory [try http://www.rsyslog.com/e/2039 ]||rsyslogd-2039
            $entry['program'] = preg_replace('/\-\d+$/', '', $entry['program']);
            $entry['program'] = str_replace('rsyslogd0', 'rsyslogd', $entry['program']);
            unset($matches);
            if (str_contains($entry['program'], '/')) {
                // postfix/smtp
                [$entry['program'], $tag] = explode('/', $entry['program'], 2);
                $entry['tag'] .= ',' . $tag;
            }
        } elseif ($os === 'netscaler') {
            //10/03/2013:16:49:07 GMT dk-lb001a PPE-4 : UI CMD_EXECUTED 10367926 : User so_readonly - Remote_ip 10.70.66.56 - Command "stat lb vserver" - Status "Success"
            //01/26/2021:12:47:07 GMT DCRX-ANS-N004 0-PPE-0 : default EVENT DEVICEDOWN 62431870 0 :  Device "server_serviceGroup_NSSVC_TCP_172.16.200.150:636(SVG_TST_LDAPS_ADMB?DC-BRU-150?636)" - State DOWN
            // main message
            [$tmp, $tags, $entry['msg']] = explode(' : ', $entry['msg'], 3);

            // try detect correct device association
            // see: https://jira.observium.org/browse/OBS-523
            $tmp = explode(' ', trim($tmp));
            array_pop($tmp);
            $netscaler_host = array_pop($tmp);
            if (strtolower($netscaler_host) !== strtolower($entry['host'])) {
                $netscaler_id = get_cache($netscaler_host, 'device_id');
                if ($netscaler_id && $netscaler_id != $entry['device_id'] &&
                    get_cache($netscaler_host, 'os') === 'netscaler') {
                    $entry['device_id'] = $netscaler_id;
                }
            }

            // program and tags
            $tags             = preg_replace('/ \d+( \d+)?$/', '', trim($tags));
            $tags             = explode(' ', $tags);
            $entry['tag']     .= ',' . array_pop($tags);
            $entry['program'] = array_pop($tags);
            unset($tags, $tmp, $netscaler_host, $netscaler_id);
        } elseif ($os === 'nos' || $entry['program'] === 'raslogd' || str_starts($entry['tag'], 'raslogd')) {
            // Brocade NOS raslogd format (see unittests)
            $values_pattern = '/\[(?<var>\w+)(?:@\d+)?\s+(?<values>[^\]]+)\]/';
            $value_pattern  = '/value="(?<value>[^"]+?)"(\s+desc="(?<descr>[^"]+?)")?/';
            $tags           = [];
            if (preg_match_all($values_pattern, $entry['msg'], $matches_all, PREG_SET_ORDER)) {
                $replace = [];
                foreach ($matches_all as $matches) {
                    $replace[] = $matches[0];
                    preg_match($value_pattern, $matches['values'], $value);
                    switch ($matches['var']) {
                        case 'log':
                            // [log@1588 value="RASLOG"] [log@1588 value="AUDIT"]
                            $entry['program'] = $value['value'];
                            break;
                        case 'severity':
                            // not sure, seems as already correct in priority
                            // [severity@1588 value="INFO"]
                            $entry['priority'] = priority_string_to_numeric($value['value']);
                            break;
                        case 'msgid':
                        case 'interface':
                        case 'application':
                            //case 'swname':
                            // [msgid@1588 value="SEC-3020"] [msgid@1588 value="L2SS-1032"]
                            // [interface@1588 value="ssh"]
                            // [application@1588 value="CLI"]
                            // [swname@1588 value="VDX_TESTPOP1"]
                            $tags[] = $value['value'];
                            break;
                    }
                    //print_vars($matches);
                }
                $entry['tag'] = implode(',', $tags);
                $entry['msg'] = str_replace($replace, '', $entry['msg']);
                $entry['msg'] = preg_replace('/^\s*BOM/', '', $entry['msg']);
            }
        } elseif (str_starts($entry['tag'], ['(', 'BZ2LR,', 'U7LR,']) ||
                  ($os === 'unifi' && str_contains($entry['tag'], ',')) ||
                  preg_match('/\w+,\w+,v\d+\.\d+\.\d+\.\d{3,}/', $entry['tag'])) {
            // Ubiquiti Unifi devices
            // Wtf is BZ2LR and BZ@..
            /**
             *Old:  10.10.34.10||3||6||6||hostapd:||2014-07-18 11:29:35|| ath2: STA c8:dd:c9:d1:d4:aa IEEE 802.11: associated||hostapd
             *New:  10.10.34.10||3||6||6||(BZ2LR,00272250c1cd,v3.2.5.2791)||2014-12-12 09:36:39|| hostapd: ath2: STA dc:a9:71:1b:d6:c7 IEEE 802.11: associated||(BZ2LR,00272250c1cd,v3.2.5.2791)
             *New2: 10.10.34.11||1||6||6||("BZ2LR,00272250c119,v3.7.8.5016")||2016-10-06 18:20:25|| syslog: wevent.ubnt_custom_event(): EVENT_STA_LEAVE ath0: dc:a9:71:1b:d6:c7 / 3||("BZ2LR,00272250c119,v3.7.8.5016")
             *      10.10.34.7||1||6||6||("U7LR,44d9e7f618f2,v3.7.17.5220")||2016-10-06 18:21:22|| libubnt[16915]: wevent.ubnt_custom_event(): EVENT_STA_JOIN ath0: fc:64:ba:c1:7d:28 / 1||("U7LR,44d9e7f618f2,v3.7.17.5220")
             *New3: 10.10.34.7||3||6||6||U7LR,44d9e7f618f2,v4.3.21.11325:||2020-10-05 16:29:43|| hostapd: ath4: STA 0c:70:4a:7d:5c:73 IEEE 802.11: disassociated||U7LR,44d9e7f618f2,v4.3.21.11325
             *      10.10.34.7||1||6||6||U7LR,44d9e7f618f2,v4.3.21.11325:||2020-10-05 15:11:28|| : mcad[2832]: wireless_agg_stats.log_sta_anomalies(): bssid=44:d9:e7:f8:18:f2 radio=wifi1 vap=ath4 sta=0c:70:4a:7d:5c:73 satisfaction_now=78 anomalies=weak_signal||U7LR,44d9e7f618f2,v4.3.21.11325
             *New4:
             *      10.12.18.11||0||4||4||44d9e7f618f2,UAP-AC-LR-6.5.28+14491:||2023-03-06 15:57:43|| kernel: [399142.041095] [wifi1] FWLOG: [6086408] WAL_DBGID_SECURITY_MCAST_KEY_SET ( 0x2 )||44d9e7f618f2,UAP-AC-LR-6.5.28+14491
             */
            if (preg_match('/^(\s*:)?\s*(?<tag>(?<program>\S+?)(\[\d+\])?): +(?<msg>.*)/', $entry['msg'], $matches)) {
                $entry['msg']     = $matches['msg'];
                $entry['program'] = $matches['program'];
                $entry['tag']     = $matches['tag'];
            }

        } elseif (str_contains($entry['program'], ',')) {
            // Mikrotik (and some other)
            // mikrotik||user||5||notice||0d||2018-03-23 07:48:39||dhcp105 assigned 192.168.58.84 to 80:BE:05:7A:73:6E||dhcp,info
            [ $entry['program'], $entry['tag'] ] = explode(',', $entry['program'], 2);

            // Mikrotik report all syslog with single priority 5 (but with tags). ie: system,info
            foreach (explode(',', $entry['tag']) as $tag) {
                $tag_priority = priority_string_to_numeric($tag);
                if ($tag_priority < 8) {
                    // Detected new priority from tags
                    $entry['priority'] = $tag_priority;
                    break;
                }
            }
        }

        // Always clear timestamp from beginning of message (if still left), test strings:
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
        $entry['msg']            = preg_replace([$pattern_timestamp, $pattern_timestamp_wo_tz, $pettern_timestamp_rfc3339], '', $entry['msg']);

        if (safe_empty($entry['msg'])) {
            // Something wrong, msg empty
            return FALSE;
        }

        // Accurate timestamp
        // FIXME. Parse timestamps from syslog message above
        switch ($GLOBALS['config']['timestamp']) {
            case 'system':
                // Default. Use Observium host system time
                $entry['unixtime']  = $start_time;
                $entry['timestamp'] = date('Y-m-d H:i:s', $start_time);
                break;

            case 'syslog':
                $entry['unixtime'] = strtotime($entry['timestamp']);
                break;

            default:
                $unixtime = strtotime($entry['timestamp']);
                if (is_intnum($GLOBALS['config']['timestamp']) &&
                    abs($start_time - $unixtime) >= $GLOBALS['config']['timestamp']) {
                    $entry['unixtime'] = $unixtime;
                } else {
                    // Seems as wrong time synchronization on device/server or something else.
                    // Use self time in this case
                    $entry['unixtime']  = $start_time;
                    $entry['timestamp'] = date('Y-m-d H:i:s', $start_time);
                }
        }

        // Wed Mar 26 12:54:17 2014 : Auth: Login incorrect (mschap: External script says Logon failure (0xc000006d)): [username] (from client 10.100.1.3 port 0 cli a4c3612a4077 via TLS tunnel)
        if (!safe_empty($entry['program'])) {
            // Always clear program from begining of message, ie Auth:, blabla[27346]:
            $pattern_program = '/^\s*' . preg_quote($entry['program'], '/') . '(\[\d+\])?\s*:/i';
            $entry['msg']    = preg_replace($pattern_program, '', $entry['msg']);
        } elseif (!safe_empty($entry['facility'])) {
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
