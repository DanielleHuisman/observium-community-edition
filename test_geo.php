#!/usr/bin/env php
<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage syslog
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// This file allows you to test geocoding using observium's libraries. It's a quick diagnostic tool.
// You probably don't need to use it.


chdir(dirname($argv[0]));
$scriptname = basename($argv[0]);

//define('OBS_DEBUG', 2);

// Get options before definitions!
$options = getopt("a:d");

include_once("includes/sql-config.inc.php");
include_once("html/includes/functions.inc.php");

array_shift($argv);
if (isset($options['a']))
{
  array_shift($argv);
  array_shift($argv);
  // Override default GEO API
  $config['geocoding']['api'] = $options['a'];
}
if (isset($options['d'])) { array_shift($argv); }

//print_vars($argv);
//print_vars($options);

$address = array_pop($argv);

if (!$address)
{
    $geo_apis = array_keys($config['geo_api']);
  print_message("%n
USAGE:
$scriptname [-d] [-a api] 'Address string'

OPTIONS:
 -a                                          API name, currently supported: ".implode(', ', $geo_apis)."
 -

DEBUGGING OPTIONS:
 -d                                          Enable debugging output.
 -dd                                         More verbose debugging output.

%rAddress string is empty!%n", 'color', FALSE);
  exit;
}

$location = get_geolocation($address, array(), FALSE);
print_vars($location);

// EOF
