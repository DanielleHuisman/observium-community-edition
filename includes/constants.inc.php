<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage definitions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
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
const OBS_QUOTES_STRIP = 1;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   // Strip ALL quotes from string
const OBS_QUOTES_TRIM  = 2;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           // Trim double/single quotes only from begin/end of string
const OBS_ESCAPE       = 4;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           // Escape strings or output
const OBS_DECODE_UTF8  = 8;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           // Decode ascii coded chars in string as correct UTF-8

// Bits 4-15 snmp flags
const OBS_SNMP_NUMERIC       = 16;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          // Use numeric OIDs  (-On)
const OBS_SNMP_NUMERIC_INDEX = 32; // Use numeric index (-Ob)
const OBS_SNMP_CONCAT        = 64; // Concatenate multiline snmp variable (newline chars removed)
const OBS_SNMP_ENUM          = 128; // Don't enumerate SNMP values
const OBS_SNMP_HEX           = 256; // Force HEX output (-Ox) and disable use of DISPLAY-HINT information when assigning values (-Ih).
const OBS_SNMP_TABLE         = 512; // Force Program Like output (-OX)
const OBS_SNMP_DISPLAY_HINT  = 1024; // Disables the use of DISPLAY-HINT information when assigning values (-Ih). This would then require providing the raw value.
const OBS_SNMP_TIMETICKS     = 2048; // Force TimeTicks values as raw numbers (-Ot)
const OBS_SNMP_ASCII         = 4096; // Force all string values as ASCII strings
const OBS_SNMP_NOINDEX       = 8192; // Allow to walk tables without indexes, like snmpwalk_cache_bare_oid()
const OBS_SNMP_NOINCREASE    = 16384; // Do not check returned OIDs are increasing in snmpwalk (-Cc)
const OBS_SNMP_INDEX_PARTS   = 32768; // Use this to split index parts by arrow (->), actually for strings in index part and/or for OBS_SNMP_TABLE flag

const OBS_SNMP_ALL               = OBS_QUOTES_TRIM | OBS_QUOTES_STRIP;
const OBS_SNMP_ALL_MULTILINE     = OBS_QUOTES_TRIM | OBS_SNMP_CONCAT;
const OBS_SNMP_ALL_ASCII         = OBS_QUOTES_TRIM | OBS_SNMP_ASCII;
const OBS_SNMP_ALL_UTF8          = OBS_SNMP_ALL_ASCII | OBS_SNMP_HEX | OBS_DECODE_UTF8;
const OBS_SNMP_ALL_HEX           = OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX;
const OBS_SNMP_ALL_ENUM          = OBS_SNMP_ALL | OBS_SNMP_ENUM;
const OBS_SNMP_ALL_NUMERIC       = OBS_SNMP_ALL | OBS_SNMP_NUMERIC;
const OBS_SNMP_ALL_NUMERIC_INDEX = OBS_SNMP_ALL | OBS_SNMP_NUMERIC_INDEX;
const OBS_SNMP_ALL_NOINDEX       = OBS_SNMP_ALL | OBS_SNMP_NOINDEX;
const OBS_SNMP_ALL_TABLE         = OBS_QUOTES_TRIM | OBS_SNMP_TABLE;
const OBS_SNMP_ALL_TIMETICKS     = OBS_SNMP_ALL | OBS_SNMP_TIMETICKS;

