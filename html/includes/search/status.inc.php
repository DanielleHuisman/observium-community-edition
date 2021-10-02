<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

/// SEARCH STATUS
$results = dbFetchRows("SELECT * FROM `status`
                        LEFT JOIN `devices` USING (`device_id`)
                        WHERE `status_descr` LIKE ? $query_permitted_device
                        ORDER BY `status_descr` LIMIT $query_limit", array($query_param));

if (safe_count($results)) {
  foreach ($results as $result) {
    $name = $result['status_descr'];
    if (strlen($name) > 35) { $name = substr($name, 0, 35) . "..."; }
    $descr = strlen($result['location']) ? escape_html($result['location']) . ' | ' : '';
    $descr .= nicecase($result['entPhysicalClass']) . ' status';

    /// FIXME: once we have alerting, colour this to the sensor's status
    $tab_colour = '#194B7F'; // FIXME: This colour pulled from functions.inc.php humanize_device, maybe set it centrally in definitions?

    $status_search_results[] = array('url' => 'graphs/type=status_graph/id=' . $result['status_id'] . '/',
      'name' => $name,
      'colour' => $tab_colour,
      'icon' => $config['icon']['status'],
      'data' => array(
        '| ' . escape_html($result['hostname']),
        $descr)
    );

  }

  $search_results['status'] = array('descr' => 'Status Indicators found', 'results' => $status_search_results);
}

// EOF
