<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage geolocation
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

// This function returns an array of location data when given an address.
// The open&free geocoding APIs are not very flexible, so addresses must be in standard formats.

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_geolocation($address, $geo_db = array(), $dns_only = FALSE)
{
  global $config;

  $ok       = FALSE;
  $address  = trim($address);
  $location = array('location' => $address); // Init location array
  $location['location_geoapi'] = strtolower(trim($config['geocoding']['api']));
  if (!isset($config['geo_api'][$location['location_geoapi']])) {
    // Use default if unknown api
    $location['location_geoapi'] = 'geocodefarm';
  }

  // v3 of geo definitions :D
  $geo_def = $config['geo_api'][$location['location_geoapi']];

  // API Rate limit
  $ratelimit = FALSE;
  if (strlen($config['geocoding']['api_key']) && isset($geo_def['ratelimit_key'])) {
    // Ratelimit if used api key
    $ratelimit = $geo_def['ratelimit_key'];
  } elseif (isset($geo_def['ratelimit'])) {
    $ratelimit = $geo_def['ratelimit'];
  }

  if (isset($config['geocoding']['enable']) && $config['geocoding']['enable']) {
    $geo_type = 'forward'; // by default forward geocoding
    $debug_msg = "Geocoding ENABLED, try detect device coordinates:".PHP_EOL;

    if ($geo_db['location_manual']) {
      // If device coordinates set manually, use Reverse Geocoding.
      $location['location_lat'] = $geo_db['location_lat'];
      $location['location_lon'] = $geo_db['location_lon'];
      $geo_type = 'reverse';
      $debug_msg .= '  MANUAL coordinates - SET'.PHP_EOL;
    } elseif ($config['geocoding']['dns']) {
      // If DNS LOC support is enabled and DNS LOC record is set, use Reverse Geocoding.
      /**
       * Ack! dns_get_record not only cannot retrieve LOC records, but it also actively filters them when using
       * DNS_ANY as query type (which, admittedly would not be all that reliable as per the manual).
       *
       * Example LOC:
       *   "20 31 55.893 N 4 57 38.269 E 45.00m 10m 100m 10m"
       *
       * From Wikipedia: d1 [m1 [s1]] {"N"|"S"}  d2 [m2 [s2]] {"E"|"W"}
       *
       * Parsing this is something for Net_DNS2 as it has the code for it.
       */
      if ($geo_db['hostname']) {
        //include_once('Net/DNS2.php');
        //include_once('Net/DNS2/RR/LOC.php');

        $resolver = new Net_DNS2_Resolver();
        try {
          $response = $resolver->query($geo_db['hostname'], 'LOC', 'IN');
        } catch(Net_DNS2_Exception $e) {
          $response = FALSE;
          print_debug('  Resolver error: '.$e->getMessage().' (hostname: '.$geo_db['hostname'].')');
        }
      } else {
        $response = FALSE;
        print_debug("  DNS LOC enabled, but device hostname empty.");
      }
      if ($response) {
        print_debug_vars($response->answer);
        foreach ($response->answer as $answer) {
          if (is_numeric($answer->latitude) && is_numeric($answer->longitude)) {
            $location['location_lat'] = $answer->latitude;
            $location['location_lon'] = $answer->longitude;
            $geo_type = 'reverse';
            break;
          } elseif (is_numeric($answer->degree_latitude) && is_numeric($answer->degree_longitude)) {
            $ns_multiplier = ($answer->ns_hem === 'N' ? 1 : -1);
            $ew_multiplier = ($answer->ew_hem === 'E' ? 1 : -1);

            $location['location_lat'] = round($answer->degree_latitude + $answer->min_latitude/60 + $answer->sec_latitude/3600,7) * $ns_multiplier;
            $location['location_lon'] = round($answer->degree_longitude + $answer->min_longitude/60 + $answer->sec_longitude/3600,7) * $ew_multiplier;
            $geo_type = 'reverse';
            break;
          }
        }
        if (isset($location['location_lat'])) {
          $debug_msg .= '  DNS LOC records - FOUND'.PHP_EOL;
        } else {
          $debug_msg .= '  DNS LOC records - NOT FOUND'.PHP_EOL;
          if ($dns_only) {
            // If we check only DNS LOC records but it not found, exit
            print_debug($debug_msg);
            return FALSE;
          }
        }
      }
    }

    /**
     * If location string contains coordinates use Reverse Geocoding.
     * Valid strings:
     *   Some location [33.234, -56.22]
     *   Some location (33.234 -56.22)
     *   Some location [33.234;-56.22]
     *   33.234,-56.22
     *   '33.234','-56.22'
     * https://mailman.nanog.org/pipermail/nanog/2020-December/210761.html:
     *   Some location|47.616380|-122.341673
     *   Some location|'47.616380'|'-122.341673'
     */
    $pattern1 = '/(?:^|[\[(])\s*[\'"]?(?<lat>[+\-]?\d+(?:\.\d+)*)[\'"]?\s*[,:; ]\s*[\'"]?(?<lon>[+\-]?\d+(?:\.\d+)*)[\'"]?\s*(?:[\])]|$)/';
    $pattern2 = '/\s*\|[\'"]?(?<lat>[+\-]?\d+(?:\.\d+)*)[\'"]?\s*\|\s*[\'"]?(?<lon>[+\-]?\d+(?:\.\d+)*)[\'"]?\s*$/';
    if ($geo_type === 'forward' &&
        (preg_match($pattern1, $address, $matches) || preg_match($pattern2, $address, $matches))) {
      if ($matches['lat'] >= -90  && $matches['lat'] <= 90 &&
          $matches['lon'] >= -180 && $matches['lon'] <= 180) {
        $location['location_lat'] = $matches['lat'];
        $location['location_lon'] = $matches['lon'];
        $geo_type = 'reverse';
      }
    }

    // Excluded bad location strings like <none>, Unknown, ''
    if ($geo_type === 'reverse' || is_valid_param($address, 'location'))
    {
      // We have correct non empty address or reverse coordinates

      // Have api specific file include and definition?
      $is_geo_def = isset($geo_def[$geo_type]);
      $is_geo_file = is_file($config['install_dir'] . '/includes/geolocation/' . $location['location_geoapi'] . '.inc.php');

      if ($geo_type === 'reverse') {
        $debug_msg .= '  by REVERSE query (API: '.strtoupper($config['geocoding']['api']).', LAT: '.$location['location_lat'].', LON: '.$location['location_lon'].') - ';
      } else {
        $debug_msg .= '  by FORWARD query (API: '.strtoupper($config['geocoding']['api']).', sysLocation: ' . $address . ') - ';
      }

      if ($is_geo_def) {
        if ($geo_type === 'forward') {
          // We seem to have hit a snag geocoding. It might be that the first element of the address is a business name.
          // Lets drop the first element and see if we get anything better! This works more often than one might expect.
          if (str_contains($address, ' - ')) {
            // Rack: NK-76 - Nikhef, Amsterdam, NL
            list(, $address_second) = explode(' - ', $address, 2);
            $address_second = trim($address_second);
          } elseif (str_contains($address, ',')) {
            // ZRH2, Badenerstrasse 569, Zurich, Switzerland
            list(, $address_second) = explode(',', $address, 2);
            $address_second = trim($address_second);
          } elseif (str_contains($address, '_')) {
            // Korea_Seoul
            $address_second = str_replace('_', ' ', $address);
          }
          if (strlen($address_second) < 4) {
            $address_second = NULL;
          }
        }

        // Generate geolocation tags, used for rewrites in definition
        $tags = generate_geolocation_tags($location['location_geoapi'], $location);

        // Generate context/options with encoded data and geo specific api headers
        $options = generate_http_context($geo_def[$geo_type], $tags);
        $options['ignore_errors'] = TRUE;

        // API URL to POST to
        $url = generate_http_url($geo_def[$geo_type], $tags);

        // First request
        $mapresponse = get_http_request($url, $options, $ratelimit);

        // Send out API call and parse response
        if (!test_http_request($geo_def[$geo_type], $mapresponse)) {
          // False response
          $geo_status = strtoupper($GLOBALS['response_headers']['status']);
          $debug_msg .= $geo_status . PHP_EOL;
          if (OBS_DEBUG < 2 && strlen($tags['key'])) {
            // Hide API KEY from output
            $url = str_replace('=' . $tags['key'], '=***', $url);
          }
          $debug_msg .= '  GEO API REQUEST: ' . $url;
          print_debug($debug_msg);
          // Return old array with new status (for later recheck)
          unset($geo_db['hostname'], $geo_db['location_updated']);
          $location['location_status']  = $debug_msg;
          $location['location_updated'] = format_unixtime($config['time']['now'], 'Y-m-d G:i:s');
          //print_vars($location);
          //print_vars($geo_db);
          if (safe_empty($address_second)) {
            return array_merge($geo_db, $location);
          }
        }

        switch ($geo_def[$geo_type]['response_format']) {
          case 'xml':
            // Hrm, currently unused
            break;
          case 'json':
          default:
            $data = safe_json_decode($mapresponse);
        }


        // Coordinates not found, try second request
        if ($geo_type === 'forward' && !safe_empty($address_second) &&
            isset($geo_def[$geo_type]['location_lat']) &&
            !strlen(array_get_nested($data, $geo_def[$geo_type]['location_lat']))) {

          // Re-Generate geolocation tags, override location
          $tags['location'] = $address_second;

          // Generate context/options with encoded data and geo specific api headers
          $options_new = generate_http_context($geo_def[$geo_type], $tags);
          $options_new['ignore_errors'] = TRUE;

          // API URL to POST to
          $url_new = generate_http_url($geo_def[$geo_type], $tags);

          // Second request
          $mapresponse = get_http_request($url_new, $options_new, $ratelimit);
          /*
          if (test_http_request($geo_def[$geo_type], $mapresponse)) {
            // Valid response
            $data_new = safe_json_decode($mapresponse);
            if (strlen(array_get_nested($data, $geo_def[$geo_type]['location_lat']))) {
              $data = $data_new;
              $url = $url_new;
            }
          } else {
            // Return old array with new status (for later recheck)
            return array_merge($geo_db, $location);
          }
          */
          if (!test_http_request($geo_def[$geo_type], $mapresponse)) {
            // False response
            $geo_status = strtoupper($GLOBALS['response_headers']['status']);
            $debug_msg .= $geo_status . PHP_EOL;
            if (OBS_DEBUG < 2 && strlen($tags['key'])) {
              // Hide API KEY from output
              $url = str_replace('=' . $tags['key'], '=***', $url);
            }
            $debug_msg .= '  GEO API REQUEST: ' . $url;
            print_debug($debug_msg);
            // Return old array with new status (for later recheck)
            unset($geo_db['hostname'], $geo_db['location_updated']);
            $location['location_status']  = $debug_msg;
            $location['location_updated'] = format_unixtime($config['time']['now'], 'Y-m-d G:i:s');
            //print_vars($location);
            //print_vars($geo_db);
            return array_merge($geo_db, $location);
          } else {
            // Valid response
            $data_new = safe_json_decode($mapresponse);
            if (strlen(array_get_nested($data_new, $geo_def[$geo_type]['location_lat']))) {
              $data = $data_new;
              $url = $url_new;
            }
          }
        } // End second forward request

      }

      //print_vars($data);
      $geo_status  = 'NOT FOUND';

      if ($is_geo_file) {
        // API specific parser
        require($config['install_dir'] . '/includes/geolocation/' . $location['location_geoapi'] . '.inc.php');

        if ($data === FALSE)
        {
          // Return old array with new status (for later recheck)
          unset($geo_db['hostname'], $geo_db['location_updated']);
          //$location['location_status']  = $debug_msg;
          $location['location_updated'] = format_unixtime($config['time']['now'], 'Y-m-d G:i:s');
          //print_vars($location);
          //print_vars($geo_db);
          return array_merge($geo_db, $location);
        }

      } elseif ($is_geo_def) {
        // Set lat/lon and others by definitions
        if ($geo_type === 'forward') {
          $location['location_lat'] = array_get_nested($data, $geo_def[$geo_type]['location_lat']);
          $location['location_lon'] = array_get_nested($data, $geo_def[$geo_type]['location_lon']);
        }
        foreach ([ 'city', 'county', 'state', 'country' ] as $entry) {
          $param = 'location_' . $entry;
          foreach ((array)$geo_def[$geo_type][$param] as $field) {
            // Possible to use multiple fields, use first not empty
            if ($location[$param] = array_get_nested($data, $field)) { break; }
          }
        }
      }

      print_debug_vars($data);
    } else {
      $geo_status  = 'NOT REQUESTED';
    }
  }

  // Use defaults if empty values
  if (!strlen($location['location_lat']) || !strlen($location['location_lon']))
  {
    // Reset to empty coordinates
    $location['location_lat'] = array('NULL');
    $location['location_lon'] = array('NULL');
    //$location['location_lat'] = $config['geocoding']['default']['lat'];
    //$location['location_lon'] = $config['geocoding']['default']['lon'];
    //if (is_numeric($config['geocoding']['default']['lat']) && is_numeric($config['geocoding']['default']['lon']))
    //{
    //  $location['location_manual']     = 1; // Set manual key for ability reset from WUI
    //}
  } else {
    // Always round lat/lon same as DB precision (DECIMAL(10,7))
    $location['location_lat'] = round($location['location_lat'], 7);
    $location['location_lon'] = round($location['location_lon'], 7);
  }

  // Remove duplicate County/State words
  foreach (array('city', 'county', 'state') as $entry)
  {
    $param = 'location_' . $entry;
    $location[$param] = strlen($location[$param]) ? str_ireplace(' '.$entry, '', $location[$param]) : 'Unknown';
  }
  // Unified Country name
  if (strlen($location['location_country']))
  {
    $location['location_country'] = country_from_code($location['location_country']);
    $geo_status = 'FOUND';
  } else {
    $location['location_country'] = 'Unknown';
  }

  // Print some debug information
  $debug_msg .= $geo_status . PHP_EOL;
  if (OBS_DEBUG < 2 && strlen($tags['key']))
  {
    // Hide API KEY from output
    $url = str_replace('=' . $tags['key'], '=***', $url);
  }
  $debug_msg .= '  GEO API REQUEST: ' . $url;

  if ($geo_status === 'FOUND')
  {
    $debug_msg .= PHP_EOL . '  GEO LOCATION: ';
    $debug_msg .= $location['location_country'].' (Country), '.$location['location_state'].' (State), ';
    $debug_msg .= $location['location_county'] .' (County), ' .$location['location_city'] .' (City)';
    $debug_msg .= PHP_EOL . '  GEO COORDINATES: ';
    $debug_msg .= $location['location_lat'] .' (Latitude), ' .$location['location_lon'] .' (Longitude)';
  } else {
    $debug_msg .= PHP_EOL . '  QUERY DATE: '.date('r'); // This is required for increase data in DB
  }
  print_debug($debug_msg);
  $location['location_status'] = $debug_msg;

  return $location;
}

