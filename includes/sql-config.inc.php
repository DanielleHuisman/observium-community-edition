<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage config
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Set scriptname and process name
$scriptname = isset($_SERVER["SCRIPT_FILENAME"]) ? basename($_SERVER["SCRIPT_FILENAME"]) : basename($argv[0]);
define('OBS_SCRIPT_NAME', $scriptname);
define('OBS_PROCESS_NAME', basename($scriptname, '.php'));

// Load configuration file into $config variable
$base_dir = isset($config['install_dir']) ? $config['install_dir'] : dirname(__DIR__);

// Clear config array, we're starting with a clean state
$config = [];

require($base_dir."/includes/defaults.inc.php");
require($base_dir."/config.php");

// Base dir, if it's not set in config
if (!isset($config['install_dir'])) {
  $config['install_dir'] = $base_dir;
}

// Include necessary supporting files
require_once($config['install_dir'] . "/includes/common.inc.php");

// Die if exec/proc_open functions disabled in php.ini. This configuration is not capable of running Observium.
if (!is_exec_available()) { die; }

if (PHP_VERSION_ID < 80100) {
  try {
    //date_create('now');
    new DateTime('now');
  } catch(Exception $e) {
    if (strpos($e->getMessage(), 'date.timezone') !== FALSE) {
      // Fix incorrect timezone setting and prevent fatal exception in DateTime
      ini_set('date.timezone', date_default_timezone_get());
    }
    //echo $e->getMessage();
    //var_dump($e);
  }
}

// Load definitions
$def_start = microtime(TRUE);
$def_start_memory = memory_get_usage();
require($config['install_dir'] . "/includes/definitions.inc.php");
print_debug("DEFINITIONS loaded by: " . format_number_short(microtime(TRUE) - $def_start, 6) . " ms\n");
print_debug("DEFINITIONS in memory: " . formatStorage(memory_get_usage() - $def_start_memory) . "\n");
unset($def_start, $def_start_memory);

// Include more necessary supporting files
require_once($config['install_dir'] . "/includes/functions.inc.php");

// Connect to database
// if (!defined('OBS_DB_SKIP') || OBS_DB_SKIP !== TRUE) {
//   $GLOBALS[OBS_DB_LINK] = dbOpen($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
// }

if (!isset($GLOBALS[OBS_DB_LINK]) || !$GLOBALS[OBS_DB_LINK]) {
  if (defined('OBS_DB_SKIP') && OBS_DB_SKIP === TRUE) {
    print_warning("WARNING: In PHP Unit tests we can skip DB connect. But if you test db functions, check your configs.");
  } else {
    print_message("%yDB not connected, please check database connection configuration.%r\nDB Error " . dbErrorNo() . ": " . dbError() . "%n", 'color');
    http_response_code(500);
    die; // Die if not PHP Unit tests
  }
} elseif (!(isset($options['u']) || isset($options['V'])) && !get_db_version()) {
  if (!dbQuery('SELECT 1 FROM `devices` LIMIT 1;')) {
    // DB schema not installed, install first
    print_error("DB schema not installed, first install it.");
    die;
  }
} else {
  // Disable STRICT mode for DB session (we not fully support them)
  $db_modes = explode(',', dbFetchCell("SELECT @@SESSION.sql_mode;"));
  //SQL WARNINGS[
  //  3135: 'NO_ZERO_DATE', 'NO_ZERO_IN_DATE' and 'ERROR_FOR_DIVISION_BY_ZERO' sql modes should be used with strict mode. They will be merged with strict mode in a future release.
  //]
  $db_modes_exclude = [ 'STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'ONLY_FULL_GROUP_BY',
                        'NO_ZERO_DATE', 'NO_ZERO_IN_DATE', 'ERROR_FOR_DIVISION_BY_ZERO' ];
  $db_modes_update  = [];
  foreach ($db_modes_exclude as $db_mode_exclude) {
    if (in_array($db_mode_exclude, $db_modes)) {
      $db_modes_update[] = $db_mode_exclude;
    }
  }
  if (count($db_modes_update)) {
    $db_modes = array_diff($db_modes, $db_modes_update);
    dbQuery('SET SESSION `sql_mode` = ?', [ implode(',', $db_modes) ]);
    print_debug('DB mode(s) disabled: '.implode(', ', $db_modes_update));
  }
  //register_shutdown_function('dbClose');

  // Sync DB timezone
  $timezone = get_timezone();
  if ($timezone['diff'] !== 0) {
    print_debug("DB timezone different from php timezone. Syncing..");
    dbSetVariable('time_zone', $timezone['php']);
    // Refresh timezone
    //get_timezone(TRUE);
  }

  // Reset Opcache in WUI
  if (OBS_PROCESS_NAME === 'index' && @get_obs_attrib('opcache_reset')) {
    if (function_exists('opcache_reset') && opcache_reset()) {
      print_debug("PHP Opcache WUI was reset.");
    }
    del_obs_attrib('opcache_reset');
  }
  //else {
    //print_vars(OBS_PROCESS_NAME);
  //}

  // Clean
  unset($db_modes, $db_modes_exclude, $db_mode_exclude, $db_modes_update, $rev_old);
}

