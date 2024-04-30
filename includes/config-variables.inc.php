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

// NOTE: Take care when adding new sections, the bar is already full on <1900-wide screens.

/// BASE ////////////////////////////////////////////////////////////

$section                           = 'base';
$config_sections[$section]['text'] = 'Base';

$config_variable['own_hostname'] = [
  'section'    => $section,
  'subsection' => 'General',
  'name'       => "Observium Server's own hostname",
  'type'       => 'string',
  'shortdesc'  => 'What is my own hostname (used so Observium can identify its host in its own database). By default equals `hostname -f`.',
];

$config_variable['require_hostname'] = [
  'section'    => $section,
  'subsection' => 'General',
  'name'       => 'Require valid hostname',
  'type'       => 'bool',
  'shortdesc'  => 'If TRUE, devices must have a valid resolvable hostname (in DNS or /etc/hosts). Default is FALSE, allowing addition of devices by IP address.',
];

$config_variable['use_ip'] = [
  'section'    => $section,
  'subsection' => 'General',
  'name'       => 'Use resolved IP',
  'type'       => 'bool',
  'shortdesc'  => 'If TRUE, snmp and other services request device by resolved ip instead hostname. This reduce queries to DNS cache.',
];

$config_variable['timestamp_format'] = [
  'section'    => $section,
  'subsection' => 'Datetime',
  'name'       => "Time format",
  'useredit'   => TRUE,
  'type'       => 'enum',
  'params'     => get_params_timestamp(),
  'shortdesc'  => 'Default time format.',
];

/* This config not used (only in reformat_us_date())
$setting = 'date_format';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Datetime';
$config_variable[$setting]['name']       = "Date format";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Default time format ('.date($config['date_format']).')';
*/

$config_variable['rrdcached'] = [
  'section'    => $section,
  'subsection' => 'RRD / RRDcacheD',
  'name'       => "RRDcacheD host or socket",
  'type'       => 'string',
  'shortdesc'  => 'Address of local (unix://xxx) or remote (IP:PORT) rrdcached host.'
];

$config_variable['rrd_override'] = [
  'section'    => $section,
  'subsection' => 'RRD / RRDcacheD',
  'name'       => 'RRD override',
  'type'       => 'bool',
  'shortdesc'  => 'Allow adding of devices if RRD directory already exists.'
];

$config_variable['db|debug'] = [
  'section'    => $section,
  'subsection' => 'Debugging / Profiling',
  'name'       => 'Database errors logging',
  'type'       => 'bool',
  'shortdesc'  => 'Log database query errors into logs/db.log.'
];

$config_variable['profile_sql'] = [
  'section'    => $section,
  'subsection' => 'Debugging / Profiling',
  'name'       => 'Profile SQL',
  'type'       => 'bool',
  'shortdesc'  => 'Store SQL queries and performance data.'
];

$config_variable['snmp|hide_auth'] = [
  'section'    => $section,
  'subsection' => 'Debugging / Profiling',
  'name'       => 'Hide SNMP auth',
  'type'       => 'bool',
  'shortdesc'  => 'Hide SNMPv1/2 community and SNMPv3 auth from debug and web output.'
];

$config_variable['snmp|errors'] = [
  'section'    => $section,
  'subsection' => 'Debugging / Profiling',
  'name'       => 'Collect SNMP Errors',
  'type'       => 'bool',
  'shortdesc'  => 'Collect SNMP errors into DB and (auto)disable SNMP queries with invalid response (empty/broken/etc).'
];

$config_variable['ping|debug'] = [
  'section'    => $section,
  'subsection' => 'Debugging / Profiling',
  'name'       => 'Ping debug',
  'type'       => 'bool',
  'shortdesc'  => 'Log ping errors into logs/debug.log.'
];

$config_variable['syslog|debug'] = [
  'section'    => $section,
  'subsection' => 'Debugging / Profiling',
  'name'       => 'Syslog debug',
  'type'       => 'bool',
  'shortdesc'  => 'Log RAW syslog entries into logs/debug.log.'
];

$config_variable['rrd|debug'] = [
  'section'    => $section,
  'subsection' => 'Debugging / Profiling',
  'name'       => 'RRD debug',
  'type'       => 'bool',
  'shortdesc'  => 'Log RRD errors into logs/rrd.log.'
];

$config_variable['web_debug_unprivileged'] = [
  'section'    => $section,
  'subsection' => 'Debugging / Profiling',
  'name'       => 'Web UI debug Unprivileged',
  'type'       => 'bool',
  'shortdesc'  => '[WARNING] Allow showing debug information to unprivileged (userlevel < 7) users in Web UI. This may leak configuration data to unauthorized users.'
];

/// POLLING /////////////////////////////////////////////////////////

$section                           = 'polling';
$config_sections[$section]['text'] = 'Polling/Cli';

$setting                                 = 'poller-wrapper|threads';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Poller Wrapper';
$config_variable[$setting]['name']       = 'Threads count';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'The number of poller threads that should run simultaneously. Default: CPU count x 2';

$setting                                 = 'poller-wrapper|max_running';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Poller Wrapper';
$config_variable[$setting]['name']       = 'Maximum allowed wrapper processes';
$config_variable[$setting]['type']       = 'enum|2|3|4|6|8|10';
$config_variable[$setting]['shortdesc']  = "The number of maximum allowed simultaneously running wrapper processes, used together with \$config['poller-wrapper']['max_la']. This prevents locking issues and too high Load Average on server. WARNING, don't set this number too high.";

$setting                                 = 'poller-wrapper|max_la';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Poller Wrapper';
$config_variable[$setting]['name']       = 'Maximum allowed Load Average';
$config_variable[$setting]['type']       = 'float';
$config_variable[$setting]['shortdesc']  = "Maximum allowed server Load Average to start wrapper processes, used together with \$config['poller-wrapper']['max_running']. This prevents locking issues and and too high Load Average on server. ";

$setting                                 = 'poller-wrapper|poller_timeout';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Poller Wrapper';
$config_variable[$setting]['name']       = 'Poller Device Timeout';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Hard poller timeout for each device (in seconds, minimum 90s). Prevents endless running for device poller process.';

$setting                                 = 'poller-wrapper|alerter';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Poller Wrapper';
$config_variable[$setting]['name']       = 'Run alerter in poller wrapper';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Execute alerter.php after poller.php from the poller wrapper.';

$setting                                 = 'poller-wrapper|stats';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Poller Wrapper';
$config_variable[$setting]['name']       = 'Enable poller wrapper statistics';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable poller wrapper statistics in RRD (can be seen at ' . $config['web_url'] . 'pollerlog/).';

$setting                                 = 'poller_modules|system';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Poller Modules';
$config_variable[$setting]['name']       = 'system';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['locked']     = TRUE; // Always locked, just display

$setting                                 = 'poller_modules|os';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Poller Modules';
$config_variable[$setting]['name']       = 'os';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['locked']     = TRUE; // Always locked, just display

foreach ($config['poller_modules'] as $key => $value) {
    $setting = 'poller_modules|' . $key;
    if (isset($config_variable[$setting])) {
        continue;
    }
    $config_variable[$setting]['section']    = $section;
    $config_variable[$setting]['subsection'] = 'Poller Modules';
    $config_variable[$setting]['name']       = $key;
    $config_variable[$setting]['type']       = 'bool';
}

$setting                                 = 'enable_ports_etherlike';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ports Modules';
$config_variable[$setting]['name']       = 'Enable Polling EtherLike-MIB';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable Polling extended EtherLike-MIB (can double port polling time).';

$setting                                 = 'enable_ports_vlan';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ports Modules';
$config_variable[$setting]['name']       = 'Enable Polling VLAN information';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable Polling basic information about VLANs for ports.';

$setting                                 = 'enable_ports_junoseatmvp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ports Modules';
$config_variable[$setting]['name']       = 'Enable Polling JunOSe ATM VC';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable JunOSe ATM VC Discovery/Poller.';

$setting                                 = 'enable_ports_adsl';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ports Modules';
$config_variable[$setting]['name']       = 'Enable Polling ADSL';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable Polling ADSL-LINE-MIB.';

$setting                                 = 'enable_ports_ipifstats';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ports Modules';
$config_variable[$setting]['name']       = 'Enable graphing of IP-MIB::ipIfStats';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable graphing of IP-MIB::ipIfStats.';

$setting                                 = 'enable_ports_fdbcount';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ports Modules';
$config_variable[$setting]['name']       = 'Enable Polling FDB count';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable Polling FDB count.';

$setting                                 = 'enable_ports_64bit';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ports Modules';
$config_variable[$setting]['name']       = 'Poll 64bit counters';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Prefer 64bit (HC) counters when available.';

//$setting = 'enable_ports_separate_walk';
//$config_variable[$setting]['section']    = $section;
//$config_variable[$setting]['subsection'] = 'Ports Modules';
//$config_variable[$setting]['name']       = 'Enable walk separate IF-MIB tables';
//$config_variable[$setting]['type']       = 'bool';
//$config_variable[$setting]['shortdesc']  = 'NOT ENABLED, do not use this globally! Walk separate IF-MIB tables instead global ifEntry, ifXEntry.';
// FIXME when we have a toggle to not display it ever, make sure it's in the array for docs generation etc.

/// DISCOVERY ////////////////////////////////////////////////////

$section                           = 'discovery';
$config_sections[$section]['text'] = 'Discovery';

$setting                                 = 'poller-wrapper|discovery_timeout';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Wrapper';
$config_variable[$setting]['name']       = 'Discovery Device Timeout';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Hard discovery timeout for each device (in seconds, minimum 90s). Prevents endless running for device poller process.';

// Ports

$setting                                 = 'bad_if_regexp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Port Discovery';
$config_variable[$setting]['name']       = 'Ignore ports by ifName/ifDescr Regular Expression';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '/^eth[0-9]+$/';
$config_variable[$setting]['shortdesc']  = 'Ports whose ifDescr or ifName match regular expressions configured here will be ignored during discovery.';

$setting                                 = 'bad_if';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Port Discovery';
$config_variable[$setting]['name']       = 'Ignore ports by ifName/ifDescr';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'pppoe-';
$config_variable[$setting]['shortdesc']  = 'Ports whose ifDescr or ifName match contain strings configured here will be ignored during discovery.';

$setting                                 = 'bad_ifalias_regexp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Port Discovery';
$config_variable[$setting]['name']       = 'Ignore ports by ifAlias Regular Expression';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '/^Transit:/';
$config_variable[$setting]['shortdesc']  = 'Ports whose ifAlias match regular expressions configured here will be ignored during discovery.';

$setting                                 = 'bad_iftype';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Port Discovery';
$config_variable[$setting]['name']       = 'Ignore ports by ifType';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'voiceOverAtm';
$config_variable[$setting]['shortdesc']  = 'Ports whose ifType match values configured here will be ignored during discovery.';

// Storage

$setting                                 = 'ignore_mount_removable';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Storage Discovery';
$config_variable[$setting]['name']       = 'Ignore removable mounted storage';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "This setting disables discovery of removable mounted storage (as indicated by the SNMP agent on the monitored device).";

$setting                                 = 'ignore_mount_optical';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Storage Discovery';
$config_variable[$setting]['name']       = 'Ignore optical mounted storage';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "This setting disables discovery of optical mounted storage (as indicated by the SNMP agent on the monitored device).";

$setting                                 = 'ignore_mount_network';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Storage Discovery';
$config_variable[$setting]['name']       = 'Ignore network mounted storage';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "This setting disables discovery of network mounted storage (as indicated by the SNMP agent on the monitored device).
                                            In general, this will include or exclude NFS mounts.";

$setting                                 = 'ignore_mount';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Storage Discovery';
$config_variable[$setting]['name']       = 'Ignore Storage by name (Exact)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '/proc';
$config_variable[$setting]['shortdesc']  = 'Storage whose names match exactly with strings configured here will be ignored during discovery.';

