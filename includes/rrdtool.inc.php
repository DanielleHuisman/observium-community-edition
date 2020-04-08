<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage rrdtool
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

/**
 * Get full path for rrd file.
 *
 * @param array $device Device array
 * @param string $filename Base filename for rrd file
 * @return string Full rrd file path
 */
// TESTME needs unit testing
function get_rrd_path($device, $filename)
{
  global $config;

  $rrd_dir = trim($config['rrd_dir']) . '/';
  $filename = trim($filename);
  if (str_starts($filename, $rrd_dir))
  {
    // Already full path
    return $filename;
  }

  $filename = safename($filename);

  // If filename empty, return base rrd dirname for device (for example in delete_device())
  $rrd_file = $rrd_dir;
  if (strlen($device['hostname']))
  {
    $rrd_file .= $device['hostname'] . '/';
  }

  if (strlen($filename) > 0)
  {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if ($ext != 'rrd') { $filename .= '.rrd'; } // Add rrd extension if not already set
    $rrd_file .= safename($filename);

    // Add rrd filename to global array $graph_return
    $GLOBALS['graph_return']['rrds'][] = $rrd_file;
  }

  return $rrd_file;
}

/**
 * Rename rrd file for device is some schema changes.
 *
 * @param array $device
 * @param string $old_rrd Base filename for old rrd file
 * @param string $new_rrd Base filename for new rrd file
 * @param boolean $overwrite Force overwrite new rrd file if already exist
 * @return bool TRUE if renamed
 */
function rename_rrd($device, $old_rrd, $new_rrd, $overwrite = FALSE)
{
  $old_rrd = get_rrd_path($device, $old_rrd);
  $new_rrd = get_rrd_path($device, $new_rrd);
  print_debug_vars($old_rrd);
  print_debug_vars($new_rrd);
  if (is_file($old_rrd))
  {
    if (!$overwrite && is_file($new_rrd))
    {
      // If not forced overwrite file, return false
      print_debug("RRD already exist new file: '$new_rrd'");
      $renamed = FALSE;
    } else {
      $renamed = rename($old_rrd, $new_rrd);
    }
  } else {
    print_debug("RRD old file not found: '$old_rrd'");
    $renamed = FALSE;
  }
  if ($renamed)
  {
    print_debug("RRD moved: '$old_rrd' -> '$new_rrd'");
  }

  return $renamed;
}

/**
 * Rename rrd file for device (same as in rename_rrd()),
 * but rrd filename detected by common entity params
 *
 * @param array $device
 * @param string $entity Entity type (sensor, status, etc..)
 * @param array $old Old entity params, based on discovery entity
 * @param array $new New entity params, based on discovery entity
 * @param boolean $overwrite Force overwrite new rrd file if already exist
 * @return bool TRUE if renamed
 */
function rename_rrd_entity($device, $entity, $old, $new, $overwrite = FALSE)
{
  switch ($entity)
  {
    case 'sensor':
      $old_sensor = array('poller_type'  => $old['poller_type'],
                          'sensor_descr' => $old['descr'],
                          'sensor_class' => $old['class'],
                          'sensor_type'  => $old['type'],
                          'sensor_index' => $old['index']);
      $new_sensor = array('poller_type'  => $new['poller_type'],
                          'sensor_descr' => $new['descr'],
                          'sensor_class' => $new['class'],
                          'sensor_type'  => $new['type'],
                          'sensor_index' => $new['index']);

      $old_rrd = get_sensor_rrd($device, $old_sensor);
      $new_rrd = get_sensor_rrd($device, $new_sensor);
      break;
    case 'status':
      $old_status = array('poller_type'  => $old['poller_type'],
                          'status_descr' => $old['descr'],
                          'status_type'  => $old['type'],
                          'status_index' => $old['index']);
      $new_status = array('poller_type'  => $new['poller_type'],
                          'status_descr' => $new['descr'],
                          'status_type'  => $new['type'],
                          'status_index' => $new['index']);

      $old_rrd = get_status_rrd($device, $old_status);
      $new_rrd = get_status_rrd($device, $new_status);
      break;
    default:
      print_debug("skipped unknown entity for rename rrd");
      return FALSE;
  }

  $old_rrd = safename($old_rrd);

  return rename_rrd($device, $old_rrd, $new_rrd, $overwrite);
}