// Load SQL configuration into $config variable
load_sqlconfig($config);

// never store this option(s) in memory! Use get_defined_settings($key)
foreach ($config['hide_config'] as $opt) {
  if (array_get_nested($config, $opt)) { unset($config[$opt]); }
}

/**
 * OHMYGOD, this is very dangerous, because this is secure hole for override static definitions,
 * now already defined configs skipped in load_sqlconfig().
 *
// Reload configuration file into $config variable to make sure it overrules all SQL-supplied and default settings
// Not the greatest hack, but array_merge was unfit for the job, unfortunately.
include($config['install_dir']."/config.php");

*/

// Init RRDcached

if (isset($config['rrdcached']) && !preg_match('!^\s*(unix:)?/!i', $config['rrdcached'])) {
  // RRD files located on remote server
  define('OBS_RRD_NOLOCAL', TRUE);
} else {
  define('OBS_RRD_NOLOCAL', FALSE);
}

// Init StatsD

if ($config['statsd']['enable'] && class_exists('StatsD')) {
  //$statsd = new StatsD(array('host' => $config['statsd']['host'], 'port' => $config['statsd']['port']));
  StatsD::$config = [
    'host' => $config['statsd']['host'],
    'port' => $config['statsd']['port'],
  ];
}


// Escape all cmd paths
//FIXME, move all cmd config into $config['cmd'][path]
$cmds = [ 'rrdtool', 'fping', 'fping6', 'snmpwalk', 'snmpget',
          'snmpbulkget', 'snmpbulkwalk', 'snmptranslate', 'whois',
          'mtr', 'nmap', 'ipmitool', 'virsh', 'dot', 'unflatten',
          'neato', 'sfdp', 'svn', 'git', 'wmic', 'file', 'wc',
          'sudo', 'tail', 'cut', 'tr' ];

foreach ($cmds as $path) {
  if (isset($config[$path])) { $config[$path] = escapeshellcmd($config[$path]); }
}
unset($cmds, $path);

// Fping 4+ use -6 argument
if (!is_executable($config['fping6']) && version_compare(get_versions('fping'), '4.0', '>=')) {
  $config['fping6'] = $config['fping'] . ' -6';
}

// Disable nonexistent features in CE, do not try to turn on, it will not give effect
if (OBSERVIUM_EDITION === 'community') {
  $config['enable_billing'] = 0;
  $config['api']['enabled'] = 0;
  $config['web_theme_default'] = 'light';

  // Disabled (not exist) modules
  unset($config['poller_modules']['oids'],
        $config['poller_modules']['loadbalancer'],
        $config['poller_modules']['aruba-controller'],
        $config['poller_modules']['netscaler-vsvr'],
        $config['poller_name']);
}

// Self hostname for observium server
/* FIXME, used only in smokeping and ipmi integration
if (!isset($config['own_hostname'])) {
  $config['own_hostname'] = get_localhost();
}
*/

