<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage definitions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

/////////////////////////////////////////////////////////
//  NO CHANGES TO THIS FILE, IT IS NOT USER-EDITABLE   //
/////////////////////////////////////////////////////////
//               YES, THAT MEANS YOU                   //
/////////////////////////////////////////////////////////

// Always set locale to EN, because we use parsing strings
setlocale(LC_ALL, 'C');
putenv('LC_ALL=C');
// Use default charset UTF-8
ini_set('default_charset', 'UTF-8');

// Flags (mostly used in snmp and network functions, only 2^bit)
// Bits 0-3 common flags
define('OBS_QUOTES_STRIP',         1); // Strip ALL quotes from string
define('OBS_QUOTES_TRIM',          2); // Trim double/single quotes only from begin/end of string
define('OBS_ESCAPE',               4); // Escape strings or output
define('OBS_DECODE_UTF8',          8); // Decode ascii coded chars in string as correct UTF-8

// Bits 4-15 snmp flags
define('OBS_SNMP_NUMERIC',        16); // Use numeric OIDs  (-On)
define('OBS_SNMP_NUMERIC_INDEX',  32); // Use numeric index (-Ob)
define('OBS_SNMP_CONCAT',         64); // Concatenate multiline snmp variable (newline chars removed)
define('OBS_SNMP_ENUM',          128); // Don't enumerate SNMP values
define('OBS_SNMP_HEX',           256); // Force HEX output (-Ox) and disable use of DISPLAY-HINT information when assigning values (-Ih).
define('OBS_SNMP_TABLE',         512); // Force Program Like output (-OX)
define('OBS_SNMP_DISPLAY_HINT', 1024); // Disables the use of DISPLAY-HINT information when assigning values (-Ih). This would then require providing the raw value.
define('OBS_SNMP_TIMETICKS',    2048); // Force TimeTicks values as raw numbers (-Ot)
define('OBS_SNMP_ASCII',        4096); // Force all string values as ASCII strings
define('OBS_SNMP_NOINDEX',      8192); // Allow to walk tables without indexes, like snmpwalk_cache_bare_oid()
define('OBS_SNMP_NOINCREASE',  16384); // Do not check returned OIDs are increasing in snmpwalk (-Cc)
define('OBS_SNMP_INDEX_PARTS', 32768); // Use this to split index parts by arrow (->), actually for strings in index part and/or for OBS_SNMP_TABLE flag

define('OBS_SNMP_ALL',               OBS_QUOTES_TRIM | OBS_QUOTES_STRIP);    // Set of common snmp options
define('OBS_SNMP_ALL_MULTILINE',     OBS_QUOTES_TRIM | OBS_SNMP_CONCAT);     // Set of common snmp options with concatenate multiline snmp variable
define('OBS_SNMP_ALL_ASCII',         OBS_QUOTES_TRIM | OBS_SNMP_ASCII);      // Set of common snmp options with forcing string values as ASCII strings
define('OBS_SNMP_ALL_UTF8',          OBS_SNMP_ALL_ASCII | OBS_SNMP_HEX | OBS_DECODE_UTF8); // Set of common snmp options with forcing string values as UTF8 strings
define('OBS_SNMP_ALL_HEX',           OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX); // Set of common snmp options forcing HEX output
define('OBS_SNMP_ALL_ENUM',          OBS_SNMP_ALL | OBS_SNMP_ENUM);          // Set of common snmp options without enumerating values
define('OBS_SNMP_ALL_NUMERIC',       OBS_SNMP_ALL | OBS_SNMP_NUMERIC);       // Set of common snmp options with numeric OIDs
define('OBS_SNMP_ALL_NUMERIC_INDEX', OBS_SNMP_ALL | OBS_SNMP_NUMERIC_INDEX); // Set of common snmp options with numeric indexes
define('OBS_SNMP_ALL_NOINDEX',       OBS_SNMP_ALL | OBS_SNMP_NOINDEX);       // Set of common snmp options with ability collect table without indexes
define('OBS_SNMP_ALL_TABLE',         OBS_QUOTES_TRIM | OBS_SNMP_TABLE);      // Set of common snmp options with Program Like (help for MAC parse in indexes)
define('OBS_SNMP_ALL_TIMETICKS',     OBS_SNMP_ALL | OBS_SNMP_TIMETICKS);     // Set of common snmp options with TimeTicks as raw numbers

// Bits 16-19 network flags
define('OBS_DNS_A',            65536); // Use only IPv4 dns queries
define('OBS_DNS_AAAA',        131072); // Use only IPv6 dns queries
define('OBS_DNS_ALL',          OBS_DNS_A | OBS_DNS_AAAA); // Use both IPv4/IPv6 dns queries
define('OBS_DNS_FIRST',       262144); // Flag for use in gethostbyname6()
define('OBS_PING_SKIP',       524288); // Skip device icmp ping checks

// Permission levels flags
define('OBS_PERMIT_ACCESS',        1); // Can access (ie: logon)
define('OBS_PERMIT_READ',          2); // Can read basic data
define('OBS_PERMIT_SECURE',        4); // Can read secure data
define('OBS_PERMIT_EDIT',          8); // Can edit
define('OBS_PERMIT_ADMIN',        16); // Can add/remove
define('OBS_PERMIT_ALL', OBS_PERMIT_ACCESS | OBS_PERMIT_READ | OBS_PERMIT_SECURE | OBS_PERMIT_EDIT | OBS_PERMIT_ADMIN); // Permit all

// Configuration view levels
define('OBS_CONFIG_BASIC',          1); // 0001: Basic view, 0001
define('OBS_CONFIG_ADVANCED',       3); // 0011: Advanced view, includes basic
define('OBS_CONFIG_EXPERT',         7); // 0111: Expert view, includes advanced and basic

// Common regex patterns
define('OBS_PATTERN_START', '%(?:^|[\s\"\(=])');       // Beginning of any pattern, matched string can start from newline, space, double quote, opening parenthesis, equal sign
define('OBS_PATTERN_END',   '(?:[\s\"\),]|[\:\.]\ |\.?$)%i'); // End of any pattern, matched string can ended with endline, space, double quote, closing parenthesis, comma, dot
define('OBS_PATTERN_END_U', OBS_PATTERN_END . 'u');    // ++Unicode

// IPv4 string in group \1 or 'ipv4'
define('OBS_PATTERN_IPV4',      '(?<ipv4>(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9]))');
define('OBS_PATTERN_IPV4_FULL', OBS_PATTERN_START . OBS_PATTERN_IPV4 . OBS_PATTERN_END);
// IPv4 netmask string in group \1 or 'ipv4_mask'
define('OBS_PATTERN_IPV4_MASK',  '(?<ipv4_mask>(?:0|128|192|224|240|248|252|254)\.0\.0\.0|255\.(?:0|128|192|224|240|248|252|254)\.0\.0|255\.255\.(?:0|128|192|224|240|248|252|254)\.0|255\.255\.255\.(?:0|128|192|224|240|248|252|254|255))');
// IPv4 inverse netmask string in group \1 or 'ipv4_inverse_mask'
define('OBS_PATTERN_IPV4_INVERSE_MASK', '(?<ipv4_inverse_mask>(?:255|127|63|31|15|7|3|1|0)\.255\.255\.255|0\.(?:255|127|63|31|15|7|3|1|0)\.255\.255|0\.0\.(?:255|127|63|31|15|7|3|1|0)\.255|0\.0\.0\.(?:255|127|63|31|15|7|3|1|0))');
// IPv4 network string in group \1 or 'ipv4_network', additionally 'ipv4', 'ipv4_prefix' or 'ipv4_mask' or 'ipv4_inverse_mask'
define('OBS_PATTERN_IPV4_NET',  '(?<ipv4_network>' . OBS_PATTERN_IPV4 . '\/(?:(?<ipv4_prefix>3[0-2]|[1-2][0-9]|[0-9])|' . OBS_PATTERN_IPV4_MASK . '|' . OBS_PATTERN_IPV4_INVERSE_MASK . '))');
define('OBS_PATTERN_IPV4_NET_FULL', OBS_PATTERN_START . OBS_PATTERN_IPV4_NET . OBS_PATTERN_END);