/**
 * Opens up a pipe to RRDTool using handles provided
 *
 * @return boolean
 * @global array $config
 * @param &rrd_process
 * @param &rrd_pipes
 */
// TESTME needs unit testing
function rrdtool_pipe_open(&$rrd_process, &$rrd_pipes)
{
  global $config;

  $command = $config['rrdtool'] . ' -'; // Waits for input via standard input (STDIN)

  $descriptorspec = array(
     0 => array('pipe', 'r'),  // stdin
     1 => array('pipe', 'w'),  // stdout
     2 => array('pipe', 'w')   // stderr
  );

  $cwd = $config['rrd_dir'];
  $env = array();

  $rrd_process = proc_open($command, $descriptorspec, $rrd_pipes, $cwd, $env);

  stream_set_blocking($rrd_pipes[1], 0);
  stream_set_blocking($rrd_pipes[2], 0);

  if (is_resource($rrd_process))
  {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout
    // 2 => readable handle connected to child stderr
    if (OBS_DEBUG > 1)
    {
      print_message('RRD PIPE OPEN[%gTRUE%n]', 'console');
    }

    return TRUE;
  } else {
    if (isset($config['rrd']['debug']) && $config['rrd']['debug'])
    {
      logfile('rrd.log', "RRD pipe process not opened '$command'.");
    }
    if (OBS_DEBUG > 1)
    {
      print_message('RRD PIPE OPEN[%rFALSE%n]', 'console');
    }
    return FALSE;
  }
}

/**
 * Closes the pipe to RRDTool
 *
 * @return integer
 * @param resource rrd_process
 * @param array rrd_pipes
 */
// TESTME needs unit testing
function rrdtool_pipe_close($rrd_process, &$rrd_pipes)
{
  if (OBS_DEBUG > 1)
  {
    $rrd_status['stdout'] = stream_get_contents($rrd_pipes[1]);
    $rrd_status['stderr'] = stream_get_contents($rrd_pipes[2]);
  }

  if (is_resource($rrd_pipes[0]))
  {
    fclose($rrd_pipes[0]);
  }
  fclose($rrd_pipes[1]);
  fclose($rrd_pipes[2]);

  // It is important that you close any pipes before calling
  // proc_close in order to avoid a deadlock

  $rrd_status['exitcode'] = proc_close($rrd_process);
  if (OBS_DEBUG > 1)
  {
    print_message('RRD PIPE CLOSE['.($rrd_status['exitcode'] !== 0 ? '%rFALSE' : '%gTRUE').'%n]', 'console');
    if ($rrd_status['stdout'])
    {
      print_message("RRD PIPE STDOUT[\n".$rrd_status['stdout']."\n]", 'console', FALSE);
    }
    if ($rrd_status['exitcode'] && $rrd_status['stderr'])
    {
      // Show stderr if exitcode not 0
      print_message("RRD PIPE STDERR[\n".$rrd_status['stderr']."\n]", 'console', FALSE);
    }
  }

  return $rrd_status['exitcode'];
}

/**
 * Generates a graph file at $graph_file using $options
 * Opens its own rrdtool pipe.
 *
 * @return integer
 * @param string graph_file
 * @param string options
 */
