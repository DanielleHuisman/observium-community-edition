<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     wmi
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

function wmi_get($device, $wql, $namespace = NULL)
{
    // Simplify this: wmi_parse(wmi_query($wql, $override), TRUE, "Name");
    return wmi_parse(wmi_query($wql, $device), TRUE, $namespace);
}

function wmi_get_all($device, $wql, $namespace = NULL)
{
    // Simplify this: wmi_parse(wmi_query($wql, $override));
    return wmi_parse(wmi_query($wql, $device), FALSE, $namespace);
}

function wmi_cmd($device, $namespace = NULL)
{
    global $config, $attribs;

    // Override old default wmic cmd path if not found
    $wmic_script = $config['install_dir'] . '/scripts/wmic';
    if (!isset($GLOBALS['cache']['wmic_type'])) {
        if ($config['wmic'] === '/bin/wmic' && !is_file($config['wmic'])) {
            // Detect if python script available, instead
            //print_vars(external_exec($wmic_script . ' -h'));
            //print_vars(str_contains(external_exec($wmic_script . ' -h'), 'WMI client'));
            if (is_executable($wmic_script) && str_contains(external_exec($wmic_script . ' -h'), 'WMI client')) {
                $GLOBALS['cache']['wmic_exec'] = $wmic_script;
                $GLOBALS['cache']['wmic_type'] = 'script';
            } elseif (is_executable('/usr/bin/wmic')) {
                $GLOBALS['cache']['wmic_exec'] = '/usr/bin/wmic';
                $GLOBALS['cache']['wmic_type'] = 'binary';
            } else {
                $GLOBALS['cache']['wmic_type'] = 'error';
                if (is_executable($wmic_script)) {
                    print_warning("For use WMIC script please install additional python packages:
                         sudo pip3 install natsort
                         sudo pip3 install impacket");
                }
            }
        } elseif (is_executable($config['wmic'])) {
            // Old or custom WMIC path
            $GLOBALS['cache']['wmic_exec'] = $config['wmic'];
            if (str_contains(external_exec($config['wmic'] . ' -h'), 'WMI client')) {
                // Detect if script used
                $GLOBALS['cache']['wmic_type'] = 'script';
            } else {
                $GLOBALS['cache']['wmic_type'] = 'binary';
            }
        } else {
            $GLOBALS['cache']['wmic_type'] = 'error';
        }
    }
    if ($GLOBALS['cache']['wmic_type'] === 'error') {
        print_error("WMI ERROR: The wmic binary was not found at the configured path (" . $config['wmic'] . ").");
        return FALSE;
    }

    if (!is_array($attribs)) {
        $attribs = get_entity_attribs('device', $device['device_id']);
    }

    if (!isset($namespace)) {
        $namespace = $config['wmi']['namespace'];
    }

    if (isset($attribs['wmi_override']) && $attribs['wmi_override']) {
        // WMI auth for this device only
        $hostname = $attribs['wmi_hostname'];
        if (empty($hostname)) {
            $hostname = $GLOBALS['device']['hostname'];
        }
        $domain   = $attribs['wmi_domain'];
        $username = $attribs['wmi_username'];
        $password = $attribs['wmi_password'];
    } else {
        // WMI auth global config
        $hostname = $GLOBALS['device']['hostname'];
        $domain   = $config['wmi']['domain'];
        $username = $config['wmi']['user'];
        $password = $config['wmi']['pass'];
    }

    if (empty($hostname)) {
        $hostname = $device['hostname'];
    }

    if (safe_empty($username) || safe_empty($hostname)) {
        print_debug("WMI ERROR: Empty hostname or username.");
        return FALSE;
    }

    if ($GLOBALS['cache']['wmic_type'] === 'script') {
        // usage: wmic [-h] [-U USER] [-A AUTHFILE] [--delimiter DELIMITER] [--namespace NAMESPACE] //host query
        //
        // WMI client
        //
        // positional arguments:
        //   //host
        //   query
        //
        // optional arguments:
        //   -h, --help            show this help message and exit
        //   -U USER, --user USER  [DOMAIN\]USERNAME[%PASSWORD]
        //   -A AUTHFILE, --authentication-file AUTHFILE
        //                         Authentication file
        //   --delimiter DELIMITER
        //                         delimiter, default: |
        //   --namespace NAMESPACE
        //                         namespace name (default //./root/cimv2)

        $wmic_auth = '';
        if (!safe_empty($domain)) {
            $wmic_auth .= $domain . '\\';
        }
        $wmic_auth .= $username;
        if (!safe_empty($password)) {
            $wmic_auth .= '%' . $password;
        }
        $options = " --user " . escapeshellarg($wmic_auth);
        if (safe_empty($config['wmi']['delimiter'])) {
            $options .= " --delimiter ##"; // FIXME. escaping
        } else {
            $options .= " --delimiter " . escapeshellarg($config['wmi']['delimiter']);
        }
        if (safe_empty($namespace)) {
            $options .= " --namespace 'root\CIMV2'";
        } else {
            $options .= " --namespace " . escapeshellarg($namespace);
        }
    } else {
        // Usage: [-?|--help] [--usage] [-d|--debuglevel DEBUGLEVEL] [--debug-stderr]
        //         [-s|--configfile CONFIGFILE] [--option=name=value]
        //         [-l|--log-basename LOGFILEBASE] [--leak-report] [--leak-report-full]
        //         [-R|--name-resolve NAME-RESOLVE-ORDER]
        //         [-O|--socket-options SOCKETOPTIONS] [-n|--netbiosname NETBIOSNAME]
        //         [-W|--workgroup WORKGROUP] [--realm=REALM] [-i|--scope SCOPE]
        //         [-m|--maxprotocol MAXPROTOCOL] [-U|--user [DOMAIN\]USERNAME[%PASSWORD]]
        //         [-N|--no-pass] [--password=STRING] [-A|--authentication-file FILE]
        //         [-S|--signing on|off|required] [-P|--machine-pass]
        //         [--simple-bind-dn=STRING] [-k|--kerberos STRING]
        //         [--use-security-mechanisms=STRING] [-V|--version] [--namespace=STRING]
        //         [--delimiter=STRING]
        //         //host query
        //
        // Example: wmic -U [domain/]adminuser%password //host "select * from Win32_ComputerSystem"
        $options = " --user=" . escapeshellarg($username);
        if (empty($password)) {
            $options .= " --no-pass";
        } else {
            $options .= " --password=" . escapeshellarg($password);
        }
        if (!safe_empty($domain)) {
            $options .= " --workgroup=" . escapeshellarg($domain);
        }
        if (safe_empty($config['wmi']['delimiter'])) {
            $options .= " --delimiter=##"; // FIXME. escaping
        } else {
            $options .= " --delimiter=" . escapeshellarg($config['wmi']['delimiter']);
        }
        if (empty($namespace)) {
            $options .= " --namespace='root\CIMV2'";
        } else {
            $options .= " --namespace=" . escapeshellarg($namespace);
        }
        if (OBS_DEBUG > 1) {
            $options .= " -d2";
        }
    }
    // Host
    $options .= " //" . escapeshellarg($hostname);

    return $GLOBALS['cache']['wmic_exec'] . " $options";
}

// Execute wmic using provided config variables and WQL then return output string
// DOCME needs phpdoc block
// TESTME needs unit testing
function wmi_query($wql, $device, $namespace = NULL)
{

    if ($cmd = wmi_cmd($device, $namespace)) {
        $cmd .= ' "' . $wql . '"';
        print_debug("WMI CMD: " . $cmd);
        //print_vars($cmd);

        return external_exec($cmd);
    }

    print_debug("WMI ERROR: query ($wql) skipped.");

    return FALSE;
}

// Import WMI string to array, remove any empty lines, find "CLASS:" in string, parse the following lines into array
// $ret_single == TRUE will output a single dimension array only if there is one "row" of results
// $ret_val == <WMI Property> will output the value of a single property. Only works when $ret_single == TRUE
// Will quit if "ERROR:" is found (usually means the WMI class does not exist)
// DOCME needs phpdoc block
// TESTME needs unit testing
function wmi_parse($wmi_string, $ret_single = FALSE, $ret_val = NULL)
{
    if (!is_string($wmi_string) || safe_empty($wmi_string)) {
        return NULL;
    }
    print_debug($wmi_string);

    $wmi_lines      = array_filter(explode(PHP_EOL, $wmi_string), 'strlen');
    $wmi_class      = NULL;
    $wmi_error      = NULL;
    $wmi_properties = [];
    $wmi_results    = [];

    foreach ($wmi_lines as $line) {
        if (str_contains($line, 'ERROR:')) {
            $wmi_error = substr($line, strpos($line, 'ERROR:') + strlen("ERROR: "));
            if (OBS_DEBUG) {
                // If the error is something other than "Retrieve result data." please report it
                switch ($wmi_error) {
                    case "Retrieve result data.":
                        echo("WMI Error: Cannot connect to host or Class\n");
                        break;
                    case "Login to remote object.":
                        echo("WMI Error: Invalid security credentials or insufficient WMI security permissions\n");
                        break;
                    default:
                        echo("WMI Error: Please report");
                        break;
                }
            }
            return NULL;
        }

        if (empty($wmi_class)) {
            if (str_starts($line, 'CLASS:')) {
                $wmi_class = substr($line, strlen("CLASS: "));
            }
        } elseif (empty($wmi_properties)) {
            $wmi_properties = explode($GLOBALS['config']['wmi']['delimiter'], $line);
        } else {
            $values = explode($GLOBALS['config']['wmi']['delimiter'], str_replace('(null)', '', $line));
            if (count($wmi_properties) !== count($values)) {
                print_error("WMI ERROR: properties count not same as values count!");
                print_debug_vars($wmi_properties);
                print_debug_vars($values);
                continue; // Prevent Fatal error: Uncaught ValueError: array_combine(): Argument #1 ($keys) and argument #2 ($values) must have the same number of elements
            }
            $wmi_results[] = array_combine($wmi_properties, $values);

            // Reset class & properties for multiple results
            $wmi_class      = NULL;
            $wmi_properties = [];
        }
    }
    if (count($wmi_results) === 1) {
        if ($ret_single) {
            if ($ret_val) {
                $wmi_results = $wmi_results[0][$ret_val];
                //return $wmi_results[0][$ret_val];
            } else {
                $wmi_results = $wmi_results[0];
                //return $wmi_results[0];
            }
        }
    }

    print_debug_vars($wmi_results);
    return $wmi_results;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function wmi_dbAppInsert($device_id, $app)
{
    $dbCheck = dbFetchRow("SELECT * FROM `applications` WHERE `device_id` = ? AND `app_type` = ? AND `app_instance` = ?", [$device_id, $app['type'], $app['instance']]);

    if (empty($dbCheck)) {
        echo("Found new application '" . strtoupper($app['type']) . "'");
        if (isset($app['instance'])) {
            echo(" Instance '" . $app['instance'] . "'");
        }
        echo("\n");

        dbInsert(['device_id' => $device_id, 'app_type' => $app['type'], 'app_instance' => $app['instance'], 'app_name' => $app['name']], 'applications');
    } elseif (empty($dbCheck['app_name']) && isset($app['name'])) {
        dbUpdate(['app_name' => $app['name']], 'applications', "`app_id` = ?", [$dbCheck['app_id']]);
    }
}

// EOF