$setting                                 = 'ignore_mount_string';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Storage Discovery';
$config_variable[$setting]['name']       = 'Ignore Storage by name (String)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'procfs';
$config_variable[$setting]['shortdesc']  = 'Storage whose names contain strings configured here will be ignored during discovery.';

$setting                                 = 'ignore_mount_regexp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Storage Discovery';
$config_variable[$setting]['name']       = 'Ignore Storage by name (Regular Expression)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '/^DFC/';
$config_variable[$setting]['shortdesc']  = 'Storage whose names match regular expressions configured here will be ignored during discovery.';

// Mempools

$setting                                 = 'ignore_mempool';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Memory Discovery';
$config_variable[$setting]['name']       = 'Ignore Memory Pool by name (Exact)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'Cached Memory';
$config_variable[$setting]['shortdesc']  = 'Memory Pools whose names match exactly with strings configured here will be ignored during discovery.';

$setting                                 = 'ignore_mempool_string';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Memory Discovery';
$config_variable[$setting]['name']       = 'Ignore Memory Pool by name (String)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'Cache';
$config_variable[$setting]['shortdesc']  = 'Memory Pools whose names contain strings configured here will be ignored during discovery.';

$setting                                 = 'ignore_mempool_regexp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Memory Discovery';
$config_variable[$setting]['name']       = 'Ignore Memory Pool by name (Regular Expression)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '/\((reserved|image)\)$/';
$config_variable[$setting]['shortdesc']  = 'Memory Pools whose names match regular expressions configured here will be ignored during discovery.';

// Processor

$setting                                 = 'ignore_processor';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Processor Discovery';
$config_variable[$setting]['name']       = 'Ignore Processor by name (Exact)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'Cached Processor';
$config_variable[$setting]['shortdesc']  = 'Processors whose names match exactly with strings configured here will be ignored during discovery.';

$setting                                 = 'ignore_processor_string';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Processor Discovery';
$config_variable[$setting]['name']       = 'Ignore Processor by name (String)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'Cache';
$config_variable[$setting]['shortdesc']  = 'Processors whose names contain strings configured here will be ignored during discovery.';

$setting                                 = 'ignore_processor_regexp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Processor Discovery';
$config_variable[$setting]['name']       = 'Ignore Processor by name (Regular Expression)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '/\((reserved|image)\)$/';
$config_variable[$setting]['shortdesc']  = 'Processors whose names match regular expressions configured here will be ignored during discovery.';

// Sensor

$setting                                 = 'ignore_sensor';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Sensor Discovery';
$config_variable[$setting]['name']       = 'Ignore Sensor by name (Exact)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'Outside Temperature';
$config_variable[$setting]['shortdesc']  = 'Sensors whose names match exactly with strings configured here will be ignored during discovery.';

$setting                                 = 'ignore_sensor_string';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Sensor Discovery';
$config_variable[$setting]['name']       = 'Ignore Sensor by name (String)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'Outside';
$config_variable[$setting]['shortdesc']  = 'Sensors whose names contain strings configured here will be ignored during discovery.';

$setting                                 = 'ignore_sensor_regexp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Sensor Discovery';
$config_variable[$setting]['name']       = 'Ignore Sensor by name (Regular Expression)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '/(OSR-7600|C6K)\ Clock\ FRU\ 2/';
$config_variable[$setting]['shortdesc']  = 'Sensors whose names match regular expressions configured here will be ignored during discovery.';

// IP addresses

$setting                                 = 'ip-address|ignore_type';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'IP addresses Discovery';
$config_variable[$setting]['name']       = 'Ignore IP addresses by type';
$config_variable[$setting]['type']       = 'enum-array';
$config_variable[$setting]['params']     = $config['ip_types'];
$config_variable[$setting]['shortdesc']  = 'IP addresses will be ignored during discovery if IP type detected as one of in this list.';


// Printer Supplies

$setting                                 = 'ignore_toner';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Printer Supply Discovery';
$config_variable[$setting]['name']       = 'Ignore Supply by name (Exact)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'Fuchsia';
$config_variable[$setting]['shortdesc']  = 'Printer Supplies whose names match exactly with strings configured here will be ignored during discovery.';

$setting                                 = 'ignore_toner_string';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Printer Supply Discovery';
$config_variable[$setting]['name']       = 'Ignore Supply by name (String)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'Fuchsia';
$config_variable[$setting]['shortdesc']  = 'Printer Supplies whose names contain strings configured here will be ignored during discovery.';

$setting                                 = 'ignore_toner_regexp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Printer Supply Discovery';
$config_variable[$setting]['name']       = 'Ignore Supply by name (Regular Expression)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '/^Fuchsia$/';
$config_variable[$setting]['shortdesc']  = 'Printer Supplies whose names match regular expressions configured here will be ignored during discovery.';

// Autodiscovery

$setting                                 = 'autodiscovery|xdp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Device Autodiscovery';
$config_variable[$setting]['name']       = 'Enable autodiscovery via discovery protocols';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "This enables autodiscovery of neighbouring devices via discovery protocols, such as CDP, LLDP or FDP.
                                            Note that this doesn't enable or disable the discovery protocol tracking features, but controls whether
                                            Observium should try to auto-add devices it sees via those protocols.";

$setting                                 = 'autodiscovery|bgp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = "Device Autodiscovery";
$config_variable[$setting]['name']       = "Enable autodiscovery via iBGP neighbours";
$config_variable[$setting]['type']       = "bool";
$config_variable[$setting]['shortdesc']  = "This enables autodiscovery of neighbouring devices via neighbours seen through the BGP protocol
                                            (internal BGP only). Note that this doesn't enable or disable the BGP protocol tracking features,
                                            but controls whether Observium should try to auto-add devices it sees via those protocols.";

$setting                                 = 'autodiscovery|bgp_as_private';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = "Device Autodiscovery";
$config_variable[$setting]['name']       = "Enable autodiscovery via eBGP Private ASN neighbours";
$config_variable[$setting]['type']       = "bool";
$config_variable[$setting]['shortdesc']  = "This enables autodiscovery of neighbouring devices via eBGP with a Private AS (64512 - 65535)";


$setting                                 = 'autodiscovery|bgp_as_whitelist';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = "Device Autodiscovery";
$config_variable[$setting]['name']       = "Enable autodiscovery via eBGP with ASN whitelist";
$config_variable[$setting]['type']       = "enum-freeinput";
$config_variable[$setting]['shortdesc']  = "This enables autodiscovery of neighbouring devices via eBGP when the peer ASN matches the supplied whitelist.";


$setting                                 = 'autodiscovery|ospf';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = "Device Autodiscovery";
$config_variable[$setting]['name']       = "Enable autodiscovery via OSPF neighbours";
$config_variable[$setting]['type']       = "bool";
$config_variable[$setting]['shortdesc']  = "This enables autodiscovery of neighbouring devices via neighbours seen through the OSPF protocol.
                                            Note that this doesn't enable or disable the OSPF protocol tracking features, but controls whether
                                            Observium should try to auto-add devices it sees via those protocols.";

$setting                                 = 'autodiscovery|libvirt';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = "Device Autodiscovery";
$config_variable[$setting]['name']       = "Enable autodiscovery via Libvirt";
$config_variable[$setting]['type']       = "bool";
$config_variable[$setting]['shortdesc']  = "This enables autodiscovery of virtual machines discovered through libvirt integration. Note that
                                            this doesn't enable or disable the libvirt virtual machine tracking features, but controls whether
                                            Observium should try to auto-add devices it sees via libvirt.";

$setting                                 = 'autodiscovery|proxmox';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Device Autodiscovery';
$config_variable[$setting]['name']       = 'Enable autodiscovery via Proxmox';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "This enables autodiscovery of virtual machines discovered through the Proxmox Unix Agent. Note that
                                            this doesn't enable or disable the Proxmox virtual machine tracking features, but controls whether
                                            Observium should try to auto-add devices it sees via Proxmox.";

$setting                                 = 'autodiscovery|vmware';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Device Autodiscovery';
$config_variable[$setting]['name']       = 'Enable autodiscovery via VMware';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "This enables autodiscovery of virtual machines discovered through VMware integration. Note that
                                            this doesn't enable or disable the VMware virtual machine tracking features, but controls whether
                                            Observium should try to auto-add devices it sees via VMware.";

$setting                                 = 'mydomain';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Device Autodiscovery Options';
$config_variable[$setting]['name']       = 'Domain name for add to autodiscovered hosts';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'If you wish to append "domain.com" FQDN to an autodiscovered host. Useful if you do not have domain names set in routers but want them added by FQDN in Observium.';

$setting                                 = 'autodiscovery|require_hostname';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Device Autodiscovery Options';
$config_variable[$setting]['name']       = 'Require valid hostname';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'If TRUE, devices must have valid resolvable hostname (in DNS or /etc/hosts). By default it is NOT allowed to add devices by IP address during autodiscovery!';

$setting                                 = 'autodiscovery|ping_skip';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Device Autodiscovery Options';
$config_variable[$setting]['name']       = 'Skip icmp echo checks';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Skip icmp echo checks during autodiscovery (beware timeouts during discovery!). Devices are checked only for a valid SNMP response.';

$setting                                 = 'autodiscovery|ip_nets';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Device Autodiscovery Options';
$config_variable[$setting]['name']       = 'Networks to permit autodiscovery';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '10.0.0.0/8';
$config_variable[$setting]['shortdesc']  = 'When discovering new devices, Observium will check if their IP address falls within these ranges
                                            before trying to add them. Currently only IPv4 is supported.';
$config_variable[$setting]['longdesc']   = ''; // FIXME please note this is not snmp scanning range

$setting                                 = 'xdp|ignore_hostname';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Device Autodiscovery Options';
$config_variable[$setting]['name']       = 'Block autodiscovery of device by hostname';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'badhost.domain.com';
$config_variable[$setting]['shortdesc']  = 'Prevent Observium from trying to auto-discover any device which matches a configured hostname.';

$setting                                 = 'xdp|ignore_hostname_regex';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Device Autodiscovery Options';
$config_variable[$setting]['name']       = 'Block autodiscovery of device by hostname (Regular Expression)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '/^badhost.*/';
$config_variable[$setting]['shortdesc']  = 'Prevent Observium from trying to auto-discover any device whose hostname matches a configured regular expression.';

$setting                                 = 'xdp|ignore_platform';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Device Autodiscovery Options';
$config_variable[$setting]['name']       = 'Block autodiscovery of device by platform';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'Cisco IP Phone';
$config_variable[$setting]['shortdesc']  = 'Prevent Observium from trying to auto-discover any device whose reported platform matches a configured string.';

$setting                                 = 'xdp|ignore_platform_regex';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Device Autodiscovery Options';
$config_variable[$setting]['name']       = 'Block autodiscovery of device by platform (Regular Expression)';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '/^Cisco IP Phone/';
$config_variable[$setting]['shortdesc']  = 'Prevent Observium from trying to auto-discover any device whose reported platform matches a configured regular expression.';

$setting                                 = 'wmi|service_permit';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'WMI Autodiscovery Options';
$config_variable[$setting]['name']       = 'WMI Services allowed to be Discovered';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = 'Dnscache';
$config_variable[$setting]['shortdesc']  = 'Prevent Observium from trying to auto-discover all WMI services. Only defined names will be discovered instead.';

// Modules

$setting                                 = 'discovery_modules|os';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Modules';
$config_variable[$setting]['name']       = 'os';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['locked']     = TRUE; // Always locked, just display

$setting                                 = 'discovery_modules|mibs';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Modules';
$config_variable[$setting]['name']       = 'mibs';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['locked']     = TRUE; // Always locked, just display