// TESTME needs unit testing
function rrdtool_graph($graph_file, $options)
{
  global $config;

  // Note, always use pipes, because standard command line has limits!
  if ($config['rrdcached'])
  {
    $options = str_replace($config['rrd_dir'].'/', '', $options);
    $cmd = 'graph --daemon ' . $config['rrdcached'] . " $graph_file $options";
  } else {
    $cmd = "graph $graph_file $options";
  }
  $GLOBALS['rrd_status']  = FALSE;
  $GLOBALS['exec_status'] = array('command'  => $config['rrdtool'] . ' ' . $cmd,
                                  'stdout'   => '',
                                  'exitcode' => -1);

  $start = microtime(TRUE);
  rrdtool_pipe_open($rrd_process, $rrd_pipes);
  if (is_resource($rrd_process))
  {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout
    // Any error output will be appended to /tmp/error-output.txt

    fwrite($rrd_pipes[0], $cmd);
    fclose($rrd_pipes[0]);

    $iter = 0;
    while (strlen($line) < 1 && $iter < 1000)
    {
      // wait for 10 milliseconds to loosen loop
      usleep(10000);
      $line = fgets($rrd_pipes[1], 1024);
      $stdout .= $line;
      $iter++;
    }
    $stdout = preg_replace('/(?:\n|\r\n|\r)$/D', '', $stdout); // remove last (only) eol
    unset($iter);

    $runtime  = microtime(TRUE) - $start;

    // Check rrdtool's output for the command.
    if (preg_match('/\d+x\d+/', $stdout))
    {
      $GLOBALS['rrd_status'] = TRUE;
    } else {
      $stderr = trim(stream_get_contents($rrd_pipes[2]));
      if (isset($config['rrd']['debug']) && $config['rrd']['debug'])
      {
        logfile('rrd.log', "RRD $stderr, CMD: " . $GLOBALS['exec_status']['command']);
      }
    }
    $exitcode = rrdtool_pipe_close($rrd_process, $rrd_pipes);

    $GLOBALS['exec_status']['exitcode'] = $exitcode;
    $GLOBALS['exec_status']['stdout']   = $stdout;
    $GLOBALS['exec_status']['stderr']   = $stderr;
  } else {
    $runtime = microtime(TRUE) - $start;
    $stdout  = NULL;
  }
  $GLOBALS['exec_status']['runtime']  = $runtime;
  // Add some data to global array $graph_return
  $GLOBALS['graph_return']['status']   = $GLOBALS['rrd_status'];
  $GLOBALS['graph_return']['command']  = $GLOBALS['exec_status']['command'];
  $GLOBALS['graph_return']['filename'] = $graph_file;
  $GLOBALS['graph_return']['output']   = $stdout;
  $GLOBALS['graph_return']['runtime']  = $GLOBALS['exec_status']['runtime'];

  if (OBS_DEBUG)
  {
    print_message(PHP_EOL . 'RRD CMD[%y' . $cmd . '%n]', 'console', FALSE);
    $debug_msg  = 'RRD RUNTIME['.($runtime > 0.1 ? '%r' : '%g').round($runtime, 4).'s%n]' . PHP_EOL;
    $debug_msg .= 'RRD STDOUT['.($GLOBALS['rrd_status'] ? '%g': '%r').$stdout.'%n]' . PHP_EOL;
    if ($stderr)
    {
      $debug_msg .= 'RRD STDERR[%r'.$stderr.'%n]' . PHP_EOL;
    }
    $debug_msg .= 'RRD_STATUS['.($GLOBALS['rrd_status'] ? '%gTRUE': '%rFALSE').'%n]';

    print_message($debug_msg . PHP_EOL, 'console');
  }

  return $stdout;
}

/**
 * Generates and pipes a command to rrdtool
 *
 * @param string command
 * @param string filename
 * @param string options
 * @global array $config
 * @global mixed $rrd_pipes
 */
