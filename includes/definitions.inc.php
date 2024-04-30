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

$def_start = microtime(TRUE);
$def_pre   = memory_get_usage();

$definition_loaded = [ 'version.inc.php' ];
require($config['install_dir'] . '/includes/definitions/version.inc.php');

// Here whitelist of base definitions keys which can be overridden by config.php file
// Note, this required only for override already exist definitions, for additions not required
$config['definitions_whitelist'] = [ 'os', 'mibs', 'device_types', 'probes', 'rancid', 'geo_api', 'search_modules', 'rewrites', 'nicecase', 'wui' ];

if (defined('OBS_DEFINITIONS_SKIP') && OBS_DEFINITIONS_SKIP === TRUE) {
    // Do not load full definitions in observium-wrapper, while not required at this point
    return;
}

// Community specific definition
if (OBSERVIUM_EDITION === 'community' &&
    is_file($config['install_dir'] . '/includes/definitions/definitions.dat')) {
    //var_dump($config);
    $config_tmp = file_get_contents($config['install_dir'] . '/includes/definitions/definitions.dat');
    $config_tmp = gzuncompress($config_tmp);
    $config_tmp = safe_unserialize($config_tmp);
    //var_dump($config_tmp);
    if (is_array($config_tmp) && isset($config_tmp['os'])) { // Simple check for passed correct data
        $config = array_merge($config, $config_tmp);
        $definition_loaded[] = 'definitions.dat';
    }
    unset($config_tmp);
}

$definition_files = [
    // file       => loading
    'os'          => TRUE, //is_cli() || !is_ajax(), // OS definitions
    'wui'         => TRUE,   // Web UI specific definitions
    'graphtypes'  => TRUE, //!is_cli() && !is_ajax(), // Graph Type definitions
    'rrdtypes'    => TRUE,   // RRD Type definitions
    'entities'    => TRUE,   // Entity type definitions
    'rewrites'    => TRUE,   // Rewriting array definitions
    'mibs'        => TRUE, //is_cli() || !is_ajax(), // MIB definitions
    'vendors'     => TRUE, //is_cli() || !is_ajax(), // Vendor/manufacturer definitions
    'geo'         => TRUE,   // Geolocation api definitions
    'vm'          => TRUE,   // Virtual Machine definitions
    'transports'  => TRUE,   // Alerting transport definitions
    'apis'        => TRUE,   // External APIs definitions
    'apps'        => TRUE,   // Apps system definitions
];

//echo "definitions was last modified: " . date ("F d Y H:i:s.", filemtime('/opt/observium/includes/definitions/')) . "\n";

foreach ($definition_files as $file => $valid) {
    $file .= '.inc.php';
    if ($valid && is_file($config['install_dir'] . '/includes/definitions/' . $file)) {
        $definition_loaded[] = $file;
        include($config['install_dir'] . '/includes/definitions/' . $file);
    }
}

