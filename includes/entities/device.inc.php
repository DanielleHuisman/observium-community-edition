<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

/**
 * Generate device array.
 *
 * @param string $hostname
 * @param string $snmp_community
 * @param string $snmp_version
 * @param int    $snmp_port
 * @param string $snmp_transport
 * @param array  $options
 *
 * @return array
 */
function build_initial_device_array($hostname, $snmp_community, $snmp_version, $snmp_port = 161, $snmp_transport = 'udp', $options = []) {
  $device = array();
  $device['hostname']       = $hostname;
  $device['snmp_port']      = $snmp_port;
  $device['snmp_transport'] = $snmp_transport;
  $device['snmp_version']   = $snmp_version;

  if ($snmp_version === "v2c" || $snmp_version === "v1") {
    $device['snmp_community'] = $snmp_community;
  } elseif ($snmp_version === "v3") {
    $device['snmp_authlevel']  = $options['authlevel'];
    $device['snmp_authname']   = $options['authname'];
    $device['snmp_authpass']   = $options['authpass'];
    $device['snmp_authalgo']   = $options['authalgo'];
    $device['snmp_cryptopass'] = $options['cryptopass'];
    $device['snmp_cryptoalgo'] = $options['cryptoalgo'];
  }

  foreach ([ 'snmp_maxrep', 'snmp_timeout', 'snmp_retries' ] as $param) {
    if (isset($options[$param]) && is_numeric($options[$param])) {
      $device[$param] = (int)$options[$param];
    }
  }

  // Append SNMP context if passed
  if (isset($options['snmp_context']) && strlen($options['snmp_context'])) {
    $device['snmp_context'] = $options['snmp_context'];
  }

  print_debug_vars($device);

  return $device;
}

/**
 * Add device to DB or queue.
 *
 * @param array $vars
 *
 * @return bool|int
 */
function add_device_vars($vars) {
  global $config;

  $hostname = strip_tags($vars['hostname']);

  // Add device to remote poller,
  // only validate vars and add to pollers_actions
  if (is_intnum($vars['poller_id']) && $vars['poller_id'] != $config['poller_id']) {
    print_message("Requested add device $hostname to remote poller id [${vars['poller_id']}].");
    if (!(is_valid_hostname($hostname) || get_ip_version($hostname))) {
      // Failed DNS lookup
      print_error("Hostname $hostname is not valid.");
      return FALSE;
    }
    if (!dbExist('pollers', '`poller_id` = ?', [ $vars['poller_id'] ])) {
      // Incorrect Poller ID
      print_error("Device with hostname $hostname not added. Unknown target Poller requested.");
      return FALSE;
    }
    if (dbExist('devices', '`hostname` = ?', [ $hostname ])) {
      // found in database
      print_error("Already got device with hostname ($hostname).");
      return FALSE;
    }
    if (function_exists('add_action_queue') &&
        $action_id = add_action_queue('device_add', $hostname, $vars)) {
      print_message("Device with hostname $hostname added to queue [$action_id] for addition on remote Poller [${vars['poller_id']}].");
      log_event("Device with hostname $hostname added to queue [$action_id] for addition on remote Poller [${vars['poller_id']}].", NULL, 'info', NULL, 7);
      return TRUE;
    }
    print_error("Device with hostname $hostname not added. Incorrect addition to actions queue.");
    return FALSE;
  }

  // Keep original snmp/rrd config, for revert at end
  $config_snmp = $config['snmp'];
  $config_rrd  = $config['rrd_override'];

  // Default snmp port
  if (is_valid_param($vars['snmp_port'], 'port')) {
    $snmp_port = (int)$vars['snmp_port'];
  } else {
    $snmp_port = 161;
  }

  // Default snmp version
  if ($vars['snmp_version'] !== "v2c" &&
      $vars['snmp_version'] !== "v3"  &&
      $vars['snmp_version'] !== "v1") {
    $vars['snmp_version'] = $config['snmp']['version'];
  }

  switch ($vars['snmp_version']) {
    case 'v2c':
    case 'v1':

      if (strlen($vars['snmp_community'])) {
        // Hrm, I not sure why strip_tags
        $snmp_community = strip_tags($vars['snmp_community']);
        $config['snmp']['community'] = array($snmp_community);
      }

      $snmp_version = $vars['snmp_version'];

      print_message("Adding SNMP$snmp_version host $hostname port $snmp_port");
      break;

    case 'v3':

      if (strlen($vars['snmp_authlevel'])) {
        $snmp_v3 = array (
          'authlevel'  => $vars['snmp_authlevel'],
          'authname'   => $vars['snmp_authname'],
          'authpass'   => $vars['snmp_authpass'],
          'authalgo'   => $vars['snmp_authalgo'],
          'cryptopass' => $vars['snmp_cryptopass'],
          'cryptoalgo' => $vars['snmp_cryptoalgo'],
        );

        array_unshift($config['snmp']['v3'], $snmp_v3);
      }

      $snmp_version = "v3";

      print_message("Adding SNMPv3 host $hostname port $snmp_port");
      break;

    default:
      print_error("Unsupported SNMP Version. There was a dropdown menu, how did you reach this error?"); // We have a hacker!
      return FALSE;
  }

  if (get_var_true($vars['ignorerrd'], 'confirm')) {
    $config['rrd_override'] = TRUE;
  }

  $snmp_options = array();
  if (get_var_true($vars['ping_skip'])) {
    $snmp_options['ping_skip'] = TRUE;
  }

  if (is_valid_param($vars['snmp_timeout'], 'snmp_timeout')) {
    $snmp_options['snmp_timeout'] = (int)$vars['snmp_timeout'];
  }

  if (is_valid_param($vars['snmp_retries'], 'snmp_retries')) {
    $snmp_options['snmp_retries'] = (int)$vars['snmp_retries'];
  }

  // Optional max repetition
  if (is_intnum($vars['snmp_maxrep']) && $vars['snmp_maxrep'] >= 0 && $vars['snmp_maxrep'] <= 500) {
    $snmp_options['snmp_maxrep'] = trim($vars['snmp_maxrep']);
  }

  // Optional SNMP Context
  if (trim($vars['snmp_context']) !== '') {
    $snmp_options['snmp_context'] = trim($vars['snmp_context']);
  }

  $result = add_device($hostname, $snmp_version, $snmp_port, strip_tags($vars['snmp_transport']), $snmp_options);

  // Revert original snmp/rrd config
  $config['snmp'] = $config_snmp;
  $config['rrd_override'] = $config_rrd;

  return $result;
}

/**
 * Adds the new device to the database.
 *
 * Before adding the device, checks duplicates in the database and the availability of device over a network.
 *
 * @param string       $hostname                           Device hostname
 * @param string|array $snmp_version                       SNMP version(s) (default: $config['snmp']['version'])
 * @param int          $snmp_port                          SNMP port (default: 161)
 * @param string       $snmp_transport                     SNMP transport (default: udp)
 * @param array        $options                            Additional options can be passed ('ping_skip' - for skip ping test and add device attrib for skip pings later
 *                                                         'break' - for break recursion,
 *                                                         'test'  - for skip adding, only test device availability)
 * @param int          $flags
 *
 * @return mixed Returns $device_id number if added, 0 (zero) if device not accessible with current auth and FALSE if device complete not accessible by network. When testing, returns -1 if the device is available.
 */