if (is_cli() && isset($config['external_url'])) {
  // Overwrite the autogenerated base_url with external_url when we're on CLI.
  $config['base_url'] = $config['external_url'];
} elseif (!isset($config['base_url'])) {
  if (isset($_SERVER["SERVER_NAME"], $_SERVER["SERVER_PORT"])) {
    if (str_contains($_SERVER["SERVER_NAME"] , ":") && !str_contains($_SERVER["SERVER_NAME"] , "[")) {
      // Literal IPv6
      $config['base_url']  = "http://[" . $_SERVER["SERVER_NAME"] ."]" . ($_SERVER["SERVER_PORT"] != 80 ? ":".$_SERVER["SERVER_PORT"] : '') ."/";
    } else {
      $config['base_url']  = "http://" . $_SERVER["SERVER_NAME"] . ($_SERVER["SERVER_PORT"] != 80 ? ":".$_SERVER["SERVER_PORT"] : '') ."/";
    }
  }
  //} else {
  //  // Try to detect base_url in cli based on hostname
  //  /// FIXME. Here require get_localhost(), but this function loaded after definitions
  //  //$config['base_url'] = "http://" . get_localhost() . "/";
  //}
} elseif (!str_ends($config['base_url'], '/')) {
  // Add / to base_url if not there
  $config['base_url'] .= '/';
}

// If we're on SSL, let's properly detect it
if (is_ssl()) {
  //print_vars($_SERVER["HTTP_HOST"]);
  //print_vars($_SERVER["SERVER_NAME"]);
  $config['base_url'] = preg_replace('/^http:/', 'https:', $config['base_url']);
  $config['base_url'] = preg_replace('!^(https://[^/]+?):443(/)!', '$1$2', $config['base_url']);
}

// Old variable backwards compatibility
if (isset($config['rancid_configs']) && !is_array($config['rancid_configs'])) {
  $config['rancid_configs'] = [ $config['rancid_configs'] ];
}
if (isset($config['auth_ldap_group']) && !is_array($config['auth_ldap_group'])) {
  $config['auth_ldap_group'] = [ $config['auth_ldap_group'] ];
}
if (isset($config['auth_ldap_kerberized']) && $config['auth_ldap_kerberized'] && $config['auth_mechanism'] === 'ldap') {
  $config['auth']['remote_user'] = TRUE;
}

// Reset possible old geocode api
$config['geocoding']['api'] = strtolower(trim($config['geocoding']['api']));
if (!isset($config['geo_api'][$config['geocoding']['api']]) ||
    !$config['geo_api'][$config['geocoding']['api']]['enable']) {
  $config['geocoding']['api'] = 'geocodefarm';
}

//print_vars($config['location_map']);
if (isset($config['location_map'])) {
  $config['location']['map'] = array_merge((array)$config['location_map'], (array)$config['location']['map']);
  unset($config['location_map']);
}
//print_vars($config['location']['map']);
//print_vars($config['location']['map_regexp']);
if ($config['location']['menu']['type'] === 'geocoded') {
  if (isset($config['geocoding']['enable']) && !$config['geocoding']['enable']) {
    $config['location']['menu']['type'] = 'plain';
  } elseif (isset($config['location_menu_geocoded']) && !$config['location_menu_geocoded']) {
    $config['location']['menu']['type'] = 'plain';
  }
}

// Compat xdp ignore options
if (isset($config['bad_xdp'])) {
  $config['xdp']['ignore_hostname'] = array_merge((array)$config['xdp']['ignore_hostname'], (array)$config['bad_xdp']);
}
if (isset($config['bad_xdp_regexp'])) {
  $config['xdp']['ignore_hostname_regex'] = array_merge((array)$config['xdp']['ignore_hostname_regex'], (array)$config['bad_xdp_regexp']);
}
if (isset($config['bad_xdp_platform'])) {
  $config['xdp']['ignore_platform'] = array_merge((array)$config['xdp']['ignore_platform'], (array)$config['bad_xdp_platform']);
}

// Compat for adama ;)
if (isset($config['sensors_limits_events'])) {
  $config['sensors']['limits_events'] = $config['sensors_limits_events'];
}

// Security fallback check
if (isset($config['auth']['remote_user']) && $config['auth']['remote_user'] && !isset($_SERVER['REMOTE_USER'])) {
  // Disable remote_user, Apache did not pass a username! Misconfigured?
  // FIXME log this somewhere?
  $config['auth']['remote_user'] = FALSE;
}