foreach ($config['discovery_modules'] as $key => $value) {
    $setting = 'discovery_modules|' . $key;
    if (isset($config_variable[$setting])) {
        continue;
    }
    $config_variable[$setting]['section']    = $section;
    $config_variable[$setting]['subsection'] = 'Modules';
    $config_variable[$setting]['name']       = $key;
    $config_variable[$setting]['type']       = 'bool';
}

/// WEB UI //////////////////////////////////////////////////////////

$section                           = 'wui';
$config_sections[$section]['text'] = 'Web UI';

$setting                                 = 'web_url';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'External Web URL';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'URL used in links generated for emails, notifications and other external media.';

$setting                                 = 'page_title_prefix';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Page Title prefix';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Prefix used in the HTML page title.';

$setting                                 = 'page_title_suffix';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Page Title suffix';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Suffix used in the HTML page title.';

$setting                                 = 'page_title_separator';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Page Title split character';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Character to split the different page title levels on.';

$setting                                 = 'web|logo';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Custom header logo';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Replace Observium's header logo with a custom logo placed in html/images/. Observium logo will move to the bottom bar. Images must be exactly 162x30px.";

$setting                                 = 'page_refresh';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Refresh pages';
$config_variable[$setting]['useredit']   = TRUE;  
$config_variable[$setting]['type']       = 'enum'; // Normally this setting is just int, but we limit it with a pre-defined list
$config_variable[$setting]['params']     = [
  0    => ['name' => 'Manually', 'icon' => 'icon-ban-circle'],
  60   => ['name' => '1 minute', 'icon' => 'icon-refresh'],
  120  => ['name' => '2 minutes', 'icon' => 'icon-refresh'],
  300  => ['name' => '5 minutes', 'icon' => 'icon-refresh'],
  900  => ['name' => '15 minutes', 'icon' => 'icon-refresh'],
  1800 => ['name' => '30 minutes', 'icon' => 'icon-refresh']
];
$config_variable[$setting]['shortdesc']  = "Defines an autorefresh for pages in the web interface. If it's unset pages won't auto refresh.";

$setting                                 = "web_always_paginate";
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Always show pagination for lists';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Forces the UI to always generation pagination sections above and below lists. Useful to show total counts or to set pagination below 100 where required.';

$setting                                 = "web_pagesize";
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Default pagination size';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'enum|10|20|50|100|500|1000|10000|50000';
$config_variable[$setting]['shortdesc']  = 'The default number of items per page used by the web UI when paginating large tables. Default is 100.';

$setting                                 = 'web_iframe';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Allow run in iframe';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Allow to run observium pages in iframe. Disabled by default.';

$setting                                 = 'web_mouseover';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Mouseover popups';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable mouseover popups with extra information and graphs.';

$setting                                 = 'web_mouseover_mobile';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Mouseover popups on Mobile phones/tablets';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Allow display of mouseover popups on Mobile devices.';

$setting                                 = 'web_show_notifications';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Notifications';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Show or not notifications on top of Web UI. I.e. old version or remote poller down.';

$setting                                            = 'web_device_name';
$config_variable[$setting]['section']               = $section;
$config_variable[$setting]['subsection']            = 'General';
$config_variable[$setting]['name']                  = 'Default name to display device';
$config_variable[$setting]['useredit']              = TRUE;
$config_variable[$setting]['type']                  = 'enum';
$config_variable[$setting]['params']['hostname']    = ['name' => 'Hostname (default)'];
$config_variable[$setting]['params']['sysName']     = ['name' => 'sysName'];
$config_variable[$setting]['params']['description'] = ['name' => 'Description'];
$config_variable[$setting]['shortdesc']             = 'Default name to display device name. Allowed: hostname (default), sysName, description. When sysname or description empty, fallback as hostname.';

$setting                                 = 'web_show_disabled';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Show disabled devices';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Whether to show disabled devices on major pages or not. (To hide disabled devices and their ports/alerts/etc, set this to FALSE).';

$setting                                 = 'web_show_overview';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = "Enable 'Overview' tab";
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "Enable 'Overview' tab on device pages.";

$setting                                 = 'web_show_notes';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = "Enable display and edit Notes";
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "Enable display and edit markdown Notes. Currently only for device pages.";

$setting                                 = 'web_show_tech';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = "Enable 'show tech' option";
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "Enable 'show tech' menu option. Currently only for device pages.";

$setting                                         = 'web_theme_default';
$config_variable[$setting]['section']            = $section;
$config_variable[$setting]['subsection']         = 'Appearance';
$config_variable[$setting]['name']               = 'Default Theme';
$config_variable[$setting]['edition']            = 'pro';
$config_variable[$setting]['useredit']           = TRUE;
$config_variable[$setting]['type']               = 'enum';
$config_variable[$setting]['params']['light']    = ['name' => 'Light (default)', 'icon' => 'sprite-sun'];
$config_variable[$setting]['params']['dark']     = ['name' => 'Dark', 'icon' => 'sprite-moon'];
$config_variable[$setting]['params']['darkblue'] = ['name' => 'Dark Blue', 'icon' => 'sprite-moon'];
$config_variable[$setting]['params']['system']   = ['name' => 'Auto System (by MacOS/Windows settings)', 'icon' => 'sprite-globe-light']; /// FIXME icon
//$config_variable[$setting]['params']['time']     = [ 'name' => 'Auto (day/night)' ];
$config_variable[$setting]['shortdesc'] = 'Set default theme.';

$setting                                 = 'cache|enable';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Caching';
$config_variable[$setting]['name']       = 'Enable/disable Web UI caching';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Cache some data from the database for faster page generation, on very large installs. Not recommended for smaller installs.';

$setting                                  = 'cache|ttl';
$config_variable[$setting]['section']     = $section;
$config_variable[$setting]['subsection']  = 'Caching';
$config_variable[$setting]['name']        = 'Default time to live for cache';
$config_variable[$setting]['type']        = 'enum';
$config_variable[$setting]['params'][60]  = [ 'name' => '1 min' ];
$config_variable[$setting]['params'][180] = [ 'name' => '3 min' ];
$config_variable[$setting]['params'][300] = [ 'name' => '5 min (default)' ];
$config_variable[$setting]['params'][600] = [ 'name' => '10 min' ];
$config_variable[$setting]['params'][900] = [ 'name' => '15 min' ];
$config_variable[$setting]['shortdesc']   = 'Time in seconds to cache some data from the database.';

$setting                                        = 'cache|driver';
$config_variable[$setting]['section']           = $section;
$config_variable[$setting]['subsection']        = 'Caching';
$config_variable[$setting]['name']              = 'Cache driver';
$config_variable[$setting]['type']              = 'enum';
$config_variable[$setting]['params']['auto']    = [ 'name' => 'Auto detect' ];
$config_variable[$setting]['params']['zendshm'] = [ 'name' => 'Zend Memory Cache' ];
$config_variable[$setting]['params']['apcu']    = [ 'name' => 'APCu' ];
$config_variable[$setting]['params']['sqlite']  = [ 'name' => 'Sqlite' ];
$config_variable[$setting]['params']['files']   = [ 'name' => 'Files' ];
$config_variable[$setting]['shortdesc']         = "Cache driver used for caching data. Auto detection will choose whichever is available.";

/*$setting                                  = 'front_page';
$config_variable[$setting]['section']     = $section;
$config_variable[$setting]['subsection']  = 'Frontpage';
$config_variable[$setting]['name']        = 'Front page to display';
$config_variable[$setting]['type']        = 'enum';
$config_variable[$setting]['params_call'] = 'config_get_front_page_files'; // Call to this function for possible options
$config_variable[$setting]['shortdesc']   = 'PHP file to use as Observium front page';

$setting                                                    = 'frontpage|order';
$config_variable[$setting]['section']                       = $section;
$config_variable[$setting]['subsection']                    = 'Frontpage';
$config_variable[$setting]['name']                          = 'Frontpage Modules (DEPRECATED)';
$config_variable[$setting]['type']                          = 'enum-freeinput';
$config_variable[$setting]['params']['map']                 = ['name' => 'Map'];
$config_variable[$setting]['params']['alert_table']         = ['name' => 'Alert Table'];
$config_variable[$setting]['params']['status_summary']      = ['name' => 'Status Summary'];
$config_variable[$setting]['params']['status_donuts']       = ['name' => 'Status Summary (as donuts)'];
$config_variable[$setting]['params']['device_status']       = ['name' => 'Status Table'];
$config_variable[$setting]['params']['device_status_boxes'] = ['name' => 'Status Boxes'];
$config_variable[$setting]['params']['overall_traffic']     = ['name' => 'Overall Traffic'];
$config_variable[$setting]['params']['custom_traffic']      = ['name' => 'Custom Traffic'];
$config_variable[$setting]['params']['portpercent']         = ['name' => 'Overall Ports Percent'];
$config_variable[$setting]['params']['minigraphs']          = ['name' => 'Mini Graphs'];
$config_variable[$setting]['params']['syslog']              = ['name' => 'Syslog'];
$config_variable[$setting]['params']['eventlog']            = ['name' => 'Eventlog'];
$config_variable[$setting]['params']['splitlog']            = ['name' => 'Syslog & Eventlog'];
$config_variable[$setting]['shortdesc']                     = 'List of modules to show on the front page. Keep required order. Options: map, alert_table, status_summary, status_donuts, device_status, device_status_boxes, overall_traffic, custom_traffic, portpercent, minigraphs, syslog, eventlog, splitlog';
*/

$setting                                 = 'frontpage|eventlog|severity';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Frontpage';
$config_variable[$setting]['name']       = 'Eventlog severities';
$config_variable[$setting]['type']       = 'enum-array';
$config_variable[$setting]['params']     = array_slice($config['syslog']['priorities'], 0, 8);
$config_variable[$setting]['value_call'] = 'priority_string_to_numeric'; // Call to this function for current values
$config_variable[$setting]['shortdesc']  = 'Show eventlog entries only with this severities';

$setting                                 = 'frontpage|syslog|items';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Frontpage';
$config_variable[$setting]['name']       = 'Syslog items';
$config_variable[$setting]['type']       = 'enum|5|10|15|25|50'; // Normally this setting is just int, but we limit it with a pre-defined list
$config_variable[$setting]['shortdesc']  = 'Only show the last XX items of the syslog view';

$setting                                 = 'frontpage|syslog|priority';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Frontpage';
$config_variable[$setting]['name']       = 'Syslog priorities';
$config_variable[$setting]['type']       = 'enum-array';
$config_variable[$setting]['params']     = array_slice($config['syslog']['priorities'], 0, 8);
$config_variable[$setting]['value_call'] = 'priority_string_to_numeric'; // Call to this function for current values
$config_variable[$setting]['shortdesc']  = 'Show syslog entries only with this priorities';

$setting                                        = 'frontpage|map|api';
$config_variable[$setting]['section']           = $section;
$config_variable[$setting]['subsection']        = 'Frontpage Map';
$config_variable[$setting]['name']              = 'Map API';
$config_variable[$setting]['type']              = 'enum';
$config_variable[$setting]['params']['leaflet'] = ['name' => 'Leaflet'];
//$config_variable[$setting]['params']['google-mc'] = [ 'name' => 'Google',  'subtext' => 'REQUIRED to use Maps API KEY', 'desc' => 'Request a KEY <a href="https://developers.google.com/maps/documentation/geocoding/get-api-key" target="_blank">here</a>' ];
//$config_variable[$setting]['params']['google']    = [ 'name' => 'Google (old)',  'subtext' => 'REQUIRED to use Maps API KEY', 'desc' => 'Request a KEY <a href="https://developers.google.com/maps/documentation/geocoding/get-api-key" target="_blank">here</a>' ];
$config_variable[$setting]['shortdesc'] = 'Map provider on the front page';