// TESTME needs unit testing
function add_device($hostname, $snmp_version = array(), $snmp_port = 161, $snmp_transport = 'udp', $options = array(), $flags = OBS_DNS_ALL) {
  global $config;

  // If $options['break'] set as TRUE, break recursive function execute
  if (isset($options['break']) && $options['break']) { return FALSE; }
  $return = FALSE; // By default return FALSE

  // Reset snmp timeout and retries options for speedup device adding
  unset($config['snmp']['timeout'], $config['snmp']['retries']);

  $snmp_transport = strtolower($snmp_transport);

  $hostname = strtolower(trim($hostname));

  // Try detect if hostname is IP
  switch (get_ip_version($hostname)) {
    case 6:
    case 4:
      if ($config['require_hostname']) {
        print_error("Hostname should be a valid resolvable FQDN name. Or set config option \$config['require_hostname'] as FALSE.");
        return $return;
      }
      $hostname = ip_compress($hostname); // Always use compressed IPv6 name
      $ip       = $hostname;
      break;
    default:
      if ($snmp_transport === 'udp6' || $snmp_transport === 'tcp6') { // IPv6 used only if transport 'udp6' or 'tcp6'
        $flags ^= OBS_DNS_A; // exclude A
      }
      // Test DNS lookup.
      $ip       = gethostbyname6($hostname, $flags);
  }

  // Test if host exists in database
  if (!dbExist('devices', '`hostname` = ?', array($hostname))) {
    if ($ip) {
      $ip_version = get_ip_version($ip);

      // Test reachability
      $options['ping_skip'] = isset($options['ping_skip']) && $options['ping_skip'];
      if ($options['ping_skip']) {
        $flags |= OBS_PING_SKIP;
      }

      if (is_pingable($hostname, $flags)) {
        // Test directory exists in /rrd/
        if (!$config['rrd_override'] && file_exists($config['rrd_dir'].'/'.$hostname)) {
          print_error("Directory <observium>/rrd/$hostname already exists.");
          return FALSE;
        }

        // Detect snmp transport
        if (str_istarts($snmp_transport, 'tcp')) {
          $snmp_transport = ($ip_version == 4 ? 'tcp' : 'tcp6');
        } else {
          $snmp_transport = ($ip_version == 4 ? 'udp' : 'udp6');
        }

        // Detect snmp port
        if (!is_valid_param($snmp_port, 'port')) {
          $snmp_port = 161;
        } else {
          $snmp_port = (int)$snmp_port;
        }

        // Detect snmp version
        if (empty($snmp_version)) {
          // Here set default snmp version order
          $i = 1;
          $snmp_version_order = [];
          foreach ([ 'v2c', 'v3', 'v1' ] as $tmp_version) {
            if ($config['snmp']['version'] == $tmp_version) {
              $snmp_version_order[0]  = $tmp_version;
            } else {
              $snmp_version_order[$i] = $tmp_version;
            }
            $i++;
          }
          ksort($snmp_version_order);

          foreach ($snmp_version_order as $tmp_version) {
            $ret = add_device($hostname, $tmp_version, $snmp_port, $snmp_transport, $options);
            if ($ret === FALSE) {
              // Set $options['break'] for break recursive
              $options['break'] = TRUE;
            } elseif (is_intnum($ret) && $ret != 0) {
              return $ret;
            }
          }
        } elseif ($snmp_version === "v3") {
          // Try each set of parameters from config
          foreach ($config['snmp']['v3'] as $auth_iter => $snmp) {
            $snmp['version']   = $snmp_version;
            $snmp['port']      = $snmp_port;
            $snmp['transport'] = $snmp_transport;

            foreach ([ 'snmp_maxrep', 'snmp_timeout', 'snmp_retries' ] as $param) {
              if (isset($options[$param]) && is_numeric($options[$param])) {
                $snmp[$param] = (int)$options[$param];
              }
            }

            // Append SNMP context if passed
            if (isset($options['snmp_context']) && strlen($options['snmp_context'])) {
              $snmp['snmp_context'] = $options['snmp_context'];
            }

            $device = build_initial_device_array($hostname, NULL, $snmp_version, $snmp_port, $snmp_transport, $snmp);

            if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
              // Hide snmp auth
              print_message("Trying v3 parameters *** / ### [$auth_iter] ... ");
            } else {
              print_message("Trying v3 parameters " . $device['snmp_authname'] . "/" .  $device['snmp_authlevel'] . " ... ");
            }

            if (isSNMPable($device)) {
              if (!check_device_duplicated($device)) {
                if (isset($options['test']) && $options['test']) {
                  print_message('%WDevice "'.$hostname.'" has successfully been tested and available by '.strtoupper($snmp_transport).' transport with SNMP '.$snmp_version.' credentials.%n', 'color');
                  $device_id = -1;
                } else {
                  //$device_id = createHost($hostname, NULL, $snmp_version, $snmp_port, $snmp_transport, $snmp);
                  $device_id = create_device($hostname, $snmp);
                  if ($options['ping_skip']) {
                    set_entity_attrib('device', $device_id, 'ping_skip', 1);
                    // Force pingable check
                    if (is_pingable($hostname, $flags ^ OBS_PING_SKIP)) {
                      //print_warning("You passed the option the skip device ICMP echo pingable checks, but device responds to ICMP echo. Please check device preferences.");
                      print_message("You have checked the option to skip ICMP ping, but the device responds to an ICMP ping. Perhaps you need to check the device settings.");
                    }
                  }
                }
                return $device_id;
              } else {
                // When detected duplicate device, this mean it already SNMPable and not need check next auth!
                return FALSE;
              }
            } else {
              print_warning("No reply on credentials " . $device['snmp_authname'] . "/" .  $device['snmp_authlevel'] . " using $snmp_version.");
            }
          }
        } elseif ($snmp_version === "v2c" || $snmp_version === "v1") {
          // Try each community from config
          foreach ($config['snmp']['community'] as $auth_iter => $snmp_community) {
            $snmp = [
              'community' => $snmp_community,
              'version'   => $snmp_version,
              'port'      => $snmp_port,
              'transport' => $snmp_transport,
            ];

            foreach ([ 'snmp_maxrep', 'snmp_timeout', 'snmp_retries' ] as $param) {
              if (isset($options[$param]) && is_numeric($options[$param])) {
                $snmp[$param] = (int)$options[$param];
              }
            }

            // Append SNMP context if passed
            if (isset($options['snmp_context']) && strlen($options['snmp_context'])) {
              $snmp['snmp_context'] = $options['snmp_context'];
            }
            $device = build_initial_device_array($hostname, $snmp_community, $snmp_version, $snmp_port, $snmp_transport, $snmp);

            if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
              // Hide snmp auth
              print_message("Trying $snmp_version community *** [$auth_iter] ...");
            } else {
              print_message("Trying $snmp_version community $snmp_community ...");
            }

            if (isSNMPable($device)) {
              if (!check_device_duplicated($device)) {
                if (isset($options['test']) && $options['test']) {
                  print_message('%WDevice "'.$hostname.'" has successfully been tested and available by '.strtoupper($snmp_transport).' transport with SNMP '.$snmp_version.' credentials.%n', 'color');
                  $device_id = -1;
                } else {
                  //$device_id = createHost($hostname, $snmp_community, $snmp_version, $snmp_port, $snmp_transport, $snmp);
                  $device_id = create_device($hostname, $snmp);
                  if ($options['ping_skip']) {
                    set_entity_attrib('device', $device_id, 'ping_skip', 1);
                    // Force pingable check
                    if (is_pingable($hostname, $flags ^ OBS_PING_SKIP)) {
                      //print_warning("You passed the option the skip device ICMP echo pingable checks, but device responds to ICMP echo. Please check device preferences.");
                      print_message("You have checked the option to skip ICMP ping, but the device responds to an ICMP ping. Perhaps you need to check the device settings.");
                    }
                  }
                }
                return $device_id;
              } else {
                // When detected duplicate device, this mean it already SNMPable and not need check next auth!
                return FALSE;
              }
            } else {
              if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
                print_warning("No reply on given community *** using $snmp_version.");
              } else {
                print_warning("No reply on community $snmp_community using $snmp_version.");
              }
              $return = 0; // Return zero for continue trying next auth
            }
          }
        } else {
          print_error("Unsupported SNMP Version \"$snmp_version\".");
          $return = 0; // Return zero for continue trying next auth
        }

        if (!$device_id) {
          // Failed SNMP
          print_error("Could not reach $hostname with given SNMP parameters using $snmp_version.");
          $return = 0; // Return zero for continue trying next auth
        }
      } else {
        // failed Reachability
        print_error("Could not ping $hostname.");
      }
    } else {
      // Failed DNS lookup
      print_error("Could not resolve $hostname.");
    }
  } else {
    // found in database
    print_error("Already got device $hostname.");
  }

  return $return;
}