// Register snmp error code constants
const OBS_SNMP_ERROR_CACHED = -1;
const OBS_SNMP_ERROR_OK     = 0;
const OBS_SNMP_ERROR_EMPTY_RESPONSE = 1;
const OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED = 2;
const OBS_SNMP_ERROR_TOO_LONG_RESPONSE = 3;
const OBS_SNMP_ERROR_TOO_BIG_MAX_REPETITION_IN_GETBULK = 4;
const OBS_SNMP_ERROR_GETNEXT_EMPTY_RESPONSE = 5;
const OBS_SNMP_ERROR_ISSNMPABLE = 900;
const OBS_SNMP_ERROR_AUTHORIZATION_ERROR = 990;
const OBS_SNMP_ERROR_AUTHENTICATION_FAILURE = 991;
const OBS_SNMP_ERROR_UNSUPPORTED_ALGO = 992;
const OBS_SNMP_ERROR_OID_NOT_INCREASING = 993;
const OBS_SNMP_ERROR_UNKNOWN_HOST = 994;
const OBS_SNMP_ERROR_INCORRECT_ARGUMENTS = 995;
const OBS_SNMP_ERROR_MIB_OR_OID_NOT_FOUND = 996;
const OBS_SNMP_ERROR_WRONG_INDEX_IN_MIBS_DIR = 997;
const OBS_SNMP_ERROR_MIB_OR_OID_DISABLED = 998;
const OBS_SNMP_ERROR_UNKNOWN = 999;
const OBS_SNMP_ERROR_FAILED_RESPONSE = 1000;
const OBS_SNMP_ERROR_REQUEST_TIMEOUT = 1002;
const OBS_SNMP_ERROR_BULK_REQUEST_TIMEOUT = 1004;

// Bits 16-19 network flags
const OBS_DNS_A     = 65536; // Use only IPv4 dns queries
const OBS_DNS_AAAA  = 131072; // Use only IPv6 dns queries
const OBS_DNS_ALL   = OBS_DNS_A | OBS_DNS_AAAA; // Use both IPv4/IPv6 dns queries
const OBS_DNS_FIRST = 262144; // Flag for use in gethostbyname6()
const OBS_PING_SKIP = 524288; // Skip device icmp ping checks

// Common regex patterns
const OBS_PATTERN_START = '%(?:^|[\s\"\(=])';       // Beginning of any pattern, matched string can start from newline, space, double quote, opening parenthesis, equal sign
const OBS_PATTERN_END   = '(?:[\s\"\),]|[\:\.]\ |\.?$)%i'; // End of any pattern, matched string can ended with endline, space, double quote, closing parenthesis, comma, dot
const OBS_PATTERN_END_U = OBS_PATTERN_END . 'u';    // ++Unicode

// IPv4 string in group \1 or 'ipv4'
const OBS_PATTERN_IPV4      = '(?<ipv4>(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9]))';
const OBS_PATTERN_IPV4_FULL = OBS_PATTERN_START . OBS_PATTERN_IPV4 . OBS_PATTERN_END;
// IPv4 netmask string in group \1 or 'ipv4_mask'
const OBS_PATTERN_IPV4_MASK = '(?<ipv4_mask>(?:0|128|192|224|240|248|252|254)\.0\.0\.0|255\.(?:0|128|192|224|240|248|252|254)\.0\.0|255\.255\.(?:0|128|192|224|240|248|252|254)\.0|255\.255\.255\.(?:0|128|192|224|240|248|252|254|255))';
// IPv4 inverse netmask string in group \1 or 'ipv4_inverse_mask'
const OBS_PATTERN_IPV4_INVERSE_MASK = '(?<ipv4_inverse_mask>(?:255|127|63|31|15|7|3|1|0)\.255\.255\.255|0\.(?:255|127|63|31|15|7|3|1|0)\.255\.255|0\.0\.(?:255|127|63|31|15|7|3|1|0)\.255|0\.0\.0\.(?:255|127|63|31|15|7|3|1|0))';
// IPv4 network string in group \1 or 'ipv4_network', additionally 'ipv4', 'ipv4_prefix' or 'ipv4_mask' or 'ipv4_inverse_mask'
const OBS_PATTERN_IPV4_NET      = '(?<ipv4_network>' . OBS_PATTERN_IPV4 . '\/(?:(?<ipv4_prefix>3[0-2]|[1-2][0-9]|[0-9])|' . OBS_PATTERN_IPV4_MASK . '|' . OBS_PATTERN_IPV4_INVERSE_MASK . '))';
const OBS_PATTERN_IPV4_NET_FULL = OBS_PATTERN_START . OBS_PATTERN_IPV4_NET . OBS_PATTERN_END;