$setting                                                     = 'frontpage|map|tiles';
$config_variable[$setting]['section']                        = $section;
$config_variable[$setting]['subsection']                     = 'Frontpage Map';
$config_variable[$setting]['name']                           = 'Map Tiles (except Google)';
$config_variable[$setting]['type']                           = 'enum';
$config_variable[$setting]['params']['carto-base-light']     = ['name' => 'Carto Basemap Light'];
$config_variable[$setting]['params']['carto-base-dark']      = ['name' => 'Carto Basemap Dark'];
$config_variable[$setting]['params']['carto-base-auto']      = ['name' => 'Carto Basemap Auto Light/Dark'];
$config_variable[$setting]['params']['esri-worldgraycanvas'] = ['name' => 'ESRI World Gray Canvas'];
$config_variable[$setting]['params']['opentopomap']          = ['name' => 'OpenTopoMap'];
$config_variable[$setting]['params']['wikimedia']            = ['name' => 'Wikimedia'];
$config_variable[$setting]['params']['nasa-night']           = ['name' => 'NASA (Night)'];
//$config_variable[$setting]['params']['osm-mapnik'] = [ 'name' => 'OpenStreetMap Mapnik' ];
$config_variable[$setting]['shortdesc'] = 'Map tiles used when building the map on the front page.';

$setting                                 = 'frontpage|map|height';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Frontpage Map';
$config_variable[$setting]['name']       = 'Map Height';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Height in pixels for the map on the front page';

$setting                                 = 'frontpage|map|okmarkersize';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Frontpage Map';
$config_variable[$setting]['name']       = 'OK Marker Size';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Marker size in pixels for the map on the front page';

$setting                                 = 'frontpage|map|alertmarkersize';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Frontpage Map';
$config_variable[$setting]['name']       = 'Alert Marker Size';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Alert marker size in pixels for the map on the front page';

$setting                                 = 'short_hostname|length';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Cosmetics';
$config_variable[$setting]['name']       = 'Short Hostname Length';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Maximum length in characters of "shortened" hostnames used in UI tables.';


$setting                                 = 'rrdgraph_real_95th';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Graphs';
$config_variable[$setting]['name']       = 'Display 95% percentile';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable or disable display the 95% based on the highest value for ports (aka real 95%).';

$setting                                        = 'graphs|style';
$config_variable[$setting]['section']           = $section;
$config_variable[$setting]['subsection']        = 'Graphs';
$config_variable[$setting]['name']              = 'Graph style';
$config_variable[$setting]['useredit']          = TRUE;
$config_variable[$setting]['type']              = 'enum';
$config_variable[$setting]['params']['default'] = ['name' => 'Default'];
$config_variable[$setting]['params']['mrtg']    = ['name' => 'MRTG'];
$config_variable[$setting]['shortdesc']         = 'Use alternative graph style. NOTE: MRTG style currently works only for port bits graphs.';

$setting                                 = 'graphs|size';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Graphs';
$config_variable[$setting]['name']       = 'Graph size';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'enum';
//$config_variable[$setting]['params']['small']  = [ 'name' => 'Small' ];
$config_variable[$setting]['params']['normal'] = [ 'name' => 'Normal' ];
$config_variable[$setting]['params']['big']    = [ 'name' => 'Large' ];
$config_variable[$setting]['shortdesc']        = 'Common graphs view size on most pages (not at all).';

$setting                                 = 'graphs|stacked_processors';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Graphs';
$config_variable[$setting]['name']       = 'Enable Stacked Processor Graphs';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable the use of stacked processor graphs for OS types with "processor_stacked" enabled.';

$setting                                      = 'graphs|ports_scale_default';
$config_variable[$setting]['section']         = $section;
$config_variable[$setting]['subsection']      = 'Graphs';
$config_variable[$setting]['name']            = 'Ports graph default scale';
$config_variable[$setting]['useredit']        = TRUE;
$config_variable[$setting]['type']            = 'enum';
$config_variable[$setting]['params']['auto']  = [ 'name' => 'Autoscale' ];
$config_variable[$setting]['params']['speed'] = [ 'name' => 'Interface Speed' ];
foreach ($config['graphs']['ports_scale_list'] as $entry) {
    $speed                                       = (int)unit_string_to_numeric($entry, 1000);
    $config_variable[$setting]['params'][$entry] = format_bps($speed, 4, 4);
}
$config_variable[$setting]['shortdesc'] = 'Use this value as default scale for port graphs.';

$setting                                 = 'graphs|ports_scale_force';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Graphs';
$config_variable[$setting]['name']       = 'Force graph scale';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Force scale also if real data more than selected scale.';

$setting                                 = 'graphs|dynamic_labels';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Graphs';
$config_variable[$setting]['name']       = 'Use Dynamic Labels';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Draw different color markers for labels (instead square marker).';

$setting                                 = 'graphs|always_draw_max';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Graphs';
$config_variable[$setting]['name']       = 'Always draw "Max" area on graphs';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Always draw the Max RRA on graphs. By default this is suppressed for graphs of one week or shorter.';

$setting                                 = "web_porttype_legend_limit";
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Graphs';
$config_variable[$setting]['name']       = 'Port-type page legend port limit';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'enum|1|5|10|15|20|100';
$config_variable[$setting]['shortdesc']  = 'The number of ports permitted to be displayed on the legend of the graph at the top of the port type (transit, peering, etc) page before the legend is disabled.';

$setting                                 = "web_group_legend_limit";
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Graphs';
$config_variable[$setting]['name']       = 'Group aggregate graph legend entity limit';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'enum|1|5|10|15|20|100';
$config_variable[$setting]['shortdesc']  = 'The number of entities permitted to be displayed on the legend of a group aggregate graph before the legend is disabled.';

/// Entities

$section                           = 'entities';
$config_sections[$section]['text'] = 'Entities';

$setting                                 = 'devices|serverscheck|temp_f';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Devices';
$config_variable[$setting]['name']       = 'ServersCheck Fahrenheit units';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Specifies that any ServersCheck devices will return temperature sensors in Fahrenheit.';

$setting                                 = 'sensors|port|ignore_shutdown';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ports';
$config_variable[$setting]['name']       = 'Ignore alert state for Administrative Down ports';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'When measured port Shutdown ignore sensor alerts.';

$setting                                 = 'sensors|port|power_to_dbm';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ports';
$config_variable[$setting]['name']       = 'Convert Port DOM power sensors to dBm';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'When device provide port DOM power sensors in Watts, set to TRUE for convert it to dBm sensors. NOTE: power DOM sensors in Watts will removed.';

$setting                                 = 'sensors|limits_events';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Sensors';
$config_variable[$setting]['name']       = 'Log sensors limit changes in eventlog';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Log sensors limit changes in eventlog.';

$setting                                 = 'sensors|web_measured_compact';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Sensors';
$config_variable[$setting]['name']       = 'Compact sensors view for measured entities';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Show sensors for measured entities in compact view style';

$setting                                 = 'ipmi_unit|discrete';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Statuses';
$config_variable[$setting]['name']       = 'Enable polling IPMI "discrete" sensors';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable experimental support for IPMI discrete sensors. NOTE: very unstable, many false positive alerts.';

/// ALERTING /////////////////////////////////////////////////////////

$section                           = 'alerting';
$config_sections[$section]['text'] = 'Alerting';

$setting                                     = 'alerts|interval';
$config_variable[$setting]['section']        = $section;
$config_variable[$setting]['subsection']     = 'Notification';
$config_variable[$setting]['name']           = 'Alert notification re-send interval';
$config_variable[$setting]['type']           = 'enum';
$config_variable[$setting]['params'][0]      = [ 'name' => 'Disable re-send' ];
$config_variable[$setting]['params'][21600]  = [ 'name' => '6 hours' ];
$config_variable[$setting]['params'][43200]  = [ 'name' => '12 hours' ];
$config_variable[$setting]['params'][86400]  = [ 'name' => '1 day' ];
$config_variable[$setting]['params'][172800] = [ 'name' => '2 days' ];
$config_variable[$setting]['params'][604800] = [ 'name' => '1 week' ];
$config_variable[$setting]['shortdesc']      = 'How frequently to re-send a notification for a continuing alert condition. Default is 1 day.';

$setting                                 = 'alerts|disable|all';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Notification';
$config_variable[$setting]['name']       = 'Disable All Notifications';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Disables alert notification generation for all notification transport types.';

$setting                                 = 'alerts|suppress';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Notification';
$config_variable[$setting]['name']       = 'Suppress All Alerts';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Causes all failed alerts to be placed in the suppressed state.';

$setting                                 = 'email|enable';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport';
$config_variable[$setting]['name']       = 'Enable Email transport';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Disables or enables email transport globally.';

$setting                                 = 'email|backend';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport';
$config_variable[$setting]['name']       = 'Mail backend';
$config_variable[$setting]['type']       = 'enum';
$config_variable[$setting]['params']     = [
  'mail'     => ['name' => "PHP's built-in"],
  'sendmail' => ['name' => 'Sendmail'],
  'mx'       => ['name' => 'SMTP by MX records'],
  'smtp'     => ['name' => 'SMTP']
];
$config_variable[$setting]['shortdesc']  = 'Mail backends. Sendmail and SMTP required additional configurations.';

$setting                                 = 'email|from';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport';
$config_variable[$setting]['name']       = 'Email From: address';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Email address used in the from: Field. Default is observium@<localhost>';

$setting                                 = 'email|graphs';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport';
$config_variable[$setting]['name']       = 'Graphs in mail';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Allow graphs in mail body.';

// Don't remove. This is useful if people just want everything to go to a single address. Without touching contacts.
//if (!empty($config['email']['default'])) {
$setting                                 = 'email|default';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport';
$config_variable[$setting]['name']       = 'Default Notification Email';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Email address to send notifications to as default. Only used when no contact matches the alert.';
//}

// FIXME. Do not use this. Will remove later. Show only if changed
// This should probably be removed and set as default behaviour when email|default is configured. Almost no one wants sysContact, and no one wants sysContact *and* default

//if ($config['email']['default_only']) {
$setting                                 = 'email|default_only';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport';
$config_variable[$setting]['name']       = 'Default Email Only';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "When no contact matches, use only the default notification email. Don't use the device's sysContact.";
//}

$setting                                 = 'email|default_syscontact';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport';
$config_variable[$setting]['name']       = 'Default Device sysContact';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "Always sent alerts by Device sysContact.";

$setting                                 = 'email|sendmail_path';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport (Sendmail)';
$config_variable[$setting]['name']       = 'Sendmail Path';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'The location of the sendmail program.';

$setting                                 = 'email|smtp_host';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport (SMTP)';
$config_variable[$setting]['name']       = 'SMTP hostname';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Outgoing SMTP server name.';

$setting                                 = 'email|smtp_port';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport (SMTP)';
$config_variable[$setting]['name']       = 'SMTP server port';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Port to be used to connect to the SMTP server.';

$setting                                 = 'email|smtp_timeout';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport (SMTP)';
$config_variable[$setting]['name']       = 'SMTP connection timeout';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'SMTP server connection timeout in seconds.';

$setting                                 = 'email|smtp_secure';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport (SMTP)';
$config_variable[$setting]['name']       = 'SMTP connection encryption';
$config_variable[$setting]['type']       = 'enum';
$config_variable[$setting]['params']     = [
  ''    => ['name' => 'No encryption'],
  'tls' => ['name' => 'TLS'],
  'ssl' => ['name' => 'SSL']
];
$config_variable[$setting]['shortdesc']  = 'Use SMTP connection encryption (TLS, SSL, or none).';

$setting                                 = 'email|smtp_auth';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport (SMTP)';
$config_variable[$setting]['name']       = 'SMTP authentication';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Whether or not to use SMTP authentication.';

$setting                                 = 'email|smtp_username';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport (SMTP)';
$config_variable[$setting]['name']       = 'SMTP username';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'SMTP authentication username.';