// IPv6 string in group \1 or 'ipv6'
define('OBS_PATTERN_IPV6',      '(?<ipv6>(?:(?:(?:[a-f\d]{1,4}:){5}[a-f\d]{1,4}|(?:[a-f\d]{1,4}:){4}:[a-f\d]{1,4}|(?:[a-f\d]{1,4}:){3}(?::[a-f\d]{1,4}){1,2}|(?:[a-f\d]{1,4}:){2}(?::[a-f\d]{1,4}){1,3}|[a-f\d]{1,4}:(?::[a-f\d]{1,4}){1,4}|(?:[a-f\d]{1,4}:){1,5}|:(?::[a-f\d]{1,4}){1,5}|:):(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])|(?:[a-f\d]{1,4}:){7}[a-f\d]{1,4}|(?:[a-f\d]{1,4}:){6}:[a-f\d]{1,4}|(?:[a-f\d]{1,4}:){5}(?::[a-f\d]{1,4}){1,2}|(?:[a-f\d]{1,4}:){4}(?::[a-f\d]{1,4}){1,3}|(?:[a-f\d]{1,4}:){3}(?::[a-f\d]{1,4}){1,4}|(?:[a-f\d]{1,4}:){2}(?::[a-f\d]{1,4}){1,5}|[a-f\d]{1,4}:(?::[a-f\d]{1,4}){1,6}|(?:[a-f\d]{1,4}:){1,7}:|:(?::[a-f\d]{1,4}){1,7}|::))');
define('OBS_PATTERN_IPV6_FULL', OBS_PATTERN_START . OBS_PATTERN_IPV6 . OBS_PATTERN_END);
// IPv6 network string in group \1 or 'ipv6_network', additionally 'ipv6', 'ipv6_prefix'
define('OBS_PATTERN_IPV6_NET',  '(?<ipv6_network>' . OBS_PATTERN_IPV6 . '\/(?<ipv6_prefix>12[0-8]|1[0-1][0-9]|[0-9]{1,2}))');
define('OBS_PATTERN_IPV6_NET_FULL', OBS_PATTERN_START . OBS_PATTERN_IPV6_NET . OBS_PATTERN_END);

// IPv4 or IPv6 string in group \1 or 'ip'
define('OBS_PATTERN_IP',        '(?<ip>' . OBS_PATTERN_IPV4 . '|' . OBS_PATTERN_IPV6 . ')');
define('OBS_PATTERN_IP_FULL',   OBS_PATTERN_START . OBS_PATTERN_IP . OBS_PATTERN_END);
// IPv4 or IPv6 network string in group \1 or 'ip_network'
define('OBS_PATTERN_IP_NET',      '(?<ip_network>' . OBS_PATTERN_IPV4_NET . '|' . OBS_PATTERN_IPV6_NET . ')');
define('OBS_PATTERN_IP_NET_FULL', OBS_PATTERN_START . OBS_PATTERN_IP_NET . OBS_PATTERN_END);

// MAC string in group \1 or 'mac'
define('OBS_PATTERN_MAC',       '(?<mac>(?:[a-f\d]{1,2}(?:\:[a-f\d]{1,2}){5}|[a-f\d]{2}(?:\-[a-f\d]{2}){5}|[a-f\d]{2}(?:\ [a-f\d]{2}){5}|[a-f\d]{4}(?:\.[a-f\d]{4}){2}|(?:0x)?[a-f\d]{12}))');
define('OBS_PATTERN_MAC_FULL',  OBS_PATTERN_START . OBS_PATTERN_MAC . OBS_PATTERN_END);

// FQDN string in group \1 or 'domain'
//define('OBS_PATTERN_FQDN',      '(?<domain>(?:(?:(?:xn--)?[a-z0-9_]+(?:\-[a-z0-9_]+)*\.)+(?:[a-z]{2,}|xn--[a-z0-9]{4,}))|localhost)'); // Alternative, less correct
//define('OBS_PATTERN_FQDN',      '(?<domain>(?:(?:(?=[a-z0-9\-_]{1,63}\.)(?:xn--)?[a-z0-9_]+(?:\-[a-z0-9_]+)*\.)+(?:[a-z]{2,63}|xn--[a-z0-9]{4,}))|localhost)'); // Punicode, Non-unicode
define('OBS_PATTERN_FQDN',      '(?<domain>(?:(?:(?=[\p{L}\d\-_]{1,63}\.)(?:xn--)?[\p{L}\d_]+(?:\-[\p{L}\d_]+)*\.)+(?:[\p{L}]{2,63}|xn--[a-z\d]{4,}))|localhost)');
define('OBS_PATTERN_FQDN_FULL', OBS_PATTERN_START . OBS_PATTERN_FQDN . OBS_PATTERN_END_U);

// pattern for email only (without Name, ie: user@domain.name)
// Email string in group \1 or 'email', additional groups: 'user', 'domain'
define('OBS_PATTERN_EMAIL',     '(?<email>(?<user>[\p{L}\d\.\'_\%\+\-]{1,63}|\"[\p{L}\d\.\'_\%\+\-\ \\\\]{1,63}\")@' . OBS_PATTERN_FQDN . ')');
define('OBS_PATTERN_EMAIL_FULL', OBS_PATTERN_START . OBS_PATTERN_EMAIL . OBS_PATTERN_END_U);
// pattern for Full email with Name (ie: "My Name" <user@domain.name>), but name is optional
// Long Email string in group \1 or 'email_long', additional groups: 'name', 'email', 'user', 'domain'
define('OBS_PATTERN_EMAIL_LONG', '(?<email_long>(?<name>[\"\'][\p{L}\d\.\'_\%\+\-\ \\\\]+[\"\']|(?:[\p{L}\d\.\'_\%\+\-]+\ )*[\p{L}\d\.\'_\%\+\-]+)?\s*<' . OBS_PATTERN_EMAIL . '>)');
define('OBS_PATTERN_EMAIL_LONG_FULL', OBS_PATTERN_START . OBS_PATTERN_EMAIL_LONG . OBS_PATTERN_END_U);

//define('OBS_PATTERN_URL',       '(?<url>(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@|\d{1,3}(?:\.\d{1,3}){3}|(?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?(?:[^\s]*)?)');
//define('OBS_PATTERN_URL_FULL',  OBS_PATTERN_START . OBS_PATTERN_URL . OBS_PATTERN_END_U);

// SNMP HEX-STRING group \1 or 'hex'
define('OBS_PATTERN_SNMP_HEX',  '(?<hex>[a-f\d]{2}(\ +[a-f\d]{2})*)\ ?');
// SNMP NUMERIC OID group \1 or 'oid_num'
define('OBS_PATTERN_SNMP_OID_NUM',  '/^(?<oid_num>\.?(\d+(?:\.\d+)+))$/');

// patterns for validating kind of used data
define('OBS_PATTERN_ALPHA',    '/^[\w\.\-]+$/');
define('OBS_PATTERN_NOPRINT',  '/[^\p{L}\p{N}\p{P}\p{S} ]/u'); // Non-printable UTF8 chars
define('OBS_PATTERN_NOLATIN',  '/[^\p{Common}\p{Latin}]/u');   // Non Latin (UTF8?) chars
define('OBS_PATTERN_VAR_NAME', '/^\w[\w\s\.\-+]*(\[[\w\.\-+]*\])*$/');

// Json flags
define('OBS_JSON_BIGINT_AS_STRING', PHP_VERSION_ID >= 50400 && PHP_INT_SIZE > 4 && !(defined('JSON_C_VERSION'))); // Check if BIGINT supported
$json_encode = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
if (defined('JSON_PRESERVE_ZERO_FRACTION')) {
  $json_encode |= JSON_PRESERVE_ZERO_FRACTION;
}
$json_decode = OBS_JSON_BIGINT_AS_STRING ? JSON_BIGINT_AS_STRING : 0;
$json_decode |= JSON_UNESCAPED_UNICODE;
/* use fix_json_unicode()
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
  // Prevent invalid UTF8 broke whole json
  // available in php7.2+
  $json_decode |=  JSON_INVALID_UTF8_IGNORE; //JSON_INVALID_UTF8_SUBSTITUTE;
}
*/
define('OBS_JSON_ENCODE', $json_encode);
define('OBS_JSON_DECODE', $json_decode);
unset($json_encode, $json_decode);

// Detect encrypt module
if (PHP_VERSION_ID >= 70200 && extension_loaded('sodium')) {
  // Libsodium is part of php since 7.2
  define('OBS_ENCRYPT', TRUE);
  define('OBS_ENCRYPT_MODULE', 'sodium');
} elseif (extension_loaded('mcrypt')) {
  // Older php can use mcrypt (not supported since php 7.2)
  define('OBS_ENCRYPT', TRUE);
  define('OBS_ENCRYPT_MODULE', 'mcrypt');
} else {
  // No encrypt modules found
  define('OBS_ENCRYPT', FALSE);
}
//var_dump(OBS_ENCRYPT);