/**
 * Check duplicated devices in DB by sysName, snmpEngineID and entPhysicalSerialNum (if possible)
 * Can work only on local poller
 *
 * If found duplicate devices return TRUE, in other cases return FALSE
 *
 * @param array $device Device array which should be checked for duplicates
 * @return bool TRUE if duplicates found
 */
// TESTME needs unit testing
function check_device_duplicated($device) {
  // Hostname should be uniq
  if ($device['hostname'] &&
      //dbFetchCell("SELECT COUNT(*) FROM `devices` WHERE `hostname` = ?", array($device['hostname'])) != '0')
      dbExist('devices', '`hostname` = ?', array($device['hostname']))) {
    // Return TRUE if have device with same hostname in DB
    print_error("Already got device with hostname (".$device['hostname'].").");
    return TRUE;
  }

  $snmpEngineID = snmp_cache_snmpEngineID($device);
  $sysName_orig = snmp_get_oid($device, 'sysName.0', 'SNMPv2-MIB');
  $sysName      = strtolower($sysName_orig);
  if (empty($sysName)) {
    $sysName_type = 'empty';
    $sysName = FALSE;
  } elseif (is_valid_hostname($sysName_orig, TRUE)) {
    // sysName stored in db as lowercase, always compare as lowercase too!
    $sysName_type = 'fqdn';
  } else{
    // sysName not FQDN, hard case, many devices have default sysname or user just not write full sysname
    $sysName_type = 'notfqdn';
  }

  if (!empty($snmpEngineID)) {
    $test_devices = dbFetchRows('SELECT * FROM `devices` WHERE `disabled` = 0 AND `snmpEngineID` = ?', array($snmpEngineID));
    foreach ($test_devices as $test) {
      $compare = strtolower($test['sysName']) === $sysName;
      if ($compare) {
        // Check (if possible) serial, for cluster devices sysName and snmpEngineID same
        $test_entPhysical = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalSerialNum` != ? ORDER BY `entPhysicalClass` LIMIT 1', array($test['device_id'], ''));
        if (isset($test_entPhysical['entPhysicalSerialNum'])) {
          // Compare by any common serial
          $serial = snmp_get_oid($device, 'entPhysicalSerialNum.'.$test_entPhysical['entPhysicalIndex'], 'ENTITY-MIB');
          $compare = strtolower($serial) === strtolower($test_entPhysical['entPhysicalSerialNum']);
          if ($compare) {
            // This devices really same, with same sysName, snmpEngineID and entPhysicalSerialNum
            print_error("Already got device with SNMP-read sysName ($sysName), 'snmpEngineID' = $snmpEngineID and 'entPhysicalSerialNum' = $serial (".$test['hostname'].").");
            return TRUE;
          }
        } else {
          // For not FQDN sysname check all (other) possible system Oids:
          if ($sysName_type !== 'fqdn') {
            $compare = compare_devices_oids($device, $test);
            // Same sysName and snmpEngineID, but different some other system Oids
            if (!$compare) { continue; }
          }
          // Return TRUE if have same snmpEngineID && sysName in DB
          print_error("Already got device with SNMP-read sysName ($sysName) and 'snmpEngineID' = $snmpEngineID (".$test['hostname'].").");
          return TRUE;
        }
      }
    }

  } else {

    if ($sysName_type === 'empty' && ($device['os'] === 'generic' || empty($device['os']))) {
      // For some derp devices (ie, only ent tree Oids) detect os before next checks
      $device['os'] = get_device_os($device);

      $test_devices = dbFetchRows('SELECT * FROM `devices` WHERE `disabled` = 0 AND `sysName` = ? AND `os` = ?', [ $sysName, $device['os'] ]);
    } else {
      // If snmpEngineID empty, check by sysName (and additional system Oids)
      $test_devices = dbFetchRows('SELECT * FROM `devices` WHERE `disabled` = 0 AND `sysName` = ?', [ $sysName ]);
    }

    foreach ($test_devices as $test) {
      // Last check (if possible) serial, for cluster devices sysName and snmpEngineID same
      $test_entPhysical = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalSerialNum` != ? ORDER BY `entPhysicalClass` LIMIT 1', array($test['device_id'], ''));
      if (isset($test_entPhysical['entPhysicalSerialNum'])) {
        $serial = snmp_get_oid($device, "entPhysicalSerialNum.".$test_entPhysical['entPhysicalIndex'], "ENTITY-MIB");
        $compare = strtolower($serial) === strtolower($test_entPhysical['entPhysicalSerialNum']);
        if ($compare) {
          // This devices really same, with same sysName, snmpEngineID and entPhysicalSerialNum
          print_error("Already got device with SNMP-read sysName ($sysName) and 'entPhysicalSerialNum' = $serial (".$test['hostname'].").");
          return TRUE;
        }
      } else {
        // Check all (other) possible system Oids:
        $compare = compare_devices_oids($device, $test);
        // Same sysName and other system Oids
        if ($compare) {
          // Derp case, when device not have uniq Oids.. completely, ie Hikvision cams
          //if (empty($sysName) && )
          // This devices really same, with same sysName and other system Oids
          print_error("Already got device with SNMP-read sysName ($sysName) and other system Oids (".$test['hostname'].").");
          return TRUE;
        }
      }
    }
    // if (!$has_entPhysical)
    // {
    //   // Return TRUE if have same sysName in DB
    //   print_error("Already got device with SNMP-read sysName ($sysName).");
    //   return TRUE;
    // }

  }

  // In all other cases return FALSE
  return FALSE;
}

/**
 * Detect SNMP auth params without adding device by hostname or IP
 * if SNMP auth detected return array with auth params or FALSE if not detected
 *
 * @param string $hostname
 * @param int    $snmp_port
 * @param string $snmp_transport
 * @param false  $detect_ip_version
 *
 * @return array|false
 */
function detect_device_snmpauth($hostname, $snmp_port = 161, $snmp_transport = 'udp', $detect_ip_version = FALSE) {
  global $config;

  // Additional checks for IP version
  if ($detect_ip_version) {
    $ip_version = get_ip_version($hostname);
    if (!$ip_version) {
      $ip = gethostbyname6($hostname);
      $ip_version = get_ip_version($ip);
    }
    // Detect snmp transport
    if (str_istarts($snmp_transport, 'tcp')) {
      $snmp_transport = ($ip_version == 4 ? 'tcp' : 'tcp6');
    } else {
      $snmp_transport = ($ip_version == 4 ? 'udp' : 'udp6');
    }
  }
  // Detect snmp port
  if (!is_valid_param($snmp_port, 'port')) {
    $snmp_port = 161;
  } else {
    $snmp_port = (int)$snmp_port;
  }

  // Here set default snmp version order
  $i = 1;
  $snmp_version_order = array();
  foreach (array('v2c', 'v3', 'v1') as $tmp_version) {
    if ($config['snmp']['version'] == $tmp_version) {
      $snmp_version_order[0]  = $tmp_version;
    } else {
      $snmp_version_order[$i] = $tmp_version;
    }
    $i++;
  }
  ksort($snmp_version_order);

  foreach ($snmp_version_order as $snmp_version) {
    if ($snmp_version === 'v3') {
      // Try each set of parameters from config
      foreach ($config['snmp']['v3'] as $auth_iter => $snmp_v3) {
        $device = build_initial_device_array($hostname, NULL, $snmp_version, $snmp_port, $snmp_transport, $snmp_v3);

        if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
          // Hide snmp auth
          print_message("Trying v3 parameters *** / ### [$auth_iter] ... ");
        } else {
          print_message("Trying v3 parameters " . $device['snmp_authname'] . "/" .  $device['snmp_authlevel'] . " ... ");
        }

        if (isSNMPable($device)) {
          return $device;
        } elseif ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
          print_warning("No reply on credentials *** / ### using $snmp_version.");
        } else {
          print_warning("No reply on credentials " . $device['snmp_authname'] . "/" .  $device['snmp_authlevel'] . " using $snmp_version.");
        }
      }

    } else { // if ($snmp_version === "v2c" || $snmp_version === "v1")

      // Try each community from config
      foreach ($config['snmp']['community'] as $auth_iter => $snmp_community) {
        $device = build_initial_device_array($hostname, $snmp_community, $snmp_version, $snmp_port, $snmp_transport);

        if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2) {
          // Hide snmp auth
          print_message("Trying $snmp_version community *** [$auth_iter] ...");
        } else {
          print_message("Trying $snmp_version community $snmp_community ...");
        }

        if (isSNMPable($device)) {
          return $device;
        } else {
          print_warning("No reply on community $snmp_community using $snmp_version.");
        }
      }
    }
  }

  return FALSE;
}