$setting                                 = 'email|smtp_password';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Email Transport (SMTP)';
$config_variable[$setting]['name']       = 'SMTP password';
$config_variable[$setting]['type']       = 'password';
$config_variable[$setting]['shortdesc']  = 'SMTP authentication password.';

/// AUTHENTICATION ///////////////////////////////////////////////////

$section                           = 'authentication';
$config_sections[$section]['text'] = 'Authentication';

$setting                                  = 'auth_mechanism';
$config_variable[$setting]['section']     = $section;
$config_variable[$setting]['subsection']  = 'General';
$config_variable[$setting]['name']        = 'Authentication module to use';
$config_variable[$setting]['type']        = 'enum';
$config_variable[$setting]['params_call'] = 'config_get_auth_modules'; // Call to this function for possible options
$config_variable[$setting]['shortdesc']   = 'Specific settings for the individual authentication modules can be found below.';

$setting                                 = 'auth|remote_user';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Trust Apache REMOTE_USER';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Disables built-in authentication and delegates this to Apache, for auth modules that support this. Make sure to read the documentation and handle with care!';

$setting                                 = 'login_message';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Login';
$config_variable[$setting]['name']       = 'Login message';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Define the login message shown on the login page.';

$setting                                 = 'login_remember_me';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Login';
$config_variable[$setting]['name']       = 'Remember me';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable or disable the remember me feature.';

$setting                                 = 'web_session_lifetime';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Sessions';
$config_variable[$setting]['name']       = 'Session lifetime';
$config_variable[$setting]['type']       = 'enum';
$config_variable[$setting]['params']     = [
  0      => ['name' => 'Until browser restart'],
  //60     => [ 'name' => '1 minute' ],
  //600    => [ 'name' => '10 minutes' ],
  1800   => ['name' => '30 minutes'],
  3600   => ['name' => '1 hour'],
  10800  => ['name' => '3 hours'],
  86400  => ['name' => '1 day'],
  604800 => ['name' => '1 week']
];
$config_variable[$setting]['shortdesc']  = 'Default user sessions lifetime in seconds (0 means until browser restart). This lifetime is used for sessions without "remember me" checkbox.';

$setting                                 = 'web_session_ip';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Sessions';
$config_variable[$setting]['name']       = 'Session bind to IP';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Bind user sessions to their IP address.';

$setting                                  = 'web_session_ipv6_prefix';
$config_variable[$setting]['section']     = $section;
$config_variable[$setting]['subsection']  = 'Sessions';
$config_variable[$setting]['name']        = 'Session IPv6 Prefix';
$config_variable[$setting]['type']        = 'enum';
$config_variable[$setting]['params'][128] = [ 'name' => '/128 (default)'];
$config_variable[$setting]['params'][127] = [ 'name' => '/127' ];
$config_variable[$setting]['params'][126] = [ 'name' => '/126' ];
$config_variable[$setting]['params'][124] = [ 'name' => '/124' ];
$config_variable[$setting]['params'][120] = [ 'name' => '/120' ];
$config_variable[$setting]['params'][112] = [ 'name' => '/112' ];
$config_variable[$setting]['params'][104] = [ 'name' => '/104' ];
$config_variable[$setting]['params'][96]  = [ 'name' => '/96' ];
$config_variable[$setting]['params'][80]  = [ 'name' => '/80' ];
$config_variable[$setting]['params'][64]  = [ 'name' => '/64' ];
$config_variable[$setting]['params'][48]  = [ 'name' => '/48' ];
if (!isset($config_variable[$setting]['params'][$config['web_session_ipv6_prefix']])) {
    // When configured in config.php
    $config['web_session_ipv6_prefix']                                       = ltrim($config['web_session_ipv6_prefix'], '/');
    $config_variable[$setting]['params'][$config['web_session_ipv6_prefix']] = ['name' => '/' . $config['web_session_ipv6_prefix'] . ' (current)'];
}
$config_variable[$setting]['shortdesc'] = 'Bind user session to prefix limited IPv6 address. Minimum /128 (single address) and maximum is /1 (just about any address)';

$setting                                 = 'web_session_ip_by_header';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Sessions';
$config_variable[$setting]['name']       = 'Use configured Remote Address HTTP header';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'DANGEROUS. Allow to use alternative Remote Address HTTP header for Session identification. Use at own risk, since address in HTTP header(s) can be spoofed.';

$setting                                                 = 'web_remote_addr_header';
$config_variable[$setting]['section']                    = $section;
$config_variable[$setting]['subsection']                 = 'Sessions';
$config_variable[$setting]['name']                       = 'Remote Address HTTP header for Web session logging';
$config_variable[$setting]['type']                       = 'enum';
$config_variable[$setting]['params']['default']          = ['name' => 'Auto Detect'];
$config_variable[$setting]['params']['CF-Connecting-IP'] = ['name' => 'CF-Connecting-IP (Cloudflare Proxy)'];
$config_variable[$setting]['params']['X-Real-IP']        = ['name' => 'X-Real-IP (Nginx HTTP Proxy)'];
$config_variable[$setting]['params']['Client-IP']        = ['name' => 'Client-IP (NetScaler Load Balancer)'];
$config_variable[$setting]['params']['X-Forwarded-For']  = ['name' => 'X-Forwarded-For (HTTP Proxy or Load Balancer)'];
$config_variable[$setting]['shortdesc']                  = 'This HTTP header will automatically log in the specified user without extra authentication. NOTE: Additionally it can used as Session Identification address (NOT BY default, see above).';

$setting                                 = 'web_session_cidr';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Sessions';
$config_variable[$setting]['name']       = 'Allow user authorization from specific IP ranges';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '10.0.0.0/8, !10.16.0.1';
$config_variable[$setting]['shortdesc']  = 'Allow users to log in from specific IP ranges only. Leave empty for access from any IP address. Use first exclamation mark for exclude IP or net (ie: !172.16.0.1).';

$setting                                 = 'allow_unauth_graphs';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Graphs';
$config_variable[$setting]['name']       = 'Allow graphs to be viewed by anyone';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Disables authentication for all graphs. This should be used with caution and should be left disabled when using the CIDR option!';

$setting                                 = 'allow_unauth_graphs_cidr';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Graphs';
$config_variable[$setting]['name']       = 'Allow graphs to be viewed by anyone from specific IP ranges';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['example']    = '10.0.0.0/8';
$config_variable[$setting]['shortdesc']  = 'Allow unauthenticated users to view graphs from specific IP ranges only.';

$setting                                 = 'auth_ldap_server';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'LDAP';
$config_variable[$setting]['name']       = 'LDAP servers';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['shortdesc']  = 'List of LDAP servers to authenticate against, in order. Note this is meant as redundancy, and not as a way to specify multiple LDAP realms. Failover to the next server will happen when it is unreachable, not when authentication fails.';

$setting                                 = 'auth_ldap_port';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'LDAP';
$config_variable[$setting]['name']       = 'LDAP server port';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Port to be used to connect to the LDAP servers.';

$setting                                 = 'auth_ldap_version';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'LDAP';
$config_variable[$setting]['name']       = 'LDAP version used';
$config_variable[$setting]['type']       = 'enum|2|3';
$config_variable[$setting]['shortdesc']  = 'LDAP version used to connect to the LDAP server.';

$setting                                 = 'auth_ldap_starttls';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'LDAP';
$config_variable[$setting]['name']       = 'Use STARTTLS';
$config_variable[$setting]['type']       = 'enum|no|optional|require';
$config_variable[$setting]['shortdesc']  = 'Use STARTTLS for LDAP security: No, Optional (Try but not require), Require (Abort connection when failing).';

$setting                                 = 'auth_ldap_referrals';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'LDAP';
$config_variable[$setting]['name']       = 'Follow LDAP referrals';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Follow referrals received from LDAP server.';

$setting                                 = 'auth_ldap_recursive';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'LDAP';
$config_variable[$setting]['name']       = 'Recursive group lookup';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Do recursive group lookup for group memberships.';

$setting                                 = 'auth_ldap_recursive_maxdepth';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'LDAP';
$config_variable[$setting]['name']       = 'Recursive group lookup maximum depth';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Maximum depth of group membership lookups.';

$setting                                 = 'auth_ad_server';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Active Directory';
$config_variable[$setting]['name']       = 'Server list';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['shortdesc']  = 'List of AD servers to authenticate against, in order. Note this is meant as redundancy, and not as a way to specify multiple AD realms. Failover to the next server will happen when it is unreachable, not when authentication fails. Leaving this empty will retrieve the server list from DNS.';

$setting                                 = 'auth_ad_tls';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Active Directory';
$config_variable[$setting]['name']       = 'Use TLS';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Use immediate TLS for AD security (usually when using port 636).';

$setting                                 = 'auth_ad_starttls';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Active Directory';
$config_variable[$setting]['name']       = 'Use STARTTLS';
$config_variable[$setting]['type']       = 'enum|no|optional|require';
$config_variable[$setting]['shortdesc']  = 'Use STARTTLS for AD security: No (Stay in plain text), Optional (Try but not require), Require (Abort connection when failing).';

$setting                                 = 'auth_ad_validatecert';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Active Directory';
$config_variable[$setting]['name']       = 'Certificate validation';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Validate the certificate of the domain controller.';

$setting                                 = 'auth_ad_port';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Active Directory';
$config_variable[$setting]['name']       = 'Server port';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Port to be used to connect to the AD servers (usually 389 or 636).';

$setting                                 = 'auth_ad_domain';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Active Directory';
$config_variable[$setting]['name']       = 'Domain name';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'FQDN form of the AD domain (e.g. contoso.com).';

$setting                                 = 'auth_ad_basedn';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Active Directory';
$config_variable[$setting]['name']       = 'Base DN';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Base DN of your AD tree (e.g. DC=contoso,DC=com). Optional if auth_ad_domain is set.';

$setting                                 = 'auth_ad_binddn';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Active Directory';
$config_variable[$setting]['name']       = 'Bind user';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'User for Observium to authenticate against AD, can be in short (e.g. observium@contoso.com) or DN (e.g CN=observium,CN=users,DC=contoso,DC=com) form.';

$setting                                 = 'auth_ad_bindpw';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Active Directory';
$config_variable[$setting]['name']       = 'Bind password';
$config_variable[$setting]['type']       = 'password';
$config_variable[$setting]['shortdesc']  = 'Password for the bind user to authenticate against AD.';

$setting                                 = 'auth_radius_server';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RADIUS';
$config_variable[$setting]['name']       = 'RADIUS servers';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['shortdesc']  = 'List of RADIUS servers to authenticate against, in order.';

$setting                                 = 'auth_radius_id';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RADIUS';
$config_variable[$setting]['name']       = 'RADIUS NAS Identifier';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'NAS-Identifier (32) attribute string. When ID empty, local server hostname is used.';

$setting                                 = 'auth_radius_port';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RADIUS';
$config_variable[$setting]['name']       = 'RADIUS server port';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Port to be used to connect to the RADIUS servers.';

$setting                                 = 'auth_radius_secret';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RADIUS';
$config_variable[$setting]['name']       = 'RADIUS authentication secret';
$config_variable[$setting]['type']       = 'password';
$config_variable[$setting]['shortdesc']  = 'Authentication secret to be used to connect to the RADIUS server.';

$setting                                 = 'auth_radius_method';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RADIUS';
$config_variable[$setting]['name']       = 'RADIUS authentication method';
$config_variable[$setting]['type']       = 'enum|PAP|CHAP|MSCHAPv1|MSCHAPv2';
$config_variable[$setting]['shortdesc']  = 'Authentication method to use: PAP (default, unencrypted), CHAP (Windows RADIUS server not supported), MSCHAPv1, MSCHAPv2';

$setting                                 = 'auth_radius_timeout';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RADIUS';
$config_variable[$setting]['name']       = 'RADIUS connection timeout';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Connection timeout in seconds.';