// TESTME needs unit testing
function rrdtool($command, $filename, $options)
{
  global $config, $rrd_pipes;

  // We now require rrdcached 1.5.5
  if ($config['rrdcached'] && (OBS_RRD_NOLOCAL || !in_array($command, ['create', 'tune'])))
  {
    $filename = str_replace($config['rrd_dir'].'/', '', $filename);
    if (OBS_RRD_NOLOCAL && $command == 'create')
    {
      // No overwrite for remote rrdtool, since no way for check if rrdfile exist
      $options  .= ' --no-overwrite';
    }
    $options  .= ' --daemon ' . $config['rrdcached'];
  }

  $cmd = "$command $filename $options";

  $GLOBALS['rrd_status'] = FALSE;
  $GLOBALS['exec_status'] = array('command' => $config['rrdtool'] . ' ' . $cmd,
                                  'exitcode' => 1);

  if ($config['norrd'])
  {
    print_message("[%rRRD Disabled - $cmd%n]", 'color');
    return NULL;
  }

  if (in_array($command, array('fetch', 'last', 'lastupdate', 'tune')))
  {
    // This commands require exact STDOUT, skip use pipes
    $command = $config['rrdtool'] . ' ' . $cmd;
    $stdout = external_exec($command, 500); // Limit exec time to 500ms
    $runtime = $GLOBALS['exec_status']['runtime'];
    $GLOBALS['rrd_status'] = $GLOBALS['exec_status']['exitcode'] === 0;
    // Check rrdtool's output for the command.
    if (!$GLOBALS['rrd_status'] && isset($config['rrd']['debug']) && $config['rrd']['debug'])
    {
      logfile('rrd.log', "RRD ".$GLOBALS['exec_status']['stderr'].", CMD: $cmd");
    }
  } else {
    // FIXME, need add check if pipes exist
    $start = microtime(TRUE);
    fwrite($rrd_pipes[0], $cmd."\n");
    usleep(1000);

    $stdout = trim(stream_get_contents($rrd_pipes[1]));
    $stderr = trim(stream_get_contents($rrd_pipes[2]));
    $runtime = microtime(TRUE) - $start;

    // Check rrdtool's output for the command.
    if (strpos($stdout, 'ERROR') !== FALSE)
    {
      if (isset($config['rrd']['debug']) && $config['rrd']['debug'])
      {
        logfile('rrd.log', "RRD $stdout, CMD: $cmd");
      }
    } else {
      $GLOBALS['rrd_status'] = TRUE;
      $GLOBALS['exec_status']['exitcode'] = 0;
    }
    $GLOBALS['exec_status']['stdout']  = $stdout;
    $GLOBALS['exec_status']['stdin']   = $stdin;
    $GLOBALS['exec_status']['runtime'] = $runtime;

  }

  $GLOBALS['rrdtool'][$command]['time'] += $runtime;
  $GLOBALS['rrdtool'][$command]['count']++;

  if (OBS_DEBUG)
  {
    print_message(PHP_EOL . 'RRD CMD[%y' . $cmd . '%n]', 'console', FALSE);
    $debug_msg  = 'RRD RUNTIME['.($runtime > 1 ? '%r' : '%g').round($runtime, 4).'s%n]' . PHP_EOL;
    $debug_msg .= 'RRD STDOUT['.($GLOBALS['rrd_status'] ? '%g': '%r').$stdout.'%n]' . PHP_EOL;
    if ($stderr)
    {
      $debug_msg .= 'RRD STDERR[%r'.$stderr.'%n]' . PHP_EOL;
    }
    $debug_msg .= 'RRD_STATUS['.($GLOBALS['rrd_status'] ? '%gTRUE': '%rFALSE').'%n]';

    print_message($debug_msg . PHP_EOL, 'console');
  }

  return $stdout;
}

/**
 * Generates an rrd database at $filename using $options
 * Creates the file if it does not exist yet.
 * DEPRECATED: use rrdtool_create_ng(), this will disappear and ng will be renamed when conversion is complete.
 *
 * @param array  device
 * @param string filename
 * @param string ds
 * @param string options
 */
function rrdtool_create($device, $filename, $ds, $options = '')
{
  global $config;

  if ($filename[0] == '/')
  {
    print_debug("You should pass the filename only (not the full path) to this function! Passed filename: ".$filename);
    $filename = basename($filename);
  }

  $fsfilename = get_rrd_path($device, $filename);

  if ($config['norrd'])
  {
    print_message("[%rRRD Disabled - create $fsfilename%n]", 'color');
    return NULL;
  }
  else if (OBS_RRD_NOLOCAL)
  {
    print_debug("RRD create $fsfilename passed to remote rrdcached with --no-overwrite.");
  }
  else if (rrd_exists($device, $filename))
  {
    print_debug("RRD $fsfilename already exists - no need to create.");
    return FALSE; // Bail out if the file exists already
  }

  if (!$options)
  {
    $options = preg_replace('/\s+/', ' ', $config['rrd']['rra']);
  }

  $step = "--step ".$config['rrd']['step'];

  //$command = $config['rrdtool'] . " create $fsfilename $ds $step $options";
  //return external_exec($command);

  // Clean up old ds strings. This is kinda nasty.
  $ds = str_replace("\
", '', $ds);
  return rrdtool('create', $fsfilename, $ds . " $step $options");

}

/**
 * Generates RRD filename from definition
 *
 * @param string/array $def    Original filename, using %index% (or %custom% %keys%) as placeholder for indexes
 * @param string/array $index  Index, if RRD type is indexed (or array of multiple indexes)
 * @return string              Filename of RRD
 */
