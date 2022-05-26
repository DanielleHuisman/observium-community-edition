<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage wmi
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

function wmi_get($device, $wql, $namespace = NULL) {
  // Simplify this: wmi_parse(wmi_query($wql, $override), TRUE, "Name");
  return wmi_parse(wmi_query($wql, $device), TRUE, $namespace);
}

function wmi_get_all($device, $wql, $namespace = NULL) {
  // Simplify this: wmi_parse(wmi_query($wql, $override));
  return wmi_parse(wmi_query($wql, $device), FALSE, $namespace);
}

// Execute wmic using provided config variables and WQL then return output string
// DOCME needs phpdoc block
// TESTME needs unit testing
function wmi_query($wql, $device, $namespace = NULL) {
  global $attribs;

  if (!is_array($attribs)) {
    $attribs = get_entity_attribs('device', $device['device_id']);
  }

  if (!isset($namespace)) {
    $namespace = $GLOBALS['config']['wmi']['namespace'];
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
    $domain   = $GLOBALS['config']['wmi']['domain'];
    $username = $GLOBALS['config']['wmi']['user'];
    $password = $GLOBALS['config']['wmi']['pass'];
  }

  if (empty($hostname)) {
    $hostname = $device['hostname'];
  }

  if (safe_empty($username) || safe_empty($hostname)) {
    print_debug("Empty hostname or username, WMI query ($wql) skipped.");
    return FALSE;
  }

  $options = " --user=" . escapeshellarg($username);
  if (empty($password)) {
    $options .= " --no-pass";
  } else {
    $options .= " --password=". escapeshellarg($password);
  }
  if (!safe_empty($domain)) {
    $options .= " --workgroup=". escapeshellarg($domain);
  }
  if (safe_empty($GLOBALS['config']['wmi']['delimiter'])) {
    $options .= " --delimiter=##"; // FIXME. escaping
  } else {
    $options .= " --delimiter=" . escapeshellarg($GLOBALS['config']['wmi']['delimiter']);
  }
  if (empty($namespace)) {
    $options .= " --namespace='root\CIMV2'";
  } else {
    $options .= " --namespace=" . escapeshellarg($namespace);
  }
  if (OBS_DEBUG > 1) { $options .= " -d2"; }
  $options .= " //" . escapeshellarg($hostname);

  // Override old default wmic cmd path if not found
  if ($GLOBALS['config']['wmic'] === '/bin/wmic' && !is_file($GLOBALS['config']['wmic'])) {
    $GLOBALS['config']['wmic'] = '/usr/bin/wmic';
  }
  if (!is_executable($GLOBALS['config']['wmic'])) {
    print_error("The wmic binary was not found at the configured path (".$GLOBALS['config']['wmic'].").");
    return FALSE;
  }

  $cmd = $GLOBALS['config']['wmic'] . " $options " . '"' . $wql . '"';

  return external_exec($cmd);
}

// Import WMI string to array, remove any empty lines, find "CLASS:" in string, parse the following lines into array
// $ret_single == TRUE will output a single dimension array only if there is one "row" of results
// $ret_val == <WMI Property> will output the value of a single property. Only works when $ret_single == TRUE
// Will quit if "ERROR:" is found (usually means the WMI class does not exist)
// DOCME needs phpdoc block
// TESTME needs unit testing
function wmi_parse($wmi_string, $ret_single = FALSE, $ret_val = NULL) {
  if (!is_string($wmi_string)) { return NULL; }

  $wmi_lines = array_filter(explode(PHP_EOL, $wmi_string), 'strlen');
  $wmi_class = NULL;
  $wmi_error = NULL;
  $wmi_properties = [];
  $wmi_results = [];

  foreach ($wmi_lines as $line) {
    if (str_contains($line, 'ERROR:')) {
      $wmi_error = substr($line, strpos($line, 'ERROR:') + strlen("ERROR: "));
      if (OBS_DEBUG) {
        // If the error is something other than "Retrieve result data." please report it
        switch($wmi_error) {
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
      $wmi_results[] = array_combine($wmi_properties, explode($GLOBALS['config']['wmi']['delimiter'], str_replace('(null)', '', $line)));
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
  $dbCheck = dbFetchRow("SELECT * FROM `applications` WHERE `device_id` = ? AND `app_type` = ? AND `app_instance` = ?", array($device_id, $app['type'], $app['instance']));

  if (empty($dbCheck))
  {
    echo("Found new application '".strtoupper($app['type'])."'");
    if (isset($app['instance']))
    {
      echo(" Instance '".$app['instance']."'");
    }
    echo("\n");

    dbInsert(array('device_id' => $device_id, 'app_type' => $app['type'], 'app_instance' => $app['instance'], 'app_name' => $app['name']), 'applications');
  }
  elseif (empty($dbCheck['app_name']) && isset($app['name']))
  {
    dbUpdate(array('app_name' => $app['name']), 'applications', "`app_id` = ?", array($dbCheck['app_id']));
  }
}

// EOF