$setting                                 = 'auth_radius_retries';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RADIUS';
$config_variable[$setting]['name']       = 'RADIUS connection retries';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Number of times to try to connect to the RADIUS server.';

/// Network ///////////////////////////////////////////////////////////////////

$section                           = 'network';
$config_sections[$section]['text'] = 'Network';

$setting                                 = 'ping|retries';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ping';
$config_variable[$setting]['name']       = 'Ping request retries';
$config_variable[$setting]['type']       = 'enum|1|3|5|7|10';
$config_variable[$setting]['shortdesc']  = 'Specifies the number of retries to be used in icmp ping requests. The default is 3.';

$setting                                 = 'ping|timeout';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ping';
$config_variable[$setting]['name']       = 'Ping timeout in milliseconds (ms)';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Specifies the timeout in seconds between retries in icmp ping requests. The default is 500.';

$setting                                 = 'snmp|version';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'SNMP';
$config_variable[$setting]['name']       = 'Default SNMP version to use';
$config_variable[$setting]['type']       = 'enum|v2c|v3|v1';
$config_variable[$setting]['shortdesc']  = 'Default version of the SNMP protocol to use for new devices.';

$setting                                 = 'snmp|community';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'SNMP';
$config_variable[$setting]['name']       = 'Default SNMP communities to use';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['shortdesc']  = 'Default communities try when adding or discovering new devices.';

$setting                                 = 'snmp|max-rep';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'SNMP';
$config_variable[$setting]['name']       = 'Enable use of max-rep values for SNMP BULKGET';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'This enables the use of max-rep values defined per-OS to help optimise the performance of SNMP BULKGET operations and can vastly speed up the poller.';

/*
$setting = 'snmp|snmp_sysorid';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'SNMP';
$config_variable[$setting]['name']       = 'Enable autodiscovery of supported MIBs via sysORID';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'This enables (extra) autodiscovery of supported MIBs on devices which report this information via the sysORID table in SNMP.';
*/

$setting                                 = 'snmp|retries';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'SNMP';
$config_variable[$setting]['name']       = 'SNMP request retries';
$config_variable[$setting]['type']       = 'enum|0|1|3|5|7';
$config_variable[$setting]['shortdesc']  = 'Specifies the number of retries to be used in snmp requests. The default is 5.';

$setting                                 = 'snmp|timeout';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'SNMP';
$config_variable[$setting]['name']       = 'SNMP timeout in seconds';
$config_variable[$setting]['type']       = 'int';
$config_variable[$setting]['shortdesc']  = 'Specifies the timeout in seconds between retries in snmp requests. The default is 1.';

$setting                                 = 'http_ip_version';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'HTTP';
$config_variable[$setting]['name']       = 'HTTP(S) IP version';
//$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['type']      = 'enum|IPv4|IPv6';
$config_variable[$setting]['shortdesc'] = 'HTTP(S) force resolve names to specified addresses only (IPv4 or IPv6). When not specified try in default order (IPv6 than IPv4).';

$setting                                 = 'http_proxy'; // FIXME should be renamed to proxy|hostname
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'HTTP';
$config_variable[$setting]['name']       = 'HTTP(S) Proxy hostname';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['example']    = 'yourproxy:3128';
$config_variable[$setting]['shortdesc']  = 'Specifies an HTTP(S) proxy to be used for external HTTP requests. Used primarily for geolocation and other external data lookups.';

$setting                                 = 'proxy_fulluri';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'HTTP';
$config_variable[$setting]['name']       = 'HTTP(S) Proxy use Full URI';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'When set to TRUE, the entire URI will be used when constructing the request. (i.e. GET https://www.example.com/path/to/file.html HTTP/1.0). While this is a non-standard request format, some proxy servers require it.';

$setting                                 = 'proxy_user'; // FIXME should be renamed to proxy|username
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'HTTP';
$config_variable[$setting]['name']       = 'HTTP(S) Proxy username (optional)';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Specifies the username to be used to authenticate to the configured HTTP proxy (basic auth). Leave empty if no authentication is required.';

$setting                                 = 'proxy_password'; // FIXME should be renamed to proxy|password
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'HTTP';
$config_variable[$setting]['name']       = 'HTTP(S) Proxy password (optional)';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Specifies the password to be used to authenticate to the configured HTTP proxy (basic auth). Leave empty if no authentication is required.';

/*

$config['autodiscovery']['snmpscan']       = TRUE; // Autodiscover hosts via SNMP scanning
^ NOT IMPLEMENTED

$config['autodiscovery']['ping_skip']      = FALSE; // Skip icmp echo checks during autodiscovery (beware timeouts during discovery!)

#$config['bad_xdp'][] = 'foo';
#$config['bad_xdp_regexp'][] = '/^SIP/'

*/

/// sysLocation ////////////////////////////////////////////////////////////

$section                           = 'syslocation';
$config_sections[$section]['text'] = 'Locations';

$setting                                      = 'location|rewrite_regexp';
$config_variable[$setting]['section']         = $section;
$config_variable[$setting]['subsection']      = 'General';
$config_variable[$setting]['name']            = 'Location Rewrite Reqular Expression';
$config_variable[$setting]['type']            = 'enum-key-value';
$config_variable[$setting]['params']['key']   = ['name' => 'Rewrite Regexp', 'type' => 'text'];
$config_variable[$setting]['params']['value'] = ['name' => 'Replace to', 'type' => 'text'];
$config_variable[$setting]['shortdesc']       = 'Use this feature for rewrite part of location string to reqired replacement by Reqular Expressions (ie: \'/C\$/\' -> \'ä\')';

$setting                                      = 'location|map';
$config_variable[$setting]['section']         = $section;
$config_variable[$setting]['subsection']      = 'General';
$config_variable[$setting]['name']            = 'Location Mapping';
$config_variable[$setting]['type']            = 'enum-key-value';
$config_variable[$setting]['params']['key']   = ['name' => 'Location', 'type' => 'text'];
$config_variable[$setting]['params']['value'] = ['name' => 'Rename to', 'type' => 'text'];
$config_variable[$setting]['shortdesc']       = 'Use this feature to map ugly locations to pretty locations (ie: \'Under the Sink\' -> \'Under The Sink, The Office, London, UK\')';

$setting                                      = 'location|map_regexp';
$config_variable[$setting]['section']         = $section;
$config_variable[$setting]['subsection']      = 'General';
$config_variable[$setting]['name']            = 'Location Mapping by Reqular Expression';
$config_variable[$setting]['type']            = 'enum-key-value';
$config_variable[$setting]['params']['key']   = ['name' => 'Location Regexp', 'type' => 'text'];
$config_variable[$setting]['params']['value'] = ['name' => 'Rename to', 'type' => 'text'];
$config_variable[$setting]['shortdesc']       = 'Use this feature to map ugly locations to pretty locations by Reqular Expressions (ie: \'/Under the Sink/\' -> \'Under The Sink, The Office, London, UK\')';

$setting                                 = 'geocoding|enable';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = 'Enable Geocoding';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable or disable geocoding of addresses. If disabled, best to disable the map on the front page as well.';

$setting                                              = 'geocoding|api';
$config_variable[$setting]['section']                 = $section;
$config_variable[$setting]['subsection']              = 'Geocoding';
$config_variable[$setting]['name']                    = 'Geolocation API';
$config_variable[$setting]['type']                    = 'enum';
$config_variable[$setting]['params']['geocodefarm']   = ['name' => 'Geocode.Farm',   'allowed' => 'geo_api|geocodefarm|key', 'subtext' => 'Free users have 250 req/day and 4 req/sec limit (IP based). Possible to configure API key.', 'desc' => 'Request a KEY <a href="https://geocode.farm/" target="_blank">here</a>'];
$config_variable[$setting]['params']['arcgis']        = ['name' => 'ArcGIS',        'subtext' => 'Free users have 25000 req/day limit.', 'desc' => 'Please see geocode quality <a href="https://developers.arcgis.com/rest/geocode/api-reference/geocode-coverage.htm" target="_blank">here</a>'];
$config_variable[$setting]['params']['openstreetmap'] = ['name' => 'OpenStreetMap', 'subtext' => 'Rate limit 150 req/day.', 'desc' => 'See the usage limits <a href="https://wiki.openstreetmap.org/wiki/Nominatim_usage_policy" target="_blank">here</a>'];
$config_variable[$setting]['params']['google']        = ['name' => 'Google',         'required' => 'geo_api|google|key', 'subtext' => 'API key REQUIRED.', 'desc' => 'Request a key <a href="https://developers.google.com/maps/documentation/geocoding/get-api-key" target="_blank">here</a>'];
$config_variable[$setting]['params']['bing']          = ['name' => 'Microsoft Bing', 'required' => 'geo_api|bing|key', 'subtext' => 'API key REQUIRED.', 'desc' => 'Request a key <a href="https://www.microsoft.com/en-us/maps/create-a-bing-maps-key" target="_blank">here</a>'];
$config_variable[$setting]['params']['yandex']        = ['name' => 'Yandex',         'allowed'  => 'geo_api|yandex|key', 'subtext' => 'Free users have 25000 req/day limit. Possible to configure API key.', 'desc' => 'Request a key <a href="https://tech.yandex.ru/maps/commercial/doc/concepts/how-to-buy-docpage" target="_blank">here</a>'];
$config_variable[$setting]['params']['mapquest']      = ['name' => 'MapQuest',       'required' => 'geo_api|mapquest|key', 'subtext' => 'API key REQUIRED.', 'desc' => 'Request a key <a href="https://developer.mapquest.com/user/register" target="_blank">here</a>'];
$config_variable[$setting]['params']['opencage']      = ['name' => 'OpenCage',       'required' => 'geo_api|opencage|key', 'subtext' => 'API key REQUIRED.', 'desc' => 'Request a key <a href="https://opencagedata.com/users/sign_up" target="_blank">here</a>'];
$config_variable[$setting]['params']['locationiq']    = ['name' => 'LocationIQ',     'required' => 'geo_api|locationiq|key', 'subtext' => 'API key REQUIRED.', 'desc' => 'Request a key <a href="https://locationiq.com/register" target="_blank">here</a>'];
$config_variable[$setting]['shortdesc']               = 'Which API to use to resolve your addresses into coordinates. If locations turn up unknown, try switching to another API.';
/*
$setting                                 = 'geocoding|api_key';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = '(DEPRECATED) API key for currently used GEO API';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'NOTE. Please use API specific KEY fields (below).';
*/
$setting                                 = 'geo_api|geocodefarm|key';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = 'Geocode.Farm API key';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Free users (without key) have 250 req/day and 4 req/sec limit (IP based).';

$setting                                 = 'geo_api|google|key';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = 'Google API key';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Google API REQUIRES a key!';

$setting                                 = 'geo_api|bing|key';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = 'Microsoft Bing API key';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Microsoft Bing API REQUIRES a key!';

$setting                                 = 'geo_api|yandex|key';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = 'Yandex API key';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Free users have 25000 req/day limit.';

$setting                                 = 'geo_api|mapquest|key';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = 'MapQuest API key';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'MapQuest API REQUIRES a key!';

$setting                                 = 'geo_api|opencage|key';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = 'OpenCage API key';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'OpenCage API REQUIRES a key!';

$setting                                 = 'geo_api|locationiq|key';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = 'LocationIQ API key';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'LocationIQ API REQUIRES a key!';

$setting                                 = 'geocoding|dns';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = 'Use DNS LOC records for geolocation';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Try to use DNS LOC records for detect device coordinates. See https://en.wikipedia.org/wiki/LOC_record and https://dnsloc.net/'; // FIXME actually link this

$setting                                 = 'geocoding|default|lat';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = 'Default latitude';
$config_variable[$setting]['type']       = 'float';
$config_variable[$setting]['shortdesc']  = 'This latitude value is used by default if a request to a geolocation API returns nothing.';