// Database currently stores v6 networks non-compressed, check for any compressed subnet and expand them
foreach ($config['ignore_common_subnet'] as $i => $content) {
  if (str_contains($content, ':')) {
    // NOTE. ipv6_networks have uncompressed but not fixed length
    $config['ignore_common_subnet'][$i] = ip_uncompress($content, FALSE);
  }
}

unset($i, $content);

// Set web_url/base_url setting to default, add trailing slash if not present

if (!isset($config['web_url'])) {
  $config['web_url'] = isset($config['base_url']) ? $config['base_url'] : 'http://' . get_localhost();
}
if (!str_ends($config['web_url'], '/')) { $config['web_url'] .= '/'; }

// Generate poller id if we're a partitioned poller and we don't yet have one.
if (OBSERVIUM_EDITION === 'community') {
  // Distributed pollers not available on community edition
  $config['poller_id'] = 0;

  // Not possible Distributed pollers in CE
  define('OBS_DISTRIBUTED', FALSE);
} elseif (isset($options['p']) && is_intnum($options['p']) && $options['p'] >= 0) {
  // Poller id passed in poller wrapper
  $config['poller_id'] = (int)$options['p'];
  print_debug("Poller ID (".$config['poller_id'].") passed from command line arguments.");
  /* not sure when required
  if ($options['p'] > 0 && !dbExist('pollers', '`poller_id` = ?', [ $options['p'] ])) {
    // This poller not exist, create it
    // I not sure that this should be in global sql-config include @mike
    $poller = [
      'poller_id'   => $options['p'],
      'poller_name' => $config['poller_name'],
      'host_id'     => get_local_id(),
      'host_uname'  => php_uname()
    ];
    $config['poller_id'] = (int) dbInsert('pollers', $poller);
    unset($poller);
  } else {
    $config['poller_id'] = (int) $options['p'];
  }
  */

  // Definitely distributed
  define('OBS_DISTRIBUTED', TRUE);
} elseif (isset($config['poller_name'])) {
  $poller_id = dbFetchCell("SELECT `poller_id` FROM `pollers` WHERE `poller_name` = ?", [ $GLOBALS['config']['poller_name'] ]);

  if (is_numeric($poller_id)) {
    $config['poller_id'] = (int) $poller_id;
  } else {
    // This poller not exist, create it
    // I not sure that this should be in global sql-config include @mike
    $poller = [
      'poller_name' => $config['poller_name'],
      'host_id'     => get_local_id(),
      'host_uname'  => php_uname()
    ];
    $config['poller_id'] = (int) dbInsert('pollers', $poller);
  }
  unset($poller_id, $poller);

  // Definitely distributed
  define('OBS_DISTRIBUTED', TRUE);
} elseif (isset($config['poller_id']) && is_intnum($config['poller_id']) && $config['poller_id'] > 0) {
  // Vice versa by poller_id

  if ($poller_name = dbFetchCell("SELECT `poller_name` FROM `pollers` WHERE `poller_id` = ?", [ $GLOBALS['config']['poller_id'] ])) {
    $config['poller_name'] = $poller_name;
  } else {
    // This poller not exist, create it
    // I not sure that this should be in global sql-config include @mike
    $poller = [
      'poller_id' => $config['poller_id'],
      'poller_name' => 'Poller '.$config['poller_id'],
      'host_id'     => get_local_id(),
      'host_uname'  => php_uname()
    ];
    dbInsert('pollers', $poller);
    unset($poller);
  }

  // Definitely distributed
  define('OBS_DISTRIBUTED', TRUE);
} elseif (isset($config['poller_by_host']) && $config['poller_by_host'] && get_local_id()) {
  // Associate poller by specific poller host_id
  $poller_id = dbFetchCell("SELECT `poller_id` FROM `pollers` WHERE `host_id` = ?", [ get_local_id() ]);

  if (is_numeric($poller_id)) {
    $config['poller_id'] = (int) $poller_id;
  } else {
    // This poller not exist, create it
    // I not sure that this should be in global sql-config include @mike
    $poller = [
      'poller_name' => 'Poller '.get_local_id(),
      'host_id'     => get_local_id(),
      'host_uname'  => php_uname()
    ];
    $config['poller_id'] = (int) dbInsert('pollers', $poller);
  }
  unset($poller_id, $poller);

  // Definitely distributed
  define('OBS_DISTRIBUTED', TRUE);
} else {
  // Default poller
  $config['poller_id'] = 0;

  // Detect distributed
  define('OBS_DISTRIBUTED', dbExist('pollers'));
}