// TESTME needs unit testing
function rrdtool_generate_filename($def, $index)
{
  if (is_string($def))
  {
    // Compat with old
    $filename = $def;
  }
  elseif (isset($def['file']))
  {
    $filename = $def['file'];
  }
  elseif (isset($def['entity_type']))
  {
    // Entity specific filename by ID, ie for sensor/status/counter
    $entity_id = $index;
    return get_entity_rrd_by_id($def['entity_type'], $entity_id);
  }

  // Generate warning for indexed filenames containing %index% - does not help if you use custom field names for indexing
  if (strstr($filename, '%index%') !== FALSE)
  {
    if ($index === NULL)
    {
      print_warning("RRD filename generation error: filename contains %index%, but \$index is NULL!");
    }
  }

  // Convert to single element array if not an array.
  // This will automatically use %index% as the field to replace (see below).
  if (!is_array($index)) { $index = array('index' => $index); }

  // Replace %index% by $index['index'], %foo% by $index['foo'] etc.
  $filename = array_tag_replace($index, $filename);

  return safename($filename);
}

/**
 * Generates an rrd database based on $type definition, using $options
 * Only creates the file if it does not exist yet.
 * Should most likely not be called on its own, as an update call will check for existence.
 *
 * @param array        $device   Device array
 * @param string/array $type     rrd file type from $config['rrd_types'] or actual config array
 * @param string/array $index    Index, if RRD type is indexed (or array of multiple tags)
 * @param array        $options  Options for create RRD, like STEP, RRA, MAX or SPEED
 *
 * @return string
 */
// TESTME needs unit testing
function rrdtool_create_ng($device, $type, $index = NULL, $options = [])
{
  global $config;

  if (!is_array($type)) // We were passed a string
  {
    if (!is_array($config['rrd_types'][$type])) // Check if definition exists
    {
      print_warning("Cannot create RRD for type $type - not found in definitions!");
      return FALSE;
    }

    $definition = $config['rrd_types'][$type];
  } else { // We were passed an array, use as-is
    $definition = $type;
  }

  $filename = rrdtool_generate_filename($definition, $index);

  $fsfilename = get_rrd_path($device, $filename);

  if ($config['norrd'])
  {
    print_message("[%rRRD Disabled - create $fsfilename%n]", 'color');
    return NULL;
  }
  else if (OBS_RRD_NOLOCAL)
  {
    print_debug("RRD create $fsfilename passed to remote rrdcached with --no-overwrite.");
  }
  else if (rrd_exists($device, $filename))
  {
    print_debug("RRD $fsfilename already exists - no need to create.");
    return FALSE; // Bail out if the file exists already
  }

  // Set RRA option
  $rra = isset($options['rra']) ? $options['rra'] : $config['rrd']['rra'];
  $rra = preg_replace('/\s+/', ' ', $rra);

  // Set step
  $step = isset($options['step']) ? $options['step'] : $config['rrd']['step'];

  // Create tags, for use in replace
  $tags = [];
  if (strlen($index))
  {
    $tags['index'] = $index;
  }
  if (isset($options['speed']))
  {
    print_debug("Passed speed: ".$options['speed']);
    $options['speed'] = intval(unit_string_to_numeric($options['speed']) / 8); // Detect passed speed value (converted to bits)
    $tags['speed']    = max($options['speed'], $config['max_port_speed']);     // But result select maximum between passed and default!
    print_debug("   RRD speed: ".$options['speed'].PHP_EOL.
                "     Default: ".$config['max_port_speed'].PHP_EOL.
                "         Max: ".$tags['speed']);
  } else {
    // Default speed
    $tags['speed'] = $config['max_port_speed'];
  }

  // Create DS parameter based on the definition
  $ds = array();

  foreach ($definition['ds'] as $name => $def)
  {
    if (strlen($name) > 19) { print_warning("SEVERE: DS name $name is longer than 19 characters - over RRD limit!"); }

    // Set defaults for missing attributes
    if (!isset($def['type']))      { $def['type'] = 'COUNTER'; }
    if (!isset($def['max']))       { $def['max'] = 'U'; }
    else                           { $def['max'] = array_tag_replace($tags, $def['max']); } // can use %speed% tag, speed must passed by $options['speed']
    if (!isset($def['min']))       { $def['min'] = 'U'; }
    if (!isset($def['heartbeat'])) { $def['heartbeat'] = 2 * $step; }

    // Create DS string to pass on the command line
    $ds[] = "DS:$name:" . $def['type'] . ':' . $def['heartbeat'] . ':' . $def['min'] . ':' . $def['max'];
  }


  return rrdtool('create', $fsfilename, implode(' ', $ds) . " --step $step $rra");

}

