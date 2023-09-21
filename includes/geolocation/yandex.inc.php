<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage geolocation
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/**
 * Here passed $data array with decoded first request response
 * At end returned main $location variables
 */

if (isset($data['response'])) {
    // Detect if required second request
    $try_second = FALSE;
    if ($data['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'] > 0) {
        $data = $data['response']['GeoObjectCollection']['featureMember'][0];
        print_debug_vars($data);
        if ($geo_type === 'forward' && $data['GeoObject']['metaDataProperty']['GeocoderMetaData']['precision'] === 'other') {
            $try_second = TRUE;
        }
    } elseif ($geo_type === 'forward') {
        $try_second = TRUE;
    }

    // Make second request (address_second passed from main function get_geolocation()
    if ($try_second && !safe_empty($address_second)) {
        // Re-Generate geolocation tags, override location
        $tags['location'] = $address_second;

        // Second request
        $data_new = get_geo_http_def($geo_def, $geo_type, $tags);
        if ($data_new && $data_new['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'] > 0 &&
            $data_new['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['metaDataProperty']['GeocoderMetaData']['precision'] !== 'other') {
            $data = $data_new['response']['GeoObjectCollection']['featureMember'][0];
        }
        unset($data_new);
    }

    if ($geo_type === 'forward') {
        // If using reverse queries, do not change lat/lon
        [ $location['location_lon'], $location['location_lat'] ] = explode(' ', $data['GeoObject']['Point']['pos']);
    }

    $data = $data['GeoObject']['metaDataProperty']['GeocoderMetaData']['AddressDetails'];
    $location['location_country'] = strtolower($data['Country']['CountryNameCode']);
    $location['location_state']   = $data['Country']['AdministrativeArea']['AdministrativeAreaName'];
    if (isset($data['Country']['AdministrativeArea']['SubAdministrativeArea'])) {
        $entry = $data['Country']['AdministrativeArea']['SubAdministrativeArea'];
        print_debug_vars($entry);
        $location['location_county'] = $entry['SubAdministrativeAreaName'];
        $location['location_city']   = $entry['Locality']['LocalityName'] ?? $entry['Locality']['DependentLocality']['DependentLocalityName'];
    } elseif (isset($data['Country']['AdministrativeArea']['Locality']['DependentLocality'])) {
        $entry = $data['Country']['AdministrativeArea']['Locality']['DependentLocality'];
        print_debug_vars($entry);
        $location['location_county'] = $entry['DependentLocalityName'];
        $location['location_city']   = $entry['DependentLocality']['DependentLocalityName'];
    } else {
        $entry = $data['Country']['AdministrativeArea'];
        print_debug_vars($entry);
        $location['location_county'] = $entry['AdministrativeAreaName'];
        $location['location_city']   = $entry['Locality']['LocalityName'];
    }
} else {
    $data = FALSE;
}

// EOF
