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

  if (isset($data['response']))
  {
    // Detect if required second request
    $try_second = FALSE;
    if ($data['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'] > 0)
    {
      $data = $data['response']['GeoObjectCollection']['featureMember'][0];
      print_debug_vars($data);
      if ($geo_type == 'forward' && $data['GeoObject']['metaDataProperty']['GeocoderMetaData']['precision'] == 'other')
      {
        $try_second = TRUE;
      }
    }
    elseif ($geo_type == 'forward')
    {
      $try_second = TRUE;
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
        if ($data_new['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'] > 0 &&
            $data_new['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['metaDataProperty']['GeocoderMetaData']['precision'] != 'other')
        {
          $url  = $url_new;
          $data = $data_new['response']['GeoObjectCollection']['featureMember'][0];
        }
      }
    }

    if ($geo_type == 'forward')
    {
      // If using reverse queries, do not change lat/lon
      list($location['location_lon'], $location['location_lat']) = explode(' ', $data['GeoObject']['Point']['pos']);
    }

    $data = $data['GeoObject']['metaDataProperty']['GeocoderMetaData']['AddressDetails'];
    $location['location_country'] = strtolower($data['Country']['CountryNameCode']);
    $location['location_state']   = $data['Country']['AdministrativeArea']['AdministrativeAreaName'];
    if (isset($data['Country']['AdministrativeArea']['SubAdministrativeArea']))
    {
      $location['location_county']  = $data['Country']['AdministrativeArea']['SubAdministrativeArea']['SubAdministrativeAreaName'];
      $location['location_city']    = $data['Country']['AdministrativeArea']['SubAdministrativeArea']['Locality']['LocalityName'];
    }
    else if (isset($data['Country']['AdministrativeArea']['Locality']['DependentLocality']))
    {
      $location['location_county']  = $data['Country']['AdministrativeArea']['Locality']['DependentLocality']['DependentLocalityName'];
      $location['location_city']    = $data['Country']['AdministrativeArea']['Locality']['DependentLocality']['DependentLocality']['DependentLocalityName'];
    } else {
      $location['location_county']  = $data['Country']['AdministrativeArea']['AdministrativeAreaName'];
      $location['location_city']    = $data['Country']['AdministrativeArea']['Locality']['LocalityName'];
    }
  } else {
    $data = FALSE;
  }

// EOF
