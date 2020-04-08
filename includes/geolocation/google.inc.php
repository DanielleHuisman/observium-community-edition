<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage geolocation
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

/**
 * Here passed $data array with decoded first request response
 * At end returned main $location variables
 */

  // Use google data only with good status response
  if (in_array($data['status'], ['OK', 'ZERO_RESULTS', 'UNKNOWN_ERROR']))
  {
    $data       = $data['results'][0];
    // Detect if required second request
    $try_second = FALSE;
    if ($geo_type == 'forward')
    {
      $try_second = !isset($data['geometry']['location_type']) || $data['geometry']['location_type'] == 'APPROXIMATE';
    }

    // Make second request (address_second passed from main function get_geolocation()
    if ($try_second && strlen($address_second) > 4)
    {
      // Re-Generate geolocation tags, override location
      $tags['location'] = $address_second;

      // Generate context/options with encoded data and geo specific api headers
      $options_new = generate_http_context($geo_def[$geo_type], $tags);

      // API URL to POST to
      $url_new = generate_http_url($geo_def[$geo_type], $tags);

      // Second request
      $mapresponse = get_http_request($url_new, $options_new, $ratelimit);
      if (test_http_request($geo_def[$geo_type], $mapresponse))
      {
        // Valid response
        $data_new = json_decode($mapresponse, TRUE);
        if ($data_new['status'] == 'OK' && $data_new['results'][0]['geometry']['location_type'] != 'APPROXIMATE')
        {
          $url  = $url_new;
          $data = $data_new['results'][0];
        }
      }
    }

    if ($geo_type == 'forward')
    {
      // If using reverse queries, do not change lat/lon
      $location['location_lat'] = $data['geometry']['location']['lat'];
      $location['location_lon'] = $data['geometry']['location']['lng'];
    }
    foreach ($data['address_components'] as $entry)
    {
      switch ($entry['types'][0])
      {
        case 'sublocality_level_1':
        case 'postal_town':
        case 'locality':
          $location['location_city'] = $entry['long_name'];
          break;
        case 'administrative_area_level_2':
          $location['location_county'] = $entry['long_name'];
          break;
        case 'administrative_area_level_1':
          $location['location_state'] = $entry['long_name'];
          break;
        case 'country':
          $location['location_country'] = strtolower($entry['short_name']);
          break;
      }
    }
  } else {
    $data = FALSE;
  }

// EOF
