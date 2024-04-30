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

// Clear config array, we're starting with a clean state
$config = [];

//$config['install_dir'] = dirname(__DIR__, 1);
$config['install_dir'] = realpath(__DIR__ . '/..');

require($config['install_dir'] . "/includes/defaults.inc.php");
require($config['install_dir'] . "/config.php");

require_once($config['install_dir'] . "/includes/polyfill.inc.php");
require_once($config['install_dir'] . "/includes/autoloader.inc.php");
require_once($config['install_dir'] . "/includes/debugging.inc.php");

// Include necessary supporting files
require_once($config['install_dir'] . "/includes/common.inc.php");

// Die if exec/proc_open functions disabled in php.ini. This configuration is not capable of running Observium.
if (!is_exec_available()) {
    die;
}

if (PHP_VERSION_ID < 80100) {
    try {
        new DateTime('now');
    } catch (Exception $e) {
        if (strpos($e -> getMessage(), 'date.timezone') !== FALSE) {
            // Fix incorrect timezone setting and prevent fatal exception in DateTime
            ini_set('date.timezone', date_default_timezone_get());
        }
    }
}

require_once($config['install_dir'] . "/includes/constants.inc.php");
require($config['install_dir'] . "/includes/definitions.inc.php");

// Include DB functions
require_once($config['install_dir'] . "/includes/db.inc.php");