/**
 * Checks if an RRD database at $filename for $device exists
 * Checks via rrdcached if configured, else via is_exists
 *
 * @param array  device
 * @param string filename
**/
function rrd_exists($device, $filename)
{

  global $config;

  $fsfilename = get_rrd_path($device, $filename);

  if (OBS_RRD_NOLOCAL)
  {
    // NOTE. RRD last on remote daemon reduce polling times
    rrdtool_last($fsfilename);

    //ERROR: realpath(vds.coosm.net/status.rrd): No such file or directory
    return strpos($GLOBALS['exec_status']['stderr'], 'No such file or directory') === FALSE;
    //return $GLOBALS['rrd_status'];
  } else {
    if (is_file($fsfilename))
    {
      return TRUE;
    } else {
      return FALSE;
    }
  }

}

/**
 * Updates an rrd database at $filename using $options
 * Where $options is an array, each entry which is not a number is replaced with "U"
 *
 * @param array        $device  Device array
 * @param string/array $type    RRD file type from $config['rrd_types'] or actual config array
 * @param array        $ds      DS data (key/value)
 * @param string/array $index   Index, if RRD type is indexed (or array of multiple indexes)
 * @param bool         $create  Create RRD file if it does not exist
 * @param array        $options Options to pass to create function if file does not exist
 *
 * @return string
 */
// TESTME needs unit testing
function rrdtool_update_ng($device, $type, $ds, $index = NULL, $create = TRUE, $options = [])
{
  global $config, $graphs;

  if (!is_array($type)) // We were passed a string
  {
    if (!is_array($config['rrd_types'][$type])) // Check if definition exists
    {
      print_warning("Cannot create RRD for type $type - not found in definitions!");
      return FALSE;
    }

    $definition = $config['rrd_types'][$type];

    // Append graph if not already passed
    if (!isset($definition['graphs']))
    {
      $definition['graphs'] = array(str_replace('-', '_', $type));
    }
  } else { // We were passed an array, use as-is
    $definition = $type;
  }

  $filename = rrdtool_generate_filename($definition, $index);

  $fsfilename = get_rrd_path($device, $filename);

  // Create the file if missing (if we have permission to create it)
  if ($create)
  {
    rrdtool_create_ng($device, $type, $index, $options);
  }

  $update = array('N');

  foreach ($definition['ds'] as $name => $def)
  {
    if (isset($ds[$name]))
    {
      if (is_numeric($ds[$name]))
      {
        // Add data to DS update string
        $update[] = $ds[$name];
      } else {
        // Data not numeric, mark unknown
        $update[] = 'U';
      }
    } else {
      // Data not sent, mark unknown
      $update[] = 'U';
    }
  }

  /** // This is setting loads of random shit that doesn't exist
      // ONLY GRAPHS THAT EXIST MAY GO INTO THIS ARRAY
  // Set global graph variable for store avialable device graphs
  foreach ($definition['graphs'] as $def)
  {
    $graphs[$def] = TRUE;
  }
  **/

  if ($config['influxdb']['enabled'])
  {
    influxdb_update($device, $filename, $ds, $definition, $index);
  }

  return rrdtool('update', $fsfilename, implode(':', $update));
}

/**
 * Updates an rrd database at $filename using $options
 * Where $options is an array, each entry which is not a number is replaced with "U"
 * DEPRECATED: use rrdtool_update_ng(), this will disappear and ng will be renamed when conversion is complete.
 *
 * @param array   $device
 * @param string  $filename
 * @param array   $options
 * @return string
 */