// IPv6 string in group \1 or 'ipv6'
const OBS_PATTERN_IPV6      = '(?<ipv6>(?:(?:(?:[a-f\d]{1,4}:){5}[a-f\d]{1,4}|(?:[a-f\d]{1,4}:){4}:[a-f\d]{1,4}|(?:[a-f\d]{1,4}:){3}(?::[a-f\d]{1,4}){1,2}|(?:[a-f\d]{1,4}:){2}(?::[a-f\d]{1,4}){1,3}|[a-f\d]{1,4}:(?::[a-f\d]{1,4}){1,4}|(?:[a-f\d]{1,4}:){1,5}|:(?::[a-f\d]{1,4}){1,5}|:):(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])|(?:[a-f\d]{1,4}:){7}[a-f\d]{1,4}|(?:[a-f\d]{1,4}:){6}:[a-f\d]{1,4}|(?:[a-f\d]{1,4}:){5}(?::[a-f\d]{1,4}){1,2}|(?:[a-f\d]{1,4}:){4}(?::[a-f\d]{1,4}){1,3}|(?:[a-f\d]{1,4}:){3}(?::[a-f\d]{1,4}){1,4}|(?:[a-f\d]{1,4}:){2}(?::[a-f\d]{1,4}){1,5}|[a-f\d]{1,4}:(?::[a-f\d]{1,4}){1,6}|(?:[a-f\d]{1,4}:){1,7}:|:(?::[a-f\d]{1,4}){1,7}|::))';
const OBS_PATTERN_IPV6_FULL = OBS_PATTERN_START . OBS_PATTERN_IPV6 . OBS_PATTERN_END;
// IPv6 network string in group \1 or 'ipv6_network', additionally 'ipv6', 'ipv6_prefix'
const OBS_PATTERN_IPV6_NET      = '(?<ipv6_network>' . OBS_PATTERN_IPV6 . '\/(?<ipv6_prefix>12[0-8]|1[0-1][0-9]|[0-9]{1,2}))';
const OBS_PATTERN_IPV6_NET_FULL = OBS_PATTERN_START . OBS_PATTERN_IPV6_NET . OBS_PATTERN_END;

// IPv4 or IPv6 string in group \1 or 'ip'
const OBS_PATTERN_IP      = '(?<ip>' . OBS_PATTERN_IPV4 . '|' . OBS_PATTERN_IPV6 . ')';
const OBS_PATTERN_IP_FULL = OBS_PATTERN_START . OBS_PATTERN_IP . OBS_PATTERN_END;
// IPv4 or IPv6 network string in group \1 or 'ip_network'
const OBS_PATTERN_IP_NET      = '(?<ip_network>' . OBS_PATTERN_IPV4_NET . '|' . OBS_PATTERN_IPV6_NET . ')';
const OBS_PATTERN_IP_NET_FULL = OBS_PATTERN_START . OBS_PATTERN_IP_NET . OBS_PATTERN_END;

// MAC string in group \1 or 'mac'
const OBS_PATTERN_MAC      = '(?<mac>(?:[a-f\d]{1,2}(?:\:[a-f\d]{1,2}){5}|[a-f\d]{2}(?:\-[a-f\d]{2}){5}|[a-f\d]{2}(?:\ [a-f\d]{2}){5}|[a-f\d]{4}(?:\.[a-f\d]{4}){2}|(?:0x)?[a-f\d]{12}))';
const OBS_PATTERN_MAC_FULL = OBS_PATTERN_START . OBS_PATTERN_MAC . OBS_PATTERN_END;

// FQDN string in group \1 or 'domain'
//define('OBS_PATTERN_FQDN',      '(?<domain>(?:(?:(?:xn--)?[a-z0-9_]+(?:\-[a-z0-9_]+)*\.)+(?:[a-z]{2,}|xn--[a-z0-9]{4,}))|localhost)'); // Alternative, less correct
//define('OBS_PATTERN_FQDN',      '(?<domain>(?:(?:(?=[a-z0-9\-_]{1,63}\.)(?:xn--)?[a-z0-9_]+(?:\-[a-z0-9_]+)*\.)+(?:[a-z]{2,63}|xn--[a-z0-9]{4,}))|localhost)'); // Punicode, Non-unicode
const OBS_PATTERN_FQDN      = '(?<domain>(?:(?:(?=[\p{L}\d\-_]{1,63}\.)(?:xn--)?[\p{L}\d_]+(?:\-[\p{L}\d_]+)*\.)+(?:[\p{L}]{2,63}|xn--[a-z\d]{4,}))|localhost)';
const OBS_PATTERN_FQDN_FULL = OBS_PATTERN_START . OBS_PATTERN_FQDN . OBS_PATTERN_END_U;