// Connect to database
if (OBS_DB_SKIP !== TRUE) {
    db_config();
    $GLOBALS[OBS_DB_LINK] = dbOpen($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
}

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
    $db_modes_exclude = ['STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'ONLY_FULL_GROUP_BY',
                         'NO_ZERO_DATE', 'NO_ZERO_IN_DATE', 'ERROR_FOR_DIVISION_BY_ZERO'];
    $db_modes_update  = [];
    foreach ($db_modes_exclude as $db_mode_exclude) {
        if (in_array($db_mode_exclude, $db_modes)) {
            $db_modes_update[] = $db_mode_exclude;
        }
    }
    if (count($db_modes_update)) {
        $db_modes = array_diff($db_modes, $db_modes_update);
        dbQuery('SET SESSION `sql_mode` = ?', [implode(',', $db_modes)]);
        print_debug('DB mode(s) disabled: ' . implode(', ', $db_modes_update));
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

/* Start fixate config options */

// never store this option(s) in memory! Use get_defined_settings($key)
foreach ($config['hide_config'] as $opt) {
    if (array_get_nested($config, $opt)) {
        unset($config[$opt]);
    }
}

// Escape all cmd paths
//FIXME, move all cmd config into $config['cmd'][path]
$cmds = [ 'rrdtool', 'fping', 'fping6', 'snmpwalk', 'snmpget', 'snmpbulkget', 'snmpbulkwalk', 'snmptranslate', 'whois',
          'mtr', 'nmap', 'ipmitool', 'virsh', 'dot', 'unflatten', 'neato', 'sfdp', 'svn', 'git', 'wmic', 'file', 'wc',
         'sudo', 'tail', 'cut', 'tr' ];

foreach ($cmds as $path) {
    if (isset($config[$path])) {
        $config[$path] = escapeshellcmd($config[$path]);
    }
}
unset($cmds);

// Fping 4+ use -6 argument
if (!is_executable($config['fping6']) && version_compare(get_versions('fping'), '4.0', '>=')) {
    $config['fping6'] = $config['fping'] . ' -6';
}

// Disable nonexistent features in CE, do not try to turn on, it will not give effect
if (OBSERVIUM_EDITION === 'community') {
    $config['enable_billing']    = 0;
    $config['api']['enabled']    = 0;
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

if (isset($config['external_url']) && is_cli()) {
    // Overwrite the autogenerated base_url with external_url when we're on CLI.
    $config['base_url'] = $config['external_url'];
} elseif (!isset($config['base_url'])) {
    if (isset($_SERVER["SERVER_NAME"], $_SERVER["SERVER_PORT"])) {
        if (str_contains($_SERVER["SERVER_NAME"], ":") && !str_contains($_SERVER["SERVER_NAME"], "[")) {
            // Literal IPv6
            $config['base_url'] = "http://[" . $_SERVER["SERVER_NAME"] . "]" . ($_SERVER["SERVER_PORT"] != 80 ? ":" . $_SERVER["SERVER_PORT"] : '') . "/";
        } else {
            $config['base_url'] = "http://" . $_SERVER["SERVER_NAME"] . ($_SERVER["SERVER_PORT"] != 80 ? ":" . $_SERVER["SERVER_PORT"] : '') . "/";
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
    $config['rancid_configs'] = [$config['rancid_configs']];
}
if (isset($config['auth_ldap_group']) && !is_array($config['auth_ldap_group'])) {
    $config['auth_ldap_group'] = [$config['auth_ldap_group']];
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
if (!empty($config['geocoding']['api_key']) && empty($config['geo_api'][$config['geocoding']['api']]['key'])) {
    // Deprecated option.
    //$config['geo_api'][$config['geocoding']['api']]['key'] = $config['geocoding']['api_key'];
    unset($config['geocoding']['api_key']);
}

//print_vars($config['location_map']);
//print_vars($config['location']['map']);
//print_vars($config['location']['map_regexp']);
if (isset($config['location_map'])) {
    $config['location']['map'] = array_merge((array)$config['location_map'], (array)$config['location']['map']);
    unset($config['location_map']);
}

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
if (isset($config['ignore_common_subnet']) && is_array($config['ignore_common_subnet'])) {
    foreach ($config['ignore_common_subnet'] as $i => $content) {
        if (str_contains($content, ':')) {
            // NOTE. ipv6_networks have uncompressed but not fixed length
            $config['ignore_common_subnet'][$i] = ip_uncompress($content, FALSE);
        }
    }
    unset($i);
}

// Set web_url/base_url setting to default, add trailing slash if not present

if (!isset($config['web_url'])) {
    $config['web_url'] = $config['base_url'] ?? 'http://' . get_localhost();
}
if (!str_ends($config['web_url'], '/')) {
    $config['web_url'] .= '/';
}

if (isset($config['web_enable_showtech'])) {
    $config['web_show_tech'] = $config['web_enable_showtech'];
}
if (isset($config['show_overview_tab'])) {
    $config['web_show_overview'] = $config['show_overview_tab'];
}
if (isset($config['show_locations'])) {
    $config['web_show_locations'] = $config['show_locations'];
}

/* End fixate config options */

// Generate poller id if we're a partitioned poller and we don't yet have one.
if (OBSERVIUM_EDITION === 'community') {
    // Distributed pollers not available on community edition
    $config['poller_id'] = 0;

    // Not possible Distributed pollers in CE
    define('OBS_DISTRIBUTED', FALSE);
} else {
    $config['poller_id'] = check_local_poller_id($options);
    // OBS_DISTRIBUTED defined in check_local_poller_id()
}

// Maybe better in another place, but at least here it runs always; keep track of what svn revision we last saw, and eventlog the upgrade versions.
// We have versions here from the includes above, and we just connected to the DB.
if (OBS_PROCESS_NAME === 'discovery') {
    if (OBS_DISTRIBUTED && $config['poller_id'] > 0) {
        // Remote poller, different place for version/revision
        $version_updated = check_local_poller_version();

    } else {
        // Master poller/host
        $rev_old = @get_obs_attrib('current_rev');
        // Ignore changes to not correctly detected version (Y.M.ERROR)
        if (OBSERVIUM_VERSION_LONG !== 'Y.M.ERROR' && ($rev_old < OBSERVIUM_REV)) {
            $version_old = @get_obs_attrib('current_version');

            if ($version_old !== OBSERVIUM_VERSION_LONG) {
                // Prevent eventlog spamming on an incorrect version detect
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
}

// Load html functions (after load sql config)
if (PHP_SAPI !== 'cli') {
    require_once($config['html_dir'] . "/includes/functions.inc.php");
}

// EOF
