#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     cli
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/// FIXME. This is mostly DERP arguments parsing, new cmd will be soon

chdir(dirname($argv[0]));

$options = getopt("dhpt");
if (isset($options['d'])) {
    array_shift($argv);
}

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
// Just test, do not add device
if (isset($options['t'])) {
    $snmp_options['test'] = TRUE;
    array_shift($argv);
}
// Add skip pingable checks if argument -p passed
if (isset($options['p'])) {
    $snmp_options['ping_skip'] = 1;
    array_shift($argv);
}

$added = 0;

if (!empty($argv[1])) {
    $add_array = [];
    if (is_file($argv[1])) {
        // Parse file into array with devices to add
        foreach (new SplFileObject($argv[1]) as $line) {
            $d = preg_split('/\s/', $line, -1, PREG_SPLIT_NO_EMPTY);
            if (empty($d) || strpos(reset($d), '#') === 0) {
                continue;
            }
            $add_array[] = $d;
        }
    } else {
        $add_array[0] = $argv;
        array_shift($add_array[0]);
    }

    // Save base SNMP v3 credentials and v2c/v1 community
    $snmp_config_v3        = $config['snmp']['v3'];
    $snmp_config_community = $config['snmp']['community'];

    foreach ($add_array as $add) {
        $hostname       = strtolower($add[0]);
        $snmp_community = $add[1];
        $snmp_version   = strtolower($add[2]);

        $snmp_port = 161;
        if (str_contains($hostname, ':') && get_ip_version($hostname) !== 6) {
            // Allow pass common hostname:port
            [$host_tmp, $port_tmp] = explode(':', $hostname, 2);
            if (is_valid_param($port_tmp, 'port')) {
                $hostname  = $host_tmp;
                $snmp_port = $port_tmp;
            }
            unset($host_tmp, $port_tmp);
        }
        $snmp_transport = 'udp';

        if ($snmp_version === "v3") {
            $config['snmp']['v3'] = $snmp_config_v3; // Restore base SNMP v3 credentials
            $snmp_v3_seclevel     = $snmp_community;

            // These values are the same as in defaults.inc.php
            $snmp_v3_auth = [
              'authlevel'  => "noAuthNoPriv",
              'authname'   => "observium",
              'authpass'   => "",
              'authalgo'   => "MD5",
              'cryptopass' => "",
              'cryptoalgo' => "AES"
            ];

            $add_context = FALSE; // Derp, last arg after transport is context
            if ($snmp_v3_seclevel === "nanp" || $snmp_v3_seclevel === "any" || $snmp_v3_seclevel === "noAuthNoPriv") {
                $snmp_v3_auth['authlevel'] = "noAuthNoPriv";
                $snmp_v3_args              = array_slice($add, 3);

                while ($arg = array_shift($snmp_v3_args)) {
                    // parse all remaining args
                    if (is_valid_param($arg, 'port')) {
                        $snmp_port = $arg;
                    } elseif (preg_match('/^(' . implode("|", $config['snmp']['transports']) . ')$/', $arg)) {
                        $snmp_transport = $arg;
                        $add_context    = TRUE; // Derp, last arg after transport is context
                    } elseif ($add_context && strlen($arg)) {
                        $snmp_context = $arg;
                        break;
                    } else {
                        // FIXME: should add a sanity check of chars allowed in user
                        $user = $arg;
                    }
                }

                if ($snmp_v3_seclevel !== "any") {
                    $config['snmp']['v3'] = [$snmp_v3_auth];
                }
            } elseif ($snmp_v3_seclevel === "anp" || $snmp_v3_seclevel === "authNoPriv") {

                $snmp_v3_auth['authlevel'] = "authNoPriv";
                $snmp_v3_args              = array_slice($argv, 4);
                $snmp_v3_auth['authname']  = array_shift($snmp_v3_args);
                $snmp_v3_auth['authpass']  = array_shift($snmp_v3_args);

                while ($arg = array_shift($snmp_v3_args)) {
                    // parse all remaining args
                    if (is_valid_param($arg, 'port')) {
                        $snmp_port = $arg;
                    } elseif (preg_match('/^(' . implode("|", $config['snmp']['transports']) . ')$/i', $arg)) {
                        $snmp_transport = $arg;
                        $add_context    = TRUE; // Derp, last arg after transport is context
                    } elseif (is_valid_param($arg, 'snmp_authalgo')) {
                        $snmp_v3_auth['authalgo'] = $arg;
                    } elseif ($add_context && strlen($arg)) {
                        $snmp_context = $arg;
                        break;
                    }
                }

                $config['snmp']['v3'] = [$snmp_v3_auth];
            } elseif ($snmp_v3_seclevel === "ap" || $snmp_v3_seclevel === "authPriv") {
                $snmp_v3_auth['authlevel']  = "authPriv";
                $snmp_v3_args               = array_slice($argv, 4);
                $snmp_v3_auth['authname']   = array_shift($snmp_v3_args);
                $snmp_v3_auth['authpass']   = array_shift($snmp_v3_args);
                $snmp_v3_auth['cryptopass'] = array_shift($snmp_v3_args);

                while ($arg = array_shift($snmp_v3_args)) {
                    // parse all remaining args
                    if (is_valid_param($arg, 'port')) {
                        $snmp_port = $arg;
                    } elseif (preg_match('/^(' . implode("|", $config['snmp']['transports']) . ')$/i', $arg)) {
                        $snmp_transport = $arg;
                        $add_context    = TRUE; // Derp, last arg after transport is context
                    } elseif (is_valid_param($arg, 'snmp_authalgo')) {
                        $snmp_v3_auth['authalgo'] = $arg;
                    } elseif (is_valid_param($arg, 'snmp_cryptoalgo')) {
                        $snmp_v3_auth['cryptoalgo'] = $arg;
                    } elseif ($add_context && strlen($arg)) {
                        $snmp_context = $arg;
                        break;
                    }
                }

                $config['snmp']['v3'] = [$snmp_v3_auth];
            }
            //print_debug_vars($snmp_v3_auth);
            //print_debug_vars($config['snmp']['v3']);
        } else {
            // v1 or v2c
            $snmp_v2_args = array_slice($argv, 2);

            $add_context = FALSE; // Derp, last arg after transport is context
            while ($arg = array_shift($snmp_v2_args)) {
                // parse all remaining args
                if (is_valid_param($arg, 'port')) {
                    $snmp_port = $arg;
                } elseif (preg_match('/(' . implode("|", $config['snmp']['transports']) . ')/i', $arg)) {
                    $snmp_transport = $arg;
                    $add_context    = TRUE; // Derp, last arg after transport is context
                } elseif (preg_match('/^(v1|v2c)$/i', $arg)) {
                    $snmp_version = $arg;
                } elseif ($add_context && strlen($arg)) {
                    $snmp_context = $arg;
                    break;
                }
            }

            $config['snmp']['community'] = ($snmp_community ? [$snmp_community] : $snmp_config_community);
        }

        // Add snmp context to params
        if (isset($snmp_context)) {
            $snmp_options['snmp_context'] = $snmp_context;
            unset($snmp_context);
        }

        print_message("Try to add $hostname:");
        if (in_array($snmp_version, ['v1', 'v2c', 'v3'])) {
            // If snmp version passed in arguments, then use the exact version
            $device_id = add_device($hostname, $snmp_version, $snmp_port, $snmp_transport, $snmp_options);
        } else {
            // If snmp version unknown check all possible snmp versions and auth options
            $device_id = add_device($hostname, NULL, $snmp_port, $snmp_transport, $snmp_options);
        }

        if ($device_id) {
            if (!isset($options['t'])) {
                $device = device_by_id_cache($device_id);
                print_success("Added device " . $device['hostname'] . " (" . $device_id . ").");
            } // Else this is device testing, success message already written by add_device()
            $added++;
        }
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

function print_help($scriptname)
{
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