// IP types
// https://www.iana.org/assignments/iana-ipv6-special-registry/iana-ipv6-special-registry.xhtml
$config['ip_types']['unspecified']   = [
  'networks'    => [ '0.0.0.0', '::/128' ],
  'name'        => 'Unspecified', 'subtext' => 'Example: ::/128, 0.0.0.0',
  'label-class' => 'error',
  'descr'       => 'This address may only be used as a source address by an initialising host before it has learned its own address. Example: ::/128, 0.0.0.0'
];
$config['ip_types']['loopback']      = [
  'networks'    => [ '127.0.0.0/8', '::1/128' ],
  'name'        => 'Loopback', 'subtext' => 'Example: ::1/128, 127.0.0.1',
  'label-class' => 'info',
  'descr'       => 'This address is used when a host talks to itself. Example: ::1/128, 127.0.0.1'
];
$config['ip_types']['private']       = [
  'networks'    => [ '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'fc00::/7' ],
  'name'        => 'Private Local Addresses', 'subtext' => 'Example: fdf8:f53b:82e4::53, 192.168.0.1',
  'label-class' => 'warning',
  'descr'       => 'These addresses are reserved for local use in home and enterprise environments and are not public address space. Example: fdf8:f53b:82e4::53, 192.168.0.1'
];
$config['ip_types']['cgnat']       = [
    'networks'    => [ '100.64.0.0/10' ],
    'name'        => 'Carrier Grade NAT (CGNAT)', 'subtext' => 'Example: 100.80.76.30',
    'label-class' => 'warning',
    'descr'       => 'Carrier Grade NAT is expressly reserved as a range that does not conflict with either the private network address ranges or the public Internet ranges. Example: 100.80.76.30'
];
$config['ip_types']['multicast']     = [
  'networks'    => [ '224.0.0.0/4', 'ff00::/8' ],
  'name'        => 'Multicast', 'subtext' => 'Example: ff01:0:0:0:0:0:0:2, 224.0.0.1',
  'label-class' => 'inverse',
  'descr'       => 'These addresses are used to identify multicast groups. Example: ff01:0:0:0:0:0:0:2, 224.0.0.1'
];
$config['ip_types']['link-local']    = [
  'networks'    => [ '169.254.0.0/16', 'fe80::/10' ],
  'name'        => 'Link-Local Addresses', 'subtext' => 'Example: fe80::200:5aee:feaa:20a2, 169.254.3.1',
  'label-class' => 'suppressed',
  'descr'       => 'These addresses are used on a single link or a non-routed common access network, such as an Ethernet LAN. Example: fe80::200:5aee:feaa:20a2, 169.254.3.1'
];
$config['ip_types']['ipv4mapped']    = [
  'networks'    => [ '::ffff/96' ],
  'name'        => 'IPv6 IPv4-Mapped', 'subtext' => 'Example: ::ffff:192.0.2.47',
  'label-class' => 'primary',
  'descr'       => 'These addresses are used to embed IPv4 addresses in an IPv6 address. Example: 64:ff9b::192.0.2.33'
];
$config['ip_types']['ipv4embedded']  = [
  'networks'    => [ '64:ff9b::/96' ],
  'name'        => 'IPv6 IPv4-Embedded', 'subtext' => 'Example: ::ffff:192.0.2.47',
  'label-class' => 'primary',
  'descr'       => 'IPv4-converted IPv6 addresses and IPv4-translatable IPv6 addresses. Example: 64:ff9b::192.0.2.33'
];
$config['ip_types']['6to4']          = [
  'networks'    => [ '192.88.99.0/24', '2002::/16' ],
  'name'        => 'IPv6 6to4', 'subtext' => 'Example: 2002:cb0a:3cdd:1::1, 192.88.99.1',
  'label-class' => 'primary',
  'descr'       => 'A 6to4 gateway adds its IPv4 address to this 2002::/16, creating a unique /48 prefix. Example: 2002:cb0a:3cdd:1::1, 192.88.99.1'
];
$config['ip_types']['documentation'] = [
  'networks'    => [ '192.0.2.0/24', '198.51.100.0/24', '203.0.113.0/24', '2001:db8::/32' ],
  'name'        => 'Documentation', 'subtext' => 'Example: 2001:db8:8:4::2, 203.0.113.1',
  'label-class' => 'primary',
  'descr'       => 'These addresses are used in examples and documentation. Example: 2001:db8:8:4::2, 203.0.113.1'
];
$config['ip_types']['teredo']        = [
  'networks'    => [ '2001:0000::/32' ],
  'name'        => 'IPv6 Teredo', 'subtext' => 'Example: 2001:0000:4136:e378:8000:63bf:3fff:fdd2',
  'label-class' => 'primary',
  'descr'       => 'This is a mapped address allowing IPv6 tunneling through IPv4 NATs. The address is formed using the Teredo prefix, the servers unique IPv4 address, flags describing the type of NAT, the obfuscated client port and the client IPv4 address, which is probably a private address. Example: 2001:0000:4136:e378:8000:63bf:3fff:fdd2'
];
$config['ip_types']['benchmark']     = [
  'networks'    => [ '198.18.0.0/15', '2001:0002::/48' ],
  'name'        => 'Benchmarking', 'subtext' => 'Example: 2001:0002:6c::430, 198.18.0.1',
  'label-class' => 'error',
  'descr'       => 'These addresses are reserved for use in documentation. Example: 2001:0002:6c::430, 198.18.0.1'
];
$config['ip_types']['orchid']        = [
  'networks'    => [ '2001:0010::/28', '2001:0020::/28' ],
  'name'        => 'IPv6 Orchid', 'subtext' => 'Example: 2001:10:240:ab::a',
  'label-class' => 'primary',
  'descr'       => 'These addresses are used for a fixed-term experiment. Example: 2001:10:240:ab::a'
];
$config['ip_types']['reserved']      = [
    'networks' => [ '192.0.0.0/24' ],
    'name'        => 'Reserved', 'subtext' => 'Address in reserved address space',
    'label-class' => 'error',
    'descr'       => 'Reserved address space'
];
$config['ip_types']['broadcast']     = [
    'networks' => [ '255.255.255.255/32' ],
    'name'        => 'IPv4 Broadcast', 'subtext' => 'Example: 255.255.255.255',
    'label-class' => 'disabled',
    'descr'       => 'IPv4 broadcast address. Example: 255.255.255.255'
];
$config['ip_types']['anycast']       = [
    //'networks' => [],
    'name'        => 'Anycast',
    'label-class' => 'primary',
    'descr'       => 'Anycast is a network addressing and routing methodology in which a single destination address has multiple routing paths to two or more endpoint destinations.'
];
// Keep this at last!
$config['ip_types']['unicast'] = [
  'networks'    => [ '2000::/3' ], // 'networks' => [ '0.0.0.0/0', '2000::/3' ],'
  'name'        => 'Global Unicast', 'subtext' => 'Example: 2a02:408:7722::, 80.94.60.2', 'disabled' => 1,
  'label-class' => 'success',
  'descr'       => 'Global Unicast addresses. Example: 2a02:408:7722::, 80.94.60.2'
];

// Syslog colour and name translation

$config['syslog']['priorities'][0] = ['name' => 'emergency', 'color' => '#D94640', 'label-class' => 'inverse', 'row-class' => 'error', 'emoji' => 'red_circle'];
$config['syslog']['priorities'][1] = ['name' => 'alert', 'color' => '#D94640', 'label-class' => 'delayed', 'row-class' => 'error', 'emoji' => 'red_circle'];
$config['syslog']['priorities'][2] = ['name' => 'critical', 'color' => '#D94640', 'label-class' => 'error', 'row-class' => 'error', 'emoji' => 'red_circle'];
$config['syslog']['priorities'][3] = ['name' => 'error', 'color' => '#E88126', 'label-class' => 'error', 'row-class' => 'error', 'emoji' => 'red_circle'];
$config['syslog']['priorities'][4] = ['name' => 'warning', 'color' => '#F2CA3F', 'label-class' => 'warning', 'row-class' => 'warning', 'emoji' => 'large_yellow_circle'];
$config['syslog']['priorities'][5] = ['name' => 'notification', 'color' => '#107373', 'label-class' => 'success', 'row-class' => 'recovery', 'emoji' => 'large_orange_circle']; // large_green_circle
$config['syslog']['priorities'][6] = ['name' => 'informational', 'color' => '#499CA6', 'label-class' => 'primary', 'row-class' => '', 'emoji' => 'large_blue_circle'];          //'row-class' => 'info');
$config['syslog']['priorities'][7] = ['name' => 'debugging', 'color' => '#5AA637', 'label-class' => 'suppressed', 'row-class' => 'suppressed', 'emoji' => 'large_purple_circle'];

for ($i = 8; $i < 16; $i++) {
    $config['syslog']['priorities'][$i] = ['name' => 'other', 'color' => '#D2D8F9', 'label-class' => 'disabled', 'row-class' => 'disabled', 'emoji' => 'large_orange_circle'];
}

// https://tools.ietf.org/html/draft-ietf-netmod-syslog-model-14
$config['syslog']['facilities'][0]  = ['name' => 'kern', 'descr' => 'kernel messages'];
$config['syslog']['facilities'][1]  = ['name' => 'user', 'descr' => 'user-level messages'];
$config['syslog']['facilities'][2]  = ['name' => 'mail', 'descr' => 'mail system'];
$config['syslog']['facilities'][3]  = ['name' => 'daemon', 'descr' => 'system daemons'];
$config['syslog']['facilities'][4]  = ['name' => 'auth', 'descr' => 'security/authorization messages'];
$config['syslog']['facilities'][5]  = ['name' => 'syslog', 'descr' => 'messages generated internally by syslogd'];
$config['syslog']['facilities'][6]  = ['name' => 'lpr', 'descr' => 'line printer subsystem'];
$config['syslog']['facilities'][7]  = ['name' => 'news', 'descr' => 'network news subsystem'];
$config['syslog']['facilities'][8]  = ['name' => 'uucp', 'descr' => 'UUCP subsystem'];
$config['syslog']['facilities'][9]  = ['name' => 'cron', 'descr' => 'clock daemon'];
$config['syslog']['facilities'][10] = ['name' => 'authpriv', 'descr' => 'security/authorization messages'];
$config['syslog']['facilities'][11] = ['name' => 'ftp', 'descr' => 'FTP daemon'];
$config['syslog']['facilities'][12] = ['name' => 'ntp', 'descr' => 'NTP subsystem'];
$config['syslog']['facilities'][13] = ['name' => 'audit', 'descr' => 'log audit'];
$config['syslog']['facilities'][14] = ['name' => 'console', 'descr' => 'log alert'];
$config['syslog']['facilities'][15] = ['name' => 'cron2', 'descr' => 'clock daemon'];
$config['syslog']['facilities'][16] = ['name' => 'local0', 'descr' => 'local use 0 (local0)'];
$config['syslog']['facilities'][17] = ['name' => 'local1', 'descr' => 'local use 1 (local1)'];
$config['syslog']['facilities'][18] = ['name' => 'local2', 'descr' => 'local use 2 (local2)'];
$config['syslog']['facilities'][19] = ['name' => 'local3', 'descr' => 'local use 3 (local3)'];
$config['syslog']['facilities'][20] = ['name' => 'local4', 'descr' => 'local use 4 (local4)'];
$config['syslog']['facilities'][21] = ['name' => 'local5', 'descr' => 'local use 5 (local5)'];
$config['syslog']['facilities'][22] = ['name' => 'local6', 'descr' => 'local use 6 (local6)'];
$config['syslog']['facilities'][23] = ['name' => 'local7', 'descr' => 'local use 7 (local7)'];

// Alert severities (emoji used _only_ as notification icon)
// Recover emoji is white_check_mark
$config['alerts']['severity']['crit'] = [ 'name' => 'Critical', 'color' => '#D94640', 'label-class' => 'error', 'row-class' => 'error', 'icon' => $config['icon']['critical'], 'emoji' => 'fire' ];
$config['alerts']['severity']['warn'] = [ 'name' => 'Warning',  'color' => '#F2CA3F', 'label-class' => 'warning', 'row-class' => 'warning', 'icon' => $config['icon']['warning'], 'emoji' => 'warning' ];
//$config['alerts']['severity']['info'] = [ 'name' => 'Informational', 'color' => '#499CA6', 'label-class' => 'primary', 'row-class' => 'info',    'icon' => $config['icon']['informational'], 'emoji' => 'information_source' ];

// Possible transports for net-snmp, used for enumeration in several functions
$config['snmp']['transports'] = ['udp', 'udp6', 'tcp', 'tcp6'];

// 'count' is min total errors count, after which autodisable this MIB/oid pair
// 'rate' is min total rate (per poll), after which autodisable this MIB/oid pair
// note, rate not fully correct after server reboot (it will less than really)
$config['snmp']['errorcodes'][-1] = [
  'reason' => 'Cached',                 // snmp really not requested, but gets from cache
  'name'   => 'OBS_SNMP_ERROR_CACHED',
  'msg'    => ''
];
$config['snmp']['errorcodes'][0]  = [
  'reason' => 'OK',
  'name'   => 'OBS_SNMP_ERROR_OK',
  'msg'    => ''
];

// [1-99] Non critical
$config['snmp']['errorcodes'][1] = [
  'reason' => 'Empty response',         // exitcode = 0, but not have any data
  'count'  => 288,                      // 288 with rate 1/poll ~ 1 day
  'rate'   => 0.9,
  'name'   => 'OBS_SNMP_ERROR_EMPTY_RESPONSE',
  'msg'    => ''
];
$config['snmp']['errorcodes'][2] = [
  'reason' => 'Request not completed',  // Snmp output return correct data, but stopped by some reason (timeout, network error)
  'name'   => 'OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED',
  'msg'    => ''
];
$config['snmp']['errorcodes'][3] = [
  'reason' => 'Too long response',      // Not empty output, but exitcode = 1 and runtime > 10
  'name'   => 'OBS_SNMP_ERROR_TOO_LONG_RESPONSE',
  'msg'    => ''
];
$config['snmp']['errorcodes'][4] = [
  'reason' => 'Too big max-repetition in GETBULK', // Not empty output, but exitcode = 2 and stderr "Reason: (tooBig)"
  'count'  => 2880,                     // 2880 with rate 1/poll ~ 10 day
  'rate'   => 0.9,
  'name'   => 'OBS_SNMP_ERROR_TOO_BIG_MAX_REPETITION_IN_GETBULK',
  'msg'    => 'WARNING! %command% did not complete. Try to increase SNMP timeout or decrease SNMP Max Repetitions on the device properties page or set to 0 to not use bulk snmp commands.'
];
$config['snmp']['errorcodes'][5] = [
  'reason' => 'GETNEXT empty response', // Not empty output, SNMPGETNEXT returned different Oid
  'count'  => 288,                      // 288 with rate 1/poll ~ 1 day
  'rate'   => 0.9,
  'name'   => 'OBS_SNMP_ERROR_GETNEXT_EMPTY_RESPONSE',
  'msg'    => ''
];

// [900-999] Critical errors, but this is incorrect auth or config or missed files on client side
$config['snmp']['errorcodes'][900] = [
  'reason' => 'isSNMPable',             // Device up/down test, not used for counting
  'name'   => 'OBS_SNMP_ERROR_ISSNMPABLE',
  'msg'    => ''
];
$config['snmp']['errorcodes'][990] = [
    'reason' => 'Authorization Error', // Snmp access denied to that object
    'name'   => 'OBS_SNMP_ERROR_AUTHORIZATION_ERROR',
    'msg'    => ''
];
$config['snmp']['errorcodes'][991] = [
  'reason' => 'Authentication failure', // Snmp auth errors
  'name'   => 'OBS_SNMP_ERROR_AUTHENTICATION_FAILURE',
  'msg'    => ''
];
$config['snmp']['errorcodes'][992] = [
  'reason' => 'Unsupported authentication or privacy protocol', // Snmp auth errors
  'name'   => 'OBS_SNMP_ERROR_UNSUPPORTED_ALGO',
  'msg'    => 'ERROR! Unsupported SNMPv3 authentication or privacy protocol detected. Newer version of net-snmp required. Please read [FAQ](' .
              OBSERVIUM_DOCS_URL . '/faq/#snmpv3-strong-authentication-or-encryption){target=_blank}.'
];
$config['snmp']['errorcodes'][993] = [
  'reason' => 'OID not increasing',     // OID not increasing
  'name'   => 'OBS_SNMP_ERROR_OID_NOT_INCREASING',
  'msg'    => 'WARNING! %command% ended prematurely due to an error [%reason%] on MIB::Oid [%mib%::%oid%]. Try to use -Cc option for %command% command.'
];
$config['snmp']['errorcodes'][994] = [
  'reason' => 'Unknown host',           // Unknown host
  'name'   => 'OBS_SNMP_ERROR_UNKNOWN_HOST',
  'msg'    => ''
];
$config['snmp']['errorcodes'][995] = [
  'reason' => 'Incorrect arguments',    // Incorrect arguments passed to snmpcmd
  'name'   => 'OBS_SNMP_ERROR_INCORRECT_ARGUMENTS',
  'msg'    => ''
];
$config['snmp']['errorcodes'][996] = [
  'reason' => 'MIB or oid not found',   // MIB module or oid not found in specified dirs
  'name'   => 'OBS_SNMP_ERROR_MIB_OR_OID_NOT_FOUND',
  'msg'    => ''
];
$config['snmp']['errorcodes'][997] = [
  'reason' => 'Wrong .index in mibs dir', // This is common net-snmp bug, require delete all .index files
  'name'   => 'OBS_SNMP_ERROR_WRONG_INDEX_IN_MIBS_DIR',
  'msg'    => 'ERROR! Wrong .index in mibs dir net-snmp bug detected. Required delete all .index files. Please read [FAQ](' .
              OBSERVIUM_DOCS_URL . '/faq/#all-my-hosts-seem-down-to-observium-snmp-doesnt-seem-to-work-anymore){target=_blank}.'
];
$config['snmp']['errorcodes'][998] = [
  'reason' => 'MIB or oid disabled',    // MIB or oid disabled
  'name'   => 'OBS_SNMP_ERROR_MIB_OR_OID_DISABLED',
  'msg'    => ''
];
$config['snmp']['errorcodes'][999] = [
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
//$config['snmp']['errorcodes'][1001] = ['reason' => 'Authentication failure',   // Snmp auth errors
//                                            'count'  => 25,                         // errors in every poll run, disable after ~ 1.5 hour
//                                            'rate'   => 0.9,
//                                            'msg'    => ''];
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

//////////////////////////////////////////////////////////////////////////
// No changes below this line // no changes above it either, remember?  //
//////////////////////////////////////////////////////////////////////////

// Base user levels

$config['user_level']     = []; // Init this array, for do not allow override over config.inc.php!
$config['user_level'][0]  = [
  'roles'     => [],
  'name'      => 'Disabled',
  'subtext'   => 'This user disabled',
  'notes'     => 'User complete can\'t login and use any services. Use it to block access for specific users, but not delete from DB.',
  'row_class' => 'disabled',
  'icon'      => $config['icon']['user-delete']
];
$config['user_level'][1]  = [
  'roles'     => [ 'LOGIN' ],
  'name'      => 'Normal User',
  'subtext'   => 'This user has read access to individual entities',
  'notes'     => 'User can\'t see or edit anything by default. Can only see devices and entities specifically permitted.',
  'row_class' => 'default',
  'icon'      => $config['icon']['users']
];
$config['user_level'][5]  = [
  'roles'     => [ 'GLOBAL_READ' ],
  'name'      => 'Global Read',
  'subtext'   => 'This user has global read access',
  'notes'     => 'User can see all devices and entities with some security and configuration data masked, such as passwords.',
  'row_class' => 'suppressed',
  'icon'      => $config['icon']['user-self']
];
$config['user_level'][7]  = [
  'roles'     => [ 'SECURE_READ' ],
  'name'      => 'Global Secure Read',
  'subtext'   => 'This user has global read access with secured info',
  'notes'     => 'User can see all devices and entities without any information being masked, including device configuration (supplied by e.g. RANCID).',
  'row_class' => 'suppressed',
  'icon'      => $config['icon']['user-self']
];
$config['user_level'][8]  = [
  'roles'     => [ 'EDIT' ],
  'name'      => 'Global Secure Read / Limited Write',
  'subtext'   => 'This user has secure global read access with scheduled maintenence read/write.',
  'notes'     => 'User can see all devices and entities without any information being masked, including device configuration (supplied by e.g. RANCID). User can also add, edit and remove scheduled maintenance, group, contacts.',
  'row_class' => 'warning',
  'icon'      => $config['icon']['user-self']
];
$config['user_level'][9]  = [
    'roles'     => [ 'SECURE_EDIT' ],
    'name'      => 'Global Secure Read/Write',
    'subtext'   => 'This user has secure global read access with add/edit/delete entities and alerts.',
    'notes'     => 'User can see all devices and entities without limits. User can add, edit and remove devices, maintenance, alerts and bills.',
    'row_class' => 'warning',
    'icon'      => $config['icon']['user-self']
];
$config['user_level'][10] = [
  'roles'     => [ 'ADMIN' ],
  'name'      => 'Administrator',
  'subtext'   => 'This user has full administrative access',
  'notes'     => 'User can see and edit all devices and entities. This includes adding and removing devices, bills and users.',
  'row_class' => 'success',
  'icon'      => $config['icon']['user-log']
];


// Obsolete config variables
// Note, for multiarray config options use conversion with '->'
// example: $config['email']['default'] --> 'email->default'
$config['obsolete_config']   = []; // NOT CONFIGURABLE, init
$config['obsolete_config'][] = ['old' => 'warn->ifdown', 'new' => 'frontpage->device_status->ports'];
$config['obsolete_config'][] = ['old' => 'alerts->email->enable', 'new' => 'email->enable', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'alerts->email->default', 'new' => 'email->default', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'alerts->email->default_only', 'new' => 'email->default_only', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'alerts->email->graphs', 'new' => 'email->graphs', 'info' => 'changed since r6976'];
$config['obsolete_config'][] = ['old' => 'email_backend', 'new' => 'email->backend', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'email_from', 'new' => 'email->from', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'email_sendmail_path', 'new' => 'email->sendmail_path', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'email_smtp_host', 'new' => 'email->smtp_host', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'email_smtp_port', 'new' => 'email->smtp_port', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'email_smtp_timeout', 'new' => 'email->smtp_timeout', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'email_smtp_secure', 'new' => 'email->smtp_secure', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'email_smtp_auth', 'new' => 'email->smtp_auth', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'email_smtp_username', 'new' => 'email->smtp_username', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'email_smtp_password', 'new' => 'email->smtp_password', 'info' => 'changed since r5787'];
$config['obsolete_config'][] = ['old' => 'discovery_modules->cisco-pw', 'new' => 'discovery_modules->pseudowires', 'info' => 'changed since r6205'];
$config['obsolete_config'][] = ['old' => 'discovery_modules->discovery-protocols', 'new' => 'discovery_modules->neighbours', 'info' => 'changed since r6744'];
$config['obsolete_config'][] = ['old' => 'search_modules', 'new' => 'wui->search_modules', 'info' => 'changed since r7463'];
$config['obsolete_config'][] = ['old' => 'discovery_modules->ipv4-addresses', 'new' => 'discovery_modules->ip-addresses', 'info' => 'changed since r7565'];
$config['obsolete_config'][] = ['old' => 'discovery_modules->ipv6-addresses', 'new' => 'discovery_modules->ip-addresses', 'info' => 'changed since r7565'];
$config['obsolete_config'][] = ['old' => 'location_map', 'new' => 'location->map', 'info' => 'changed since r8021'];
$config['obsolete_config'][] = ['old' => 'geocoding->api_key', 'new' => 'geo_api->google->key', 'info' => 'DEPRECATED since 19.8.10000'];
$config['obsolete_config'][] = ['old' => 'snmp->snmp_sysorid', 'new' => 'discovery_modules->mibs', 'info' => 'Migrated to separate module since 19.10.10091'];

$config['obsolete_config'][] = ['old' => 'bad_xdp', 'new' => 'xdp->ignore_hostname', 'info' => 'changed since 20.6.10520'];
$config['obsolete_config'][] = ['old' => 'bad_xdp_regexp', 'new' => 'xdp->ignore_hostname_regex', 'info' => 'changed since 20.6.10520'];
$config['obsolete_config'][] = ['old' => 'bad_xdp_platform', 'new' => 'xdp->ignore_platform', 'info' => 'changed since 20.6.10520'];

$config['obsolete_config'][] = ['old' => 'discovery_modules->cisco-vrf', 'new' => 'discovery_modules->vrf', 'info' => 'changed since 20.10.10792'];

$config['obsolete_config'][] = ['old' => 'web_enable_showtech', 'new' => 'web_show_tech', 'info' => 'changed since 23.5.12832'];
$config['obsolete_config'][] = ['old' => 'show_overview_tab',   'new' => 'web_show_overview', 'info' => 'changed since 23.5.12832'];

$config['obsolete_config'][] = ['old' => 'show_locations', 'new' => 'web_show_locations', 'info' => 'changed since 23.7.12893'];

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

$defs_time = elapsed_time($def_start);
$defs_mem  = memory_get_usage() - $def_pre;

print_debug("DEFINITIONS Time  : " . format_number_short($defs_time, 6) . " ms\n");
print_debug("DEFINITIONS Memory: " . format_bytes($defs_mem) . "\n");
if ($config['devel']) {
    bdump($definition_loaded);
}

unset($definition_files, $definition_loaded, $file, $valid); // Clean

// End of includes/definitions.inc.php