function rrdtool_update($device, $filename, $options)
{
  // Do some sanitization on the data if passed as an array.
  if (is_array($options))
  {
    $values[] = "N";
    foreach ($options as $value)
    {
      if (!is_numeric($value)) { $value = 'U'; }
      $values[] = $value;
    }
    $options = implode(':', $values);
  }

  if ($filename[0] == '/')
  {
    $filename = basename($filename);
    print_debug("You should pass the filename only (not the full path) to this function!");
  }

  $fsfilename = get_rrd_path($device, $filename);

  if ($GLOBALS['config']['influxdb']['enabled'])
  {
    influxdb_update( $device, $filename, $options );
  }

  return rrdtool("update", $fsfilename, $options);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rrdtool_fetch($filename, $options)
{
  return rrdtool('fetch', $filename, $options);
}

// TESTME needs unit testing
/**
 * Returns the UNIX timestamp of the most recent update of $filename
 *
 * @param string $filename RRD filename
 * @param string $options Mostly not required
 * @return string UNIX timestamp
 */
function rrdtool_last($filename, $options = '')
{
  return rrdtool('last', $filename, $options);
}

// TESTME needs unit testing
/**
 * Returns the UNIX timestamp and the value stored for each datum in the most recent update of $filename
 *
 * @param string $filename RRD filename
 * @param string $options Mostly not required
 * @return string UNIX timestamp and the value stored for each datum
 */
function rrdtool_lastupdate($filename, $options = '')
{
  return rrdtool('lastupdate', $filename, $options);
}

// TESTME needs unit testing
/**
 * Renames a DS inside an RRD file
 *
 * @param array  $device   Device
 * @param string $filename Filename
 * @param string $oldname  Current DS name
 * @param string $newname  New DS name
 */
function rrdtool_rename_ds($device, $filename, $oldname, $newname)
{
  global $config;

  $return = FALSE;
  if ($config['norrd'])
  {
    print_message('[%gRRD Disabled%n] ');
    return $return;
  }

  // rrdtool tune rename DS supported since v1.4
  $version = get_versions();
  if (version_compare($version['rrdtool_version'], '1.4', '>='))
  {
    $fsfilename = get_rrd_path($device, $filename);
    print_debug("RRD DS renamed, file $fsfilename: '$oldname' -> '$newname'");
    return rrdtool('tune', $filename, "--data-source-rename $oldname:$newname");
  }

  // Comparability with old version (but we support only >= v1.5.5, this not required)
  if (OBS_RRD_NOLOCAL)
  {
    print_message('[%gRRD REMOTE UNSUPPORTED%n] ');
  } else {
    $fsfilename = get_rrd_path($device, $filename);
    if (is_file($fsfilename))
    {
      // this function used in discovery, where not exist rrd pipes
      $command = $config['rrdtool'] . " tune $fsfilename --data-source-rename $oldname:$newname";
      $return  = external_exec($command);
      //print_vars($GLOBALS['exec_status']);
      if ($GLOBALS['exec_status']['exitcode'] === 0)
      {
        print_debug("RRD DS renamed, file $fsfilename: '$oldname' -> '$newname'");
      } else {
        $return = FALSE;
      }
    }
  }

  return $return;
}

// TESTME needs unit testing
/**
 * Adds a DS to an RRD file
 *
 * @param array Device
 * @param string Filename
 * @param string New DS name
 */
function rrdtool_add_ds($device, $filename, $add)
{
  global $config;

  $return = FALSE;
  if ($config['norrd'])
  {
    print_message("[%gRRD Disabled%n] ");
    return $return;
  }

  // rrdtool tune add DS supported since v1.4
  $version = get_versions();
  if (version_compare($version['rrdtool_version'], '1.4', '>='))
  {
    $fsfilename = get_rrd_path($device, $filename);
    print_debug("RRD DS added, file ".$fsfilename.": '".$add."'");
    return rrdtool('tune', $filename, "DS:$add");
  }

  // Comparability with old version (but we support only >= v1.5.5, this not required)
  if (OBS_RRD_NOLOCAL)
  {
    print_message('[%gRRD REMOTE UNSUPPORTED%n] ');
  } else {
    $fsfilename = get_rrd_path($device, $filename);
    if (is_file($fsfilename))
    {
      // this function used in discovery, where not exist rrd pipes

      $fsfilename = get_rrd_path($device, $filename);

      $return  = external_exec($config['install_dir'] . "/scripts/add_ds_to_rrd.pl ".dirname($fsfilename)." ".basename($fsfilename)." $add");

      //print_vars($GLOBALS['exec_status']);
      if ($GLOBALS['exec_status']['exitcode'] === 0)
      {
        print_debug("RRD DS added, file ".$fsfilename.": '".$add."'");
      } else {
        $return = FALSE;
      }
    }
  }

  return $return;
}

// TESTME needs unit testing
/**
 * Adds one or more RRAs to an RRD file; space-separated if you want to add more than one.
 *
 * @param array  Device
 * @param string Filename
 * @param array  RRA(s) to be added to the RRD file
 */
function rrdtool_add_rra($device, $filename, $options)
{
  global $config;

  if ($config['norrd'])
  {
    print_message('[%gRRD Disabled%n] ');
  }
  else if (OBS_RRD_NOLOCAL)
  {
    ///FIXME Currently unsupported on remote rrdcached
    print_message('[%gRRD REMOTE UNSUPPORTED%n] ');
  } else {
    $fsfilename = get_rrd_path($device, $filename);

    external_exec($config['install_dir'] . "/scripts/rrdtoolx.py addrra $fsfilename $fsfilename.new $options");
    rename("$fsfilename.new", $fsfilename);
  }
}

/**
 * Escapes strings for RRDtool
 *
 * @param string String to escape
 * @param integer if passed, string will be padded and trimmed to exactly this length (after rrdtool unescapes it)
 *
 * @return string Escaped string
 */
// TESTME needs unit testing
function rrdtool_escape($string, $maxlength = NULL)
{
  if ($maxlength != NULL)
  {
    $string = substr(str_pad($string, $maxlength),0,$maxlength);
  }

  $string = str_replace(array(':', "'", '%'), array('\:', '`', '%%'), $string);

  // FIXME: should maybe also probably escape these? # \ ? [ ^ ] ( $ ) '

  return $string;
}

/**
 * Helper function to strip quotes from RRD output
 *
 * @str RRD-Info generated string
 * @return String with one surrounding pair of quotes stripped
 */
// TESTME needs unit testing
function rrd_strip_quotes($str)
{
  if ($str[0] == '"' && $str[strlen($str)-1] == '"')
  {
    return substr($str, 1, strlen($str)-2);
  }

  return $str;
}

/**
 * Determine useful information about RRD file
 *
 * Copyright (C) 2009  Bruno Prémont <bonbons AT linux-vserver.org>
 *
 * @file Name of RRD file to analyse
 *
 * @return Array describing the RRD file
 *
 */
// TESTME needs unit testing
function rrdtool_file_info($file)
{
  global $config;

  $info = array('filename'=>$file);

  if (OBS_RRD_NOLOCAL)
  {
    ///FIXME Currently unsupported on remote rrdcached
    print_message('[%gRRD REMOTE UNSUPPORTED%n] ');
    return $info;
  }

  $rrd = array_filter(explode(PHP_EOL, external_exec($config['rrdtool'] . ' info ' . $file)), 'strlen');
  if ($rrd)
  {
    foreach ($rrd as $s)
    {
      $p = strpos($s, '=');
      if ($p === false)
      {
        continue;
      }

      $key = trim(substr($s, 0, $p));
      $value = trim(substr($s, $p+1));
      if (strncmp($key,'ds[', 3) == 0)
      {
        /* DS definition */
        $p = strpos($key, ']');
        $ds = substr($key, 3, $p-3);
        if (!isset($info['DS']))
        {
          $info['DS'] = array();
        }

        $ds_key = substr($key, $p+2);

        if (strpos($ds_key, '[') === false)
        {
          if (!isset($info['DS']["$ds"]))
          {
            $info['DS']["$ds"] = array();
          }
          $info['DS']["$ds"]["$ds_key"] = rrd_strip_quotes($value);
        }
      }
      else if (strncmp($key, 'rra[', 4) == 0)
      {
        /* RRD definition */
        $p = strpos($key, ']');
        $rra = substr($key, 4, $p-4);
        if (!isset($info['RRA']))
        {
          $info['RRA'] = array();
        }
        $rra_key = substr($key, $p+2);

        if (strpos($rra_key, '[') === false)
        {
          if (!isset($info['RRA']["$rra"]))
          {
            $info['RRA']["$rra"] = array();
          }
          $info['RRA']["$rra"]["$rra_key"] = rrd_strip_quotes($value);
        }
      } else if (strpos($key, '[') === false) {
        $info[$key] = rrd_strip_quotes($value);
      }
    }
  }

  return $info;
}

// Creates a string of X number of ,ADDNAN. Used when aggregating things.
function rrd_addnan($count)
{
  return str_repeat(',ADDNAN', $count);
}

// creates an rpn string to add an array of DSes together
function rrd_aggregate_dses($ds_list)
{
  return implode(',', $ds_list) . rrd_addnan(count($ds_list) - 1);
}

// EOF
