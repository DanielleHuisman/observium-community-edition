#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage cli
 * @copyright  (C) Adam Armstrong
 *
 */

/// FIXME. This is mostly DERP arguments parsing

chdir(dirname($argv[0]));

$options = getopt("dhpt", [], $opt_index);

include("includes/observium.inc.php");
include("includes/discovery/functions.inc.php");

print_message("%g" . OBSERVIUM_PRODUCT . " " . OBSERVIUM_VERSION . "\n%WAdd Device(s)%n\n", 'color');

if (OBS_DEBUG) {
    print_versions();
}

if (isset($options['h'])) {
    print_help(OBS_SCRIPT_NAME);
    exit;
}

$snmp_options = [];
// Just test, do not add a device
if (isset($options['t'])) {
    $snmp_options['test'] = TRUE;
}
// Add skip pingable checks if argument -p passed
if (isset($options['p'])) {
    $snmp_options['ping_skip'] = 1;
}
// Remove options and script name from argv
$argv = array_slice($argv, $opt_index);

$added     = 0;
$add_array = [];
if (!empty($argv[0])) {
    if (is_file($argv[0])) {
        // Parse file into an array with devices to add
        foreach (new SplFileObject($argv[0]) as $line) {
            $d = preg_split('/\s/', $line, -1, PREG_SPLIT_NO_EMPTY);
            if (empty($d) || str_starts_with($d[0], '#')) {
                // Skip empty lines or commented
                continue;
            }
            $add_array[] = $d;
        }
    } else {
        $add_array[] = $argv;
    }

    // Save base SNMP v3 credentials and v2c/v1 community
    $snmp_config_v3        = $config['snmp']['v3'];
    $snmp_config_community = $config['snmp']['community'];

    foreach ($add_array as $add) {
        $snmp = get_device_snmp_argv($add, $snmp_options);
        if (!$snmp) {
            //print_error("Try to add $hostname:");
            continue;
        }
        $hostname       = $snmp['hostname'];
        $snmp_version   = $snmp['snmp_version'];
        $snmp_transport = $snmp['snmp_transport'];
        $snmp_port      = $snmp['snmp_port'];

        // FIXME. Still used hard set v2c/v3 auth by config
        if ($snmp_version === "v3") {
            // v3
            $config['snmp']['v3'] = $snmp['snmp_v3_auth'];
        } elseif (!empty($snmp_version)) {
            // v1 or v2c
            $config['snmp']['community'] = $snmp['snmp_community'];
        }

        print_message("Try to add $hostname:");

        // If a known snmp version passed in arguments, then use the exact version (v1, v2c, v3)
        // otherwise checks all possible snmp versions and auth options
        if ($device_id = add_device($hostname, $snmp_version, $snmp_port, $snmp_transport, $snmp_options)) {
            if (!isset($snmp_options['test'])) {
                $device = device_by_id_cache($device_id);
                print_success("Added device " . $device['hostname'] . " (" . $device_id . ").");
            } // Else this is device testing, success message already written by add_device()
            $added++;
        }

        // Restore base SNMP v1/2c/3 credentials (need for add multiple devices)
        $config['snmp']['community'] = $snmp_config_community;
        $config['snmp']['v3']        = $snmp_config_v3;
    }
}

$count  = safe_count($add_array);
$failed = $count - $added;
if ($added) {
    print_message("\nDevices success: $added.");
    if ($failed) {
        print_message("Devices failed: $failed.");
    }
} else {
    if ($count) {
        print_message("Devices failed: $failed.");
    }
    print_help(OBS_SCRIPT_NAME);
}

function print_help($scriptname) {
    global $config;

    $snmp_version = get_versions('snmp');
    if (version_compare($snmp_version, '5.8', '<')) {
        $snmpv3_auth   = '[md5|sha]';
        $snmpv3_crypto = '[des|aes]';
    } else {
        $snmpv3_auth   = '[md5|sha|sha-224|sha-256|sha-384|sha-512]';
        $snmpv3_crypto = '[des|aes|aes-192|aes-192-c|aes-256|aes-256-c]';
    }
    $snmp_transports = '[' . implode("|", $config['snmp']['transports']) . ']';
    print_message("%n
USAGE:
$scriptname <hostname> [community] [v1|v2c] [port] $snmp_transports
$scriptname <hostname> [any|nanp|anp|ap] [v3] [user] [password] [enckey] $snmpv3_auth $snmpv3_crypto [port] $snmp_transports
$scriptname <filename>

EXAMPLE:
%WSNMPv1/2c%n:                    $scriptname <%Whostname%n> [community] [v1|v2c] [port] $snmp_transports [context]
%WSNMPv3%n   :         Defaults : $scriptname <%Whostname%n> any v3 [user] [port] $snmp_transports [context]
           No Auth, No Priv : $scriptname <%Whostname%n> nanp v3 [user] [port] $snmp_transports [context]
              Auth, No Priv : $scriptname <%Whostname%n> anp v3 <user> <password> $snmpv3_auth [port] $snmp_transports [context]
              Auth,    Priv : $scriptname <%Whostname%n> ap v3 <user> <password> <enckey> $snmpv3_auth $snmpv3_crypto [port] $snmp_transports [context]
%WFILE%n     :                    $scriptname <%Wfilename%n>

ADD FROM FILE:
 To add multiple devices, create a file in which each line contains one device with or without options.
 Format for device options, the same as specified in USAGE.

OPTIONS:
 -p                                          Skip icmp echo checks, device added only by SNMP checks

DEBUGGING OPTIONS:
 -d                                          Enable debugging output.
 -dd                                         More verbose debugging output.
 -t                                          Do not add device(s), only test auth options.", 'color', FALSE);
}

// EOF