$setting                                 = 'geocoding|default|lon';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Geocoding';
$config_variable[$setting]['name']       = 'Default longitude';
$config_variable[$setting]['type']       = 'float';
$config_variable[$setting]['shortdesc']  = 'This longitude value is used by default if a request to a geolocation API returns nothing.';

$setting                                 = 'web_show_locations';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Web UI';
$config_variable[$setting]['name']       = 'Enable Locations on menu';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['shortdesc']  = "If disabled, the 'Locations' submenu under Devices is omitted.";

$setting                                 = 'location|menu|type';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Web UI';
$config_variable[$setting]['name']       = 'Location menu type';
$config_variable[$setting]['type']       = 'enum|geocoded|nested|plain';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['shortdesc']  = "Use either geocoded (nested by Country, County, etc), nested (by configured separator) or plain location menu (simple list). Nested and plain are useful if you don't have mappable addresses in your devices. Automatically set to plain if you completely disable geocoding.";

$setting                                 = 'location|menu|nested_reversed';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Web UI';
$config_variable[$setting]['name']       = 'Reverse Nested Location hierarchy';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['shortdesc']  = 'Treat locations as most-to-least significant instead of the other way around';

$setting                                 = 'location|menu|nested_max_depth';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Web UI';
$config_variable[$setting]['name']       = 'Nested Location menu depth';
$config_variable[$setting]['type']       = 'enum|2|3|4|5|6'; // Normally this setting is just int, but we limit it with a pre-defined list
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['shortdesc']  = 'Maximum depth to split the nested Location menu on.';

$setting                                 = 'location|menu|nested_split_char';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Web UI';
$config_variable[$setting]['name']       = 'Nested Location split character';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['useredit']   = TRUE;
$config_variable[$setting]['shortdesc']  = 'Character to split the nested Location menu on.';

/// SYSLOG //////////////////////////////////////////////////////

$config_sections['syslog']['text'] = 'Syslog';

$section = 'syslog';

$setting                                 = 'enable_syslog';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Enable Syslog';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable Syslog collector.';
$config_variable[$setting]['set_attrib'] = 'syslog_config_changed'; //set_obs_attrib('syslog_config_changed', time()); // Trigger reload syslog script

$setting                                 = 'syslog|debug';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Enable Syslog DEBUG';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "Enable store RAW syslog lines into logs/debug.log file. Make sure that logs dir and debug.log file have write permission for your syslog server user. For example in Ubuntu rsyslog uses user syslog, add write permission for it: `sudo chmod o+w " . $config['log_dir'] . "/debug.log`";
$config_variable[$setting]['set_attrib'] = 'syslog_config_changed'; //set_obs_attrib('syslog_config_changed', time()); // Trigger reload syslog script

$setting                                       = 'syslog|timestamp';
$config_variable[$setting]['section']          = $section;
$config_variable[$setting]['subsection']       = 'General';
$config_variable[$setting]['name']             = 'Timestamp';
$config_variable[$setting]['type']             = 'enum';
$config_variable[$setting]['params']['system'] = ['name' => 'System (default)'];
$config_variable[$setting]['params']['syslog'] = ['name' => 'Syslog'];
$config_variable[$setting]['params']['60']     = ['name' => '1 min difference'];
$config_variable[$setting]['params']['3600']   = ['name' => '1 hour difference'];
$config_variable[$setting]['params']['86400']  = ['name' => '1 day difference'];
$config_variable[$setting]['shortdesc']        = 'Use timestamp from Observium system (default) or from syslog server. You can set this param to number of seconds, when diff timestams of system and syslog greater this use syslog (instead system).';
$config_variable[$setting]['set_attrib']       = 'syslog_config_changed';

$setting                                 = 'syslog|fifo';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['locked']     = TRUE; // Do not allow this option to be edited to prevent users set it from the web.
$config_variable[$setting]['name']       = 'FIFO file';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Set this to a FIFO to take input from FIFO. Default: php://stdin';
$config_variable[$setting]['set_attrib'] = 'syslog_config_changed'; //set_obs_attrib('syslog_config_changed', time()); // Trigger reload syslog script

$setting                                 = 'syslog|use_ip';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Hosts & filters';
$config_variable[$setting]['name']       = 'Associate hosts by Cached IP';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "Allow associate syslog hosts with devices by Cached IP (resolved by DNS query)";
$config_variable[$setting]['set_attrib'] = 'syslog_config_changed';

$setting                                 = 'syslog|unknown_hosts';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Hosts & filters';
$config_variable[$setting]['name']       = 'Collect syslog messages from unknown hosts';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = "Allow collect syslog messages from unknown hosts. This entries not displayed in any place, while you not link this hosts to specific devices on device editpage or map in \$config['syslog']['host_map']";
$config_variable[$setting]['set_attrib'] = 'syslog_config_changed'; //set_obs_attrib('syslog_config_changed', time()); // Trigger reload syslog script

$setting                                 = 'syslog|filter';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Hosts & filters';
$config_variable[$setting]['name']       = 'Syslog messages filters';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['shortdesc']  = 'Filter (ignore) syslog entries containing these strings.';
$config_variable[$setting]['set_attrib'] = 'syslog_config_changed'; //set_obs_attrib('syslog_config_changed', time()); // Trigger reload syslog script

$setting                                 = 'syslog|filter_regex';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Hosts & filters';
$config_variable[$setting]['name']       = 'Syslog messages filters by regex';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['shortdesc']  = 'Filter (ignore) syslog entries containing these regular expressions.';
$config_variable[$setting]['set_attrib'] = 'syslog_config_changed'; //set_obs_attrib('syslog_config_changed', time()); // Trigger reload syslog script

/// INTEGRATION //////////////////////////////////////////////////////

$config_sections['integration']['text'] = 'Integration';

$section = 'integration';

$setting                                 = 'weathermap|enable';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Weathermap';
$config_variable[$setting]['name']       = 'Enable Built-in PHP Weathermap';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enables the built-in PHP Weathermap functionality. Note that the PHP Weathermap code is very, very old and may introduce bugs or security issues.';

$setting                                 = 'rancid_configs';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RANCID';
$config_variable[$setting]['name']       = 'RANCID configuration directories';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['shortdesc']  = 'Defines rancid configuration directories. This is an array, multiple rancid groups are supported. For performance, put your largest/most likely group in front. Configurations should have the same hostname as used in Observium. Leave empty to disable.';