// pattern for email only (without Name, ie: user@domain.name)
// Email string in group \1 or 'email', additional groups: 'user', 'domain'
const OBS_PATTERN_EMAIL      = '(?<email>(?<user>[\p{L}\d\.\'_\%\+\-]{1,63}|\"[\p{L}\d\.\'_\%\+\-\ \\\\]{1,63}\")@' . OBS_PATTERN_FQDN . ')';
const OBS_PATTERN_EMAIL_FULL = OBS_PATTERN_START . OBS_PATTERN_EMAIL . OBS_PATTERN_END_U;
// pattern for Full email with Name (ie: "My Name" <user@domain.name>), but name is optional
// Long Email string in group \1 or 'email_long', additional groups: 'name', 'email', 'user', 'domain'
const OBS_PATTERN_EMAIL_LONG      = '(?<email_long>(?<name>[\"\'][\p{L}\d\.\'_\%\+\-\ \\\\]+[\"\']|(?:[\p{L}\d\.\'_\%\+\-]+\ )*[\p{L}\d\.\'_\%\+\-]+)?\s*<' . OBS_PATTERN_EMAIL . '>)';
const OBS_PATTERN_EMAIL_LONG_FULL = OBS_PATTERN_START . OBS_PATTERN_EMAIL_LONG . OBS_PATTERN_END_U;

//define('OBS_PATTERN_URL',       '(?<url>(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@|\d{1,3}(?:\.\d{1,3}){3}|(?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?(?:[^\s]*)?)');
//define('OBS_PATTERN_URL_FULL',  OBS_PATTERN_START . OBS_PATTERN_URL . OBS_PATTERN_END_U);

// SNMP HEX-STRING group \1 or 'hex'
const OBS_PATTERN_SNMP_HEX = '(?<hex>[a-f\d]{2}(\ +[a-f\d]{2})*)\ ?';
// SNMP NUMERIC OID group \1 or 'oid_num'
const OBS_PATTERN_SNMP_OID_NUM = '/^(?<oid_num>\.?(\d+(?:\.\d+)+))$/';

// Split graph type with subtype
const OBS_PATTERN_GRAPH_TYPE = '/^(?<type>[a-z\d\-]+)_(?<subtype>[a-z\d\-_]+)/i';
// timestamps formatted as 'Y-m-d H:i:s'
const OBS_PATTERN_TIMESTAMP   = '/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/';
const OBS_PATTERN_WINDOWSTIME = '/^(?<year>\d{4})(?<month>\d{2})(?<day>\d{2})(?<hour>\d{2})(?<min>\d{2})(?<sec>\d{2}\.\d+)(\+\d+)?$/';
const OBS_PATTERN_RRDTIME     = '/^(\-?\d+|[\-\+]\d+[dwmysh]|now)$/i';
const OBS_PATTERN_LATLON      = '/(?:^|[\[(])\s*[\'"]?(?<lat>[+\-]?\d+(?:\.\d+)*)[\'"]?\s*[,:; ]\s*[\'"]?(?<lon>[+\-]?\d+(?:\.\d+)*)[\'"]?\s*(?:[\])]|$)/';
const OBS_PATTERN_LATLON_ALT  = '/\s*\|[\'"]?(?<lat>[+\-]?\d+(?:\.\d+)*)[\'"]?\s*\|\s*[\'"]?(?<lon>[+\-]?\d+(?:\.\d+)*)[\'"]?\s*$/';

