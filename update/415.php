<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$sql = "SELECT * FROM `devices_locations` WHERE 1";
$sql .= generate_query_values_and([ 'Unknown', '' ], 'location_country', '!=');
$devices_locations = dbFetchRows($sql);

if (count((array)$devices_locations))
{
  echo 'Unificate GEO Country names: ';

  foreach ($devices_locations as $entry)
  {

    $country = country_from_code($entry['location_country']);
    if ($country != $entry['location_country'])
    {
      $update_array = [ 'location_country' => $country,
                        'location_updated' => $entry['location_updated'] ]; // Keep updated timestamp
      dbUpdate($update_array, 'devices_locations', '`location_id` = ?', [$entry['location_id']]);
      echo('.');
    }
  }
}

// EOF

