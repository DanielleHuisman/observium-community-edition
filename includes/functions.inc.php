<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage functions
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Observium Includes

require_once($config['install_dir'] . "/includes/common.inc.php");
include_once($config['install_dir'] . "/includes/encrypt.inc.php");
include_once($config['install_dir'] . "/includes/rrdtool.inc.php");
include_once($config['install_dir'] . "/includes/influx.inc.php");
include_once($config['install_dir'] . "/includes/syslog.inc.php");
include_once($config['install_dir'] . "/includes/rewrites.inc.php");
include_once($config['install_dir'] . "/includes/templates.inc.php");
include_once($config['install_dir'] . "/includes/snmp.inc.php");
include_once($config['install_dir'] . "/includes/services.inc.php");
include_once($config['install_dir'] . "/includes/entities.inc.php");
include_once($config['install_dir'] . "/includes/wifi.inc.php");
include_once($config['install_dir'] . "/includes/geolocation.inc.php");

include_once($config['install_dir'] . "/includes/alerts.inc.php");

//if (OBSERVIUM_EDITION != 'community') // OBSERVIUM_EDITION - not defined here..
//{
foreach (array('groups', 'billing', // Not exist in community edition
               'community',         // community edition specific
               'custom',            // custom functions, i.e. short_hostname
              ) as $entry)
{
  $file = $config['install_dir'] . '/includes/' . $entry . '.inc.php';
  if (is_file($file)) { include_once($file); }
}


// DOCME needs phpdoc block
// Send to AMQP via UDP-based python proxy.
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function messagebus_send($message)
{
  global $config;

  if ($socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))
  {
    $message = json_encode($message);
    print_debug('Sending JSON via AMQP: ' . $message);
    socket_sendto($socket, $message, strlen($message), 0, $config['amqp']['proxy']['host'], $config['amqp']['proxy']['port']);
    socket_close($socket);
    return TRUE;
  } else {
    print_error("Failed to create UDP socket towards AMQP proxy.");
    return FALSE;
  }
}



/**
 * Transforms a given string using an array of different transformations, in order.
 *
 * @param string $string Original string to be transformed
 * @param array $transformations Transformation array
 *
 * Available transformations:
 *   'action' => 'prepend'    Prepend static 'string'
 *   'action' => 'append'     Append static 'string'
 *   'action' => 'trim'       Trim 'characters' from both sides of the string
 *   'action' => 'ltrim'      Trim 'characters' from the left of the string
 *   'action' => 'rtrim'      Trim 'characters' from the right of the string
 *   'action' => 'replace'    Case-sensitively replace 'from' string by 'to'; 'from' can be an array of strings
 *   'action' => 'ireplace'   Case-insensitively replace 'from' string by 'to'; 'from' can be an array of strings
 *   'action' => 'timeticks'  Convert standart Timeticks to seconds
 *   'action' => 'explode'    Explode string by 'delimiter' (default ' ') and fetch array element (first (default), last)
 *   'action' => 'regex_replace'  Replace 'from' with 'to'.
 *
 * @return string Transformed string
 */
function string_transform($string, $transformations)
{
  if (!is_array($transformations) || empty($transformations))
  {
    // Bail out if no transformations are given
    return $string;
  }

  // Simplify single action definition with less array nesting
  if (isset($transformations['action']))
  {
    $transformations = array($transformations);
  }

  foreach ($transformations as $transformation)
  {
    $msg = "  String '$string' transformed by action [".$transformation['action']."] to: ";
    switch ($transformation['action'])
    {
      case 'prepend':
        $string = $transformation['string'] . $string;
        break;

      case 'append':
        $string .= $transformation['string'];
        break;

      case 'upper':
        $string = strtoupper($string);
        break;

      case 'lower':
        $string = strtolower($string);
        break;

      case 'nicecase':
        $string = nicecase($string);
        break;

      case 'trim':
      case 'ltrim':
      case 'rtrim':
        if (isset($transformation['chars']) && !isset($transformation['characters']))
        {
          // Just simple for brain memory key
          $transformation['characters'] = $transformation['chars'];
        }
        if (!isset($transformation['characters']))
        {
          $transformation['characters'] = " \t\n\r\0\x0B";
        }

        if ($transformation['action'] == 'rtrim')
        {
          $string = rtrim($string, $transformation['characters']);
        }
        else if ($transformation['action'] == 'ltrim')
        {
          $string = ltrim($string, $transformation['characters']);
        } else {
          $string = trim($string, $transformation['characters']);
        }
        break;

      case 'replace':
        $string = str_replace($transformation['from'], $transformation['to'], $string);
        break;

      case 'ireplace':
        $string = str_ireplace($transformation['from'], $transformation['to'], $string);
        break;

      case 'regex_replace':
      case 'preg_replace':
        $tmp_string = preg_replace($transformation['from'], $transformation['to'], $string);
        if (strlen($tmp_string))
        {
          $string = $tmp_string;
        }
        break;

      case 'regex_match':
      case 'preg_match':
        if (preg_match($transformation['from'], $string, $matches))
        {
          $string = array_tag_replace($matches, $transformation['to']);
        }
        break;

      case 'map':
        // Map string by key -> value
        if (is_array($transformation['map']) && isset($transformation['map'][$string]))
        {
          $string = $transformation['map'][$string];
        }
        break;

      case 'timeticks':
        // Timeticks: (2542831) 7:03:48.31
        $string = timeticks_to_sec($string);
        break;

      case 'asdot':
        // BGP 32bit ASN from asdot to plain
        $string = bgp_asdot_to_asplain($string);
        break;

      case 'units':
        // 200kbps -> 200000, 50M -> 52428800
        $string = unit_string_to_numeric($string);
        break;

      case 'entity_name':
        $string = rewrite_entity_name($string);
        break;

      case 'explode':
        // String delimiter (default is single space " ")
        if (isset($transformation['delimiter']) && strlen($transformation['delimiter']))
        {
          $delimiter = $transformation['delimiter'];
        } else {
          $delimiter = ' ';
        }
        $array = explode($delimiter, $string);
        // Get array index (default is first)
        if (!isset($transformation['index']))
        {
          $transformation['index'] = 'first';
        }
        switch ($transformation['index'])
        {
          case 'first':
          case 'begin':
            $string = array_shift($array);
            break;
          case 'last':
          case 'end':
            $string = array_pop($array);
            break;
          default:

            if (strlen($array[$transformation['index']]))
            {
              $string = $array[$transformation['index']];
            }
        }
        break;

      default:
        // FIXME echo HALP, unknown transformation!
        break;
    }
    print_debug($msg . "'$string'");
  }

  return $string;
}

// DOCME needs phpdoc block
// Sorts an $array by a passed field.
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function array_sort($array, $on, $order='SORT_ASC')
{
  $new_array = array();
  $sortable_array = array();

  if (count($array) > 0)
  {
    foreach ($array as $k => $v)
    {
      if (is_array($v))
      {
        foreach ($v as $k2 => $v2)
        {
          if ($k2 == $on)
          {
            $sortable_array[$k] = $v2;
          }
        }
      } else {
        $sortable_array[$k] = $v;
      }
    }

    switch ($order)
    {
      case 'SORT_ASC':
        asort($sortable_array);
        break;
      case 'SORT_DESC':
        arsort($sortable_array);
        break;
    }

    foreach ($sortable_array as $k => $v)
    {
      $new_array[$k] = $array[$k];
    }
  }

  return $new_array;
}

/** hex2float
* (Convert 8 digit hexadecimal value to float (single-precision 32bits)
* Accepts 8 digit hexadecimal values in a string
* @usage:
* hex2float32n("429241f0"); returns -> "73.128784179688"
* */
function hex2float($number) {
    $binfinal = sprintf("%032b",hexdec($number));
    $sign = substr($binfinal, 0, 1);
    $exp = substr($binfinal, 1, 8);
    $mantissa = "1".substr($binfinal, 9);
    $mantissa = str_split($mantissa);
    $exp = bindec($exp)-127;
    $significand=0;
    for ($i = 0; $i < 24; $i++) {
        $significand += (1 / pow(2,$i))*$mantissa[$i];
    }
    return $significand * pow(2,$exp) * ($sign*-2+1);
}

// A function to process numerical values according to a $scale value
// Functionised to allow us to have "magic" scales which do special things
// Initially used for dec>hex>float values used by accuview
function scale_value($value, $scale)
{

  if ($scale == '161616') // This is used by Accuview Accuvim II
  {
    //CLEANME. Not required anymore, use unit name "accuenergy"
    return hex2float(dechex($value));
  } else if($scale != 0) {
    return $value * $scale;
  } else {
    return $value;
  }

}

/**
 * Custom value unit conversion functions for some vendors,
 * who do not know how use snmp float conversions,
 * do not know physics, mathematics and in general badly studied at school
 */

function value_unit_accuenergy($value)
{
  return hex2float(dechex($value));
}

// See: https://jira.observium.org/browse/OBS-2941
// Oids "pm10010mpMesrlineNetRxInputPwrPortn" and "pm10010mpMesrlineNetTxLaserOutputPwrPortn" in EKINOPS-Pm10010mp-MIB
// If AV<32768:  Tx_Pwr(dBm) = AV/100
// If AV>=32768: Tx_Pwr(dBm) = (AV-65536)/100
function value_unit_ekinops_dbm1($value)
{
  if ($value >= 32768 && $value <= 65536)
  {
    return ($value - 65536) / 100;
  }
  else if ($value > 65536 || $value < 0)
  {
    return FALSE;
  }

  return $value / 100;
}

// See: https://jira.observium.org/browse/OBS-2941
// oids "pm10010mpMesrclientNetTxPwrPortn" and "pm10010mpMesrclientNetRxPwrPortn" in EKINOPS-Pm10010mp-MIB
// Power = 10*log(AV)-40) (Unit = dBm)
function value_unit_ekinops_dbm2($value)
{
  //$si_value = 10 * log10($value) + 30; // this is how watts converted to dbm
  $si_value = 10 * log10($value) - 40; // BUT this how convert it EKINOPS.... WHY??????

  return $si_value;
}

// Another sort array function
// http://php.net/manual/en/function.array-multisort.php#100534
// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function array_sort_by()
{
  $args = func_get_args();
  $data = array_shift($args);
  foreach ($args as $n => $field)
  {
    if (is_string($field))
    {
      $tmp = array();
      foreach ($data as $key => $row)
      {
        $tmp[$key] = $row[$field];
      }
      $args[$n] = $tmp;
    }
  }
  $args[] = &$data;
  call_user_func_array('array_multisort', $args);
  return array_pop($args);
}

/**
 * Given two arrays, the function diff will return an array of the changes.
 *
 * @param array $old First array
 * @param array $new Second array
 * @return array Array with diffs and same elements
 */