//DEPRECATED. Compatibility for old style
function createHost($hostname, $snmp_community, $snmp_version, $snmp_port = 161, $snmp_transport = 'udp', $snmp_extra = []) {
  $snmp_array = [
    'snmp_version'   => $snmp_version,
    'snmp_port'      => $snmp_port,
    'snmp_transport' => $snmp_transport,
    'community'      => $snmp_community,
  ];
  if (!empty($snmp_extra)) {
    $snmp_array = array_merge($snmp_array, $snmp_extra);
  }

  return create_device($hostname, $snmp_array);
}

/**
 * Add device into database
 *
 * @param string $hostname Device hostname
 * @param array  $snmp SNMP v1/v2c/v3 params and Extra options
 *
 * @return bool|string
 */
function create_device($hostname, $snmp = []) {
  $hostname = strtolower(trim($hostname));

  $device = [
    'hostname'       => $hostname,
    'sysName'        => $hostname,
    'status'         => '1',
  ];

  // Add snmp params & snmp extra
  $snmp_params = [
    'port', 'transport', 'version', 'timeout', 'retries', 'maxrep',
    'community', 'authlevel', 'authname', 'authpass', 'authalgo',
    'cryptopass', 'cryptoalgo', 'context',
  ];
  foreach ($snmp_params as $param) {
    $snmp_param = 'snmp_'.$param;
    if (isset($snmp[$snmp_param])) {
      // Or $snmp_v3['snmp_authlevel']
      $device[$snmp_param] = $snmp[$snmp_param];
    } elseif (isset($snmp[$param])) {
      // Or $snmp_v3['authlevel']
      $device[$snmp_param] = $snmp[$param];
    } elseif ($param === 'port') {
      $device[$snmp_param] = 161;
    } elseif ($param === 'transport') {
      $device[$snmp_param] = 'udp';
    } else {
      //$device[$snmp_param] = [ 'NULL' ];
    }
  }

  // Local poller id (for distributed system)
  $poller_id = $GLOBALS['config']['poller_id']; // $config['poller_id'] sets in sql-config.php
  if (isset($GLOBALS['config']['poller_name']) &&
      $poller_local_id = dbFetchCell("SELECT `poller_id` FROM `pollers` WHERE `poller_name` = ?", [ $GLOBALS['config']['poller_name'] ])) {
    $poller_local = $poller_id == $poller_local_id;
  } else {
    $poller_local = $poller_id == 0;
  }
  $device['poller_id'] = $poller_id;

  // FIXME. Need ability for requests from remote poller or queue to remote
  if ($poller_local) {
    // SNMP requests only on local poller
    $device['os']           = get_device_os($device);
    $device['sysObjectID']  = snmp_cache_sysObjectID($device);
    $device['snmpEngineID'] = snmp_cache_snmpEngineID($device);
    $device['sysName']      = snmp_get_oid($device, 'sysName.0', 'SNMPv2-MIB');
    $device['location']     = snmp_get_oid($device, 'sysLocation.0', 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_UTF8);
    $device['sysContact']   = snmp_get_oid($device, 'sysContact.0', 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_UTF8);
  }

  if (!$poller_local || $device['os']) {
    $device_id = dbInsert($device, 'devices');
    if ($device_id) {
      $device['device_id'] = $device_id;

      $log_msg = "Device added: $hostname";
      if ($poller_id > 0 &&
          $poller_name = dbFetchCell("SELECT `poller_name` FROM `pollers` WHERE `poller_id` = ?", [ $poller_id ])) {
        // Append poller name
        $log_msg .= " (Poller: $poller_name [$poller_id])";
      }
      log_event($log_msg, $device_id, 'device', $device_id, 5); // severity 5, for logging user/console info
      if (isset($device['sysObjectID']) && strlen($device['sysObjectID'])) {
        log_event('sysObjectID -> ' . $device['sysObjectID'], $device, 'device', $device_id);
      }
      if (isset($device['snmpEngineID']) && strlen($device['snmpEngineID'])) {
        log_event('snmpEngineID -> ' . $device['snmpEngineID'], $device, 'device', $device_id);
      }

      if ($poller_local && is_cli()) {
        print_success("Now discovering ".$device['hostname']." (id = ".$device_id.")");
        $device['device_id'] = $device_id;
        // Discover things we need when linking this to other hosts.
        discover_device($device, $options = array('m' => 'os,mibs,ports,ip-addresses'));
        // Reset `last_discovered` for full rediscover device by cron
        dbUpdate(array('last_discovered' => 'NULL'), 'devices', '`device_id` = ?', array($device_id));
      }

      // Request for clear WUI cache
      set_cache_clear('wui');

      return($device_id);
    }
  }

  return FALSE;
}