// Always use "enhanced algorithm" for rounding float numbers in JSON/serialize
ini_set('serialize_precision', -1);

// Use more accurate math
if (function_exists('bcadd')) {
  // BC Math
  define('OBS_MATH', 'bc');
} elseif (defined('GMP_VERSION')) {
  // GMP (gmp have troubles with convert float numbers)
  define('OBS_MATH', 'gmp');
} else {
  // Fallback to php math
  define('OBS_MATH', 'php');
}
//var_dump(OBS_MATH);

// Minimum supported versions
// NOTE. Minimum supported versions equals to latest in minimum supported RHEL (7.x at 02.2021)
define('OBS_MIN_PHP_VERSION',     '5.6.26'); // PHP (15 Sep 2016, https://www.php.net/releases/index.php)
//define('OBS_MIN_PYTHON2_VERSION', '2.7.12'); // Python 2 (26 June 2016, https://www.python.org/doc/versions/)
define('OBS_MIN_PYTHON2_VERSION', '2.7.5');  // last in RHEL/CentOS 7 (and Ubuntu LTS 14.04)
define('OBS_MIN_PYTHON3_VERSION', '3.5.2');  // Python 3 (27 June 2016, https://www.python.org/doc/versions/)
define('OBS_MIN_MYSQL_VERSION',   '5.6.5');  // https://stackoverflow.com/questions/4489548/why-there-can-be-only-one-timestamp-column-with-current-timestamp-in-default-cla
//define('OBS_MIN_MARIADB_VERSION', '10.0');   // MySQL 5.6 mostly equals with MariaDB 10.0: https://mariadb.com/kb/en/timestamp/
define('OBS_MIN_MARIADB_VERSION', '5.5.68'); // 5.5.68 last in RHEL/CentOS 7 and 5.5.63 in Ubuntu LTS 14.04
define('OBS_MIN_RRD_VERSION',     '1.4.8');  // last in RHEL/CentOS 7
//define('OBS_MIN_RRD_VERSION',     '1.5.5');  // RRDTool (10 Nov 2015, https://github.com/oetiker/rrdtool-1.x/tags)

// Minimum possible unixtime, only for validate passed unixtime
//define('OBS_MIN_UNIXTIME', 946684800); // 01/01/2000 @ 12:00am (UTC), just in most cases unixtime not possible less than this date (net-snmp released in 2000, any network device not have uptime longest)
define('OBS_MIN_UNIXTIME', 504921600); // 01/01/1986 @ 12:00am (UTC), not network devices produces before this date :)

// OBSERVIUM URLs
define('OBSERVIUM_URL',          'https://www.observium.org');
define('OBSERVIUM_DOCS_URL',     'https://docs.observium.org');

// Set QUIET
define('OBS_QUIET', isset($options['q']));

// Set DEBUG
if (isset($options['d'])) {
  // CLI
  echo("DEBUG!\n");
  define('OBS_DEBUG', is_array($options['d']) ? count($options['d']) : 1); // -d == 1, -dd == 2..
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  if (OBS_DEBUG > 1) {
    //ini_set('error_reporting', E_ALL ^ E_NOTICE); // FIXME, too many warnings ;)
    ini_set('error_reporting', E_ALL ^ E_NOTICE ^ E_WARNING);
  } else {
    ini_set('error_reporting', E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR); // Only various errors
  }
} elseif (defined('OBS_API')) {
  // API
  $debug_web_requested = ((isset($_REQUEST['debug']) && $_REQUEST['debug']) ||
                          (isset($_SERVER['PATH_INFO']) && strpos($_SERVER['PATH_INFO'], 'debug')) ||
                          (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'debug')));
  if ($debug_web_requested) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 0);
    ini_set('allow_url_fopen', 0);
    ini_set('error_reporting', E_ALL);
    $debug = TRUE;
    // API used self debug output
    if (isset($config['web_debug_unprivileged']) && $config['web_debug_unprivileged'] &&
        is_numeric($_GET['debug']) && $_GET['debug'] > 1) {
      define('OBS_DEBUG', 1);
    } else {
      define('OBS_DEBUG', 0);
    }
  } else {
    define('OBS_DEBUG', 0);
  }
} elseif ($debug_web_requested = ((isset($_REQUEST['debug']) && $_REQUEST['debug']) ||
                                  (isset($_SERVER['PATH_INFO']) && strpos($_SERVER['PATH_INFO'], 'debug')) ||
                                  (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'debug')))) {
  // WEB

  // Note, for security reasons set OBS_DEBUG constant in WUI moved to auth module
  if (isset($config['web_debug_unprivileged']) && $config['web_debug_unprivileged']) {
    define('OBS_DEBUG', 1);

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    //ini_set('error_reporting', E_ALL ^ E_NOTICE);
    ini_set('error_reporting', E_ALL ^ E_NOTICE ^ E_WARNING);
  } // else not set anything before auth
  
} else {
  define('OBS_DEBUG', 0);
  ini_set('display_errors', 0);
  ini_set('display_startup_errors', 0);
  //ini_set('error_reporting', 0); // Use default php config
}
if (!defined('OBS_DEBUG') || !OBS_DEBUG) {
  // Disable E_NOTICE from reporting.
  error_reporting(error_reporting() & ~E_NOTICE);
}
ini_set('log_errors', 1);

//
// Unit test not used sql connect and does not include includes/sql-config.inc.php
if (defined('__PHPUNIT_PHAR__'))
{
  // Base dir, if it's not set in config
  if (!isset($config['install_dir'])) {
    $config['install_dir'] = dirname(__DIR__);
  }
  if (!defined('OBS_DB_SKIP')) {
    define('OBS_DB_SKIP', TRUE);
  }
  // In phpunit, autoload not work
  set_include_path(dirname(__DIR__) . "/libs/pear" . PATH_SEPARATOR . get_include_path());
  require("Net/IPv4.php");
  require("Net/IPv6.php");
  require("Console/Color2.php");
  //print_warning("WARNING. In PHP Unit tests can skip MySQL connect. But If you test mysql functions, check your configs.");
} else {
  define('OBS_DB_SKIP', FALSE);
}

// Set default Include path
set_include_path($config['install_dir'] . "/libs/pear" . PATH_SEPARATOR . // Still required Pear path
                 $config['install_dir'] . "/libs"      . PATH_SEPARATOR .
                 get_include_path());

// Set default paths.
$config['install_dir'] = rtrim($config['install_dir'], ' /');
if (!isset($config['html_dir'])) { $config['html_dir'] = $config['install_dir'] . '/html'; }
else                             { $config['html_dir'] = rtrim($config['html_dir'], ' /'); }
if (!isset($config['rrd_dir']))  { $config['rrd_dir']  = $config['install_dir'] . '/rrd'; }
else                             { $config['rrd_dir']  = rtrim($config['rrd_dir'], ' /'); }

// Fix RRD Directory path to always have a trailing slash so that it works nicely with rrdcached
//$config['rrd_dir'] = fix_path_slash($config['rrd_dir']);

if (!isset($config['log_dir']))       { $config['log_dir']      = $config['install_dir'] . '/logs'; }
else                                  { $config['log_dir']      = rtrim($config['log_dir'], ' /'); }
if (!isset($config['log_file']))      { $config['log_file']     = $config['log_dir'] . '/observium.log'; } // FIXME should not be absolute path, look for where it is used
if (!isset($config['temp_dir']))      { $config['temp_dir']     = '/tmp'; }
else                                  { $config['temp_dir']     = rtrim($config['temp_dir'], ' /'); }
if (!isset($config['mib_dir']))       { $config['mib_dir']      = $config['install_dir'] . '/mibs'; }
else                                  { $config['mib_dir']      = rtrim($config['mib_dir'], ' /'); }
if (!isset($config['template_dir']))  { $config['template_dir'] = $config['install_dir'] . '/templates'; }
else                                  { $config['template_dir'] = rtrim($config['template_dir'], ' /'); }
if (!isset($config['cache_dir']))     { $config['cache_dir']    = $config['temp_dir'] . '/observium_cache'; }
else                                  { $config['cache_dir']    = rtrim($config['cache_dir'], ' /'); }
if (!isset($config['nagplug_dir']))   { $config['nagplug_dir']   = '/usr/lib/nagios/plugins'; }
else                                  { $config['nagplug_dir']   = rtrim($config['nagplug_dir'], ' /'); }

// Load random_compat (for PHP 5.x)
require_once("random_compat/random.php");