$setting                                 = 'rancid_ignorecomments';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RANCID';
$config_variable[$setting]['name']       = 'Ignore comments';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Toggles whether or not to filter out RANCID comments (lines starting with #) in configuration files.';

$setting                                     = 'rancid_version';
$config_variable[$setting]['section']        = $section;
$config_variable[$setting]['subsection']     = 'RANCID';
$config_variable[$setting]['name']           = 'RANCID version used';
$config_variable[$setting]['type']           = 'enum';
$config_variable[$setting]['params']['2']    = [ 'name' => '2.0+' ];
$config_variable[$setting]['params']['3']    = [ 'name' => '3.0+' ];
$config_variable[$setting]['params']['3.2']  = [ 'name' => '3.2+' ];
$config_variable[$setting]['params']['3.3']  = [ 'name' => '3.3+' ];
$config_variable[$setting]['params']['3.4']  = [ 'name' => '3.4+' ];
$config_variable[$setting]['params']['3.5']  = [ 'name' => '3.5+' ];
$config_variable[$setting]['params']['3.7']  = [ 'name' => '3.7+' ];
$config_variable[$setting]['params']['3.8']  = [ 'name' => '3.8+' ];
$config_variable[$setting]['params']['3.9']  = [ 'name' => '3.9+' ];
$config_variable[$setting]['params']['3.10'] = [ 'name' => '3.10+' ];
$config_variable[$setting]['params']['3.11'] = [ 'name' => '3.11+' ];
$config_variable[$setting]['params']['3.12'] = [ 'name' => '3.12+' ];
$config_variable[$setting]['params']['3.13'] = [ 'name' => '3.13+' ];
$config_variable[$setting]['shortdesc']      = 'Depending on the RANCID version, a different delimiter is used in the RANCID configuration files ":" for 2.x and ";" for 3.x.';

$setting                                 = 'rancid_revisions';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RANCID';
$config_variable[$setting]['name']       = 'RANCID revisions';
$config_variable[$setting]['type']       = 'enum|5|10|15|20|30';
$config_variable[$setting]['shortdesc']  = 'Show such count of latest revisions for config changes.';

$setting                                 = 'rancid_suffix';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RANCID';
$config_variable[$setting]['name']       = 'Hostname suffix';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'This string is added to the hostname in RANCID, for non-FQDN device names.';

$setting                                 = 'smokeping|dir';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Smokeping';
$config_variable[$setting]['name']       = 'Smokeping directory';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Defines the smokeping directory containing the RRD files. Names in Smokeping should use the split character (as defined below) instead of dots.';

$setting                                 = 'smokeping|master_hostname';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Smokeping';
$config_variable[$setting]['name']       = 'Master hostname';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Defines the hostname of your Smokeping instance. Useful if you are NFS mounting the smokeping directory to Observium from another server. Defaults to the Observium server\'s hostname (own_hostname setting) if unset.';

$setting                                 = 'smokeping|split_char';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Smokeping';
$config_variable[$setting]['name']       = 'Split character';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Defines what character to use in Smokeping host titles, to replace the dot as this is not allowed in Smokeping configuration.';

$setting                                 = 'smokeping|suffix';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Smokeping';
$config_variable[$setting]['name']       = 'Hostname suffix';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'This string is added to the hostname in Smokeping, for non-FQDN device names.';

$setting                                 = 'smokeping|slaves';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Smokeping';
$config_variable[$setting]['name']       = 'Smokeping slaves';
$config_variable[$setting]['type']       = 'enum-freeinput';
$config_variable[$setting]['shortdesc']  = 'Defines slaves to be used in Smokeping configuration. Only used by the Smokeping configuration generator script.';

$setting                                 = 'collectd_dir';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'CollectD';
$config_variable[$setting]['name']       = 'CollectD directory';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Defines collectd directory. Hosts should be set to use the same hostname in collectd.conf as is used in Observium.';

$setting                                 = 'nfsen_enable';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'NfSen';
$config_variable[$setting]['name']       = 'Enable NfSen integration';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Allows you to read RRD files created by NfSen.';

$setting                                 = 'nfsen_rrds';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'NfSen';
$config_variable[$setting]['name']       = 'NfSen RRD path';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Local file system path to NfSen RRD files';

$setting                                 = 'nfsen_split_char';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'NfSen';
$config_variable[$setting]['name']       = 'NfSen split character';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'This character will be converted to dots (router1_foo -> router1.foo) and nfsen_suffix will be appended (router1_foo_yourdomain_com - > router1.foo.yourdomain.com).';

$setting                                 = 'nfsen_suffix';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'NfSen';
$config_variable[$setting]['name']       = 'NfSen suffix';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'This string is added to the hostname in NfSen. According to nfsen.conf, ident strings must be 1 to 19 characters long only, containing characters [a-zA-Z0-9_].';

/// ROUTING //////////////////////////////////////////////////////////

$section                            = 'routing';
$config_sections['routing']['text'] = 'Routing';

$setting                                 = 'enable_bgp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Protocols';
$config_variable[$setting]['name']       = 'BGP collection';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable collection and display of BGP data.';

//$setting = 'enable_rip';
//$config_variable[$setting]['section']    = $section;
//$config_variable[$setting]['subsection'] = 'Protocols';
//$config_variable[$setting]['name']       = 'RIP sessions';
//$config_variable[$setting]['type']       = 'bool';
//$config_variable[$setting]['shortdesc']  = 'Enable collection and display of RIP data.';

$setting                                 = 'enable_ospf';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Protocols';
$config_variable[$setting]['name']       = 'OSPF collection';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable collection and display of OSPF data.';

//$setting = 'enable_isis';
//$config_variable[$setting]['section']    = $section;
//$config_variable[$setting]['subsection'] = 'Protocols';
//$config_variable[$setting]['name']       = 'ISIS sessions';
//$config_variable[$setting]['type']       = 'bool';
//$config_variable[$setting]['shortdesc']  = 'Enable collection and display of ISIS data.';

$setting                                 = 'enable_eigrp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Protocols';
$config_variable[$setting]['name']       = 'EIGRP collection';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable collection and display of EIGRP data.';

$setting                                 = 'enable_vrfs';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Protocols';
$config_variable[$setting]['name']       = 'VRF collection';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable collection and display of VRF data.';

$setting                                 = 'web_show_bgp_asdot';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'BGP';
$config_variable[$setting]['name']       = 'BGP 32bit ASN in asdot format';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Show BGP 32bit ASNs in asdot format (ie AS5.20 instead AS327700).';

$setting                                      = 'astext';
$config_variable[$setting]['section']         = $section;
$config_variable[$setting]['subsection']      = 'BGP';
$config_variable[$setting]['name']            = 'AS Text Mapping';
$config_variable[$setting]['type']            = 'enum-key-value';
$config_variable[$setting]['params']['key']   = ['name' => 'ASN',     'type' => 'text'];
$config_variable[$setting]['params']['value'] = ['name' => 'AS text', 'type' => 'text'];
$config_variable[$setting]['shortdesc']       = 'Set own AS text for private ASes or override RIPE DB assigned name.';


/// API //////////////////////////////////////////////////////////

$section                           = 'api';
$config_sections[$section]['text'] = 'API';

$setting                                 = 'api|enable';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']      = 'Enable API';
$config_variable[$setting]['type']      = 'bool';
$config_variable[$setting]['shortdesc'] = 'Enable or disable the API.';

// Endpoints

$setting                                 = 'api|endpoints|alerts';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Alerts';
$config_variable[$setting]['type']       = 'bool';

$setting                                 = 'api|endpoints|bills';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Billing';
$config_variable[$setting]['type']       = 'bool';

$setting                                 = 'api|endpoints|devices';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Devices';
$config_variable[$setting]['type']       = 'bool';

$setting                                 = 'api|endpoints|ports';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Ports';
$config_variable[$setting]['type']       = 'bool';

$setting                                 = 'api|endpoints|sensors';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Sensors';
$config_variable[$setting]['type']       = 'bool';

$setting                                 = 'api|endpoints|status';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Statuses';
$config_variable[$setting]['type']       = 'bool';
/*
$setting = 'api|endpoints|counters';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Counters';
$config_variable[$setting]['type']       = 'bool';
*/
$setting                                 = 'api|endpoints|storage';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Storages';
$config_variable[$setting]['type']       = 'bool';

$setting                                 = 'api|endpoints|mempools';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Memory Pools';
$config_variable[$setting]['type']       = 'bool';

$setting                                 = 'api|endpoints|address';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'IP Addresses';
$config_variable[$setting]['type']       = 'bool';

$setting                                 = 'api|endpoints|printersupplies';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Printer Supplies';
$config_variable[$setting]['type']       = 'bool';

$setting                                 = 'api|endpoints|inventory';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Inventories';
$config_variable[$setting]['type']       = 'bool';

$setting                                 = 'api|endpoints|neighbours';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'Neighbours';
$config_variable[$setting]['type']       = 'bool';

$setting                                 = 'api|endpoints|vlans';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Endpoints';
$config_variable[$setting]['name']       = 'VLANs';
$config_variable[$setting]['type']       = 'bool';


/// BILLING //////////////////////////////////////////////////////////

$section                              = 'billing';
$config_sections[$section]['text']    = 'Billing';
$config_sections[$section]['edition'] = 'pro';

$setting                                 = 'enable_billing';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['edition']    = 'pro';
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Billing module';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable Billing module';

/* Unimplemented :)
$setting = 'billing|customer_autoadd';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['edition']    = 'pro';
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Customer auto-add';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable Auto-add bill per customer';

$setting = 'billing|circuit_autoadd';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['edition']    = 'pro';
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Circuit ID auto-add';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable Auto-add bill per circuit_id';

$setting = 'billing|bill_autoadd';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['edition']    = 'pro';
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Bill ID auto-add';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Enable Auto-add bill per bill_id';
*/

$setting                                 = 'billing|base';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['edition']    = 'pro';
$config_variable[$setting]['subsection'] = 'General';
$config_variable[$setting]['name']       = 'Billing base';
$config_variable[$setting]['type']       = 'enum';
$config_variable[$setting]['params']     = [
  1000 => ['name' => '1000', 'subtext' => '1kB = 1000B'],
  1024 => ['name' => '1024', 'subtext' => '1kB = 1024B']
];
$config_variable[$setting]['shortdesc']  = 'Set the base to divider bytes to kB, MB, GB, ... 1000 or 1024';

/// HOUSEKEEPING /////////////////////////////////////////////////////

$section                           = 'housekeeping';
$config_sections[$section]['text'] = 'Housekeeping';

$setting                                 = 'housekeeping|eventlog|age';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Event Log';
$config_variable[$setting]['name']       = 'Event Log Max Age';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Maximum age of Event Log entries in seconds; 0 to disable (i.e. 30*86400 for 30 days.)';

$setting                                 = 'housekeeping|syslog|age';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Syslog';
$config_variable[$setting]['name']       = 'Syslog Max Age';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Maximum age of syslog entries in seconds; 0 to disable (i.e. 30*86400 for 30 days.)';

$setting                                 = 'housekeeping|alertlog|age';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Alert Log';
$config_variable[$setting]['name']       = 'Alert Log Max Age';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Maximum age of Alert Log entries in seconds; 0 to disable (i.e. 30*86400 for 30 days.)';

$setting                                 = 'housekeeping|authlog|age';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Auth Log';
$config_variable[$setting]['name']       = 'Auth Log Max Age';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Maximum age of Authentication Log entries in seconds; 0 to disable (i.e. 30*86400 for 30 days.)';

$setting                                 = 'housekeeping|rrd|age';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RRD Files';
$config_variable[$setting]['name']       = 'RRD File Max Age';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Maximum time since an RRD file was last updated in seconds. Useful for deleting old RRDs for removed ports and sensors. (i.e. 30*86400 for 30 days.)';

$setting                                 = 'housekeeping|rrd|notmodified';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RRD Files';
$config_variable[$setting]['name']       = 'Delete old RRD files';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Delete .rrd files not modified more than age (eg removed entities)';

$setting                                 = 'housekeeping|rrd|invalid';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RRD Files';
$config_variable[$setting]['name']       = 'Delete invalid RRD files';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Delete .rrd files that are not valid RRD files (eg created with a full disk)';

$setting                                 = 'housekeeping|rrd|deleted';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RRD Files';
$config_variable[$setting]['name']       = 'Delete stale RRD dirs';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Delete RRD directories for non-existent hostnames (deleted devices from db)';

$setting                                 = 'housekeeping|rrd|disabled';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'RRD Files';
$config_variable[$setting]['name']       = 'Delete old RRD dirs for disabled devices';
$config_variable[$setting]['type']       = 'bool';
$config_variable[$setting]['shortdesc']  = 'Delete rrd dirs for disabled devices more than age ago(device still in db, but disabled by some reasons)';

$setting                                 = 'housekeeping|deleted_ports|age';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Ports';
$config_variable[$setting]['name']       = 'Remove Deleted Ports Age';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Maximum age of deleted ports in seconds before automatically purging; 0 to disable (i.e. 30*86400 for 30 days.)';

/* Paths commented out
/// PATHS ////////////////////////////////////////////////////////////

$section = 'paths';
$config_sections[$section]['text'] = 'System Paths';

// Observium system paths

$setting = 'temp_dir';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'System paths';
$config_variable[$setting]['name']       = 'Temporary directory';
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = 'Path to write temporary files to (e.g. RRDtool graphs). Must be writable by the webserver user.';

// Essential binaries

$setting = 'rrdtool';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Essential binaries';
$config_variable[$setting]['name']       = "Path to 'rrdtool' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'rrdtool' binary. Required.";

$setting = 'fping';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Essential binaries';
$config_variable[$setting]['name']       = "Path to 'fping' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'fping' binary. Required for IPv4 support.";

$setting = 'fping6';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Essential binaries';
$config_variable[$setting]['name']       = "Path to 'fping6' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'fping6' binary. Required for IPv6 support.";

$setting = 'svn';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Essential binaries';
$config_variable[$setting]['name']       = "Path to 'svn' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'svn' binary. Required for versioning (in Pro) or for RANCID svn-based repository support.";

// SNMP

$setting = 'snmpget';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'SNMP';
$config_variable[$setting]['name']       = "Path to 'snmpget' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'snmpget' binary. Required.";

$setting = 'snmpwalk';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'SNMP';
$config_variable[$setting]['name']       = "Path to 'snmpwalk' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'snmpwalk' binary. Required.";

$setting = 'snmpbulkget';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'SNMP';
$config_variable[$setting]['name']       = "Path to 'snmpbulkget' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'snmpbulkget' binary. Required.";

$setting = 'snmpbulkwalk';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'SNMP';
$config_variable[$setting]['name']       = "Path to 'snmpbulkwalk' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'snmpbulkwalk' binary. Required.";

$setting = 'snmptranslate';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'SNMP';
$config_variable[$setting]['name']       = "Path to 'snmptranslate' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'snmptranslate' binary. Required.";

// Integration

$setting = 'ipmitool';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Integration';
$config_variable[$setting]['name']       = "Path to 'ipmitool' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'ipmitool' binary. Required for IPMI polling support.";

$setting = 'virsh';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Integration';
$config_variable[$setting]['name']       = "Path to 'virsh' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'virsh' binary. Required for libvirt-based Virtual Machine polling support.";

$setting = 'wmic';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Integration';
$config_variable[$setting]['name']       = "Path to 'wmic' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'wmic' binary. Required for WMI (Windows Management Instrumentation) polling support.";

// Web UI toolkit

$setting = 'whois';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Network tools';
$config_variable[$setting]['name']       = "Path to 'whois' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'whois' binary. Required for whois support in the web interface.";

$setting = 'mtr';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Network tools';
$config_variable[$setting]['name']       = "Path to 'mtr' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'mtr' binary. Required for mtr support in the web interface.";

$setting = 'nmap';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Network tools';
$config_variable[$setting]['name']       = "Path to 'nmap' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'nmap' binary. Required for nmap support in the web interface.";

// RANCID

$setting = 'git';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Integration';
$config_variable[$setting]['name']       = "Path to 'git' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'git' binary. Required for RANCID git-based repository support.";

// Mapping

$setting = 'dot';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Neighbour maps';
$config_variable[$setting]['name']       = "Path to 'dot' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'dot' graphviz binary. Required for display of neighbour maps";
*/

/* All of this binaries not used for now in neighbour maps
$setting = 'unflatten';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Neighbour maps';
$config_variable[$setting]['name']       = "Path to 'unflatten' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'unflatten' graphviz binary. Required for display of neighbour maps";

$setting = 'neato';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Neighbour maps';
$config_variable[$setting]['name']       = "Path to 'neato' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'neato' graphviz binary. Required for display of neighbour maps";

$setting = 'sfdp';
$config_variable[$setting]['section']    = $section;
$config_variable[$setting]['subsection'] = 'Neighbour maps';
$config_variable[$setting]['name']       = "Path to 'sfdp' binary";
$config_variable[$setting]['type']       = 'string';
$config_variable[$setting]['shortdesc']  = "Path to the 'sfdp' graphviz binary. Required for display of neighbour maps";
*/

// EOF
