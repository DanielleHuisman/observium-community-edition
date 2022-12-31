<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage common
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

// Common Functions
/// FIXME. There should be functions that use only standard php (and self) functions.

/**
 * Autoloader for Classes used in Observium
 *
 */
function observium_autoload($class_name) {
  //var_dump($class_name);
  if (isset($GLOBALS['config']['install_dir'])) {
    $base_dir = $GLOBALS['config']['install_dir'] . '/libs/';
  } else {
    // not know why in phpunit $GLOBALS and $config reset on this stage
    $base_dir = dirname(__DIR__) . '/libs/';
  }

  $class_array = explode('\\', $class_name);
  $class_file  = str_replace('_', '/', implode('/', $class_array)) . '.php';
  //print_vars($class_array);
  switch ($class_array[0]) {
    case 'cli':
      include_once($base_dir . 'cli/cli.php'); // Cli classes required base functions
      $class_file = str_replace('/Table/', '/table/', $class_file);
      //var_dump($class_file);
      break;

    case 'Phpfastcache':
    case 'phpFastCache':
      if (PHP_VERSION_ID >= 70300) {
        $class_array[0] = 'Phpfastcache8';
        $class_file     = str_replace('_', '/', implode('/', $class_array)) . '.php';
      }
      break;

    case 'Flight':
      $class_file = array_pop($class_array) . '.php';
      if (PHP_VERSION_ID >= 70400) {
        $class_file = 'flight2/' . $class_file;
      } else {
        // Old compat version
        $class_file = 'flight/' . $class_file;
      }
      break;

    case 'Ramsey':
      if (PHP_VERSION_ID >= 80000 && $class_array[1] === 'Uuid') {
        // PHP 7.2+ (for 8.1 required)
        //$class_array[1] = 'Uuid4';
        $class_file = str_replace('/Uuid/', '/Uuid4/', $class_file);
        //$class_file     = str_replace('_', '/', implode('/', $class_array)) . '.php';
      }
      break;

    case 'Defuse':
    case 'donatj':
      $class_file = str_replace($class_array[0] . '/', '', $class_file);

      // Initial base class file
      $class_file_base = $base_dir . end($class_array) . '.php';
      if (is_file($class_file_base)) {
        $base_status = include_once($class_file_base);
        if (defined('OBS_DEBUG') && OBS_DEBUG > 1) {
          print_message("%WLoad base file for class '$class_name' from '$class_file_base': " . ($base_status ? '%gOK' : '%rFAIL'), 'console');
        }
      }
      break;

    case 'PhpUnitsOfMeasure':
      include_once($base_dir . 'PhpUnitsOfMeasure/UnitOfMeasureInterface.php');
      break;

    default:
      if (strpos($class_name, 'Parsedown') === 0) {
        $class_file = 'parsedown/' . $class_file;
      } elseif (is_file($base_dir . 'pear/' . $class_file)) {
        // By default try Pear file
        $class_file = 'pear/' . $class_file;
      } elseif (is_dir($base_dir . 'pear/' . $class_name)) {
        // And Pear dir
        $class_file = 'pear/' . $class_name . '/' . $class_file;
      }
      //elseif (!is_cli() && is_file($GLOBALS['config']['html_dir'] . '/includes/' . $class_file))
      //{
      //  // For WUI check class files in html_dir
      //  $base_dir   = $GLOBALS['config']['html_dir'] . '/includes/';
      //}
  }
  $full_path = $base_dir . $class_file;

  $status = is_file($full_path);
  if ($status) {
    $status = include_once($full_path);
  }
  if (defined('OBS_DEBUG') && OBS_DEBUG > 1) {
    print_message("%WLoad class '$class_name' from '$full_path': " . ($status ? '%gOK' : '%rFAIL'), 'console');
  }
  return $status;
}
// Register autoload function
spl_autoload_register('observium_autoload');

// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/functions.inc.php
function del_obs_attrib($attrib_type)
{
  if (isset($GLOBALS['cache']['attribs'])) { unset($GLOBALS['cache']['attribs']); } // Reset attribs cache

  return dbDelete('observium_attribs', "`attrib_type` = ?", array($attrib_type));
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/functions.inc.php
function set_obs_attrib($attrib_type, $attrib_value)
{
  if (isset($GLOBALS['cache']['attribs'])) { unset($GLOBALS['cache']['attribs']); } // Reset attribs cache

  //if (dbFetchCell("SELECT COUNT(*) FROM `observium_attribs` WHERE `attrib_type` = ?;", array($attrib_type)))
  if (dbExist('observium_attribs', '`attrib_type` = ?', array($attrib_type)))
  {
    $status = dbUpdate(array('attrib_value' => $attrib_value), 'observium_attribs', "`attrib_type` = ?", array($attrib_type));
  } else {
    $status = dbInsert(array('attrib_type' => $attrib_type, 'attrib_value' => $attrib_value), 'observium_attribs');
    if ($status !== FALSE) { $status = TRUE; } // Note dbInsert return IDs if exist or 0 for not indexed tables
  }
  return $status;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/functions.inc.php
function get_obs_attribs($type_filter)
{
  if (!isset($GLOBALS['cache']['attribs']))
  {
    $attribs = array();
    foreach (dbFetchRows("SELECT * FROM `observium_attribs`") as $entry)
    {
      $attribs[$entry['attrib_type']] = $entry['attrib_value'];
    }
    $GLOBALS['cache']['attribs'] = $attribs;
  }

  if (strlen($type_filter))
  {
    $attribs = array();
    foreach ($GLOBALS['cache']['attribs'] as $type => $value)
    {
      if (strpos($type, $type_filter) !== FALSE)
      {
        $attribs[$type] = $value;
      }
    }
    return $attribs; // Return filtered attribs
  }

  return $GLOBALS['cache']['attribs']; // All cached attribs
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// MOVEME to includes/functions.inc.php
function get_obs_attrib($attrib_type) {
  if (isset($GLOBALS['cache']['attribs'][$attrib_type])) {
    return $GLOBALS['cache']['attribs'][$attrib_type];
  }

  if ($row = dbFetchRow("SELECT `attrib_value` FROM `observium_attribs` WHERE `attrib_type` = ?;", [ $attrib_type ])) {
    return $row['attrib_value'];
  }

  return NULL;
}

// FIXME. Function temporary placed here, since cache_* functions currently included in WUI only.
// MOVEME includes/cache.inc.php
/**
 * Add clear cache attrib, this will request for clearing cache in next request.
 *
 * @param string $target Clear cache target: wui or cli (default if wui)
 */
function set_cache_clear($target = 'wui') {
  if (OBS_DEBUG || (defined('OBS_CACHE_DEBUG') && OBS_CACHE_DEBUG)) {
    print_error('<span class="text-warning">CACHE CLEAR SET.</span> Cache clear set.');
  }
  if (!$GLOBALS['config']['cache']['enable']) {
    // Cache not enabled
    return;
  }

  switch (strtolower($target)) {
    case 'cli':
      // Add clear CLI cache attrib. Currently not used
      set_obs_attrib('cache_cli_clear', get_request_id());
      break;
    default:
      // Add clear WUI cache attrib
      set_obs_attrib('cache_wui_clear', get_request_id());
  }
}

function set_status_var($var, $value) {
  $GLOBALS['cache']['status_vars'][$var] = $value;
  return TRUE;
}

function isset_status_var($var) {
  return array_key_exists($var, (array)$GLOBALS['cache']['status_vars']);
}

function get_status_var($var) {
  return $GLOBALS['cache']['status_vars'][$var];
}

/**
 * Generate and store Unique ID for current system. Store in DB at first run.
 *  IDs is RFC 4122 version 4 (without dashes, varchar(32)), i.e. c39b2386c4e8487fad4a87cd367b279d
 *
 * @return string Unique system ID
 * @throws Exception
 */
function get_unique_id() {
  if (!defined('OBS_UNIQUE_ID')) {
    $unique_id = get_obs_attrib('unique_id');

    if (safe_empty($unique_id)) {
      // Generate a version 4 (random) UUID object
      $uuid4 = Ramsey\Uuid\Uuid::uuid4();
      //$unique_id = $uuid4->toString(); // i.e. c39b2386-c4e8-487f-ad4a-87cd367b279d
      $unique_id = $uuid4->getHex();   // i.e. c39b2386c4e8487fad4a87cd367b279d
      dbInsert(array('attrib_type' => 'unique_id', 'attrib_value' => $unique_id), 'observium_attribs');
    }
    define('OBS_UNIQUE_ID', $unique_id);
  }

  return OBS_UNIQUE_ID;
}

/**
 * Generate and store Unique Request ID for current script/page.
 * ID unique between 2 different requests or page loads
 *  IDs is RFC 4122 version 4, i.e. 25769c6c-d34d-4bfe-ba98-e0ee856f3e7a
 *
 * @return string Unique Request ID
 * @throws Exception
 */
function get_request_id() {
  if (!defined('OBS_REQUEST_ID')) {
    // Generate a version 4 (random) UUID object
    $uuid4 = Ramsey\Uuid\Uuid::uuid4();
    $request_id = $uuid4->toString(); // i.e. 25769c6c-d34d-4bfe-ba98-e0ee856f3e7a
    define('OBS_REQUEST_ID', $request_id);
  }

  return OBS_REQUEST_ID;
}

/**
 * Set new DB Schema version
 *
 * @param integer $db_rev New DB schema revision
 * @param boolean $schema_insert Update (by default) or insert by first install db schema
 * @return boolean Status of DB schema update
 */
function set_db_version($db_rev, $schema_insert = FALSE) {
  if ($db_rev >= 211) { // Do not remove this check, since before this revision observium_attribs table not exist!
    $status = set_obs_attrib('dbSchema', $db_rev);
  } else {
    if ($schema_insert) {
      $status = dbInsert(array('version' => $db_rev), 'dbSchema');
      if ($status !== FALSE) { $status = TRUE; } // Note dbInsert return IDs if exist or 0 for not indexed tables
    } else {
      $status = dbUpdate(array('version' => $db_rev), 'dbSchema');
    }
  }

  if ($status) {
    $GLOBALS['cache']['db_version'] = $db_rev; // Cache new db version
  }

  return $status;
}

/**
 * Get current DB Schema version
 *
 * @return string DB schema version
 */
// TESTME needs unit testing
function get_db_version() {
  if (!isset($GLOBALS['cache']['db_version'])) {
    $db_rev = @get_obs_attrib('dbSchema');
    if (!$db_rev) {
      $db_rev = 0;
    }
    $db_rev = (int)$db_rev;
    if ($db_rev > 0) {
      $GLOBALS['cache']['db_version'] = $db_rev;
    } else {
      // Do not cache zero value
      return $db_rev;
    }
  }
  return $GLOBALS['cache']['db_version'];
}

/**
 * Get current DB Size
 *
 * @return string DB size in bytes
 */
// TESTME needs unit testing
function get_db_size() {
  return dbFetchCell('SELECT SUM(`data_length` + `index_length`) AS `size` FROM `information_schema`.`tables` WHERE `table_schema` = ?;', array($GLOBALS['config']['db_name']));
}

/**
 * Get unique local id.
 * Need to identify poller system.
 *
 * @return string
 */
function get_local_id() {
  // http://0pointer.de/blog/projects/ids.html
  switch (PHP_OS) {
    case 'Linux':
      // Note. system-uuid is good, but available only for root
      if (is_file('/etc/machine-id')) {
        // 1d56dd4b3c334a20bff1fc4878b9e1ee
        return trim(file_get_contents('/etc/machine-id'));
      }
      if (is_file('/var/lib/dbus/machine-id')) {
        return trim(file_get_contents('/var/lib/dbus/machine-id'));
      }
      break;
    case 'FreeBSD':
      // kern.hostuuid: fe38be37-5d64-11eb-b896-6470021048e6
      list(, $id) = explode(': ', external_exec('sysctl kern.hostuuid'));
      if ($id) {
        return str_replace('-', '', trim($id));
      }
      break;
    case 'Darwin':
      // $ system_profiler SPHardwareDataType
      // Hardware:
      //
      //     Hardware Overview:
      //
      //       Model Name: iMac
      //       Model Identifier: iMac21,1
      //       Chip: Apple M1
      //       Total Number of Cores: 8 (4 performance and 4 efficiency)
      //       Memory: 16 GB
      //       System Firmware Version: 7429.41.5
      //       OS Loader Version: 6723.140.2
      //       Serial Number (system): XXXX402RXXXX
      //       Hardware UUID: 360CXXXX-XXXX-XXXX-8C34-D2EA2266XXXX
      //       Provisioning UDID: 0000XXXX-0009193C36FB001E
      //       Activation Lock Status: Disabled

      foreach (explode("\n", external_exec('system_profiler SPHardwareDataType')) as $line) {
        if (str_contains($line, 'UUID:')) {
          list(, $id) = explode(': ', $line);
          if ($id) {
            return str_replace('-', '', strtolower(trim($id)));
          }
        }
      }
      break;
  }

  // Derp way, need to store lock file, available only for current host (not in db!)..
  $id_file = $GLOBALS['config']['log_dir'] . '/.machine-id';
  if (is_file($id_file)) {
    return file_get_contents($id_file);
  }

  try {
    $uuid4 = Ramsey\Uuid\Uuid::uuid4();
  } catch(Exception $e) {
    return '';
  }

  $unique_id = $uuid4->getHex();   // i.e. c39b2386c4e8487fad4a87cd367b279d
  if (file_put_contents($id_file, $unique_id)) {
    // return generated id, only when lock file is writable, for prevent logs spamming
    return $unique_id;
  }

  return '';
}

/**
 * Get local hostname
 *
 * @return string FQDN local hostname
 */
function get_localhost() {
  global $cache;

  if (!isset($cache['localhost'])) {
    // FastCache for less system exec
    $cache_item = get_cache_item('own_hostname');
    if (!ishit_cache_item($cache_item)) {

      $cache['localhost'] = php_uname('n');
      if (!str_contains($cache['localhost'], '.')) {
        // try use hostname -f for get FQDN hostname
        $localhost_t = external_exec('/bin/hostname -f');
        if (str_contains($localhost_t, '.')) {
          $cache['localhost'] = $localhost_t;
        }
      }

      // Store in fast caching (this value very rare changed (mostly - never)
      set_cache_item($cache_item, $cache['localhost'], [ 'ttl' => 3600 ]); // set valid for 1 hour

    } else {
      // Cached item
      $cache['localhost'] = get_cache_data($cache_item);
    }
  }

  return $cache['localhost'];
}

/**
 * Get owner of current process
 *
 * @return string Username
 */
function get_localuser() {
  if ($_SERVER['USER']) {
    return $_SERVER['USER'];
  }
  if (function_exists('posix_geteuid')) {
    return posix_getpwuid(posix_geteuid())['name'];
  }

  return external_exec('whoami');
}

/**
 * Get the directory size
 *
 * @param string $dir
 * @return integer Directory size in bytes
 */
function get_dir_size($dir) {
  $size = 0;

  //$files = [];
  foreach (get_recursive_directory_iterator($dir) as $path => $file) {
    //$files[] = $path;
    $size += $file->getSize();
  }
  //print_vars($files);

  return $size;
}

/**
 * Recursively delete dir.
 *
 * @param string $dir
 *
 * @return bool
 */
function delete_dir($dir) {
  if (!file_exists($dir)) {
    print_debug("Dir '$dir' not exist.");
    return TRUE;
  }

  $dirs = [];
  $files = [];
  // Delete files inside dir
  foreach (get_recursive_directory_iterator($dir) as $path => $file) {
    $files[] = $path;
    if ($dir !== $file->getPath()) {
      $dirs[] = $file->getPath();
    }

    if (!unlink($path)) {
      // File not deleted
      return FALSE;
    }
    /*
    print_vars($file->getFilename());
    echo PHP_EOL;
    print_vars($file->getExtension());
    echo PHP_EOL;
    print_vars($file->getPath());
    echo PHP_EOL;
    */
  }
  if (count($files)) {
    print_debug("Deleted files:");
    print_debug_vars($files);
  }

  // Now delete sub-dirs
  foreach ($dirs as $d) {
    if (!rmdir($d)) {
      // Sub dir not deleted
      return FALSE;
    }
  }
  $dirs[] = $dir;
  print_debug("Deleted dirs:");
  print_debug_vars($dirs);

  return rmdir($dir);
}

function percent($value, $max, $precision = 0) {

  $percent = float_div($value, $max) * 100;

  if (is_numeric($precision)) { return round($percent, $precision); }
  return $percent;
}

/**
 * Percent Class
 *
 * Given a percentage value return a class name (for CSS).
 *
 * @param int|string $percent
 * @return string
 */
function percent_class($percent)
{
  if ($percent < "25")
  {
    $class = "info";
  } elseif ($percent < "50") {
    $class = "";
  } elseif ($percent < "75") {
    $class = "success";
  } elseif ($percent < "90") {
    $class = "warning";
  } else {
    $class = "danger";
  }

  return $class;
}

/**
 * Percent Colour
 *
 * This function returns a colour based on a 0-100 value
 * It scales from green to red from 0-100 as default.
 *
 * @param integer $value
 * @param integer $brightness
 * @param integer $max
 * @param integer $min
 * @param string  $thirdColourHex
 * @return string
 */
function percent_colour($value, $brightness = 128, $max = 100, $min = 0, $thirdColourHex = '00') {
  if ($value > $max) { $value = $max; }
  if ($value < $min) { $value = $min; }

  // Calculate first and second colour (Inverse relationship)
  $div = float_div($value, $max);
  $first  = (1 - $div) * $brightness;
  $second = $div * $brightness;

  // Find the influence of the middle Colour (yellow if 1st and 2nd are red and green)
  $diff = abs($first - $second);
  $influence = ($brightness - $diff) / 2;
  $first = (int)($first + $influence);
  $second = (int)($second + $influence);

  // Convert to HEX, format and return
  $firstHex = str_pad(dechex($first), 2, 0, STR_PAD_LEFT);
  $secondHex = str_pad(dechex($second), 2, 0, STR_PAD_LEFT);

  return '#' . $secondHex . $firstHex . $thirdColourHex;

  // alternatives:
  // return $thirdColourHex . $firstHex . $secondHex;
  // return $firstHex . $thirdColourHex . $secondHex;
}

/**
 * Convert sequence of numbers in an array to range of numbers.
 * Example:
 *  array(1,2,3,4,5,6,7,8,9,10)    -> '1-10'
 *  array(1,2,3,5,7,9,10,11,12,14) -> '1-3,5,7,9-12,14'
 *
 * @param array $arr Array with sequence of numbers
 * @param string $separator Use this separator for list
 * @param bool $sort Sort input array or not
 * @return string
 */
function range_to_list($arr, $separator = ',', $sort = TRUE) {
  if (!is_array($arr)) { return ''; }

  if ($sort) { sort($arr, SORT_NUMERIC); }

  $ranges = [];
  $count = count($arr);
  for ($i = 0; $i < $count; $i++) {
    $rstart = $arr[$i];
    $rend  = $rstart;
    while (isset($arr[$i+1]) && ((int)$arr[$i+1] - (int)$arr[$i]) === 1) {
      $rend = $arr[$i+1];
      $i++;
    }
    if (is_numeric($rstart) && is_numeric($rend)) {
      $ranges[] = ($rstart == $rend) ? $rstart : $rstart.'-'.$rend;
    } else {
      return ''; // Not numeric value(s)
    }
  }

  return implode($separator, $ranges);
}

// '1-3,5,7,9-12,14' -> array(1,2,3,5,7,9,10,11,12,14)
function list_to_range($str, $separator = ',', $sort = TRUE) {
  if (!is_string($str)) { return $str; }

  // Clean spaces while separator not with spaces
  if (!str_contains($separator, ' ')) {
    $str = str_replace(' ', '', $str);
  }

  $arr = [];
  foreach (explode($separator, trim($str)) as $list) {
    $negative = FALSE;
    if ($list[0] === '-') {
      $negative = TRUE;
      $list = substr($list, 1);
    }
    if (str_contains($list, '-')) {
      list($min, $max) = explode('-', $list, 2);
      if (!is_numeric($min) || !is_numeric($max)) { continue; }
      if ($negative) {
        $min = '-' . $min;
      }
      if ($min > $max) {
        // ie 10-3
        list($min, $max) = [ $max, $min ];
      } elseif ($min == $max) {
        // ie 1-1
        $arr[] = (int)$min;
        continue;
      }
      for ($i = $min; $i <= $max; $i++) {
        $arr[] = (int)$i;
      }
    } elseif (is_numeric($list)) {
      $arr[] = $negative ? (int)('-' . $list) : (int)$list;
    }
  }

  if ($sort) { sort($arr, SORT_NUMERIC); }

  return $arr;
}

/**
 * Write a line to the specified logfile (or default log if not specified).
 * We open & close for every line, somewhat lower performance but this means multiple concurrent processes could write to the file.
 * Now marking process and pid, if things are running simultaneously you can still see what's coming from where.
 *
 * @param string $filename
 * @param string $string
 *
 * @return false|void
 */
function logfile($filename, $string = NULL) {
  global $config, $argv;

  if (defined('__PHPUNIT_PHAR__')) {
    print_debug("Skip logging to '$filename' when run phpunit tests.");
    return FALSE;
  }

  // Use default logfile if none specified
  if (safe_empty($string)) {
    $string = $filename;
    $filename = $config['log_file'];
  }

  // Place logfile in log directory if no path specified
  if (basename($filename) === $filename) {
    $filename = $config['log_dir'] . '/' . $filename;
  }
  // Create logfile if not exist
  if (is_file($filename)) {
    if (!is_writable($filename)) {
      print_debug("Log file '$filename' is not writable, check file permissions.");
      return FALSE;
    }
    $fd = fopen($filename, 'ab');
  } else {
    $fd = fopen($filename, 'wb');
    // Check writable file (only after creation for speedup)
    if (!is_writable($filename)) {
      print_debug("Log file '$filename' is not writable or not created.");
      fclose($fd);
      return FALSE;
    }
  }

  //$string = '[' . date('Y/m/d H:i:s O') . '] ' . basename($argv[0]) . '(' . getmypid() . '): ' . trim($string) . PHP_EOL;
  $string = '[' . date('Y/m/d H:i:s O') . '] ' . OBS_SCRIPT_NAME . '(' . getmypid() . '): ' . trim($string) . PHP_EOL;
  fwrite($fd, $string);
  fclose($fd);
}

/**
 * Get used system versions
 *
 * @param string|null $program
 *
 * @return  array|string
 */
function get_versions($program = NULL) {
  $return_version = !empty($program); // return only version string for program

  if (isset($GLOBALS['cache']['versions'])) {
    // Already cached
    if ($return_version) {
      $key = strtolower($program) . '_version';
      return isset($GLOBALS['cache']['versions'][$key]) ? $GLOBALS['cache']['versions'][$key] : '';
    }
    return $GLOBALS['cache']['versions'];
  }
  $versions = array(); // Init
  if (!empty($program)) {
    // Return only one not cached version
    $programs = (array)$program;
  } else {
    // return array with all versions
    $programs = [ 'os', 'php', 'python', 'mysql', 'snmp', 'rrdtool', 'fping', 'http' ];
  }

  foreach ($programs as $entry) {
    switch ($entry) {
      case 'os':
        // Local system OS version
        if (is_executable($GLOBALS['config']['install_dir'].'/scripts/distro')) {
          $os = explode('|', external_exec($GLOBALS['config']['install_dir'].'/scripts/distro'), 6);
          $versions['os_system']         = $os[0];
          $versions['os_version']        = $os[1];
          $versions['os_arch']           = $os[2];
          $versions['os_distro']         = $os[3];
          $versions['os_distro_version'] = $os[4];
          $versions['os_virt']           = $os[5];
          $versions['os_text']           = $os[0].' '.$os[1].' ['.$os[2].'] ('.$os[3].' '.$os[4].')';
        }
        if ($return_version) {
          return (string)$versions['os_version'];
        }
        break;

      case 'php':
        // PHP
        $php_version = PHP_VERSION;
        $versions['php_full'] = $php_version;
        $versions['php_version'] = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
        if ($return_version) {
          return $versions['php_version'];
        }

        $versions['php_old'] = version_compare($versions['php_version'], OBS_MIN_PHP_VERSION, '<');
        // PHP OPcache
        $versions['php_opcache'] = FALSE;
        if (extension_loaded('Zend OPcache')) {
          $opcache = ini_get('opcache.enable');
          $php_version  .= ' (OPcache: ';
          if ($opcache && is_cli() && ini_get('opcache.enable_cli')) { // CLI
            $php_version  .= 'ENABLED)';
            $versions['php_opcache'] = 'ENABLED';
          } elseif ($opcache && !is_cli()) { // WUI
            $php_version  .= 'ENABLED)';
            $versions['php_opcache'] = 'ENABLED';
          } else {
            $php_version  .= 'DISABLED)';
            $versions['php_opcache'] = 'DISABLED';
          }
        }
        $versions['php_text'] = $php_version;

        // PHP memory_limit
        $php_memory_limit = unit_string_to_numeric(ini_get('memory_limit'));
        $versions['php_memory_limit'] = $php_memory_limit;
        if ($php_memory_limit < 0) {
          $versions['php_memory_limit_text'] = 'Unlimited';
        } else {
          $versions['php_memory_limit_text'] = formatStorage($php_memory_limit);
        }
        break;

      case 'python':
        /** Python
         * I.e.:
         * python_version = 2.7.5
         * python_text    = 2.7.5
         */
        $python_version  = str_replace('Python ', '', external_exec('/usr/bin/env python --version 2>&1'));
        $python_default  = TRUE;
        if (str_contains($python_version, 'No such file or directory')) {
          // /usr/bin/env: 'python': No such file or directory
          $python_version  = str_replace('Python ', '', external_exec('/usr/bin/env python3 --version 2>&1'));
          if (str_contains($python_version, 'No such file or directory')) {
            // /usr/bin/env: 'python': No such file or directory
            $python_version  = 'Not found';
          } else {
            // Append info about no default python executable
            $python_default = FALSE;
          }
        }
        $versions['python_version'] = $python_version;
        if ($return_version) {
          return $versions['python_version'];
        }
        if (str_starts($python_version, '2.')) {
          $versions['python_old'] = version_compare($versions['python_version'], OBS_MIN_PYTHON2_VERSION, '<');
        } else {
          $versions['python_old'] = version_compare($versions['python_version'], OBS_MIN_PYTHON3_VERSION, '<');
        }
        $versions['python_text']  = $python_version;
        if (!$python_default) {
          $versions['python_text'] .= ' (python3 not used as default python)';
        }
        break;

      case 'mysql':
      case 'mariadb':
        /** MySQL
         * I.e.:
         * mysql_client  = 5.0.12-dev
         * mysql_full    = 10.3.23-MariaDB-log
         * mysql_name    = MariaDB
         * mysql_version = 10.3.23
         * mysql_text    = 10.3.23-MariaDB-log (extension: mysqli 5.0.12-dev)
         */
        $mysql_client    = dbClientInfo();
        if (preg_match('/(\d+\.[\w\.\-]+)/', $mysql_client, $matches)) {
          $mysql_client  = $matches[1];
        }
        $versions['mysql_client']  = $mysql_client;
        $mysql_version   = dbFetchCell("SELECT version();");
        $versions['mysql_full']    = $mysql_version;
        list($versions['mysql_version']) = explode('-', $mysql_version);
        if ($return_version) {
          return $versions['mysql_version'];
        }
        $versions['mysql_name']    = str_icontains_array($mysql_version, 'Maria') ? 'MariaDB' : 'MySQL';
        if ($versions['mysql_name'] === 'MariaDB') {
          $versions['mysql_old'] = version_compare($versions['mysql_version'], OBS_MIN_MARIADB_VERSION, '<');
        } else {
          $versions['mysql_old'] = version_compare($versions['mysql_version'], OBS_MIN_MYSQL_VERSION, '<');
        }
        $mysql_version  .= ' (extension: ' . OBS_DB_EXTENSION . ' ' . $mysql_client . ')';
        $versions['mysql_text']    = $mysql_version;

        // Define DB NAME for later use
        if (!defined('OBS_DB_NAME')) {
          define('OBS_DB_NAME', $versions['mysql_name']);
        }
        break;

      case 'snmp':
        /** SNMP
         * I.e.:
         * snmp_version = 5.7.2
         * snmp_text    = NET-SNMP 5.7.2
         */
        $snmp_cmd = is_executable($GLOBALS['config']['snmpget']) ? $GLOBALS['config']['snmpget'] : '/usr/bin/env snmpget';
        $snmp_version  = str_replace(' version:', '', external_exec($snmp_cmd . " --version 2>&1"));
        $versions['snmp_version'] = str_replace('NET-SNMP ', '', $snmp_version);
        if ($return_version) {
          return $versions['snmp_version'];
        }
        if (empty($versions['snmp_version'])) {
          $versions['snmp_version'] = 'not found';
        }
        $versions['snmp_text']    = $snmp_version;
        break;

      case 'rrdtool':
        /** RRDtool
         * I.e.:
         * rrdtool_version   = 1.5.5
         * rrdcached_version = 1.5.5
         * rrdtool_text      = 1.5.5 (rrdcached 1.5.5: unix:/var/run/rrdcached.sock)
         */
        $rrdtool_cmd = is_executable($GLOBALS['config']['rrdtool']) ? $GLOBALS['config']['rrdtool'] : '/usr/bin/env rrdtool';
        list(,$rrdtool_version) = explode(' ', external_exec($rrdtool_cmd . ' --version | head -n1'));
        $versions['rrdtool_version'] = $rrdtool_version;
        if ($return_version) {
          return $versions['rrdtool_version'];
        }
        $versions['rrdtool_old'] = version_compare($versions['rrdtool_version'], OBS_MIN_RRD_VERSION, '<');

        if (!safe_empty($GLOBALS['config']['rrdcached'])) {
          if (OBS_RRD_NOLOCAL) {
            // Remote rrdcached daemon (unknown version)
            $rrdtool_version .= ' (rrdcached remote: ' . $GLOBALS['config']['rrdcached'] . ')';
            // Remote RRDcached require version 1.5.5
            $versions['rrdtool_old'] = version_compare($versions['rrdtool_version'], '1.5.5', '<');
          } else {
            $rrdcached_exec = str_replace('rrdtool', 'rrdcached', $GLOBALS['config']['rrdtool']);
            if (!is_executable($rrdcached_exec)) {
              $rrdcached_exec = '/usr/bin/env rrdcached -h';
            }
            list(,$versions['rrdcached_version']) = explode(' ', external_exec($rrdcached_exec . ' -h | head -n1'));
            $rrdtool_version .= ' (rrdcached ' . $versions['rrdcached_version'] . ': ' . $GLOBALS['config']['rrdcached'] . ')';
          }
        }

        if (empty($rrdtool_version)) {
          $rrdtool_version = 'not found';
          $versions['rrdtool_version'] = $rrdtool_version;
          $versions['rrdtool_old'] = TRUE;
        }
        $versions['rrdtool_text'] = $rrdtool_version;
        break;

      case 'fping':
        /** Fping
         * I.e.:
         * fping_version = 3.13
         * fping_text    = 3.13 (IPv4 and IPv6)
         */
        $fping_version = 'not found';
        $fping_exec = is_executable($GLOBALS['config']['fping']) ? $GLOBALS['config']['fping'] : '/usr/bin/env fping';
        $fping  = external_exec($fping_exec . " -v 2>&1");
        if (preg_match('/Version\s+(\d\S+)/', $fping, $matches)) {
          $fping_version = $matches[1];
          $fping_text    = $fping_version;

          if (version_compare($fping_version, '4.0', '>=')) {
            $fping_text .= ' (IPv4 and IPv6)';
          } elseif (is_executable($GLOBALS['config']['fping6'])) {
            $fping_text .= ' (IPv4 and IPv6)';
          } else {
            $fping_text .= ' (IPv4 only)';
          }
        }
        $versions['fping_version'] = $fping_version;
        $versions['fping_text']    = $fping_text;
        if ($return_version) {
          return $versions['fping_version'];
        }
        break;

      case 'http':
        // Apache (or any http used?)
        if (is_cli()) {
          foreach ([ 'apache2', 'httpd' ] as $http_cmd) {
            if (is_executable('/usr/sbin/'.$http_cmd)) {
              $http_cmd = '/usr/sbin/'.$http_cmd;
            } else {
              $http_cmd = '/usr/bin/env '.$http_cmd;
            }
            $http_version = external_exec($http_cmd.' -v | awk \'/Server version:/ {print $3}\'');

            if ($http_version) { break; }
          }
          if (empty($http_version)) {
            $http_version  = 'not found';
          }
          $versions['http_full']    = $http_version;
        } else {
          $versions['http_full']    = $_SERVER['SERVER_SOFTWARE'];
        }
        $versions['http_version'] = str_replace('Apache/', '', $versions['http_full']);
        $versions['http_text']    = $versions['http_version'];
        if ($return_version) {
          return $versions['http_version'];
        }
        break;
    }
  }

  // Cache for current execution
  $GLOBALS['cache']['versions'] = $versions;
  //print_vars($GLOBALS['cache']['versions']);

  return $versions;
}

/**
 * Print version information about used Observium and additional software.
 *
 * @return NULL
 */
function print_versions() {
  get_versions();

  $os_version      = $GLOBALS['cache']['versions']['os_text'];
  $php_version     = $GLOBALS['cache']['versions']['php_text'];
  $python_version  = $GLOBALS['cache']['versions']['python_text'];
  $mysql_version   = $GLOBALS['cache']['versions']['mysql_text'];
  $mysql_name      = $GLOBALS['cache']['versions']['mysql_name'];
  $snmp_version    = $GLOBALS['cache']['versions']['snmp_text'];
  $rrdtool_version = $GLOBALS['cache']['versions']['rrdtool_text'];
  $fping_version   = $GLOBALS['cache']['versions']['fping_text'];
  $http_version    = $GLOBALS['cache']['versions']['http_text'];

  // PHP memory_limit
  $php_memory_limit      = $GLOBALS['cache']['versions']['php_memory_limit'];
  $php_memory_limit_text = $GLOBALS['cache']['versions']['php_memory_limit_text'];

  if (is_cli()) {
    $timezone      = get_timezone();
    //print_vars($timezone);

    $mysql_mode    = dbFetchCell("SELECT @@SESSION.sql_mode;");
    $mysql_charset = dbShowVariables("LIKE 'character_set_connection'");

    if ($GLOBALS['cache']['versions']['php_old']) {
      $php_version = '%r' . $php_version;
    }
    if ($GLOBALS['cache']['versions']['python_old']) {
      $python_version = '%r' . $python_version;
    }
    if ($GLOBALS['cache']['versions']['mysql_old']) {
      $mysql_version = '%r' . $mysql_version;
    }
    if ($GLOBALS['cache']['versions']['rrdtool_old']) {
      $rrdtool_version = '%r' . $rrdtool_version;
    }

    echo(PHP_EOL);
    print_cli_heading("Software versions");
    print_cli_data("OS",      $os_version);
    print_cli_data("Apache",  $http_version);
    print_cli_data("PHP",     $php_version);
    print_cli_data("Python",  $python_version);
    print_cli_data($mysql_name, $mysql_version);
    print_cli_data("SNMP",    $snmp_version);
    print_cli_data("RRDtool", $rrdtool_version);
    print_cli_data("Fping",   $fping_version);

    // Additionally, in CLI always display Memory Limit, MySQL Mode and Charset info

    echo(PHP_EOL);
    print_cli_heading("Memory Limit", 3);
    print_cli_data("PHP",     ($php_memory_limit >= 0 && $php_memory_limit < 268435456 ? '%r' : '') . $php_memory_limit_text, 3);

    echo(PHP_EOL);
    print_cli_heading("DB info", 3);
    print_cli_data("DB schema", get_db_version(), 3);
    print_cli_data("$mysql_name mode", $mysql_mode, 3);

    echo(PHP_EOL);
    print_cli_heading("Charset info", 3);
    print_cli_data("PHP",     ini_get("default_charset"), 3);
    print_cli_data($mysql_name, $mysql_charset['character_set_connection'], 3);

    echo(PHP_EOL);
    print_cli_heading("Timezones info", 3);
    print_cli_data("Date",    date("l, d-M-y H:i:s T"), 3);
    print_cli_data("PHP",     $timezone['php'], 3);
    print_cli_data($mysql_name, ($timezone['diff'] !== 0 ? '%r' : '') . $timezone['mysql'], 3);

    if (OBS_DEBUG) {
      $phpfastcache = get_cache_stats();
      echo(PHP_EOL);
      print_cli_heading("Fast Cache info", 3);
      print_cli_data("Enabled", $phpfastcache['enabled'] ? '%gYes%n' : '%yNo%n', 3);
      print_cli_data("Driver", $phpfastcache['driver'], 3);
      print_cli_data("Total size", formatStorage($phpfastcache['size']), 3);
    }

    if (OBS_DISTRIBUTED) {
      $id = $GLOBALS['config']['poller_id'];
      echo(PHP_EOL);
      print_cli_heading("Poller info", 3);
      print_cli_data("ID",  $id, 3);
      print_cli_data("Name", ($id !== 0 ? $GLOBALS['config']['poller_name'] : 'Main'), 3);
    }
    echo(PHP_EOL);

  } else {
    $observium_date  = format_unixtime(strtotime(OBSERVIUM_DATE), 'jS F Y');

    if ($php_memory_limit >= 0 && $php_memory_limit < 268435456) {
      $php_memory_limit_text = '<span class="text-danger">'.$php_memory_limit_text.'</span>';
    }

    // Check minimum versions
    if ($GLOBALS['cache']['versions']['php_old']) {
      $php_class = 'error';
      $php_version = generate_tooltip_link(NULL, $php_version, 'Minimum supported: ' . OBS_MIN_PHP_VERSION);
    } else {
      $php_class = '';
      $php_version = escape_html($php_version);
    }
    if ($GLOBALS['cache']['versions']['python_old']) {
      $python_class = 'warning';
      if (str_starts($python_version, '2.')) {
        $python_version = generate_tooltip_link(NULL, $python_version, 'Recommended version is greater than or equal to: ' . OBS_MIN_PYTHON2_VERSION . ' or ' . OBS_MIN_PYTHON3_VERSION);
      } else {
        $python_version = generate_tooltip_link(NULL, $python_version, 'Recommended version is greater than or equal to: ' . OBS_MIN_PYTHON3_VERSION);
      }
    } else {
      $python_class = '';
      $python_version = escape_html($python_version);
    }
    if ($GLOBALS['cache']['versions']['mysql_old']) {
      $mysql_class = 'warning';
      if ($mysql_name === 'MariaDB') {
        $mysql_version = generate_tooltip_link(NULL, $mysql_version, 'Recommended version is greater than or equal to: ' . OBS_MIN_MARIADB_VERSION);
      } else {
        $mysql_version = generate_tooltip_link(NULL, $mysql_version, 'Recommended version is greater than or equal to: ' . OBS_MIN_MYSQL_VERSION);
      }
    } else {
      $mysql_class = '';
      $mysql_version = escape_html($mysql_version);
    }
    if ($GLOBALS['cache']['versions']['rrdtool_old']) {
      $rrdtool_class = 'error';
      $rrdtool_version = generate_tooltip_link(NULL, $rrdtool_version, 'Minimum supported: ' . OBS_MIN_RRD_VERSION);
    } else {
      $rrdtool_class = '';
      $rrdtool_version = escape_html($rrdtool_version);
    }

    echo generate_box_open([ 'title' => 'Version Information' ]);
    echo('
        <table class="table table-striped table-condensed-more">
          <tbody>
            <tr><td><b>'.escape_html(OBSERVIUM_PRODUCT).'</b></td><td>'.escape_html(OBSERVIUM_VERSION).' ('.escape_html($observium_date).')</td></tr>
            <tr><td><b>OS</b></td><td>'.escape_html($os_version).'</td></tr>
            <tr><td><b>Apache</b></td><td>'.escape_html($http_version).'</td></tr>
            <tr class="'.$php_class.'"><td><b>PHP</b></td><td>'.$php_version.' (Memory: '.$php_memory_limit_text.')</td></tr>
            <tr class="'.$python_class.'"><td><b>Python</b></td><td>'.$python_version.'</td></tr>
            <tr class="'.$mysql_class.'"><td><b>'.$mysql_name.'</b></td><td>'.$mysql_version.'</td></tr>
            <tr><td><b>SNMP</b></td><td>'.escape_html($snmp_version).'</td></tr>
            <tr class="'.$rrdtool_class.'"><td><b>RRDtool</b></td><td>'.$rrdtool_version.'</td></tr>
            <tr><td><b>Fping</b></td><td>'.escape_html($fping_version).'</td></tr>
          </tbody>
        </table>'.PHP_EOL);
    echo generate_box_close();
  }
}

/**
 * Observium's SQL debugging. Chooses nice output depending upon web or cli.
 * Use format param:
 *  'compress' (default) print compressed and highlight query;
 *  'full', 'html' print fully formatted query (multiline);
 *  'log' for return compressed query.
 *
 * @param string $query
 * @param string $format
 */
function print_sql($query, $format = 'compress') {
  if (PHP_VERSION_ID < 70000) {
    // SqlFormatter support only php 7+
    return rtrim($query);
  }

  switch ($format) {
    case 'full':
    case 'format':
    case 'html':
      // Fully formatted
      $output = (new Doctrine\SqlFormatter\SqlFormatter())->format($query);
      break;

    case 'log':
      // Only compress and return for log
      return (new Doctrine\SqlFormatter\SqlFormatter())->compress($query);

    default:
      // Only compress and highlight in single line (default)
      $compressed = (new Doctrine\SqlFormatter\SqlFormatter())->compress($query);
      $output = (new Doctrine\SqlFormatter\SqlFormatter())->highlight($compressed);
  }

  if (!is_cli()) {
    $output = '<p>' . $output . '</p>';
  } else {
    $output = rtrim($output);
  }

  echo $output;
}

// DOCME needs phpdoc block
// Observium's variable debugging. Chooses nice output depending upon web or cli
// TESTME needs unit testing
function print_vars($vars, $trace = NULL) {
  if (isset($GLOBALS['cli']) && $GLOBALS['cli']) {
    if (function_exists('rt')) {
      ref::config('shortcutFunc', array('print_vars', 'print_debug_vars'));
      ref::config('showUrls', FALSE);
      if (OBS_DEBUG > 0)
      {
        if (is_null($trace))
        {
          $backtrace = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : debug_backtrace();
        } else {
          $backtrace = $trace;
        }
        ref::config('Backtrace', $backtrace); // pass original backtrace
        ref::config('showStringMatches',  FALSE);
      } else {
        ref::config('showBacktrace',      FALSE);
        ref::config('showResourceInfo',   FALSE);
        ref::config('showStringMatches',  FALSE);
        ref::config('showMethods',        FALSE);
      }
      rt($vars);
    } else {
      print_r($vars);
    }
  } else {
    if (function_exists('r'))
    {
      ref::config('shortcutFunc', array('print_vars', 'print_debug_vars'));
      ref::config('showUrls',     FALSE);
      if (OBS_DEBUG > 0)
      {
        if (is_null($trace))
        {
          $backtrace = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : debug_backtrace();
        } else {
          $backtrace = $trace;
        }
        ref::config('Backtrace', $backtrace); // pass original backtrace
      } else {
        ref::config('showBacktrace',      FALSE);
        ref::config('showResourceInfo',   FALSE);
        ref::config('showStringMatches',  FALSE);
        ref::config('showMethods',        FALSE);
      }
      //ref::config('stylePath',  $GLOBALS['config']['html_dir'] . '/css/ref.css');
      //ref::config('scriptPath', $GLOBALS['config']['html_dir'] . '/js/ref.js');
      r($vars);
    } else {
      print_r($vars);
    }
  }
}

/**
 * Call to print_vars in debug mode only
 * By default var displayed only for debug level 2
 *
 * @param mixed $vars Variable to print
 * @param integer $debug_level Minimum debug level, default 2
 */
function print_debug_vars($vars, $debug_level = 2) {
  // For level 2 display always (also empty), for level 1 only non empty vars
  if (defined('OBS_DEBUG') && OBS_DEBUG &&
      OBS_DEBUG >= $debug_level && (OBS_DEBUG > 1 || !safe_empty($vars))) {
    $trace = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : debug_backtrace();
    print_vars($vars, $trace);
  } elseif (defined('OBS_CACHE_DEBUG') && OBS_CACHE_DEBUG) {
    $trace = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : debug_backtrace();
    print_vars($vars, $trace);
  }
}

/**
 * Convert SNMP timeticks string into seconds
 *
 * SNMP timeticks can be in two different normal formats:
 *  - "(2105)"       == 21.05 sec
 *  - "0:0:00:21.05" == 21.05 sec
 * Sometime devices return wrong type or numeric instead timetick:
 *  - "Wrong Type (should be Timeticks): 1632295600" == 16322956 sec
 *  - "1546241903" == 15462419.03 sec
 * Parse the timeticks string and convert it to seconds.
 *
 * @param string $timetick
 * @param bool $float - Return a float with microseconds
 *
 * @return int|float
 */
function timeticks_to_sec($timetick, $float = FALSE)
{
  if (str_contains($timetick, 'Wrong Type')) {
    // Wrong Type (should be Timeticks): 1632295600
    list(, $timetick) = explode(': ', $timetick, 2);
  }

  $timetick = trim($timetick, " \t\n\r\0\x0B\"()"); // Clean string
  if (is_numeric($timetick))
  {
    // When "Wrong Type" or timetick as an integer, than time with count of ten millisecond ticks
    $time = $timetick / 100;
    return ($float ? (float)$time : (int)$time);
  }
  if (!preg_match('/^[\d\.: ]+$/', $timetick)) { return FALSE; }

  $timetick_array = explode(':', $timetick);
  if (count($timetick_array) == 1 && is_numeric($timetick))
  {
    $secs = $timetick;
    $microsecs = 0;
  } else {
    //list($days, $hours, $mins, $secs) = $timetick_array;
    $secs  = array_pop($timetick_array);
    $mins  = array_pop($timetick_array);
    $hours = array_pop($timetick_array);
    $days  = array_pop($timetick_array);
    list($secs, $microsecs) = explode('.', $secs);

    $hours += $days  * 24;
    $mins  += $hours * 60;
    $secs  += $mins  * 60;

    // Sometime used non standard years counter
    if (count($timetick_array))
    {
      $years = array_pop($timetick_array);
      $secs  += $years * 31536000; // 365 * 24 * 60 * 60;
    }
    //print_vars($timetick_array);
  }
  $time  = ($float ? (float)$secs + $microsecs/100 : (int)$secs);
  print_debug("Timeticks converted $timetick -> $time");

  return $time;
}

/**
 * Convert SNMP DateAndTime string into unixtime
 *
 * field octets contents range
 * ----- ------ -------- -----
 * 1 1-2 year 0..65536
 * 2 3 month 1..12
 * 3 4 day 1..31
 * 4 5 hour 0..23
 * 5 6 minutes 0..59
 * 6 7 seconds 0..60
 * (use 60 for leap-second)
 * 7 8 deci-seconds 0..9
 * 8 9 direction from UTC '+' / '-'
 * 9 10 hours from UTC 0..11
 * 10 11 minutes from UTC 0..59
 *
 * For example, Tuesday May 26, 1992 at 1:30:15 PM EDT would be displayed as:
 * 1992-5-26,13:30:15.0,-4:0
 *
 * Note that if only local time is known, then timezone information (fields 8-10) is not present.
 *
 * @param string $datetime DateAndTime string
 * @param boolean $use_gmt Return unixtime converted to GMT or Local (by default)
 *
 * @return integer Unixtime
 */
function datetime_to_unixtime($datetime, $use_gmt = FALSE) {
  $timezone = get_timezone();

  $datetime = trim($datetime);
  if (preg_match('/(?<year>\d+)-(?<mon>\d+)-(?<day>\d+)(?:,(?<hour>\d+):(?<min>\d+):(?<sec>\d+)(?<millisec>\.\d+)?(?:,(?<tzs>[+\-]?)(?<tzh>\d+):(?<tzm>\d+))?)/', $datetime, $matches)) {
    if (isset($matches['tzh'])) {
      // Use TZ offset from datetime string
      $offset = $matches['tzs'] . ($matches['tzh'] * 3600 + $matches['tzm'] * 60); // Offset from GMT in seconds
    } else {
      // Or use system offset
      $offset = $timezone['php_offset'];
    }
    $time_tmp = mktime($matches['hour'], $matches['min'], $matches['sec'], $matches['mon'], $matches['day'], $matches['year']); // Generate unixtime

    $time_gmt   = $time_tmp + ($offset * -1);            // Unixtime from string in GMT
    $time_local = $time_gmt + $timezone['php_offset'];   // Unixtime from string in local timezone
  } else {
    $time_local = time();                                // Current unixtime with local timezone
    $time_gmt   = $time_local - $timezone['php_offset']; // Current unixtime in GMT
  }

  if (OBS_DEBUG > 1) {
    $debug_msg  = 'UNIXTIME from DATETIME "' . ($time_tmp ? $datetime : 'time()') . '": ';
    $debug_msg .= 'LOCAL (' . format_unixtime($time_local) . '), GMT (' . format_unixtime($time_gmt) . '), TZ (' . $timezone['php'] . ')';
    print_message($debug_msg);
  }

  if ($use_gmt) {
    return ($time_gmt);
  }

  return ($time_local);
}

/**
 * Format seconds to requested time format.
 *
 * Default format is "long".
 *
 * Supported formats:
 *   long    => '1 year, 1 day, 1h 1m 1s'
 *   longest => '1 year, 1 day, 1 hour 1 minute 1 second'
 *   short-3 => '1y 1d 1h'
 *   short-2 => '1y 1d'
 *   shorter => *same as short-2 above
 *   (else)  => '1y 1d 1h 1m 1s'
 *
 * @param int|string $uptime Time is seconds
 * @param string $format Optional format
 *
 * @return string
 */
function format_uptime($uptime, $format = "long") {
  if (!is_numeric($uptime)) {
    print_debug("Passed incorrect value to ".__FUNCTION__."()");
    print_debug_vars($uptime);
    //return FALSE;
    return '0s'; // incorrect, but for keep compatibility
  }

  $uptime = (int) round($uptime);
  if ($uptime <= 0) { return '0s'; }

  $up['y'] = floor($uptime / 31536000);
  $up['d'] = floor($uptime % 31536000 / 86400);
  $up['h'] = floor($uptime % 86400 / 3600);
  $up['m'] = floor($uptime % 3600 / 60);
  $up['s'] = floor($uptime % 60);

  $result = '';

  if (str_starts($format, 'long')) {
    if ($up['y'] > 0) {
      $result .= $up['y'] . ' year'. ($up['y'] != 1 ? 's' : '');
      if ($up['d'] > 0 || $up['h'] > 0 || $up['m'] > 0 || $up['s'] > 0) {
        $result .= ', ';
      }
    }

    if ($up['d'] > 0) {
      $result .= $up['d'] . ' day' . ($up['d'] != 1 ? 's' : '');
      if ($up['h'] > 0 || $up['m'] > 0 || $up['s'] > 0) {
        $result .= ', ';
      }
    }

    if ($format === 'longest') {
      if ($up['h'] > 0) { $result .= $up['h'] . ' hour'   . ($up['h'] != 1 ? 's ' : ' '); }
      if ($up['m'] > 0) { $result .= $up['m'] . ' minute' . ($up['m'] != 1 ? 's ' : ' '); }
      if ($up['s'] > 0) { $result .= $up['s'] . ' second' . ($up['s'] != 1 ? 's ' : ' '); }
    } else {
      if ($up['h'] > 0) { $result .= $up['h'] . 'h '; }
      if ($up['m'] > 0) { $result .= $up['m'] . 'm '; }
      if ($up['s'] > 0) { $result .= $up['s'] . 's '; }
    }
  } else {
    $count = $format === 'shorter' ? 2 : 6;
    if (str_starts($format, 'short-')) {
      // short-2 => 2, short-3 => 3 and up to 6
      list(, $tmp) = explode('-', $format, 2);
      if (is_numeric($count) && $count >= 1 && $count <= 6) {
        $count = (int) $tmp;
      }
    }

    foreach ($up as $period => $value) {
      if ($value == 0) { continue; }

      $result .= $value.$period.' ';
      $count--;
      if ($count == 0) { break; }
    }
  }

  return trim($result);
}

/**
 * Get current timezones for mysql and php.
 * Use this function when need display timedate from mysql
 * for fix diffs between this timezones
 *
 * Example:
 * Array
 * (
 *  [mysql] => +03:00
 *  [php] => +03:00
 *  [php_abbr] => MSK
 *  [php_offset] => +10800
 *  [mysql_offset] => +10800
 *  [diff] => 0
 * )
 *
 * @param bool $refresh Refresh timezones
 *
 * @return array Timezones info
 */
function get_timezone($refresh = FALSE) {
  global $cache;

  if ($refresh || !isset($cache['timezone'])) {
    $timezone = [];
    if ($refresh) {
      // Call to external exec only when refresh (basically it's not required)
      $timezone['system'] = external_exec('date "+%:z"');                            // return '+03:00'
    }
    if (!OBS_DB_SKIP) {
      $timezone['mysql']  = dbFetchCell('SELECT TIMEDIFF(NOW(), UTC_TIMESTAMP);'); // return '03:00:00'
      if ($timezone['mysql'][0] !== '-') {
        $timezone['mysql'] = '+'.$timezone['mysql'];
      }
      $timezone['mysql']       = preg_replace('/:00$/', '', $timezone['mysql']);  // convert to '+03:00'
    }
    list($timezone['php'], $timezone['php_abbr'], $timezone['php_name'], $timezone['php_daylight']) = explode('|', date('P|T|e|I'));
    //$timezone['php']         = date('P');                                       // return '+03:00'
    //$timezone['php_abbr']    = date('T');                                       // return 'MSK'
    //$timezone['php_name']    = date('e');                                       // return 'Europe/Moscow'
    //$timezone['php_daylight'] = date('I');                                      // return '0'

    foreach ([ 'php', 'mysql' ] as $entry) {
      if (!isset($timezone[$entry])) { continue; } // skip mysql if OBS_DB_SKIP

      $sign = $timezone[$entry][0];
      list($hours, $minutes) = explode(':', $timezone[$entry]);
      $timezone[$entry . '_offset'] = $sign . (abs($hours) * 3600 + $minutes * 60); // Offset from GMT in seconds
    }

    if (OBS_DB_SKIP) {
      // If mysql skipped, just return system/php timezones without caching
      return $timezone;
    }

    // Get the difference in sec between mysql and php timezones
    $timezone['diff'] = (int)$timezone['mysql_offset'] - (int)$timezone['php_offset'];
    $cache['timezone'] = $timezone;
  }

  return $cache['timezone'];
}


/**
 * Convert common MAC strings to fixed 12 char string
 * @param  string $mac MAC string (ie: 66:c:9b:1b:62:7e, 00 02 99 09 E9 84)
 * @return string      Cleaned MAC string (ie: 00029909e984)
 */
function mac_zeropad($mac) {
  $mac = strtolower(trim($mac));
  if (str_contains($mac, ':')) {
    // STRING: 66:c:9b:1b:62:7e
    $mac_parts = explode(':', $mac);
    if (count($mac_parts) == 6) {
      $mac = '';
      foreach ($mac_parts as $part) {
        $mac .= zeropad($part);
      }
    }
  } else {
    // Hex-STRING: 00 02 99 09 E9 84
    // Cisco MAC:  1234.5678.9abc
    // Other Vendors: 00-0B-DC-00-68-AF
    // Some other: 0x123456789ABC
    $mac = str_replace([ ' ', '.', '-', '0x' ], '', $mac);
  }

  if (strlen($mac) === 12 && ctype_xdigit($mac)) {
    $mac_clean = $mac;
  } else {
    // Strip out non-hex digits (Not sure when this required, copied for compat with old format_mac())
    $mac = preg_replace('/[[:^xdigit:]]/', '', $mac);
    $mac_clean = (strlen($mac) === 12) ? $mac : NULL;
    //$mac_clean = NULL;
  }

  return $mac_clean;
}

/**
 * Format any mac address string.
 *
 * @param string $mac        MAC address string in any known format
 * @param string $split_char Allowed split chars for specific formats: ':', '', ' ', '0x'
 *
 * @return string
 */
function format_mac($mac, $split_char = ':')
{
  // Clean MAC string
  $mac = mac_zeropad($mac);

  // Add colons
  $mac = preg_replace('/([[:xdigit:]]{2})(?!$)/', '$1:', $mac);

  // Convert fake MACs to IP
  //if (preg_match('/ff:fe:([[:xdigit:]]+):([[:xdigit:]]+):([[:xdigit:]]+):([[:xdigit:]]{1,2})/', $mac, $matches))
  if (preg_match('/ff:fe:([[:xdigit:]]{2}):([[:xdigit:]]{2}):([[:xdigit:]]{2}):([[:xdigit:]]{2})/', $mac, $matches))
  {
    if ($matches[1] == '00' && $matches[2] == '00')
    {
      $mac = hexdec($matches[3]).'.'.hexdec($matches[4]).'.X.X'; // Cisco, why you convert 192.88.99.1 to 0:0:c0:58 (should be c0:58:63:1)
    } else {
      $mac = hexdec($matches[1]).'.'.hexdec($matches[2]).'.'.hexdec($matches[3]).'.'.hexdec($matches[4]);
    }
  }

  if ($split_char === '') {
    $mac = str_replace(':', $split_char, $mac);
  } elseif ($split_char === ' ') {
    $mac = strtoupper(str_replace(':', $split_char, $mac));
  } elseif ($split_char === '.') {
    // Cisco like format
    $parts = explode(':', $mac, 6);
    $mac = $parts[0].$parts[1] . '.' . $parts[2].$parts[3] . '.' . $parts[4].$parts[5];
  } elseif ($split_char === '0x') {
    $mac = '0x' . strtoupper(str_replace(':', '', $mac));
  }

  return $mac;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function format_number_short($number, $sf) {
  // This formats a number so that we only send back three digits plus an optional decimal point.
  // Example: 723.42 -> 723, 72.34 -> 72.3, 2.23 -> 2.23

  list($whole, $decimal) = explode('.', $number);
  $whole_len = strlen($whole);

  if (is_numeric($number) && abs($number) > 0 && preg_match('/^\-?0$/', $whole) &&
      preg_match('/^(?<zero>\-?0\.0+)(?<numeric>\d+?)0*$/', (string) $number, $matches)) {
    // prevent show small values as zero
    // ie 0.000627 as 0.00
    //r($orig);
    //r($value);

    $numeric = substr($matches['numeric'], 0, $sf) . '.' . substr($matches['numeric'], $sf);
    $number = $matches['zero'] . round(rtrim($numeric, '.'));
    //r($value);
  } elseif ($whole_len >= $sf || !is_numeric($decimal)) {
    $number = $whole;
  } elseif ($whole_len < $sf) {
    $number  = $whole;
    $diff    = $sf - $whole_len;
    $decimal = rtrim(substr($decimal, 0, $diff), '0');
    if (strlen($decimal)) {
      $number .= '.' . $decimal;
    }
  }

  return $number;
}

/**
 * Detect if required exec functions available
 *
 * @return boolean TRUE if proc_open/proc_get_status available and not in safe mode.
 */
function is_exec_available()
{
  // Detect that function ini_get() not disabled too
  if (!function_exists('ini_get'))
  {
    print_warning('WARNING: Function ini_get() is disabled via the `disable_functions` option in php.ini configuration file. Please clean this function from this list.');

    return TRUE; // NOTE, this is not a critical function for functionally
  }

  $required_functions = array('proc_open', 'proc_get_status');
  $disabled_functions = explode(',', ini_get('disable_functions'));
  foreach ($required_functions as $func)
  {
    if (in_array($func, $disabled_functions))
    {
      print_error('ERROR: Function ' . $func . '() is disabled via the `disable_functions` option in php.ini configuration file. Please clean this function from this list.');
      return FALSE;
    }
  }

  /*
  // Detect safe mode
  $safe_mode = ini_get('safe_mode');
  if (strtolower($safe_mode) != 'off')
  {
    return FALSE;
  }
  */

  return TRUE;
}

function pipe_open($command, &$pipes, $cwd = NULL, $env = []) {
  $descriptorspec = array(
    0 => [ 'pipe', 'r' ],  // stdin
    1 => [ 'pipe', 'w' ],  // stdout
    2 => [ 'pipe', 'w' ]   // stderr
  );

  $process = proc_open($command, $descriptorspec, $pipes, $cwd, $env);

  if (is_resource($process)) {
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
  }

  return $process;
}

function pipe_read($command, &$pipes, $fullread = TRUE) {
  // $pipes now looks like this:
  // 0 => writeable handle connected to child stdin
  // 1 => readable handle connected to child stdout
  // Any error output will be appended to /tmp/error-output.txt

  fwrite($pipes[0], $command);
  fclose($pipes[0]);

  if ($fullread) {
    // Read while not end of file
    $stdout = '';
    while (!feof($pipes[1])) {
      $stdout .= fgets($pipes[1]);
    }
  } else {
    // Output not matter, for rrdtool
    $iter = 0;
    $line = '';
    $stdout = '';
    while (strlen($line) < 1 && $iter < 1000) {
      // wait for 10 milliseconds to loosen loop (max 1s)
      usleep(1000);
      $line = fgets($pipes[1], 1024);
      $stdout .= $line;
      $iter++;
    }
  }

  return preg_replace('/(?:\n|\r\n|\r)$/D', '', $stdout); // remove last (only) eol
}

function pipe_close($process, &$pipes) {
  if (is_resource($pipes[0])) {
    fclose($pipes[0]);
  }
  fclose($pipes[1]);
  fclose($pipes[2]);

  // It is important that you close any pipes before calling
  // proc_close in order to avoid a deadlock

  return proc_close($process);
}

// DOCME needs phpdoc block
function external_exec($command, $timeout = NULL) {
  global $exec_status;

  $command     = trim($command);

  // Debug the command *before* we run it!
  $debug = defined('OBS_DEBUG') ? OBS_DEBUG : 0;
  if ($debug > 0) {
    $debug_command = ($command === '' && isset($GLOBALS['snmp_command'])) ? $GLOBALS['snmp_command'] : $command;
    if (OBS_DEBUG < 2 && $GLOBALS['config']['snmp']['hide_auth'] &&
        preg_match("/snmp(bulk)?(get|getnext|walk)(\s+-(t|r|Cr)['\d\s]+){0,3}(\s+-Cc)?\s+-v[123]c?\s+/", $debug_command)) {
      // Hide snmp auth params from debug cmd out,
      // for help users who want send debug output to developers
      $pattern = "/\s+(?:(\-[uxXaA])\s*(?:'.*?')|(\-c)\s*(?:'.*?(@\S+)?'))/"; // do not hide contexts, only community and v3 auth
      $debug_command = preg_replace($pattern, ' \1\2 ***\3', $debug_command);
    } elseif (OBS_DEBUG < 2 && preg_match("!\ --(user(?:name)?|password)=!", $debug_command)) {
      // Hide any username/password in debug output, ie in WMIC
      $pattern = "/ --(user(?:name)?|password)=(\S+|\'[^\']*\')/";
      $debug_command = preg_replace($pattern, ' --\1=***', $debug_command);
    }
    print_message(PHP_EOL . 'CMD[%y' . $debug_command . '%n]' . PHP_EOL, 'console');
  }

  $exec_status = [
    'command'   => $command,
    'exitdelay' => 0
  ];
  if ($command === '') {
    // Hardcode exec_status if empty command passed
    if (isset($GLOBALS['snmp_command'])) {
      $exec_status['command'] = $GLOBALS['snmp_command'];
      unset($GLOBALS['snmp_command']); // Now clean this global var
    }
    $exec_status['exitcode'] = -1;
    $exec_status['endtime']  = microtime(TRUE); // store end unixtime with microseconds
    $exec_status['runtime']  = 0;
    $exec_status['stderr']   = 'Empty command passed';
    $exec_status['stdout']   = '';

    if ($debug > 0) {
      print_message('CMD EXITCODE['.($exec_status['exitcode'] !== 0 ? '%r' : '%g').$exec_status['exitcode'].'%n]'.PHP_EOL.
                    'CMD RUNTIME['.($exec_status['runtime'] > 7 ? '%r' : '%g').round($exec_status['runtime'], 4).'s%n]', 'console');
      print_message("STDOUT[\n\n]", 'console', FALSE);
      if ($exec_status['exitcode'] && $exec_status['stderr']) {
        // Show stderr if exitcode not 0
        print_message("STDERR[\n".$exec_status['stderr']."\n]", 'console', FALSE);
      }
    }
    return '';
  }

  if (is_numeric($timeout) && $timeout > 0) {
    $timeout_usec = $timeout * 1000000;
    $timeout = 0;
  } else {
    // set timeout to null (not to 0!), see stream_select() description
    $timeout_usec = NULL;
    $timeout = NULL;
  }

  $descriptorspec = [
    //0 => [ 'pipe', 'r' ], // stdin
    1 => [ 'pipe', 'w' ], // stdout
    2 => [ 'pipe', 'w' ]  // stderr
  ];

  //$process = proc_open($command, $descriptorspec, $pipes);
  $process = proc_open('exec ' . $command, $descriptorspec, $pipes); // exec prevent to use shell

  $stdout = $stderr = '';
  $runtime = 0;
  if (is_resource($process)) {
    //stream_set_blocking($pipes[0], 0); // Make stdin/stdout/stderr non-blocking
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);

    $start = microtime(TRUE);
    //while ($status['running'] !== FALSE)
    //while (feof($pipes[1]) === FALSE || feof($pipes[2]) === FALSE)
    while (TRUE) {
      $read = array();
      if (!feof($pipes[1])) { $read[] = $pipes[1]; }
      if (!feof($pipes[2])) { $read[] = $pipes[2]; }
      if (empty($read)) { break; }
      $write  = NULL;
      $except = NULL;
      stream_select($read, $write, $except, $timeout, $timeout_usec);

      // Read the contents from the buffers
      foreach ($read as $pipe) {
        if ($pipe === $pipes[1]) {
          $stdout .= fread($pipe, 8192);
        } elseif ($pipe === $pipes[2]) {
          $stderr .= fread($pipe, 8192);
        }
      }
      $runtime = microtime(TRUE) - $start;

      // Get the status of the process
      $status = proc_get_status($process);

      // Break from this loop if the process exited before timeout
      if (!$status['running']) {
        if (feof($pipes[1]) === FALSE) {
          // Very rare situation, seems as next proc_get_status() bug
          if (!isset($status_fix)) { $status_fix = $status; }
          if ($debug > 1) {
            print_error("Wrong process status! Issue in proc_get_status(), see: https://bugs.php.net/bug.php?id=69014");
          }
        } else {
          //var_dump($status);
          break;
        }
      }
      // Break from this loop if the process exited by timeout
      if ($timeout !== NULL) {
        $timeout_usec -= $runtime * 1000000;
        if ($timeout_usec < 0) {
          $status['running']  = FALSE;
          $status['exitcode'] = -1;
          break;
        }
      }
    }
    if ($status['running']) {
      // Fix sometimes wrong status, wait for 10 milliseconds
      $delay      = 0;
      $delay_step = 10000;  // 10ms
      $delay_max  = 300000; // 300ms
      while ($status['running'] && $delay < $delay_max) {
        usleep($delay_step);
        $status = proc_get_status($process);
        $delay += $delay_step;
      }
      $exec_status['exitdelay'] = (int)($delay / 1000); // Convert to ms
    } elseif (isset($status_fix)) {
      // See fixed proc_get_status() above
      $status = $status_fix;
    }
    $exec_status['exitcode'] = (int)$status['exitcode'];
    $exec_status['stderr']   = rtrim($stderr);
    $stdout = preg_replace('/(?:\n|\r\n|\r)$/D', '', $stdout); // remove last (only) eol

    proc_terminate($process, 9);
    //fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
  } else {
    // That happened on low memory for process
    //logfile('debug_exec.log', "Failed run external_exec(): '$command'.");
    //logfile('debug_exec.log', var_export($process, TRUE));
    $stdout = FALSE;
    $exec_status['stderr']   = '';
    $exec_status['exitcode'] = -1;
  }

  $exec_status['endtime'] = $start + $runtime; // store end unixtime with microseconds
  $exec_status['runtime'] = $runtime;
  $exec_status['stdout']  = $stdout;

  if ($debug > 0) {
    print_message('CMD EXITCODE['.($exec_status['exitcode'] !== 0 ? '%r' : '%g').$exec_status['exitcode'].'%n]'.PHP_EOL.
                  'CMD RUNTIME['.($runtime > 7 ? '%r' : '%g').round($runtime, 4).'s%n]', 'console');
    if ($exec_status['exitdelay'] > 0)
    {
      print_message("CMD EXITDELAY[%r".$exec_status['exitdelay']."ms%n]", 'console');
    }
    //print_message("STDOUT[\n".$stdout."\n]", 'console', FALSE);
    echo("STDOUT[\n".$stdout."\n]\n"); // Do not colouring stdout
    if ($exec_status['exitcode'] && $exec_status['stderr'])
    {
      // Show stderr if exitcode not 0
      //print_message("STDERR[\n".$exec_status['stderr']."\n]", 'console', FALSE);
      echo("STDERR[\n".$exec_status['stderr']."\n]\n"); // Do not colouring stdout
    }
  }

  return $stdout;
}

/**
 * Get information about process by it identifier (pid)
 *
 * @param int     $pid    The process identifier.
 * @param boolean $stats  If true, additionally show cpu/memory stats
 * @return array|false  Array with information about process, If process not found, return FALSE
 */
function get_pid_info($pid, $stats = FALSE) {
  $pid = (int)$pid;
  if ($pid < 1) {
    print_debug("Incorrect PID passed");
    //trigger_error("PID ".$pid." doesn't exists", E_USER_WARNING);
    return FALSE;
  }

  if (!$stats && stripos(PHP_OS, 'Linux') === 0) {
    // Do not use call to ps on Linux and extended stat not required
    // FIXME. Need something same on BSD and other Unix platforms

    if ($pid_stat = lstat("/proc/$pid")) {
      $pid_info = [ 'PID' => (string)$pid ];
      $ps_stat = explode(" ", file_get_contents("/proc/$pid/stat"));
      //echo PHP_EOL; print_vars($ps_stat); echo PHP_EOL;
      //echo PHP_EOL; print_vars($pid_stat); echo PHP_EOL;
      $pid_info['PPID']         = $ps_stat[3];
      $pid_info['UID']          = $pid_stat['uid'].'';
      $pid_info['GID']          = $pid_stat['gid'].'';
      $pid_info['STAT']         = $ps_stat[2];
      $pid_info['COMMAND']      = trim(str_replace("\0", " ", file_get_contents("/proc/$pid/cmdline")));
      $pid_info['STARTED']      = date("r", $pid_stat['mtime']);
      $pid_info['STARTED_UNIX'] = $pid_stat['mtime'];
    } else {
      $pid_info = FALSE;
    }

  } else {
    // Use ps call, have troubles on high load systems!

    if ($stats) {
      // Add CPU/Mem stats
      $options = 'pid,ppid,uid,gid,pcpu,pmem,vsz,rss,tty,stat,time,lstart,args';
    } else {
      $options = 'pid,ppid,uid,gid,tty,stat,time,lstart,args';
    }

    //$timezone = get_timezone(); // Get system timezone info, for correct started time conversion

    $ps = external_exec('/bin/ps -ww -o '.$options.' -p '.$pid, 1); // Set timeout 1sec for exec
    $ps = explode("\n", rtrim($ps));

    if ($GLOBALS['exec_status']['exitcode'] === 127) {
      print_debug("/bin/ps command not found, not possible to get process info.");
      return NULL;
    }
    if ($GLOBALS['exec_status']['exitcode'] !== 0 || count($ps) < 2) {
      print_debug("PID ".$pid." doesn't exists");
      //trigger_error("PID ".$pid." doesn't exists", E_USER_WARNING);
      return FALSE;
    }

    // "  PID  PPID   UID   GID %CPU %MEM    VSZ   RSS TT       STAT     TIME                  STARTED COMMAND"
    // "14675 10250  1000  1000  0.0  0.2 194640 11240 pts/4    S+   00:00:00 Mon Mar 21 14:48:08 2016 php ./test_pid.php"
    //
    // "  PID  PPID   UID   GID TT       STAT     TIME                  STARTED COMMAND"
    // "14675 10250  1000  1000 pts/4    S+   00:00:00 Mon Mar 21 14:48:08 2016 php ./test_pid.php"
    //print_vars($ps);

    // Parse output
    $keys = preg_split("/\s+/", $ps[0], -1, PREG_SPLIT_NO_EMPTY);
    $entries = preg_split("/\s+/", $ps[1], count($keys) - 1, PREG_SPLIT_NO_EMPTY);
    $started = preg_split("/\s+/", array_pop($entries), 6, PREG_SPLIT_NO_EMPTY);
    $command = array_pop($started);

    //$started[]    = str_replace(':', '', $timezone['system']); // Add system TZ to started time
    $started[]    = external_exec('date "+%z"');  // Add system TZ to started time
    $started_rfc  = array_shift($started) . ','; // Sun
    // Reimplode and convert to RFC2822 started date 'Sun, 20 Mar 2016 18:01:53 +0300'
    $started_rfc .= ' ' . $started[1]; // 20
    $started_rfc .= ' ' . $started[0]; // Mar
    $started_rfc .= ' ' . $started[3]; // 2016
    $started_rfc .= ' ' . $started[2]; // 18:01:53
    $started_rfc .= ' ' . $started[4]; // +0300
    //$started_rfc .= implode(' ', $started);
    $entries[] = $started_rfc;

    $entries[] = $command; // Re-add command
    //print_vars($entries);
    //print_vars($started);

    $pid_info = [];
    foreach ($keys as $i => $key) {
      $pid_info[$key] = $entries[$i];
    }
    $pid_info['STARTED_UNIX'] = strtotime($pid_info['STARTED']);
    //print_vars($pid_info);

  }

  return $pid_info;
}

/**
 * Add information about process into DB
 *
 * @param array|int $device Device array
 * @param int       $pid    PID for process. If empty used current PHP process ID
 * @return int        DB id for inserted row
 */
function add_process_info($device, $pid = NULL)
{
  global $argv, $config;

  $process_name = OBS_SCRIPT_NAME;
  $process      = OBS_PROCESS_NAME;

  // Ability for skip any process checking
  // WARNING. USE AT OWN RISK
  if (isset($config['check_process'][$process]) && !$config['check_process'][$process])
  {
    if (OBS_DEBUG)
    {
      print_error("WARNING. Process '$process_name' adding disabled.");
    }
    return NULL;
  }

  // Check if device_id passed instead array
  if (is_numeric($device))
  {
    $device = array('device_id' => $device);
  }
  if (!is_numeric($pid))
  {
    $pid = getmypid();
  }
  $pid_info = get_pid_info($pid);

  if (is_array($pid_info))
  {
    if ($process_name === 'poller.php' || $process_name === 'alerter.php')
    {
      // Try detect parent poller wrapper
      $parent_info = $pid_info;
      do
      {
        $found = FALSE;
        $parent_info = get_pid_info($parent_info['PPID']);
        if (str_contains($parent_info['COMMAND'], $process_name)) {
          $found = TRUE;
        }
        elseif (str_contains($parent_info['COMMAND'], 'poller-wrapper.py')) {
          $pid_info['PPID'] = $parent_info['PID'];
        }
      } while ($found);
    }
    $update_array = [
      'process_pid'     => $pid,
      'process_name'    => $process_name,
      'process_ppid'    => $pid_info['PPID'],
      'process_uid'     => $pid_info['UID'],
      'process_command' => $pid_info['COMMAND'],
      'process_start'   => $pid_info['STARTED_UNIX'],
      'device_id'       => $device['device_id']
    ];
    if ($config['poller_id'] > 0 && is_cli()) {
      $update_array['poller_id'] = $config['poller_id'];
    }
    return dbInsert($update_array, 'observium_processes');
  }
  print_debug("Process info not added for PID: $pid");

  return NULL;
}

/**
 * Delete information about process from DB
 *
 * @param array  $device       Device array
 * @param int    $pid          PID for process. If empty used current PHP process ID
 * @return int                 DB id for inserted row
 */
function del_process_info($device, $pid = NULL)
{
  global $argv, $config;

  $process_name = basename($argv[0]);

  // Ability for skip any process checking
  // WARNING. USE AT OWN RISK
  $process = str_replace('.php', '', $process_name);
  if (isset($config['check_process'][$process]) && !$config['check_process'][$process])
  {
    if (OBS_DEBUG)
    {
      print_error("WARNING. Process '$process_name' delete disabled.");
    }
    return NULL;
  }

  // Check if device_id passed instead array
  if (is_numeric($device))
  {
    $device = array('device_id' => $device);
  }
  if (!is_numeric($pid))
  {
    $pid = getmypid();
  }

  if ($pid) {
    $params = [ $pid, $process_name, $device['device_id'], $config['poller_id'] ];

    return dbDelete('observium_processes', '`process_pid` = ? AND `process_name` = ? AND `device_id` = ? AND `poller_id` = ?', $params);
  }

  return NULL;
}

function check_process_run($device, $pid = NULL)
{
  global $config, $argv;

  $check = FALSE;

  $process_name = basename($argv[0]);

  // Ability for skip any process checking
  // WARNING. USE AT OWN RISK
  $process = str_replace('.php', '', $process_name);
  if (isset($config['check_process'][$process]) && !$config['check_process'][$process])
  {
    if (OBS_DEBUG)
    {
      print_error("WARNING. Process '$process_name' checking disabled.");
    }
    return $check;
  }

  // Check if device_id passed instead array
  if (is_numeric($device))
  {
    $device = array('device_id' => $device);
  }

  $query  = 'SELECT * FROM `observium_processes` WHERE `process_name` = ? AND `device_id` = ? AND `poller_id` = ?';
  $params = [ $process_name, $device['device_id'], $config['poller_id'] ];
  if (is_numeric($pid))
  {
    $query .= ' AND `process_pid` = ?';
    $params[] = (int) $pid;
  }

  foreach (dbFetchRows($query, $params) as $process)
  {
    // We found processes in DB, check if it exist on system
    $pid_info = get_pid_info($process['process_pid']);
    if (is_array($pid_info) && strpos($pid_info['COMMAND'], $process_name) !== FALSE)
    {
      // Process still running
      $check = array_merge($pid_info, $process);
    } else {
      // Remove stalled DB entries
      dbDelete('observium_processes', '`process_id` = ?', array($process['process_id']));
    }
  }

  return $check;
}

/**
 * Determine array is associative?
 *
 * @param array $array
 * @return boolean
 */
function is_array_assoc($array) {
  if (PHP_VERSION_ID >= 80100) {
    return is_array($array) && !array_is_list($array);
  }
  return is_array($array) && $array !== array_values($array);
}

/**
 * Determine array is sequential list?
 *
 * @param array $array
 * @return boolean
 */
function is_array_list($array) {
  if (PHP_VERSION_ID >= 80100) {
    return is_array($array) && array_is_list($array);
  }
  return is_array($array) && $array === array_values($array);
}

/**
 * Checks if the given key or index exists in the array.
 * Case-insensitive implementation
 *
 * @param string|int $key Value to check.
 * @param array $array An array with keys to check.
 *
 * @return bool
 */
function array_key_iexists($key, array $array) {
  return in_array(strtolower($key), array_map('strtolower', array_keys($array)), TRUE);
}

/**
 * Get all values from specific key in a multidimensional array
 *
 * @param $key string
 * @param $arr array
 * @return null|string|array
 */
function array_value_recursive($key, array $arr) {
  $val = [];
  array_walk_recursive($arr, function($v, $k) use($key, &$val) {
    if ($k == $key) { array_push($val, $v); }
  });

  return count($val) > 1 ? $val : array_pop($val);
}

/**
 * @param        $array
 * @param        $string
 * @param string $delimiter
 *
 * @return mixed|null
 */
function array_get_nested($array, $string, $delimiter = '->') {
  foreach (explode($delimiter, $string) as $key) {
    if (!array_key_exists($key, (array)$array)) {
      return NULL;
    }
    $array = $array[$key];
  }

  return $array;
}

/**
 * Insert a value or key/value pair after a specific key in an array.  If key doesn't exist, value is appended
 * to the end of the array.
 *
 * @param array $array
 * @param string $key
 * @param array|string $new
 *
 * @return array
 */
function array_push_after(array $array, $key, $new) {
  $keys  = array_keys($array);
  $index = array_search($key, $keys, TRUE);
  $count = count($array);
  if ($index === FALSE) {
    return array_merge_recursive($array, (array)$new);
  }
  $pos = $index + 1;

  return array_merge_recursive(array_slice($array, 0, $pos, TRUE), (array)$new, array_slice($array, $pos, $count - 1, TRUE));
}


/**
 * Fast string compare function, checks if string contain $needle
 * Note: function renamed from str_contains() for not to intersect with php8 function.
 *
 * @param string $string              The string to search in
 * @param mixed  $needle              If needle is not a string, it is converted to an string
 * @param mixed  $encoding            For use "slow" multibyte compare, pass required encoding here (ie: UTF-8)
 * @param bool   $case_insensitivity  If case_insensitivity is TRUE, comparison is case insensitive
 * @return bool                       Returns TRUE if $string starts with $needle or FALSE otherwise
 */
function str_contains_array($string, $needle, $encoding = FALSE, $case_insensitivity = FALSE) {
  // If needle is array, use recursive compare
  if (is_array($needle)) {
    foreach ($needle as $findme) {
      if (str_contains_array($string, (string)$findme, $encoding, $case_insensitivity)) {
        $GLOBALS['str_last_needle'] = (string)$findme;
        return TRUE;
      }
    }
    $GLOBALS['str_last_needle'] = (string)$findme;
    return FALSE;
  }

  $needle  = (string)$needle;
  $GLOBALS['str_last_needle'] = $needle;
  $compare = $string === $needle;
  if ($needle === '') { return $compare; }
  if ($case_insensitivity) {
    // Case-INsensitive

    // NOTE, multibyte compare required mb_* functions and slower than general functions
    if ($encoding && check_extension_exists('mbstring') &&
        mb_strlen($string, $encoding) !== strlen($string)) {
      //$encoding = 'UTF-8';
      //return mb_strripos($string, $needle, -mb_strlen($string, $encoding), $encoding) !== FALSE;
      return $compare || mb_stripos($string, $needle) !== FALSE;
    }

    return $compare || stripos($string, $needle) !== FALSE;
  }
  // Case-sensitive
  return $compare || strpos($string, $needle) !== FALSE;
}

function str_icontains_array($string, $needle, $encoding = FALSE) {
  return str_contains_array($string, $needle, $encoding, TRUE);
}

/**
 * Fast string compare function, checks if string begin with $needle
 *
 * @param string $string              The string to search in
 * @param mixed  $needle              If needle is not a string, it is converted to an string
 * @param mixed  $encoding            For use "slow" multibyte compare, pass required encoding here (ie: UTF-8)
 * @param boolean $case_insensitivity If case_insensitivity is TRUE, comparison is case insensitive
 * @return boolean                    Returns TRUE if $string starts with $needle or FALSE otherwise
 */
function str_starts($string, $needle, $encoding = FALSE, $case_insensitivity = FALSE) {
  // If needle is array, use recursive compare
  if (is_array($needle)) {
    foreach ($needle as $findme) {
      if (str_starts($string, (string)$findme, $encoding, $case_insensitivity)) {
        $GLOBALS['str_last_needle'] = (string)$findme;
        return TRUE;
      }
    }
    $GLOBALS['str_last_needle'] = (string)$findme;
    return FALSE;
  }

  $needle = (string)$needle;
  $GLOBALS['str_last_needle'] = $needle;
  if ($needle === '') { return $string === $needle; }
  if ($case_insensitivity) {
    // Case-INsensitive

    // NOTE, multibyte compare required mb_* functions and slower than general functions
    if ($encoding &&
        check_extension_exists('mbstring') && mb_strlen($string, $encoding) !== strlen($string)) {
      //$encoding = 'UTF-8';
      return mb_strripos($string, $needle, -mb_strlen($string, $encoding), $encoding) !== FALSE;
    }

    return $needle !== ''
           ? strncasecmp($string, $needle, strlen($needle)) === 0
           : $string === '';
  }

  // PHP 8.0 simplify for case sensitive compare
  if (PHP_VERSION_ID >= 80000) {
    return str_starts_with((string)$string, $needle);
  }

  // Case-sensitive
  return $string[0] === $needle[0] && strncmp($string, $needle, strlen($needle)) === 0;
}

function str_istarts($string, $needle, $encoding = FALSE) {
  return str_starts($string, $needle, $encoding, TRUE);
}

/**
 * Fast string compare function, checks if string end with $needle
 *
 * @param string $string              The string to search in
 * @param mixed  $needle              If needle is not a string, it is converted to an string
 * @param mixed  $encoding            For use "slow" multibyte compare, pass required encoding here (ie: UTF-8)
 * @param boolean $case_insensitivity If case_insensitivity is TRUE, comparison is case insensitive
 * @return boolean                    Returns TRUE if $string ends with $needle or FALSE otherwise
 */
function str_ends($string, $needle, $encoding = FALSE, $case_insensitivity = FALSE) {
  // If needle is array, use recursive compare
  if (is_array($needle)) {
    foreach ($needle as $findme) {
      if (str_ends($string, (string)$findme, $encoding, $case_insensitivity)) {
        $GLOBALS['str_last_needle'] = (string)$findme;
        return TRUE;
      }
    }
    $GLOBALS['str_last_needle'] = (string)$findme;
    return FALSE;
  }

  $needle  = (string)$needle;
  $GLOBALS['str_last_needle'] = $needle;
  $compare = $needle !== '';
  if ($needle === '') { return $string === $needle; }

  // NOTE, multibyte compare required mb_* functions and slower than general functions
  if ($encoding && $compare &&
      check_extension_exists('mbstring') && mb_strlen($string, $encoding) != strlen($string)) {
    //$encoding = 'UTF-8';
    $diff = mb_strlen($string, $encoding) - mb_strlen($needle, $encoding);
    if ($case_insensitivity) {
      return $diff >= 0 && mb_stripos($string, $needle, $diff, $encoding) !== FALSE;
    }
    return $diff >= 0 && mb_strpos($string, $needle, $diff, $encoding) !== FALSE;
  }

  // PHP 8.0 simplify for case sensitive compare
  if (!$case_insensitivity && PHP_VERSION_ID >= 80000) {
    return str_ends_with((string)$string, $needle);
  }

  $nlen = strlen($needle);

  return $compare
         ? substr_compare($string, $needle, -$nlen, $nlen, $case_insensitivity) === 0
         : $string === '';
}

function str_iends($string, $needle, $encoding = FALSE) {
  return str_ends($string, $needle, $encoding, TRUE);
}

/**
 * Compress long strings to hexified compressed string. Can be uncompressed by str_decompress().
 *
 * @param string $string
 * @param string $encode Encoding for return string: hex or base64 (shorten)
 *
 * @return string
 */
function str_compress($string, $encode = 'base64') {
  if (!is_string($string)) { return $string; }

  if ($compressed = gzdeflate($string,  9)) {
    $compressed = gzdeflate($compressed, 9);

    if (OBS_DEBUG > 1) {
      $compressed = $encode === 'hex' ? bin2hex($compressed) : safe_base64_encode($compressed);
      print_cli("DEBUG: String '$string' [".strlen($string)."] compressed to '".$compressed."' [".strlen($compressed)."].");
      return $compressed;
    }

    return $encode === 'hex' ? bin2hex($compressed) : safe_base64_encode($compressed);
  }

  return $string;
}

/**
 * Decompress strings compressed by str_compress().
 *
 * @param string $compressed
 *
 * @return string
 */
function str_decompress($compressed) {
  if (!is_string($compressed) || !ctype_print($compressed) || !$bin = safe_base64_decode($compressed)) { return FALSE; }

  $string = gzinflate(gzinflate($bin));
  if (!is_string($string) &&
      // COMPAT with old style, encoded by hex (can be removed in 2023 @mike)
      !(ctype_xdigit($compressed) && ($bin = hex2bin($compressed)) && ($string = gzinflate(gzinflate($bin))))) {
    // Not an compressed string?
    //var_dump(hex2bin($compressed));
    //var_dump(gzinflate(hex2bin($compressed)));
    //var_dump($string);

    return FALSE;
  }

  if (OBS_DEBUG > 1) {
    print_cli("DEBUG: String '$compressed' [".strlen($compressed)."] decompressed to '".$string."' [".strlen($string)."].");
  }
  return $string;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function is_cli() {
  if (defined('__PHPUNIT_PHAR__') && isset($GLOBALS['cache']['is_cli'])) {
    // Allow override is_cli() in PHPUNIT
    return $GLOBALS['cache']['is_cli'];
  }
  if (!defined('OBS_CLI')) {
    define('OBS_CLI', PHP_SAPI === 'cli' && empty($_SERVER['REMOTE_ADDR']));
    if (OBS_DEBUG > 1) {
      print_cli("DEBUG: is_cli() == " . (OBS_CLI ? 'TRUE' : 'FALSE') . ", PHP_SAPI: '" . PHP_SAPI . "', REMOTE_ADDR: '" . $_SERVER['REMOTE_ADDR'] . "'");
    }
  }

  return OBS_CLI;
}

function cli_is_piped() {
  if (!defined('OBS_CLI_PIPED')) {
    define('OBS_CLI_PIPED', check_extension_exists('posix') && !posix_isatty(STDOUT));
  }

  return OBS_CLI_PIPED;
}

// Detect if script runned from crontab
// DOCME needs phpdoc block
// TESTME needs unit testing
function is_cron()
{
  if (!defined('OBS_CRON'))
  {
    $cron = is_cli() && !isset($_SERVER['TERM']);
    // For more accurate check if STDOUT exist (but this requires posix extension)
    if ($cron)
    {
      $cron = $cron && cli_is_piped();
    }
    define('OBS_CRON', $cron);
  }

  return OBS_CRON;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function print_prompt($text, $default_yes = FALSE) {
  if (is_cli()) {
    if (cli_is_piped()) {
      // If now not have interactive TTY skip any prompts, return default
      return TRUE && $default_yes;
    }

    $question = ($default_yes ? 'Y/n' : 'y/N');
    echo trim($text), " [$question]: ";
    $handle = fopen ('php://stdin', 'r');
    $line  = strtolower(trim(fgets($handle, 3)));
    fclose($handle);
    if ($default_yes)
    {
      $return = ($line === 'no' || $line === 'n');
    } else {
      $return = ($line === 'yes' || $line === 'y');
    }
  } else {
    // Here placeholder for web prompt
    $return = TRUE && $default_yes;
  }

  return $return;
}

/**
 * This function echoes text with style 'debug', see print_message().
 * Here checked constant OBS_DEBUG, if OBS_DEBUG not set output - empty.
 *
 * @param string $text
 * @param boolean $strip Stripe special characters (for web) or html tags (for cli)
 */
function print_debug($text, $strip = FALSE)
{
  if (defined('OBS_DEBUG') && OBS_DEBUG > 0)
  {
    print_message($text, 'debug', $strip);
  }
}

/**
 * This function echoes text with style 'error', see print_message().
 *
 * @param string $text
 * @param boolean $strip Stripe special characters (for web) or html tags (for cli)
 */
function print_error($text, $strip = TRUE)
{
  print_message($text, 'error', $strip);
}

/**
 * This function echoes text with style 'warning', see print_message().
 *
 * @param string $text
 * @param boolean $strip Stripe special characters (for web) or html tags (for cli)
 */
function print_warning($text, $strip = TRUE)
{
  print_message($text, 'warning', $strip);
}

/**
 * This function echoes text with style 'success', see print_message().
 *
 * @param string $text
 * @param boolean $strip Stripe special characters (for web) or html tags (for cli)
 */
function print_success($text, $strip = TRUE)
{
  print_message($text, 'success', $strip);
}

/**
 * This function echoes text with specific styles (different for cli and web output).
 *
 * @param string $text
 * @param string $type Supported types: default, success, warning, error, debug
 * @param boolean $strip Stripe special characters (for web) or html tags (for cli)
 */
function print_message($text, $type='', $strip = TRUE)
{
  global $config;

  // Do nothing if input text not any string (like NULL, array or other). (Empty string '' still printed).
  if (!is_string($text) && !is_numeric($text)) { return NULL; }

  $type = strtolower(trim($type));
  switch ($type)
  {
    case 'success':
      $color = array('cli'       => '%g',                   // green
                     'cli_color' => FALSE,                  // by default cli coloring disabled
                     'class'     => 'alert alert-success'); // green
      $icon  = 'oicon-tick-circle';
      break;
    case 'warning':
      $color = array('cli'       => '%b',                   // blue
                     'cli_color' => FALSE,                  // by default cli coloring disabled
                     'class'     => 'alert alert-warning');               // yellow
      $icon  = 'oicon-bell';
      break;
    case 'error':
      $color = array('cli'       => '%r',                   // red
                     'cli_color' => FALSE,                  // by default cli coloring disabled
                     'class'     => 'alert alert-danger');   // red
      $icon  = 'oicon-exclamation-red';
      break;
    case 'debug':
      $color = array('cli'       => '%r',                   // red
                     'cli_color' => FALSE,                  // by default cli coloring disabled
                     'class'     => 'alert alert-danger');  // red
      $icon  = 'oicon-exclamation-red';
      break;
    case 'color':
      $color = array('cli'       => '',                     // none
                     'cli_color' => TRUE,                   // allow using coloring
                     'class'     => 'alert alert-info');    // blue
      $icon  = 'oicon-information';
      break;
    case 'console':
      // This is special type used nl2br conversion for display console messages on WUI with correct line breaks
      $color = array('cli'       => '',                     // none
                     'cli_color' => TRUE,                   // allow using coloring
                     'class'     => 'alert alert-suppressed'); // purple
      $icon  = 'oicon-information';
      break;
    default:
      $color = array('cli'       => '%W',                   // bold
                     'cli_color' => FALSE,                  // by default cli coloring disabled
                     'class'     => 'alert alert-info');    // blue
      $icon  = 'oicon-information';
      break;
  }

  if (is_cli())
  {
    if ($strip)
    {
      $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8'); // Convert special HTML entities back to characters
      $text = str_ireplace(array('<br />', '<br>', '<br/>'), PHP_EOL, $text); // Convert html <br> to into newline
      $text = strip_tags($text);
    }
    // Store to global var
    if ($type !== 'debug') { $GLOBALS['last_message'] = $text; }
    if ($type === 'debug' && !$color['cli_color'])
    {
      // For debug just echo message.
      echo($text . PHP_EOL);
    } else {
      print_cli($color['cli'].$text.'%n'.PHP_EOL, $color['cli_color']);
    }
  } else {
    $GLOBALS['last_message'] = $text;
    if ($text === '') {
      // Do not web output if the string is empty
      return NULL;
    }
    if ($strip)
    {
      if ($text == strip_tags($text))
      {
        // Convert special characters to HTML entities only if text not have html tags
        $text = escape_html($text);
      }
      if ($color['cli_color'])
      {
        // Replace some Pear::Console_Color2 color codes with html styles
        $replace = array('%',                                  // '%%'
                         '</span>',                            // '%n'
                         '<span class="label label-warning">', // '%y'
                         '<span class="label label-success">', // '%g'
                         '<span class="label label-danger">',  // '%r'
                         '<span class="label label-primary">', // '%b'
                         '<span class="label label-info">',    // '%c'
                         '<span class="label label-default">', // '%W'
                         '<span class="label label-default" style="color:black;">', // '%k'
                         '<span style="font-weight: bold;">',  // '%_'
                         '<span style="text-decoration: underline;">', // '%U'
                         );
      } else {
        $replace = array('%', '');
      }
      $text = str_replace(array('%%', '%n', '%y', '%g', '%r', '%b', '%c', '%W', '%k', '%_', '%U'), $replace, $text);
    }

    $msg = PHP_EOL.'    <div class="'.$color['class'].'">';
    if ($type != 'warning' && $type != 'error')
    {
      $msg .= '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    }
    if ($type == 'console')
    {
      $text = nl2br(trim($text)); // Convert newline to <br /> for console messages with line breaks
    }

    $msg .= '
      <div>'.$text.'</div>
    </div>'.PHP_EOL;

    echo($msg);
  }
}

function get_last_message() {
  if (!isset($GLOBALS['last_message'])) { return NULL; }
  $text = str_replace(array('%%', '%n', '%y', '%g', '%r', '%b', '%c', '%W', '%k', '%_', '%U'), '', $GLOBALS['last_message']);

  // Reset message for prevent errors in loops
  //unset($GLOBALS['last_message']);

  if (preg_match('/^[A-Z\_ ]+\[(.+)\]$/s', $text, $matches)) {
    // CLI messages like:
    // RESPONSE ERROR[You must use an API key to authenticate each request]
    return $matches[1];
  }
  return strip_tags($text);
}

function print_cli($text, $colour = TRUE)
{

  $msg = new Console_Color2();

  // Always append reset colour at text end
  if ($colour && str_contains($text, '%')) {
    $text .= '%n';
  }

  echo $msg->convert($text, $colour);
}

/**
 * Determine if poller/discovery module enabled for device
 *
 * @param array       $device Device array
 * @param string      $module Module name
 * @param string|null $process Process name (poller, discovery). Try to detect if not passed.
 *
 * @return bool
 */
function is_module_enabled($device, $module, $process = NULL, $settings = TRUE) {
  global $config;

  // Detect used process (poller, discovery, etc)
  if (is_null($process) || !in_array($process, [ 'poller', 'discovery' ])) {
    list($process) = explode('.', basename($GLOBALS['argv'][0]));
  }

  if (!in_array($process, [ 'poller', 'discovery' ])) {
    print_debug("Module [$module] skipped. Not specified process name (poller or discovery).");
    return FALSE;
  }

  // Detect if need to check submodule, ie: ports_ifstats
  list($module, $submodule) = explode('_', $module, 2);
  /* Allow specify module name with process prefix (poller_, discovery_), currently disabled
  if (in_array($module, ['poller', 'discovery']))
  {
    $process = $module;
    list($module, $submodule) = explode('_', $submodule, 2);
  }
  */

  //print_vars($process);
  //print_vars($module);
  //print_vars($submodule);

  // Pre check if module is known (discovery_modules or poller_modules global config)
  if (!isset($config[$process . '_modules'][$module])) {
    print_debug("Module [$module] not exist.");
    return FALSE;
  }

  if (!safe_empty($submodule)) {
    // Check enabled only submodule

    // Ie: $config['enable_ports_ipifstats']
    $module_name = $module.'_'.$submodule;
    $setting_name = 'enable_'.$module_name;
    if (isset($config[$setting_name])) {
      // Globally enabled/disabled submodule
      $ok = (bool)$config[$setting_name];
      $debug_reason = "disabled globally by config";
    } else {
      // Unknown Submodule
      print_debug("Submodule [$module_name] not exist.");
      return FALSE;
    }

    if ($settings) {
      $attrib = get_entity_attrib('device', $device, $setting_name);
    }
    if ($module_name === 'ports_junoseatmvp' && $device['os'] !== 'junose') {
      // Some hardcoded excludes
      $ok = FALSE;
      $debug_reason = "excluded for device os";
    } elseif ($settings && !safe_empty($attrib)) {
      // Submodule set by device setting
      $ok = (bool)$attrib;
      $debug_reason = "disabled by device setting";
    } elseif (isset($config['os'][$device['os']]['modules'][$module_name])) {
      // Submodule set by os definition
      $ok           = (bool)$config['os'][$device['os']]['modules'][$module_name];
      $debug_reason = "disabled by os definition";
    } else {
      $model = get_model_array($device);
      if (isset($model[$module_name])) {
        // Submodule set by model definition
        $ok = (bool)$model[$module_name];
        $debug_reason = "disabled by model definition";
      } elseif ($module_name === 'ports_separate_walk' && isset($config['os'][$device['os']][$module_name])) {
        // FIXME. Need refactor:
        // $config['os'][$os]['ports_separate_walk'] -> $config['os'][$os]['modules']['ports_separate_walk']
        // Submodule set by os definition
        $ok = (bool)$config['os'][$device['os']][$module_name];
        $debug_reason = "disabled by os definition";
        if ($device['os'] === 'junos' && !safe_empty($device['version']) && strnatcmp($device['version'], '17') > 0) {
          // Hardcoded exclude for JunOS greater than 17+
          // I not know how set this in definition, currently need only for junos
          $ok = FALSE;
          $debug_reason = "forced to disable for JunOS greater than 17+";
        }
      }
    }
    //print_warning("Module: $module_name = ".($ok ? 'ENABLED' : 'DISABLED'));

  } else {
    // Check main module
    $module_name = $module;

    // Globally enabled/disabled module
    $ok = (bool)$config[$process . '_modules'][$module];
    $debug_reason = "disabled globally by config";

    if ($process === 'poller') {
      if ($module === 'os' || $module === 'system') {
        // os, system modules always enabled for poller
        //print_debug("Module [$module] always enabled in process [$process].");
        $GLOBALS['cache']['devices'][$process.'_modules'][$device['device_id']]['enabled'][] = $module;
        return TRUE;
      }
      if (!function_exists('poller_module_excluded')) {
        // In discovery this function missed
        include_once($config['install_dir'] . "/includes/polling/functions.inc.php");
      }
      if (poller_module_excluded($device, $module)) {
        // Module excluded
        print_debug("Module [$module] excluded in process [$process].");
        $GLOBALS['cache']['devices'][$process.'_modules'][$device['device_id']]['excluded'][] = $module;
        return FALSE;
      }
      $setting_name = 'poll_'.$module;
    } elseif ($process === 'discovery') {
      if ($module === 'os') { // || $module == 'mibs')
        // os module always enabled for discovery
        //print_debug("Module [$module] always enabled in process [$process].");
        $GLOBALS['cache']['devices'][$process.'_modules'][$device['device_id']]['enabled'][] = $module;
        return TRUE;
      }
      $setting_name = 'discover_'.$module;
    }

    if ($settings) {
      $attrib = get_entity_attrib('device', $device, $setting_name);
    }
    if ($settings && !safe_empty($attrib)) {
      // Module set by device setting
      $ok = (bool)$attrib;
      $debug_reason = "disabled by device setting";
    } elseif (in_array($module, (array)$config['os'][$device['os']][$process . '_blacklist'], TRUE)) {
      // Module blacklisted by os definition
      // $config['os'][$os]['poller_blacklist']     (array)  list of blacklisted poller modules
      // $config['os'][$os]['discovery_blacklist']  (array)  list of blacklisted discovery modules
      //$ok = FALSE;
      //$debug_reason = "blacklisted by os definition";
      $GLOBALS['cache']['devices'][$process.'_modules'][$device['device_id']]['excluded'][] = $module;
      print_debug("Module [$module] blacklisted by os definition in process [$process].");
      return FALSE;
    } elseif ($ok && isset($config['os'][$device['os']]['modules'][$module_name])) {
      // use os definition if module globally enabled (only), ie unix-agent: globally disabled, default disabled byt enabled on unix group
      // Module set by os definition
      $ok = (bool)$config['os'][$device['os']]['modules'][$module_name];
      $debug_reason = "disabled by os definition";
    } elseif ($module === 'wifi' && !in_array($device['type'], [ 'network', 'firewall', 'wireless' ], TRUE)) {
      // WiFi by default allowed only for network/wireless/firewall types
      $ok = FALSE;
      $debug_reason = "disabled for device type '{$device['type']}'.";
    } /* else {
      $model = get_model_array($device);
      if (isset($model[$module_name])) {
        // Submodule set by model definition
        $ok = (bool)$model[$module_name];
        $debug_reason = "disabled by model definition";
      }
    } */
  }

  if (!$ok) {
    print_debug("Module [$module_name] $debug_reason in process [$process].");
    $GLOBALS['cache']['devices'][$process.'_modules'][$device['device_id']]['disabled'][] = $module;
  } else {
    $GLOBALS['cache']['devices'][$process.'_modules'][$device['device_id']]['enabled'][] = $module;
  }
  return $ok;
}

// TESTME needs unit testing
/**
 * Print an discovery/poller module stats
 *
 * @global array $GLOBALS['module_stats']
 * @param array $device Device array
 * @param string $module Module name
 */
function print_module_stats($device, $module)
{
  $log_event = FALSE;
  $stats_msg = array();
  foreach (array('added', 'updated', 'deleted', 'unchanged') as $key)
  {
    if ($GLOBALS['module_stats'][$module][$key])
    {
      $stats_msg[] = (int)$GLOBALS['module_stats'][$module][$key].' '.$key;
      if ($key != 'unchanged') { $log_event = TRUE; }
    }
  }
  if (count($GLOBALS['module_stats'][$module])) { echo(PHP_EOL); }
  if (count($stats_msg)) { print_cli_data("Changes", implode(', ', $stats_msg)); }
  if ($GLOBALS['module_stats'][$module]['time'])
  {
    print_cli_data("Duration", $GLOBALS['module_stats'][$module]['time']."s");
  }
  if ($log_event) { log_event(nicecase($module).': '.implode(', ', $stats_msg).'.', $device, 'device', $device['device_id']); }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function print_obsolete_config($filter = '')
{
  global $config;

  $list = array();
  foreach ($config['obsolete_config'] as $entry)
  {
    if ($filter && strpos($entry['old'], $filter) === FALSE) { continue; }
    $old = explode('->', $entry['old']);
    // Founded obsolete config in overall $config
    $found = !is_null(array_get_nested($config, $entry['old']));
    if ($found && is_null(array_get_nested(get_defined_settings(), $entry['old'])))
    {
      // Check if this is in config.php or in DB config
      $found = FALSE;
      // FIXME need migrate old DB config to new
    }

    if ($found)
    {
      $new  = explode('->', $entry['new']);
      $info = strlen($entry['info']) ? ' ('.$entry['info'].')' : '';
      $list[] = "  %r\$config['".implode("']['", $old)."']%n -> %g\$config['".implode("']['", $new)."']%n".$info;
    }
  }

  if ($list)
  {
    $msg = "%WWARNING%n Obsolete configuration(s) found in config.php file, please rename respectively:\n".implode(PHP_EOL, $list);
    print_message($msg, 'color');
    return TRUE;
  } else {
    return FALSE;
  }
}

// Check if php extension exist, than warn or fail
// DOCME needs phpdoc block
// TESTME needs unit testing
function check_extension_exists($extension, $text = FALSE, $fatal = FALSE)
{
  $extension = strtolower($extension);

  if (isset($GLOBALS['cache']['extension'][$extension]))
  {
    // Cached
    $exist = $GLOBALS['cache']['extension'][$extension];
  } else {

    $extension_functions = array(
      'ldap'     => 'ldap_connect',
      'mysql'    => 'mysql_connect',
      'mysqli'   => 'mysqli_connect',
      'mbstring' => 'mb_detect_encoding',
      'mcrypt'   => 'mcrypt_encrypt', // CLEANME, mcrypt not used anymore (deprecated since php 7.1, removed since php 7.2)
      'posix'    => 'posix_isatty',
      'session'  => 'session_name',
      'svn'      => 'svn_log'
    );

    if (isset($extension_functions[$extension]))
    {
      $exist = @function_exists($extension_functions[$extension]);
    } else {
      $exist = @extension_loaded($extension);
    }

    // Cache
    $GLOBALS['cache']['extension'][$extension] = $exist;
  }

  if (!$exist)
  {
    // Print error (only if $text not equals to FALSE)
    if ($text === '' || $text === TRUE)
    {
      // Generic message
      print_error("The extension '$extension' is missing. Please check your PHP configuration.");
    }
    elseif ($text !== FALSE)
    {
      // Custom message
      print_error("The extension '$extension' is missing. $text");
    } else {
      // Debug message
      print_debug("The extension '$extension' is missing. Please check your PHP configuration.");
    }

    // Exit if $fatal set to TRUE
    if ($fatal) { exit(2); }
  }

  return $exist;
}

/**
 * Sign function
 *
 * This function extracts the sign of the number.
 * Returns -1 (negative), 0 (zero), 1 (positive)
 *
 * @param integer|float $int
 * @return integer
 */
function sgn($int) {
  if ($int < 0) {
    return -1;
  }
  return $int > 0 ? 1 : 0;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function truncate($substring, $max = 50, $rep = '...') {
  if ($rep === '...' && !is_cli()) {
    // in html use entities triple dot
    $rep_len = 1;
    //$rep = '&hellip;';
    $rep = '&mldr;';
  } else {
    $rep_len = strlen($rep);
  }
  if (safe_empty($substring)) {
    //$string = $rep;
    return $rep;
  }

  $string = (string)$substring;

  if (strlen($string) > $max) {
    $leave = $max - $rep_len;
    return substr_replace($string, $rep, $leave);
  }
  return $string;
}

/**
 * Wrapper to htmlspecialchars()
 *
 * @param string $string
 * @param int    $flags
 *
 * @return string
 */
// TESTME needs unit testing
function escape_html($string, $flags = ENT_QUOTES) {

  $string = htmlspecialchars($string, $flags, 'UTF-8');

  // Un-escape allowed tags
  if (str_contains($string, '&lt;') && str_contains_array($string, $GLOBALS['config']['escape_html']['tags'])) {
    foreach ($GLOBALS['config']['escape_html']['tags'] as $tag) {
      $string = str_replace([ '&lt;' . $tag . '&gt;', '&lt;/' . $tag . '&gt;', '&lt;' . $tag . ' /&gt;', '&lt;' . $tag . '/&gt;' ],
                            [ '<' . $tag . '>',       '</' . $tag . '>',       '<' . $tag . ' />',       '<' . $tag . '/>' ],
                            $string);
    }
  }
  // Un-escape allowed entities
  if (str_contains($string, '&amp;') && str_contains_array($string, $GLOBALS['config']['escape_html']['entities'])) {
    foreach ($GLOBALS['config']['escape_html']['entities'] as $tag) {
      $string = str_replace('&amp;' . $tag . ';', '&' . $tag . ';', $string);
    }
  }

  return $string;
}

// Return random string with optional character list
// DOCME needs phpdoc block
// TESTME needs unit testing
function generate_random_string($max = 16, $characters = NULL)
{
  if (!$characters || !is_string($characters))
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  }

  $randstring = '';
  $length = strlen($characters) - 1;

  for ($i = 0; $i < $max; $i++)
  {
    $randstring .= $characters[random_int(0, $length)]; // Require PHP 7.x or random_compat
  }

  return $randstring;
}

// Backward compatible random string generator
// DOCME needs phpdoc block
// TESTME needs unit testing
function strgen($length = 16)
{
  return generate_random_string($length);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function safename($filename)
{
  return preg_replace('/[^a-zA-Z0-9._\-]/', '_', $filename);
}


// DOCME needs phpdoc block
// TESTME needs unit testing
function zeropad($num, $length = 2)
{
  return str_pad($num, $length, '0', STR_PAD_LEFT);
}

/**
 * Return model array from definitions, based on device sysObjectID
 *
 * @param	array	 $device          Device array required keys -> os, sysObjectID
 * @param	string $sysObjectID_new If passed, then use "new" sysObjectID instead from device array
 * @return array|FALSE            Model array or FALSE if no model specific definitions
 */
function get_model_array($device, $sysObjectID_new = NULL) {
  global $config, $cache;

  if (isset($config['os'][$device['os']]['model'])) {
    $model  = $config['os'][$device['os']]['model'];
    if (!isset($config['model'][$model])) { return FALSE; }

    $models = $config['model'][$model];
    $set_cache = FALSE;
    if ($sysObjectID_new && preg_match('/^\.\d[\d\.]+$/', $sysObjectID_new)) {
      // Use passed as param sysObjectID
      $sysObjectID = $sysObjectID_new;
    } elseif (isset($cache['devices']['model'][$device['device_id']])) {
      // Return already cached array if no passed param sysObjectID
      return $cache['devices']['model'][$device['device_id']];
    } elseif (preg_match('/^\.\d[\d\.]+$/', $device['sysObjectID'])) {
      // Use sysObjectID from device array
      $sysObjectID = $device['sysObjectID'];
      $set_cache = TRUE;
    } else {
      // Just random non empty string
      $sysObjectID = 'empty_sysObjectID_3948ffakc';
      $set_cache = TRUE;
    }

    if ($set_cache && (!is_numeric($device['device_id']) || defined('__PHPUNIT_PHAR__'))) {
      // Do not set cache for unknown device_id (not added device) or phpunit
      $set_cache = FALSE;
    }

    // Exactly sysObjectID match
    if (isset($models[$sysObjectID])) {
      // Check by device params, ie os version (see Juniper EX model definitions)
      if (isset($models[$sysObjectID]['test'])) {
        // Single associated array with test condition
        $models[$sysObjectID] = [ $models[$sysObjectID] ];
      }

      if (!is_array_assoc($models[$sysObjectID])) {
        // For cases when multiple test conditions for same sysObjectID
        $model = FALSE;
        foreach ($models[$sysObjectID] as $def) {
          if (discovery_check_requires($device, $def, $device, 'device')) { continue; }

          $model = $def;
          break;
        }
        $models[$sysObjectID] = $model;
      }

      if ($set_cache) {
        $cache['devices']['model'][$device['device_id']] = $models[$sysObjectID];
      }
      //r($models[$sysObjectID]);
      return $models[$sysObjectID];
    }

    // Resort sysObjectID array by oids with from high to low order!
    //krsort($config['model'][$model]);
    uksort($config['model'][$model], 'compare_numeric_oids_reverse');
    foreach ($config['model'][$model] as $key => $entry) {
      if (strpos($sysObjectID, $key) === 0) {
        // Check by device params, ie os version (see Juniper EX model definitions)
        if (isset($entry['test'])) {
          // Single associated array with test condition
          $entry = [ $entry ];
        }
        if (!is_array_assoc($entry)) {
          // For cases when multiple test conditions for same sysObjectID
          $model = FALSE;
          foreach ($entry as $def) {
            if (discovery_check_requires($device, $def, $device, 'device')) { continue; }

            $model = $def;
            break;
          }
          $entry = $model;
        }

        if ($set_cache) {
          $cache['devices']['model'][$device['device_id']] = $entry;
        }
        return $entry;
        break;
      }
    }
    // If model array not found, set cache entry to FALSE,
    // for do not search again
    if ($set_cache) {
      $cache['devices']['model'][$device['device_id']] = FALSE;
    }
  }
  return FALSE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function formatRates($value, $round = 2, $sf = 3)
{
   $value = format_si($value, $round, $sf) . "bps";
   return $value;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function formatStorage($value, $round = 2, $sf = 3)
{
   $value = format_bi($value, $round, $sf) . 'B';
   return $value;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function format_si($value, $round = 2, $sf = 3) {
  if (!is_numeric($value)) {
    print_debug("Passed incorrect value to ".__FUNCTION__."()");
    print_debug_vars($value);
    //return FALSE;
    return '0'; // incorrect, but for keep compatibility
  }

  if ($value < 0) {
    $neg = TRUE;
    $value *= -1;
  } else {
    $neg = FALSE;
  }

  // https://physics.nist.gov/cuu/Units/prefixes.html
  if ($value >= 0.1) {
    $sizes = [ '', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y' ];
    $ext = $sizes[0];
    for ($i = 1; (($i < count($sizes)) && ($value >= 1000)); $i++) {
      $value /= 1000;
      $ext = $sizes[$i];
    }
  } else {
    $sizes = [ '', 'm', 'µ', 'n', 'p', 'f', 'a', 'z', 'y' ];
    $ext = $sizes[0];
    for ($i = 1; (($i < count($sizes)) && ($value != 0) && ($value <= 0.1)); $i++) {
      $value *= 1000;
      $ext = $sizes[$i];
    }
  }

  if ($neg) { $value *= -1; }
  //print_warning("$value " . round($value, $round));

  return format_number_short(round($value, $round), $sf).$ext;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function format_bi($value, $round = 2, $sf = 3) {
  if (!is_numeric($value)) {
    print_debug("Passed incorrect value to ".__FUNCTION__."()");
    print_debug_vars($value);
    //return FALSE;
    return '0'; // incorrect, but for keep compatibility
  }

  if ($value < 0) {
    $neg = TRUE;
    $value *= -1;
  } else {
    $neg = FALSE;
  }
  $sizes = [ '', 'k', 'M', 'G', 'T', 'P', 'E' ];
  $ext = $sizes[0];
  for ($i = 1; (($i < count($sizes)) && ($value >= 1024)); $i++) {
    $value /= 1024;
    $ext = $sizes[$i];
  }

  if ($neg) { $value *= -1; }

  return format_number_short(round($value, $round), $sf).$ext;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function format_number($value, $base = '1000', $round = 2, $sf = 3)
{
  if ($base == '1000')
  {
    return format_si($value, $round, $sf);
  } else {
    return format_bi($value, $round, $sf);
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function format_value($value, $format = '', $round = 2, $sf = 3) {

  switch (strtolower((string)$format)) {
    case 'si':
    case '1000':
      $value = format_si($value, $round, $sf);
      break;
    case 'bi':
    case '1024':
      $value = format_bi($value, $round, $sf);
      break;

    case 'shorttime':
      $value = format_uptime($value, 'short');
      break;

    case 'uptime':
    case 'time':
      $value = format_uptime($value);
      break;

    default:
      if (is_numeric($value)) {
        $orig = $value;
        $value = sprintf("%01.{$round}f", $value);
        if (abs($orig) > 0 && preg_match('/^\-?0\.0+$/', $value)) {
          // prevent show small values as zero
          // ie 0.000627 as 0.00
          //r($orig);
          //r($value);
          $value = format_number_short($orig, $sf);
          //r($value);
        } else {
          $value = preg_replace(array( '/\.0+$/', '/(\.\d)0+$/' ), '\1', $value);
        }
      }
      elseif ($value === TRUE)  { $value = 'TRUE'; }
      elseif ($value === FALSE) { $value = 'FALSE'; }
      elseif (is_null($value))  { $value = 'NULL'; }
      elseif ($value === '')    { $value = '""'; }
  }

  return $value;
}

/**
 * Is Valid Hostname
 *
 * See: http://stackoverflow.com/a/4694816
 *      http://stackoverflow.com/a/2183140
 *
 * The Internet standards (Request for Comments) for protocols mandate that
 * component hostname labels may contain only the ASCII letters 'a' through 'z'
 * (in a case-insensitive manner), the digits '0' through '9', and the hyphen
 * ('-'). The original specification of hostnames in RFC 952, mandated that
 * labels could not start with a digit or with a hyphen, and must not end with
 * a hyphen. However, a subsequent specification (RFC 1123) permitted hostname
 * labels to start with digits. No other symbols, punctuation characters, or
 * white space are permitted. While a hostname may not contain other characters,
 * such as the underscore character (_), other DNS names may contain the underscore
 *
 * @param string $hostname
 * @param bool   $fqdn
 *
 * @return bool
 */
function is_valid_hostname($hostname, $fqdn = FALSE)
{
  // Pre check if hostname is FQDN
  if ($fqdn && !preg_match('/\.(xn\-\-[a-z0-9]{2,}|[a-z]{2,})$/i', $hostname))
  {
    return FALSE;
  }

  return (preg_match("/^(_?[a-z\d](-*[_a-z\d])*)(\.(_?[a-z\d](-*[_a-z\d])*))*$/i", $hostname) // valid chars check
          && preg_match("/^.{1,253}$/", $hostname)                                      // overall length check
          && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $hostname));                 // length of each label
}

/**
 * Correct alternative for is_int(), is_integer() and ctype_digit() for validate integer numbers.
 * Work with string and numbers.
 *
 * @param string|mixed $value
 *
 * @return bool
 */
function is_intnum($value) {
  if (!is_numeric($value)) { return FALSE; }
  $value = (string)$value;
  if ($value[0] === '-') { $value = substr($value, 1); } // negative number
  return ctype_digit($value);
}

/**
 * Validate returned values for common parameters like hardware/version/serial/location.
 *
 * @param string $string
 * @param string $type
 *
 * @return bool
 */
function is_valid_param($string, $type = '') {

  // Empty or not string is invalid
  if (!(is_string($string) || is_numeric($string)) || safe_empty($string)) {
    print_debug("Detected empty value for param '$type'.");
    return FALSE;
  }

  // --, **, .., **--.--**
  $poor_default_pattern = '/^[\*\.\-]+$/';

  switch (strtolower($type)) {
    case 'asset_tag':
    case 'hardware':
    case 'vendor':
    case 'serial':
    case 'version':
    case 'revision':
      $valid = ctype_print($string) &&
               !(str_istarts($string, [ 'Not Avail', 'Not Specified', 'To be filled by O.E.M.' ]) ||
                 str_contains_array($string, [ 'denied', 'No Such' ]) || preg_match($poor_default_pattern, $string) ||
                 in_array($string, [ '<EMPTY>', 'empty', 'n/a', 'N/A', 'na', 'NA', '1234567890', '0123456789',
                                     'No Asset Tag', 'Tag 12345', 'sim', 'Unknown' ], TRUE));
      break;

    case 'location':
    case 'syslocation':
      $poor_locations_pattern = 'unknown|private|none|office|location|snmplocation|Sitting on the Dock of the Bay|Not Available';
      $valid = strlen($string) > 4 && !preg_match('/^[<\\\(]?('.$poor_locations_pattern.')[>\\\)]?$/i', $string);
      break;

    case 'contact':
    case 'syscontact':
      $valid = !(in_array($string, [ 'Uninitialized', 'not set', '<none>', '(none)', 'SNMPv2', 'Unknown', '?', '<private>' ]) ||
                 preg_match($poor_default_pattern, $string));
      break;

    case 'sysobjectid':
      $valid = preg_match('/^\.?\d+(\.\d+)*$/', $string);
      break;

    case 'type':
      $valid = array_key_exists($string, (array)$GLOBALS['config']['devicetypes']);
      break;

    case 'port':
    case 'snmp_port':
      // port 0 also valid, but we exclude because it reserved
      $valid = is_intnum($string) && $string > 0 && $string <= 65353;
      break;

    case 'path':
      // https://stackoverflow.com/questions/6416065/c-sharp-regex-for-file-paths-e-g-c-test-test-exe/42036026#42036026
      $unix_pattern = '!^\/$|(^(?=\/)|^\.|^\.\.)(\/(?=[^/\0\n\r])[^/\0\n\r]+)*\/?$!';
      $valid = preg_match($unix_pattern, $string);
      if (!$valid) {
        $windows_pattern = '@(^([a-z]|[A-Z]):(?=\\(?![\0-\37<>:"/\\|?*])|\/(?![\0-\37<>:"/\\|?*])|$)|^\\(?=[\\\/][^\0-\37<>:"/\\|?*]+)|^(?=(\\|\/)$)|^\.(?=(\\|\/)$)|^\.\.(?=(\\|\/)$)|^(?=(\\|\/)[^\0-\37<>:"/\\|?*]+)|^\.(?=(\\|\/)[^\0-\37<>:"/\\|?*]+)|^\.\.(?=(\\|\/)[^\0-\37<>:"/\\|?*]+))((\\|\/)[^\0-\37<>:"/\\|?*]+|(\\|\/)$)*()$@';
        $valid = preg_match($windows_pattern, $string);
      }
      break;

    case 'posix_username':
      // strict posix (https://unix.stackexchange.com/questions/157426/what-is-the-regex-to-validate-linux-users):
      // ^[a-z_]([a-z0-9_-]{0,31}|[a-z0-9_-]{0,30}\$)$
      $valid = preg_match('/^[a-z_]([a-z0-9_-]{0,31}|[a-z0-9_-]{0,30}\$)$/', $string);
      break;

    case 'username':
      // posix 32 chars
      // windows/ldap 20 chars
      // pre-windows2000 256 chars
      //$valid = strlen($string) <= 256 && preg_match('/^\w[\w\-\\\\]+\$?$/u', $string); // allow utf8 usernames
      $valid = preg_match('/^[\w!][\w@!#^~+\$\-\.\ \\\\]{0,254}[\w\$]$/u', $string); // allow utf8 usernames
      break;

    case 'password':
      $valid = preg_match('/^[[:print:]]+$/u', $string); // allow any printable utf8
      break;

    case 'snmp_community':
      // allow all common latin and special chars
      $valid = preg_match('/^[\w\ %!@#\$%\^&\*\(\)_\-\+~`\[\]\{\}\|\\\\<>,\.\/\?;:]{1,32}$/', $string);
      break;

    case 'snmp_timeout':
      $valid = is_intnum($string) && $string > 0 && $string <= 120;
      break;

    case 'snmp_retries':
      $valid = is_intnum($string) && $string > 0 && $string <= 10;
      break;

    case 'snmp_authalgo':
      // MD5|SHA|SHA-224|SHA-256|SHA-384|SHA-512
      $valid = preg_match('/^(md5|sha(\-?(224|256|384|512))?)$/i', $string);
      break;

    case 'snmp_cryptoalgo':
      // DES|AES|AES-192|AES-192-C|AES-256|AES-256-C
      $valid = preg_match('/^(des|aes(\-?(192|256)(\-?c)?)?)$/i', $string);
      break;

    default:
      // --, **, .., **--.--**
      $valid = !preg_match($poor_default_pattern, $string);
  }

  if (!$valid) {
    print_debug("Detected invalid value '$string' for param '$type'.");
  }

  return (bool) $valid;
}

/**
 * BOOLEAN safe function to check if hostname resolves as IPv4 or IPv6 address
 *
 * @param string $hostname
 * @param int $flags
 *
 * @return bool
 */
function is_domain_resolves($hostname, $flags = OBS_DNS_ALL) {
  return (is_valid_hostname($hostname) && gethostbyname6($hostname, $flags));
}

/**
 * Get $host record from /etc/hosts
 *
 * @param string $host
 * @param int $flags
 *
 * @return false|mixed|string|null
 */
function ip_from_hosts($host, $flags = OBS_DNS_ALL) {
  $host = strtolower($host);

  try {
    foreach (new SplFileObject('/etc/hosts') as $line) {
      // skip empty and comments
      if (str_contains($line, '#')) {
        // remove inline comments
        list($line,) = explode('#', $line, 2);
      }
      $line = trim($line);
      if (safe_empty($line)) { continue; }

      $hosts = preg_split('/\s/', strtolower($line), -1, PREG_SPLIT_NO_EMPTY);

      //print_debug_vars($hosts);
      $ip = array_shift($hosts);
      //$hosts = array_map('strtolower', $d);
      if (in_array($host, $hosts, TRUE)) {
        if ((is_flag_set(OBS_DNS_A, $flags) && str_contains($ip, '.')) ||
            (is_flag_set(OBS_DNS_AAAA, $flags) && str_contains($ip, ':'))) {
          print_debug("Host '$host' found in hosts: $ip");
          return $ip;
        }
      }
    }
  } catch (Exception $e) {
    print_warning("Could not open the file /etc/hosts! This file should be world readable, also check that SELinux is not in enforcing mode.");
  }

  return FALSE;
}

/**
 * Same as gethostbyname(), but work with both IPv4 and IPv6.
 * Get the IPv4 or IPv6 address corresponding to a given Internet hostname.
 * By default, return IPv4 address (A record) if exist,
 * else IPv6 address (AAAA record) if exist.
 * For get only IPv6 record use gethostbyname6($hostname, OBS_DNS_AAAA).
 *
 * @param string $host
 * @param int $flags
 *
 * @return false|mixed
 */
function gethostbyname6($host, $flags = OBS_DNS_ALL) {
  // get AAAA record for $host
  // if flag OBS_DNS_A is set, if AAAA fails, it tries for A
  // the first match found is returned
  // otherwise returns FALSE

  $flags |= OBS_DNS_FIRST; // Set return only first found dns record (do not request all A/AAAA/hosts records)
  $dns = gethostbynamel6($host, $flags);

  if (safe_count($dns)) {
    return array_shift($dns);
  }

  return FALSE;
}

/**
 * Same as gethostbynamel(), but work with both IPv4 and IPv6.
 * By default, returns both IPv4/6 addresses (A and AAAA records),
 * for get only IPv6 addresses use gethostbynamel6($hostname, OBS_DNS_AAAA).
 *
 * @param string $host
 * @param int $flags
 *
 * @return array|false
 */
function gethostbynamel6($host, $flags = OBS_DNS_ALL) {
  // get AAAA records for $host,
  // if $try_a is true, if AAAA fails, it tries for A
  // results are returned in an array of ips found matching type
  // otherwise returns FALSE

  $ip6 = [];
  $ip4 = [];

  $try_a    = is_flag_set(OBS_DNS_A, $flags);
  $try_aaaa = is_flag_set(OBS_DNS_AAAA, $flags);
  $first    = is_flag_set(OBS_DNS_FIRST, $flags); // Return first found record, when flag set

  if ($try_a === TRUE) {
    // First try /etc/hosts (v4)
    $etc4 = ip_from_hosts($host, OBS_DNS_A);
    if ($etc4) {
      $ip4[] = $etc4;

      if ($first) { return $ip4; }
    }

    // Second try /etc/hosts (v6)
    $etc6 = $try_aaaa ? ip_from_hosts($host, OBS_DNS_AAAA) : FALSE;
    if ($etc6) {
      $ip6[] = $etc6;

      if ($first) { return $ip6; }
    }

    // Separate A and AAAA queries, see: https://www.mail-archive.com/observium@observium.org/msg09239.html
    $dns = dns_get_record($host, DNS_A);
    print_debug_vars($dns);
    if (!is_array($dns)) { $dns = []; }

    // Request AAAA record (when requested only first record and A record exist, skip)
    if ($try_aaaa && !($first && count($dns))) {
      $dns6 = dns_get_record($host, DNS_AAAA);
      print_debug_vars($dns6);
      if (is_array($dns6)) {
        $dns = array_merge($dns, $dns6);
      }
    }
  } elseif ($try_aaaa) {
    // First try /etc/hosts (v6)
    $etc6 = ip_from_hosts($host, OBS_DNS_AAAA);
    if ($etc6) {
      $ip6[] = $etc6;

      if ($first) { return $ip6; }
    }
    $dns = dns_get_record($host, DNS_AAAA);
    print_debug_vars($dns);
  } else {
    // Not A or AAAA record requested
    return FALSE;
  }

  foreach ($dns as $record) {
    switch ($record['type']) {
      case 'A':
        $ip4[] = $record['ip'];
        break;
      case 'AAAA':
        $ip6[] = $record['ipv6'];
        break;
    }
  }

  if ($try_a && count($ip4)) {
    // Merge ipv4 & ipv6
    $ip6 = array_merge($ip4, $ip6);
  }

  if (count($ip6)) {
    return $ip6;
  }

  return FALSE;
}

/**
 * Get hostname by IP (both IPv4/IPv6)
 * Return PTR or FALSE.
 *
 * @param string $ip
 *
 * @return string|false
 */
function gethostbyaddr6($ip) {

  $ptr = FALSE;
  $resolver = new Net_DNS2_Resolver();
  try {
    $response = $resolver->query($ip, 'PTR');
    if ($response) {
      $ptr = $response->answer[0]->ptrdname;
    }
  } catch (Net_DNS2_Exception $e) {}

  return $ptr;
}

/**
 * Request an http(s) url.
 * Note. If first runtime request exit with timeout,
 *       than will be set constant OBS_HTTP_REQUEST as FALSE
 *       and all other requests will skipped with FALSE response!
 *
 * @param string   $request Requested URL
 * @param array    $context Set additional HTTP context options, see http://php.net/manual/en/context.http.php
 * @param int|boolean $rate_limit Rate limit per day for specified domain (in url). If FALSE no limits
 * @global array   $config
 * @global array   $GLOBALS['response_headers'] Response headers with keys:
 *                                              code (HTTP code status), status (HTTP status description) and all other
 * @global boolean $GLOBALS['request_status'] TRUE if response code is 2xx or 3xx
 *
 * @return string|boolean Return response content or FALSE
 */
function get_http_request($request, $context = [], $rate_limit = FALSE) {
  global $config;

  $ok = TRUE;
  if (defined('OBS_HTTP_REQUEST') && OBS_HTTP_REQUEST === FALSE) {
    print_debug("HTTP requests skipped since previous request exit with timeout");
    $ok = FALSE;
    $GLOBALS['response_headers'] = array('code' => 408, 'descr' => 'Request Timeout');
  } elseif (!ini_get('allow_url_fopen')) {
    print_debug('HTTP requests disabled, since PHP config option "allow_url_fopen" set to off. Please enable this option in your PHP config.');
    $ok = FALSE;
    $GLOBALS['response_headers'] = array('code' => 400, 'descr' => 'HTTP Method Disabled');
  } elseif (str_istarts($request, 'https') && !check_extension_exists('openssl')) {
    // Check if Secure requests allowed, but ssl extension not exist
    print_debug(__FUNCTION__.'() wants to connect with https but https is not enabled on this server. Please check your PHP settings, the openssl extension must exist and be enabled.');
    logfile(__FUNCTION__.'() wants to connect with https but https is not enabled on this server. Please check your PHP settings, the openssl extension must exist and be enabled.');
    $ok = FALSE;
    $GLOBALS['response_headers'] = array('code' => 400, 'descr' => 'HTTPS Method Disabled');
  }

  if ($ok && $rate_limit && is_numeric($rate_limit) && $rate_limit >= 0) {
    // Check limit rates to this domain (per/day)
    if (preg_match('/^https?:\/\/([\w\.]+[\w\-\.]*(:\d+)?)/i', $request, $matches)) {
      $date    = format_unixtime(get_time(), 'Y-m-d');
      $domain  = $matches[0]; // base domain (with http(s)): https://test-me.com/ -> https://test-me.com
      $rate_db = safe_json_decode(get_obs_attrib('http_rate_' . $domain));
      //print_vars($date); print_vars($rate_db);
      if (is_array($rate_db) && isset($rate_db[$date])) {
        $rate_count = $rate_db[$date];
      } else {
        $rate_count = 0;
      }
      $rate_count++;
      set_obs_attrib('http_rate_' . $domain, safe_json_encode(array($date => $rate_count)));
      if ($rate_count > $rate_limit) {
        print_debug("HTTP requests skipped because the rate limit $rate_limit/day for domain '$domain' is exceeded (count: $rate_count)");
        $GLOBALS['response_headers'] = array('code' => 429, 'descr' => 'Too Many Requests');
        $ok = FALSE;
      } elseif (OBS_DEBUG > 1) {
        print_debug("HTTP rate count for domain '$domain': $rate_count ($rate_limit/day)");
      }
    } else {
      $rate_limit = FALSE;
    }
  }

  if (OBS_DEBUG > 0) {
    $debug_request = $request;
    if (OBS_DEBUG < 2 && strpos($request, 'update.observium.org')) {
      $debug_request = preg_replace('/&stats=.+/', '&stats=***', $debug_request);
    }
    $debug_msg = PHP_EOL . 'REQUEST[%y' . $debug_request . '%n]';
  }

  if (!$ok) {
    if (OBS_DEBUG > 0) {
      print_message($debug_msg . PHP_EOL .
                    'REQUEST STATUS[%rFALSE%n]' . PHP_EOL .
                    'RESPONSE CODE[' . $GLOBALS['response_headers']['code'] . ' ' . $GLOBALS['response_headers']['descr'] . ']', 'console');
    }

    // Set GLOBAL var $request_status for use as validate status of last responce
    $GLOBALS['request_status'] = FALSE;
    return FALSE;
  }

  $response = '';

  // Add common http context
  $opts = [ 'http' => generate_http_context_defaults($context) ];

  // Force IPv4 or IPv6
  if (isset($config['http_ip_version'])) {
    $bindto = str_contains($config['http_ip_version'], '6') ? '[::]:0' : '0:0';
    $opts['socket'] = [ 'bindto' => $bindto ];
    //$opts['socket'] = [ 'bindto' => '0:0' ]; // IPv4
    //$opts['socket'] = [ 'bindto' => '[::]:0' ]; // IPv6
  }

  // HTTPS
  // if ($parse_url = parse_url($request))
  // {
  //   if ($parse_url['scheme'] == 'https')
  //   {
  //     $opts['ssl'] = [ 'SNI_enabled' => TRUE, 'SNI_server_name' => $parse_url['host'] ];
  //   }
  // }

  // DEBUG, generate curl cmd for testing:
  if (defined('OBS_DEBUG') && OBS_DEBUG) {
    $curl_cmd = 'curl';
    if (OBS_DEBUG > 1) {
      // Show response headers
      $curl_cmd .= ' -i';
    }
    if (isset($config['http_ip_version'])) {
      $curl_cmd .= str_contains($config['http_ip_version'], '6') ? ' -6' : ' -4';
    }
    if (isset($opts['http']['timeout'])) {
      $curl_cmd .= ' --connect-timeout '.$opts['http']['timeout'];
    }
    if (isset($opts['http']['method'])) {
      $curl_cmd .= ' -X '.$opts['http']['method'];
    }
    if (isset($opts['http']['header'])) {
      foreach (explode("\r\n", $opts['http']['header']) as $curl_header) {
        if (safe_empty($curl_header)) { continue; }
        $curl_cmd .= ' -H \'' . $curl_header . '\'';
      }
    }
    if (isset($opts['http']['content'])) {
      $curl_cmd .= ' -d \''.$opts['http']['content'].'\'';
    }
    // Proxy
    // -x, --proxy <[protocol://][user:password@]proxyhost[:port]>
    // -U, --proxy-user <user:password>
    if (isset($config['http_proxy']) && $config['http_proxy']) {
      $http_proxy = $config['http_proxy'];

      // Basic proxy auth
      if (isset($config['proxy_user'], $config['proxy_password']) && $config['proxy_user']) {
        $http_proxy = $config['proxy_user'].':'.$config['proxy_password'].'@'.$http_proxy;
      }
      $curl_cmd .= ' -x '.$http_proxy;
    }
    print_debug("HTTP CURL cmd:\n$curl_cmd $request");
  }

  // Process http request and calculate runtime
  $start = utime();
  $context = stream_context_create($opts);
  $response = file_get_contents($request, FALSE, $context);
  $end = utime();
  $runtime = $end - $start;

  // Request end unixtime and runtime
  $GLOBALS['request_unixtime'] = $end;
  $GLOBALS['request_runtime'] = $runtime;

  // Parse response headers
  // Note: $http_response_header - see: http://php.net/manual/en/reserved.variables.httpresponseheader.php
  $head = [];
  foreach ($http_response_header as $k => $v) {
    $t = explode(':', $v, 2);
    if (isset($t[1])) {
      // Date: Sat, 12 Apr 2008 17:30:38 GMT
      $head[trim($t[0])] = trim($t[1]);
    } elseif (preg_match("!HTTP/([\d\.]+)\s+(\d+)(.*)!", $v, $matches)) {
      // HTTP/1.1 200 OK
      $head['http']   = $matches[1];
      $head['code']   = (int)$matches[2];
      $head['descr']  = trim($matches[3]);
    } else {
      $head[] = $v;
    }
  }
  $GLOBALS['response_headers'] = $head;

  // Set GLOBAL var $request_status for use as validate status of last responce
  if (isset($head['code']) && ($head['code'] < 200 || $head['code'] >= 400)) {
    $GLOBALS['request_status'] = FALSE;
  } elseif ($response === FALSE) {
    // An error in get response
    $GLOBALS['response_headers'] = [ 'code' => 408, 'descr' => 'Request Timeout' ];
    $GLOBALS['request_status'] = FALSE;
  } else {
    // Valid statuses: 2xx Success, 3xx Redirection or head code not set (ie response not correctly parsed)
    $GLOBALS['request_status'] = TRUE;
  }

  // Set OBS_HTTP_REQUEST for skip all other requests (FALSE for skip all other requests)
  if (!defined('OBS_HTTP_REQUEST')) {
    if ($response === FALSE && empty($http_response_header)) {
      // Derp, no way for get proxy headers
      if ($runtime < 1 &&
          isset($config['http_proxy']) && $config['http_proxy'] &&
          !(isset($config['proxy_user']) || isset($config['proxy_password']))) {
        $GLOBALS['response_headers'] = [ 'code' => 407, 'descr' => 'Proxy Authentication Required' ];
      } else {
        $GLOBALS['response_headers'] = [ 'code' => 408, 'descr' => 'Request Timeout' ];
      }
      $GLOBALS['request_status'] = FALSE;

      // Validate host from request and check if it timeout request
      if (OBS_PROCESS_NAME === 'poller' && gethostbyname6(parse_url($request, PHP_URL_HOST))) {
        // Timeout error, only if not received response headers
        define('OBS_HTTP_REQUEST', FALSE);
        print_debug(__FUNCTION__.'() exit with timeout. Access to outside localnet is blocked by firewall or network problems. Check proxy settings.');
        logfile(__FUNCTION__.'() exit with timeout. Access to outside localnet is blocked by firewall or network problems. Check proxy settings.');
      }
    } else {
      define('OBS_HTTP_REQUEST', TRUE);
    }
  }
  // FIXME. what if first request fine, but second broken?
  //else if ($response === FALSE)
  //{
  //  if (function_exists('runkit_constant_redefine')) { runkit_constant_redefine('OBS_HTTP_REQUEST', FALSE); }
  //}

  if (defined('OBS_DEBUG') && OBS_DEBUG) {
    // Hide extended stats in normal debug level = 1
    if (OBS_DEBUG < 2 && strpos($request, 'update.observium.org')) { $request = preg_replace('/&stats=.+/', '&stats=***', $request); }
    // Show debug info
    print_message($debug_msg . PHP_EOL .
                  'REQUEST STATUS[' . ($GLOBALS['request_status'] ? '%gTRUE' : '%rFALSE') . '%n]' . PHP_EOL .
                  'REQUEST RUNTIME['.($runtime > 3 ? '%r' : '%g').round($runtime, 4).'s%n]' . PHP_EOL .
                  'RESPONSE CODE[' . $GLOBALS['response_headers']['code'] . ' ' . $GLOBALS['response_headers']['descr'] . ']', 'console');
    if (OBS_DEBUG > 1) {
      echo("RESPONSE[\n".$response."\n]");
      print_vars($http_response_header);
      print_vars($opts);
    }
  }

  return $response;
}

/**
 * Process HTTP request by definition array and process it for valid status.
 * Used definition params in response key.
 *
 * @param string|array $def       Definition array or alert transport key (see transports definitions)
 * @param string $response  Response from get_http_request()
 * @return boolean          Return TRUE if request processed with valid HTTP code (2xx, 3xx) and API response return valid param
 */
function test_http_request($def, $response) {
  $response = trim($response);

  if (is_string($def)) {
    // Get transport definition for responses
    $def = $GLOBALS['config']['transports'][$def]['notification'];
  }

  // Response is array (or xml)?
  $is_response_array = strtolower($def['response_format']) === 'json';

  // Set status by response status
  $success = get_http_last_status();

  // If response return valid code and content, additional parse for specific defined tests
  if ($success) {
    // Decode if request OK
    if ($is_response_array) {
      $response = safe_json_decode($response);
    }
    // else additional formats?

    // Check if call succeeded
    if (isset($def['response_test'])) {
      // Convert single test condition to multi-level condition
      if (isset($def['response_test']['operator'])) {
        $def['response_test'] = array($def['response_test']);
      }

      // Compare all definition fields with response,
      // if response param not equals to expected, set not success
      // multilevel keys should written with '->' separator, ie: $a[key][some][0] - key->some->0
      foreach ($def['response_test'] as $test) {
        if ($is_response_array) {
          $field = array_get_nested($response, $test['field']);
        } else {
          // RAW response
          $field = $response;
        }
        if (test_condition($field, $test['operator'], $test['value']) === FALSE) {
          print_debug("Response [".$field."] not valid: [" . $test['field'] . "] " . $test['operator'] . " [" . implode(', ', (array)$test['value']) . "]");

          $success = FALSE;
          break;
        } else {
          print_debug("Response [".$field."] valid: [" . $test['field'] . "] " . $test['operator'] . " [" . implode(', ', (array)$test['value']) . "]");
        }
      }
    }
  } elseif ($is_response_array && isset($def['response_fields']['message'], $def['response_fields']['status'])) {
    // Decode response for useful error reports also for bad statuses
    $response = safe_json_decode($response);
  }

  if (!$success) {
    if (isset($def['response_fields']['message'], $def['response_fields']['status']) && is_array($response)) {
      echo PHP_EOL;
      if (isset($def['response_fields']['status'])) {
        if ($def['response_fields']['status'] === 'raw') {
          $status = get_http_last_code();
        } else {
          $status = array_get_nested($response, $def['response_fields']['status']);
        }
        if (OBS_DEBUG) {
          print_message("%WRESPONSE STATUS%n[%r".$status."%n]", 'console');
        }
      }
      $msg = array_get_nested($response, $def['response_fields']['message']);
      if (isset($def['response_fields']['info']) &&
          $info = array_get_nested($response, $def['response_fields']['info'])) {
        $msg .= " ($info)";
      }
      if (OBS_DEBUG) { print_message("%WRESPONSE ERROR%n[%y".$msg ."%n]\n", 'console'); }
      $GLOBALS['last_message'] = $msg;
    } elseif (is_string($response) && $response && !get_http_last_status()) {
      if (OBS_DEBUG) {
        echo PHP_EOL;
        print_message("%WRESPONSE STATUS%n[%r" . get_http_last_code() . "%n]", 'console');
        print_message("%WRESPONSE ERROR%n[%y" . $response . "%n]\n", 'console');
      }
      $GLOBALS['last_message'] = $response;
    }
  }
  print_debug_vars($response, 1);

  return $success;
}

/**
 * Return HTTP return code for last request by get_http_request()
 *
 * @return integer HTTP code
 */
function get_http_last_code() {
  return $GLOBALS['response_headers']['code'];
}

/**
 * Return HTTP return code for last request by get_http_request()
 *
 * @return boolean HTTP status TRUE if response code 2xx or 3xx
 */
function get_http_last_status() {
  return $GLOBALS['request_status'];
}

/**
 * Generate HTTP specific context with some defaults for proxy, timeout, user-agent.
 * Used in get_http_request().
 *
 * @param array $context HTTP specified context, see http://php.net/manual/ru/function.stream-context-create.php
 * @return array HTTP context array
 */
function generate_http_context_defaults($context = array())
{
  global $config;

  if (!is_array($context)) { $context = array(); } // Fix context if not array passed

  // Defaults
  if (!isset($context['timeout'])) {
    $context['timeout'] = '15';
  }
  // HTTP/1.1
  $context['protocol_version'] = 1.1;
  // get the entire body of the response in case of error (HTTP/1.1 400, for example)
  if (OBS_DEBUG) {
    $context['ignore_errors'] = TRUE;
  }

  // User agent (required for some type of queries, ie geocoding)
  if (!isset($context['header']))
  {
    $context['header'] = ''; // Avoid 'undefined index' when concatting below
  }
  $context['header'] .= 'User-Agent: ' . OBSERVIUM_PRODUCT . '/' . OBSERVIUM_VERSION . "\r\n";

  if (isset($config['http_proxy']) && $config['http_proxy'])
  {
    $context['proxy'] = 'tcp://' . $config['http_proxy'];
    $context['request_fulluri'] = isset($config['proxy_fulluri']) ? (bool)$config['proxy_fulluri'] : TRUE;
  }

  // Basic proxy auth
  if (isset($config['proxy_user']) && $config['proxy_user'] && isset($config['proxy_password']))
  {
    $auth = base64_encode($config['proxy_user'].':'.$config['proxy_password']);
    $context['header'] .= 'Proxy-Authorization: Basic ' . $auth . "\r\n";
  }

  print_debug_vars($context);

  return $context;
}


/**
 * Generate HTTP context based on passed params, tags and definition.
 * This context will used in get_http_request_test() (or get_http_request())
 *
 * @global array       $config
 * @param string|array $def      Definition array or alert transport key (see transports definitions)
 * @param array  $tags     (optional) Contact array and other tags
 * @param array  $params   (optional) Array of requested params with key => value entries (used with request method POST)
 * @return array           HTTP Context which can used in get_http_request_test() or get_http_request()
 */
function generate_http_context($def, $tags = [], $params = [])
{
  global $config;

  if (is_string($def)) {
    // Get transport definition for requests
    $def = $config['transports'][$def]['notification'];
  }

  $context = array(); // Init

  // Request method POST/GET
  if ($def['method']) {
    $context['method'] = strtoupper($def['method']);
  }
  // Request timeout
  if (is_intnum($def['timeout']) || $def['timeout'] >= 1 || $def['timeout'] <= 300) {
    $context['timeout'] = $def['timeout'];
  }

  // Content and headers
  $header = "Connection: close\r\n";

  // Add encode $params for POST request inside http headers
  if ($context['method'] === 'POST' || $context['method'] === 'PUT') {
    if (isset($def['request_params_key'])) {
      // Key based link to request params, see google-chat notification
      $key = 'request_params_' . strtolower(array_tag_replace($tags, $def['request_params_key']));
      if (isset($def[$key])) {
        $request_params = &$def[$key];
      } else {
        $request_params = &$def['request_params'];
      }
    } else {
      // Common default request_params
      $request_params = &$def['request_params'];
    }

    // Generate request params
    foreach ((array)$request_params as $param => $entry) {
      // Try to find all keys in header like %bot_hash% matched with same key in $endpoint array
      if (is_array($entry)) {
        // ie teams and pagerduty
        $params[$param] = array_merge((array)$params[$param], array_tag_replace($tags, $entry));
      } elseif (!isset($params[$param]) || $params[$param] === '') {
        $params[$param] = array_tag_replace($tags, $entry);
      }
      // Clean empty params
      if (safe_empty($params[$param])) { unset($params[$param]); }
    }

    if (strtolower($def['request_format']) === 'json') {
      // Encode params as json string
      $data   = safe_json_encode($params);
      $header .= "Content-Type: application/json; charset=utf-8\r\n";
    } else {
      // Encode params as url encoded string
      $data   = http_build_query($params);
      // https://stackoverflow.com/questions/4007969/application-x-www-form-urlencoded-or-multipart-form-data
      //$header .= "Content-Type: multipart/form-data\r\n";
      $header .= "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n";
    }
    $header .= "Content-Length: ".strlen($data)."\r\n";

    // Encoded content data
    $context['content'] = $data;
  }

  // Basic auth
  if (isset($def['request_user'])) {
    $basic_auth = $def['request_user'];
    if (isset($def['request_password'])) {
      $basic_auth .= ':' . $def['request_password'];
    }
    $basic_auth = array_tag_replace($tags, $basic_auth);

    $header .= 'Authorization: Basic ' . \base64_encode($basic_auth) . "\r\n";
  }

  // Additional headers with contact params
  foreach ($def['request_header'] as $entry) {
    // Try to find all keys in header like %bot_hash% matched with same key in $endpoint array
    $header .= array_tag_replace($tags, $entry) . "\r\n";
  }

  $context['header'] = $header;

  return $context;
}

/**
 * Generate URL based on passed params, tags and definition.
 * This context will used in get_http_request_test() (or get_http_request())
 *
 * @global array $config
 * @param string|array $def       Definition array or alert transport key (see transports definitions)
 * @param array  $tags      (optional) Contact array, used only if transport required additional headers (ie hipchat)
 * @param array  $params    (optional) Array of requested params with key => value entries (used with request method GET)
 * @return string           URL which can used in get_http_request_test() or get_http_request()
 */
function generate_http_url($def, $tags = array(), $params = array())
{
  global $config;

  if (is_string($def))
  {
    // Get definition for transport API
    $def = $config['transports'][$def]['notification'];
  }

  $url = ''; // Init

  // Append (if set $def['url_param']) or set hardcoded url for transport
  if (isset($def['url'])) {
    // Try to find all keys in URL like %bot_hash% matched with same key in $endpoint array
    $url .= array_tag_replace($tags, $def['url']);
  }

  // Add GET params to url
  if ($def['method'] === 'GET' || $def['method'] === 'DELETE') {
    if (isset($def['request_params_key'])) {
      // Key based link to request params, see google-chat notification
      $key = 'request_params_' . strtolower(array_tag_replace($tags, $def['request_params_key']));
      if (isset($def[$key])) {
        $request_params = &$def[$key];
      } else {
        $request_params = &$def['request_params'];
      }
    } else {
      // Common default request_params
      $request_params = &$def['request_params'];
    }

    // Generate request params
    foreach ((array)$request_params as $param => $entry) {
      // Try to find all keys in header like %bot_hash% matched with same key in $endpoint array
      if (is_array($entry)) {
        // ie teams and pagerduty
        $params[$param] = array_merge((array)$params[$param], array_tag_replace($tags, $entry));
      } elseif (!isset($params[$param]) || $params[$param] === '') {
        $params[$param] = array_tag_replace($tags, $entry);
      }
      // Clean empty params
      if (safe_empty($params[$param])) { unset($params[$param]); }
    }

    // Append params to url
    if (count($params)) {
      $data   = http_build_query($params);
      if (str_contains($url, '?')) {
        // Append additional params to url string
        $url .= '&' . $data;
      } else {
        // Add get params as first time
        $url .= '?' . $data;
      }
    }
  }

  return $url;
}

/**
 * Return named unix times from now
 *
 * @param string $str Named time from now, ie: fiveminute, twelvehour, year
 * @param bool   $future If TRUE, return unix time in future.
 *
 * @return int
 */
function get_time($str = 'now', $future = FALSE) {
  global $config;

  // Set some times needed by loads of scripts (it's dynamic, so we do it here!)
  $config['time']['now'] = time();
  /*
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
  */

  $str = strtolower(trim($str));

  if ($str === 'now' || empty($str)) {
    return $config['time']['now'];
  }
  $time = $config['time']['now'];

  $multipliers = [
    'two' => 2, 'three' => 3, 'four' => 4,
    'five' => 5, 'six' => 6, 'seven' => 7,
    'eight' => 8, 'nine' => 9, 'ten' => 10,
    'eleven' => 11, 'twelve' => 12
  ];
  $times = [
    'year'  => 31536000,
    'month' => 2678400,
    'week'  => 604800,
    'day'   => 86400,
    'hour'  => 3600,
    'minute' => 60
  ];
  $time_pattern = '/^(?<multiplier>' . implode('|', array_keys($multipliers)) . ')?(?<time>'  . implode('|', array_keys($times)) . ')$/';
  //$time_pattern = '/^(?<multiplier>two|three|four|five|six|seven|eight|nine|ten|eleven|twelve)?(?<time>year|month|week|day|hour|minute)$/';
  if (preg_match($time_pattern, $str, $matches)) {
    $multiplier = isset($multipliers[$matches['multiplier']]) ? $multipliers[$matches['multiplier']] : 1;

    $diff = $multiplier * $times[$matches['time']];

    if ($future) {
      $time += $diff;
    } else {
      $time -= $diff;
    }
  }

  return (int)$time;
}

/**
 * Format date string.
 * If passed 'now' return formatted current datetime.
 *
 * This function convert date/time string to format from
 * config option $config['timestamp_format'].
 * If date/time not detected in string, function return original string.
 * Example conversions to format 'd-m-Y H:i':
 * '2012-04-18 14:25:01' -> '18-04-2012 14:25'
 * 'Star wars' -> 'Star wars'
 *
 * @param string $str
 *
 * @return string
 */
// TESTME needs unit testing
function format_timestamp($str) {
  global $config;

  if ($str === 'now') {
    // Use for get formatted current time
    $timestamp = get_time($str);
  } elseif (($timestamp = strtotime($str)) === FALSE) {
    return $str;
  }

  return date($config['timestamp_format'], $timestamp);
}

/**
 * Format unixtime.
 *
 * This function convert unixtime string to format from
 * config option $config['timestamp_format'].
 * Can take an optional format parameter, which is passed to date();
 *
 * @param string $time Unixtime in seconds since the Unix Epoch (also allowed microseconds)
 * @param string $format Common date format
 * @return string
 */
// TESTME needs unit testing
function format_unixtime($time, $format = NULL) {

  list($sec, $usec) = explode('.', (string)$time);
  if (!safe_empty($usec)) {
    $date = date_create_from_format('U.u', number_format($time, 6, '.', ''));
  } else {
    $date = date_create_from_format('U', $sec);
    $usec = 0;
  }

  // If something wrong with create data object, just return empty string (and yes, we never use zero unixtime)
  if (!$date || $time == 0) { return ''; }

  // Set correct timezone
  $tz = get_timezone();
  //r($tz);
  try {
    $date_timezone = new DateTimeZone(str_replace(':', '', $tz['php']));
    //$date_timezone = new DateTimeZone($tz['php_name']);
    $date->setTimeZone($date_timezone);
  } catch(Throwable $throwable) {
    print_debug($throwable->getMessage());
  } catch(Exception $exception) {
    print_debug($exception->getMessage());
  }
  //r($date);

  if (safe_empty($format)) {
    $format = $GLOBALS['config']['timestamp_format'];
  }
  $return = date_format($date, (string)$format);
  if (PHP_VERSION_ID < 70300 && str_contains($return, 'v')) {
    // The 'v' format specifier has been added in php 7.3
    $msec = $usec ? substr(str_pad((string)$usec, 6, '0'), 0, 3) : '000';
    //echo sprintf("%s -> %s\n", $usec, $msec);
    return str_replace('v', $msec, $return);
  }

  return $return;
}

/**
 * Reformat US-based dates to display based on $config['date_format']
 *
 * Supported input formats:
 *   DD/MM/YYYY
 *   DD/MM/YY
 *
 * Handling of YY -> YYYY years is passed on to PHP's strtotime, which
 * is currently cut off at 1970/2069.
 *
 * @param string $date Erroneous date format
 * @return string $date
 */
function reformat_us_date($date) {
  global $config;

  $date = trim($date);
  if (preg_match('!^\d{1,2}/\d{1,2}/(\d{2}|\d{4})$!', $date)) {
    // Only date
    $format = $config['date_format'];
  } elseif (preg_match('!^\d{1,2}/\d{1,2}/(\d{2}|\d{4})\s+\d{1,2}:\d{1,2}(:\d{1,2})?$!', $date)) {
    // Date + time
    $format = $config['timestamp_format'];
  } else {
    return $date;
  }

  return date($format, strtotime($date));
}

/**
 * This function convert human written Uptime to seconds.
 * Opposite function for format_uptime().
 *
 * Also applicable for some uptime formats in MIB, like EigrpUpTimeString:
 *  'hh:mm:ss', reflecting hours, minutes, and seconds
 *  If the up time is greater than 24 hours, is less precise and
 *  the minutes and seconds are not reflected. Instead only the days
 *  and hours are shown and the string will be formatted like this: 'xxxdxxh'
 *
 * @param string $uptime Uptime in human readable string or timetick
 * @return int Uptime in seconds
 */
function uptime_to_seconds($uptime) {
  if (str_contains($uptime, 'Wrong Type')) {
    // Wrong Type (should be Timeticks): 1632295600
    $seconds = timeticks_to_sec($uptime);
  } elseif (str_contains($uptime, ':') &&
            !preg_match('/[a-zA-Z]/', $uptime)) { // exclude strings: 315 days18:50:04
    $seconds = timeticks_to_sec($uptime);
  } else {
    $uptime = preg_replace('/^[a-z]+ */i', '', $uptime); // Clean "up" string
    $seconds = age_to_seconds($uptime);
  }

  return $seconds;
}

/**
 * Convert age string to seconds.
 *
 * This function convert age string to seconds.
 * If age is numeric than it in seconds.
 * The supplied age accepts values such as 31d, 240h, 1.5d etc.
 * Accepted age scales are:
 * y (years), M (months), w (weeks), d (days), h (hours), m (minutes), s (seconds).
 * NOTE, for month use CAPITAL 'M'
 * With wrong and negative returns 0
 *
 * '3y 4M 6w 5d 3h 1m 3s' -> 109191663
 * '184 days 22 hrs 02 min 38 sec' ->
 * '3y4M6w5d3h1m3s'       -> 109191663
 * '1.5w'                 -> 907200
 * -886732     -> 0
 * 'Star wars' -> 0
 *
 * @param string|int $age
 * @return int
 */
// TESTME needs unit testing
function age_to_seconds($age) {
  $age = trim($age);

  if (is_numeric($age)) {
    $age = (int)$age;
    if ($age > 0) {
      return $age;
    }
    return 0;
  }

  $pattern = '/^';
  $pattern .= '(?:(?<years>\d+(?:\.\d)*)\ ?(?:[yY][eE][aA][rR][sS]?|[yY])[,\ ]*)*';         // y (years)
  $pattern .= '(?:(?<months>\d+(?:\.\d)*)\ ?(?:[mM][oO][nN][tT][hH][sS]?|M)[,\ ]*)*';       // M (months)
  $pattern .= '(?:(?<weeks>\d+(?:\.\d)*)\ ?(?:[wW][eE][eE][kK][sS]?|[wW])[,\ ]*)*';         // w (weeks)
  $pattern .= '(?:(?<days>\d+(?:\.\d)*)\ ?(?:[dD][aA][yY][sS]?|[dD])[,\ ]*)*';              // d (days)
  $pattern .= '(?:(?:';
  $pattern .= '(?:(?<hours>\d+(?:\.\d)*)\ ?(?:[hH][oO][uU][rR][sS]?|[hH][rR][sS]|[hH])[,\ ]*)*';         // h (hours)
  $pattern .= '(?:(?<minutes>\d+(?:\.\d)*)\ ?(?:[mM][iI][nN][uU][tT][eE][sS]?|[mM][iI][nN]|m)[,\ ]*)*';  // m (minutes)
  $pattern .= '(?:(?<seconds>\d+(?:\.\d)*)\ ?(?:[sS][eE][cC][oO][nN][dD][sS]?|[sS][eE][cC]|[sS]))*';     // s (seconds)
  $pattern .= '|(?:(?<hours>\d{1,2}):(?<minutes>\d{1,2}):(?<seconds>\d{1,2}(?:\.\d+)*))';                // hh:mm:ss.s
  $pattern .= '))';
  $pattern .= '$/J';
  //print_vars($pattern); echo PHP_EOL;

  if (!empty($age) && preg_match($pattern, $age, $matches)) {
    $ages = [
      'years'  => 31536000, // year   = 365 * 24 * 60 * 60
      'months'  => 2628000, // month  = year / 12
      'weeks'   =>  604800, // week   = 7 days
      'days'    =>   86400, // day    = 24 * 60 * 60
      'hours'   =>    3600, // hour   = 60 * 60
      'minutes' =>      60  // minute = 60
    ];
    $seconds  = isset($matches['seconds']) ? (float)$matches['seconds'] : 0;
    foreach ($ages as $period => $scale) {
      if (isset($matches[$period])) {
        $seconds += (float)$matches[$period] * $scale;
      }
    }

    return (int)$seconds;
  }

  return 0;
}

/**
 * Convert age string to unixtime.
 *
 * This function convert age string to unixtime.
 *
 * Description and notes same as for age_to_seconds()
 *
 * Additional check if $age more than minimal age in seconds
 *
 * '3y 4M 6w 5d 3h 1m 3s' -> time() - 109191663
 * '3y4M6w5d3h1m3s'       -> time() - 109191663
 * '1.5w'                 -> time() - 907200
 * -886732     -> 0
 * 'Star wars' -> 0
 *
 * @param string|int $age
 * @param string|int $min_age
 *
 * @return int
 */
// TESTME needs unit testing
function age_to_unixtime($age, $min_age = 1) {
  $age = age_to_seconds($age);
  if ($age >= $min_age) {
    return time() - $age;
  }
  return 0;
}

/**
 * Convert an variable to base64 encoded string
 *
 * This function converts any array or other variable to encoded string
 * which can be used in urls.
 * Can use serialize and json(default) methods.
 *
 * NOTE. In PHP < 5.4 json converts UTF-8 characters to Unicode escape sequences
 * also json rounds float numbers (98172397.1234567890 ==> 98172397.123457)
 *
 * @param mixed $var
 * @param string $method
 * @return string
 */
function var_encode($var, $method = 'json')
{
  switch ($method)
  {
    case 'serialize':
      $string = base64_encode(serialize($var));
      break;
    default:
      //$tmp = json_encode($var, OBS_JSON_ENCODE);
      //echo PHP_EOL . 'precision = ' . ini_get('precision') . "\n";
      //echo 'serialize_precision = ' . ini_get('serialize_precision');
      //echo("\n---\n"); var_dump($var); echo("\n---\n"); var_dump($tmp);
      $string = base64_encode(json_encode($var, OBS_JSON_ENCODE));
      break;
  }
  return $string;
}

/**
 * Decode an previously encoded string by var_encode() to original variable
 *
 * This function converts base64 encoded string to original variable.
 * Can use serialize and json(default) methods.
 * If json/serialize not detected returns original var
 *
 * NOTE. In PHP < 5.4 json converts UTF-8 characters to Unicode escape sequences,
 * also json rounds float numbers (98172397.1234567890 ==> 98172397.123457)
 *
 * @param string $string
 * @param string $method
 *
 * @return mixed
 */
function var_decode($string, $method = 'json') {
  if (!is_string($string)) {
    // Decode only string vars
    return $string;
  }
  if ((strlen($string) % 4) > 0)
  {
    // BASE64 length must be multiple by 4
    return $string;
  }

  $value = base64_decode($string, TRUE);
  if ($value === FALSE)
  {
    // This is not base64 string, return original var
    return $string;
  }

  switch ($method)
  {
    case 'serialize':
    case 'unserialize':
      if ($value === 'b:0;') { return FALSE; }
      $decoded = safe_unserialize($value);
      if ($decoded !== FALSE)
      {
        // Serialized encoded string detected
        return $decoded;
      }
      break;
    default:
      if ($string === 'bnVsbA==') { return NULL; }
      if (OBS_JSON_DECODE > 0)
      {
        $decoded = @json_decode($value, TRUE, 512, OBS_JSON_DECODE);
      } else {
        // Prevent to broke on old php (5.3), where supported only 3 params
        $decoded = @json_decode($value, TRUE, 512);
      }
      switch (json_last_error())
      {
        case JSON_ERROR_STATE_MISMATCH:
        case JSON_ERROR_SYNTAX:
          // Critical json errors, return original string
          break;
        case JSON_ERROR_NONE:
        default:
          if ($decoded !== NULL)
          {
            // JSON encoded string detected
            return $decoded;
          }
      }
      break;
  }

  // In all other cases return original var
  return $string;
}

/**
 * Convert CSV like string to array or keep as is if not csv.
 *
 * @param mixed $str String probably CSV list or encoded.
 * @param bool $encoded If TRUE and string is encoded (by var_encode()) decode it
 *
 * @return array|mixed
 */
function get_var_csv($str, $encoded = FALSE)
{
  if (is_string($str))
  {
    // Better to understand quoted vars
    $values = str_getcsv($str);
    if (count($values) === 1)
    {
      // not comma list, but can be quoted value
      $values = $values[0];

      // Try decode var if original value not csv
      if ($encoded && $str === $values)
      {
        $values = var_decode($str);
      } else {
        $values = var_comma_safe($values);
      }
    } else {
      $values = var_comma_safe($values);
    }
  } else {
    // If variable already array, keep as is
    $values = $str;
  }

  return $values;
}

function var_comma_safe($value)
{
  if (is_array($value))
  {
    foreach ($value as &$entry)
    {
      $entry = var_comma_safe($entry);
    }
    return $value;
  }

  if (strpos($value, '%1F') !== FALSE)
  {
    $value = str_replace('%1F', ',', $value); // %1F (US, unit separator) - not defined in HTML 4 standard
  }
  return $value;
}

/**
 * Parse number with units to numeric.
 *
 * This function converts numbers with units (e.g. 100MB) to their value
 * in bytes (e.g. 104857600).
 *
 * @param string $str
 * @param int Use custom rigid unit base (1000 or 1024)
 * @return int
 */
function unit_string_to_numeric($str, $unit_base = NULL)
{
  $value = is_string($str) ? trim($str) : $str;

  // If it's already a number, return original value
  if (is_numeric($value)) { return (float)$value; }
  // Any not numeric values return as is (array, booleans)
  if (!is_string($value)) { return $str; }

  //preg_match('/(\d+\.?\d*)\ ?(\w+)/', $str, $matches);
  $pattern = '/^(?<number>\d+\.?\d*)\ ?(?<prefix>[kmgtpezy]i?)?(?<unit>[a-z]*)$/i';
  preg_match($pattern, $value, $matches);

  // Error, return original string
  if (!is_numeric($matches['number'])) { return $str; }

  // Unit base 1000 or 1024
  $prefix_len = strlen($matches['prefix']);
  if (in_array($unit_base, [ 1000, 1024 ]))
  {
    // Use rigid unit base, this interprets any units with hard multiplier base
    $base = $unit_base;
  }
  elseif ($prefix_len === 2)
  {
    // IEC prefixes Ki, Gi, Ti, etc
    $base = 1024;
  } else {
    switch ($matches['unit'])
    {
      case '':
      case 'B':
      case 'Byte':
      case 'byte':
        $base = 1024;
        break;
      case 'b':
      case 'Bps':
      case 'bit':
      case 'bps':
        $base = 1000;
        break;
      default:
        $base = 1024;
    }
  }

  // https://en.wikipedia.org/wiki/Binary_prefix
  $prefixes = [ //'b' => 0,
                'k' => 1, 'ki' => 1,
                'm' => 2, 'mi' => 2,
                'g' => 3, 'gi' => 3,
                't' => 4, 'ti' => 4,
                'p' => 5, 'pi' => 5,
                'e' => 6, 'ei' => 6,
                'z' => 7, 'zi' => 7,
                'y' => 8, 'yi' => 8 ];

  $power = 0;
  if ($prefix_len)
  {
    $prefix = strtolower($matches['prefix']);
    if (isset($prefixes[$prefix]))
    {
      $power = $prefixes[$prefix];
    } else {
      // incorrect prefixes, return original value
      return $str;
    }
  }
  switch ($matches['unit'])
  {
    case '':
    case 'B':
    case 'Byte':
    case 'Bytes':
    case 'byte':
    case 'bytes':
      $base = isset($base) ? $base : 1024;
      break;
    case 'b':
    case 'Bps':
    case 'bit':
    case 'bits':
    case 'bps':
      $base = isset($base) ? $base : 1000;
      break;
    default:
      // unknown unit, return original value
      return $str;
  }

  $multiplier = pow($base, $power);

  return (float)($matches[1] * $multiplier);
}

/**
 * Generate Unique ID from string, based on crc32b hash. This ID unique for specific string and not changed over next call.
 *
 * @param string $string String
 * @return int Unique ID
 */
function string_to_id($string)
{
  return hexdec(hash("crc32b", $string));
}

/**
 * Convert value of sensor from known unit to defined SI unit (used in poller/discovery)
 *
 * @param float|string $value Value in non standard unit
 * @param string       $unit  Unit name/symbol
 * @param string|null  $type  Type of value (optional, if same unit can used for multiple types)
 *
 * @return float|string Value converted to standard (SI) unit
 */
function value_to_si($value, $unit, $type = NULL) {
  if (!is_numeric($value)) { return $value; } // Just return original value if not numeric

  $unit_lower = strtolower($unit);
  $case_units = [
    'c'          => 'C',
    'celsius'    => 'C',
    'f'          => 'F',
    'fahrenheit' => 'F',
    'k'          => 'K',
    'kelvin'     => 'K',

    'w'          => 'W',
    'watts'      => 'W',
    'dbm'        => 'dBm',

    'mpsi'       => 'Mpsi',
    'mmhg'       => 'mmHg',
    'inhg'       => 'inHg',
  ];
  // set correct unit case (required for external lib)
  if (isset($case_units[$unit_lower])) {
    $unit = $case_units[$unit_lower];
  }
  switch ($unit_lower) {
    case 'f':
    case 'fahrenheit':
    case 'k':
    case 'kelvin':
      try {
        $tmp = \PhpUnitsOfMeasure\PhysicalQuantity\Temperature::getUnit($unit);
      } catch(Throwable $e) {
        //PHP 7+
        $unit = $unit_lower;
      } catch (Exception $e) {
        // PHP 5.x
        $unit = $unit_lower;
      }
      $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\Temperature($value, $unit);
      $si_value = $value_from->toUnit('C');
      if ($si_value < -273.15) {
        // Physically incorrect value
        $si_value = FALSE;
      }

      $type  = 'temperature';
      $from  = $value    . " $unit";
      $to    = $si_value . ' Celsius';
      break;

    case 'c':
    case 'celsius':
      // not convert, just keep correct value
      $type  = 'temperature';
      break;

    case 'w':
    case 'watts':
      if ($type === 'dbm') {
        // Used when Power convert to dBm
        // https://en.wikipedia.org/wiki/DBm
        // https://www.everythingrf.com/rf-calculators/watt-to-dbm
        if ($value > 0) {
          $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\Power($value, 'W');
          $si_value = $value_from->toUnit('dBm');

          $from     = $value . " $unit";
          $to       = $si_value . ' dBm';
        } elseif (strlen($value) && $value == 0) {
          // See: https://jira.observium.org/browse/OBS-3200
          $si_value = -99; // This is incorrect, but minimum possible value for dBm
          $from  = $value    . ' W';
          $to = $si_value . ' dBm';
        } else {
          $si_value = FALSE;
          $from  = $value    . ' W';
          $to    = 'FALSE';
        }
      } else {
        // not convert, just keep correct value
        $type  = 'power';
      }
      break;

    case 'dbm':
      if ($type === 'power') {
        // Used when Power convert to dBm
        // https://en.wikipedia.org/wiki/DBm
        // https://www.everythingrf.com/rf-calculators/dbm-to-watts
        $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\Power($value, $unit);
        $si_value = $value_from->toUnit('W');

        $from  = $value    . " $unit";
        $to    = $si_value . ' W';

      } else {
        // not convert, just keep correct value
        $type  = 'dbm';
      }
      break;

    case 'psi':
    case 'ksi':
    case 'mpsi':
    case 'mmhg':
    case 'inhg':
    case 'bar':
    case 'atm':
      // https://en.wikipedia.org/wiki/Pounds_per_square_inch
      try {
        $tmp = \PhpUnitsOfMeasure\PhysicalQuantity\Pressure::getUnit($unit);
      } catch(Throwable $e) {
        //PHP 7+
        $unit = $unit_lower;
      } catch (Exception $e) {
        // PHP 5.x
        $unit = $unit_lower;
      }
      $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\Pressure($value, $unit);
      $si_value = $value_from->toUnit('Pa');

      $type  = 'pressure';
      $from  = $value    . " $unit";
      $to    = $si_value . ' Pa';
      break;

    case 'ft/s':
    case 'fps':
    case 'ft/min':
    case 'fpm':
    case 'lfm': // linear feet per minute
    case 'mph': // Miles per hour
    case 'mps': // Miles per second
    case 'm/min': // Meter per minute
    case 'km/h':  // Kilometer per hour
      try {
        $tmp = \PhpUnitsOfMeasure\PhysicalQuantity\Velocity::getUnit($unit);
      } catch(Throwable $e) {
        //PHP 7+
        $unit = $unit_lower;
      } catch (Exception $e) {
        // PHP 5.x
        $unit = $unit_lower;
      }
      // Any velocity units:
      $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\Velocity($value, $unit);
      $si_value = $value_from->toUnit('m/s');

      $type  = 'velocity';
      $from  = $value    . " $unit";
      $to    = $si_value . ' m/s';
      break;

    case 'ft3/s':
    case 'cfs':
    case 'ft3/min':
    case 'cfm':
    case 'gpd': // US (gallon per day)
    case 'gpm': // US (gallon per min)
    case 'l/min':
    case 'lpm':
    case 'cmh':
    case 'm3/h':
    case 'cmm':
    case 'm3/min':
      try {
        $tmp = \PhpUnitsOfMeasure\PhysicalQuantity\VolumeFlow::getUnit($unit);
      } catch(Throwable $e) {
        //PHP 7+
        $unit = $unit_lower;
      } catch (Exception $e) {
        // PHP 5.x
        $unit = $unit_lower;
      }
      if ($type === 'waterflow') {
        // Waterflow default unit is L/s
        $si_unit = 'L/s';
      } elseif ($type === 'airflow') {
        // Use for Airflow imperial unit CFM (Cubic foot per minute) as more common industry standard
        $si_unit = 'CFM';
      } else {
        // For future
        $si_unit = 'm^3/s';
      }
      $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\VolumeFlow($value, $unit);
      $si_value = $value_from->toUnit($si_unit);

      $from  = $value    . " $unit";
      $to    = $si_value . " $si_unit";
      break;

    default:
      // Ability to use any custom function to convert value based on unit name
      $function_name = 'value_unit_'.$unit_lower; // ie: value_unit_ekinops_dbm1($value) or value_unit_accuenergy($value)
      if (function_exists($function_name)) {
        $si_value = call_user_func_array($function_name, array($value));

        //$type  = $unit;
        $from  = $value . " $unit";
        $to    = $si_value;
      } elseif ($type === 'pressure' && str_ends($unit_lower, [ 'pa', 'si' ])) {
        // Any of pressure unit, like hPa
        $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\Pressure($value, $unit);
        $si_value = $value_from->toUnit('Pa');

        $from  = $value    . " $unit";
        $to    = $si_value . ' Pa';
      }
  }

  if (isset($si_value)) {
    print_debug('Converted '.strtoupper($type).' value: '.$from.' -> '.$to);
    return $si_value;
  }

  return $value; // Fallback original value
}

/**
 * Convert value of sensor from known unit to defined SI unit (used in poller/discovery)
 *
 * @param float|string $value Value
 * @param string       $unit_from Unit name/symbol for value
 * @param string       $class Type of value
 * @param string|array $unit_to Unit name/symbol for convert value (by default used sensor class default unit)
 * @return array       Array with values converted to unit_from
 */
function value_to_units($value, $unit_from, $class, $unit_to = [])
{
  global $config;

  // Convert symbols to supported by lib units
  $unit_from = str_replace(['<sup>', '</sup>'], ['^', ''], $unit_from); // I.e. mg/m<sup>3</sup> => mg/m^3
  $unit_from = html_entity_decode($unit_from);                          // I.e. &deg;C => °C

  // Non numeric values
  if (!is_numeric($value))
  {
    return [$unit_from => $value];
  }

  switch ($class)
  {
    case 'temperature':
      $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\Temperature($value, $unit_from);
      break;

    case 'pressure':
      $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\Pressure($value, $unit_from);
      break;

    case 'power':
    case 'dbm':
      $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\Power($value, $unit_from);
      break;

    case 'waterflow':
    case 'airflow':
      $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\VolumeFlow($value, $unit_from);
      break;

    case 'velocity':
      $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\Velocity($value, $unit_from);
      break;

    case 'lifetime':
    case 'uptime':
    case 'time':
      if ($unit_from == '') { $unit_from = 's'; }
      $value_from = new PhpUnitsOfMeasure\PhysicalQuantity\Time($value, $unit_from);
      break;

    default:
      // Unknown, return original value
      return [ $unit_from => $value ];
  }

  // Use our default unit (if not passed)
  if (empty($unit_to) && isset($config['sensor_types'][$class]['symbol']))
  {
    $unit_to = $config['sensor_types'][$class]['symbol'];
  }

  // Convert to units
  $units = [];
  foreach ((array)$unit_to as $to)
  {
    // Convert symbols to supported by lib units
    $tou = str_replace(['<sup>', '</sup>'], ['^', ''], $to); // I.e. mg/m<sup>3</sup> => mg/m^3
    $tou = html_entity_decode($tou);                         // I.e. &deg;C => °C

    $units[$to] = $value_from->toUnit($tou);
  }

  return $units;
}

/**
 * Replace all newlines in string to space char (except string begin and end)
 *
 * @param string $string Input string
 * @return string Output string without NL characters
 */
function nl2space($string)
{
  if (!is_string($string) || $string == '')
  {
    return $string;
  }

  $string = trim($string, "\n\r");
  return preg_replace('/ ?(\r\n|\r|\n) ?/', ' ', $string);
}

/**
 * This noob function replace windows/mac newline character to unix newline
 *
 * @param string $string Input string
 * @return string Clean output string
 */
function nl2nl($string)
{
  if (!is_string($string) || $string == '')
  {
    return $string;
  }

  return preg_replace('/\r\n|\r/', PHP_EOL, $string);
}

/**
 * Microtime
 *
 * This function returns the current Unix timestamp seconds, accurate to the
 * nearest microsecond.
 *
 * @return float
 */
function utime()
{
  return microtime(TRUE);
}


/**
 * Bitwise checking if flags set
 *
 * Examples:
 *  if (is_flag_set(FLAG_A, some_var)) // eg: some_var = 0b01100000000010
 *  if (is_flag_set(FLAG_A | FLAG_F | FLAG_L, some_var)) // to check if at least one flag is set
 *  if (is_flag_set(FLAG_A | FLAG_J | FLAG_M | FLAG_D, some_var, TRUE)) // to check if all flags are set
 *
 * @param int $flag Checked flags
 * @param int $param Parameter for checking
 * @param bool $all Check all flags
 * @return bool
 */
function is_flag_set($flag, $param, $all = FALSE)
{
  $set = $flag & $param;

  if               ($set and !$all) { return TRUE; } // at least one of the flags passed is set
  elseif ($all and ($set == $flag)) { return TRUE; } // to check that all flags are set

  return FALSE;
}

/**
 * Checks if a string is composed solely of lower case letters.
 * Primarily used to sanitise strings used for file inclusion
 *
 * @param $string
 * @return false|int
 */
function is_alpha($string) {
  return preg_match(OBS_PATTERN_ALPHA, $string);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function is_ssl() {
  if (isset($_SERVER['HTTPS'])) {
    if ('on' === strtolower($_SERVER['HTTPS']) || '1' === $_SERVER['HTTPS']) {
      return TRUE;
    }
  } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
    return TRUE;
  } elseif (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on') {
    return TRUE;
  } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
    return TRUE;
  }
  //elseif (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') { return TRUE; }

  return FALSE;
}

/**
 * Convert "smart quotes" to real quotes and emdash into a hyphen.
 *
 * @url https://stackoverflow.com/questions/1262038/how-to-replace-microsoft-encoded-quotes-in-php
 * @param string $string
 *
 * @return string
 */
function smart_quotes($string) {
  if (!is_string($string)) { return $string; }

  $quotes = [
    "\xC2\xAB"     => '"', // « (U+00AB) in UTF-8
    "\xC2\xBB"     => '"', // » (U+00BB) in UTF-8
    "\xE2\x80\x98" => "'", // ‘ (U+2018) in UTF-8
    "\xE2\x80\x99" => "'", // ’ (U+2019) in UTF-8
    "\xE2\x80\x9A" => "'", // ‚ (U+201A) in UTF-8
    "\xE2\x80\x9B" => "'", // ‛ (U+201B) in UTF-8
    "\xE2\x80\x9C" => '"', // “ (U+201C) in UTF-8
    "\xE2\x80\x9D" => '"', // ” (U+201D) in UTF-8
    "\xE2\x80\x9E" => '"', // „ (U+201E) in UTF-8
    "\xE2\x80\x9F" => '"', // ‟ (U+201F) in UTF-8
    "\xE2\x80\xB9" => "'", // ‹ (U+2039) in UTF-8
    "\xE2\x80\xBA" => "'", // › (U+203A) in UTF-8
    "\xE2\x80\xB2" => "'", // ′ (U+2032) in UTF-8
    "\xE2\x80\xB3" => '"', // ″ (U+2033) in UTF-8
    // dashes
    "\xE2\x80\x94" => '-', // — (U+2014) in UTF-8
    "\xEF\xB9\x98" => '-', // ﹘ (U+FE58) in UTF-8
  ];

  return strtr($string, $quotes);
}

// JSON escaping for JS see:
// https://stackoverflow.com/questions/7462394/php-json-string-escape-double-quotes-for-js-output
function json_escape($str) {
  if (!is_string($str)) { return $str; }

  $str = str_replace([ '\"', '"' ], [ '"', '\"' ], smart_quotes($str));
  //$str = str_replace("\u0022", "\\\"", $str );
  //$str = str_replace("\u0027", "\\'",  $str );
  return $str;
}

function safe_json_encode($var, $options = 0) {
  $options |= OBS_JSON_ENCODE; // JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION

  $str = @json_encode($var, $options);

  if (OBS_DEBUG && json_last_error() !== JSON_ERROR_NONE) {
    print_message('JSON ENCODE[%r'.json_last_error_msg().'%n]');
    echo("JSON VAR[\n".print_vars($var)."\n]\n");
  }

  return $str;
}

function safe_json_decode($str, $options = 0) {
  if (!is_string($str)) {
    // When not string passed return original variable
    // This is not same as json_decode do, but better for us
    if (OBS_DEBUG) {
      print_message('JSON DECODE[%rNot string passed%n]');
      echo("JSON RAW[\n");
      print_vars($str);
      echo("\n]\n");
    }
    //r($str);
    return $str;
  }

  $options |= OBS_JSON_DECODE; // JSON_BIGINT_AS_STRING

  $json = @json_decode(smart_quotes($str), TRUE, 512, $options);

  $json_error = json_last_error();
  if ($json_error !== JSON_ERROR_NONE) {
    $msg = json_last_error_msg();

    if ($json_error === JSON_ERROR_CTRL_CHAR) {
      // Try fix "Control character error, possibly incorrectly encoded"
      $str_fix = preg_replace('/[[:cntrl:]]/', '', smart_quotes($str));
      print_debug_vars($str_fix);
    } else {
      // Try fix utf errors
      $str_fix = fix_json_unicode(smart_quotes($str));
      print_debug_vars($str_fix);
    }
    $json_fix = @json_decode($str_fix, TRUE, 512, $options);
    if (json_last_error() === JSON_ERROR_NONE) {
      //print_vars(smart_quotes(fix_json_unicode($str)));
      //print_vars($json_fix);
      return $json_fix;
    }
    if (OBS_DEBUG) {
      print_message('JSON DECODE[%r' . $msg . '%n]');
      echo("JSON[\n" . $str . "\n]\n");
    }
  }

  return $json;
}

/*
 * PHP json_decode not correctly convert UTF8 encoded chars, but correct decode escaped unicode :/
 * "ËЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ" ->
 * "\u00cb\u0419\u0426\u0423\u041a\u0415\u041d\u0413\u0428\u0429\u0417\u0425\u042a\u0424\u042b\u0412\u0410\u041f\u0420\u041e\u041b\u0414\u0416\u042d\u042f\u0427\u0421\u041c\u0418\u0422\u042c\u0411\u042e"
 */
function fix_json_unicode($string) {
  $ret = '';
  $len = strlen($string);
  $offset = 0;
  while ($offset < $len) {
    $code = ord($string[$offset]);
    if ($code >= 128) {        //otherwise 0xxxxxxx
      if ($code < 224) {
        $bytesnumber = 2;      //110xxxxx
      } elseif ($code < 240) {
        $bytesnumber = 3;      //1110xxxx
      } elseif ($code < 248) {
        $bytesnumber = 4;      //11110xxx
      } else {
        $bytesnumber = 0;
      }
      $codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
      for ($i = 2; $i <= $bytesnumber; $i++) {
        $offset++;
        $code2 = ord($string[$offset]) - 128; //10xxxxxx
        $codetemp = $codetemp * 64 + $code2;
      }
      $code = $codetemp;

      $ret .= '\u' . str_pad(dechex($code), 4, '0', STR_PAD_LEFT);
    } else {
      $ret .= $string[$offset];
    }
    $offset++;
  }
  return $ret;
}

function safe_unserialize($str) {
  if (is_array($str)) { return NULL; }

  if (PHP_VERSION_ID >= 70000) {
    return @unserialize($str, [ 'allowed_classes' => FALSE ]);
  }

  return @unserialize($str);
}

function safe_count($array, $mode = COUNT_NORMAL) {
  if (is_countable($array)) {
    return count($array, $mode);
  }

  return 0;
}

/**
 * Report if var empty (only empty array [], string '' and NULL)
 * Note FALSE, 0 and '0' return TRUE (not empty)
 *
 * @param $var
 *
 * @return bool
 */
function safe_empty($var) {
  return $var !== 0 && $var !== '0' && $var !== FALSE && empty($var);
}

/**
 * This function return object with recursive directory iterator.
 *
 * @param string $dir
 * @param integer $max_depth The maximum allowed subdirectory depth. -1 is used for any depth.
 *
 * @return RecursiveIteratorIterator
 */
function get_recursive_directory_iterator($dir, $max_depth = -1)
{
  $directory = new RecursiveDirectoryIterator($dir, FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS);
  $iterator  = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD);
  $iterator->setMaxDepth($max_depth);

  return $iterator;
}

// Nice PHP (7.3) compat functions

if (PHP_VERSION_ID < 70300) {
  /**
   * Gets the first key of an array
   *
   * @param array $array
   * @return mixed
   */
  function array_key_first($array) {
    if (!is_array($array) || empty($array)) {
      return NULL;
    }

    foreach($array as $key => $unused) {
      return $key;
    }
    return NULL;
  }
  /* Use official polifyll
  function array_key_first($array) {
    return $array && is_array($array) ? array_keys($array)[0] : NULL;
  }
  */

  /**
   * Gets the last key of an array
   *
   * @param array $array
   * @return mixed
   */
  function array_key_last($array) {
    if (!is_array($array) || empty($array)) {
      return NULL;
    }

    end($array);
    return key($array);
    //return $array && is_array($array) ? array_keys($array)[count($array) - 1] : NULL;
  }

  function is_countable($var) {
    return (is_array($var) || $var instanceof Countable);
  }
}

// Nice PHP (8.0) compat functions

if (PHP_VERSION_ID < 80000) {
  // Note. We use better implementations str_contains_array() and str_icontains_array()
  function str_contains($haystack, $needle) {
    return '' === $needle || FALSE !== strpos($haystack, $needle);
  }

  /*
  function str_starts_with($haystack, $needle) {
    return 0 === strncmp($haystack, $needle, strlen($needle));
  }

  function str_ends_with($haystack, $needle) {
    return '' === $needle || ('' !== $haystack && 0 === substr_compare($haystack, $needle, -strlen($needle)));
  }
  */

  function fdiv($dividend, $divisor) {
    return @($dividend / $divisor);
  }
}

// EOF