// Load hash-compat (for < PHP 5.6)
require_once("hash-compat/hash_equals.php");

// Collect php errors mostly fr catch php8 errors
if ($config['php_debug']) {
  ini_set('error_reporting', E_ALL ^ E_WARNING ^ E_STRICT);
  ini_set("error_log", $config['log_dir'] . "/php-errors.log");
}

// Debug nicer functions
if ((defined('OBS_DEBUG') && OBS_DEBUG) || strlen($_SERVER['REMOTE_ADDR'])) {
  // Nicer for print_vars(), for WUI loaded always,
  // Required php tokenizer extension!
  if (!function_exists('rt') && function_exists('token_get_all') &&
      is_file($config['install_dir']."/libs/ref.inc.php")) {
    include($config['install_dir']."/libs/ref.inc.php");
  }
}

// Community specific definition
if (is_file($config['install_dir'].'/includes/definitions/definitions.dat')) {
  //var_dump($config);
  $config_tmp = file_get_contents($config['install_dir'].'/includes/definitions/definitions.dat');
  $config_tmp = gzuncompress($config_tmp);
  $config_tmp = safe_unserialize($config_tmp);
  //var_dump($config_tmp);
  if (is_array($config_tmp) && isset($config_tmp['os'])) { // Simple check for passed correct data
    $config = array_merge($config, $config_tmp);
  }
  unset($config_tmp);
}

$definition_files = [
  'os',           // OS definitions
  'wui',          // Web UI specific definitions
  'graphtypes',   // Graph Type definitions
  'rrdtypes',     // RRD Type definitions
  'entities',     // Entity type definitions
  'rewrites',     // Rewriting array definitions
  'mibs',         // MIB definitions
  'models',       // Hardware model definitions (leave it after os and rewrites)
  'vendors',      // Vendor/manufacturer definitions
  'geo',          // Geolocation api definitions
  'vm',           // Virtual Machine definitions
  'transports',   // Alerting transport definitions
  'apis',         // External APIs definitions
  'apps',         // Apps system definitions
];

foreach ($definition_files as $file) {
  $file = $config['install_dir'].'/includes/definitions/'.$file.'.inc.php';
  if (is_file($file)) {
    include($file);
  }
}

unset($definition_files, $file); // Clean

// Alert Graphs
## FIXME - this is ugly
## Merge it in to entities, since that's what it is!

$config['alert_graphs']['port']['ifInOctets_rate']       = array('type' => 'port_bits', 'id' => '@port_id');
$config['alert_graphs']['port']['ifOutOctets_rate']      = array('type' => 'port_bits', 'id' => '@port_id');
$config['alert_graphs']['port']['ifInOctets_perc']       = array('type' => 'port_percent', 'id' => '@port_id');
$config['alert_graphs']['port']['ifOutOctets_perc']      = array('type' => 'port_percent', 'id' => '@port_id');
$config['alert_graphs']['mempool']['mempool_perc']       = array('type' => 'mempool_usage', 'id' => '@mempool_id');
$config['alert_graphs']['sensor']['sensor_value']        = array('type' => 'sensor_graph', 'id' => '@sensor_id');
$config['alert_graphs']['sensor']['sensor_event']        = array('type' => 'sensor_graph', 'id' => '@sensor_id');
$config['alert_graphs']['status']['status_event']        = array('type' => 'status_graph', 'id' => '@status_id');
$config['alert_graphs']['status']['status_state']        = array('type' => 'status_graph', 'id' => '@status_id');

$config['alert_graphs']['processor']['processor_usage']  = array('type' => 'processor_usage', 'id' => '@processor_id');
$config['alert_graphs']['storage']['storage_perc']  = array('type' => 'storage_usage', 'id' => '@storage_id');

// IP types
// https://www.iana.org/assignments/iana-ipv6-special-registry/iana-ipv6-special-registry.xhtml
$config['ip_types']['unspecified']    = array('networks' => array('0.0.0.0', '::/128'),
                                              'name'     => 'Unspecified', 'subtext' => 'Example: ::/128, 0.0.0.0',
                                              'label-class' => 'error',
                                              'descr'    => 'This address may only be used as a source address by an initialising host before it has learned its own address. Example: ::/128, 0.0.0.0');
$config['ip_types']['loopback']       = array('networks' => array('127.0.0.0/8', '::1/128'),
                                              'name'     => 'Loopback', 'subtext' => 'Example: ::1/128, 127.0.0.1',
                                              'label-class' => 'info',
                                              'descr'    => 'This address is used when a host talks to itself. Example: ::1/128, 127.0.0.1');
