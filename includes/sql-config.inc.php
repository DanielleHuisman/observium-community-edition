<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2013, Observium Developers - http://www.observium.org
 *
 * @package    observium
 * @subpackage config
 * @author     Tom Laermans <sid3windr@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Load configuration file into $config variable
if (!isset($config['install_dir']))
{
  $base_dir = realpath(dirname(__FILE__) . '/..');
} else {
  $base_dir = $config['install_dir'];
}

// Clear config array, we're starting with a clean state
$config = array();

require($base_dir."/includes/defaults.inc.php");
require($base_dir."/config.php");

// Base dir, if it's not set in config
if (!isset($config['install_dir']))
{
  $config['install_dir'] = $base_dir;
}

// Include necessary supporting files
require_once($config['install_dir'] . "/includes/functions.inc.php");

// Die if exec/proc_open functions disabled in php.ini. This install not functional for run Observium.
if (!is_exec_available()) { die; }

// Load definitions
require($config['install_dir'] . "/includes/definitions.inc.php");

// CLEANME, this already included in functions.inc.php
// Common functions, for is_ssl and print_warning/print_error
//include_once($config['install_dir'].'/includes/common.inc.php');

// Connect to database
if (!$GLOBALS[OBS_DB_LINK])
{
  if (defined('OBS_DB_SKIP') && OBS_DB_SKIP === TRUE)
  {
    print_warning("WARNING: In PHP Unit tests we can skip DB connect. But if you test db functions, check your configs.");
  } else {
    print_error("DB Error " . dbErrorNo() . ": " . dbError());
    die; // Die if not PHP Unit tests
  }
}
else if (!get_db_version() && !(isset($options['u']) || isset($options['V'])))
{
  if (!dbQuery('SELECT 1 FROM `devices` LIMIT 1;'))
  {
    // DB schema not installed, install first
    print_error("DB schema not installed, first install it.");
    die;
  }
} else {
  // Disable STRICT mode for DB session (we not fully support them)
  $db_modes = explode(',', dbFetchCell("SELECT @@SESSION.sql_mode;"));
  $db_modes_exclude = array('STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'ONLY_FULL_GROUP_BY');
  $db_modes_update  = array();
  foreach ($db_modes_exclude as $db_mode_exclude)
  {
    if (in_array($db_mode_exclude, $db_modes))
    {
      $db_modes_update[] = $db_mode_exclude;
    }
  }
  if (count($db_modes_update))
  {
    $db_modes = array_diff($db_modes, $db_modes_update);
    dbQuery('SET SESSION `sql_mode` = ?', array(implode(',', $db_modes)));
    print_debug('DB mode(s) disabled: '.implode(', ', $db_modes_update));
  }
  //register_shutdown_function('dbClose');
  // Maybe better in another place, but at least here it runs always; keep track of what svn revision we last saw, and eventlog the upgrade versions.
  // We have versions here from the includes above, and we just connected to the DB.
  $rev_old = @get_obs_attrib('current_rev');
  if (($rev_old < OBSERVIUM_REV || !is_numeric($rev_old)) && OBSERVIUM_VERSION_LONG != '0.SVN.ERROR')
  {
    // Ignore changes to not correctly detected version (0.SVN.ERROR)
    // Version update detected, log it
    $version_old = @get_obs_attrib('current_version');
    log_event("Observium updated: $version_old -> " . OBSERVIUM_VERSION_LONG, NULL, NULL, NULL, 5);

    set_obs_attrib('current_rev',     OBSERVIUM_REV);
    set_obs_attrib('current_version', OBSERVIUM_VERSION_LONG);
  }

  // Clean
  unset($db_modes, $db_modes_exclude, $db_mode_exclude, $db_modes_update, $rev_old);
}

// Load SQL configuration into $config variable
load_sqlconfig($config);

/**
 * OHMYGOD, this is very dangerous, because this is secure hole for override static definitions,
 * now already defined configs skipped in load_sqlconfig().
 *
// Reload configuration file into $config variable to make sure it overrules all SQL-supplied and default settings
// Not the greatest hack, but array_merge was unfit for the job, unfortunately.
include($config['install_dir']."/config.php");

*/

// Init RRDcached

if (isset($config['rrdcached']) && !preg_match('!^\s*(unix:)?/!i', $config['rrdcached']))
{
  // RRD files located on remote server
  define('OBS_RRD_NOLOCAL', TRUE);
} else {
  define('OBS_RRD_NOLOCAL', FALSE);
}

// Init StatsD

if ($config['statsd']['enable'] && class_exists('StatsD'))
{
  //$statsd = new StatsD(array('host' => $config['statsd']['host'], 'port' => $config['statsd']['port']));
  StatsD::$config = array(
    'host' => $config['statsd']['host'],
    'port' => $config['statsd']['port'],
  );
}


// Escape all cmd paths
//FIXME, move all cmd config into $config['cmd'][path]
$cmds = array('rrdtool', 'fping', 'fping6', 'snmpwalk', 'snmpget',
              'snmpbulkget', 'snmpbulkwalk', 'snmptranslate', 'whois',
              'mtr', 'nmap', 'ipmitool', 'virsh', 'dot', 'unflatten',
              'neato', 'sfdp', 'svn', 'git', 'wmic', 'file', 'wc',
              'sudo', 'tail', 'cut', 'tr',
             );

foreach ($cmds as $path)
{
  if (isset($config[$path])) { $config[$path] = escapeshellcmd($config[$path]); }
}
unset($cmds, $path);

// Disable nonexistant features in CE, do not try to turn on, it will not give effect
if (OBSERVIUM_EDITION == 'community')
{
  $config['enable_billing'] = 0;
  $config['api']['enabled'] = 0;

  // Disabled (not exist) modules
  unset($config['poller_modules']['oids'],
        $config['poller_modules']['loadbalancer'],
        $config['poller_modules']['aruba-controller'],
        $config['poller_modules']['netscaler-vsvr']);
}

// Self hostname for observium server
// FIXME, used only in smokeping integration
if (!isset($config['own_hostname']))
{
  $config['own_hostname'] = get_localhost();
}

// Set web_url/base_url setting to default, add trailing slash if not present

if (!isset($config['web_url']))
{
  $config['web_url'] = isset($config['base_url']) ? $config['base_url'] : 'http://' . get_localhost();
}
if (substr($config['web_url'], -1) != '/') { $config['web_url'] .= '/'; }

if (is_cli() && isset($config['external_url']))
{
  // Overwrite the autogenerated base_url with external_url when we're on CLI.
  $config['base_url'] = $config['external_url'];
}
else if (!isset($config['base_url']))
{
  if (isset($_SERVER["SERVER_NAME"]) && isset($_SERVER["SERVER_PORT"]))
  {
    if (strpos($_SERVER["SERVER_NAME"] , ":"))
    {
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
} else {
  // Add / to base_url if not there
  if (substr($config['base_url'], -1) != '/') { $config['base_url'] .= '/'; }
}

// If we're on SSL, let's properly detect it
if (is_ssl())
{
  $config['base_url'] = preg_replace('/^http:/','https:', $config['base_url']);
}

// Old variable backwards compatibility
if (isset($config['rancid_configs']) && !is_array($config['rancid_configs'])) { $config['rancid_configs'] = array($config['rancid_configs']); }
if (isset($config['auth_ldap_group']) && !is_array($config['auth_ldap_group'])) { $config['auth_ldap_group'] = array($config['auth_ldap_group']); }
if (isset($config['auth_ldap_kerberized']) && $config['auth_ldap_kerberized'] && $config['auth_mechanism'] == 'ldap') { $config['auth']['remote_user'] = TRUE; }

//print_vars($config['location_map']);
if (isset($config['location_map']))
{
  $config['location']['map'] = array_merge((array)$config['location_map'], (array)$config['location']['map']);
  unset($config['location_map']);
}
//print_vars($config['location']['map']);
//print_vars($config['location']['map_regexp']);
if ($config['location']['menu']['type'] == 'geocoded')
{
  if (isset($config['geocoding']['enable']) && !$config['geocoding']['enable'])            { $config['location']['menu']['type'] = 'plain'; }
  else if (isset($config['location_menu_geocoded']) && !$config['location_menu_geocoded']) { $config['location']['menu']['type'] = 'plain'; }
}

// Security fallback check
if (isset($config['auth']['remote_user']) && $config['auth']['remote_user'] && !isset($_SERVER['REMOTE_USER']))
{
  // Disable remote_user, Apache did not pass a username! Misconfigured?
  // FIXME log this somewhere?
  $config['auth']['remote_user'] = FALSE;
}

// Database currently stores v6 networks non-compressed, check for any compressed subnet and expand them
foreach ($config['ignore_common_subnet'] as $i => $content)
{
  if (strstr($content,':') !== FALSE) { $config['ignore_common_subnet'][$i] = Net_IPv6::uncompress($content); }
}

unset($i); unset($content);

if (isset($config['rrdgraph_def_text']))
{
  $config['rrdgraph_def_text'] = str_replace("  ", " ", $config['rrdgraph_def_text']);
  $config['rrd_opts_array'] = explode(" ", trim($config['rrdgraph_def_text']));
}

// Disable phpFastCache 5.x for PHP less than 5.5, since it unsupported
if ($config['cache']['enable'] && version_compare(PHP_VERSION, '5.5.0', '<'))
{
  $config['cache']['enable'] = FALSE;
}

// Generate poller id if we're a partitioned poller and we don't yet have one.
/*
if (isset($config['poller_id']))
{
  // Use already configured poller_id
}
else
*/
if (isset($config['poller_name']))
{
  $poller_id = dbFetchCell("SELECT `poller_id` FROM `pollers` WHERE `poller_name` = ?", array($GLOBALS['config']['poller_name']));

  if (is_numeric($poller_id))
  {
    $config['poller_id'] = $poller_id;
  } else {
    // This poller not exist, create it
    // I not sure that this should be in global sql-config include @mike
    $config['poller_id'] = dbInsert('pollers', array('poller_name' => $config['poller_name']));
  }
  unset($poller_id);

} else {
  // Default poller
  $config['poller_id'] = 0;

}

// EOF