// patterns for validating kind of used data
const OBS_PATTERN_ALPHA    = '/^[\w\.\-]+$/';
const OBS_PATTERN_NOPRINT  = '/[^\p{L}\p{N}\p{P}\p{S} ]/u'; // Non-printable UTF8 chars
const OBS_PATTERN_NOLATIN  = '/[^\p{Common}\p{Latin}]/u';   // Non Latin (UTF8?) chars
const OBS_PATTERN_VAR_NAME = '/^\w[\w\s\.\-+]*(\[[\w\.\-+]*\])*$/';
const OBS_PATTERN_REGEXP   = '/^\s*(?<delimiter>[\/!#@%+])(?<pattern>.+)(\1)(?<modifiers>[imsxu]+)?\s*$/s'; // Detect string is common pattern
const OBS_PATTERN_PATH_UNIX = '!^\/$|(^(?=\/)|^\.|^\.\.)(\/(?=[^/\0\n\r])[^/\0\n\r]+)*\/?$!'; // https://stackoverflow.com/questions/6416065/c-sharp-regex-for-file-paths-e-g-c-test-test-exe/42036026#42036026
const OBS_PATTERN_PATH_WIN = '@(^([a-z]|[A-Z]):(?=\\(?![\0-\37<>:"/\\|?*])|\/(?![\0-\37<>:"/\\|?*])|$)|^\\(?=[\\\/][^\0-\37<>:"/\\|?*]+)|^(?=(\\|\/)$)|^\.(?=(\\|\/)$)|^\.\.(?=(\\|\/)$)|^(?=(\\|\/)[^\0-\37<>:"/\\|?*]+)|^\.(?=(\\|\/)[^\0-\37<>:"/\\|?*]+)|^\.\.(?=(\\|\/)[^\0-\37<>:"/\\|?*]+))((\\|\/)[^\0-\37<>:"/\\|?*]+|(\\|\/)$)*()$@';
const OBS_PATTERN_XSS      = '!((^|<.+=.*?)\s*(J\s*A\s*V\s*A\s*)?S\s*C\s*R\s*I\s*P\s*T\s*:|<\s*/?\s*S\s*C\s*R\s*I\s*P\s*T\s*>|(<\s*\w+.*[\s\/&](o\s*n[\w\s]+|s\s*c\s*r\s*i\s*p\s*t))\s*=|<\s*(i\s*f\s*r\s*a\s*m\s*e|s\s*c\s*r\s*i\s*p\s*t).+s\s*r\s*c\s*=|<.*?=\s*e\s*v\s*a\s*l\s*\(|\s*e\s*v\s*a\s*l\s*\(.*?(a\s*t\s*o\s*b|f\s*r\s*o\s*m\s*C\s*h\s*a\s*r\s*C\s*o\s*d\s*e)\s*\()!i';

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

// Minimum supported versions
// NOTE. Minimum supported versions equals to latest in minimum supported RHEL (7.x at 02.2021)
const OBS_MIN_PHP_VERSION = '7.1.30';                                                                             // PHP (30 May 2019, https://www.php.net/releases/index.php)
//define('OBS_MIN_PYTHON2_VERSION', '2.7.12'); // Python 2 (26 June 2016, https://www.python.org/doc/versions/)
const OBS_MIN_PYTHON2_VERSION = '2.8.0';                                                                          // Python 2.7.18 is the last release of Python 2 (EOL 2020-01-01)
const OBS_MIN_PYTHON3_VERSION = '3.6.6';                                                                          // Python 3 (27 June 2016, https://www.python.org/doc/versions/)
//const OBS_MIN_MYSQL_VERSION   = '5.6.5';  // https://stackoverflow.com/questions/4489548/why-there-can-be-only-one-timestamp-column-with-current-timestamp-in-default-cla
const OBS_MIN_MYSQL_VERSION = '5.7.0';                                                                            // JSON data type was added in 5.7
//const OBS_MIN_MARIADB_VERSION = '5.5.68'; // 5.5.68 last in RHEL/CentOS 7 and 5.5.63 in Ubuntu LTS 14.04
const OBS_MIN_MARIADB_VERSION = '10.2.7';                                                                         // JSON data type was added in 10.2.7: https://mariadb.com/kb/en/json-data-type/
const OBS_MIN_RRD_VERSION     = '1.4.8';                                                                          // last in RHEL/CentOS 7
//define('OBS_MIN_RRD_VERSION',     '1.5.5');  // RRDTool (10 Nov 2015, https://github.com/oetiker/rrdtool-1.x/tags)

