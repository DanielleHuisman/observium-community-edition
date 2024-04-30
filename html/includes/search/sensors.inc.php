<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/// SEARCH SENSORS
$results = dbFetchRows("SELECT * FROM `sensors`
                        LEFT JOIN `devices` USING (`device_id`)" .
                       generate_where_clause($GLOBALS['cache']['where']['devices_permitted'], '`sensor_descr` LIKE ?') .
                       " ORDER BY `sensor_descr` LIMIT $query_limit", [$query_param]);

if (!safe_empty($results)) {
    $max_len = 35;
    foreach ($results as $result) {
        $name        = truncate($result['sensor_descr'], $max_len);
        $device_name = truncate($result['hostname'], $max_len);
        if ($result['hostname'] != $result['sysName'] && $result['sysName']) {
            $device_name .= ' | ' . truncate($result['sysName'], $max_len);
        }

        $descr = strlen($result['location']) ? escape_html($result['location']) . ' | ' : '';
        $descr .= nicecase($result['sensor_class']) . ' sensor';

        /// FIXME: once we have alerting, colour this to the sensor's status
        $tab_colour = '#194B7F'; // FIXME: This colour pulled from functions.inc.php humanize_device, maybe set it centrally in definitions?

        $sensor_search_results[] = ['url'    => 'graphs/type=sensor_' . $result['sensor_class'] . '/id=' . $result['sensor_id'] . '/',
                                    'name'   => $name,
                                    'colour' => $tab_colour,
                                    'icon'   => $config['sensor_types'][$result['sensor_class']]['icon'],
                                    'data'   => [
                                      '| ' . escape_html($device_name),
                                      $descr]
        ];
    }

    $search_results['sensors'] = ['descr' => 'Sensors found', 'results' => $sensor_search_results];
}

// EOF
