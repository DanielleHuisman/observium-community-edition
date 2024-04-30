<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage functions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// This function returns an array of location data when given an address.
// The open&free geocoding APIs are not very flexible, so addresses must be in standard formats.

function geo_detect($device, $poll_device, $geo_db, &$dns_only) {
    global $config;

    if (!$config['geocoding']['enable']) {
        return FALSE;
    }

    $geo_detect    = FALSE;
    $geo_frequency = 86400; // Minimum seconds for next GEO api request (default is 1 day)
    $geo_updated   = get_time() - $geo_db['location_unixtime']; // Seconds since previous GEO update

    // Device coordinates still empty, re-detect no more than 1 time per 1 day ($geo_frequency param)
    if (!(is_numeric($geo_db['location_lat']) && is_numeric($geo_db['location_lon']))) {
        // Re-detect geolocation if coordinates still empty, no more frequently than once a day
        $geo_detect = $geo_updated > $geo_frequency;
    }

    // sysLocation changed (and not empty!), redetect now
    $geo_detect = $geo_detect || ($poll_device['sysLocation'] && $device['location'] != $poll_device['sysLocation']);
    // Geo API changed, force re-detect
    $geo_detect = $geo_detect || ($geo_db['location_geoapi'] !== strtolower($config['geocoding']['api']));

    // This seems to cause endless geolocation every poll. Disabled.
    //$geo_detect = $geo_detect || ($geo_db['location_manual'] && (!$geo_db['location_country'] || $geo_db['location_country'] == 'Unknown')); // Manual coordinates passed

    // Detect location by DNS LOC record for hostname, no more than 1 time per 1 day ($geo_frequency param)
    $dns_only   = !$geo_detect && ($config['geocoding']['dns'] && ($geo_updated > $geo_frequency));

    return $geo_detect;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_geolocation($address, $geo_db = [], $dns_only = FALSE) {
    global $config;

    $ok                          = FALSE;
    $address                     = trim($address);
    $location                    = [ 'location' => $address ]; // Init location array
    $location['location_geoapi'] = strtolower(trim($config['geocoding']['api']));
    if (!isset($config['geo_api'][$location['location_geoapi']])) {
        // Use default if unknown api
        $location['location_geoapi'] = 'geocodefarm';
    }

    // Geo definitions
    $geo_def = $config['geo_api'][$location['location_geoapi']];

    if (isset($config['geocoding']['enable']) && $config['geocoding']['enable']) {
        $geo_type  = 'forward'; // by default forward geocoding
        $debug_msg = "Geocoding ENABLED, try detect device coordinates:" . PHP_EOL;

        if ($geo_db['location_manual']) {
            // If device coordinates set manually, use Reverse Geocoding.
            $location['location_lat'] = $geo_db['location_lat'];
            $location['location_lon'] = $geo_db['location_lon'];
            $geo_type                 = 'reverse';
            $debug_msg                .= '  MANUAL coordinates - SET' . PHP_EOL;
        } elseif ($config['geocoding']['dns']) {
            // If DNS LOC support is enabled and DNS LOC record is set, use Reverse Geocoding.
            if ($geo_dnsloc = get_geo_dnsloc($geo_db['hostname'])) {
                $location = array_merge($location, $geo_dnsloc);
                $geo_type = 'reverse';
            }

            if (isset($location['location_lat'])) {
                $debug_msg .= '  DNS LOC records - FOUND' . PHP_EOL;
            } else {
                $debug_msg .= '  DNS LOC records - NOT FOUND' . PHP_EOL;
                if ($dns_only) {
                    // If we check only DNS LOC records, but it not found, exit
                    print_debug($debug_msg);
                    return FALSE;
                }
            }
        }

        /**
         * If location string contains coordinates, use Reverse Geocoding.
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
        if ($geo_type === 'forward' &&
            (preg_match(OBS_PATTERN_LATLON, $address, $matches) ||
             preg_match(OBS_PATTERN_LATLON_ALT, $address, $matches)))
        {
            if ($matches['lat'] >= -90 && $matches['lat'] <= 90 &&
                $matches['lon'] >= -180 && $matches['lon'] <= 180)
            {
                $location['location_lat'] = $matches['lat'];
                $location['location_lon'] = $matches['lon'];
                $geo_type                 = 'reverse';
            }
        }

        // Excluded bad location strings like <none>, Unknown, ''
        if ($geo_type === 'reverse' || is_valid_param($address, 'location')) {
            // We have correct non-empty address or reverse coordinates

            // Have api specific file include and definition?
            $is_geo_def  = isset($geo_def[$geo_type]);
            $is_geo_file = is_file($config['install_dir'] . '/includes/geolocation/' . $location['location_geoapi'] . '.inc.php');

            if ($geo_type === 'reverse') {
                $debug_msg .= '  by REVERSE query (API: ' . strtoupper($config['geocoding']['api']) .
                              ', LAT: ' . $location['location_lat'] . ', LON: ' . $location['location_lon'] . ') - ';
            } else {
                $debug_msg .= '  by FORWARD query (API: ' . strtoupper($config['geocoding']['api']) .
                              ', sysLocation: ' . $address . ') - ';
            }

            if ($is_geo_def) {
                // We seem to have hit a snag geocoding. It might be that the first element of the address is a business name.
                // Let's drop the first element and see if we get anything better! This works more often than one might expect.
                $address_second = $geo_type === 'forward' ? generate_location_alt($address) : NULL;

                // Generate geolocation tags, used for rewrites in definition
                $tags = generate_geolocation_tags($location['location_geoapi'], $location);

                $data = get_geo_http_def($geo_def, $geo_type, $tags, $debug_msg);

                if (!$data) {
                    // Return an old array with new status (for later recheck)
                    unset($geo_db['hostname'], $geo_db['location_updated']);
                    $location['location_status']  = $debug_msg;
                    $location['location_updated'] = format_unixtime(get_time(), 'Y-m-d G:i:s');
                    //print_vars($location);
                    //print_vars($geo_db);
                    if (safe_empty($address_second)) {
                        return array_merge($geo_db, $location);
                    }
                }

                // Coordinates aren't found, try alternative location request
                if ($geo_type === 'forward' && isset($geo_def[$geo_type]['location_lat']) &&
                    !safe_empty($address_second) &&
                    safe_empty(array_get_nested($data, $geo_def[$geo_type]['location_lat']))) {

                    // Re-Generate geolocation tags, override location
                    $tags['location'] = $address_second;

                    $data_new = get_geo_http_def($geo_def, $geo_type, $tags);
                    if (!$data_new || safe_empty(array_get_nested($data_new, $geo_def[$geo_type]['location_lat']))) {
                        // Return an old array with new status (for later recheck)
                        //print_vars($location);
                        //print_vars($geo_db);
                        return array_merge($geo_db, $location);
                    }
                    $data = $data_new;
                    unset($data_new);

                } // End second forward request
            }

            //print_vars($data);
            $geo_status = 'NOT FOUND';

            if ($is_geo_file) {
                // API-specific parser
                require($config['install_dir'] . '/includes/geolocation/' . $location['location_geoapi'] . '.inc.php');

                if (!$data) {
                    // Return an old array with new status (for later recheck)
                    unset($geo_db['hostname'], $geo_db['location_updated']);
                    //$location['location_status']  = $debug_msg;
                    $location['location_updated'] = format_unixtime(get_time(), 'Y-m-d G:i:s');
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
                        if ($location[$param] = array_get_nested($data, $field)) {
                            break;
                        }
                    }
                }
            }

            print_debug_vars($data);
        } else {
            $geo_status = 'NOT REQUESTED';
        }
    }

    // Use defaults if empty values
    if (safe_empty($location['location_lat']) || safe_empty($location['location_lon'])) {
        // Reset to empty coordinates
        $location['location_lat'] = [ 'NULL' ];
        $location['location_lon'] = [ 'NULL' ];
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
    foreach ([ 'city', 'county', 'state' ] as $entry) {
        $param = 'location_' . $entry;
        $location[$param] = !safe_empty($location[$param]) ? str_ireplace(' ' . $entry, '', $location[$param]) : 'Unknown';
    }
    // Unified Country name
    if (!safe_empty($location['location_country'])) {
        $location['location_country'] = country_from_code($location['location_country']);
        $geo_status                   = 'FOUND';
    } else {
        $location['location_country'] = 'Unknown';
    }

    // Print some debug information
    $debug_msg .= $geo_status . PHP_EOL;
    if (OBS_DEBUG < 2 && strlen($tags['key'])) {
        // Hide API KEY from output
        $url = str_replace('=' . $tags['key'], '=***', $url);
    }
    $debug_msg .= '  GEO API REQUEST: ' . $url;

    if ($geo_status === 'FOUND') {
        $debug_msg .= PHP_EOL . '  GEO LOCATION: ';
        $debug_msg .= $location['location_country'] . ' (Country), ' . $location['location_state'] . ' (State), ';
        $debug_msg .= $location['location_county'] . ' (County), ' . $location['location_city'] . ' (City)';
        $debug_msg .= PHP_EOL . '  GEO COORDINATES: ';
        $debug_msg .= $location['location_lat'] . ' (Latitude), ' . $location['location_lon'] . ' (Longitude)';
    } else {
        $debug_msg .= PHP_EOL . '  QUERY DATE: ' . date('r'); // This is required for increase data in DB
    }
    print_debug($debug_msg);
    $location['location_status'] = $debug_msg;

    return $location;
}

/**
 * Ack! dns_get_record not only cannot retrieve LOC records, but it also actively filters them when using
 * DNS_ANY as a query type (which, admittedly, would not be all that reliable as per the manual).
 *
 * Example LOC:
 *   "20 31 55.893 N 4 57 38.269 E 45.00m 10m 100m 10m"
 *
 * From Wikipedia: d1 [m1 [s1]] {"N"|"S"}  d2 [m2 [s2]] {"E"|"W"}
 *
 * Parsing this is something for Net_DNS2 as it has the code for it.
 *
 * @param string $hostname
 *
 * @return array Array with
 */
function get_geo_dnsloc($hostname) {

    if ($hostname) {
        //include_once('Net/DNS2.php');
        //include_once('Net/DNS2/RR/LOC.php');

        $resolver = new Net_DNS2_Resolver();
        try {
            $response = $resolver->query($hostname, 'LOC', 'IN');
        } catch (Net_DNS2_Exception $e) {
            $response = FALSE;
            print_debug('  Resolver error: ' . $e->getMessage() . ' (hostname: ' . $hostname . ')');
        }
    } else {
        $response = FALSE;
        print_debug("  DNS LOC enabled, but device hostname empty.");
    }

    if ($response) {
        //print_debug_vars($response->answer);
        foreach ($response->answer as $answer) {
            print_debug_vars($answer);
            if (is_numeric($answer->latitude) && is_numeric($answer->longitude)) {
                return [ 'location_lat' => $answer->latitude,
                         'location_lon' => $answer->longitude ];
            }

            if (is_numeric($answer->degree_latitude) && is_numeric($answer->degree_longitude)) {
                $ns_multiplier = $answer->ns_hem === 'N' ? 1 : -1;
                $ew_multiplier = $answer->ew_hem === 'E' ? 1 : -1;

                return [ 'location_lat' => round(($answer->degree_latitude  + $answer->min_latitude / 60  + $answer->sec_latitude / 3600)  * $ns_multiplier, 7),
                         'location_lon' => round(($answer->degree_longitude + $answer->min_longitude / 60 + $answer->sec_longitude / 3600) * $ew_multiplier, 7) ];
            }
        }
    }

    return [];
}

function get_geo_http_def($geo_def, $geo_type, $tags, &$debug_msg = '') {

    // Merge Geo forward/reverse definition
    $geo_def = array_merge($geo_def, $geo_def[$geo_type]);
    //dump($geo_def);

    // Generate context/options with encoded data and geo-specific api headers
    $options = generate_http_context($geo_def, $tags);
    $options['ignore_errors'] = TRUE;

    // API URL to POST to
    $url = generate_http_url($geo_def, $tags);

    // First or second request
    if (process_http_request($geo_def, $url, $options, $mapresponse)) {

        switch ($geo_def['response_format']) {
            case 'xml':
                // Hrm, currently unused
                break;
            case 'json':
            default:
                return safe_json_decode($mapresponse);
        }
    }

    $geo_status = $GLOBALS['response_headers']['status'] ?: get_last_message();
    $debug_msg  .= strtoupper($geo_status) . PHP_EOL;
    if (OBS_DEBUG < 2 && !safe_empty($tags['key'])) {
        // Hide API KEY from output
        $url = str_replace('=' . $tags['key'], '=***', $url);
    }
    $debug_msg .= '  GEO API REQUEST: ' . $url;
    print_debug($debug_msg);

    return FALSE;
}

/**
 * @param string $address
 *
 * @return string|null
 */
function generate_location_alt($address) {
    // We seem to have hit a snag geocoding. It might be that the first element of the address is a business name.
    // Let's drop the first element and see if we get anything better! This works more often than one might expect.
    if (str_contains($address, ' - ')) {
        // Rack: NK-76 - Nikhef, Amsterdam, NL
        $address_second = trim(explode(' - ', $address, 2)[1]);
    } elseif (str_contains($address, ',')) {
        // ZRH2, Badenerstrasse 569, Zurich, Switzerland
        $address_second = trim(explode(',', $address, 2)[1]);
    } elseif (str_contains($address, '_')) {
        // Korea_Seoul
        $address_second = str_replace('_', ' ', $address);
    } else {
        return NULL;
    }

    if (strlen($address_second) < 4) {
        return NULL;
    }

    return $address_second;
}

/**
 * Generate geolocation tags, used for transform any other parts of geo api definition.
 *
 * @param string $api      GEO api key (see geo definitions)
 * @param array  $tags     (optional) Location array and other tags
 * @param array  $params   (optional) Array of requested params with key => value entries (used with request method POST)
 * @param string $location (optional) Location string, if passed override location tag
 *
 * @return array           HTTP Context which can used in get_http_request()
 * @global array $config
 */
function generate_geolocation_tags($api, $tags = [], $params = [], $location = NULL)
{
    global $config;

    $tags = array_merge($tags, $params);

    // Override location tag if passed as argument
    if (!safe_empty($location)) {
        $tags['location'] = $location;
    }
    // Add lat/lon tags
    if (isset($tags['location_lon'])) {
        $tags['lon'] = $tags['location_lon'];
    }
    if (isset($tags['location_lon'])) {
        $tags['lat'] = $tags['location_lat'];
    }

    // Commonly used params for geo apis
    $tags['uuid'] = get_unique_id();
    $tags['id']   = OBSERVIUM_PRODUCT . '-' . substr($tags['uuid'], 0, 8);

    // API key if not empty
    if (isset($config['geo_api'][$api]['key']) && !safe_empty($config['geo_api'][$api]['key'])) {
        // Ability to store API keys for each GEO API separately
        $tags['key'] = escape_html($config['geo_api'][$api]['key']); // KEYs is never used special characters
    }

    //print_vars($tags);
    return $tags;
}

// EOF
