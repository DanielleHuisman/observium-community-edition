<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage functions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

// Observium Includes

require_once($config['install_dir'] . "/includes/common.inc.php"); // already included in sql-config.inc.php before definitions
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
$fincludes = [
  'groups', 'billing', // Not exist in community edition
  'distributed',       // Not exist in community edition, distributed poller functions
  'community',         // community edition specific
  'custom',            // custom functions, i.e. short_hostname
];
foreach ($fincludes as $entry) {
  $file = $config['install_dir'] . '/includes/' . $entry . '.inc.php';
  if (is_file($file)) { include_once($file); }
}
unset($fincludes);

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

function get_var_true($var, $true = NULL) {
  return $var === '1' || $var === 1 ||
         $var === 'on' || $var === 'yes' ||
         $var === TRUE ||
         // allow extra param for true, ie confirm
         (!empty($true) && $var === $true);
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
 *   'action' => 'upper'      Call strtoupper()
 *   'action' => 'lower'      Call strtolower()
 *   'action' => 'nicecase'   Call nicecase()
 *   'action' => 'replace'    Case-sensitively replace 'from' string by 'to'; 'from' can be an array of strings
 *   'action' => 'ireplace'   Case-insensitively replace 'from' string by 'to'; 'from' can be an array of strings
 *   'action' => 'regex_replace'/'preg_replace'  Replace 'from' with 'to'.
 *   'action' => 'timeticks'  Convert standart Timeticks to seconds
 *   'action' => 'age'/'uptime' Convert any human readable age/uptime to seconds (also support timeticks)
 *   'action' => 'explode'    Explode string by 'delimiter' (default ' ') and fetch array element (first (default), second, last or number)
 *   'action' => 'map'        Map string by key -> value, where key-value pairs passed by array 'map'
 *   'action' => 'map_match'  Map string by pattern -> value, where pattern-value pairs passed by array 'map'
 *   'action' => 'units'      Convert byte/bit string with units to bytes/bits
 *   'action' => 'asdot'      Call bgp_asdot_to_asplain()
 *   'action' => 'entity_name' Call rewrite_entity_name()
 *   'action' => 'urlencode'  Call rawurlencode()
 *   'action' => 'urldecode'  Call rawurldecode()
 *   'action' => 'escape'     Call escape_html()
 *
 * @return string|array|null Transformed string
 */
function string_transform($string, $transformations) {
  if (!is_array($transformations) || empty($transformations)) {
    // Bail out if no transformations are given
    return $string;
  }

  // Simplify single action definition with less array nesting
  if (isset($transformations['action'])) {
    $transformations = array($transformations);
  }

  foreach ($transformations as $transformation) {
    $msg = "  String '$string' transformed by action [".$transformation['action']."] to: ";
    switch ($transformation['action']) {
      case 'prepend':
        $string = $transformation['string'] . $string;
        break;

      case 'append':
        $string .= $transformation['string'];
        break;

      case 'strtoupper':
      case 'upper':
        $string = strtoupper($string);
        break;

      case 'strtolower':
      case 'lower':
        $string = strtolower($string);
        break;

      case 'nicecase':
        $string = nicecase($string);
        break;

      case 'trim':
      case 'ltrim':
      case 'rtrim':
        if (isset($transformation['chars']) && !isset($transformation['characters'])) {
          // Just simple for brain memory key
          $transformation['characters'] = $transformation['chars'];
        }
        if (!isset($transformation['characters'])) {
          $transformation['characters'] = " \t\n\r\0\x0B";
        }

        if ($transformation['action'] === 'rtrim') {
          $string = rtrim($string, $transformation['characters']);
        } elseif ($transformation['action'] === 'ltrim') {
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
        $to_string = preg_replace($transformation['from'], $transformation['to'], $string);
        $preg_last_error = preg_last_error();
        if ($preg_last_error === PREG_INTERNAL_ERROR) {
          // Dear Saint Patrick, we passed "from" without delimiter
          $transformation['from'] = !str_contains($transformation['from'], '/') ? '/' . $transformation['from'] . '/' : '!' . $transformation['from'] . '!';
          $to_string = preg_replace($transformation['from'], $transformation['to'], $string);
          $preg_last_error = preg_last_error();
        }
        if ($preg_last_error === PREG_NO_ERROR) {
          $string = $to_string;
        }
        break;

      case 'regex_match':
      case 'preg_match':
        if (preg_match($transformation['from'], $string, $matches)) {
          $string = array_tag_replace($matches, $transformation['to']);
        }
        break;

      case 'map':
        // Map string by key -> value
        if (is_array($transformation['map']) && isset($transformation['map'][$string])) {
          $string = $transformation['map'][$string];
        }
        break;

      case 'map_match':
        if (isset($transformation['map_match'])) {
          $transformation['map'] = $transformation['map_match'];
        }
        // Map string by pattern -> value
        if (is_array($transformation['map'])) {
          foreach ($transformation['map'] as $pattern => $to_string) {
            if (preg_match($pattern, $string)) {
              $string = $to_string;
              break;
            }
          }
        }
        break;

      case 'timeticks':
        // Timeticks: (2542831) 7:03:48.31
        $string = timeticks_to_sec($string);
        break;

      case 'age':
      case 'uptime':
        // Any human readable age/uptime to seconds (also support timeticks)
        $string = uptime_to_seconds($string);
        break;

      case 'asdot':
        // BGP 32bit ASN from asdot to plain
        $string = bgp_asdot_to_asplain($string);
        break;

      case 'ip_uncompress':
      case 'ip-uncompress':
        // IPv4/6 Uncompress
        $string = ip_uncompress($string);
        break;

      case 'ip_compress':
      case 'ip-compress':
        // IPv4/6 Compress
        $string = ip_compress($string);
        break;

      case 'mac':
        // MAC address formatting
        $string = format_mac($string);
        break;

      case 'units':
        // 200kbps -> 200000, 50M -> 52428800
        $string = unit_string_to_numeric($string);
        break;

      case 'entity_name':
        $string = rewrite_entity_name($string);
        break;

      case 'explode':
      case 'split':
        // String delimiter (default is single space " ")
        if (isset($transformation['delimiter']) && strlen($transformation['delimiter'])) {
          $delimiter = $transformation['delimiter'];
        } else {
          $delimiter = ' ';
        }
        $array = explode($delimiter, $string);
        // Get array index (default is first)
        if (!isset($transformation['index'])) {
          $transformation['index'] = 'first';
        }
        switch ((string)$transformation['index']) {
          case 'all':
          case 'array':
            // return array instead single string
            $string = $array;
            break;
          case 'first':
          case 'begin':
            $string = array_shift($array);
            break;
          case 'second':
          case 'secondary':
          case 'two':
            array_shift($array);
            $string = array_shift($array);
            break;
          case 'last':
          case 'end':
            $string = array_pop($array);
            break;
          default:
            if (strlen($array[$transformation['index']])) {
              $string = $array[$transformation['index']];
            }
        }
        break;

      case 'urlencode':
      case 'rawurlencode':
        $string = rawurlencode($string);
        break;

      case 'urldecode':
      case 'rawurldecode':
        $string = rawurldecode($string);
        break;

      case 'escape':
        $string = escape_html($string);
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
function array_sort($array, $on, $order='SORT_ASC') {
  $new_array = array();
  $sortable_array = array();

  if (safe_count($array) > 0) {
    foreach ($array as $k => $v) {
      if (is_array($v)) {
        foreach ($v as $k2 => $v2) {
          if ($k2 == $on) {
            $sortable_array[$k] = $v2;
          }
        }
      } else {
        $sortable_array[$k] = $v;
      }
    }

    switch ($order) {
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
function scale_value($value, $scale) {

  // Scale when not zero (0, 0.0, '0', '0.0') or one (1, 1.0, '1', '1.0')
  if ($scale != 0 && $scale != 1 &&
      is_numeric($scale) && is_numeric($value)) {
    return $value * $scale;
  }

  return $value;
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
  $sysDescr_ok  = $GLOBALS['snmp_status'] || $GLOBALS['snmp_error_code'] === OBS_SNMP_ERROR_EMPTY_RESPONSE; // Allow empty response for sysDescr (not timeouts)
  $sysObjectID  = snmp_cache_sysObjectID($device);

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
    if (match_oid_num($sysObjectID, $def))
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
      if (match_discovery_oids($device, $def, $sysObjectID, $sysDescr))
      {
        print_cli_table($table_rows, $table_headers, $table_desc . " ($old_os: ".$config['os'][$old_os]['text'].'):', $table_opts);
        return $old_os;
      }
    }
    foreach ($discovery_os['discovery_network'][$old_os] as $def)
    {
      if (match_discovery_oids($device, $def, $sysObjectID, $sysDescr))
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
      if (match_discovery_oids($device, $def, $sysObjectID, $sysDescr)) { $os = $cos; break 2; }
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
        if (match_discovery_oids($device, $def, $sysObjectID, $sysDescr)) { $os = $cos; break 2; }
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
 * Compares Numeric OID with $needle. Return TRUE if match.
 *
 * @param string       $oid    Numeric OID for compare
 * @param string|array $needle Compare with this
 * @return bool                TRUE if match, otherwise FALSE
 */
function match_oid_num($oid, $needle)
{
  if (is_array($needle)) {
    foreach ($needle as $entry) {
      //print_debug("OID $oid compare to $entry = ");
      if (match_oid_num($oid, $entry)) {
        //print_debug("match\n");
        return TRUE;
      }
      //print_debug("NOT match\n");
    }
    return FALSE;
  }

  # validate OID
  $oid = trim($oid);
  if (!preg_match('/^(?:(?<start>\.?)(?:\d+(?:\.\d+)+)|\.\d+)$/', $oid, $matches)) {
    print_debug("Incorrect OID passed to match_oid_num('$oid', '$needle').");
    return FALSE;
  }
  // append leading point if missing (1.3.6 -> .1.3.6)
  if (isset($matches['start']) && $matches['start'] === '') {
    $oid = '.'.$oid;
  }

  # validate needle (in other cases use regex)
  $needle = trim($needle);
  if (!preg_match('/^(?:(?<start>\.?)(?:\d+(?:\.\d+)+)|\.\d+)(?<end>\.)?$/', $needle, $matches)) {

    // Try simple regex matches, ie .1.*.1., .1.3.(4|5|9), .1.3.4[56].*
    if (preg_match('/^(?<start>\.?)\d+(?:\.(\d+|[\d\[\]\-]+|[\d\(\)\|]+|[\d\*]+))*(?<end>\.)?$/', $needle, $matches)) {
      // append leading point if missing (1.3.6 -> .1.3.6)
      if (isset($matches['start']) && $matches['start'] === '') {
        $needle = '.'.$needle;
      }

      $needle_pattern = str_replace([ '.', '*' ], [ '\.', '.*?' ], $needle);
      if (isset($matches['end']) && $matches['end'] === '.') {
        $needle_pattern .= '\d+(\.\d+)*';
      } else {
        $needle_pattern .= '(\.\d+)*';
      }

      //print_message("/^$needle_pattern$/");
      return (bool)preg_match('/^' . $needle_pattern . '$/', $oid);
    }

    print_debug("Incorrect Needle passed to match_oid_num('$oid', '$needle').");
    return FALSE;
  }
  // append leading point if missing (1.3.6 -> .1.3.6)
  if (isset($matches['start']) && $matches['start'] === '') {
    $needle = '.'.$needle;
  }

  // Use wildcard compare if sysObjectID definition have '.' at end, ie:
  //   .1.3.6.1.4.1.2011.1.
  if (isset($matches['end']) && $matches['end'] === '.') {
    return str_starts($oid, $needle);
  }

  // Use exact match sysObjectID definition or wildcard compare with '.' at end, ie:
  //   .1.3.6.1.4.1.2011.2.27
  return $oid === $needle || str_starts($oid, $needle.'.');
}

/**
 * Compares complex sysObjectID/sysDescr and any other MIB::Oid combination with definition.
 * Can check some device params (os, os_group, vendor, hardware, version)
 * Return TRUE if match.
 *
 * @param array  $device      Device array
 * @param array  $needle      Compare with this definition array
 * @param string $sysObjectID Walked sysObjectID from device
 * @param string $sysDescr    Walked sysDescr from device
 *
 * @return boolean            TRUE if match, otherwise FALSE
 */
function match_discovery_oids($device, $needle, $sysObjectID = NULL, $sysDescr = NULL)
{
  global $table_rows;

  // Count required conditions
  $needle_oids  = array_keys($needle);
  $needle_count = count($needle_oids);

  // Match sysObjectID and sysDescr always first!
  $needle_oids_order = array_merge(array('sysObjectID', 'sysDescr'), $needle_oids);
  $needle_oids_order = array_unique($needle_oids_order);
  $needle_oids_order = array_intersect($needle_oids_order, $needle_oids);

  $matched_defs = [];

  // By first detect device os and os_group params match with "or" condition
  // Note, when os/os_group not defined, $os_match also TRUE
  $os_match = match_discovery_os_group($device, $needle, $os_params, $matched_defs);

  // Check if any of device param matched
  if (count($os_params))
  {
    if ($os_match)
    {
      // Remove device params from later Oids checks
      $needle_oids_order = array_diff($needle_oids_order, $os_params);
      // Reduce needle count by device params count
      $needle_count -= count($os_params);
    } else {
      // No any device param matched, stop all other checks
      return FALSE;
    }
  }

  // Now do Oids matching
  foreach ($needle_oids_order as $oid)
  {
    $match = FALSE;
    switch ($oid)
    {
      case 'type':
        foreach ((array)$needle[$oid] as $def)
        {
          if ($device[$oid] == $def)
          {
            $matched_defs[] = [ 'device ' . $oid, $def, $device[$oid] ];
            $needle_count--;
            $match = TRUE;
            break;
          }
        }
        break;

      case 'hardware':
      case 'version':
      case 'vendor':
      case 'distro':
      case 'distro_ver':
      case 'kernel':
      case 'arch':
        foreach ((array)$needle[$oid] as $def)
        {
          if (preg_match($def, $device[$oid]))
          {
            $matched_defs[] = [ 'device ' . $oid, $def, $device[$oid] ];
            $needle_count--;
            $match = TRUE;
            break;
          }
        }
        break;

      case 'sysObjectID':
        foreach ((array)$needle[$oid] as $def)
        {
          //var_dump($def);
          //var_dump($sysObjectID);
          //var_dump(match_oid_num($sysObjectID, $def));
          if (match_oid_num($sysObjectID, $def))
          {
            $matched_defs[] = [ $oid, $def, $sysObjectID ];
            $needle_count--;
            $match = TRUE;
            break;
          }
        }
        break;

      case 'sysDescr':
      case 'sysName':
        // Common SNMPv2-MIB Oids
        if ($oid == 'sysDescr')
        {
          $value = $sysDescr;
          $value_ok = TRUE;
        } else {
          $value    = snmp_fix_string(snmp_cache_oid($device, $oid . '.0', 'SNMPv2-MIB'));
          $value_ok = $GLOBALS['snmp_status'] || $GLOBALS['snmp_error_code'] === OBS_SNMP_ERROR_EMPTY_RESPONSE; // Allow empty response
        }

        foreach ((array)$needle[$oid] as $def)
        {
          //print_vars($def);
          //print_vars($value);
          //print_vars(preg_match($def, $value));
          if ($value_ok && preg_match($def, $value))
          {
            $matched_defs[] = [ $oid, $def, $value ];
            $needle_count--;
            $match = TRUE;
            break;
          }
        }
        break;

      case 'package':
      case 'hrSWInstalledName':
      case 'HOST-RESOURCES-MIB::hrSWInstalledName':
        $defs = (array)$needle[$oid];
        $oid = 'hrSWInstalledName';
        foreach (snmp_cache_table($device, $oid, [], 'HOST-RESOURCES-MIB') as $entry) {
          $value = $entry[$oid];
          foreach ($defs as $def) {
            if (preg_match($def, $value)) {
              $matched_defs[] = [ $oid, $def, $value ];
              $needle_count--;
              $match = TRUE;
              break 2;
            }
          }
        }
        break;

      default:
        // All other oids,
        // fetch by first, than compare with pattern
        $value    = snmp_fix_string(snmp_cache_oid($device, $oid));
        $value_ok = $GLOBALS['snmp_status'] || $GLOBALS['snmp_error_code'] === OBS_SNMP_ERROR_EMPTY_RESPONSE; // Allow empty response

        foreach ((array)$needle[$oid] as $def)
        {
          // print_vars($def);
          // print_vars($value);
          // print_vars(preg_match($def, $value));
          if ($value_ok && preg_match($def, $value))
          {
            $matched_defs[] = [ $oid, $def, $value ];
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
    foreach ($matched_defs as $entry)
    {
      $table_rows[] = $entry;
    }
  }

  return $match;
}

/**
 * Compares base device os/os_group params with definition by OR condition.
 * Return TRUE if match.
 * Note, if os params not exist in definition, return TRUE!
 *
 * @param array  $device      Device array
 * @param array  $needle      Compare with this definition array
 * @param array  $os_params   (optional) Callback list of defined os params
 * @param array  $match       (optional) Callback array with matched param/value
 *
 * @return boolean            TRUE if match, otherwise FALSE
 */
function match_discovery_os_group($device, $needle, &$os_params = [], &$match = [])
{
  $needle_params = ['os', 'os_group']; // list required match os params

  $needle_keys = array_keys($needle);
  $os_params = array_intersect($needle_keys, $needle_params);

  // If os params not defined, just return TRUE
  if (empty($os_params))
  {
    return TRUE;
  }

  foreach ($needle_params as $param)
  {
    if (!in_array($param, $needle_keys)) { continue; }

    foreach ((array)$needle[$param] as $def)
    {
      if ($device[$param] == $def)
      {
        $match[] = [ 'device ' . $param, $def, $device[$param] ];
        return TRUE;
      }
    }
  }

  return FALSE;
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
function compare_numeric_oids($oid1, $oid2) {
  $oid1_array = explode('.', ltrim($oid1, '.'));
  $oid2_array = explode('.', ltrim($oid2, '.'));

  $count1 = count($oid1_array);
  $count2 = count($oid2_array);

  for ($i = 0; $i <= min($count1, $count2) - 1; $i++)
  {
    $int1 = (int)$oid1_array[$i];
    $int2 = (int)$oid2_array[$i];
    if ($int1 > $int2) { return 1; }
    if ($int1 < $int2) { return -1; }
  }
  if ($count1 > $count2) { return 1; }
  if ($count1 < $count2) { return -1; }

  return 0;
}

/**
 * Compare two numeric oids and return -1, 0, 1
 * here reverse order
 * ie: .1.2.1. vs 1.2.2
 */
function compare_numeric_oids_reverse($oid1, $oid2) {
  return compare_numeric_oids($oid2, $oid1);
}

function set_value_param_definition($param, $def, $entry)
{
  $value = NULL;
  $oid = $def['oid_' . $param];
  if (isset($entry[$oid]))
  {
    $value = $entry[$oid];
  }
  elseif (isset($def[$param]))
  {
    $value = array_tag_replace($entry, $def[$param]);
  }

  return $value;
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

/**
 * Compare two devices by specified Oids (default: sysObjectID, sysDescr, sysContact, sysLocation, sysUpTime)
 *
 * @param array $device1 First device to compare (which we add)
 * @param array $device2 Second device to compare (which we already have)
 * @param array $oids List of oids for compare, default is common system Oids
 * @param boolean $use_db Prefer known Oids from db (also actually for down devices)
 *
 * @return bool TRUE if all Oids return same values
 */
function compare_devices_oids($device1, $device2, $oids = [], $use_db = TRUE)
{
  if (empty($oids))
  {
    // Compare IP addresses at last if all other Oids still same
    // Note: IF-MIB::ifPhysAddress checks only when IP-MIB::ipAdEntAddr unavailable from devices
    $oids = [ 'sysObjectID', 'sysDescr', 'sysContact', 'sysLocation', 'sysUpTime',
              'IP-MIB::ipAdEntAddr', 'IF-MIB::ifPhysAddress' ];
  }

  // First device must be "new" not in db, secondary from db, check if swapped
  if (!$device2['device_id'])
  {
    list($device1, $device2) = array($device2, $device1);
  }
  // Re-fetch secondary device array
  $device2 = device_by_id_cache($device2['device_id'], TRUE);

  // Disable snmp bulk and increase for new device while testing
  $flags1  = OBS_SNMP_ALL_MULTILINE; // Default
  if (empty($device1['os']) || $device1['os'] == 'generic')
  {
    // Disable snmp bulk for second device
    $device1['snmp_nobulk'] = TRUE;
    // Add no snmp increase flag for unknown devices, while can be troubles for adding device
    $flags1 |= OBS_SNMP_NOINCREASE;
  }
  elseif (isset($GLOBALS['config']['os'][$device1['os']]['snmpcheck']))
  {
    // See Hikvision os
    $oids = array_merge($oids, (array)$GLOBALS['config']['os'][$device1['os']]['snmpcheck']);
    print_debug_vars($oids);
  }
  elseif (isset($GLOBALS['config']['os'][$device2['os']]['snmpcheck']))
  {
    // In other cases use check Oids by second device.
    $oids = array_merge($oids, (array)$GLOBALS['config']['os'][$device2['os']]['snmpcheck']);
    print_debug_vars($oids);
  } else {
    // compare by mib specific serials
    foreach (get_device_mibs_permitted($device2) as $mib2)
    {
      foreach ($GLOBALS['config']['mibs'][$mib2]['serial'] as $entry)
      {
        if (isset($entry['oid']))
        {
          $oids[] = $mib2 . '::' . $entry['oid'];
        }
      }
    }
  }

  $same = FALSE;
  $skip_ifPhysAddress = FALSE;
  $oids_compare = [];
  foreach ($oids as $full_oid)
  {
    if (!str_contains($full_oid, '::'))
    {
      $full_oid = 'SNMPv2-MIB::' . $full_oid;
    }
    list($mib, $oid) = explode('::', $full_oid);

    switch ($full_oid)
    {
      case 'SNMPv2-MIB::sysObjectID':
        // Do not compare when not support standard MIB
        if (!is_device_mib($device2, 'SNMPv2-MIB'))
        {
          break;
        }

        $value1 = snmp_cache_sysObjectID($device1);
        if ($use_db)
        {
          $value2 = $device2['sysObjectID'];
        } else {
          $value2 = snmp_cache_sysObjectID($device2);
        }

        // Do not compare if both values empty
        if ($value1 === '' && $value2 === '') { break; }

        $same = $value1 == $value2;
        if (!$same)
        {
          // Not same, break foreach
          if (OBS_DEBUG)
          {
            // print_warning("The compared oid differs on devices:");
            // print_cli_table([ [ $device1['hostname'], $value1 ],
            //                   [ $device2['hostname'], $value2 ] ], [ 'Device', $full_oid ]);
            $oids_compare[] = [ '%r'.$full_oid.'%n', $value1, $value2 ];
          }
          break 2;
        }
        elseif (OBS_DEBUG)
        {
          $oids_compare[] = [ $full_oid, $value1, $value2 ];
        }
        break;

      case 'SNMPv2-MIB::sysUpTime':
        // Do not compare uptime when
        if ($device2['status'] == 0 ||              // secondary device is down
            !is_device_mib($device2, 'SNMPv2-MIB')) // not support standard MIB
        {
          break;
        }

        $oid .= '.0';
        $time1 = microtime(TRUE);

        // sysUpTime always compare not cached values
        $value1 = snmp_get_oid($device1, $oid, $mib);
        $status1 = snmp_status();
        $value2 = snmp_get_oid($device2, $oid, $mib);
        $status2 = snmp_status();

        $time_diff = round(microtime(TRUE) - $time1, 0, PHP_ROUND_HALF_UP);

        if ($status1 && $status2)
        {
          // Do not compare if both values empty
          if ($value1 === '' && $value2 === '') { break; }

          $same = (timeticks_to_sec($value2) - timeticks_to_sec($value1)) <= $time_diff;
          if (!$same)
          {
            // Not same, break foreach
            if (OBS_DEBUG)
            {
              // print_warning("The compared oid differs on devices:");
              // print_cli_table([ [ $device1['hostname'], $value1 ],
              //                   [ $device2['hostname'], $value2 ] ], [ 'Device', $full_oid ]);
              $oids_compare[] = [ '%r'.$full_oid.'%n', $value1, $value2 ];
            }
            break 2;
          }
          elseif (OBS_DEBUG)
          {
            $oids_compare[] = [ $full_oid, $value1, $value2 ];
          }
        }
        break;

      case 'IP-MIB::ipAdEntAddr':
        // Do not compare when not support standard MIB
        if (!is_device_mib($device2, 'IP-MIB'))
        {
          break;
        }

        $value1  = snmp_cache_table($device1, $oid, [], $mib, NULL, $flags1);
        $status1 = snmp_status();
        if ($use_db)
        {
          $value1 = array_values($value1); // Remove indexes for compare by db
          sort($value1);
          $value2 = dbFetchColumn('SELECT `ipv4_address` FROM `ipv4_addresses` WHERE `device_id` = ?', [ $device2['device_id'] ]);
          sort($value2);
          $status2 = TRUE;
        }
        elseif (!$device2['status'])
        {
          // Secondary down, do not compare
          break;
        } else {
          $value2  = snmp_cache_table($device2, $oid, [], $mib);
          $status2 = snmp_status();
        }

        if ($status1 && $status2)
        {
          // Skip IF-MIB::ifPhysAddress check when IP-MIB::ipAdEntAddr available
          $skip_ifPhysAddress = TRUE;

          $same = count(array_diff($value1, $value2)) == 0;
          if (!$same)
          {
            // Not same, break foreach
            if (OBS_DEBUG)
            {
              // print_warning("The compared oid differs on devices:");
              // print_cli_table([ [ $device1['hostname'], implode(', ', $value1) ],
              //                   [ $device2['hostname'], implode(', ', $value2) ] ], [ 'Device', $full_oid ]);
              $oids_compare[] = [ '%r'.$full_oid.'%n', implode(', ', $value1), implode(', ', $value2) ];
            }
            break 2;
          }
          elseif (OBS_DEBUG)
          {
            $oids_compare[] = [ $full_oid, implode(', ', $value1), implode(', ', $value2) ];
          }
        }
        break;

      case 'IF-MIB::ifPhysAddress':
        // Do not compare mac addresses if already checked IP addresses
        if ($skip_ifPhysAddress) { break; }
        // Do not compare when not support standard MIB
        elseif (!is_device_mib($device2, 'IF-MIB'))
        {
          break;
        }

        // For ports ifPhysAddress, by first simple check total ports count (IF-MIB::ifNumber.0)
        $value1 = snmp_cache_oid($device1, 'ifNumber.0', 'IF-MIB');
        $status1 = snmp_status();
        if ($use_db)
        {
          $ifPhysAddress2 = dbFetchRows('SELECT `ifIndex`, `ifPhysAddress` FROM `ports` WHERE `device_id` = ? AND `deleted` = ?', [ $device2['device_id'], 0 ]);
          print_debug_vars($ifPhysAddress2);
          $value2 = count($ifPhysAddress2);
          if ($value2 < $value1 && $value2 > 0)
          {
            // Since we can ignore ports, in DB can be same or less ports!
            // Than compare only by mac values
            $value2 = $value1;
          }
          $status2 = TRUE;
        }
        elseif (!$device2['status'])
        {
          // Secondary down, do not compare
          break;
        } else {
          $value2 = snmp_cache_oid($device2, 'ifNumber.0', 'IF-MIB');
          $status2 = snmp_status();
        }

        if ($status1 && $status2 && ($value1 > 0) && $value1 == $value2)
        {
          // Ports count same, now check phys mac addresses
          $value1  = snmp_cache_table($device1, $oid, [], $mib, NULL, $flags1);
          $status1 = snmp_status();

          if ($use_db && $status1)
          {
            // Compare mac addresses by db entries
            $mac_same = TRUE;
            foreach ($ifPhysAddress2 as $entry)
            {
              $ifIndex = $entry['ifIndex'];
              // When ifIndex not exist on device - devices not same
              if (!isset($value1[$ifIndex]))
              {
                $same = FALSE;
                break 2;
              }
              // do not compare empty mac addresses
              if (empty($entry['ifPhysAddress']) || $entry['ifPhysAddress'] == '000000000000') { continue; }
              // There need compare all mac on device
              $mac_same = $mac_same && $entry['ifPhysAddress'] == mac_zeropad($value1[$ifIndex]['ifPhysAddress']);
              //print_debug("");
            }
            $same = $mac_same;
            // exit case
            break;
          }

          // Or compare by snmp request
          $value2  = snmp_cache_table($device2, $oid, [], $mib);
          $status2 = snmp_status();

          if ($status1 && $status2)
          {
            $same = count(array_diff($value1, $value2)) == 0;
            if (!$same)
            {
              // Not same, break foreach
              if (OBS_DEBUG)
              {
                // print_warning("The compared oid differs on devices:");
                // print_cli_table([ [ $device1['hostname'], implode(', ', $value1) ],
                //                   [ $device2['hostname'], implode(', ', $value2) ] ], [ 'Device', $oid ]);
                $oids_compare[] = [ '%r'.$full_oid.'%n', implode(', ', $value1), implode(', ', $value2) ];
              }
              break 2;
            }
            elseif (OBS_DEBUG)
            {
              $oids_compare[] = [ $full_oid, implode(', ', $value1), implode(', ', $value2) ];
            }
          }
        }
        break;

      default:
        if ($device2['status'] == 0 ||    // secondary device is down
          !is_device_mib($device2, $mib)) // not support MIB
        {
          print_debug("Check Oid ($full_oid) skipped, because second device is down or MIB ($mib) not defined.");
          break;
        }

        // When Oid not indexed, append default index
        if (!str_contains($oid, '.'))
        {
          $oid .= '.0';
        }

        $value1 = snmp_cache_oid($device1, $oid, $mib);
        $status1 = snmp_status();
        $value2 = snmp_cache_oid($device2, $oid, $mib);
        $status2 = snmp_status();
        if ($status1 && $status2)
        {
          // Do not compare if both values empty
          if ($value1 === '' && $value2 === '') { break; }

          $same = $value1 == $value2;
          if (!$same)
          {
            // Not same, break foreach
            if (OBS_DEBUG)
            {
              // print_warning("The compared oid differs on devices:");
              // print_cli_table([ [ $device1['hostname'], $value1 ],
              //                   [ $device2['hostname'], $value2 ] ], [ 'Device', $full_oid ]);
              $oids_compare[] = [ '%r'.$full_oid.'%n', $value1, $value2 ];
            }
            break 2;
          }
          elseif (OBS_DEBUG)
          {
            $oids_compare[] = [ $full_oid, $value1, $value2 ];
          }
        }
    }
  }

  if ($same && OBS_DEBUG)
  {
    // If anyway device detect as same, just resolve hostnames for debug
    if (!get_ip_version($device1['hostname']))
    {
      $ip1 = gethostbyname6($device1['hostname']);
    } else {
      $ip1 = $device1['hostname'];
    }
    if (!get_ip_version($device2['hostname']))
    {
      $ip2 = gethostbyname6($device2['hostname']);
    } else {
      $ip2 = $device2['hostname'];
    }

    array_unshift($oids_compare, [ '---', '---', '---' ]);
    array_unshift($oids_compare, [ 'Resolved IPs:', $ip1, $ip2 ]);
  }

  if (count($oids_compare))
  {
    print_warning("The compared oids on devices:");
    print_cli_table($oids_compare, [ 'Oid', $device1['hostname'] . ' (%gnew%n)', $device2['hostname'] . ' ('.$device2['device_id'].')' ]);
  }

  return $same;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/common.inc.php
function scan_port($host, $port, $proto = 'udp', $timeout = 1.0) {
  if (is_float($timeout)) {
    $msec = fmod($timeout, 1) * 1000;
  } else {
    $msec = 0;
  }
  if (!(get_ip_version($host) || is_valid_hostname($host))) {
    // not valid hostname/ip
    print_error("Invalid host $host passed.");
    return 0;
  }
  if (!str_istarts($proto, 'tcp')) {
    // default scan udp
    $host = 'udp://'.$host;
  }
  if (!is_valid_param($port, 'port')) {
    print_error("Invalid port $port passed.");
    return 0;
  }
  if ($handle = fsockopen($host, $port, $errno, $errstr, (float)$timeout)) {
    stream_set_timeout($handle, (int)$timeout, (int)$msec);
    $write = fwrite($handle, "\x00");
    if (!$write) {
      return 0;
    }

    $startTime = time();
    $header    = fread($handle, 1);
    $endTime   = time();
    $timeDiff  = $endTime - $startTime;

    fclose($handle);
    if ($timeDiff >= $timeout) {
      return $timeDiff;
    }
  }
  return 0;
}

function scanUDP($host, $port, $timeout) {
  return scan_port($host, $port, 'udp', $timeout);
}

/**
 * Checks device availability by snmp query common oids
 *
 * @param array $device Device array
 * @return float SNMP query runtime in milliseconds
 */
// TESTME needs unit testing
function isSNMPable($device) {
  if (isset($device['os'][0]) && isset($GLOBALS['config']['os'][$device['os']]['snmpable']) && $device['os'] !== 'generic') {
    // Known device os, and defined custom snmpable OIDs
    $pos   = snmp_get_multi_oid($device, $GLOBALS['config']['os'][$device['os']]['snmpable'], array(), 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_NUMERIC);
    //$err   = snmp_error_code();
    $count = safe_count($pos);
  } else {
    // Normal checks by sysObjectID and sysUpTime
    $pos = snmp_get_multi_oid($device, '.1.3.6.1.2.1.1.2.0 .1.3.6.1.2.1.1.3.0', [], 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_NUMERIC);
    //print_vars($pos);
    $count = safe_count($pos);
    /*
    $pos   = snmp_get_multi_oid($device, 'sysObjectID.0 sysUpTime.0', array(), 'SNMPv2-MIB');
    $count = safe_count($pos[0]);
    */
    $err   = snmp_error_code();

    if ($count === 0 &&
        $err !== OBS_SNMP_ERROR_AUTHENTICATION_FAILURE && $err !== OBS_SNMP_ERROR_UNSUPPORTED_ALGO && // skip on incorrect auth
        (empty($device['os']) || !isset($GLOBALS['config']['os'][$device['os']]))) {
      // New device (or os changed) try to all snmpable OIDs
      foreach (array_chunk($GLOBALS['config']['os']['generic']['snmpable'], 3) as $snmpable) {
        $pos = snmp_get_multi_oid($device, $snmpable, array(), 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_NUMERIC);
        if ($count = safe_count($pos)) { break; } // stop foreach on first oids set
      }
    }
  }

  if ($GLOBALS['snmp_status'] && $count > 0) {
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
    $ip = ($try_a ? gethostbyname6($hostname, OBS_DNS_A) : FALSE); // Do not check IPv4 if transport IPv6
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

// Allows overwriting elements of an array of OIDs with replacement values from a private MIB.
// Currently used by ports to replace OIDs with private ports tables.

function merge_private_mib(&$device, $entity_type, $mib, &$entity_stats, $limit_oids = NULL) {
  global $config;

  $oids_limited = safe_count($limit_oids);

  foreach ($config['mibs'][$mib][$entity_type] as $table => $def) {

    // Skip unknown definitions..
    if (!isset($def['oids']) || !is_array($def['oids'])) { continue; }

    print_cli_data_field($mib);
    echo $table . ' ';

    $walked = array();

    // Walk to $entity_tmp, link to $entity_stats if we're not rewriting
    if (isset($def['map'])) {
      $entity_tmp = array();
      if (isset($def['map']['oid'])) {
        echo $def['map']['oid'] . ' ';
        $entity_tmp = snmpwalk_cache_oid($device, $def['map']['oid'], $entity_tmp, $mib);

        // Skip next Oids walk if no response
        if (!snmp_status()) { continue; }

        $walked[] = $def['map']['oid'];
      }
      foreach ((array)$def['map']['oid_extra'] as $oid) {
        echo $oid . ' ';
        $entity_tmp = snmpwalk_cache_oid($device, $oid, $entity_tmp, $mib);
        $walked[] = $oid;
      }
    } else {
      $entity_tmp = &$entity_stats;
    }

    // Populated $entity_tmp
    foreach ($def['oids'] as $oid => $entry) {
      // Skip if there's an OID list and we're not on it.
      if ($oids_limited && !in_array($oid, (array)$limit_oids, TRUE)) { continue; }

      // If this OID is being used twice, don't walk it again.
      if (!isset($entry['oid']) || in_array($entry['oid'], $walked)) { continue; }

      echo $entry['oid'] . ' ';
      $flags = isset($entry['snmp_flags']) ? $entry['snmp_flags'] : OBS_SNMP_ALL;
      $entity_tmp = snmpwalk_cache_oid($device, $entry['oid'], $entity_tmp, $mib, NULL, $flags);

      // Skip next Oids walk if no response
      if (!snmp_status() && $oid == array_key_first($def['oids'])) { continue 2; }

      $walked[] = $entry['oid'];
    }
    print_debug_vars($entity_tmp);

    // Rewrite indexes using map from $entity_tmp to $entity_stats
    if (isset($def['map'])) {
      $entity_new = array();
      $map_tmp = array();
      // Generate mapping list
      if (isset($def['map']['index'])) {
        // Index by tags
        foreach ($entity_tmp as $index => $entry) {
          $entry['index'] = $index;
          foreach (explode('.', $index) as $k => $idx) {
            $entry['index'.$k] = $idx;
          }
          $map_index = array_tag_replace($entry, $def['map']['index']);
          $map_tmp[$index] = $map_index;
        }
      } else {
        // Mapping by Oid
        foreach ($entity_tmp as $index => $entry) {
          if (isset($entry[$def['map']['oid']])) {
            $map_tmp[$index] = $entry[$def['map']['oid']];
          }
        }
      }
      print_debug_vars($map_tmp);

      foreach ($entity_tmp as $index => $entry) {
        if (isset($map_tmp[$index])) {
          foreach ($entry as $oid => $value) {
            $entity_new[$map_tmp[$index]][$oid] = $value;
          }
        }
      }
    } else {
      $entity_new = $entity_tmp;
    }

    echo '['; // start change list

    foreach ($entity_new as $index => $port) {
      foreach ($def['oids'] as $oid => $entry) {
        // Skip if there's an OID list and we're not on it.
        if ($oids_limited && !in_array($oid, (array)$limit_oids, TRUE)) {
          continue;
        }

        $mib_oid = $entry['oid'];
        if (isset($entry['oid']) && isset($port[$entry['oid']])) {

          if (isset($entry['transform'])) {
            $entry['transformations'] = $entry['transform'];
          }
          if (isset($entry['transformations'])) {
            // Translate to standard IF-MIB values
            $port[$entry['oid']] = string_transform($port[$entry['oid']], $entry['transformations']);
            echo 'T';
          }

          if (isset($entry['unit']) && str_ends($entry['unit'], 'ps')) { // bps, pps
            // Set suffixied Oid instead main
            // Currently used for Rate Oids, see SPECTRA-LOGIC-STRATA-MIB definition
            $entity_stats[$index][$oid.'_rate'] = $port[$entry['oid']];
          } else {
            $entity_stats[$index][$oid] = $port[$entry['oid']];
          }
          echo '.';
        } elseif (isset($entry['value'])) {
          // Set fixed value
          $entity_stats[$index][$oid] = $entry['value'];
        } else {
          echo '!';
        }
      }
    }
    echo ']'; // end change list
    echo PHP_EOL; // end CLI DATA FIELD
  }

  return NULL;
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

function log_event_process(&$text, &$device = NULL, &$type = NULL, &$reference = NULL, &$severity = 6) {
  if (is_null($device) && is_null($type)) {
    // Without device and type - is global events
    $type = 'global';
  } else {
    $type = strtolower($type);
  }
  $entity_id = $reference;

  // Global events not have device_id
  if (!in_array($type, [ 'global', 'info' ], TRUE)) {
    if (!is_array($device) && is_intnum($device)) {
      $device = device_by_id_cache($device);
    }
    // Do not log events if device id not found
    if (!is_array($device)) {
      return FALSE;
    }
    // Do not log events if device ignored
    if ($device['ignore'] && $type !== 'device') {
      return FALSE;
    }

    // Type is valid entity
    if (isset($GLOBALS['config']['entities'][$type])) {
      $translate = entity_type_translate_array($type);
      if (is_array($reference)) {
        $entity    = $reference;
        $entity_id = $entity[$translate['id_field']];
        //$reference = $entity[$translate['id_field']];
      } else {
        $entity = get_entity_by_id_cache($type, $reference);
      }
      // Do not log events if entity ignored
      if (isset($translate['ignore_field']) && $entity[$translate['ignore_field']]) {
        return FALSE;
      }
    }
  }

  $severity = priority_string_to_numeric($severity); // Convert named severities to numeric
  if (($type === 'device' && $severity == 5) || isset($_SESSION['username'])) // Severity "Notification" additional log info about username or cli
  {
    $severity = ($severity == 6 ? 5 : $severity); // If severity default, change to notification
    if (isset($_SESSION['username'])) {
      $text .= ' (by user: ' . $_SESSION['username'] . ')';
    } elseif (is_cli()) {
      if (is_cron()) {
        $text .= ' (by cron)';
      } else {
        $text .= ' (by console, user ' . get_localuser() . ')';
      }
    }
  }

  return [
    'message'     => $text,
    'device_id'   => $device['device_id'],
    'entity_id'   => $entity_id,
    'entity_type' => $type,
    'severity'    => $severity
  ];
}

/**
 * Use this function to write to the eventlog table
 *
 * @param string    $text      Message text
 * @param int|array $device    Device array or device id
 * @param string    $type      Entity type (ie port, device, global)
 * @param int|array $reference Reference ID to current entity type
 * @param int       $severity  Event severity (0 - 8)
 * @return int                 Event DB id
 */
// TESTME needs unit testing
function log_event($text, $device = NULL, $type = NULL, $reference = NULL, $severity = 6) {

  $event = log_event_process($text, $device, $type, $reference, $severity);
  if (!is_array($event)) { return $event; }

  $insert = [
    'device_id'   => ($event['device_id'] ?: 0), // Currently db schema not allow NULL value for device_id
    'entity_id'   => (is_numeric($event['entity_id']) ? $event['entity_id'] : [ 'NULL' ]),
    'entity_type' => ($event['entity_type'] ?: [ 'NULL' ]),
    'timestamp'   => [ "NOW()" ],
    'severity'    => $event['severity'],
    'message'     => $event['message']
  ];

  return dbInsert($insert, 'eventlog');
}

/**
 * Use this function to write to the eventlog table.
 * Unlike the function log_event() this write events to cache and pull to db at end of process.
 * Also many of the same log entries are combined into one with repeated counter.
 *
 * @param string    $text      Message text
 * @param int|array $device    Device array or device id
 * @param string    $type      Entity type (ie port, device, global)
 * @param int|array $reference Reference ID to current entity type
 * @param int       $severity  Event severity (0 - 8)
 * @return int                 Event DB id
 */
function log_event_cache($text, $device = NULL, $type = NULL, $reference = NULL, $severity = 6) {
  global $cache;

  // Magic key for write/pull all cached events
  if ($text === TRUE || in_array(strtolower($text), [ 'write', 'pull' ])) {
    $insert = [];
    foreach ($cache['log_events'] as $log_type => $e1) {
      foreach ($e1 as $log_message => $e2) {

        if ($log_type === 'global') {
          $message = $e2['count'] > 1 ? $log_message . " (repeated ".$e2['count']." times)" : $log_message;

          $insert[] = [
            'device_id'   => 0, // Currently db schema not allow NULL value for device_id
            'entity_id'   => [ 'NULL' ],
            'entity_type' => $log_type,
            'timestamp'   => [ "NOW()" ],
            'severity'    => $e2['severity'],
            'message'     => $message
          ];
        } else {
          foreach ($e2 as $device_id => $e3) {
            $message = $e3['count'] > 1 ? $log_message . " (repeated ".$e3['count']." times)" : $log_message;

            $insert[] = [
              'device_id'   => ($device_id ?: 0), // Currently db schema not allow NULL value for device_id
              'entity_id'   => (is_numeric($e3['entity_id']) ? $e3['entity_id'] : [ 'NULL' ]),
              'entity_type' => ($log_type ?: [ 'NULL' ]),
              'timestamp'   => [ "NOW()" ],
              'severity'    => $e3['severity'],
              'message'     => $message
            ];
          }
        }
      }
    }
    print_debug_vars($insert);
    // pull events to db
    dbInsertMulti($insert, 'eventlog');

    // clear cache after pull
    unset($cache['log_events']);
    return TRUE;
  }

  // Register shutdown function for write cached event logs (on first log_event_cache() call)
  if (!isset($cache['log_events'])) {
    register_shutdown_function('log_event_cache', TRUE); // log_event_cache(TRUE) writes cached logs to db
  }

  // Process log entries
  $event = log_event_process($text, $device, $type, $reference, $severity);
  if (!is_array($event)) { return $event; }

  // Cache log entries
  if ($type === 'global') {
    if (!isset($cache['log_events'][$type][$text])) {
      $cache['log_events'][$type][$text] = [
        'count' => 0,
        'severity' => $severity
      ];
    }
    $cache['log_events'][$type][$text]['count']++;
    $cache['log_events'][$type][$text]['severity'] = min($cache['log_events'][$type][$text]['severity'], $severity);

    return $cache['log_events'][$type][$text];
  } else {
    $device_id = $event['device_id'] ?: 0;
    if (!isset($cache['log_events'][$type][$text][$device_id])) {
      $cache['log_events'][$type][$text][$device_id] = [
        'count'     => 0,
        'severity'  => $severity,
        'entity_id' => $reference
      ];
    }
    $cache['log_events'][$type][$text][$device_id]['count']++;
    $cache['log_events'][$type][$text][$device_id]['severity'] = min($cache['log_events'][$type][$text][$device_id]['severity'], $severity);

    return $cache['log_events'][$type][$text][$device_id];
  }
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
function str2hex($string) {
  $hex = '';
  $len = strlen($string);
  for ($i = 0; $i < $len; $i++) {
    $char = dechex(ord($string[$i]));
    if (strlen($char) === 1) {
      $char = '0'.$char;
    }
    $hex .= $char;
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
  elseif (is_numeric($ord))
  {
    $ord = intval($ord);
  }
  elseif (preg_match('/^[\p{L}]+$/u', $ord))
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
  elseif ($ord < 2048)
  {
    $no_bytes = 2;
  }
  elseif ($ord < 65536)
  {
    $no_bytes = 3;
  }
  elseif ($ord < 1114112)
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
  return (bool) preg_match('/^' . OBS_PATTERN_SNMP_HEX . '$/is', $str);
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

function get_defined_settings($key = NULL) {
  $config = [];
  include($GLOBALS['config']['install_dir'] . "/config.php");

  if ($key) {
    // return specific setting if defined
    return array_get_nested($config, $key);
  }

  // never store this option(s) in memory! Use get_defined_settings($key)
  foreach ($GLOBALS['config']['hide_config'] as $opt) {
    if (array_get_nested($config, $opt)) { unset($config[$opt]); }
  }

  return $config;
}

function get_default_settings($key = NULL) {
  $config = [];
  include($GLOBALS['config']['install_dir'] . "/includes/defaults.inc.php");

  if ($key) {
    // return specific setting if defined
    return array_get_nested($config, $key);
  }

  return $config;
}

function get_config_json($filter = NULL, $print = TRUE) {
  global $config;

  if (safe_empty($filter)) {
    if ($print) {
      echo safe_json_encode($config);
      return;
    }
    return safe_json_encode($config);
  }

  if (!is_array($filter)) {
    $filter = explode(',', $filter);
  }
  $json_config = [];
  foreach ($filter as $key) {
    if (array_key_exists($key, $config)) {
      $json_config[$key] = $config[$key];
    }
  }

  if ($print) {
    echo safe_json_encode($json_config);
  } else {
    return safe_json_encode($json_config);
  }
}

// Load configuration from SQL into supplied variable (pass by reference!)
function load_sqlconfig(&$config) {
  $config_defined = get_defined_settings(); // defined in config.php

  // Override some whitelisted definitions from config.php
  foreach ($config_defined as $key => $definition) {
    //if (is_null($config['definitions_whitelist'])) { print_error("NULL on $key"); } else { print_warning("ARRAY on $key"); }
    if (in_array($key, $GLOBALS['config']['definitions_whitelist']) && // Always use global config here!
        is_array($definition) && is_array($config[$key]))
    {
      /* Fix mib definitions for dumb users, who copied old defaults.php
         where mibs was just MIB => 1,
         This definition should be array */
      // Fetch first element and validate that this is array
      if ($key === 'mibs' && !is_array(array_shift(array_values($definition)))) { continue; }

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
        $config[$tree[0]] = safe_unserialize($item['config_value']);
        break;
      case 2:
        if (isset($config_defined[$tree[0]][$tree[1]])) { break; } // Note, false for null values
        $config[$tree[0]][$tree[1]] = safe_unserialize($item['config_value']);
        break;
      case 3:
        if (isset($config_defined[$tree[0]][$tree[1]][$tree[2]])) { break; } // Note, false for null values
        $config[$tree[0]][$tree[1]][$tree[2]] = safe_unserialize($item['config_value']);
        break;
      case 4:
        if (isset($config_defined[$tree[0]][$tree[1]][$tree[2]][$tree[3]])) { break; } // Note, false for null values
        $config[$tree[0]][$tree[1]][$tree[2]][$tree[3]] = safe_unserialize($item['config_value']);
        break;
      case 5:
        if (isset($config_defined[$tree[0]][$tree[1]][$tree[2]][$tree[3]][$tree[4]])) { break; } // Note, false for null values
        $config[$tree[0]][$tree[1]][$tree[2]][$tree[3]][$tree[4]] = safe_unserialize($item['config_value']);
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
      $a = (string)$a;
      $b = (string)$b;
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
      $a = (string)$a;
      $b = (string)$b;
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
function gmp_init_float($value) {
  if (is_intnum($value)) {
    return $value;
  }
  if (is_float($value)) {
    return sprintf("%.0f", $value);
  }
  if (str_contains($value, '.')) {
    // Return int part of string
    list($value) = explode('.', $value);
    //return $value;
  }
  if (safe_empty($value)) {
    return 0;
  }

  return (string)$value;
}

// Translate syslog priorities from string to numbers
// ie: ('emerg','alert','crit','err','warning','notice') >> ('0', '1', '2', '3', '4', '5')
// Note, this is safe function, for unknown data return 15
// DOCME needs phpdoc block
function priority_string_to_numeric($value) {
  $priority = 15; // Default priority for unknown data
  if (!is_numeric($value)) {
    foreach ($GLOBALS['config']['syslog']['priorities'] as $pri => $entry) {
      if (is_string($value) && str_istarts($entry['name'], substr($value, 0, 3))) {
        $priority = $pri;
        break;
      }
    }
  } elseif ($value == (int)$value && $value >= 0 && $value < 16) {
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
  str_pad(OBSERVIUM_URL , 59, " ", STR_PAD_LEFT)."%N\n", 'color');

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
| %WCurrently recommended version(s): >%g7.2.x%n                |
|                                                         |
| See additional information here:                        |
| %c".
  str_pad(OBSERVIUM_DOCS_URL . '/software_requirements/' , 56, ' ')."%n|
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
 * Creates a list of php files available in the html/includes/authentication directory, to show in a
 * dropdown on the web configuration page.
 *
 * @return array List of authentication modules available
 */
function config_get_auth_modules()
{
  global $config;

  $authmodules = array();

  foreach (get_recursive_directory_iterator($config['html_dir'] . '/includes/authentication') as $file => $info) {
    if (str_ends($file, '.inc.php')) {
      $auth = basename($file, '.inc.php');
      $authmodules[$auth] = nicecase($auth);
    }
  }

  return $authmodules;
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
function force_discovery($device, $modules = []) {
  $return = FALSE;

  if (safe_empty($modules)) {
    // Modules not passed, just full rediscover device
    return dbUpdate([ 'force_discovery' => 1 ], 'devices', '`device_id` = ?', array($device['device_id']));
  }

  // Modules passed, check if modules valid and enabled
  $modules = (array)$modules;
  $forced_modules = get_entity_attrib('device', $device['device_id'], 'force_discovery_modules');
  if ($forced_modules) {
    // Already forced modules exist, merge it with new
    $modules = array_unique(array_merge($modules, safe_json_decode($forced_modules)));
  }

  $valid_modules = [];
  foreach ($GLOBALS['config']['discovery_modules'] as $module => $ok) {
    // Filter by valid and enabled modules
    if ($ok && in_array($module, $modules)) {
      $valid_modules[] = $module;
    }
  }

  if (count($valid_modules)) {
    $return = dbUpdate(array('force_discovery' => 1), 'devices', '`device_id` = ?', array($device['device_id']));
    set_entity_attrib('device', $device['device_id'], 'force_discovery_modules', safe_json_encode($valid_modules));
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
 * @param numeric $scale Scaling to apply to the supplied values.
 * @param numeric $used  Used value of mempool, before scaling, or NULL.
 * @param numeric $total Total value of mempool, before scaling, or NULL.
 * @param numeric $free  Free value of mempool, before scaling, or NULL.
 * @param numeric $perc  Used percentage value of mempool, or NULL.
 * @param array $options Additional options, ie separate scales for used/total/free
 *
 * @return array Array consisting of 'used', 'total', 'free' and 'perc' fields
 */
function calculate_mempool_properties($scale, $used, $total, $free, $perc = NULL, $options = []) {
  // Scale, before maths!
  if ($scale == 0) { $scale = 1; }

  $numeric = [ 'perc' => is_numeric($perc) ];
  foreach ([ 'total', 'used', 'free' ] as $param) {
    $numeric[$param] = is_numeric($$param);
    if ($numeric[$param]) {
      if (isset($options['scale_'.$param])) {
        // Separate scale for current param
        $$param *= $options['scale_'.$param];
      } elseif ($scale != 1) {
        // Common scale
        $$param *= $scale;
      }
    }
  }
  print_debug_vars($numeric);

  if ($numeric['total'] && $numeric['free']) {
    $used = $total - $free;
    $perc = $total != 0 ? round($used / $total * 100, 2) : 0;
  } elseif ($numeric['used'] && $numeric['free']) {
    $total = $used + $free;
    $perc = $total != 0 ? round($used / $total * 100, 2) : 0;
  } elseif ($numeric['total'] && $numeric['perc']) {
    $used = $total * $perc / 100;
    $free = $total - $used;
  } elseif ($numeric['total'] && $numeric['used']) {
    $free = $total - $used;
    $perc = $total != 0 ? round($used / $total * 100, 2) : 0;
  } elseif ($numeric['perc']) {
    $total  = 100;
    $used   = $perc;
    $free   = 100 - $perc;
    //$scale  = 1; // Reset scale for percentage-only
  }

  if (OBS_DEBUG && ($perc < 0 || $perc > 100)) {
    print_error('Incorrect scales or passed params to function ' . __FUNCTION__ . '()');
  }
  print_debug_vars([ 'used' => $used, 'total' => $total, 'free' => $free, 'perc' => $perc, 'units' => $scale, 'scale' => $scale ]);

  return [ 'used' => $used, 'total' => $total, 'free' => $free, 'perc' => $perc, 'units' => $scale, 'scale' => $scale ];
}

function discovery_check_if_type_exist($entry, $entity_type, $entity = []) {

  if (is_string($entry) || is_array_seq($entry)) {
    $skip_if_valid_exist = $entry;
  } elseif (isset($entry['skip_if_valid_exist'])) {
    $skip_if_valid_exist = $entry['skip_if_valid_exist'];
  } else {
    return FALSE;
  }

  $valid = &$GLOBALS['valid'];

  foreach ((array)$skip_if_valid_exist as $skip_entry) {
    // use %index% tags
    if (!empty($entity) && str_contains($skip_entry, '%')) {
      $skip_entry = array_tag_replace($entity, $skip_entry);
    }
    // FIXME. Need switch to array_get_nested()
    //array_get_nested($valid[$entity_type], $skip_entry);
    $tree = explode('->', $skip_entry);
    //print_vars($tree);
    switch (count($tree)) {
      case 1:
        if (is_array($valid[$entity_type]) &&
            array_key_exists($tree[0], $valid[$entity_type])) {

          print_debug("Excluded by valid exist: " . $skip_entry);
          return TRUE;
        }
        break;

      case 2:
        if (is_array($valid[$entity_type][$tree[0]]) &&
            array_key_exists($tree[1], $valid[$entity_type][$tree[0]])) {

          print_debug("Excluded by valid exist: " . $skip_entry);
          return TRUE;
        }
        break;

      case 3:
        if (is_array($valid[$entity_type][$tree[0]][$tree[1]]) &&
            array_key_exists($tree[2], $valid[$entity_type][$tree[0]][$tree[1]]))
        {
          print_debug("Excluded by valid exist: " . $skip_entry);

          return TRUE;
        }
        break;
      default:
        print_debug("Too many array levels for valid sensor!");
    }
  }

  return FALSE;
}

function discovery_check_requires_pre($device, $entry, $entity_type = NULL) {

  if (isset($entry['test_pre']) && !isset($entry['pre_test'])) {
    // DERP. I keep forgetting how to do it right.
    $entry['pre_test'] = $entry['test_pre'];
    unset($entry['test_pre']);
  }

  if (isset($entry['pre_test']) && is_array($entry['pre_test'])) {
    // Convert single test condition to multi-level condition
    if (isset($entry['pre_test']['operator'])) {
      $entry['pre_test'] = array($entry['pre_test']);
    }

    foreach ($entry['pre_test'] as $test) {
      if (isset($test['oid'])) {
        // Fetch just the value eof the OID.
        if (isset($entry['mib']) && !str_contains($test['oid'], '::')) {
          $test['data'] = snmp_cache_oid($device, $test['oid'], $entry['mib'], NULL, OBS_SNMP_ALL);
          $oid = $entry['mib'] . '::' . $test['oid'];
        } else {
          $test['data'] = snmp_cache_oid($device, $test['oid'], NULL, NULL, OBS_SNMP_ALL);
          $oid = $test['oid'];
        }
      } elseif (isset($test['field'])) {
        $test['data'] = $entry[$test['field']];
        $oid = $test['field'];
      } elseif (isset($test['device_field']) && array_key_iexists($test['device_field'], $device)) {
        // compare by device field(s), ie: sysObjectId, sysName
        if (isset($device[$test['device_field']])) {
          $test['data'] = $device[$test['device_field']];
          $oid = 'Device ' . $test['device_field'];
        } else {
          // case-insensitive
          $test['device_field'] = strtolower($test['device_field']);
          foreach ($device as $key => $value) {
            if (strtolower($key) === $test['device_field']) {
              $test['data'] = $value;
              $oid = 'Device ' . $key;
              break;
            }
          }
        }
      } else {
        print_debug("Not correct Field (".$test['field'].") passed to discovery_check_requires(). Need add it to 'oid_extra' definition.");
        return FALSE;
      }

      if (test_condition($test['data'], $test['operator'], $test['value']) === FALSE) {
        print_debug("Excluded by not test condition: $oid [".$test['data']."] ".$test['operator']." [".implode(', ', (array)$test['value'])."]");
        return TRUE;
      }
    }
  }

  return FALSE;
}

function discovery_check_requires($device, $entry, $array, $entity_type = NULL) {
  if (isset($entry['test']) && is_array($entry['test'])) {
    // Convert single test condition to multi-level condition
    if (isset($entry['test']['operator'])) {
      $entry['test'] = array($entry['test']);
    }

    $test_and = FALSE;
    $test_count = count($entry['test']);
    $debug_array = [];

    //print_debug_vars($entry['test']);
    //print_debug_vars($array);
    foreach ($entry['test'] as $test) {
      if (isset($test['oid'])) {
        // Fetch just the value eof the OID.
        if (isset($entry['mib']) && !str_contains($test['oid'], '::')) {
          $test['data'] = snmp_cache_oid($device, $test['oid'], $entry['mib'], NULL, OBS_SNMP_ALL);
          $oid = $entry['mib'] . '::' . $test['oid'];
        } else {
          $test['data'] = snmp_cache_oid($device, $test['oid'], NULL, NULL, OBS_SNMP_ALL);
          $oid = $test['oid'];
        }
      } elseif (isset($test['field'])) {
        $test['data'] = $array[$test['field']];
        if (!isset($array[$test['field']]) && !str_contains($test['operator'], 'null')) {
          // Show debug error (some time Oid fetched, but not exist for current index)
          print_debug("Not correct Field (" . $test['field'] . ") passed to discovery_check_requires(). Need add it to 'oid_extra' definition.");
          //return FALSE;
        }
        $oid = $test['field'];
      }

      // If 'and' is TRUE, check all conditions
      if (isset($test['and'])) {
        $test_and = $test_and || $test['and'];
      }

      $debug_array[] = "$oid [".$test['data']."] ".$test['operator']." [".implode(', ', (array)$test['value'])."]";
      if (test_condition($test['data'], $test['operator'], $test['value']) === FALSE) {
        if ($test_and) { continue; }

        print_debug("Excluded by not test condition: $oid [".$test['data']."] ".$test['operator']." [".implode(', ', (array)$test['value'])."]");
        return TRUE;
      }
      $test_count--;
    }
  }

  if ($test_and && $test_count > 0) {
    print_debug("Excluded by not all test conditions:\n  " . implode("\n  ", $debug_array));
    return TRUE;
  }

  return FALSE;
}


// EOF