/**
 * Generate geolocation tags, used for transform any other parts of geo api definition.
 *
 * @global array $config
 * @param string $api      GEO api key (see geo definitions)
 * @param array  $tags     (optional) Location array and other tags
 * @param array  $params   (optional) Array of requested params with key => value entries (used with request method POST)
 * @param string $location (optional) Location string, if passed override location tag
 * @return array           HTTP Context which can used in get_http_request_test() or get_http_request()
 */
function generate_geolocation_tags($api, $tags = array(), $params = array(), $location = NULL)
{
  global $config;

  $tags = array_merge($tags, $params);

  // Override location tag if passed as argument
  if (strlen($location))
  {
    $tags['location'] = $location;
  }
  // Add lat/lon tags
  if (isset($tags['location_lon']))
  {
    $tags['lon'] = $tags['location_lon'];
  }
  if (isset($tags['location_lon']))
  {
    $tags['lat'] = $tags['location_lat'];
  }

  // Common used params for geo apis
  $tags['id'] = OBSERVIUM_PRODUCT . '-' . substr(get_unique_id(), 0, 8);
  $tags['uuid'] = get_unique_id();

  // API key if not empty
  if (isset($config['geo_api'][$api]['key']) && strlen($config['geo_api'][$api]['key']))
  {
    // Ability to store API keys for each GEO API separately
    $tags['key'] = escape_html($config['geo_api'][$api]['key']); // KEYs is never used special characters
  }
  elseif (strlen($config['geocoding']['api_key']))
  {
    $tags['key'] = escape_html($config['geocoding']['api_key']); // KEYs is never used special characters
  }

  //print_vars($tags);
  return $tags;
}

// EOF