// Maybe better in another place, but at least here it runs always; keep track of what svn revision we last saw, and eventlog the upgrade versions.
// We have versions here from the includes above, and we just connected to the DB.
if (OBS_PROCESS_NAME === 'discovery') {
  if ($config['poller_id'] > 0) {
    // Remote poller, different place for version/revision
    $version_updated = FALSE;
    if ($poller_version = dbFetchCell("SELECT `poller_version` FROM `pollers` WHERE `poller_id` = ?", [ $config['poller_id'] ])) {
      list($poller_version) = explode(' ', $poller_version); // remove train
      list(,, $rev_old) = explode('.', $poller_version);
      if (OBSERVIUM_VERSION_LONG !== 'Y.M.ERROR' && $poller_version !== OBSERVIUM_VERSION) {
        $poller = dbFetchRow('SELECT * FROM `pollers` WHERE `poller_id` = ?', [ $config['poller_id'] ]);
        // Prevent eventlog spamming on incorrect version detect
        log_event("Poller (".$config['poller_id'].': '.$poller['poller_name'].") updated: $poller_version -> " . OBSERVIUM_VERSION_LONG, NULL, NULL, NULL, 5);

        $poller = [
          'poller_version' => OBSERVIUM_VERSION_LONG,
          'host_id'        => get_local_id(),
          'host_uname'     => php_uname()
        ];
        dbUpdate($poller, 'pollers', '`poller_id` = ?', [ $config['poller_id'] ]);

        // Set reset opcache (need split cli/web, because has a separate opcode)
        if (function_exists('opcache_reset') && opcache_reset()) {
          print_debug("PHP Opcache CLI was reset.");
        }
      }
    } elseif (OBSERVIUM_VERSION_LONG !== 'Y.M.ERROR' && get_db_version() > 477) {
      // need update after db schema upgrade
      $poller = [
        'poller_version' => OBSERVIUM_VERSION_LONG,
        'host_id'        => get_local_id(),
        'host_uname'     => php_uname()
      ];
      dbUpdate($poller, 'pollers', '`poller_id` = ?', [ $config['poller_id'] ]);
    }
    unset($poller);
  } else {
    // Main poller/host
    $rev_old = @get_obs_attrib('current_rev');
    // Ignore changes to not correctly detected version (Y.M.ERROR)
    if (OBSERVIUM_VERSION_LONG !== 'Y.M.ERROR' && ($rev_old < OBSERVIUM_REV)) {
      $version_old = @get_obs_attrib('current_version');

      if ($version_old !== OBSERVIUM_VERSION_LONG) {
        // Prevent eventlog spamming on incorrect version detect
        log_event("Observium updated: $version_old -> " . OBSERVIUM_VERSION_LONG, NULL, NULL, NULL, 5);

        set_obs_attrib('current_rev', OBSERVIUM_REV);
        set_obs_attrib('current_version', OBSERVIUM_VERSION_LONG);

        // Set reset opcache (need split cli/web, because has a separate opcode)
        if (function_exists('opcache_reset') && opcache_reset()) {
          print_debug("PHP Opcache CLI was reset.");
        }
      }
    }
  }

  // Detect if version updated
  if ($version_updated) {
    // Ignore changes to not correctly detected version (Y.M.ERROR)
    // Version update detected, log it

    if ($version_old !== OBSERVIUM_VERSION_LONG) {
      // Prevent eventlog spamming on incorrect version detect
      log_event("Observium updated: $version_old -> " . OBSERVIUM_VERSION_LONG, NULL, NULL, NULL, 5);

      set_obs_attrib('current_rev', OBSERVIUM_REV);
      set_obs_attrib('current_version', OBSERVIUM_VERSION_LONG);

      // Set reset opcache (need split cli/web, because has a separate opcode)
      if (function_exists('opcache_reset') && opcache_reset()) {
        print_debug("PHP Opcache CLI was reset.");
      }
    }
  }
}

// EOF