function diff($old, $new)
{

  $matrix = array();
  $maxlen = 0;
  foreach ($old as $oindex => $ovalue)
  {
    $nkeys = array_keys($new, $ovalue);
    foreach ($nkeys as $nindex)
    {
      $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
      if ($matrix[$oindex][$nindex] > $maxlen)
      {
        $maxlen = $matrix[$oindex][$nindex];
        $omax = $oindex + 1 - $maxlen;
        $nmax = $nindex + 1 - $maxlen;
      }
    }
  }
  if ($maxlen == 0)
  {
    return array(array('d'=>$old, 'i'=>$new));
  }

  return array_merge(
    diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
    array_slice($new, $nmax, $maxlen),
    diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}

/**
 * Return similar part of two strings (or empty)
 *
 * @param string $old First string
 * @param string $new Second string
 * @return string Similar part of two strings
 */
function str_similar($old, $new)
{

  $ret = array();
  $diff = diff(preg_split("/[\s]+/u", $old), preg_split("/[\s]+/u", $new));
  foreach ($diff as $k)
  {
    if (!is_array($k))
    {
      $ret[] = $k;
    }
  }
  return implode(' ', $ret);
}

/**
 * Return sets of all similar strings from passed array.
 *
 * @param array $array Array with strings for find similar
 * @param boolean $return_flip If TRUE return pairs String -> Similar part,
 *                             instead vice versa by default return set of arrays Similar part -> Strings
 * @param integer $similarity Percent of similarity compared string, mostly common is 90%
 * @return array Array with sets of similar strings
 */
function find_similar($array, $return_flip = FALSE, $similarity = 89)
{
  natsort($array);
  //var_dump($array);
  $array2 = $array;
  $same_array = array();
  $same_array_flip = array();

  // $i = 0; // DEBUG
  foreach ($array as $k => $old)
  {
    foreach ($array2 as $k2 => $new)
    {
      if ($k === $k2) { continue; } // Skip same array elements

      // Detect string similarity
      similar_text($old, $new, $perc);

      // $i++; echo "$i ($perc %): '$old' <> '$new' (".str_similar($old, $new).")\n"; // DEBUG

      if ($perc > $similarity)
      {
        if (isset($same_array_flip[$old]))
        {
          // This is found already similar string by previous round(s)
          $same = $same_array_flip[$old];
          $same_array_flip[$new] = $same;
        }
        else if (isset($same_array_flip[$new]))
        {
          // This is found already similar string by previous round(s)
          $same = $same_array_flip[$new];
          $same_array_flip[$old] = $same;
        } else {
          // New similarity, get similar string part
          $same = str_similar($old, $new);

          // Return array pairs as:
          // String -> Similar part
          $same_array_flip[$old] = $same;
          $same_array_flip[$new] = $same;
        }

        // Return array elements as:
        // Similar part -> Strings
        if (!isset($same_array[$same]) || !in_array($old, $same_array[$same])) {
          $same_array[$same][] = $old;
        }
        $same_array[$same][] = $new;

        unset($array2[$k]); // Remove array element if similar found

        break;
      }
    }
    if (!isset($same_array_flip[$old]))
    {
      // Similarity not found, just add as single string
      $same_array_flip[$old] = $old;
      $same_array[$old][] = $old;
    }
  }

  if ($return_flip)
  {
    // Return array pairs as:
    // String -> Similar part
    return $same_array_flip;
  } else {
    // Return array elements as:
    // Similar part -> Strings
    return $same_array;
  }
}

/**
 * Includes filename with global config variable
 *
 * @param string $filename Filename for include
 *
 * @return boolean Status of include
 */
function include_wrapper($filename)
{
  global $config;

  $status = include($filename);

  return (boolean)$status;
}

// Strip all non-alphanumeric characters from a string.
// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function only_alphanumeric($string)
{
  return preg_replace('/[^a-zA-Z0-9]/', '', $string);
}

/**
 * Detect the device's OS
 *
 * Order for detect:
 *  if device rechecking (know old os): complex discovery (all), sysObjectID, sysDescr, file check
 *  if device first checking:           complex discovery (except network), sysObjectID, sysDescr, complex discovery (network), file check
 *
 * @param array $device Device array
 * @return string Detected device os name
 */
function get_device_os($device)
{
  global $config, $table_rows, $cache_os;

  // If $recheck sets as TRUE, verified that 'os' corresponds to the old value.
  // recheck only if old device exist in definitions
  $recheck = isset($config['os'][$device['os']]);

  $sysDescr     = snmp_fix_string(snmp_get_oid($device, 'sysDescr.0', 'SNMPv2-MIB'));
  $sysDescr_ok  = $GLOBALS['snmp_status'] || $GLOBALS['snmp_error_code'] === 1; // Allow empty response for sysDescr (not timeouts)
  $sysObjectID  = snmp_cache_sysObjectID($device);
  /*
  $sysObjectID  = snmp_get($device, 'sysObjectID.0', '-Ovqn', 'SNMPv2-MIB');
  if (strpos($sysObjectID, 'Wrong Type') !== FALSE)
  {
    // Wrong Type (should be OBJECT IDENTIFIER): "1.3.6.1.4.1.25651.1.2"
    list(, $sysObjectID) = explode(':', $sysObjectID);
    $sysObjectID = '.'.trim($sysObjectID, ' ."');
  }
  */

  // Cache discovery os definitions
  cache_discovery_definitions();
  $discovery_os = $GLOBALS['cache']['discovery_os'];
  $cache_os = array();

  $table_rows    = array();
  $table_opts    = array('max-table-width' => TRUE); // Set maximum table width as available columns in terminal
  $table_headers = array('%WOID%n', '');
  $table_rows[] = array('sysDescr',    $sysDescr);
  $table_rows[] = array('sysObjectID', $sysObjectID);
 	print_cli_table($table_rows, $table_headers, NULL, $table_opts);
  //print_debug("Detect OS. sysDescr: '$sysDescr', sysObjectID: '$sysObjectID'");

  $table_rows    = array(); // Reinit
  //$table_opts    = array('max-table-width' => 200);
  $table_headers = array('%WOID%n', '%WMatched definition%n', '');
  // By first check all sysObjectID
  foreach ($discovery_os['sysObjectID'] as $def => $cos)
  {
    if (match_sysObjectID($sysObjectID, $def))
    {
      // Store matched OS, but by first need check by complex discovery arrays!
      $sysObjectID_def = $def;
      $sysObjectID_os  = $cos;
      break;
    }
  }

  if ($recheck)
  {
    $table_desc = 'Re-Detect OS matched';
    $old_os = $device['os'];

    if (!$sysDescr_ok && !empty($old_os))
    {
      // If sysDescr empty - return old os, because some snmp error
      // print_debug("ERROR: sysDescr not received, OS re-check stopped.");
      // return $old_os;
    }

    // Recheck by complex discovery array
    // Yes, before sysObjectID, because complex more accurate and can intersect with it!
    foreach ($discovery_os['discovery'][$old_os] as $def)
    {
      if (match_discovery_os($sysObjectID, $sysDescr, $def, $device))
      {
        print_cli_table($table_rows, $table_headers, $table_desc . " ($old_os: ".$config['os'][$old_os]['text'].'):', $table_opts);
        return $old_os;
      }
    }
    foreach ($discovery_os['discovery_network'][$old_os] as $def)
    {
      if (match_discovery_os($sysObjectID, $sysDescr, $def, $device))
      {
        print_cli_table($table_rows, $table_headers, $table_desc . " ($old_os: ".$config['os'][$old_os]['text'].'):', $table_opts);
        return $old_os;
      }
    }

    /** DISABLED.
     * Recheck only by complex, networked and file rules

    // Recheck by sysObjectID
    if ($sysObjectID_os)
    {
      // If OS detected by sysObjectID just return it
      $table_rows[] = array('sysObjectID', $sysObjectID_def, $sysObjectID);
      print_cli_table($table_rows, $table_headers, $table_desc . " ($old_os: ".$config['os'][$old_os]['text'].'):', $table_opts);
      return $sysObjectID_os;
    }

    // Recheck by sysDescr from definitions
    foreach ($discovery_os['sysDescr'][$old_os] as $pattern)
    {
      if (preg_match($pattern, $sysDescr))
      {
        $table_rows[] = array('sysDescr', $pattern, $sysDescr);
        print_cli_table($table_rows, $table_headers, $table_desc . " ($old_os: ".$config['os'][$old_os]['text'].'):', $table_opts);
        return $old_os;
      }
    }
    */

    // Recheck by include file (moved to end!)

    // Else full recheck 'os'!
    unset($os, $file);

  } // End recheck

  $table_desc = 'Detect OS matched';

  // Check by complex discovery arrays (except networked)
  // Yes, before sysObjectID, because complex more accurate and can intersect with it!
  foreach ($discovery_os['discovery'] as $cos => $defs)
  {
    foreach ($defs as $def)
    {
      if (match_discovery_os($sysObjectID, $sysDescr, $def, $device)) { $os = $cos; break 2; }
    }
  }

  // Check by sysObjectID
  if (!$os && $sysObjectID_os)
  {
    // If OS detected by sysObjectID just return it
    $os = $sysObjectID_os;
    $table_rows[] = array('sysObjectID', $sysObjectID_def, $sysObjectID);
    print_cli_table($table_rows, $table_headers, $table_desc . " ($os: ".$config['os'][$os]['text'].'):', $table_opts);
    return $os;
  }

  if (!$os && $sysDescr)
  {
    // Check by sysDescr from definitions
    foreach ($discovery_os['sysDescr'] as $cos => $patterns)
    {
      foreach ($patterns as $pattern)
      {
        if (preg_match($pattern, $sysDescr))
        {
          $table_rows[] = array('sysDescr', $pattern, $sysDescr);
          $os = $cos;
          break 2;
        }
      }
    }
  }

  // Check by complex discovery arrays, now networked
  if (!$os)
  {
    foreach ($discovery_os['discovery_network'] as $cos => $defs)
    {
      foreach ($defs as $def)
      {
        if (match_discovery_os($sysObjectID, $sysDescr, $def, $device)) { $os = $cos; break 2; }
      }
    }
  }

  if (!$os)
  {
    $path = $config['install_dir'] . '/includes/discovery/os';
    $sysObjectId = $sysObjectID; // old files use wrong variable name

    // Recheck first
    $recheck_file = FALSE;
    if ($recheck && $old_os)
    {
      if (is_file($path . "/$old_os.inc.php"))
      {
        $recheck_file = $path . "/$old_os.inc.php";
      }
      else if (isset($config['os'][$old_os]['discovery_os']) &&
               is_file($path . '/'.$config['os'][$old_os]['discovery_os'] . '.inc.php'))
      {
        $recheck_file = $path . '/'.$config['os'][$old_os]['discovery_os'] . '.inc.php';
      }

      if ($recheck_file)
      {
        print_debug("Including $recheck_file");

        $sysObjectId = $sysObjectID; // old files use wrong variable name
        include($recheck_file);

        if ($os && $os == $old_os)
        {
          $table_rows[] = array('file', $recheck_file, '');
          print_cli_table($table_rows, $table_headers, $table_desc . " ($old_os: ".$config['os'][$old_os]['text'].'):', $table_opts);
          return $old_os;
        }
      }
    }

    // Check all other by include file
    $dir_handle = @opendir($path) or die("Unable to open $path");
    while ($file = readdir($dir_handle))
    {
      if (preg_match('/\.inc\.php$/', $file) && $file !== $recheck_file)
      {
        print_debug("Including $file");

        include($path . '/' . $file);

        if ($os)
        {
          $table_rows[] = array('file', $file, '');
          break; // Stop while if os detected
        }
      }
    }
    closedir($dir_handle);
  }

  if ($os)
  {
    print_cli_table($table_rows, $table_headers, $table_desc . " ($os: ".$config['os'][$os]['text'].'):', $table_opts);
    return $os;
  } else {
    return 'generic';
  }
}

/**
 * Compares sysObjectID with $needle. Return TRUE if match.
 *
 * @param string $sysObjectID Walked sysObjectID from device
 * @param string $needle      Compare with this
 * @return boolean            TRUE if match, otherwise FALSE
 */
function match_sysObjectID($sysObjectID, $needle)
{
  if (substr($needle, -1) === '.')
  {
    // Use wildcard compare if sysObjectID definition have '.' at end, ie:
    //   .1.3.6.1.4.1.2011.1.
    if (str_starts($sysObjectID, $needle)) { return TRUE; }
  } else {
    // Use exact match sysObjectID definition or wildcard compare with '.' at end, ie:
    //   .1.3.6.1.4.1.2011.2.27
    if ($sysObjectID === $needle || str_starts($sysObjectID, $needle.'.')) { return TRUE; }
  }

  return FALSE;
}

/**
 * Compares complex sysObjectID/sysDescr definition with $needle. Return TRUE if match.
 *
 * @param string $sysObjectID Walked sysObjectID from device
 * @param string $sysDescr    Walked sysDescr from device
 * @param array  $needle      Compare with this definition array
 * @param array  $device      Device array, optional if compare used not standard OIDs
 * @return boolean            TRUE if match, otherwise FALSE
 */
function match_discovery_os($sysObjectID, $sysDescr, $needle, $device = array())
{
  global $table_rows, $cache_os;

  $needle_oids  = array_keys($needle);
  $needle_count = count($needle_oids);

  // Match sysObjectID and sysDescr always first!
  $needle_oids_order = array_merge(array('sysObjectID', 'sysDescr'), $needle_oids);
  $needle_oids_order = array_unique($needle_oids_order);
  $needle_oids_order = array_intersect($needle_oids_order, $needle_oids);

  foreach ($needle_oids_order as $oid)
  {
    $match = FALSE;
    switch ($oid)
    {
      case 'sysObjectID':
        foreach ((array)$needle[$oid] as $def)
        {
          //var_dump($def);
          //var_dump($sysObjectID);
          //var_dump(match_sysObjectID($sysObjectID, $def));
          if (match_sysObjectID($sysObjectID, $def))
          {
            $match_defs[$oid] = array($def, $sysObjectID);
            $needle_count--;
            $match = TRUE;
            break;
          }
        }
        break;

      case 'sysDescr':
        foreach ((array)$needle[$oid] as $def)
        {
          //print_vars($def);
          //print_vars($sysDescr);
          //print_vars(preg_match($def, $sysDescr));
          if (preg_match($def, $sysDescr))
          {
            $match_defs[$oid] = array($def, $sysDescr);
            $needle_count--;
            $match = TRUE;
            break;
          }
        }
        break;

      case 'sysName':
        // other common SNMPv2-MIB fetch first
        if (!isset($cache_os[$oid]))
        {
          $value    = snmp_fix_string(snmp_get_oid($device, $oid . '.0', 'SNMPv2-MIB'));
          $value_ok = $GLOBALS['snmp_status'] || $GLOBALS['snmp_error_code'] === 1; // Allow empty response
          $cache_os[$oid] = array('ok' => $value_ok, 'value' => $value);
        } else {
          // Use already cached data
          $value    = $cache_os[$oid]['value'];
          $value_ok = $cache_os[$oid]['ok'];
        }
        foreach ((array)$needle[$oid] as $def)
        {
          //print_vars($def);
          //print_vars($value);
          //print_vars(preg_match($def, $value));
          if ($value_ok && preg_match($def, $value))
          {
            $match_defs[$oid] = array($def, $value);
            $needle_count--;
            $match = TRUE;
            break;
          }
        }
        break;

      default:
        // All other oids,
        // fetch by first, than compare with pattern
        if (!isset($cache_os[$oid]))
        {
          $value    = snmp_fix_string(snmp_get_oid($device, $oid));
          $value_ok = $GLOBALS['snmp_status'] || $GLOBALS['snmp_error_code'] === 1; // Allow empty response
          $cache_os[$oid] = array('ok' => $value_ok, 'value' => $value);
        } else {
          // Use already cached data
          $value    = $cache_os[$oid]['value'];
          $value_ok = $cache_os[$oid]['ok'];
        }
        foreach ((array)$needle[$oid] as $def)
        {
          // print_vars($def);
          // print_vars($value);
          // print_vars(preg_match($def, $value));
          if ($value_ok && preg_match($def, $value))
          {
            $match_defs[$oid] = array($def, $value);
            $needle_count--;
            $match = TRUE;
            break;
          }
        }
        break;
    }

    // Stop all other checks, last oid not match with any..
    if (!$match) { return FALSE; }
  }

  // Match only if all oids found and matched
  $match = $needle_count === 0;

  // Store detailed info
  if ($match)
  {
    foreach ($match_defs as $oid => $def)
    {
      $table_rows[] = array($oid, $def[0], $def[1]);
    }
  }

  return $match;
}

function cache_discovery_definitions()
{
  global $config, $cache;

  // Cache/organize discovery definitions
  if (!isset($cache['discovery_os']))
  {
    foreach (array_keys($config['os']) as $cos)
    {
      // Generate full array with sysObjectID from definitions
      foreach ($config['os'][$cos]['sysObjectID'] as $oid)
      {
        $oid = trim($oid);
        if ($oid[0] != '.') { $oid = '.' . $oid; } // Add first point if not already added

        if (isset($cache['discovery_os']['sysObjectID'][$oid]) && strpos($cache['discovery_os']['sysObjectID'][$oid], 'test_') !== 0)
        {
          print_error("Duplicate sysObjectID '$oid' in definitions for OSes: ".$cache['discovery_os']['sysObjectID'][$oid]." and $cos!");
          continue;
        }
        // sysObjectID -> os
        $cache['discovery_os']['sysObjectID'][$oid] = $cos;
        $cache['discovery_os']['sysObjectID_cos'][$oid][] = $cos; // Collect how many same sysObjectID known by definitions
        //$sysObjectID_def[$oid] = $cos;
      }

      // Generate full array with sysDescr from definitions
      if (isset($config['os'][$cos]['sysDescr']))
      {
        // os -> sysDescr (list)
        $cache['discovery_os']['sysDescr'][$cos] = $config['os'][$cos]['sysDescr'];
      }

      // Complex match with combinations of sysDescr / sysObjectID and any other
      foreach ($config['os'][$cos]['discovery'] as $discovery)
      {
        $oids = array_keys($discovery);
        if (!in_array('sysObjectID', $oids))
        {
          // Check if definition have additional "networked" OIDs (without sysObjectID checks)
          $def_name = 'discovery_network';
        } else {
          $def_name = 'discovery';
        }

        if (count($oids) === 1)
        {
          // single oids convert to old array format
          switch (array_shift($oids))
          {
            case 'sysObjectID':
              foreach ((array)$discovery['sysObjectID'] as $oid)
              {
                $oid = trim($oid);
                if ($oid[0] != '.') { $oid = '.' . $oid; } // Add first point if not already added

                if (isset($cache['discovery_os']['sysObjectID'][$oid]) && strpos($cache['discovery_os']['sysObjectID'][$oid], 'test_') !== 0)
                {
                  print_error("Duplicate sysObjectID '$oid' in definitions for OSes: ".$cache['discovery_os']['sysObjectID'][$oid]." and $cos!");
                  continue;
                }
                // sysObjectID -> os
                $cache['discovery_os']['sysObjectID'][$oid] = $cos;
                $cache['discovery_os']['sysObjectID_cos'][$oid][] = $cos; // Collect how many same sysObjectID known by definitions
              }
              break;
            case 'sysDescr':
              // os -> sysDescr (list)
              if (isset($cache['discovery_os']['sysDescr'][$cos]))
              {
                $cache['discovery_os']['sysDescr'][$cos] = array_unique(array_merge((array)$cache['discovery_os']['sysDescr'][$cos], (array)$discovery['sysDescr']));
              } else {
                $cache['discovery_os']['sysDescr'][$cos] = (array)$discovery['sysDescr'];
              }
              break;
            case 'file':
              $cache['discovery_os']['file'][$cos] = $discovery['file'];
              break;
            default:
              // All other leave as is
              $cache['discovery_os'][$def_name][$cos][] = $discovery;
          }
        } else {
          if ($def_name == 'discovery') // This have sysObjectID
          {
            $new_oids = array();
            foreach ((array)$discovery['sysObjectID'] as $oid)
            {
              $oid = trim($oid);
              if ($oid[0] != '.') { $oid = '.' . $oid; } // Add first point if not already added
              $new_oids[] = $oid;
              $cache['discovery_os']['sysObjectID_cos'][$oid][] = $cos; // Collect how many same sysObjectID known by definitions
            }
            $discovery['sysObjectID'] = $new_oids; // Override sysObjectIDs with normalized
          }
          // Leave complex definitions as is
          $cache['discovery_os'][$def_name][$cos][] = $discovery;
        }
      }
    }

    // NOTE: Currently too hard for detect if same sysObjectID used in multiple OSes, and how get best OS
    // Best os should match by max params
    // Remove all single sysObjectIDs count
    foreach ($cache['discovery_os']['sysObjectID_cos'] as $oid => $oses)
    {
      $oses = array_unique($oses);
      if (count($oses) < 2)
      {
        // Single sysObjectID, no additional matches needed
        unset($cache['discovery_os']['sysObjectID_cos'][$oid]);
      } else {
        /*
        if (isset($cache['discovery_os']['sysObjectID'][$oid]))
        {
          // Move simple check to complex
          $cos = $cache['discovery_os']['sysObjectID'][$oid];
          ///$cache['discovery_os']['discovery'][$cos][] = array('sysObjectID' => $oid);
          //unset($cache['discovery_os']['sysObjectID'][$oid]);
        }
        */
        $cache['discovery_os']['sysObjectID_cos'][$oid] = $oses;
      }
    }

    // Resort sysObjectID array by oids with from high to low order!
    //krsort($cache['discovery_os']['sysObjectID']);
    uksort($cache['discovery_os']['sysObjectID'], 'compare_numeric_oids_reverse');

    //print_vars($cache['discovery_os']['sysObjectID_cos']);
    //print_vars($cache['discovery_os']);
  }
}

/**
 * Compare two numeric oids and return -1, 0, 1
 * ie: .1.2.1. vs 1.2.2
 */
function compare_numeric_oids($oid1, $oid2)
{
  $oid1_array = explode('.', ltrim($oid1, '.'));
  $oid2_array = explode('.', ltrim($oid2, '.'));

  $count1 = count($oid1_array);
  $count2 = count($oid2_array);

  for ($i = 0; $i <= min($count1, $count2) - 1; $i++)
  {
    $int1 = intval($oid1_array[$i]);
    $int2 = intval($oid2_array[$i]);
    if      ($int1 > $int2) { return 1; }
    else if ($int1 < $int2) { return -1; }
  }
  if      ($count1 > $count2) { return 1; }
  else if ($count1 < $count2) { return -1; }

  return 0;
}

/**
 * Compare two numeric oids and return -1, 0, 1
 * here reverse order
 * ie: .1.2.1. vs 1.2.2
 */
function compare_numeric_oids_reverse($oid1, $oid2)
{
  return compare_numeric_oids($oid2, $oid1);
}

// Rename a device
// DOCME needs phpdoc block
// TESTME needs unit testing
function renamehost($id, $new, $source = 'console', $options = array())
{
  global $config;

  $new = strtolower(trim($new));

  // Test if new host exists in database
  //if (dbFetchCell('SELECT COUNT(`device_id`) FROM `devices` WHERE `hostname` = ?', array($new)) == 0)
  if (!dbExist('devices', '`hostname` = ?', array($new)))
  {
    $flags = OBS_DNS_ALL;
    $transport = strtolower(dbFetchCell("SELECT `snmp_transport` FROM `devices` WHERE `device_id` = ?", array($id)));

    // Try detect if hostname is IP
    switch (get_ip_version($new))
    {
      case 6:
        $new     = Net_IPv6::compress($new, TRUE); // Always use compressed IPv6 name
      case 4:
        if ($config['require_hostname'])
        {
          print_error("Hostname should be a valid resolvable FQDN name. Or set config option \$config['require_hostname'] as FALSE.");
 	 	      return FALSE;
        }
        $ip      = $new;
        break;
      default:
        if ($transport == 'udp6' || $transport == 'tcp6') // Exclude IPv4 if used transport 'udp6' or 'tcp6'
        {
          $flags = $flags ^ OBS_DNS_A; // exclude A
        }
        // Test DNS lookup.
        $ip      = gethostbyname6($new, $flags);
    }

    if ($ip)
    {
      $options['ping_skip'] = (isset($options['ping_skip']) && $options['ping_skip']) || get_entity_attrib('device', $id, 'ping_skip');
      if ($options['ping_skip'])
      {
        // Skip ping checks
        $flags = $flags | OBS_PING_SKIP;
      }
      // Test reachability
      if (isPingable($new, $flags))
      {
        // Test directory mess in /rrd/
        if (!file_exists($config['rrd_dir'].'/'.$new))
        {
          $host = dbFetchCell("SELECT `hostname` FROM `devices` WHERE `device_id` = ?", array($id));
          if (!file_exists($config['rrd_dir'].'/'.$host))
          {
            print_warning("Old RRD directory does not exist, rename skipped.");
          }
          else if (!rename($config['rrd_dir'].'/'.$host, $config['rrd_dir'].'/'.$new))
          {
            print_error("NOT renamed. Error while renaming RRD directory.");
            return FALSE;
          }
          $return = dbUpdate(array('hostname' => $new), 'devices', '`device_id` = ?', array($id));
          if ($options['ping_skip'])
          {
            set_entity_attrib('device', $id, 'ping_skip', 1);
          }
          log_event("Device hostname changed: $host -> $new", $id, 'device', $id, 5); // severity 5, for logging user/console info
          return TRUE;
        } else {
          // directory already exists
          print_error("NOT renamed. Directory rrd/$new already exists");
        }
      } else {
        // failed Reachability
        print_error("NOT renamed. Could not ping $new");
      }
    } else {
      // Failed DNS lookup
      print_error("NOT renamed. Could not resolve $new");
    }
  } else {
    // found in database
    print_error("NOT renamed. Already got host $new");
  }
  return FALSE;
}

// Deletes device from database and RRD dir.
// DOCME needs phpdoc block
// TESTME needs unit testing
function delete_device($id, $delete_rrd = FALSE)
{
  global $config;

  $ret = PHP_EOL;
  $device = device_by_id_cache($id);
  $host = $device['hostname'];

  if (!is_array($device))
  {
    return FALSE;
  } else {
    $ports = dbFetchRows("SELECT * FROM `ports` WHERE `device_id` = ?", array($id));
    if (!empty($ports))
    {
      $ret .= ' * Deleted interfaces: ';
      $deleted_ports = [];
      foreach ($ports as $int_data)
      {
        $int_if = $int_data['ifDescr'];
        $int_id = $int_data['port_id'];
        delete_port($int_id, $delete_rrd);
        $deleted_ports[] = "id=$int_id ($int_if)";
      }
      $ret .= implode(', ', $deleted_ports).PHP_EOL;
    }

    // Remove entities from common tables
    $deleted_entities = array();
    foreach (get_device_entities($id) as $entity_type => $entity_ids)
    {
      foreach ($config['entity_tables'] as $table)
      {
        $where = '`entity_type` = ?' . generate_query_values($entity_ids, 'entity_id');
        $table_status = dbDelete($table, $where, array($entity_type));
        if ($table_status) { $deleted_entities[$entity_type] = 1; }
      }
    }
    if (count($deleted_entities))
    {
      $ret .= ' * Deleted common entity entries linked to device: ';
      $ret .= implode(', ', array_keys($deleted_entities)) . PHP_EOL;
    }

    $deleted_tables = array();
    $ret .= ' * Deleted device entries from tables: ';
    foreach ($config['device_tables'] as $table)
    {
      $where = '`device_id` = ?';
      $table_status = dbDelete($table, $where, array($id));
      if ($table_status) { $deleted_tables[] = $table; }
    }
    if (count($deleted_tables))
    {
      $ret .= implode(', ', $deleted_tables).PHP_EOL;

      // Request for clear WUI cache
      set_cache_clear('wui');
    }

    if ($delete_rrd)
    {
      $device_rrd = rtrim(get_rrd_path($device, ''), '/');
      if (is_file($device_rrd.'/status.rrd'))
      {
        external_exec("rm -rf ".escapeshellarg($device_rrd));
        $ret .= ' * Deleted device RRDs dir: ' . $device_rrd . PHP_EOL;
      }

    }

    log_event("Deleted device: $host", $id, 'device', $id, 5); // severity 5, for logging user/console info
    $ret .= " * Deleted device: $host";
  }

  return $ret;
}

function add_device_vars($vars)
{

    global $config;

    $hostname = strip_tags($vars['hostname']);

    // Keep original snmp/rrd config, for revert at end
    $config_snmp = $config['snmp'];
    $config_rrd  = $config['rrd_override'];

    // Default snmp port
    if ($vars['snmp_port'] && is_numeric($vars['snmp_port']))
    {
      $snmp_port = (int)$vars['snmp_port'];
    } else {
      $snmp_port = 161;
    }

    // Default snmp version
    if ($vars['snmp_version'] !== "v2c" &&
        $vars['snmp_version'] !== "v3"  &&
        $vars['snmp_version'] !== "v1")
    {
      $vars['snmp_version'] = $config['snmp']['version'];
    }

    switch ($vars['snmp_version'])
    {
      case 'v2c':
      case 'v1':

        if (strlen($vars['snmp_community']))
        {
          // Hrm, I not sure why strip_tags
          $snmp_community = strip_tags($vars['snmp_community']);
          $config['snmp']['community'] = array($snmp_community);
        }

        $snmp_version = $vars['snmp_version'];

        print_message("Adding SNMP$snmp_version host $hostname port $snmp_port");
        break;

      case 'v3':

        if (strlen($vars['snmp_authlevel']))
        {
          $snmp_v3 = array (
            'authlevel'  => $vars['snmp_authlevel'],
            'authname'   => $vars['snmp_authname'],
            'authpass'   => $vars['snmp_authpass'],
            'authalgo'   => $vars['snmp_authalgo'],
            'cryptopass' => $vars['snmp_cryptopass'],
            'cryptoalgo' => $vars['snmp_cryptoalgo'],
          );

          array_unshift($config['snmp']['v3'], $snmp_v3);
        }

        $snmp_version = "v3";

        print_message("Adding SNMPv3 host $hostname port $snmp_port");
        break;

      default:
        print_error("Unsupported SNMP Version. There was a dropdown menu, how did you reach this error?"); // We have a hacker!
        return FALSE;
    }

    if ($vars['ignorerrd'] == 'confirm' ||
        $vars['ignorerrd'] == '1' ||
        $vars['ignorerrd'] == 'on')
    {
      $config['rrd_override'] = TRUE;
    }

    $snmp_options = array();
    if ($vars['ping_skip'] == '1' || $vars['ping_skip'] == 'on')
    {
      $snmp_options['ping_skip'] = TRUE;
    }

    // Optional SNMP Context
    if (strlen(trim($vars['snmp_context'])))
    {
      $snmp_options['snmp_context'] = trim($vars['snmp_context']);
    }

    $result = add_device($hostname, $snmp_version, $snmp_port, strip_tags($vars['snmp_transport']), $snmp_options);

    // Revert original snmp/rrd config
    $config['snmp'] = $config_snmp;
    $config['rrd_override'] = $config_rrd;

    return $result;

}



/**
 * Adds the new device to the database.
 *
 * Before adding the device, checks duplicates in the database and the availability of device over a network.
 *
 * @param string $hostname Device hostname
 * @param string|array $snmp_version SNMP version(s) (default: $config['snmp']['version'])
 * @param string $snmp_port SNMP port (default: 161)
 * @param string $snmp_transport SNMP transport (default: udp)
 * @param array $options Additional options can be passed ('ping_skip' - for skip ping test and add device attrib for skip pings later
 *                                                         'break' - for break recursion,
 *                                                         'test'  - for skip adding, only test device availability)
 *
 * @return mixed Returns $device_id number if added, 0 (zero) if device not accessible with current auth and FALSE if device complete not accessible by network. When testing, returns -1 if the device is available.
 */
// TESTME needs unit testing
function add_device($hostname, $snmp_version = array(), $snmp_port = 161, $snmp_transport = 'udp', $options = array(), $flags = OBS_DNS_ALL)
{
  global $config;

  // If $options['break'] set as TRUE, break recursive function execute
  if (isset($options['break']) && $options['break']) { return FALSE; }
  $return = FALSE; // By default return FALSE

  // Reset snmp timeout and retries options for speedup device adding
  unset($config['snmp']['timeout'], $config['snmp']['retries']);

  $snmp_transport = strtolower($snmp_transport);

  $hostname = strtolower(trim($hostname));

  // Try detect if hostname is IP
  switch (get_ip_version($hostname))
  {
    case 6:
      $hostname = Net_IPv6::compress($hostname, TRUE); // Always use compressed IPv6 name
    case 4:
      if ($config['require_hostname'])
      {
        print_error("Hostname should be a valid resolvable FQDN name. Or set config option \$config['require_hostname'] as FALSE.");
        return $return;
      }
      $ip       = $hostname;
      break;
    default:
      if ($snmp_transport == 'udp6' || $snmp_transport == 'tcp6') // IPv6 used only if transport 'udp6' or 'tcp6'
      {
        $flags = $flags ^ OBS_DNS_A; // exclude A
      }
      // Test DNS lookup.
      $ip       = gethostbyname6($hostname, $flags);
  }

  // Test if host exists in database
  //if (dbFetchCell("SELECT COUNT(*) FROM `devices` WHERE `hostname` = ?", array($hostname)) == '0')
  if (!dbExist('devices', '`hostname` = ?', array($hostname)))
  {
    if ($ip)
    {
      $ip_version = get_ip_version($ip);

      // Test reachability
      $options['ping_skip'] = isset($options['ping_skip']) && $options['ping_skip'];
      if ($options['ping_skip'])
      {
        $flags = $flags | OBS_PING_SKIP;
      }
      if (isPingable($hostname, $flags))
      {
        // Test directory exists in /rrd/
        if (!$config['rrd_override'] && file_exists($config['rrd_dir'].'/'.$hostname))
        {
          print_error("Directory <observium>/rrd/$hostname already exists.");
          return FALSE;
        }

        // Detect snmp transport
        if (stripos($snmp_transport, 'tcp') !== FALSE)
        {
          $snmp_transport = ($ip_version == 4 ? 'tcp' : 'tcp6');
        } else {
          $snmp_transport = ($ip_version == 4 ? 'udp' : 'udp6');
        }
        // Detect snmp port
        if (!is_numeric($snmp_port) || $snmp_port < 1 || $snmp_port > 65535)
        {
          $snmp_port = 161;
        } else {
          $snmp_port = (int)$snmp_port;
        }
        // Detect snmp version
        if (empty($snmp_version))
        {
          // Here set default snmp version order
          $i = 1;
          $snmp_version_order = array();
          foreach (array('v2c', 'v3', 'v1') as $tmp_version)
          {
            if ($config['snmp']['version'] == $tmp_version)
            {
              $snmp_version_order[0]  = $tmp_version;
            } else {
              $snmp_version_order[$i] = $tmp_version;
            }
            $i++;
          }
          ksort($snmp_version_order);

          foreach ($snmp_version_order as $tmp_version)
          {
            $ret = add_device($hostname, $tmp_version, $snmp_port, $snmp_transport, $options);
            if ($ret === FALSE)
            {
              // Set $options['break'] for break recursive
              $options['break'] = TRUE;
            }
            else if (is_numeric($ret) && $ret != 0)
            {
              return $ret;
            }
          }
        }
        else if ($snmp_version === "v3")
        {
          // Try each set of parameters from config
          foreach ($config['snmp']['v3'] as $auth_iter => $snmp_extra)
          {
            // Append SNMP context if passed
            if (isset($options['snmp_context']) && strlen($options['snmp_context']))
            {
              $snmp_extra['snmp_context'] = $options['snmp_context'];
            }

            $device = build_initial_device_array($hostname, NULL, $snmp_version, $snmp_port, $snmp_transport, $snmp_extra);

            if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2)
            {
              // Hide snmp auth
              print_message("Trying v3 parameters *** / ### [$auth_iter] ... ");
            } else {
              print_message("Trying v3 parameters " . $device['snmp_authname'] . "/" .  $device['snmp_authlevel'] . " ... ");
            }

            if (isSNMPable($device))
            {
              if (!check_device_duplicated($device))
              {
                if (isset($options['test']) && $options['test'])
                {
                  print_message('%WDevice "'.$hostname.'" has successfully been tested and available by '.strtoupper($snmp_transport).' transport with SNMP '.$snmp_version.' credentials.%n', 'color');
                  $device_id = -1;
                } else {
                  $device_id = createHost($hostname, NULL, $snmp_version, $snmp_port, $snmp_transport, $snmp_extra);
                  if ($options['ping_skip'])
                  {
                    set_entity_attrib('device', $device_id, 'ping_skip', 1);
                    // Force pingable check
                    if (isPingable($hostname, $flags ^ OBS_PING_SKIP))
                    {
                      print_warning("You passed the option the skip device ICMP echo pingable checks, but device responds to ICMP echo. Please check device preferences.");
                    }
                  }
                }
                return $device_id;
              } else {
                // When detected duplicate device, this mean it already SNMPable and not need check next auth!
                return FALSE;
              }
            } else {
              print_warning("No reply on credentials " . $device['snmp_authname'] . "/" .  $device['snmp_authlevel'] . " using $snmp_version.");
            }
          }
        }
        else if ($snmp_version === "v2c" || $snmp_version === "v1")
        {
          // Try each community from config
          foreach ($config['snmp']['community'] as $auth_iter => $snmp_community)
          {
            // Append SNMP context if passed
            $snmp_extra = array();
            if (isset($options['snmp_context']) && strlen($options['snmp_context']))
            {
              $snmp_extra['snmp_context'] = $options['snmp_context'];
            }
            $device = build_initial_device_array($hostname, $snmp_community, $snmp_version, $snmp_port, $snmp_transport, $snmp_extra);

            if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2)
            {
              // Hide snmp auth
              print_message("Trying $snmp_version community *** [$auth_iter] ...");
            } else {
              print_message("Trying $snmp_version community $snmp_community ...");
            }

            if (isSNMPable($device))
            {
              if (!check_device_duplicated($device))
              {
                if (isset($options['test']) && $options['test'])
                {
                  print_message('%WDevice "'.$hostname.'" has successfully been tested and available by '.strtoupper($snmp_transport).' transport with SNMP '.$snmp_version.' credentials.%n', 'color');
                  $device_id = -1;
                } else {
                  $device_id = createHost($hostname, $snmp_community, $snmp_version, $snmp_port, $snmp_transport, $snmp_extra);
                  if ($options['ping_skip'])
                  {
                    set_entity_attrib('device', $device_id, 'ping_skip', 1);
                    // Force pingable check
                    if (isPingable($hostname, $flags ^ OBS_PING_SKIP))
                    {
                      print_warning("You passed the option the skip device ICMP echo pingable checks, but device responds to ICMP echo. Please check device preferences.");
                    }
                  }
                }
                return $device_id;
              } else {
                // When detected duplicate device, this mean it already SNMPable and not need check next auth!
                return FALSE;
              }
            } else {
              if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2)
              {
                print_warning("No reply on given community *** using $snmp_version.");
              } else {
                print_warning("No reply on community $snmp_community using $snmp_version.");
              }
              $return = 0; // Return zero for continue trying next auth
            }
          }
        } else {
          print_error("Unsupported SNMP Version \"$snmp_version\".");
          $return = 0; // Return zero for continue trying next auth
        }

        if (!$device_id)
        {
          // Failed SNMP
          print_error("Could not reach $hostname with given SNMP parameters using $snmp_version.");
          $return = 0; // Return zero for continue trying next auth
        }
      } else {
        // failed Reachability
        print_error("Could not ping $hostname.");
      }
    } else {
      // Failed DNS lookup
      print_error("Could not resolve $hostname.");
    }
  } else {
    // found in database
    print_error("Already got device $hostname.");
  }

  return $return;
}