/**
 * Deletes device from database and RRD dir.
 *
 * @param int $id
 * @param bool $delete_rrd
 *
 * @return false|string
 */
function delete_device($id, $delete_rrd = FALSE) {
  global $config;

  $ret = PHP_EOL;
  $device = device_by_id_cache($id);
  $host = $device['hostname'];

  if (!is_array($device)) {
    return FALSE;
  } else {
    $ports = dbFetchRows("SELECT * FROM `ports` WHERE `device_id` = ?", array($id));
    if (!empty($ports)) {
      $ret .= ' * Deleted interfaces: ';
      $deleted_ports = [];
      foreach ($ports as $int_data) {
        $int_if = $int_data['ifDescr'];
        $int_id = $int_data['port_id'];
        delete_port($int_id, $delete_rrd);
        $deleted_ports[] = "id=$int_id ($int_if)";
      }
      $ret .= implode(', ', $deleted_ports).PHP_EOL;
    }

    // Remove entities from common tables
    $deleted_entities = array();
    foreach (get_device_entities($id) as $entity_type => $entity_ids) {
      foreach ($config['entity_tables'] as $table) {
        $where = '`entity_type` = ?' . generate_query_values($entity_ids, 'entity_id');
        $table_status = dbDelete($table, $where, array($entity_type));
        if ($table_status) { $deleted_entities[$entity_type] = 1; }
      }
    }
    if (count($deleted_entities)) {
      $ret .= ' * Deleted common entity entries linked to device: ';
      $ret .= implode(', ', array_keys($deleted_entities)) . PHP_EOL;
    }

    $deleted_tables = array();
    $ret .= ' * Deleted device entries from tables: ';
    foreach ($config['device_tables'] as $table) {
      $where = '`device_id` = ?';
      $table_status = dbDelete($table, $where, array($id));
      if ($table_status) { $deleted_tables[] = $table; }
    }

    // Remove autodiscovery entries
    $table_status = dbDelete('autodiscovery', '`remote_device_id` = ?', [ $id ]);
    if ($table_status) { $deleted_tables[] = 'autodiscovery'; }

    if (count($deleted_tables)) {
      $ret .= implode(', ', $deleted_tables).PHP_EOL;

      // Request for clear WUI cache
      set_cache_clear('wui');
    }

    if ($delete_rrd) {
      $device_rrd = rtrim(get_rrd_path($device, ''), '/');
      if (is_file($device_rrd.'/status.rrd')) {
        external_exec("rm -rf ".escapeshellarg($device_rrd));
        $ret .= ' * Deleted device RRDs dir: ' . $device_rrd . PHP_EOL;
      }

    }

    log_event("Deleted device: $host", $id, 'device', $id, 5); // severity 5, for logging user/console info
    $ret .= " * Deleted device: $host";
  }

  return $ret;
}

function device_status_array(&$device) {
  global $attribs;

  $flags = OBS_DNS_ALL;
  if ($device['snmp_transport'] === 'udp6' || $device['snmp_transport'] === 'tcp6') { // Exclude IPv4 if used transport 'udp6' or 'tcp6'
    $flags ^= OBS_DNS_A;
  }

  $attribs['ping_skip'] = isset($attribs['ping_skip']) && $attribs['ping_skip'];
  if ($attribs['ping_skip']) {
    $flags |= OBS_PING_SKIP; // Add skip ping flag
  }

  $device['pingable'] = is_pingable($device['hostname'], $flags);
  if ($device['pingable']) {
    $device['snmpable'] = isSNMPable($device);
    if ($device['snmpable']) {
      $ping_msg = ($attribs['ping_skip'] ? '' : 'PING (' . $device['pingable'] . 'ms) and ');

      //print_cli_data("Device status", "Device is reachable by " . $ping_msg . "SNMP (".$device['snmpable']."ms)", 1);
      $status_message = "Device is reachable by " . $ping_msg . "SNMP (".$device['snmpable']."ms)";
      $status = "1";
      $status_type = 'ok';
    } else {
      //print_cli_data("Device status", "Device is not responding to SNMP requests", 1);
      $status_message = "Device is not responding to SNMP requests";
      $status = "0";
      $status_type = 'snmp';
    }
  } else {
    //print_cli_data("Device status", "Device is not responding to PINGs", 1);
    $status_message = "Device is not responding to PINGs";
    $status = "0";
    //print_vars(get_status_var('ping_dns'));
    if (isset_status_var('ping_dns') && get_status_var('ping_dns') !== 'ok') {
      $status_type = 'dns';
    } else {
      $status_type = 'ping';
    }
  }

  return [ 'status' => $status, 'status_type' => $status_type, 'message' => $status_message ];
}

function device_host($device) {
  // Return cached IP (only for poller, other processes not resolve IPs)
  return (OBS_PROCESS_NAME === 'poller' && $GLOBALS['config']['use_ip'] && !safe_empty($device['ip'])) ? $device['ip'] : $device['hostname'];
}

/**
 * If a device is up, return its uptime, otherwise return the
 * time since the last time we were able to poll it.  This
 * is not very accurate, but better than reporting what the
 * uptime was at some time before it went down.
 * @param array  $device
 * @param string $format
 *
 * @return string
 */
// TESTME needs unit testing
function device_uptime($device, $format = "long")
{
  if ($device['status'] == 0)
  {
    if ($device['last_polled'] == 0)
    {
      return "Never polled";
    }

    $since = time() - strtotime($device['last_polled']);
    //$reason = isset($device['status_type']) && $format == 'long' ? '('.strtoupper($device['status_type']).') ' : '';
    $reason = isset($device['status_type']) ? '('.strtoupper($device['status_type']).') ' : '';

    return "Down $reason" . format_uptime($since, $format);
  } else {
    return format_uptime($device['uptime'], $format);
  }
}