$config['ip_types']['private']        = array('networks' => array('10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'fc00::/7'),
                                              'name'     => 'Private Local Addresses', 'subtext' => 'Example: fdf8:f53b:82e4::53, 192.168.0.1',
                                              'label-class' => 'warning',
                                              'descr'    => 'These addresses are reserved for local use in home and enterprise environments and are not public address space. Example: fdf8:f53b:82e4::53, 192.168.0.1');
$config['ip_types']['multicast']      = array('networks' => array('224.0.0.0/4', 'ff00::/8'),
                                              'name'     => 'Multicast', 'subtext' => 'Example: ff01:0:0:0:0:0:0:2, 224.0.0.1',
                                              'label-class' => 'inverse',
                                              'descr'    => 'These addresses are used to identify multicast groups. Example: ff01:0:0:0:0:0:0:2, 224.0.0.1');
$config['ip_types']['link-local']     = array('networks' => array('169.254.0.0/16', 'fe80::/10'),
                                              'name'     => 'Link-Local Addresses', 'subtext' => 'Example: fe80::200:5aee:feaa:20a2, 169.254.3.1',
                                              'label-class' => 'suppressed',
                                              'descr'    => 'These addresses are used on a single link or a non-routed common access network, such as an Ethernet LAN. Example: fe80::200:5aee:feaa:20a2, 169.254.3.1');
$config['ip_types']['ipv4mapped']     = array('networks' => array('::ffff/96'),
                                              'name'     => 'IPv6 IPv4-Mapped', 'subtext' => 'Example: ::ffff:192.0.2.47',
                                              'label-class' => 'primary',
                                              'descr'    => 'These addresses are used to embed IPv4 addresses in an IPv6 address. Example: 64:ff9b::192.0.2.33');
$config['ip_types']['ipv4embedded']   = array('networks' => array('64:ff9b::/96'),
                                              'name'     => 'IPv6 IPv4-Embedded', 'subtext' => 'Example: ::ffff:192.0.2.47',
                                              'label-class' => 'primary',
                                              'descr'    => 'IPv4-converted IPv6 addresses and IPv4-translatable IPv6 addresses. Example: 64:ff9b::192.0.2.33');
$config['ip_types']['6to4']           = array('networks' => array('192.88.99.0/24', '2002::/16'),
                                              'name'     => 'IPv6 6to4', 'subtext' => 'Example: 2002:cb0a:3cdd:1::1, 192.88.99.1',
                                              'label-class' => 'primary',
                                              'descr'    => 'A 6to4 gateway adds its IPv4 address to this 2002::/16, creating a unique /48 prefix. Example: 2002:cb0a:3cdd:1::1, 192.88.99.1');
$config['ip_types']['documentation']  = array('networks' => array('192.0.2.0/24', '198.51.100.0/24', '203.0.113.0/24', '2001:db8::/32'),
                                              'name'     => 'Documentation', 'subtext' => 'Example: 2001:db8:8:4::2, 203.0.113.1',
                                              'label-class' => 'primary',
                                              'descr'    => 'These addresses are used in examples and documentation. Example: 2001:db8:8:4::2, 203.0.113.1');
$config['ip_types']['teredo']         = array('networks' => array('2001:0000::/32'),
                                              'name'     => 'IPv6 Teredo', 'subtext' => 'Example: 2001:0000:4136:e378:8000:63bf:3fff:fdd2',
                                              'label-class' => 'primary',
                                              'descr'    => 'This is a mapped address allowing IPv6 tunneling through IPv4 NATs. The address is formed using the Teredo prefix, the servers unique IPv4 address, flags describing the type of NAT, the obfuscated client port and the client IPv4 address, which is probably a private address. Example: 2001:0000:4136:e378:8000:63bf:3fff:fdd2');
$config['ip_types']['benchmark']      = array('networks' => array('198.18.0.0/15', '2001:0002::/48'),
                                              'name'     => 'Benchmarking', 'subtext' => 'Example: 2001:0002:6c::430, 198.18.0.1',
                                              'label-class' => 'error',
                                              'descr'    => 'These addresses are reserved for use in documentation. Example: 2001:0002:6c::430, 198.18.0.1');
$config['ip_types']['orchid']         = array('networks' => array('2001:0010::/28', '2001:0020::/28'),
                                              'name'     => 'IPv6 Orchid', 'subtext' => 'Example: 2001:10:240:ab::a',
                                              'label-class' => 'primary',
                                              'descr'    => 'These addresses are used for a fixed-term experiment. Example: 2001:10:240:ab::a');
$config['ip_types']['reserved']       = array(//'networks' => array(),
                                              'name'     => 'Reserved', 'subtext' => 'Address in reserved address space',
                                              'label-class' => 'error',
                                              'descr'    => 'Reserved address space');
$config['ip_types']['broadcast']      = array(//'networks' => array(),
                                              'name'     => 'IPv4 Broadcast', 'subtext' => 'Example: 255.255.255.255',
                                              'label-class' => 'disabled',
                                              'descr'    => 'IPv4 broadcast address. Example: 255.255.255.255');
$config['ip_types']['anycast']        = array(//'networks' => array(),
                                              'name'     => 'Anycast',
                                              'label-class' => 'primary',
                                              'descr'    => 'Anycast is a network addressing and routing methodology in which a single destination address has multiple routing paths to two or more endpoint destinations.');
// Keep this at last!
$config['ip_types']['unicast']        = array('networks' => array('2000::/3'), // 'networks' => array('0.0.0.0/0', '2000::/3'),'
                                              'name'     => 'Global Unicast', 'subtext' => 'Example: 2a02:408:7722::, 80.94.60.2', 'disabled' => 1,
                                              'label-class' => 'success',
                                              'descr'    => 'Global Unicast addresses. Example: 2a02:408:7722::, 80.94.60.2');

// Syslog colour and name translation

$config['syslog']['priorities'][0] = array('name' => 'emergency',     'color' => '#D94640', 'label-class' => 'inverse',    'row-class' => 'error',      'emoji' => 'red_circle');
$config['syslog']['priorities'][1] = array('name' => 'alert',         'color' => '#D94640', 'label-class' => 'delayed',    'row-class' => 'error',      'emoji' => 'red_circle');
$config['syslog']['priorities'][2] = array('name' => 'critical',      'color' => '#D94640', 'label-class' => 'error',      'row-class' => 'error',      'emoji' => 'red_circle');
$config['syslog']['priorities'][3] = array('name' => 'error',         'color' => '#E88126', 'label-class' => 'error',      'row-class' => 'error',      'emoji' => 'red_circle');
$config['syslog']['priorities'][4] = array('name' => 'warning',       'color' => '#F2CA3F', 'label-class' => 'warning',    'row-class' => 'warning',    'emoji' => 'large_yellow_circle');
$config['syslog']['priorities'][5] = array('name' => 'notification',  'color' => '#107373', 'label-class' => 'success',    'row-class' => 'recovery',   'emoji' => 'large_orange_circle'); // large_green_circle
$config['syslog']['priorities'][6] = array('name' => 'informational', 'color' => '#499CA6', 'label-class' => 'primary',    'row-class' => '',           'emoji' => 'large_blue_circle'); //'row-class' => 'info');
$config['syslog']['priorities'][7] = array('name' => 'debugging',     'color' => '#5AA637', 'label-class' => 'suppressed', 'row-class' => 'suppressed', 'emoji' => 'large_purple_circle');

for ($i = 8; $i < 16; $i++)
{
  $config['syslog']['priorities'][$i] = array('name' => 'other',        'color' => '#D2D8F9', 'label-class' => 'disabled',   'row-class' => 'disabled', 'emoji' => 'large_orange_circle');
}

// https://tools.ietf.org/html/draft-ietf-netmod-syslog-model-14
$config['syslog']['facilities'][0]  = array('name' => 'kern',     'descr' => 'kernel messages');
$config['syslog']['facilities'][1]  = array('name' => 'user',     'descr' => 'user-level messages');
$config['syslog']['facilities'][2]  = array('name' => 'mail',     'descr' => 'mail system');
$config['syslog']['facilities'][3]  = array('name' => 'daemon',   'descr' => 'system daemons');
$config['syslog']['facilities'][4]  = array('name' => 'auth',     'descr' => 'security/authorization messages');
$config['syslog']['facilities'][5]  = array('name' => 'syslog',   'descr' => 'messages generated internally by syslogd');
$config['syslog']['facilities'][6]  = array('name' => 'lpr',      'descr' => 'line printer subsystem');
$config['syslog']['facilities'][7]  = array('name' => 'news',     'descr' => 'network news subsystem');
$config['syslog']['facilities'][8]  = array('name' => 'uucp',     'descr' => 'UUCP subsystem');
$config['syslog']['facilities'][9]  = array('name' => 'cron',     'descr' => 'clock daemon');
$config['syslog']['facilities'][10] = array('name' => 'authpriv', 'descr' => 'security/authorization messages');
$config['syslog']['facilities'][11] = array('name' => 'ftp',      'descr' => 'FTP daemon');
$config['syslog']['facilities'][12] = array('name' => 'ntp',      'descr' => 'NTP subsystem');
$config['syslog']['facilities'][13] = array('name' => 'audit',    'descr' => 'log audit');
$config['syslog']['facilities'][14] = array('name' => 'console',  'descr' => 'log alert');
$config['syslog']['facilities'][15] = array('name' => 'cron2',    'descr' => 'clock daemon');
$config['syslog']['facilities'][16] = array('name' => 'local0',   'descr' => 'local use 0 (local0)');
$config['syslog']['facilities'][17] = array('name' => 'local1',   'descr' => 'local use 1 (local1)');
$config['syslog']['facilities'][18] = array('name' => 'local2',   'descr' => 'local use 2 (local2)');
$config['syslog']['facilities'][19] = array('name' => 'local3',   'descr' => 'local use 3 (local3)');
$config['syslog']['facilities'][20] = array('name' => 'local4',   'descr' => 'local use 4 (local4)');
$config['syslog']['facilities'][21] = array('name' => 'local5',   'descr' => 'local use 5 (local5)');
$config['syslog']['facilities'][22] = array('name' => 'local6',   'descr' => 'local use 6 (local6)');
$config['syslog']['facilities'][23] = array('name' => 'local7',   'descr' => 'local use 7 (local7)');

// Alert severities (emoji used _only_ as notification icon)
// Recover emoji is white_check_mark
$config['alert']['severity']['crit'] = [ 'name' => 'Critical',      'color' => '#D94640', 'label-class' => 'error',   'row-class' => 'error',   'icon' => $config['icon']['critical'],      'emoji' => 'fire' ];
$config['alert']['severity']['warn'] = [ 'name' => 'Warning',       'color' => '#F2CA3F', 'label-class' => 'warning', 'row-class' => 'warning', 'icon' => $config['icon']['warning'],       'emoji' => 'warning' ];
//$config['alert']['severity']['info'] = [ 'name' => 'Informational', 'color' => '#499CA6', 'label-class' => 'primary', 'row-class' => 'info',    'icon' => $config['icon']['informational'], 'emoji' => 'information_source' ];

// Possible transports for net-snmp, used for enumeration in several functions
$config['snmp']['transports'] = array('udp', 'udp6', 'tcp', 'tcp6');

// 'count' is min total errors count, after which autodisable this MIB/oid pair
// 'rate' is min total rate (per poll), after which autodisable this MIB/oid pair
// note, rate not fully correct after server reboot (it will less than really)
$config['snmp']['errorcodes'][-1]   = [
  'reason' => 'Cached',                 // snmp really not requested, but gets from cache
  'name'   => 'OBS_SNMP_ERROR_CACHED',
  'msg'    => ''
];
$config['snmp']['errorcodes'][0]    = [
  'reason' => 'OK',
  'name'   => 'OBS_SNMP_ERROR_OK',
  'msg'    => ''
];

// [1-99] Non critical
$config['snmp']['errorcodes'][1]    = [
  'reason' => 'Empty response',         // exitcode = 0, but not have any data
  'count'  => 288,                      // 288 with rate 1/poll ~ 1 day
  'rate'   => 0.9,
  'name'   => 'OBS_SNMP_ERROR_EMPTY_RESPONSE',
  'msg'    => ''
];
$config['snmp']['errorcodes'][2]    = [
  'reason' => 'Request not completed',  // Snmp output return correct data, but stopped by some reason (timeout, network error)
  'name'   => 'OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED',
  'msg'    => ''
];
$config['snmp']['errorcodes'][3]    = [
  'reason' => 'Too long response',      // Not empty output, but exitcode = 1 and runtime > 10
  'name'   => 'OBS_SNMP_ERROR_TOO_LONG_RESPONSE',
  'msg'    => ''
];
$config['snmp']['errorcodes'][4]    = [
  'reason' => 'Too big max-repetition in GETBULK', // Not empty output, but exitcode = 2 and stderr "Reason: (tooBig)"
  'count'  => 2880,                     // 2880 with rate 1/poll ~ 10 day
  'rate'   => 0.9,
  'name'   => 'OBS_SNMP_ERROR_TOO_BIG_MAX_REPETITION_IN_GETBULK',
  'msg'    => 'WARNING! %command% did not complete. Try to increase SNMP timeout or decrease SNMP Max Repetitions on the device properties page or set to 0 to not use bulk snmp commands.'
];
$config['snmp']['errorcodes'][5]    = [
  'reason' => 'GETNEXT empty response', // Not empty output, SNMPGETNEXT returned different Oid
  'count'  => 288,                      // 288 with rate 1/poll ~ 1 day
  'rate'   => 0.9,
  'name'   => 'OBS_SNMP_ERROR_GETNEXT_EMPTY_RESPONSE',
  'msg'    => ''
];

// [900-999] Critical errors, but this is incorrect auth or config or missed files on client side
$config['snmp']['errorcodes'][900]  = [
  'reason' => 'isSNMPable',             // Device up/down test, not used for counting
  'name'   => 'OBS_SNMP_ERROR_ISSNMPABLE',
  'msg'    => ''
];
$config['snmp']['errorcodes'][991]  = [
  'reason' => 'Authentication failure', // Snmp auth errors
  'name'   => 'OBS_SNMP_ERROR_AUTHENTICATION_FAILURE',
  'msg'    => ''
];
$config['snmp']['errorcodes'][992]  = [
  'reason' => 'Unsupported authentication or privacy protocol', // Snmp auth errors
  'name'   => 'OBS_SNMP_ERROR_UNSUPPORTED_ALGO',
  'msg'    => 'ERROR! Unsupported SNMPv3 authentication or privacy protocol detected. Newer version of net-snmp required. Please read [FAQ](' .
              OBSERVIUM_DOCS_URL . '/faq/#snmpv3-strong-authentication-or-encryption){target=_blank}.'
];
$config['snmp']['errorcodes'][993]  = [
  'reason' => 'OID not increasing',     // OID not increasing
  'name'   => 'OBS_SNMP_ERROR_OID_NOT_INCREASING',
  'msg'    => 'WARNING! %command% ended prematurely due to an error [%reason%] on MIB::Oid [%mib%::%oid%]. Try to use -Cc option for %command% command.'
];
$config['snmp']['errorcodes'][994]  = [
  'reason' => 'Unknown host',           // Unknown host
  'name'   => 'OBS_SNMP_ERROR_UNKNOWN_HOST',
  'msg'    => ''
];
$config['snmp']['errorcodes'][995]  = [
  'reason' => 'Incorrect arguments',    // Incorrect arguments passed to snmpcmd
  'name'   => 'OBS_SNMP_ERROR_INCORRECT_ARGUMENTS',
  'msg'    => ''
];
$config['snmp']['errorcodes'][996]  = [
  'reason' => 'MIB or oid not found',   // MIB module or oid not found in specified dirs
  'name'   => 'OBS_SNMP_ERROR_MIB_OR_OID_NOT_FOUND',
  'msg'    => ''
];
$config['snmp']['errorcodes'][997]  = [
  'reason' => 'Wrong .index in mibs dir', // This is common net-snmp bug, require delete all .index files
  'name'   => 'OBS_SNMP_ERROR_WRONG_INDEX_IN_MIBS_DIR',
  'msg'    => 'ERROR! Wrong .index in mibs dir net-snmp bug detected. Required delete all .index files. Please read [FAQ](' .
              OBSERVIUM_DOCS_URL . '/faq/#all-my-hosts-seem-down-to-observium-snmp-doesnt-seem-to-work-anymore){target=_blank}.'
];
$config['snmp']['errorcodes'][998]  = [
  'reason' => 'MIB or oid disabled',    // MIB or oid disabled
  'name'   => 'OBS_SNMP_ERROR_MIB_OR_OID_DISABLED',
  'msg'    => ''
];
$config['snmp']['errorcodes'][999]  = [
  'reason' => 'Unknown',                // Some unidentified error
  'count'  => 288,                      // 288 with rate 1.95/poll ~ 12 hours
  'rate'   => 0.9,
  'name'   => 'OBS_SNMP_ERROR_UNKNOWN',
  'msg'    => ''
];

// [1000-1xxx] Critical errors on device side, can be auto disabled
$config['snmp']['errorcodes'][1000] = [
  'reason' => 'Failed response',          // Any critical error in snmp output, which not return useful data
  'count'  => 70,                         // errors in every poll run, disable after ~ 6 hours
  'rate'   => 0.9,
  'name'   => 'OBS_SNMP_ERROR_FAILED_RESPONSE',
  'msg'    => ''
];
//$config['snmp']['errorcodes'][1001] = array('reason' => 'Authentication failure',   // Snmp auth errors
//                                            'count'  => 25,                         // errors in every poll run, disable after ~ 1.5 hour
//                                            'rate'   => 0.9,
//                                            'msg'    => '');
$config['snmp']['errorcodes'][1002] = [
  'reason' => 'Request timeout',          // Cmd exit by timeout
  'count'  => 25,                         // errors in every poll run, disable after ~ 1.5 hour
  'rate'   => 0.9,
  'name'   => 'OBS_SNMP_ERROR_REQUEST_TIMEOUT',
  'msg'    => ''
];
$config['snmp']['errorcodes'][1004] = [
  'reason' => 'Bulk Request timeout',     // Cmd exit by timeout
  'count'  => 25,                         // errors in every poll run, disable after ~ 1.5 hour
  'rate'   => 0.9,
  'name'   => 'OBS_SNMP_ERROR_BULK_REQUEST_TIMEOUT',
  'msg'    => 'ERROR! %command% exit by timeout. Try to decrease SNMP Max Repetitions on the device properties page or set to 0 to not use bulk snmp commands.'
];
// Register error code constants
define('OBS_SNMP_ERROR_CACHED',                           -1);
define('OBS_SNMP_ERROR_OK',                                0);
define('OBS_SNMP_ERROR_EMPTY_RESPONSE',                    1);
define('OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED',             2);
define('OBS_SNMP_ERROR_TOO_LONG_RESPONSE',                 3);
define('OBS_SNMP_ERROR_TOO_BIG_MAX_REPETITION_IN_GETBULK', 4);
define('OBS_SNMP_ERROR_GETNEXT_EMPTY_RESPONSE',            5);
define('OBS_SNMP_ERROR_ISSNMPABLE',                      900);
define('OBS_SNMP_ERROR_AUTHENTICATION_FAILURE',          991);
define('OBS_SNMP_ERROR_UNSUPPORTED_ALGO',                992);
define('OBS_SNMP_ERROR_OID_NOT_INCREASING',              993);
define('OBS_SNMP_ERROR_UNKNOWN_HOST',                    994);
define('OBS_SNMP_ERROR_INCORRECT_ARGUMENTS',             995);
define('OBS_SNMP_ERROR_MIB_OR_OID_NOT_FOUND',            996);
define('OBS_SNMP_ERROR_WRONG_INDEX_IN_MIBS_DIR',         997);
define('OBS_SNMP_ERROR_MIB_OR_OID_DISABLED',             998);
define('OBS_SNMP_ERROR_UNKNOWN',                         999);
define('OBS_SNMP_ERROR_FAILED_RESPONSE',                1000);
define('OBS_SNMP_ERROR_REQUEST_TIMEOUT',                1002);
define('OBS_SNMP_ERROR_BULK_REQUEST_TIMEOUT',           1004);
/*
foreach ($config['snmp']['errorcodes'] as $errorcode => $tmp) {
  //$errorname = $tmp['name'];
  // fast generate names:
  $errorname = str_replace([ '.', ' ', '-' ], [ '', '_', '_' ], $tmp['reason']);
  $errorname = 'OBS_SNMP_ERROR_'.strtoupper($errorname);
  print_debug("define('$errorname', $errorcode);");

  define($errorname, $errorcode);
}
unset($errorname, $errorcode, $tmp);
*/

// IPMI user levels (used in GUI, first entry = default if unset)

$config['ipmi']['userlevels']['USER']          = array('text' => 'User');
$config['ipmi']['userlevels']['OPERATOR']      = array('text' => 'Operator');
$config['ipmi']['userlevels']['ADMINISTRATOR'] = array('text' => 'Administrator');
$config['ipmi']['userlevels']['CALLBACK']      = array('text' => 'Callback');

// IPMI interfaces (used in GUI, first entry = default if unset)

$config['ipmi']['interfaces']['lan']     = array('text' => 'IPMI v1.5 LAN Interface');
$config['ipmi']['interfaces']['lanplus'] = array('text' => 'IPMI v2.0 RMCP+ LAN Interface');
$config['ipmi']['interfaces']['imb']     = array('text' => 'Intel IMB Interface');
$config['ipmi']['interfaces']['open']    = array('text' => 'Linux OpenIPMI Interface');

// CLEANME. RANCID OS map (for config generation script)
/* MOVED to os definitions as $config['os'][$os]['rancid']
$config['rancid']['os_map']['arista_eos'] = 'arista';
//$config['rancid']['os_map']['avocent']    = 'avocent';
//$config['rancid']['os_map']['ciena-waveserveros']   = 'ciena-ws';
$config['rancid']['os_map']['cyclades']   = 'avocent';
$config['rancid']['os_map']['f5']         = 'f5'; // Only for <= v10
$config['rancid']['os_map']['fortigate']  = 'fortigate';
$config['rancid']['os_map']['ftos']       = 'force10';
$config['rancid']['os_map']['ios']        = 'cisco'; // ios in rancid 3.11+
$config['rancid']['os_map']['iosxe']      = 'cisco';
$config['rancid']['os_map']['iosxr']      = 'cisco-xr'; // ios-xr in rancid 3.11+
$config['rancid']['os_map']['asa']        = 'cisco';
$config['rancid']['os_map']['pixos']      = 'cisco';
$config['rancid']['os_map']['nxos']       = 'cisco-nx'; // ios-nx in rancid 3.11+
$config['rancid']['os_map']['ironware']   = 'foundry';
$config['rancid']['os_map']['junos']      = 'juniper'; // junos in rancid 3.11+
$config['rancid']['os_map']['screenos']   = 'netscreen';
$config['rancid']['os_map']['opengear']   = 'opengear';
$config['rancid']['os_map']['routeros']   = 'mikrotik'; // routeros in rancid 3.13+
$config['rancid']['os_map']['pfsense']    = 'pfsense';
$config['rancid']['os_map']['netscaler']  = 'netscaler';
// Rancid v3.0+ specific os map
//$config['rancid']['os_map_3']['adtran-aos']            = 'adtran';
$config['rancid']['os_map_3']['arbos']                 = 'arbor';
$config['rancid']['os_map_3']['powerconnect-fastpath'] = 'dell';
$config['rancid']['os_map_3']['powerconnect-radlan']   = 'dell';
$config['rancid']['os_map_3']['dnos6']                 = 'dell';
$config['rancid']['os_map_3']['enterasys']             = 'enterasys';
$config['rancid']['os_map_3']['xos']                   = 'extreme';
//--$config['rancid']['os_map_3']['juniper-srx']           = 'juniper-srx'; // SRX in junos..
$config['rancid']['os_map_3']['mrvos']                 = 'mrv';
$config['rancid']['os_map_3']['seos']                  = 'redback';
// Rancid v3.2+ specific os map
//--$config['rancid']['os_map_3.2']['wlc']                 = 'cisco-wlc4';
$config['rancid']['os_map_3.2']['wlc']                 = 'cisco-wlc5';
$config['rancid']['os_map_3.2']['panos']               = 'paloalto';
$config['rancid']['os_map_3.2']['procurve']            = 'hp';
// Rancid v3.3+ specific os map
$config['rancid']['os_map_3.3']['ciena-waveserveros']  = 'ciena-ws';
$config['rancid']['os_map_3.3']['steelhead']           = 'riverbed';
// Rancid v3.4+ specific os map
$config['rancid']['os_map_3.4']['a10-ax']              = 'a10';
$config['rancid']['os_map_3.4']['a10-ex']              = 'a10';
// Rancid v3.5+ specific os map
$config['rancid']['os_map_3.5']['edgemax']             = 'edgemax';
//--$config['rancid']['os_map_3.5']['f5']                  = 'bigip'; // v11+
// Rancid v3.7+ specific os map
$config['rancid']['os_map_3.7']['ciscosb']             = 'cisco-sb'; // ios-sb in rancid 3.11+
//--$config['rancid']['os_map_3.7']['wlc']                 = 'cisco-wlc8';
$config['rancid']['os_map_3.7']['timos']               = 'sros'; // Classic CLI (TiMOS)
// Rancid v3.8+ specific os map
$config['rancid']['os_map_3.8']['cisco-fxos']          = 'fxos';
$config['rancid']['os_map_3.8']['vrp']                 = 'vrp';
//--$config['rancid']['os_map_3.8']['f5']                  = 'bigip13'; // v13+
//$config['rancid']['os_map_3.8']['timos']               = 'sros-md'; // MD-CLI (TiMOS) 7750 SR and 7950 XRS routers
// Rancid v3.9+ specific os map
//$config['rancid']['os_map_3.9']['arrcus']                 = 'arcos'; // We not support this OS
// Rancid v3.11+ specific os map
//$config['rancid']['os_map_3.11']['ios-exr']               = 'ios-exr';
//$config['rancid']['os_map_3.11']['junos-evo']             = 'junos-evo';
// Rancid v3.13+ specific os map
//$config['rancid']['os_map_3.13']['axis-switch']             = 'axis';
//--$config['rancid']['os_map_3.13']['ios-xr7']               = 'ios-xr7'; // IOS XR 7+
*/
# Enable these (in config.php) if you added the powerconnect addon to your RANCID install
#$config['rancid']['os_map']['powerconnect-fastpath'] = 'dell';
#$config['rancid']['os_map']['powerconnect-radlan']   = 'dell';
#$config['rancid']['os_map']['dnos6']                 = 'dell';

//////////////////////////////////////////////////////////////////////////
// No changes below this line // (no changes above it either, remember? //
//////////////////////////////////////////////////////////////////////////

// Include DB functions

define('OBS_DB_LINK', 'observium_link'); // Global variable name for DB link identifier, required for mysqli
define('OBS_DB_EXTENSION', 'mysqli');    // Old MySQL extension is deprecated since PHP 5.5, we unsupported it anymore
$config['db_extension'] = OBS_DB_EXTENSION;
/*
switch ($config['db_extension'])
{
  case 'mysql':
    define('OBS_DB_EXTENSION', $config['db_extension']);
    print_error("MySQL extension is deprecated since PHP 5.5, we unsupported it anymore. Use mysqli extension instead!");
    break;
  case 'mysqli':
  default:
    define('OBS_DB_EXTENSION', 'mysqli');
}
*/
require_once($config['install_dir'] . "/includes/db.inc.php");

// Connect to database
if (OBS_DB_SKIP !== TRUE) {
  $GLOBALS[OBS_DB_LINK] = dbOpen($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
}

require($config['install_dir'] . '/includes/definitions/version.inc.php');

// Base user levels

$config['user_level']     = array(); // Init this array, for do not allow override over config.inc.php!
$config['user_level'][0]  = array('permission' => 0,
                                  'name'       => 'Disabled',
                                  'subtext'    => 'This user disabled',
                                  'notes'      => 'User complete can\'t login and use any services. Use it to block access for specific users, but not delete from DB.',
                                  'row_class'  => 'disabled',
                                  'icon'       => $config['icon']['user-delete']);
$config['user_level'][1]  = array('permission' => OBS_PERMIT_ACCESS,
                                  'name'       => 'Normal User',
                                  'subtext'    => 'This user has read access to individual entities',
                                  'notes'      => 'User can\'t see or edit anything by default. Can only see devices and entities specifically permitted.',
                                  'row_class'  => 'default',
                                  'icon'       => $config['icon']['users']);
$config['user_level'][5]  = array('permission' => OBS_PERMIT_ACCESS | OBS_PERMIT_READ,
                                  'name'       => 'Global Read',
                                  'subtext'    => 'This user has global read access',
                                  'notes'      => 'User can see all devices and entities with some security and configuration data masked, such as passwords.',
                                  'row_class'  => 'suppressed',
                                  'icon'       => $config['icon']['user-self']);
$config['user_level'][7]  = array('permission' => OBS_PERMIT_ACCESS | OBS_PERMIT_READ | OBS_PERMIT_SECURE,
                                  'name'       => 'Global Secure Read',
                                  'subtext'    => 'This user has global read access with secured info',
                                  'notes'      => 'User can see all devices and entities without any information being masked, including device configuration (supplied by e.g. RANCID).',
                                  'row_class'  => 'warning',
                                  'icon'       => $config['icon']['user-self']);
$config['user_level'][8]  = array('permission' => OBS_PERMIT_ACCESS | OBS_PERMIT_READ | OBS_PERMIT_SECURE | OBS_PERMIT_EDIT,
                                  'name'       => 'Global Secure Read / Limited Write',
                                  'subtext'    => 'This user has secure global read access with scheduled maintenence read/write.',
                                  'notes'      => 'User can see all devices and entities without any information being masked, including device configuration (supplied by e.g. RANCID). User can also add, edit and remove scheduled maintenance, group, contacts.',
                                  'row_class'  => 'warning',
                                  'icon'       => $config['icon']['user-self']);
$config['user_level'][10] = array('permission' => OBS_PERMIT_ALL,
                                  'name'       => 'Administrator',
                                  'subtext'    => 'This user has full administrative access',
                                  'notes'      => 'User can see and edit all devices and entities. This includes adding and removing devices, bills and users.',
                                  'row_class'  => 'success',
                                  'icon'       => $config['icon']['user-log']);

$config['remote_access']['ssh']    = array('name' => "SSH",    'port' => '22',   'icon' => 'oicon-application-terminal');
$config['remote_access']['telnet'] = array('name' => "Telnet", 'port' => '23',   'icon' => 'oicon-application-list');
$config['remote_access']['scp']    = array('name' => "SFTP",   'port' => '22',   'icon' => 'oicon-disk-black');
$config['remote_access']['ftp']    = array('name' => "FTP",    'port' => '21',   'icon' => 'oicon-disk');
$config['remote_access']['http']   = array('name' => "HTTP",   'port' => '80',   'icon' => 'oicon-application-icon-large');
$config['remote_access']['https']  = array('name' => "HTTPS",  'port' => '443',  'icon' => 'oicon-shield');
$config['remote_access']['rdp']    = array('name' => "RDP",    'port' => '3389', 'icon' => 'oicon-connect');
$config['remote_access']['vnc']    = array('name' => "VNC",    'port' => '5901', 'icon' => 'oicon-computer');

// Set some times needed by loads of scripts (it's dynamic, so we do it here!)
$config['time']['now']        = time();
$config['time']['fiveminute'] = $config['time']['now'] - 300;      //time() - (5 * 60);
$config['time']['fourhour']   = $config['time']['now'] - 14400;    //time() - (4 * 60 * 60);
$config['time']['sixhour']    = $config['time']['now'] - 21600;    //time() - (6 * 60 * 60);
$config['time']['twelvehour'] = $config['time']['now'] - 43200;    //time() - (12 * 60 * 60);
$config['time']['day']        = $config['time']['now'] - 86400;    //time() - (24 * 60 * 60);
$config['time']['twoday']     = $config['time']['now'] - 172800;   //time() - (2 * 24 * 60 * 60);
$config['time']['week']       = $config['time']['now'] - 604800;   //time() - (7 * 24 * 60 * 60);
$config['time']['twoweek']    = $config['time']['now'] - 1209600;  //time() - (2 * 7 * 24 * 60 * 60);
$config['time']['month']      = $config['time']['now'] - 2678400;  //time() - (31 * 24 * 60 * 60);
$config['time']['twomonth']   = $config['time']['now'] - 5356800;  //time() - (2 * 31 * 24 * 60 * 60);
$config['time']['threemonth'] = $config['time']['now'] - 8035200;  //time() - (3 * 31 * 24 * 60 * 60);
$config['time']['sixmonth']   = $config['time']['now'] - 16070400; //time() - (6 * 31 * 24 * 60 * 60);
$config['time']['year']       = $config['time']['now'] - 31536000; //time() - (365 * 24 * 60 * 60);
$config['time']['twoyear']    = $config['time']['now'] - 63072000; //time() - (2 * 365 * 24 * 60 * 60);
$config['time']['threeyear']  = $config['time']['now'] - 94608000; //time() - (3 * 365 * 24 * 60 * 60);


// Obsolete config variables
// Note, for multiarray config options use conversion with '->'
// example: $config['email']['default'] --> 'email->default'
$config['obsolete_config'] = array(); // NOT CONFIGURABLE, init
$config['obsolete_config'][] = array('old' => 'warn->ifdown',        'new' => 'frontpage->device_status->ports');
$config['obsolete_config'][] = array('old' => 'alerts->email->enable',       'new' => 'email->enable',       'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'alerts->email->default',      'new' => 'email->default',      'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'alerts->email->default_only', 'new' => 'email->default_only', 'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'alerts->email->graphs',       'new' => 'email->graphs',       'info' => 'changed since r6976');
$config['obsolete_config'][] = array('old' => 'email_backend',       'new' => 'email->backend',       'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'email_from',          'new' => 'email->from',          'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'email_sendmail_path', 'new' => 'email->sendmail_path', 'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'email_smtp_host',     'new' => 'email->smtp_host',     'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'email_smtp_port',     'new' => 'email->smtp_port',     'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'email_smtp_timeout',  'new' => 'email->smtp_timeout',  'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'email_smtp_secure',   'new' => 'email->smtp_secure',   'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'email_smtp_auth',     'new' => 'email->smtp_auth',     'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'email_smtp_username', 'new' => 'email->smtp_username', 'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'email_smtp_password', 'new' => 'email->smtp_password', 'info' => 'changed since r5787');
$config['obsolete_config'][] = array('old' => 'discovery_modules->cisco-pw', 'new' => 'discovery_modules->pseudowires', 'info' => 'changed since r6205');
$config['obsolete_config'][] = array('old' => 'discovery_modules->discovery-protocols', 'new' => 'discovery_modules->neighbours', 'info' => 'changed since r6744');
$config['obsolete_config'][] = array('old' => 'search_modules',      'new' => 'wui->search_modules', 'info' => 'changed since r7463');
$config['obsolete_config'][] = array('old' => 'discovery_modules->ipv4-addresses', 'new' => 'discovery_modules->ip-addresses', 'info' => 'changed since r7565');
$config['obsolete_config'][] = array('old' => 'discovery_modules->ipv6-addresses', 'new' => 'discovery_modules->ip-addresses', 'info' => 'changed since r7565');
$config['obsolete_config'][] = array('old' => 'location_map',        'new' => 'location->map',       'info' => 'changed since r8021');
$config['obsolete_config'][] = array('old' => 'geocoding->api_key',  'new' => 'geo_api->google->key', 'info' => 'DEPRECATED since 19.8.10000');
$config['obsolete_config'][] = array('old' => 'snmp->snmp_sysorid',  'new' => 'discovery_modules->mibs', 'info' => 'Migrated to separate module since 19.10.10091');

$config['obsolete_config'][] = array('old' => 'bad_xdp',             'new' => 'xdp->ignore_hostname',       'info' => 'changed since 20.6.10520');
$config['obsolete_config'][] = array('old' => 'bad_xdp_regexp',      'new' => 'xdp->ignore_hostname_regex', 'info' => 'changed since 20.6.10520');
$config['obsolete_config'][] = array('old' => 'bad_xdp_platform',    'new' => 'xdp->ignore_platform',       'info' => 'changed since 20.6.10520');

$config['obsolete_config'][] = [ 'old' => 'discovery_modules->cisco-vrf', 'new' => 'discovery_modules->vrf', 'info' => 'changed since 20.10.10792' ];

// do not keep in memory this setting, use get_defined_settings($key)
$config['hide_config'] = [
  // SSH related
  //'ssh_username', 'ssh_key', 'ssh_key_path', 'ssh_key_password',
  // DB related
  //'db_user', 'db_pass',
  // Auth related
  //'auth_radius_secret', 'auth_ldap_binddn', 'auth_ldap_bindpw',
  // WMI related
  //'wmi->user', 'wmi->pass',
];

// Here whitelist of base definitions keys which can be overridden by config.php file
// Note, this required only for override already exist definitions, for additions not required
$config['definitions_whitelist'] = array('os', 'mibs', 'device_types', 'rancid', 'geo_api', 'search_modules', 'rewrites', 'nicecase', 'wui');

// End of includes/definitions.inc.php
