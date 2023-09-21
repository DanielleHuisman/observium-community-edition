#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage tests
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// This file allows you to test geocoding using observium's libraries. It's a quick diagnostic tool.
// You probably don't need to use it.


chdir(dirname($argv[0]));

//define('OBS_DEBUG', 2);

// Get options before definitions!
$options = getopt("a:d");

include_once("includes/observium.inc.php");
include_once("html/includes/functions.inc.php");

array_shift($argv);
if (isset($options['a'])) {
    array_shift($argv);
    array_shift($argv);
}
if (isset($options['d'])) {
    array_shift($argv);
}

//print_vars($argv);
//print_vars($options);

$address  = array_pop($argv);
$geo_apis = array_keys($config['geo_api']);

if (!$address) {
    print_message("%n
USAGE:
$scriptname [-d] [-a api] 'Address string'

OPTIONS:
 -a                                          API name, currently supported: " . implode(', ', $geo_apis) . "

DEBUGGING OPTIONS:
 -d                                          Enable debugging output.
 -dd                                         More verbose debugging output.

%rAddress string is empty!%n", 'color', FALSE);
    exit;
}

if (isset($options['a'])) {
    // Override default GEO API
    //$config['geocoding']['api'] = $options['a'];
    $apis = $options['a'] === 'all' ? $geo_apis : explode(',', $options['a']);
} else {
    $apis = (array)$config['geocoding']['api'];
}

print_cli_table([[$address]], ['Location']);
$table_rows = [];
foreach ($apis as $api) {
    $config['geocoding']['api'] = $api;
    $location                   = get_geolocation($address, [], FALSE);
    if (isset($location['location_lat'])) {
        $table_row = [
          '%g' . $api . '%n',
          is_array($location['location_lat']) ? '%yUnknown%n' : $location['location_lat'],
          is_array($location['location_lon']) ? '%yUnknown%n' : $location['location_lon'],
          $location['location_country'] === 'Unknown' ? '%y' . $location['location_country'] . '%n' : $location['location_country'],
          $location['location_state'] === 'Unknown' ? '%y' . $location['location_state'] . '%n' : $location['location_state'],
          $location['location_county'] === 'Unknown' ? '%y' . $location['location_county'] . '%n' : $location['location_county'],
          $location['location_city'] === 'Unknown' ? '%y' . $location['location_city'] . '%n' : $location['location_city'],
          ''
        ];
    } else {
        $table_row = ['%r' . $api . '%n', '-', '-', '-', '-', '-', '-', get_last_message()];
    }
    unset($GLOBALS['last_message']);
    $table_rows[] = $table_row;
    print_debug_vars($location);
}
print_cli_table($table_rows, ['Geo: API', 'Lat', 'Lon', 'Country', 'State', 'County', 'City', 'Error']);

// EOF