//FIXME. Need refactor
function deviceUptime($device, $format = "long")
{
  return device_uptime($device, $format);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function device_by_name($name, $refresh = 0)
{
  // FIXME - cache name > id too.
  return device_by_id_cache(get_device_id_by_hostname($name), $refresh);
}

/**
 * Returns a device_id when given an entity_id and an entity_type. Returns FALSE if the device isn't found.
 *
 * @param $entity_id
 * @param $entity_type
 *
 * @return bool|integer
 */
function get_device_id_by_entity_id($entity_id, $entity_type) {
  if ($entity_type === 'device') { return $entity_id; }

  // $entity = get_entity_by_id_cache($entity_type, $entity_id);
  $translate = entity_type_translate_array($entity_type);

  if (isset($translate['device_id_field']) && $translate['device_id_field'] &&
      is_numeric($entity_id) && $entity_type) {
    $device_id = dbFetchCell('SELECT `' . $translate['device_id_field'] . '` FROM `' . $translate['table']. '` WHERE `' . $translate['id_field'] . '` = ?', array($entity_id));
  }
  if (is_numeric($device_id)) {
    return $device_id;
  }

  return FALSE;
}


// DOCME needs phpdoc block
// TESTME needs unit testing
function device_by_id_cache($device_id, $refresh = 0)
{
  global $cache;

  if (!$refresh && isset($cache['devices']['id'][$device_id]) && is_array($cache['devices']['id'][$device_id]))
  {
    $device = $cache['devices']['id'][$device_id];
  } else {
    $device = dbFetchRow("SELECT * FROM `devices` WHERE `device_id` = ?", array($device_id));
  }

  if (!empty($device))
  {
    humanize_device($device);
    if ($refresh || !isset($device['graphs']))
    {
      // Fetch device graphs
      $device['graphs'] = dbFetchRows("SELECT * FROM `device_graphs` WHERE `device_id` = ?", array($device_id));
    }
    $cache['devices']['id'][$device_id] = $device;

    return $device;
  } else {
    return FALSE;
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_hostname($hostname)
{
  global $cache;

  if (isset($cache['devices']['hostname'][$hostname]))
  {
    $id = $cache['devices']['hostname'][$hostname];
  } else {
    $id = dbFetchCell("SELECT `device_id` FROM `devices` WHERE `hostname` = ?", array($hostname));
  }

  if (is_numeric($id))
  {
    return $id;
  } else {
    return FALSE;
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_port_id($port_id)
{
  if (is_numeric($port_id))
  {
    $device_id = dbFetchCell("SELECT `device_id` FROM `ports` WHERE `port_id` = ?", array($port_id));
    if (is_numeric($device_id))
    {
      return $device_id;
    }
  }

  return FALSE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_mac($mac, $exclude_device_id = NULL)
{
  $remote_mac = mac_zeropad($mac);
  if ($remote_mac && $remote_mac != '000000000000')
  {
    $where = '`deleted` = ? AND `ifPhysAddress` = ?';
    $params = [ 0, $remote_mac ];
    if (is_numeric($exclude_device_id))
    {
      $where .= ' AND `device_id` != ?';
      $params[] = $exclude_device_id;
    }
    $device_id = dbFetchCell("SELECT `device_id` FROM `ports` WHERE $where LIMIT 1;", $params);
    if (is_numeric($device_id))
    {
      return $device_id;
    }
  }

  return FALSE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_app_id($app_id)
{
  if (is_numeric($app_id))
  {
    $device_id = dbFetchCell("SELECT `device_id` FROM `applications` WHERE `app_id` = ?", array($app_id));
  }
  if (is_numeric($device_id))
  {
    return $device_id;
  } else {
    return FALSE;
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_entphysical_state($device)
{
  $state = array();
  foreach (dbFetchRows("SELECT * FROM `entPhysical-state` WHERE `device_id` = ?", array($device)) as $entity)
  {
    $state['group'][$entity['group']][$entity['entPhysicalIndex']][$entity['subindex']][$entity['key']] = $entity['value'];
    $state['index'][$entity['entPhysicalIndex']][$entity['subindex']][$entity['group']][$entity['key']] = $entity['value'];
  }

  return $state;
}

/**
 * Generates list of possible device hostnames for external integrations (ie RANCID, Oxidized)
 *
 * @param array|string $device
 * @param array|string $suffix
 * @param bool         $dprint
 *
 * @return array
 */
function generate_device_hostnames($device, $suffix = '', $dprint = FALSE) {

  $hostnames = [];

  if (is_intnum($device)) {
    $device = device_by_id_cache($device);
    if ($dprint) { echo "Device: get by device id.<br />"; }
  } elseif (is_string($device)) {
    $hostname = $device;
    $device = device_by_name($device);

    // Device by IP
    if (!$device && get_ip_version($hostname) &&
        $ids = get_entity_ids_ip_by_network('device', $hostname)) {
      $device = device_by_id_cache($ids[0]);
      if ($dprint) { echo "Device: get by device IP.<br />"; }
    } elseif ($dprint) { echo "Device: get by hostname.<br />"; }
  }

  // Not valid device
  if (!is_array($device) || !isset($device['hostname'])) {
    if ($dprint) { echo "Device: No valid device array or hostname passed.<br />"; }
    return $hostnames;
  }

  // Base hostname
  $hostname = $device['hostname'];
  $hostnames[] = $device['hostname'];
  if ($dprint) { echo("Hostname: $hostname<br />"); }

  // Also check non-FQDN hostname.
  $is_ip = (bool)get_ip_version($hostname);
  if (!$is_ip && str_contains($hostname, '.')) {
    list($shortname) = explode('.', $hostname);

    if ($dprint) {
      echo("Short hostname: $shortname<br />");
    }

    if ($shortname != $hostname) {
      $hostnames[] = $shortname;
      if ($dprint) {
        echo("Hostname different from short hostname, looking for both<br />");
      }
    }
  }

  // Addition of a domain suffix for non-FQDN device names.
  if (!$is_ip && !safe_empty($suffix)) {
    foreach ((array)$suffix as $append) {
      $fqdn = $hostname . '.' . trim($append, ' .');
      if (is_valid_hostname($fqdn, TRUE)) {
        $hostnames[] = $fqdn;
        if ($dprint) {
          echo("Hostname suffix passed, also looking for " . $fqdn . "<br />");
        }
      }
    }
  }

  // Device sysName
  $sysname = strtolower($device['sysName']);
  if (!in_array($sysname, $hostnames, TRUE) &&
      !in_array($sysname, $GLOBALS['config']['devices']['ignore_sysname'], TRUE) &&
      is_valid_hostname($sysname)) {
    $hostnames[] = $sysname;
    if ($dprint) { echo("sysName: $sysname<br />"); }

    // Addition of a domain suffix for non-FQDN device sysName.
    if (!str_contains($sysname, '.') && !safe_empty($suffix)) {
      foreach ((array)$suffix as $append) {
        $fqdn = $sysname . '.' . trim($append, ' .');
        if (!in_array($fqdn, $hostnames, TRUE) && is_valid_hostname($fqdn, TRUE)) {
          $hostnames[] = $fqdn;
          if ($dprint) {
            echo("Hostname suffix passed, also looking for " . $fqdn . "<br />");
          }
        }
      }
    }
  }

  return $hostnames;
}

/**
 * Generate device tags for use with some entities (ie probes)
 * @param array $device
 * @param bool $escape
 *
 * @return array
 */
function generate_device_tags($device, $escape = TRUE)
{
  $params = [
    // Basic
    'hostname',
    // SNMP
    'snmp_version', 'snmp_community', 'snmp_context', 'snmp_authlevel', 'snmp_authname', 'snmp_authpass',  'snmp_authalgo',
    'snmp_cryptopass', 'snmp_cryptoalgo', 'snmp_port', 'snmp_timeout', 'snmp_retries'
  ];

  $device_tags = [];

  foreach($params as $variable)
  {
    if (!empty($device[$variable]) || in_array($variable, ['snmp_timeout', 'snmp_authname']))
    {
      switch ($variable)
      {
        case 'snmp_version':
          $device_tags[$variable] = str_replace('v', '', $device[$variable]);
          break;
        case 'snmp_authalgo':
        case 'snmp_cryptoalgo':
          $device_tags[$variable] = strtoupper($device[$variable]);
          break;
        case 'snmp_authname':
          // Always pass username, default observium (need for noathnopriv)
          $device_tags[$variable] = strlen($device[$variable]) ? $device[$variable] : 'observium';
          break;
        case 'snmp_timeout':
          // Always pass timeout, default 15
          $device_tags[$variable] = is_numeric($device[$variable]) ? $device[$variable] : 15;
          break;
        default:
          $device_tags[$variable] = $device[$variable];
      }
      // Escape for pass to shell cmd
      if ($escape) { $device_tags[$variable] = escapeshellarg($device_tags[$variable]); }
    }
  }

  return $device_tags;
}

/**
 * Poll device metatypes, see os, system and wifi modules.
 *
 * @param array $device Device array
 * @param array $metatypes List of required metatypes
 * @param array $poll_device
 *
 * @return array
 */
function poll_device_mib_metatypes($device, $metatypes, &$poll_device = []) {
  global $config;

  foreach ($metatypes as $metatype) {

    switch ($metatype) {
      case 'sysUpTime':
        $param = 'uptime'; // For sysUptime use simple param name
        break;
      case 'wifi_clients':
        $param    = 'wifi_clients';
        $metatype = 'wifi_clients1';
        break;
      default:
        $param = strtolower($metatype);
    }

    foreach (get_device_mibs_permitted($device) as $mib) { // Check every MIB supported by the device, in order
      if (isset($config['mibs'][$mib][$param])) {
        $metatype_uptime = $metatype === 'sysUpTime' || $metatype === 'reboot';

        foreach ($config['mibs'][$mib][$param] as $entry) {
          // For ability override metatype by vendor mib definition use 'force' boolean param
          if (!isset($poll_device[$metatype]) ||                     // Poll if metatype not already set
              $metatype_uptime ||                                    // Or uptime/reboot (always polled)
              (isset($entry['force']) && $entry['force']) ||         // Or forced override (see ekinops EKINOPS-MGNT2-MIB mib definition
              !is_valid_param($poll_device[$metatype], $metatype)) { // Poll if metatype not valid by previous round

            if (discovery_check_requires_pre($device, $entry, 'device')) {
              // Examples in RFC UPS-MIB, QNAP NAS-MIB and APC PowerNet-MIB
              print_debug("Definition entry [$metatype] [$mib::".$entry['oid'].$entry['oid_next'].$entry['oid_count']."] skipped by pre test condition");
              continue;
            }

            // Force HEX to UTF8 conversion by default
            $flags = isset($entry['snmp_flags']) ? $entry['snmp_flags'] : OBS_SNMP_ALL_UTF8;

            if (isset($entry['table'])) {
              // Get Oid by table walk (with possible tests)
              $value = NULL;
              foreach (snmp_cache_table($device, $entry['table'], [], $mib, NULL, $flags) as $index => $values) {
                if (!isset($values[$entry['oid']])) { continue; } // Skip unknown entries

                if (isset($entry['test']) && discovery_check_requires($device, $entry, $values, 'device')) {
                  // Examples in MDS-SYSTEM-MIB
                  print_debug("Definition entry [$metatype] [$mib::".$entry['oid']."] skipped by test condition");
                  continue;
                }
                // if test not required, use first entry (as getnext)
                $value = $values[$entry['oid']];
                $full_oid = "$mib::${entry['oid']}.$index";
                break;
              }
            } elseif (isset($entry['oid_num'])) { // Use numeric OID if set, otherwise fetch text based string
              $value = snmp_get_oid($device, $entry['oid_num'], NULL, $flags);
              $full_oid = $entry['oid_num'];
            } elseif (isset($entry['oid_next'])) {
              // If Oid passed without index part use snmpgetnext (see FCMGMT-MIB definitions)
              $value = snmp_getnext_oid($device, $entry['oid_next'], $mib, NULL, $flags);
              $full_oid = "$mib::${entry['oid_next']}";
            } elseif (isset($entry['oid_count'])) {
              // This is special type of get data by snmpwalk and count entries
              $data = snmpwalk_values($device, $entry['oid_count'], $mib);
              $value = is_array($data) ? count($data) : '';
              $full_oid = str_starts($entry['oid_count'], '.') ? $entry['oid_count'] : "$mib::${entry['oid_count']}";
            } else {
              $value = snmp_get_oid($device, $entry['oid'], $mib, NULL, $flags);
              $full_oid = "$mib::${entry['oid']}";
            }

            if (snmp_status() && !safe_empty($value)) {
              $polled = round(snmp_endtime());

              // Additional Oids for current metaparam (see IMM-MIB hardware definition)
              if (isset($entry['oid_extra']) && is_valid_param($value, $metatype)) {
                $extra = [];
                foreach ((array)$entry['oid_extra'] as $oid_extra) {
                  //$value_extra = trim(snmp_hexstring(snmp_get_oid($device, $oid_extra, $mib)));
                  $value_extra = snmp_get_oid($device, $oid_extra, $mib, NULL, $flags);
                  if (snmp_status() && !safe_empty($value_extra)) {
                    $extra[] = $value_extra;
                  }
                }
                if (count($extra)) {
                  if ($metatype === 'version' && preg_match('/^[\d\.]+$/', $value)) {
                    // version -> xxx.y.z
                    $value .= '.' . implode('.', $extra);
                  } else {
                    // others ->  xxx (y, z)
                    $value .= ' (' . implode(', ', $extra) . ')';
                  }
                }
                unset($oid_extra, $extra, $value_extra);
              }

              // Field found (no SNMP error), perform optional transformations.
              if (isset($entry['transform'])) {
                // Just simplify definition entry (unify with others)
                $entry['transformations'] = $entry['transform'];
              }
              $value = trim(string_transform($value, $entry['transformations']));

              // Detect uptime with MIB defined oids, see below uptimes 2.
              if ($metatype_uptime) {
                // Previous uptime from standard sysUpTime or other MIB::oid
                $uptime_previous = isset($poll_device['device_uptime']) ? $poll_device['device_uptime'] : $poll_device['sysUpTime'];
                if ($param === 'uptime' && !isset($entry['transformations']) && !is_numeric($value)) {
                  // Convert common uptime strings to seconds by default
                  // See MDS-SYSTEM-MIB
                  $value = uptime_to_seconds($value);
                } elseif ($param === 'reboot' && is_numeric($value)) {
                  // Last reboot is same uptime, but as diff with current (polled) time (see INFINERA-ENTITY-CHASSIS-MIB)
                  $value = $polled - $value;
                }
                // Detect if new sysUpTime value more than the previous
                if (is_numeric($value) && $value >= $uptime_previous) {
                  $poll_device['device_uptime'] = $value;
                  // Fixme, not know how re-add this message
                  //$uptimes['message'] = isset($entry['oid_num']) ? $entry['oid_num'] : $mib . '::' . $entry['oid'];
                  //$uptimes['message'] = 'Using device MIB poller '.$metatype.': ' . $uptimes['message'];

                  print_debug("Added System Uptime from SNMP definition fetch: 'device_uptime' = '$value'");
                }
                // Continue for other possible sysUpTime and use maximum value
                continue;
              }

              if (!is_valid_param($value, $metatype)) {
                // Skip invalid values
                continue;
              }

              $poll_device[$metatype] = $value;
              print_debug("Added Device param from SNMP definition by [$full_oid]: '$metatype' = '$value'");

              // Exit both foreach loops and move on to the next metatype.
              break 2;
            }
          }
        }
      }
    }

  }
  print_debug_vars($poll_device);

  return $poll_device;
}

function poll_device_unix_packages($device, $metatypes, $defs = []) {
  global $attribs, $agent_data;

  // packages definitions
  $package_defs = [];
  if (empty($defs)) {
    // Used in poll os packages
    foreach ($GLOBALS['config']['os'][$device['os']]['packages'] as $def) {
      $package_defs[$def['name']] = $def;
    }
    $attrib_key = 'os_package_index';
  } else {
    foreach ($defs as $def) {
      $package_defs[$def['name']] = $def;
    }
    $attrib_key = 'def_package_index';
  }
  if (safe_empty($package_defs)) { return []; }

  $data = [];

  // Unix-agent packages exist?
  $agent_packages = !safe_empty($agent_data['dpkg']) || !safe_empty($agent_data['rpm']);

  if ($agent_packages) {
    // by unix-agent packages
    //$sql = 'SELECT * FROM `packages` WHERE `device_id` = ? AND `status` = ? AND `name` IN (?, ?, ?)';
    $sql = 'SELECT * FROM `packages` WHERE `device_id` = ? AND `status` = ?';
    $sql .= generate_query_values(array_keys($package_defs), 'name');
    $params = [ $device['device_id'], 1 ];
    if ($package = dbFetchRow($sql, $params)) {
      //$name    = $package['name'];
      $def     = $package_defs[$package['name']];
      print_debug_vars($def, 1);
      print_debug_vars($package, 1);

      // Type, features, version, etc
      foreach ($metatypes as $metatype) {
        if ($metatype === 'version' || $metatype === 'distro_ver') {
          // Version
          $data[$metatype] = $package['version'];
          if (isset($def['transform'], $data[$metatype])) {
            $data[$metatype] = string_transform($data[$metatype], $def['transform']);
          }
        } elseif (isset($def[$metatype])) {
          // all other metatypes
          $data[$metatype] = $def[$metatype];
        }
      }
    }
  } elseif (is_device_mib($device, 'HOST-RESOURCES-MIB')) {
    // by SNMP query

    $found = FALSE;
    if (isset($attribs[$attrib_key])) {
      // fetch package version and check if indexes was not changed
      $package = snmp_get_oid($device, 'hrSWInstalledName.'.$attribs[$attrib_key], 'HOST-RESOURCES-MIB');
      if (snmp_status()) {
        foreach ($package_defs as $def) {
          if (preg_match($def['regex'], $package, $matches)) {
            $found = TRUE;
            print_debug_vars($def, 1);
            print_debug_vars($package, 1);

            // Type, features, version, etc
            foreach ($metatypes as $metatype) {
              if ($metatype === 'version' || $metatype === 'distro_ver') {
                // Version
                if (isset($matches['version'])) {
                  $data[$metatype] = $matches['version'];

                  if (isset($def['transform'])) {
                    $data[$metatype] = string_transform($data[$metatype], $def['transform']);
                  }
                }
              } elseif (isset($def[$metatype])) {
                // all other metatypes
                $data[$metatype] = $def[$metatype];
              }
            }
            break;
          }
        }
      }

      if (!$found) {
        // packages changed, re-walk all
        print_debug("Packages changed, refetch all");
        del_entity_attrib('device', $device['device_id'], 'os_package_index');
        unset($attribs[$attrib_key]);
      }
      unset($package);
    }

    if (!$found) {
      // walk all packages
      $oids = snmpwalk_cache_oid($device, "hrSWInstalledName", [], 'HOST-RESOURCES-MIB');

      foreach ($oids as $index => $entry) {
        $package = $entry['hrSWInstalledName'];
        foreach ($package_defs as $def) {
          if (preg_match($def['regex'], $package, $matches)) {
            $found = TRUE;
            print_debug_vars($def, 1);
            print_debug_vars($entry, 1);

            // Type, features, version, etc
            foreach ($metatypes as $metatype) {
              if ($metatype === 'version' || $metatype === 'distro_ver') {
                // Version
                if (isset($matches['version'])) {
                  $data[$metatype] = $matches['version'];

                  if (isset($def['transform'])) {
                    $data[$metatype] = string_transform($data[$metatype], $def['transform']);
                  }
                }
              } elseif (isset($def[$metatype])) {
                // all other metatypes
                $data[$metatype] = $def[$metatype];
              }
            }

            // store package index for faster polling
            if (!isset($attribs[$attrib_key]) || $attribs[$attrib_key] != $index) {
              set_entity_attrib('device', $device['device_id'], 'os_package_index', $index);
            }
            break 2;
          }
        }

      }
    }
  }

  return $data;
}

/* OBSOLETE, BUT STILL USED FUNCTIONS */

// CLEANME remove when all function calls will be deleted
function get_dev_attribs($device_id)
{
  // Call to new function
  return get_entity_attribs('device', $device_id);
}

// CLEANME remove when all function calls will be deleted
function set_dev_attrib($device, $attrib_type, $attrib_value)
{
  // Call to new function
  return set_entity_attrib('device', $device, $attrib_type, $attrib_value);
}

// CLEANME remove when all function calls will be deleted
function del_dev_attrib($device, $attrib_type)
{
  // Call to new function
  return del_entity_attrib('device', $device, $attrib_type);
}

/** OLD DEPRECATED FUNCTIONS */

/* CLEANME Unused, useless function
function gethostosbyid($id)
{
  global $cache;

  if (isset($cache['devices']['id'][$id]['os']))
  {
    $os = $cache['devices']['id'][$id]['os'];
  } else {
    $os = dbFetchCell("SELECT `os` FROM `devices` WHERE `device_id` = ?", array($id));
  }

  return $os;
}
*/

/* CLEANME Unused, useless function
function get_device_hostname_by_device_id($id)
{
  global $cache;

  if (isset($cache['devices']['id'][$id]['hostname']))
  {
    $hostname = $cache['devices']['id'][$id]['hostname'];
  } else {
    $hostname = dbFetchCell("SELECT `hostname` FROM `devices` WHERE `device_id` = ?", array($id));
  }

  return $hostname;
}
*/

// EOF
