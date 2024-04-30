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

/// SEARCH STATUS
$sql = "SELECT * FROM `status` LEFT JOIN `devices` USING (`device_id`)" .
       generate_where_clause($GLOBALS['cache']['where']['devices_permitted'], '`status_descr` LIKE ?') .
       " ORDER BY `status_descr` LIMIT $query_limit";

$results = dbFetchRows($sql, [$query_param]);

if (!safe_empty($results)) {
    $max_len = 35;
    foreach ($results as $result) {
        $name        = truncate($result['status_descr'], $max_len);
        $device_name = truncate($result['hostname'], $max_len);
        if ($result['hostname'] != $result['sysName'] && $result['sysName']) {
            $device_name .= ' | ' . truncate($result['sysName'], $max_len);
        }
        $descr = strlen($result['location']) ? escape_html($result['location']) . ' | ' : '';
        $descr .= nicecase($result['entPhysicalClass']) . ' status';

        /// FIXME: once we have alerting, colour this to the sensor's status
        $tab_colour = '#194B7F'; // FIXME: This colour pulled from functions.inc.php humanize_device, maybe set it centrally in definitions?

        $status_search_results[] = [
          'url'    => 'graphs/type=status_graph/id=' . $result['status_id'] . '/',
          'name'   => $name,
          'colour' => $tab_colour,
          'icon'   => $config['icon']['status'],
          'data'   => ['| ' . escape_html($device_name), $descr]
        ];

    }

    $search_results['status'] = ['descr' => 'Status Indicators found', 'results' => $status_search_results];
}

// EOF
