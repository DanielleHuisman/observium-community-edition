<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/// SEARCH ACCESSPOINTS
$results = dbFetchRows("SELECT * FROM `wifi_aps`" .
                       generate_where_clause($GLOBALS['cache']['where']['devices_permitted'], '`ap_name` LIKE ?') .
                       " ORDER BY `ap_name` LIMIT $query_limit", [$query_param]);

if (!safe_empty($results)) {
    $max_len = 35;
    foreach ($results as $result) {
        $name = truncate($result['ap_name']);

        /// FIXME: once we have alerting, colour this to the sensor's status
        $tab_colour = '#194B7F'; // FIXME: This colour pulled from functions.inc.php humanize_device, maybe set it centrally in definitions?

        $ap_search_results[] = [
          'url'    => generate_url(['page' => 'device', 'device' => $result['device_id'], 'tab' => 'wifi', 'view' => 'accesspoints', 'accesspoint' => $result['wifi_ap_id']]),
          'name'   => $name,
          'colour' => $tab_colour,
          'icon'   => $config['icon']['wifi'],
          'data'   => [
            $result['ap_name'],
            escape_html($result['ap_location']) . ' | Access point'
          ]
        ];

    }

    $search_results['accesspoints'] = ['descr' => 'APs found', 'results' => $ap_search_results];
}

// EOF