// Minimum possible unixtime, only for validate passed unixtime
//define('OBS_MIN_UNIXTIME', 946684800); // 01/01/2000 @ 12:00am (UTC), just in most cases unixtime not possible less than this date (net-snmp released in 2000, any network device not have uptime longest)
const OBS_MIN_UNIXTIME = 504921600;                                                                               // 01/01/1986 @ 12:00am (UTC), not network devices produces before this date :)

// OBSERVIUM URLs
const OBSERVIUM_URL      = 'https://www.observium.org';
const OBSERVIUM_DOCS_URL = 'https://docs.observium.org';
const OBSERVIUM_CHANGELOG_URL = 'https://changelog.observium.org';
const OBSERVIUM_BUG_URL  = 'https://jira.observium.org';
const OBSERVIUM_MIBS_URL = 'https://mibs.observium.org/mib';

// DB constants
// Unit test not used sql connect and does not include includes/observium.inc.php
if (defined('__PHPUNIT_PHAR__')) {
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
const OBS_DB_EXTENSION = 'mysqli';
const OBS_DB_LINK      = 'observium_link'; // Global variable name for DB link identifier, required for mysqli

// Set default Include path
set_include_path($config['install_dir'] . "/libs/pear" . PATH_SEPARATOR . // Still required Pear path
                 $config['install_dir'] . "/libs" . PATH_SEPARATOR .
                 get_include_path());

// Set default paths.
$config['install_dir'] = rtrim($config['install_dir'], ' /');
if (!isset($config['html_dir'])) {
    $config['html_dir'] = $config['install_dir'] . '/html';
} else {
    $config['html_dir'] = rtrim($config['html_dir'], ' /');
}
if (!isset($config['rrd_dir'])) {
    $config['rrd_dir'] = $config['install_dir'] . '/rrd';
} else {
    $config['rrd_dir'] = rtrim($config['rrd_dir'], ' /');
}

// Fix RRD Directory path to always have a trailing slash so that it works nicely with rrdcached
//$config['rrd_dir'] = fix_path_slash($config['rrd_dir']);

if (!isset($config['log_dir'])) {
    $config['log_dir'] = $config['install_dir'] . '/logs';
} else {
    $config['log_dir'] = rtrim($config['log_dir'], ' /');
}
if (!isset($config['log_file'])) {
    $config['log_file'] = $config['log_dir'] . '/observium.log';
} // FIXME should not be absolute path, look for where it is used
if (!isset($config['temp_dir'])) {
    $config['temp_dir'] = '/tmp';
} else {
    $config['temp_dir'] = rtrim($config['temp_dir'], ' /');
}
if (!isset($config['mib_dir'])) {
    $config['mib_dir'] = $config['install_dir'] . '/mibs';
} else {
    $config['mib_dir'] = rtrim($config['mib_dir'], ' /');
}
if (!isset($config['template_dir'])) {
    $config['template_dir'] = $config['install_dir'] . '/templates';
} else {
    $config['template_dir'] = rtrim($config['template_dir'], ' /');
}
if (!isset($config['cache_dir'])) {
    $config['cache_dir'] = $config['temp_dir'] . '/observium_cache';
} else {
    $config['cache_dir'] = rtrim($config['cache_dir'], ' /');
}
#if (!isset($config['nagplug_dir']))   { $config['nagplug_dir']   = '/usr/lib/nagios/plugins'; }
#else                                  { $config['nagplug_dir']   = rtrim($config['nagplug_dir'], ' /'); }

// Collect php errors mostly for catch php8 errors
if ($config['php_debug'] && !defined('__PHPUNIT_PHAR__')) {
    ini_set('error_reporting', E_ALL ^ E_DEPRECATED ^ E_NOTICE ^ E_WARNING ^ E_STRICT);
    ini_set("error_log", $config['log_dir'] . "/php-errors.log");
}