/**
 * Check duplicated devices in DB by sysName, snmpEngineID and entPhysicalSerialNum (if possible)
 *
 * If found duplicate devices return TRUE, in other cases return FALSE
 *
 * @param array $device Device array which should be checked for duplicates
 * @return bool TRUE if duplicates found
 */
// TESTME needs unit testing
function check_device_duplicated($device)
{
  // Hostname should be uniq
  if ($device['hostname'] &&
      //dbFetchCell("SELECT COUNT(*) FROM `devices` WHERE `hostname` = ?", array($device['hostname'])) != '0')
      dbExist('devices', '`hostname` = ?', array($device['hostname'])))
  {
    // Retun TRUE if have device with same hostname in DB
    print_error("Already got device with hostname (".$device['hostname'].").");
    return TRUE;
  }

  $snmpEngineID = snmp_cache_snmpEngineID($device);
  $sysName      = snmp_get_oid($device, 'sysName.0', 'SNMPv2-MIB');
  if (empty($sysName) || strpos($sysName, '.') === FALSE)
  {
    $sysName = FALSE;
  } else{
    // sysName stored in db as lowercase, always compare as lowercase too!
    $sysName = strtolower($sysName);
  }

  if (!empty($snmpEngineID))
  {
    $test_devices = dbFetchRows('SELECT * FROM `devices` WHERE `disabled` = 0 AND `snmpEngineID` = ?', array($snmpEngineID));
    foreach ($test_devices as $test)
    {
      $compare = strtolower($test['sysName']) === $sysName;
      if ($compare)
      {
        // Last check (if possible) serial, for cluster devices sysName and snmpEngineID same
        $test_entPhysical = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalSerialNum` != ? ORDER BY `entPhysicalClass` LIMIT 1', array($test['device_id'], ''));
        if (isset($test_entPhysical['entPhysicalSerialNum']))
        {
          $serial = snmp_get_oid($device, 'entPhysicalSerialNum.'.$test_entPhysical['entPhysicalIndex'], 'ENTITY-MIB');
          $compare = strtolower($serial) == strtolower($test_entPhysical['entPhysicalSerialNum']);
          if ($compare)
          {
            // This devices really same, with same sysName, snmpEngineID and entPhysicalSerialNum
            print_error("Already got device with SNMP-read sysName ($sysName), 'snmpEngineID' = $snmpEngineID and 'entPhysicalSerialNum' = $serial (".$test['hostname'].").");
            return TRUE;
          }
        } else {
          // Return TRUE if have same snmpEngineID && sysName in DB
          print_error("Already got device with SNMP-read sysName ($sysName) and 'snmpEngineID' = $snmpEngineID (".$test['hostname'].").");
          return TRUE;
        }
      }
    }
  } else {
    // If snmpEngineID empty, check only by sysName
    $test_devices = dbFetchRows('SELECT * FROM `devices` WHERE `disabled` = 0 AND `sysName` = ?', array($sysName));
    if ($sysName !== FALSE && is_array($test_devices) && count($test_devices) > 0)
    {
      $has_serial = FALSE;
      foreach ($test_devices as $test)
      {
        // Last check (if possible) serial, for cluster devices sysName and snmpEngineID same
        $test_entPhysical = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalSerialNum` != ? ORDER BY `entPhysicalClass` LIMIT 1', array($test['device_id'], ''));
        if (isset($test_entPhysical['entPhysicalSerialNum']))
        {
          $serial = snmp_get_oid($device, "entPhysicalSerialNum.".$test_entPhysical['entPhysicalIndex'], "ENTITY-MIB");
          $compare = strtolower($serial) == strtolower($test_entPhysical['entPhysicalSerialNum']);
          if ($compare)
          {
            // This devices really same, with same sysName, snmpEngineID and entPhysicalSerialNum
            print_error("Already got device with SNMP-read sysName ($sysName) and 'entPhysicalSerialNum' = $serial (".$test['hostname'].").");
            return TRUE;
          }
          $has_serial = TRUE;
        }
      }
      if (!$has_entPhysical)
      {
        // Return TRUE if have same sysName in DB
        print_error("Already got device with SNMP-read sysName ($sysName).");
        return TRUE;
      }
    }
  }

  // In all other cases return FALSE
  return FALSE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function scanUDP($host, $port, $timeout)
{
  $handle = fsockopen($host, $port, $errno, $errstr, 2);
  socket_set_timeout($handle, $timeout);
  $write = fwrite($handle,"\x00");
  if (!$write) { next; }
  $startTime = time();
  $header = fread($handle, 1);
  $endTime = time();
  $timeDiff = $endTime - $startTime;

  if ($timeDiff >= $timeout)
  {
    fclose($handle); return 1;
  } else { fclose($handle); return 0; }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function build_initial_device_array($hostname, $snmp_community, $snmp_version, $snmp_port = 161, $snmp_transport = 'udp', $snmp_extra = array())
{
  $device = array();
  $device['hostname']       = $hostname;
  $device['snmp_port']      = $snmp_port;
  $device['snmp_transport'] = $snmp_transport;
  $device['snmp_version']   = $snmp_version;

  if ($snmp_version === "v2c" || $snmp_version === "v1")
  {
    $device['snmp_community'] = $snmp_community;
  }
  else if ($snmp_version == "v3")
  {
    $device['snmp_authlevel']  = $snmp_extra['authlevel'];
    $device['snmp_authname']   = $snmp_extra['authname'];
    $device['snmp_authpass']   = $snmp_extra['authpass'];
    $device['snmp_authalgo']   = $snmp_extra['authalgo'];
    $device['snmp_cryptopass'] = $snmp_extra['cryptopass'];
    $device['snmp_cryptoalgo'] = $snmp_extra['cryptoalgo'];
  }
  // Append SNMP context if passed
  if (isset($snmp_extra['snmp_context']) && strlen($snmp_extra['snmp_context']))
  {
    $device['snmp_context'] = $snmp_extra['snmp_context'];
  }

  print_debug_vars($device);

  return $device;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function netmask2cidr($netmask)
{
  $addr = Net_IPv4::parseAddress("1.2.3.4/$netmask");
  return $addr->bitmask;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function cidr2netmask($cidr)
{
  return (long2ip(ip2long("255.255.255.255") << (32-$cidr)));
}

// Detect SNMP auth params without adding device by hostname or IP
// if SNMP auth detected return array with auth params or FALSE if not detected
// DOCME needs phpdoc block
// TESTME needs unit testing
function detect_device_snmpauth($hostname, $snmp_port = 161, $snmp_transport = 'udp', $detect_ip_version = FALSE)
{
  global $config;

  // Additional checks for IP version
  if ($detect_ip_version)
  {
    $ip_version = get_ip_version($hostname);
    if (!$ip_version)
    {
      $ip = gethostbyname6($hostname);
      $ip_version = get_ip_version($ip);
    }
    // Detect snmp transport
    if (stripos($snmp_transport, 'tcp') !== FALSE)
    {
      $snmp_transport = ($ip_version == 4 ? 'tcp' : 'tcp6');
    } else {
      $snmp_transport = ($ip_version == 4 ? 'udp' : 'udp6');
    }
  }
  // Detect snmp port
  if (!is_numeric($snmp_port) || $snmp_port < 1 || $snmp_port > 65535)
  {
    $snmp_port = 161;
  } else {
    $snmp_port = (int)$snmp_port;
  }

  // Here set default snmp version order
  $i = 1;
  $snmp_version_order = array();
  foreach (array('v2c', 'v3', 'v1') as $tmp_version)
  {
    if ($config['snmp']['version'] == $tmp_version)
    {
      $snmp_version_order[0]  = $tmp_version;
    } else {
      $snmp_version_order[$i] = $tmp_version;
    }
    $i++;
  }
  ksort($snmp_version_order);

  foreach ($snmp_version_order as $snmp_version)
  {
    if ($snmp_version === 'v3')
    {
      // Try each set of parameters from config
      foreach ($config['snmp']['v3'] as $auth_iter => $snmp_v3)
      {
        $device = build_initial_device_array($hostname, NULL, $snmp_version, $snmp_port, $snmp_transport, $snmp_v3);

        if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2)
        {
          // Hide snmp auth
          print_message("Trying v3 parameters *** / ### [$auth_iter] ... ");
        } else {
          print_message("Trying v3 parameters " . $device['snmp_authname'] . "/" .  $device['snmp_authlevel'] . " ... ");
        }

        if (isSNMPable($device))
        {
          return $device;
        } else {
          if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2)
          {
            print_warning("No reply on credentials *** / ### using $snmp_version.");
          } else {
            print_warning("No reply on credentials " . $device['snmp_authname'] . "/" .  $device['snmp_authlevel'] . " using $snmp_version.");
          }
        }
      }
    } else { // if ($snmp_version === "v2c" || $snmp_version === "v1")
      // Try each community from config
      foreach ($config['snmp']['community'] as $auth_iter => $snmp_community)
      {
        $device = build_initial_device_array($hostname, $snmp_community, $snmp_version, $snmp_port, $snmp_transport);

        if ($config['snmp']['hide_auth'] && OBS_DEBUG < 2)
        {
          // Hide snmp auth
          print_message("Trying $snmp_version community *** [$auth_iter] ...");
        } else {
          print_message("Trying $snmp_version community $snmp_community ...");
        }

        if (isSNMPable($device))
        {
          return $device;
        } else {
          print_warning("No reply on community $snmp_community using $snmp_version.");
        }
      }
    }
  }

  return FALSE;
}

/**
 * Checks device availability by snmp query common oids
 *
 * @param array $device Device array
 * @return float SNMP query runtime in milliseconds
 */
// TESTME needs unit testing
function isSNMPable($device)
{
  if (isset($device['os'][0]) && isset($GLOBALS['config']['os'][$device['os']]['snmpable']) && $device['os'] != 'generic')
  {
    // Known device os, and defined custom snmpable OIDs
    $pos   = snmp_get_multi_oid($device, $GLOBALS['config']['os'][$device['os']]['snmpable'], array(), 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_NUMERIC);
    $count = count($pos);
  } else {
    // Normal checks by sysObjectID and sysUpTime
    $pos   = snmp_get_multi_oid($device, 'sysObjectID.0 sysUpTime.0', array(), 'SNMPv2-MIB');
    $count = count($pos[0]);

    if ($count === 0 && (empty($device['os']) || !isset($GLOBALS['config']['os'][$device['os']])))
    {
      // New device (or os changed) try to all snmpable OIDs
      foreach (array_chunk($GLOBALS['config']['os']['generic']['snmpable'], 3) as $snmpable)
      {
        $pos   = snmp_get_multi_oid($device, $snmpable, array(), 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_NUMERIC);
        if ($count = count($pos)) { break; } // stop foreach on first oids set
      }
    }
  }

  if ($GLOBALS['snmp_status'] && $count > 0)
  {
    // SNMP response time in milliseconds.
    $time_snmp = $GLOBALS['exec_status']['runtime'] * 1000;
    $time_snmp = number_format($time_snmp, 2, '.', '');
    return $time_snmp;
  }

  return 0;
}

/**
 * Checks device availability by icmp echo response
 * If flag OBS_PING_SKIP passed, pings skipped and returns 0.001 (1ms)
 *
 * @param string $hostname Device hostname or IP address
 * @param int Flags. Supported OBS_DNS_A, OBS_DNS_AAAA and OBS_PING_SKIP
 * @return float Average response time for used retries count (default retries is 3)
 */
function isPingable($hostname, $flags = OBS_DNS_ALL)
{
  global $config;

  $ping_debug = isset($config['ping']['debug']) && $config['ping']['debug'];
  $try_a      = is_flag_set(OBS_DNS_A, $flags);

  set_status_var('ping_dns', 'ok'); // Set initially dns status as ok
  if (is_flag_set(OBS_PING_SKIP, $flags))
  {
    return 0.001; // Ping is skipped, just return 1ms
  }

  // Timeout, default is 500ms (as in fping)
  $timeout = (isset($config['ping']['timeout']) ? (int)$config['ping']['timeout'] : 500);
  if ($timeout < 50) { $timeout = 50; }
  else if ($timeout > 2000) { $timeout = 2000; }

  // Retries, default is 3
  $retries = (isset($config['ping']['retries']) ? (int)$config['ping']['retries'] : 3);
  if      ($retries < 1)  { $retries = 3; }
  else if ($retries > 10) { $retries = 10; }

  if ($ip_version = get_ip_version($hostname))
  {
    // Ping by IP
    if ($ip_version === 6)
    {
      $cmd = $config['fping6'] . " -t $timeout -c 1 -q $hostname 2>&1";
    } else {
      if (!$try_a)
      {
        if ($ping_debug) { logfile('debug.log', __FUNCTION__ . "() | DEVICE: $hostname | Passed IPv4 address but device use IPv6 transport"); }
        print_debug('Into function ' . __FUNCTION__ . '() passed IPv4 address ('.$hostname.'but device use IPv6 transport');
        set_status_var('ping_dns', 'incorrect'); // Incorrect
        return 0;
      }
      // Forced check for actual IPv4 address
      $cmd = $config['fping'] . " -t $timeout -c 1 -q $hostname 2>&1";
    }
  } else {
    // First try IPv4
    $ip = ($try_a ? gethostbyname($hostname) : FALSE); // Do not check IPv4 if transport IPv6
    if ($ip && $ip != $hostname)
    {
      $cmd = $config['fping'] . " -t $timeout -c 1 -q $ip 2>&1";
    } else {
      $ip = gethostbyname6($hostname, OBS_DNS_AAAA);
      // Second try IPv6
      if ($ip)
      {
        $cmd = $config['fping6'] . " -t $timeout -c 1 -q $ip 2>&1";
      } else {
        // No DNS records
        if ($ping_debug) { logfile('debug.log', __FUNCTION__ . "() | DEVICE: $hostname | NO DNS record found"); }
        set_status_var('ping_dns', 'alert');
        return 0;
      }
    }
  }

  // Sleep interval between retries, max 1 sec, min 333ms (1s/3),
  // next retry will increase interval by 1.5 Backoff factor (see fping -B option)
  // We not use fping native retries, because fping waiting for all responses, but we wait only first OK
  $sleep = floor(1000000 / $retries);
  if ($sleep < 333000) { $sleep = 333000; }

  $ping = 0; // Init false
  for ($i=1; $i <= $retries; $i++)
  {
    $output = external_exec($cmd);
    if ($GLOBALS['exec_status']['exitcode'] === 0)
    {
      // normal $output = '8.8.8.8 : xmt/rcv/%loss = 1/1/0%, min/avg/max = 1.21/1.21/1.21'
      $tmp = explode('/', $output);
      $ping = $tmp[7]; // Avg
      if (!$ping) { $ping = 0.001; } // Protection from zero (exclude false status)
    }
    if ($ping) { break; }

    if ($ping_debug)
    {
      $ping_times = format_unixtime($GLOBALS['exec_status']['endtime'] - $GLOBALS['exec_status']['runtime'], 'H:i:s.v') . ', ' . round($GLOBALS['exec_status']['runtime'], 3) . 's';
      logfile('debug.log', __FUNCTION__ . "() | DEVICE: $hostname | $ping_times | FPING OUT ($i): " . $output);
      //log_event(__FUNCTION__ . "() | DEVICE: $hostname | FPING OUT ($i): " . $output, $device_id, 'device', $device_id, 7); // severity 7, debug
      // WARNING, this is very long operation, increase polling time up to 10s
      if ($i == $retries && is_file($config['mtr']))
      {
        $mtr = $config['mtr'] . " -r -n -c 3 $ip";
        logfile('debug.log', __FUNCTION__ . "() | DEVICE: $hostname | MTR OUT:\n" . external_exec($mtr));
      }
    }

    if ($i < $retries)
    {
      // Sleep and increase next sleep interval
      usleep($sleep);
      $sleep *= 1.5; // Backoff factor 1.5
    }
  }

  return $ping;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function is_odd($number)
{
  return $number & 1; // 0 = even, 1 = odd
}

// TESTME needs unit testing
/**
 * Add device into database
 *
 * @param string $hostname Device hostname
 * @param string $snmp_community SNMP v1/v2c community
 * @param string $snmp_version   SNMP version (v1, v2c, v3)
 * @param int    $snmp_port      SNMP port (default: 161)
 * @param string $snmp_transport SNMP transport (udp, udp6, tcp, tcp6)
 * @param array  $snmp_extra     SNMP v3 auth params and/or SNMP context
 *
 * @return bool|string
 */
function createHost($hostname, $snmp_community = NULL, $snmp_version, $snmp_port = 161, $snmp_transport = 'udp', $snmp_extra = array())
{
  $hostname = trim(strtolower($hostname));

  $device = array('hostname'       => $hostname,
                  'sysName'        => $hostname,
                  'status'         => '1',
                  'snmp_community' => $snmp_community,
                  'snmp_port'      => $snmp_port,
                  'snmp_transport' => $snmp_transport,
                  'snmp_version'   => $snmp_version
            );

  // Add snmp v3 auth params & snmp context
  foreach (array('authlevel', 'authname', 'authpass', 'authalgo', 'cryptopass', 'cryptoalgo', 'context') as $v3_key)
  {
    if (isset($snmp_extra['snmp_'.$v3_key]))
    {
      // Or $snmp_v3['snmp_authlevel']
      $device['snmp_'.$v3_key] = $snmp_extra['snmp_'.$v3_key];
    }
    else if (isset($snmp_extra[$v3_key]))
    {
      // Or $snmp_v3['authlevel']
      $device['snmp_'.$v3_key] = $snmp_extra[$v3_key];
    }
  }

  $device['os']           = get_device_os($device);
  $device['snmpEngineID'] = snmp_cache_snmpEngineID($device);
  $device['sysName']      = snmp_get_oid($device, 'sysName.0',     'SNMPv2-MIB');
  $device['location']     = snmp_get_oid($device, 'sysLocation.0', 'SNMPv2-MIB');
  $device['sysContact']   = snmp_get_oid($device, 'sysContact.0',  'SNMPv2-MIB');

  if(isset($GLOBALS['config']['poller_name']))
  {
    $poller_id = dbFetchCell("SELECT `poller_id` FROM `pollers` WHERE `poller_name` = ?", array($GLOBALS['config']['poller_name']));

    if(is_numeric($poller_id))
    {
      $device['poller_id'] = $poller_id;
    }
  }

  if ($device['os'])
  {
    $device_id = dbInsert($device, 'devices');
    if ($device_id)
    {
      log_event("Device added: $hostname", $device_id, 'device', $device_id, 5); // severity 5, for logging user/console info
      if (is_cli())
      {
        print_success("Now discovering ".$device['hostname']." (id = ".$device_id.")");
        $device['device_id'] = $device_id;
        // Discover things we need when linking this to other hosts.
        discover_device($device, $options = array('m' => 'ports'));
        discover_device($device, $options = array('m' => 'ipv4-addresses'));
        discover_device($device, $options = array('m' => 'ipv6-addresses'));
        log_event("snmpEngineID -> ".$device['snmpEngineID'], $device, 'device', $device['device_id']);
        // Reset `last_discovered` for full rediscover device by cron
        dbUpdate(array('last_discovered' => 'NULL'), 'devices', '`device_id` = ?', array($device_id));
        array_push($GLOBALS['devices'], $device_id); // FIXME, seems as $devices var not used anymore
      }

      // Request for clear WUI cache
      set_cache_clear('wui');

      return($device_id);
    } else {
      return FALSE;
    }
  } else {
    return FALSE;
  }
}

// Allows overwriting elements of an array of OIDs with replacement values from a private MIB.
// Currently used by ports to replace OIDs with private ports tables.

function merge_private_mib(&$device, $entity_type, $mib, &$entity_stats, $limit_oids = NULL)
{

  global $config;

  foreach ($config['mibs'][$mib][$entity_type] as $table => $def)
  {

    if (isset($def['oids']) && is_array($def['oids']))
    {

      print_cli_data_field($mib);
      echo $table . ' ';

      $walked = array();

      // Walk to $entity_tmp, link to $entity_stats if we're not rewriting
      if (isset($def['map']))
      {
        $entity_tmp = array();
        if (isset($def['map']['oid']))
        {
          echo $def['map']['oid'] . ' ';
          $entity_tmp = snmpwalk_cache_oid($device, $def['map']['oid'], $entity_tmp, $mib);

          // Skip next Oids walk if no response
          if (!snmp_status()) { continue; }

          $walked[] = $def['map']['oid'];
        }
        foreach ((array)$def['map']['oid_extra'] as $oid)
        {
          echo $oid . ' ';
          $entity_tmp = snmpwalk_cache_oid($device, $oid, $entity_tmp, $mib);
          $walked[] = $oid;
        }
      } else {
        $entity_tmp = &$entity_stats;
      }

      // Populated $entity_tmp
      foreach ($def['oids'] as $oid => $entry)
      {
        // Skip if there's an OID list and we're not on it.
        if (isset($limit_oids) && !in_array($oid, $limit_oids)) { continue; }

        // If this OID is being used twice, don't walk it again.
        if (!isset($entry['oid']) || in_array($entry['oid'], $walked)) { continue; }

        echo $entry['oid'] . ' ';
        $flags = isset($entry['snmp_flags']) ? $entry['snmp_flags'] : OBS_SNMP_ALL;
        $entity_tmp = snmpwalk_cache_oid($device, $entry['oid'], $entity_tmp, $mib, NULL, $flags);

        // Skip next Oids walk if no response
        if (!snmp_status() && $oid == array_key_first($def['oids'])) { continue 2; }

        $walked[] = $entry['oid'];
      }

      // Rewrite indexes using map from $entity_tmp to $entity_stats
      if (isset($def['map']))
      {
        $entity_new = array();
        $map_tmp = array();
        // Generate mapping list
        if (isset($def['map']['index']))
        {
          // Index by tags
          foreach ($entity_tmp as $index => $entry)
          {
            $entry['index'] = $index;
            $map_index = array_tag_replace($entry, $def['map']['index']);
            $map_tmp[$index] = $map_index;
          }
        } else {
          // Mapping by Oid
          foreach ($entity_stats as $index => $entry)
          {
            if (isset($entry[$def['map']['map']]))
            {
              $map_tmp[$entry[$def['map']['map']]] = $index;
            }
          }
        }

        foreach ($entity_tmp as $index => $entry)
        {
          if (isset($map_tmp[$index]))
          {
            foreach ($entry as $oid => $value)
            {
              $entity_new[$map_tmp[$index]][$oid] = $value;
            }
          }
        }
      } else {
        $entity_new = $entity_tmp;
      }

      echo '['; // start change list

      foreach ($entity_new as $index => $port)
      {
        foreach ($def['oids'] as $oid => $entry)
        {
          // Skip if there's an OID list and we're not on it.
          if (isset($limit_oids) && !in_array($oid, $limit_oids))
          {
            continue;
          }

          $mib_oid = $entry['oid'];
          if (isset($entry['oid']) && isset($port[$mib_oid]))
          {
            $entity_stats[$index][$oid] = $port[$mib_oid];

            if (isset($entry['transformations']))
            {
              // Translate to standard IF-MIB values
              $entity_stats[$index][$oid] = string_transform($entity_stats[$index][$oid], $entry['transformations']);
              echo 'T';
            }

            echo '.';
          }
          else if (isset($entry['value']))
          {
            // Set fixed value
            $entity_stats[$index][$oid] = $entry['value'];
          }
          else
          {
            echo '!';
          }
        }
      }
      echo ']'; // end change list
      echo PHP_EOL; // end CLI DATA FIELD
    }
  }
  return TRUE;
}

// BOOLEAN safe function to check if hostname resolves as IPv4 or IPv6 address
// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function isDomainResolves($hostname, $flags = OBS_DNS_ALL)
{
  return (TRUE && gethostbyname6($hostname, $flags));
}

/**
 * Returns IP version for string or FALSE if string not an IP
 *
 * Examples:
 *  get_ip_version('127.0.0.1')   === 4
 *  get_ip_version('::1')         === 6
 *  get_ip_version('my_hostname') === FALSE
 *
 * @param sting $address IP address string
 * @return mixed IP version or FALSE if passed incorrect address
 */
function get_ip_version($address)
{
  $address_version = FALSE;
  if      (strpos($address, '/') !== FALSE)
  {
    // Dump condition,
    // IPs with CIDR not correct for us here
  }
  //else if (strpos($address, '.') !== FALSE && Net_IPv4::validateIP($address))
  else if (preg_match('%^'.OBS_PATTERN_IPV4.'$%', $address))
  {
    $address_version = 4;
  }
  //else if (strpos($address, ':') !== FALSE && Net_IPv6::checkIPv6($address))
  else if (preg_match('%^'.OBS_PATTERN_IPV6.'$%i', $address))
  {
    $address_version = 6;
  }
  return $address_version;
}

/**
 * Check if a given IPv4 address (and prefix length) is valid.
 *
 * @param string $ipv4_address    IPv4 Address
 * @param string $ipv4_prefixlen  IPv4 Prefix length (optional, either 24 or 255.255.255.0)
 *
 * @return bool Returns TRUE if address is valid, FALSE if not valid.
 */
// TESTME needs unit testing
function is_ipv4_valid($ipv4_address, $ipv4_prefixlen = NULL)
{

  if (str_contains($ipv4_address, '/'))
  {
    list($ipv4_address, $ipv4_prefixlen) = explode('/', $ipv4_address);
  }
  $ip_full = $ipv4_address . '/' . $ipv4_prefixlen;

  // False if invalid IPv4 syntax
  if (strlen($ipv4_prefixlen) &&
      !preg_match('%^'.OBS_PATTERN_IPV4_NET.'$%', $ip_full))
  {
    // Address with prefix
    return FALSE;
  }
  else if (!preg_match('%^'.OBS_PATTERN_IPV4.'$%i', $ipv4_address))
  {
    // Address withot prefix
    return FALSE;
  }

  $ipv4_type = get_ip_type($ip_full);

  // False if link-local, unspecified or any used defined
  if (in_array($ipv4_type, $GLOBALS['config']['ip-address']['ignore_type']))
  {
    return FALSE;
  }

  return TRUE;
}

/**
 * Check if a given IPv6 address (and prefix length) is valid.
 * Link-local addresses are considered invalid.
 *
 * @param string $ipv6_address    IPv6 Address
 * @param string $ipv6_prefixlen  IPv6 Prefix length (optional)
 *
 * @return bool Returns TRUE if address is valid, FALSE if not valid.
 */
// TESTME needs unit testing
function is_ipv6_valid($ipv6_address, $ipv6_prefixlen = NULL)
{
  if (str_contains($ipv6_address, '/'))
  {
    list($ipv6_address, $ipv6_prefixlen) = explode('/', $ipv6_address);
  }
  $ip_full = $ipv6_address . '/' . $ipv6_prefixlen;

  // False if invalid IPv6 syntax
  if (strlen($ipv6_prefixlen) &&
      !preg_match('%^'.OBS_PATTERN_IPV6_NET.'$%i', $ip_full))
  {
    // Address with prefix
    return FALSE;
  }
  else if (!preg_match('%^'.OBS_PATTERN_IPV6.'$%i', $ipv6_address))
  {
    // Address withot prefix
    return FALSE;
  }

  $ipv6_type = get_ip_type($ip_full);

  // False if link-local, unspecified or any used defined
  if (in_array($ipv6_type, $GLOBALS['config']['ip-address']['ignore_type']))
  {
    return FALSE;
  }

  return TRUE;
}

/**
 * Detect IP type.
 * Based on https://www.ripe.net/manage-ips-and-asns/ipv6/ipv6-address-types/ipv6-address-types
 *
 *  Known types:
 *   - unspecified    : ::/128, 0.0.0.0
 *   - loopback       : ::1/128, 127.0.0.1
 *   - ipv4mapped     : only for IPv6 ::ffff/96 (::ffff:192.0.2.47)
 *   - private        : fc00::/7 (fdf8:f53b:82e4::53),
 *                      10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
 *   - link-local     : fe80::/10 (fe80::200:5aee:feaa:20a2),
 *                      169.254.0.0/16
 *   - teredo         : only for IPv6 2001:0000::/32 (2001:0000:4136:e378:8000:63bf:3fff:fdd2)
 *   - benchmark      : 2001:0002::/48 (2001:0002:6c::430),
 *                      198.18.0.0/15
 *   - orchid         : only for IPv6 2001:0010::/28 (2001:10:240:ab::a)
 *   - 6to4           : 2002::/16 (2002:cb0a:3cdd:1::1),
 *                      192.88.99.0/24
 *   - documentation  : 2001:db8::/32 (2001:db8:8:4::2),
 *                      192.0.2.0/24, 198.51.100.0/24, 203.0.113.0/24
 *   - global-unicast : only for IPv6 2000::/3
 *   - multicast      : ff00::/8 (ff01:0:0:0:0:0:0:2), 224.0.0.0/4
 *   - unicast        : all other
 *
 * @param string $address IPv4 or IPv6 address string
 * @return string IP type
 */
function get_ip_type($address)
{
  global $config;

  list($ip, $bits) = explode('/', trim($address)); // Remove subnet/mask if exist

  $ip_version = get_ip_version($ip);
  switch ($ip_version)
  {
    case 4:

      // Detect IPv4 broadcast
      if (strlen($bits))
      {
        $ip_parse = Net_IPv4::parseAddress($address);
        if ($ip == $ip_parse->broadcast && $ip_parse->bitmask < 31) // Do not set /31 and /32 as broadcast!
        {
          $ip_type = 'broadcast';
          break;
        }
      }

      // no break here!
    case 6:

      $ip_type = ($ip_version == 4) ? 'unicast': 'reserved'; // Default for any valid address
      foreach ($config['ip_types'] as $type => $entry)
      {
        if (isset($entry['networks']) && match_network($ip, $entry['networks'], TRUE))
        {
          // Stop loop if IP founded in networks
          $ip_type = $type;
          break;
        }
        
      }
      break;

    default:
      // Not valid IP address
      return FALSE;
  }

  return $ip_type;
}

/**
 * Parse string for valid IP/Network queries.
 *
 * Valid queries example:
 *    - 10.0.0.0/8  - exactly IPv4 network, matches to 10.255.0.2, 10.0.0.0, 10.255.255.255
 *    - 10.12.0.3/8 - same as previous, NOTE network detected by prefix: 10.0.0.0/8
 *    - 10.12.0.3   - single IPv4 address
 *    - *.12.0.3, 10.*.3   - * matching by any string in address
 *    - ?.12.0.3, 10.?.?.3 - ? matching by single char
 *    - 10.12              - match by part of address, matches to 10.12.2.3, 10.122.3.3, 1.2.110.120
 *    - 1762::b03:1:ae00/119        - exactly IPv6 network, matches to 1762::b03:1:ae00, 1762::B03:1:AF18, 1762::b03:1:afff
 *    - 1762:0:0:0:0:B03:1:AF18/119 - same as previous, NOTE network detected by prefix: 1762::b03:1:ae00/119
 *    - 1762:0:0:0:0:B03:1:AF18     - single IPv6 address
 *    - *::B03:1:AF18, 1762::*:AF18 - * matching by any string in address
 *    - ?::B03:1:AF18, 1762::?:AF18 - ? matching by single char
 *    - 1762:b03                    -  match by part of address, matches to 1:AF18:1762::B03, 1762::b03:1:ae00
 *
 * Return array contain this params:
 *    'query_type' - which query type required.
 *                   Possible: single       (single IP address),
 *                             network      (addresses inside network),
 *                             like, %like% (part of address with masks *, ?)
 *    'ip_version' - numeric IP version (4, 6)
 *    'ip_type'    - ipv4 or ipv6
 *    'address'    - string with passed IP address without prefixes or address part
 *    'prefix'     - detected network prefix
 *    'network'    - detected network with prefix
 *    'network_start' - first address of network
 *    'network_end'   - last address of network (broadcast)
 *
 * @param string $network Network/IP query string
 * @return array Array with parsed network params
 */
function parse_network($network)
{
  $network = trim($network);

  $array = array(
    'query_type' => 'network', // Default query type by valid network with prefix
  );

  if (preg_match('%^'.OBS_PATTERN_IPV4_NET.'$%', $network, $matches))
  {
    // Match by valid IPv4 network
    $array['ip_version'] = 4;
    $array['ip_type']    = 'ipv4';
    $array['address']    = $matches['ipv4']; // Same as IP
    //$array['prefix']     = $matches['ipv4_prefix'];

    // Convert Cisco Inverse netmask to normal mask
    if (isset($matches['ipv4_inverse_mask']))
    {
      $matches['ipv4_mask'] = inet_pton($matches['ipv4_inverse_mask']);
      $matches['ipv4_mask'] = inet_ntop(~$matches['ipv4_mask']); // Binary inverse and back to IP string
      $matches['ipv4_network'] = $matches['ipv4'] . '/' . $matches['ipv4_mask'];
    }

    if ($matches['ipv4_prefix'] == '32' || $matches['ipv4_mask'] == '255.255.255.255')
    {
      $array['prefix']        = '32';
      $array['network_start'] = $array['address'];
      $array['network_end']   = $array['address'];
      $array['network']       = $matches['ipv4_network']; // Network with prefix
      $array['query_type']    = 'single'; // Single IP query
    } else {
      $address = Net_IPv4::parseAddress($matches['ipv4_network']);
      //print_vars($address);
      $array['prefix']        = $address->bitmask . '';
      $array['network_start'] = $address->network;
      $array['network_end']   = $address->broadcast;
      $array['network']       = $array['network_start'] . '/' . $array['prefix']; // Network with prefix
    }
  }
  else if (preg_match('%^'.OBS_PATTERN_IPV6_NET.'$%i', $network, $matches))
  {
    // Match by valid IPv6 network
    $array['ip_version'] = 6;
    $array['ip_type']    = 'ipv6';
    $array['address']    = $matches['ipv6']; // Same as IP
    $array['prefix']     = $matches['ipv6_prefix'];
    if ($array['prefix'] == 128)
    {
      $array['network_start'] = $array['address'];
      $array['network_end']   = $array['address'];
      $array['network']       = $matches['ipv6_network']; // Network with prefix
      $array['query_type'] = 'single'; // Single IP query
    } else {
      $address = Net_IPv6::parseAddress($array['address'], $array['prefix']);
      //print_vars($address);
      $array['network_start'] = $address['start'];
      $array['network_end']   = $address['end'];
      $array['network']       = $array['network_start'] . '/' . $array['prefix']; // Network with prefix
    }
  }
  else if ($ip_version = get_ip_version($network))
  {
    // Single IPv4/IPv6
    $array['ip_version'] = $ip_version;
    $array['address']    = $network;
    $array['prefix']     = $matches['ipv6_prefix'];
    if ($ip_version == 4)
    {
      $array['ip_type']  = 'ipv4';
      $array['prefix']   = '32';
    } else {
      $array['ip_type']  = 'ipv6';
      $array['prefix']   = '128';
    }
    $array['network_start'] = $array['address'];
    $array['network_end']   = $array['address'];
    $array['network']       = $network . '/' . $array['prefix']; // Add prefix
    $array['query_type']    = 'single'; // Single IP query
  }
  else if (preg_match('/^[\d\.\?\*]+$/', $network))
  {
    // Match IPv4 by mask
    $array['ip_version'] = 4;
    $array['ip_type']    = 'ipv4';
    $array['address']    = $network;
    if (str_contains($network, array('?', '*')))
    {
      // If network contains * or !
      $array['query_type'] = 'like';
    } else {
      // All other cases
      $array['query_type'] = '%like%';
    }
  }
  else if (preg_match('/^[abcdef\d\:\?\*]+$/i', $network))
  {
    // Match IPv6 by mask
    $array['ip_version'] = 6;
    $array['ip_type']    = 'ipv6';
    $array['address']    = $network;
    if (str_contains($network, array('?', '*')))
    {
      // If network contains * or !
      $array['query_type'] = 'like';
    } else {
      // All other cases
      $array['query_type'] = '%like%';
    }
  } else {
    // Not valid network string passed
    return FALSE;
  }

  // Add binary addresses for single and network queries
  switch ($array['query_type'])
  {
    case 'single':
      $array['address_binary']       = inet_pton($array['address']);
      break;
    case 'network':
      $array['network_start_binary'] = inet_pton($array['network_start']);
      $array['network_end_binary']   = inet_pton($array['network_end']);
      break;
  }

  return $array;
}

/**
 * Determines whether or not the supplied IP address is within the supplied network (IPv4 or IPv6).
 *
 * @param string $ip     IP Address
 * @param string $nets   IPv4/v6 networks
 * @param bool   $first  FIXME
 *
 * @return bool Returns TRUE if address is found in supplied network, FALSE if it is not.
 */
// TESTME needs unit testing
function match_network($ip, $nets, $first = FALSE)
{
  $return = FALSE;
  $ip_version = get_ip_version($ip);
  if ($ip_version)
  {
    if (!is_array($nets)) { $nets = array($nets); }
    foreach ($nets as $net)
    {
      $ip_in_net = FALSE;

      $revert    = (preg_match('/^\!/', $net) ? TRUE : FALSE); // NOT match network
      if ($revert)
      {
        $net     = preg_replace('/^\!/', '', $net);
      }

      if ($ip_version == 4)
      {
        if (strpos($net, '.') === FALSE) { continue; }      // NOT IPv4 net, skip
        if (strpos($net, '/') === FALSE) { $net .= '/32'; } // NET without mask as single IP
        $ip_in_net = Net_IPv4::ipInNetwork($ip, $net);
      } else {
        //print_vars($ip); echo(' '); print_vars($net); echo(PHP_EOL);
        if (strpos($net, ':') === FALSE) { continue; }       // NOT IPv6 net, skip
        if (strpos($net, '/') === FALSE) { $net .= '/128'; } // NET without mask as single IP
        $ip_in_net = Net_IPv6::isInNetmask($ip, $net);
      }

      if ($revert && $ip_in_net) { return FALSE; } // Return FALSE if IP found in network where should NOT match
      if ($first  && $ip_in_net) { return TRUE; }  // Return TRUE if IP found in first match
      $return = $return || $ip_in_net;
    }
  }

  return $return;
}

/**
 * Convert SNMP hex string to binary map, mostly used for VLANs discovery
 *
 * Examples:
 *   "00 40" => "0000000001000000"
 *
 * @param string $hex HEX encoded string
 * @return string Binary string
 */
function hex2binmap($hex)
{
  $hex = str_replace(array(' ', "\n"), '', $hex);

  $binary = '';
  $length = strlen($hex);
  for ($i = 0; $i < $length; $i++)
  {
    $char = $hex[$i];
    $binary .= zeropad(base_convert($char, 16, 2), 4);
  }

  return $binary;
}

/**
 * Convert HEX encoded IP value to pretty IP string
 *
 * Examples:
 *  IPv4 "C1 9C 5A 26" => "193.156.90.38"
 *  IPv4 "J}4:"        => "74.125.52.58"
 *  IPv6 "20 01 07 F8 00 12 00 01 00 00 00 00 00 05 02 72" => "2001:07f8:0012:0001:0000:0000:0005:0272"
 *  IPv6 "20:01:07:F8:00:12:00:01:00:00:00:00:00:05:02:72" => "2001:07f8:0012:0001:0000:0000:0005:0272"
 *
 * @param string $ip_hex HEX encoded IP address
 *
 * @return string IP address or original input string if not contains IP address
 */
function hex2ip($ip_hex)
{
  $ip  = trim($ip_hex, "\"\t\n\r\0\x0B");

  // IPv6z, ie: 2a:02:a0:10:80:03:00:00:00:00:00:00:00:00:00:01%503316482
  if (str_contains($ip, '%'))
  {
    list($ip) = explode('%', $ip);
  }

  $len = strlen($ip);
  if ($len === 5 && $ip[0] === ' ')
  {
    $ip  = substr($ip, 1);
    $len = 4;
  }
  if ($len === 4)
  {
    // IPv4 hex string converted to SNMP string
    $ip  = str2hex($ip);
    $len = strlen($ip);
  }

  $ip  = str_replace(' ', '', $ip);

  if ($len > 8)
  {
    // For IPv6
    $ip = str_replace(':', '', $ip);
    $len = strlen($ip);
  }

  if (!ctype_xdigit($ip))
  {
    return $ip_hex;
  }

  switch ($len)
  {
    case 8:
      // IPv4
      $ip_array = array();
      foreach (str_split($ip, 2) as $entry)
      {
        $ip_array[] = hexdec($entry);
      }
      $separator = '.';
      break;

    case 16:
      // Cisco incorrect IPv4 (54 2E 68 02 FF FF FF FF)
      $ip_array = array();
      foreach (str_split($ip, 2) as $i => $entry)
      {
        if ($i == 4) { break; }
        $ip_array[] = hexdec($entry);
      }
      $separator = '.';
      break;

    case 32:
      // IPv6
      $ip_array = str_split(strtolower($ip), 4);
      $separator = ':';
      break;

    default:
      // Try convert hex string to string
      $ip = snmp_hexstring($ip_hex);
      if (get_ip_version($ip))
      {
        return $ip;
      }
      return $ip_hex;
  }
  $ip = implode($separator, $ip_array);

  return $ip;
}

/**
 * Convert IP string to HEX encoded value.
 *
 * Examples:
 *  IPv4 "193.156.90.38" => "C1 9C 5A 26"
 *  IPv6 "2001:07f8:0012:0001:0000:0000:0005:0272" => "20 01 07 f8 00 12 00 01 00 00 00 00 00 05 02 72"
 *  IPv6 "2001:7f8:12:1::5:0272" => "20 01 07 f8 00 12 00 01 00 00 00 00 00 05 02 72"
 *
 * @param string $ip IP address string
 * @param string $separator Separator for HEX parts
 *
 * @return string HEX encoded address
 */
function ip2hex($ip, $separator = ' ')
{
  $ip_hex     = trim($ip, " \"\t\n\r\0\x0B");
  $ip_version = get_ip_version($ip_hex);

  if ($ip_version === 4)
  {
    // IPv4
    $ip_array = array();
    foreach (explode('.', $ip_hex) as $entry)
    {
      $ip_array[] = zeropad(dechex($entry));
    }
  }
  else if ($ip_version === 6)
  {
    // IPv6
    $ip_hex   = str_replace(':', '', Net_IPv6::uncompress($ip_hex, TRUE));
    $ip_array = str_split($ip_hex, 2);
  } else {
    return $ip;
  }
  $ip_hex = implode($separator, $ip_array);

  return $ip_hex;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function snmp2ipv6($ipv6_snmp)
{
  $ipv6 = explode('.',$ipv6_snmp);

  // Workaround stupid Microsoft bug in Windows 2008 -- this is fixed length!
  // < fenestro> "because whoever implemented this mib for Microsoft was ignorant of RFC 2578 section 7.7 (2)"
  if (count($ipv6) == 17 && $ipv6[0] == 16)
  {
    array_shift($ipv6);
  }

  for ($i = 0;$i <= 15;$i++) { $ipv6[$i] = zeropad(dechex($ipv6[$i])); }
  for ($i = 0;$i <= 15;$i+=2) { $ipv6_2[] = $ipv6[$i] . $ipv6[$i+1]; }

  return implode(':',$ipv6_2);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function ipv62snmp($ipv6)
{
  $ipv6_ex = explode(':',Net_IPv6::uncompress($ipv6));
  for ($i = 0;$i < 8;$i++) { $ipv6_ex[$i] = zeropad($ipv6_ex[$i],4); }
  $ipv6_ip = implode('',$ipv6_ex);
  for ($i = 0;$i < 32;$i+=2) $ipv6_split[] = hexdec(substr($ipv6_ip,$i,2));

  return implode('.',$ipv6_split);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function get_astext($asn)
{
  global $config, $cache;

  // Fetch pre-set AS text from config first
  if (isset($config['astext'][$asn]))
  {
    return $config['astext'][$asn];
  } else {
    // Not preconfigured, check cache before doing a new DNS request
    if (!isset($cache['astext'][$asn]))
    {
      $result = dns_get_record("AS$asn.asn.cymru.com", DNS_TXT);
      if (OBS_DEBUG > 1)
      {
        print_vars($result);
      }
      $txt = explode('|', $result[0]['txt']);
      $cache['astext'][$asn] = trim(str_replace('"', '', $txt[4]));
    }

    return $cache['astext'][$asn];
  }
}

/**
 * Use this function to write to the eventlog table
 *
 * @param string        $text      Message text
 * @param integer|array $device    Device array or device id
 * @param string        $type      Entity type (ie port, device, global)
 * @param integer       $reference Reference ID to current entity type
 * @param integer       $severity  Event severity (0 - 8)
 * @return integer                 Event DB id
 */
// TESTME needs unit testing
function log_event($text, $device = NULL, $type = NULL, $reference = NULL, $severity = 6)
{
  if (is_null($device) && is_null($type))
  {
    // Without device and type - is global events
    $type = 'global';
  } else {
    $type = strtolower($type);
  }

  // Global events not have device_id
  if ($type != 'global')
  {
    if (!is_array($device)) { $device = device_by_id_cache($device); }
    if ($device['ignore'] && $type != 'device') { return FALSE; } // Do not log events if device ignored
  }

  if ($type == 'port')
  {
    if (is_array($reference))
    {
      $port      = $reference;
      $reference = $port['port_id'];
    } else {
      $port = get_port_by_id_cache($reference);
    }
    if ($port['ignore']) { return FALSE; } // Do not log events if interface ignored
  }

  $severity = priority_string_to_numeric($severity); // Convert named severities to numeric
  if (($type == 'device' && $severity == 5) || isset($_SESSION['username'])) // Severity "Notification" additional log info about username or cli
  {
    $severity = ($severity == 6 ? 5 : $severity); // If severity default, change to notification
    if (isset($_SESSION['username']))
    {
      $text .= ' (by user: '.$_SESSION['username'].')';
    }
    else if (is_cli())
    {
      if (is_cron())
      {
        $text .= ' (by cron)';
      } else {
        $text .= ' (by console, user '  . $_SERVER['USER'] . ')';
      }
    }
  }

  $insert = array('device_id' => ($device['device_id'] ? $device['device_id'] : 0), // Currently db schema not allow NULL value for device_id
                  'entity_id' => (is_numeric($reference) ? $reference : array('NULL')),
                  'entity_type' => ($type ? $type : array('NULL')),
                  'timestamp' => array("NOW()"),
                  'severity' => $severity,
                  'message' => $text);

  $id = dbInsert($insert, 'eventlog');

  return $id;
}

// Parse string with emails. Return array with email (as key) and name (as value)
// DOCME needs phpdoc block
// MOVEME to includes/common.inc.php
function parse_email($emails)
{
  $result = array();

  if (is_string($emails))
  {
    $emails = preg_split('/[,;]\s{0,}/', $emails);
    foreach ($emails as $email)
    {
      $email = trim($email);
      if (preg_match('/^\s*' . OBS_PATTERN_EMAIL_LONG . '\s*$/iu', $email, $matches))
      {
        $email = trim($matches['email']);
        $name  = trim($matches['name'], " \t\n'\"");
        $result[$email] = (!empty($name) ? $name : NULL);
      }
      else if (strpos($email, "@") && !preg_match('/\s/', $email))
      {
        $result[$email] = NULL;
      } else {
        return FALSE;
      }
    }
  } else {
    // Return FALSE if input not string
    return FALSE;
  }
  return $result;
}

/**
 * Converting string to hex
 *
 * By Greg Winiarski of ditio.net
 * http://ditio.net/2008/11/04/php-string-to-hex-and-hex-to-string-functions/
 * We claim no copyright over this function and assume that it is free to use.
 *
 * @param string $string
 *
 * @return string
 */
// MOVEME to includes/common.inc.php
function str2hex($string)
{
  $hex='';
  for ($i=0; $i < strlen($string); $i++)
  {
    $hex .= dechex(ord($string[$i]));
  }
  return $hex;
}

/**
 * Converting hex to string
 *
 * By Greg Winiarski of ditio.net
 * http://ditio.net/2008/11/04/php-string-to-hex-and-hex-to-string-functions/
 * We claim no copyright over this function and assume that it is free to use.
 *
 * @param string $hex HEX string
 * @param string $eol EOL char, default is \n
 *
 * @return string
 */
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function hex2str($hex, $eol = "\n")
{
  $string='';

  $hex = str_replace(' ', '', $hex);
  for ($i = 0; $i < strlen($hex) - 1; $i += 2)
  {
    $hex_chr = $hex[$i].$hex[$i+1];
    if ($hex_chr == '00')
    {
      // 00 is EOL
      $string .= $eol;
    } else {
      $string .= chr(hexdec($hex_chr));
    }
  }

  return $string;
}

/**
 * Converting hex/dec coded ascii char to UTF-8 char
 *
 * Used together with snmp_fix_string()
 *
 * @param string $hex
 *
 * @return string
 */
function convert_ord_char($ord)
{
  if (is_array($ord))
  {
    $ord = array_shift($ord);
  }
  if (preg_match('/^(?:<|x)([0-9a-f]+)>?$/i', $ord, $match))
  {
    $ord = hexdec($match[1]);
  }
  else if (is_numeric($ord))
  {
    $ord = intval($ord);
  }
  else if (preg_match('/^[\p{L}]+$/u', $ord))
  {
    // Unicode chars
    return $ord;
  } else {
    // Non-printable chars
    $ord = ord($ord);
  }

  $no_bytes = 0;
  $byte = array();

  if ($ord < 128)
  {
    return chr($ord);
  }
  else if ($ord < 2048)
  {
    $no_bytes = 2;
  }
  else if ($ord < 65536)
  {
    $no_bytes = 3;
  }
  else if ($ord < 1114112)
  {
    $no_bytes = 4;
  } else {
    return;
  }
  switch($no_bytes)
  {
    case 2:
      $prefix = array(31, 192);
      break;
    case 3:
      $prefix = array(15, 224);
      break;
    case 4:
      $prefix = array(7, 240);
      break;
  }

  for ($i = 0; $i < $no_bytes; $i++)
  {
    $byte[$no_bytes - $i - 1] = (($ord & (63 * pow(2, 6 * $i))) / pow(2, 6 * $i)) & 63 | 128;
  }

  $byte[0] = ($byte[0] & $prefix[0]) | $prefix[1];

  $ret = '';
  for ($i = 0; $i < $no_bytes; $i++)
  {
    $ret .= chr($byte[$i]);
  }

  return $ret;
}

// Check if the supplied string is a hex string
// FIXME This is test for SNMP hex string, for just hex string use ctype_xdigit()
// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/snmp.inc.php
function isHexString($str)
{
  return (preg_match("/^[a-f0-9][a-f0-9](\ +[a-f0-9][a-f0-9])*$/is", trim($str)) ? TRUE : FALSE);
}

// Include all .inc.php files in $dir
// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function include_dir($dir, $regex = "")
{
  global $device, $config, $valid;

  if ($regex == "")
  {
    $regex = "/\.inc\.php$/";
  }

  if ($handle = opendir($config['install_dir'] . '/' . $dir))
  {
    while (false !== ($file = readdir($handle)))
    {
      if (filetype($config['install_dir'] . '/' . $dir . '/' . $file) == 'file' && preg_match($regex, $file))
      {
        print_debug("Including: " . $config['install_dir'] . '/' . $dir . '/' . $file);

        include($config['install_dir'] . '/' . $dir . '/' . $file);
      }
    }

    closedir($handle);
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function is_port_valid($port, $device)
{
  global $config;
  $valid = TRUE;

  if (isset($port['ifOperStatus']) && strlen($port['ifOperStatus']) && // Currently skiped empty ifOperStatus for exclude false positives
      !in_array($port['ifOperStatus'], array('testing', 'dormant', 'down', 'lowerLayerDown', 'unknown', 'up', 'monitoring')))
  {
    // See http://tools.cisco.com/Support/SNMP/do/BrowseOID.do?objectInput=ifOperStatus
    $valid = FALSE;
    print_debug("ignored (by ifOperStatus = notPresent or invalid value).");
  }

  $ports_skip_ifType = isset($config['os'][$device['os']]['ports_skip_ifType']) && $config['os'][$device['os']]['ports_skip_ifType'];
  if ($valid && !isset($port['ifType']) && !$ports_skip_ifType)
  {
    /* Some devices (ie D-Link) report ports without any usefull info, example:
    [74] => Array
        (
            [ifName] => po22
            [ifInMulticastPkts] => 0
            [ifInBroadcastPkts] => 0
            [ifOutMulticastPkts] => 0
            [ifOutBroadcastPkts] => 0
            [ifLinkUpDownTrapEnable] => enabled
            [ifHighSpeed] => 0
            [ifPromiscuousMode] => false
            [ifConnectorPresent] => false
            [ifAlias] => po22
            [ifCounterDiscontinuityTime] => 0:0:00:00.00
        )
    */
    $valid = FALSE;
    print_debug("ignored (by empty ifType).");
  }

  if ($port['ifDescr'] === '' && $config['os'][$device['os']]['ifType_ifDescr'] && $port['ifIndex'])
  {
    // This happen on some liebert UPS devices
    $type = rewrite_iftype($port['ifType']);
    if ($type)
    {
      $port['ifDescr'] = $type . ' ' . $port['ifIndex'];
    }
  }

  $if = ($config['os'][$device['os']]['ifname'] ? $port['ifName'] : $port['ifDescr']);

  if ($valid && is_array($config['bad_if']))
  {
    foreach ($config['bad_if'] as $bi)
    {
      if (stripos($port['ifDescr'], $bi) !== FALSE)
      {
        $valid = FALSE;
        print_debug("ignored (by ifDescr): ".$port['ifDescr']." [ $bi ]");
        break;
      }
      elseif (stripos($port['ifName'], $bi) !== FALSE)
      {
        $valid = FALSE;
        print_debug("ignored (by ifName): ".$port['ifName']." [ $bi ]");
        break;
      }
    }
  }

  if ($valid && is_array($config['bad_ifalias_regexp']))
  {
    foreach ($config['bad_ifalias_regexp'] as $bi)
    {
      if (preg_match($bi . 'i', $port['ifAlias']))
      {
        $valid = FALSE;
        print_debug("ignored (by ifAlias): ".$port['ifAlias']." [ $bi ]");
        break;
      }
    }
  }

  if ($valid && is_array($config['bad_if_regexp']))
  {
    foreach ($config['bad_if_regexp'] as $bi)
    {
      if (preg_match($bi . 'i', $port['ifName']))
      {
        $valid = FALSE;
        print_debug("ignored (by ifName regexp): ".$port['ifName']." [ $bi ]");
        break;
      }
      elseif (preg_match($bi . 'i', $port['ifDescr']))
      {
        $valid = FALSE;
        print_debug("ignored (by ifDescr regexp): ".$port['ifDescr']." [ $bi ]");
        break;
      }
    }
  }

  if ($valid && is_array($config['bad_iftype']))
  {
    foreach ($config['bad_iftype'] as $bi)
    {
      if (strpos($port['ifType'], $bi) !== FALSE)
      {
        $valid = FALSE;
        print_debug("ignored (by ifType): ".$port['ifType']." [ $bi ]");
        break;
      }
    }
  }
  if ($valid && empty($port['ifDescr']) && empty($port['ifName']))
  {
    $valid = FALSE;
    print_debug("ignored (by empty ifDescr and ifName).");
  }
  if ($valid && $device['os'] == 'catos' && strstr($if, "vlan")) { $valid = FALSE; }

  return $valid;
}

/**
 * Validate BGP peer
 *
 * @param array $peer BGP peer array from discovery or polling process
 * @param array $device Common device array
 * @return boolean TRUE if peer array valid
 */
function is_bgp_peer_valid($peer, $device)
{
  $valid = TRUE;

  if (isset($peer['admin_status']) && empty($peer['admin_status']))
  {
    $valid = FALSE;
    print_debug("Peer ignored (by empty Admin Status).");
  }

  if ($valid && !(is_numeric($peer['as']) && $peer['as'] != 0))
  {
    $valid = FALSE;
    print_debug("Peer ignored (by invalid AS number '".$peer['as']."').");
  }

  if ($valid && !get_ip_version($peer['ip']))
  {
    $valid = FALSE;
    print_debug("Peer ignored (by invalid Remote IP '".$peer['ip']."').");
  }

  return $valid;
}

/**
 * Detect is BGP AS number in private range, see:
 * https://tools.ietf.org/html/rfc6996
 * https://tools.ietf.org/html/rfc7300
 *
 * @param string|int $as AS number
 * @return boolean TRUE if AS number in private range
 */
function is_bgp_as_private($as)
{
  $as = bgp_asdot_to_asplain($as); // Convert ASdot to ASplain

  // Note 65535 and 5294967295 not really Private ASNs,
  // this is Reserved for use by Well-known Communities
  $private = ($as >= 64512      && $as <= 65535) ||    // 16-bit private ASn
             ($as >= 4200000000 && $as <= 5294967295); // 32-bit private ASn

  return $private;
}

/**
 * Convert AS number from asplain to asdot format (for 32bit ASn).
 *
 * @param string|int $as AS number in plain or dot format
 * @return string AS number in dot format (for 32bit ASn)
 */
function bgp_asplain_to_asdot($as)
{
  if (strpos($as, '.') || // Already asdot format
      ($as < 65536))      // 16bit ASn no need to formatting
  {
    return $as;
  }

  $as2 = $as % 65536;
  $as1 = ($as - $as2) / 65536;

  return intval($as1) . '.' . intval($as2);
}

/**
 * Convert AS number from asdot to asplain format (for 32bit ASn).
 *
 * @param string|int $as AS number in plain or dot format
 * @return string AS number in plain format (for 32bit ASn)
 */
function bgp_asdot_to_asplain($as)
{
  if (strpos($as, '.') === FALSE)   // Already asplain format
  {
    return $as;
  }

  list($as1, $as2) = explode('.', $as, 2);
  $as = $as1 * 65536 + $as2;

  return "$as";
}

/**
 * Convert BGP peer index to vendor MIB specific entries
 *
 * @param array $peer Array with walked peer oids
 * @param string $index Peer index
 * @param string $mib MIB name
 */
function parse_bgp_peer_index(&$peer, $index, $mib = 'BGP4V2-MIB')
{
  $address_types = $GLOBALS['config']['mibs']['INET-ADDRESS-MIB']['rewrite']['InetAddressType'];
  $index_parts   = explode('.', $index);
  switch ($mib)
  {
    case 'BGP4-MIB':
      // bgpPeerRemoteAddr
      if (get_ip_version($index))
      {
        $peer['bgpPeerRemoteAddr'] = $index;
      }
      break;

    case 'ARISTA-BGP4V2-MIB':
      // 1. aristaBgp4V2PeerInstance
      $peer['aristaBgp4V2PeerInstance'] = array_shift($index_parts);
      // 2. aristaBgp4V2PeerRemoteAddrType
      $peer_addr_type = array_shift($index_parts);
      if (strlen($peer['aristaBgp4V2PeerRemoteAddrType']) == 0)
      {
        $peer['aristaBgp4V2PeerRemoteAddrType'] = $peer_addr_type;
      }
      if (isset($address_types[$peer['aristaBgp4V2PeerRemoteAddrType']]))
      {
        $peer['aristaBgp4V2PeerRemoteAddrType'] = $address_types[$peer['aristaBgp4V2PeerRemoteAddrType']];
      }
      // 3. length of the IP address
      $ip_len = array_shift($index_parts);
      // 4. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 5. aristaBgp4V2PeerRemoteAddr
      $peer_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $peer_ip = snmp2ipv6($peer_ip);
      }
      if ($peer_addr_type = get_ip_version($peer_ip))
      {
        $peer['aristaBgp4V2PeerRemoteAddr']     = $peer_ip;
        $peer['aristaBgp4V2PeerRemoteAddrType'] = 'ipv' . $peer_addr_type; // FIXME. not sure, but seems as Arista use only ipv4/ipv6 for afi
      }
      break;

    case 'BGP4V2-MIB':
    case 'FOUNDRY-BGP4V2-MIB': // BGP4V2-MIB draft
      // 1. bgp4V2PeerInstance
      $peer['bgp4V2PeerInstance'] = array_shift($index_parts);
      // 2. bgp4V2PeerLocalAddrType
      $local_addr_type = array_shift($index_parts);
      if (strlen($peer['bgp4V2PeerLocalAddrType']) == 0)
      {
        $peer['bgp4V2PeerLocalAddrType'] = $local_addr_type;
      }
      if (isset($address_types[$peer['bgp4V2PeerLocalAddrType']]))
      {
        $peer['bgp4V2PeerLocalAddrType'] = $address_types[$peer['bgp4V2PeerLocalAddrType']];
      }
      // 3. length of the local IP address
      $ip_len = array_shift($index_parts);
      // 4. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 5. bgp4V2PeerLocalAddr
      $local_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $local_ip = snmp2ipv6($local_ip);
      }
      if (get_ip_version($local_ip))
      {
        $peer['bgp4V2PeerLocalAddr'] = $local_ip;
      }

      // Get second part of index
      $index_parts = array_slice($index_parts, $ip_len);
      $peer_addr_type = array_shift($index_parts);
      if (strlen($peer['bgp4V2PeerRemoteAddrType']) == 0)
      {
        $peer['bgp4V2PeerRemoteAddrType'] = $peer_addr_type;
      }
      if (isset($address_types[$peer['bgp4V2PeerRemoteAddrType']]))
      {
        $peer['bgp4V2PeerRemoteAddrType'] = $address_types[$peer['bgp4V2PeerRemoteAddrType']];
      }
      // 6. length of the IP address
      $ip_len = array_shift($index_parts);
      // 7. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 8. bgp4V2PeerRemoteAddr
      $peer_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $peer_ip = snmp2ipv6($peer_ip);
      }
      if ($peer_addr_type = get_ip_version($peer_ip))
      {
        $peer['bgp4V2PeerRemoteAddr']     = $peer_ip;
        $peer['bgp4V2PeerRemoteAddrType'] = 'ipv' . $peer_addr_type;
      }
      break;

    case 'BGP4-V2-MIB-JUNIPER':
      // 1. jnxBgpM2PeerRoutingInstance
      $peer['jnxBgpM2PeerRoutingInstance'] = array_shift($index_parts);
      // 2. jnxBgpM2PeerLocalAddrType
      $local_addr_type = array_shift($index_parts);
      if (strlen($peer['jnxBgpM2PeerLocalAddrType']) == 0)
      {
        $peer['jnxBgpM2PeerLocalAddrType'] = $local_addr_type;
      }
      if (isset($address_types[$peer['jnxBgpM2PeerLocalAddrType']]))
      {
        $peer['jnxBgpM2PeerLocalAddrType'] = $address_types[$peer['jnxBgpM2PeerLocalAddrType']];
      }
      // 3. length of the local IP address
      $ip_len = (strstr($peer['jnxBgpM2PeerLocalAddrType'], 'ipv6') ? 16 : 4);
      // 4. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 5. jnxBgpM2PeerLocalAddr
      $local_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $local_ip = snmp2ipv6($local_ip);
      }
      if (get_ip_version($local_ip))
      {
        $peer['jnxBgpM2PeerLocalAddr'] = $local_ip;
      }

      // Get second part of index
      $index_parts = array_slice($index_parts, $ip_len);
      // 6. jnxBgpM2PeerRemoteAddrType
      $peer_addr_type = array_shift($index_parts);
      if (strlen($peer['jnxBgpM2PeerRemoteAddrType']) == 0)
      {
        $peer['jnxBgpM2PeerRemoteAddrType'] = $peer_addr_type;
      }
      if (isset($address_types[$peer['jnxBgpM2PeerRemoteAddrType']]))
      {
        $peer['jnxBgpM2PeerRemoteAddrType'] = $address_types[$peer['jnxBgpM2PeerRemoteAddrType']];
      }
      // 7. length of the remote IP address
      $ip_len = (strstr($peer['jnxBgpM2PeerRemoteAddrType'], 'ipv6') ? 16 : 4);
      // 8. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 9. jnxBgpM2PeerRemoteAddr
      $peer_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $peer_ip = snmp2ipv6($peer_ip);
      }
      if (get_ip_version($peer_ip))
      {
        $peer['jnxBgpM2PeerRemoteAddr'] = $peer_ip;
      }
      break;

    case 'FORCE10-BGP4-V2-MIB':
      // 1. f10BgpM2PeerInstance
      $peer['f10BgpM2PeerInstance'] = array_shift($index_parts);
      // 2. f10BgpM2PeerLocalAddrType
      $local_addr_type = array_shift($index_parts);
      if (strlen($peer['f10BgpM2PeerLocalAddrType']) == 0)
      {
        $peer['f10BgpM2PeerLocalAddrType'] = $local_addr_type;
      }
      if (isset($address_types[$peer['f10BgpM2PeerLocalAddrType']]))
      {
        $peer['f10BgpM2PeerLocalAddrType'] = $address_types[$peer['f10BgpM2PeerLocalAddrType']];
      }
      // 3. length of the local IP address
      $ip_len = (strstr($peer['f10BgpM2PeerLocalAddrType'], 'ipv6') ? 16 : 4);
      // 4. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 5. f10BgpM2PeerLocalAddr
      $local_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $local_ip = snmp2ipv6($local_ip);
      }
      if (get_ip_version($local_ip))
      {
        $peer['f10BgpM2PeerLocalAddr'] = $local_ip;
      }

      // Get second part of index
      $index_parts = array_slice($index_parts, $ip_len);
      // 6. f10BgpM2PeerRemoteAddrType
      $peer_addr_type = array_shift($index_parts);
      if (strlen($peer['f10BgpM2PeerRemoteAddrType']) == 0)
      {
        $peer['f10BgpM2PeerRemoteAddrType'] = $peer_addr_type;
      }
      if (isset($address_types[$peer['f10BgpM2PeerRemoteAddrType']]))
      {
        $peer['f10BgpM2PeerRemoteAddrType'] = $address_types[$peer['f10BgpM2PeerRemoteAddrType']];
      }
      // 7. length of the remote IP address
      $ip_len = (strstr($peer['f10BgpM2PeerRemoteAddrType'], 'ipv6') ? 16 : 4);
      // 8. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 9. f10BgpM2PeerRemoteAddr
      $peer_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $peer_ip = snmp2ipv6($peer_ip);
      }
      if (get_ip_version($peer_ip))
      {
        $peer['f10BgpM2PeerRemoteAddr'] = $peer_ip;
      }
      break;

  }

}

# Parse CSV files with or without header, and return a multidimensional array
// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function parse_csv($content, $has_header = 1, $separator = ",")
{
  $lines = explode("\n", $content);
  $result = array();

  # If the CSV file has a header, load up the titles into $headers
  if ($has_header)
  {
    $headcount = 1;
    $header = array_shift($lines);
    foreach (explode($separator,$header) as $heading)
    {
      if (trim($heading) != "")
      {
        $headers[$headcount] = trim($heading);
        $headcount++;
      }
    }
  }

  # Process every line
  foreach ($lines as $line)
  {
    if ($line != "")
    {
      $entrycount = 1;
      foreach (explode($separator,$line) as $entry)
      {
        # If we use header, place the value inside the named array entry
        # Otherwise, just stuff it in numbered fields in the array
        if (trim($entry) != "")
        {
          if ($has_header)
          {
            $line_array[$headers[$entrycount]] = trim($entry);
          } else {
            $line_array[] = trim($entry);
          }
        }
        $entrycount++;
      }

      # Add resulting line array to final result
      $result[] = $line_array; unset($line_array);
    }
  }

  return $result;
}

function get_defined_settings()
{
  $config = [];
  include($GLOBALS['config']['install_dir'] . "/config.php");

  return $config;
}

function get_default_settings()
{
  $config = [];
  include($GLOBALS['config']['install_dir'] . "/includes/defaults.inc.php");

  return $config;
}

// Load configuration from SQL into supplied variable (pass by reference!)
function load_sqlconfig(&$config)
{
  $config_defined = get_defined_settings(); // defined in config.php

  // Override some whitelisted definitions from config.php
  foreach ($config_defined as $key => $definition)
  {
    if (in_array($key, $config['definitions_whitelist']) && version_compare(PHP_VERSION, '5.3.0') >= 0 &&
        is_array($definition) && is_array($config[$key]))
    {
      /* Fix mib definitions for dumb users, who copied old defaults.php
         where mibs was just MIB => 1,
         This definition should be array */
      // Fetch first element and validate that this is array
      if ($key == 'mibs' && !is_array(array_shift(array_values($definition)))) { continue; }

      $config[$key] = array_replace_recursive($config[$key], $definition);
    }
  }

  foreach (dbFetchRows("SELECT * FROM `config`") as $item)
  {
    // Convert boo|bee|baa config value into $config['boo']['bee']['baa']
    $tree = explode('|', $item['config_key']);

    //if (array_key_exists($tree[0], $config_defined)) { continue; } // This complete skip option if first level key defined in $config

    // Unfortunately, I don't know of a better way to do this...
    // Perhaps using array_map() ? Unclear... hacky. :[
    // FIXME use a loop with references! (cf. nested location menu)
    switch (count($tree))
    {
      case 1:
        //if (isset($config_defined[$tree[0]])) { continue; } // Note, false for null values
        if (array_key_exists($tree[0], $config_defined)) { break; }
        $config[$tree[0]] = unserialize($item['config_value']);
        break;
      case 2:
        if (isset($config_defined[$tree[0]][$tree[1]])) { break; } // Note, false for null values
        $config[$tree[0]][$tree[1]] = unserialize($item['config_value']);
        break;
      case 3:
        if (isset($config_defined[$tree[0]][$tree[1]][$tree[2]])) { break; } // Note, false for null values
        $config[$tree[0]][$tree[1]][$tree[2]] = unserialize($item['config_value']);
        break;
      case 4:
        if (isset($config_defined[$tree[0]][$tree[1]][$tree[2]][$tree[3]])) { break; } // Note, false for null values
        $config[$tree[0]][$tree[1]][$tree[2]][$tree[3]] = unserialize($item['config_value']);
        break;
      case 5:
        if (isset($config_defined[$tree[0]][$tree[1]][$tree[2]][$tree[3]][$tree[4]])) { break; } // Note, false for null values
        $config[$tree[0]][$tree[1]][$tree[2]][$tree[3]][$tree[4]] = unserialize($item['config_value']);
        break;
      default:
        print_error("Too many array levels for SQL configuration parser!");
    }
  }
}

function isset_array_key($key, &$array, $split = '|')
{
  // Convert boo|bee|baa key into $array['boo']['bee']['baa']
  $tree = explode($split, $key);

  switch (count($tree))
  {
    case 1:
      //if (isset($array[$tree[0]])) { continue; } // Note, false for null values
      return array_key_exists($tree[0], $array);
      break;
    case 2:
      //if (isset($array[$tree[0]][$tree[1]])) { continue; } // Note, false for null values
      return isset($array[$tree[0]]) && array_key_exists($tree[1], $array[$tree[0]]);
      break;
    case 3:
      //if (isset($array[$tree[0]][$tree[1]][$tree[2]])) { continue; } // Note, false for null values
      return isset($array[$tree[0]][$tree[1]]) && array_key_exists($tree[2], $array[$tree[0]][$tree[1]]);
      break;
    case 4:
      //if (isset($array[$tree[0]][$tree[1]][$tree[2]][$tree[3]])) { continue; } // Note, false for null values
      return isset($array[$tree[0]][$tree[1]][$tree[2]]) && array_key_exists($tree[3], $array[$tree[0]][$tree[1]][$tree[2]]);
      break;
    case 5:
      //if (isset($array[$tree[0]][$tree[1]][$tree[2]][$tree[3]][$tree[4]])) { continue; } // Note, false for null values
      return isset($array[$tree[0]][$tree[1]][$tree[2]][$tree[3]]) && array_key_exists($tree[4], $array[$tree[0]][$tree[1]][$tree[2]][$tree[3]]);
      break;
    default:
      print_error("Too many array levels for array");
  }

  return FALSE;
}

function set_sql_config($key, $value, $force = TRUE)
{

  if (!$force && // Currently configuration store forced also if not exist in defaults
      !isset_array_key($key, $GLOBALS['config']))
  {
    print_error("Not exist config key ($key).");
    return FALSE;
  }

  $s_value = serialize($value); // in db we store serialized config value

  $sql = 'SELECT * FROM `config` WHERE `config_key` = ? LIMIT 1';
  if ($in_db = dbFetchRow($sql, [$key]))
  {
    // Exist, compare? and update
    if ($s_value != $in_db[$key])
    {
      dbUpdate(['config_value' => $s_value], 'config', '`config_key` = ?', [$key]);
    }
  }
  else {
    // Insert new
    dbInsert(array('config_key' => $key, 'config_value' => $s_value), 'config');
  }

  return TRUE;
}

function del_sql_config($key)
{
  if (dbExist('config', '`config_key` = ?', [$key]))
  {
    dbDelete('config', '`config_key` = ?', [$key]);
  }

  return TRUE;
}

// Convert SI scales to scalar scale. Example return:
// si_to_scale('milli');    // return 0.001
// si_to_scale('femto', 8); // return 1.0E-23
// si_to_scale('-2');       // return 0.01
// DOCME needs phpdoc block
// MOVEME to includes/common.inc.php
function si_to_scale($si = 'units', $precision = NULL)
{
  // See all scales here: http://tools.cisco.com/Support/SNMP/do/BrowseOID.do?local=en&translate=Translate&typeName=SensorDataScale
  $si       = strtolower($si);
  $si_array = array('yocto' => -24, 'zepto' => -21, 'atto'  => -18,
                    'femto' => -15, 'pico'  => -12, 'nano'  => -9,
                    'micro' => -6,  'milli' => -3,  'centi' => -2,
                    'deci'  => -1,  'units' => 0,   'deca'  => 1,
                    'hecto' => 2,   'kilo'  => 3,   'mega'  => 6,
                    'giga'  => 9,   'tera'  => 12,  'peta'  => 15,
                    'exa'   => 18,  'zetta' => 21,  'yotta' => 24);
  $exp = 0;
  if (isset($si_array[$si]))
  {
    $exp = $si_array[$si];
  }
  else if (is_numeric($si))
  {
    $exp = (int)$si;
  }

  if (is_numeric($precision) && $precision > 0)
  {
    /**
     * NOTES. For EntitySensorPrecision:
     *  If an object of this type contains a value in the range 1 to 9, it represents the number of decimal places in the
     *  fractional part of an associated EntitySensorValue fixed-point number.
     *  If an object of this type contains a value in the range -8 to -1, it represents the number of accurate digits in the
     *  associated EntitySensorValue fixed-point number.
     */
    $exp -= (int)$precision;
  }

  $scale = pow(10, $exp);

  return $scale;
}

/**
 * Compare variables considering epsilon for float numbers
 * returns: 0 - variables same, 1 - $a greater than $b, -1 - $a less than $b
 *
 * @param mixed $a First compare number
 * @param mixed $b Second compare number
 * @param float $epsilon
 *
 * @return integer $compare
 */
// MOVEME to includes/common.inc.php
function float_cmp($a, $b, $epsilon = NULL)
{
  $epsilon = (is_numeric($epsilon) ? abs((float)$epsilon) : 0.00001); // Default epsilon for float compare
  $compare = FALSE;
  $both    = 0;
  // Convert to float if possible
  if (is_numeric($a)) { $a = (float)$a; $both++; }
  if (is_numeric($b)) { $b = (float)$b; $both++; }

  if ($both === 2)
  {
    // Compare numeric variables as float numbers
    // Based on compare logic from http://floating-point-gui.de/errors/comparison/
    if ($a === $b)
    {
      $compare = 0; // Variables same
      $test = 0;
    } else {
      $diff = abs($a - $b);
      //$pow_epsilon = pow($epsilon, 2);
      if ($a == 0 || $b == 0)
      {
        // Around zero
        $test    = $diff;
        $epsilon = pow($epsilon, 2);
        if ($test < $epsilon) { $compare = 0; }
      } else {
        // Note, still exist issue with numbers around zero (ie: -0.00000001, 0.00000002)
        $test = $diff / min(abs($a) + abs($b), PHP_INT_MAX);
        if ($test < $epsilon) { $compare = 0; }
      }
    }

    if (OBS_DEBUG > 1)
    {
      print_message('Compare float numbers: "'.$a.'" with "'.$b.'", epsilon: "'.$epsilon.'", comparision: "'.$test.' < '.$epsilon.'", numbers: '.($compare === 0 ? 'SAME' : 'DIFFERENT'));
    }
  } else {
    // All other compare as usual
    if ($a === $b)
    {
      $compare = 0; // Variables same
    }
  }
  if ($compare === FALSE)
  {
    // Compare if variables not same
    if ($a > $b)
    {
      $compare = 1;  // $a greater than $b
    } else {
      $compare = -1; // $a less than $b
    }
  }

  return $compare;
}

/**
 * Add integer numbers.
 * This function better to use with big Counter64 numbers
 *
 * @param int|string $a The first number
 * @param int|string $b The second number
 * @return string       A number representing the sum of the arguments.
 */
function int_add($a, $b)
{
  switch (OBS_MATH)
  {
    case 'gmp':
      // Convert values to string
      $a = gmp_init_float($a);
      $b = gmp_init_float($b);
      // Better to use GMP extension, for more correct operations with big numbers
      // $a = "18446742978492891134"; $b = "0"; $sum = gmp_add($a, $b); echo gmp_strval($sum) . "\n"; // Result: 18446742978492891134
      // $a = "18446742978492891134"; $b = "0"; $sum = $a + $b; printf("%.0f\n", $sum);               // Result: 18446742978492891136
      $sum = gmp_add($a, $b);
      $sum = gmp_strval($sum); // Convert GMP number to string
      print_debug("GMP ADD: $a + $b = $sum");
      break;
    case 'bc':
      // Convert values to string
      $a = strval($a);
      $b = strval($b);
      $sum = bcadd($a, $b);
      print_debug("BC ADD: $a + $b = $sum");
      break;
    default:
      // Fallback to php math
      $sum = $a + $b;
      // Convert this values to int string, for prevent rrd update error with big Counter64 numbers,
      // see: http://jira.observium.org/browse/OBSERVIUM-1749
      $sum = sprintf("%.0f", $sum);
      print_debug("PHP ADD: $a + $b = $sum");
  }

  return $sum;
}

/**
 * Subtract integer numbers.
 * This function better to use with big Counter64 numbers
 *
 * @param int|string $a The first number
 * @param int|string $b The second number
 * @return string       A number representing the subtract of the arguments.
 */
function int_sub($a, $b)
{
  switch (OBS_MATH)
  {
    case 'gmp':
      // Convert values to string
      $a = gmp_init_float($a);
      $b = gmp_init_float($b);
      $sub = gmp_sub($a, $b);
      $sub = gmp_strval($sub); // Convert GMP number to string
      print_debug("GMP SUB: $a - $b = $sub");
      break;
    case 'bc':
      // Convert values to string
      $a = strval($a);
      $b = strval($b);
      $sub = bcsub($a, $b);
      print_debug("BC SUB: $a - $b = $sub");
      break;
    default:
      // Fallback to php math
      $sub = $a - $b;
      // Convert this values to int string, for prevent rrd update error with big Counter64 numbers,
      // see: http://jira.observium.org/browse/OBSERVIUM-1749
      $sub = sprintf("%.0f", $sub);
      print_debug("PHP SUB: $a - $b = $sub");
  }

  return $sub;
}

/**
 * GMP have troubles with float number math
 *
 * php > $sum = 1111111111111111111111111.1; echo sprintf("%.0f", $sum)."\n"; echo sprintf("%d", $sum)."\n"; echo strval($sum)."\n"; echo $sum;
1111111111111111092469760
8375319363669983232
1.1111111111111E+24
1.1111111111111E+24
php > $sum = "1111111111111111111111111.1"; echo sprintf("%.0f", $sum)."\n"; echo sprintf("%d", $sum)."\n"; echo strval($sum)."\n"; echo $sum;
1111111111111111092469760
9223372036854775807
1111111111111111111111111.1
1111111111111111111111111.1
 */
function gmp_init_float($value)
{
  if (is_int($value))
  {
    return $value;
  }
  if (is_float($value))
  {
    return sprintf("%.0f", $value);
  }
  if (strpos($value, '.') !== FALSE)
  {
    // Return int part of string
    list($value) = explode('.', $value);
    return $value;
  }

  return "$value";
}

// Translate syslog priorities from string to numbers
// ie: ('emerg','alert','crit','err','warning','notice') >> ('0', '1', '2', '3', '4', '5')
// Note, this is safe function, for unknown data return 15
// DOCME needs phpdoc block
function priority_string_to_numeric($value)
{
  $priority = 15; // Default priority for unknown data
  if (!is_numeric($value))
  {
    foreach ($GLOBALS['config']['syslog']['priorities'] as $pri => $entry)
    {
      if (stripos($entry['name'], substr($value, 0, 3)) === 0) { $priority = $pri; break; }
    }
  }
  else if ($value == (int)$value && $value >= 0 && $value < 16)
  {
    $priority = (int)$value;
  }

  return $priority;
}

/**
 * Translate syslog facilities from string to numeric
 *
 */
function facility_string_to_numeric($facility)
{
  if (!is_numeric($facility))
  {
    foreach ($GLOBALS['config']['syslog']['facilities'] as $f => $entry)
    {
      if ($entry['name'] == $facility)
      {
        $facility = $f;
        break;
      }
    }
  }
  else if ($facility == (int)$facility && $facility >= 0 && $facility <= 23)
  {
    $facility = (int)$facility;
  }

  return $facility;
}

function array_merge_indexed(&$array1, &$array2)
{
  $merged = $array1;
  //print_vars($merged);

  foreach ($array2 as $key => &$value)
  {
    if (is_array($value) && isset($merged[$key]) && is_array($merged[$key]))
    {
      $merged[$key] = array_merge_indexed($merged[$key], $value);
    } else {
      $merged[$key] = $value;
    }
  }

  //print_vars($merged);
  return $merged;
}

// Merge 2 arrays by their index, ie:
//  Array( [1] => [TestCase] = '1' ) + Array( [1] => [Bananas] = 'Yes )
// becomes
//  Array( [1] => [TestCase] = '1', [Bananas] = 'Yes' )
//
// array_merge_recursive() only works for string keys, not numeric as we get from snmp functions.
//
// Accepts infinite parameters.
//
// Currently not used. Does not cope well with multilevel arrays.
// DOCME needs phpdoc block
// MOVEME to includes/common.inc.php
/*
function array_merge_indexed()
{
  $array = array();

  foreach (func_get_args() as $array2)
  {
    if (count($array2) == 0) continue; // Skip for loop for empty array, infinite loop ahead.
    for ($i = 0; $i <= count($array2); $i++)
    {
      foreach (array_keys($array2[$i]) as $key)
      {
        $array[$i][$key] = $array2[$i][$key];
      }
    }
  }

  return $array;
}
*/

// DOCME needs phpdoc block
// TESTME needs unit testing
function print_cli_heading($contents, $level = 2)
{
  if (OBS_QUIET || !is_cli()) { return; } // Silent exit if not cli or quiet

//  $tl = html_entity_decode('&#x2554;', ENT_NOQUOTES, 'UTF-8'); // top left corner
//  $tr = html_entity_decode('&#x2557;', ENT_NOQUOTES, 'UTF-8'); // top right corner
//  $bl = html_entity_decode('&#x255a;', ENT_NOQUOTES, 'UTF-8'); // bottom left corner
//  $br = html_entity_decode('&#x255d;', ENT_NOQUOTES, 'UTF-8'); // bottom right corner
//  $v = html_entity_decode('&#x2551;', ENT_NOQUOTES, 'UTF-8');  // vertical wall
//  $h = html_entity_decode('&#x2550;', ENT_NOQUOTES, 'UTF-8');  // horizontal wall

//  print_message($tl . str_repeat($h, strlen($contents)+2)  . $tr . "\n" .
//                $v  . ' '.$contents.' '   . $v  . "\n" .
//                $bl . str_repeat($h, strlen($contents)+2)  . $br . "\n", 'color');

  $level_colours = array('0' => '%W', '1' => '%g', '2' => '%c' , '3' => '%p');

  //print_message(str_repeat("  ", $level). $level_colours[$level]."#####  %W". $contents ."%n\n", 'color');
  print_message($level_colours[$level]."#####  %W". $contents .$level_colours[$level]."  #####%n\n", 'color');
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function print_cli_data($field, $data = NULL, $level = 2)
{
  if (OBS_QUIET || !is_cli()) { return; } // Silent exit if not cli or quiet

  //$level_colours = array('0' => '%W', '1' => '%g', '2' => '%c' , '3' => '%p');

  //print_cli(str_repeat("  ", $level) . $level_colours[$level]."  o %W".str_pad($field, 20). "%n ");
  //print_cli($level_colours[$level]." o %W".str_pad($field, 20). "%n "); // strlen == 24
  print_cli_data_field($field, $level);

  $field_len = 0;
  $max_len = 110;

  $lines = explode("\n", $data);

  foreach ($lines as $line)
  {
    $len = strlen($line) + 24;
    if ($len > $max_len)
    {
      $len = $field_len;
      $data = explode(" ", $line);
      foreach ($data as $datum)
      {
        $len = $len + strlen($datum);
        if ($len > $max_len)
        {
          $len = strlen($datum);
          //$datum = "\n". str_repeat(" ", 26+($level * 2)). $datum;
          $datum = "\n". str_repeat(" ", 24). $datum;
        } else {
          $datum .= ' ';
        }
        print_cli($datum);
      }
    } else {
      $datum = str_repeat(" ", $field_len). $line;
      print_cli($datum);
    }
    $field_len = 24;
    print_cli(PHP_EOL);
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function print_cli_data_field($field, $level = 2)
{
  if (OBS_QUIET || !is_cli()) { return; } // Silent exit if not cli or quiet

  $level_colours = array('0' => '%W', '1' => '%g', '2' => '%c' , '3' => '%p', '4' => '%y');

  // print_cli(str_repeat("  ", $level) . $level_colours[$level]."  o %W".str_pad($field, 20). "%n ");
  print_cli($level_colours[$level]." o %W".str_pad($field, 20). "%n ");
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function print_cli_table($table_rows, $table_header = array(), $descr = NULL, $options = array())
{
  // FIXME, probably need ability to view this tables in WUI?!
  if (OBS_QUIET || !is_cli()) { return; } // Silent exit if not cli or quiet

  if (!is_array($table_rows)) { print_debug("print_cli_table() argument $table_rows should be an array. Please report this error to developers."); return; }

  if (!cli_is_piped() || OBS_DEBUG)
  {
    $count_rows   = count($table_rows);
    if ($count_rows == 0) { return; }

    if (strlen($descr))
    {
      print_cli_data($descr, '', 3);
    }

    // Init table and renderer
    $table = new \cli\Table();
    cli\Colors::enable(TRUE);

    // Set default maximum width globally
    if (!isset($options['max-table-width']))
    {
      $options['max-table-width'] = 240;
      //$options['max-table-width']  = TRUE;
    }
    // WARNING, min-column-width not worked in cli Class, I wait when issue will fixed
    //$options['min-column-width'] = 30;
    if (!empty($options))
    {
      $renderer = new cli\Table\Ascii;
      if (isset($options['max-table-width']))
      {
        if ($options['max-table-width'] === TRUE)
        {
          // Set maximum table width as available columns in terminal
          $options['max-table-width'] = cli\Shell::columns();
        }
        if (is_numeric($options['max-table-width']))
        {
          $renderer->setConstraintWidth($options['max-table-width']);
        }
      }
      if (isset($options['min-column-width']))
      {
        $cols = array();
        foreach (current($table_rows) as $col)
        {
          $cols[] = $options['min-column-width'];
        }
        //var_dump($cols);
        $renderer->setWidths($cols);
      }
      $table->setRenderer($renderer);
    }

    $count_header = count($table_header);
    if ($count_header)
    {
      $table->setHeaders($table_header);
    }
    $table->setRows($table_rows);
    $table->display();
    echo(PHP_EOL);
  } else {
    print_cli_data("Notice", "Table output suppressed due to piped output.".PHP_EOL);
  }
}

/**
 * Prints Observium banner containing ASCII logo and version information for use in CLI utilities.
 */
function print_cli_banner()
{
  if (OBS_QUIET || !is_cli()) { return; } // Silent exit if not cli or quiet

  print_message("%W
  ___   _                              _
 / _ \ | |__   ___   ___  _ __ __   __(_) _   _  _ __ ___
| | | || '_ \ / __| / _ \| '__|\ \ / /| || | | || '_ ` _ \
| |_| || |_) |\__ \|  __/| |    \ V / | || |_| || | | | | |
 \___/ |_.__/ |___/ \___||_|     \_/  |_| \__,_||_| |_| |_|%c
".
  str_pad(OBSERVIUM_PRODUCT_LONG." ".OBSERVIUM_VERSION, 59, " ", STR_PAD_LEFT)."\n".
  str_pad("http://www.observium.org" , 59, " ", STR_PAD_LEFT)."%N\n", 'color');

  // One time alert about deprecated (eol) php version
  if (version_compare(PHP_VERSION, OBS_MIN_PHP_VERSION, '<'))
  {
    $php_version = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
    print_message("

+---------------------------------------------------------+
|                                                         |
|                %rDANGER! ACHTUNG! BHUMAHUE!%n               |
|                                                         |
".
    str_pad("| %WYour PHP version is too old (%r".$php_version."%W),", 64, ' ')."%n|
| %Wfunctionality may be broken. Please update your PHP!%n    |
| %WCurrently recommended version(s): >%g7.1.x%n                |
|                                                         |
| See additional information here:                        |
| %c".
  str_pad(OBSERVIUM_URL . '/docs/software_requirements/' , 56, ' ')."%n|
|                                                         |
+---------------------------------------------------------+
", 'color');
  }
}

// TESTME needs unit testing
/**
 * Creates a list of php files available in the html/pages/front directory, to show in a
 * dropdown on the web configuration page.
 *
 * @return array List of front page files available
 */
function config_get_front_page_files()
{
  global $config;

  $frontpages = array();

  foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($config['html_dir'] . '/pages/front')) as $file)
  {
    $filename = $file->getFileName();
    if ($filename[0] != '.')
    {
      $frontpages["pages/front/$filename"] = nicecase(basename($filename,'.php'));
    }
  }

  return $frontpages;
}

/**
 * Triggers a rediscovery of the given device at the following discovery -h new run.
 *
 * @param array $device  Device array.
 * @param array $modules Array with modules required for rediscovery, if empty rediscover device full
 *
 * @return mixed Status of added or not force device discovery
 */
// TESTME needs unit testing
function force_discovery($device, $modules = array())
{
  $return = FALSE;

  if (count($modules) == 0)
  {
    // Modules not passed, just full rediscover device
    $return = dbUpdate(array('force_discovery' => 1), 'devices', '`device_id` = ?', array($device['device_id']));
  } else {
    // Modules passed, check if modules valid and enabled
    $modules = (array)$modules;
    $forced_modules = get_entity_attrib('device', $device['device_id'], 'force_discovery_modules');
    if ($forced_modules)
    {
      // Already forced modules exist, merge it with new
      $modules = array_unique(array_merge($modules, json_decode($forced_modules, TRUE)));
    }

    $valid_modules = array();
    foreach ($GLOBALS['config']['discovery_modules'] as $module => $ok)
    {
      // Filter by valid and enabled modules
      if ($ok && in_array($module, $modules))
      {
        $valid_modules[] = $module;
      }
    }

    if (count($valid_modules))
    {
      $return = dbUpdate(array('force_discovery' => 1), 'devices', '`device_id` = ?', array($device['device_id']));
      set_entity_attrib('device', $device['device_id'], 'force_discovery_modules', json_encode($valid_modules));
    }
  }

  return $return;
}

// From http://stackoverflow.com/questions/9339619/php-checking-if-the-last-character-is-a-if-not-then-tack-it-on
// Assumed free to use :)
// DOCME needs phpdoc block
// TESTME needs unit testing
function fix_path_slash($p)
{
    $p = str_replace('\\','/',trim($p));
    return (substr($p,-1)!='/') ? $p.='/' : $p;
}

/**
 * Calculates missing fields of a mempool based on supplied information and returns them all.
 * This function also applies the scaling as requested.
 * Also works for storage.
 *
 * @param float $scale   Scaling to apply to the supplied values.
 * @param int   $used    Used value of mempool, before scaling, or NULL.
 * @param int   $total   Total value of mempool, before scaling, or NULL.
 * @param int   $free    Free value of mempool, before scaling, or NULL.
 * @param int   $perc    Used percentage value of mempool, or NULL.
 * @param array $options Additional options, ie separate scales for used/total/free
 *
 * @return array Array consisting of 'used', 'total', 'free' and 'perc' fields
 */
function calculate_mempool_properties($scale, $used, $total, $free, $perc = NULL, $options = array())
{
  // Scale, before maths!
  foreach (array('total', 'used', 'free') as $param)
  {
    if (is_numeric($$param))
    {
      if (isset($options['scale_'.$param]))
      {
        // Separate sclae for current param
        $$param *= $options['scale_'.$param];
      }
      else if ($scale != 0 && $scale != 1)
      {
        // Common scale
        $$param *= $scale;
      }
    }
  }

  if (is_numeric($total) && is_numeric($free))
  {
    $used = $total - $free;
    $perc = round($used / $total * 100, 2);
  }
  else if (is_numeric($used) && is_numeric($free))
  {
    $total = $used + $free;
    $perc = round($used / $total * 100, 2);
  }
  else if (is_numeric($total) && is_numeric($perc))
  {
    $used = $total * $perc / 100;
    $free = $total - $used;
  }
  else if (is_numeric($total) && is_numeric($used))
  {
    $free = $total - $used;
    $perc = round($used / $total * 100, 2);
  }
  else if (is_numeric($perc))
  {
    $total  = 100;
    $used   = $perc;
    $free   = 100 - $perc;
    //$scale  = 1; // Reset scale for percentage-only
  }
  if (OBS_DEBUG && ($perc < 0 || $perc > 100))
  {
    print_error('Incorrect scales or passed params to function ' . __FUNCTION__ . '()');
  }

  return array('used' => $used, 'total' => $total, 'free' => $free, 'perc' => $perc);
}

/**
 * Get all values from specific key in a multidimensional array
 *
 * @param $key string
 * @param $arr array
 * @return null|string|array
 */

function array_value_recursive($key, array $arr){
    $val = array();
    array_walk_recursive($arr, function($v, $k) use($key, &$val){
        if($k == $key) array_push($val, $v);
    });
    return count($val) > 1 ? $val : array_pop($val);
}

function discovery_check_if_type_exist(&$valid, $entry, $entity_type)
{

  if (isset($entry['skip_if_valid_exist']))
  {
    $tree = explode('->', $entry['skip_if_valid_exist']);
    //print_vars($tree);
    switch (count($tree))
    {
      case 1:
        if (isset($valid[$entity_type][$tree[0]]) &&
            count($valid[$entity_type][$tree[0]]))
        {
          print_debug("Excluded by valid exist: ".$entry['skip_if_valid_exist']);
          return TRUE;
        }
        break;
      case 2:
        if (isset($valid[$entity_type][$tree[0]][$tree[1]]) &&
            count($valid[$entity_type][$tree[0]][$tree[1]]))
        {
          print_debug("Excluded by valid exist: ".$entry['skip_if_valid_exist']);
          return TRUE;
        }
        break;
      case 3:
        if (isset($valid[$entity_type][$tree[0]][$tree[1]][$tree[2]]) &&
            count($valid[$entity_type][$tree[0]][$tree[1]][$tree[2]]))
        {
          print_debug("Excluded by valid exist: ".$entry['skip_if_valid_exist']);
          return TRUE;
        }
        break;
      default:
        print_debug("Too many array levels for valid sensor!");
    }
  }
  return FALSE;
}

function discovery_check_requires_pre($device, $entry, $entity_type)
{

  if (isset($entry['pre_test']) && is_array($entry['pre_test']))
  {
    // Convert single test condition to multi-level condition
    if (isset($entry['pre_test']['operator'])) {
      $entry['pre_test'] = array($entry['pre_test']);
    }

    foreach ($entry['pre_test'] as $test)
    {
      if (isset($test['oid']))
      {
        // Fetch just the value eof the OID.
        $test['data'] = snmp_cache_oid($device, $test['oid'], NULL, NULL, OBS_SNMP_ALL);
        $oid = $test['oid'];
      }
      else if (isset($test['field']))
      {
        $test['data'] = $entry[$test['field']];
        $oid = $test['field'];
      } else {
        print_debug("Not correct Field (".$test['field'].") passed to discovery_check_requires(). Need add it to 'oid_extra' definition.");
        return FALSE;
      }
      if (test_condition($test['data'], $test['operator'], $test['value']) === FALSE)
      {
        print_debug("Excluded by not test condition: $oid [".$test['data']."] ".$test['operator']." [".implode(', ', (array)$test['value'])."]");
        return TRUE;
      }
    }
  }

  return FALSE;
}

function discovery_check_requires($device, $entry, $array, $entity_type)
{
  if (isset($entry['test']) && is_array($entry['test']))
  {
    // Convert single test condition to multi-level condition
    if (isset($entry['test']['operator'])) {
      $entry['test'] = array($entry['test']);
    }

    foreach ($entry['test'] as $test)
    {
      if (isset($test['oid']))
      {
        // Fetch just the value eof the OID.
        $test['data'] = snmp_cache_oid($device, $test['oid'], NULL, NULL, OBS_SNMP_ALL);
        $oid = $test['oid'];
      }
      else if (isset($test['field']))
      {
        $test['data'] = $array[$test['field']];
        if (!isset($array[$test['field']]))
        {
          // Show debug error (some time Oid fetched, but not exist for current index)
          print_debug("Not correct Field (" . $test['field'] . ") passed to discovery_check_requires(). Need add it to 'oid_extra' definition.");
          //return FALSE;
        }
        $oid = $test['field'];
      }
      if (test_condition($test['data'], $test['operator'], $test['value']) === FALSE)
      {
        print_debug("Excluded by not test condition: $oid [".$test['data']."] ".$test['operator']." [".implode(', ', (array)$test['value'])."]");
        return TRUE;
      }
    }
  }

  return FALSE;
}


// EOF
