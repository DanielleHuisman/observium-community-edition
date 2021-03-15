<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

/// SEARCH STATUS
$results = dbFetchRows("SELECT * FROM `entPhysical`
                        LEFT JOIN `devices` USING (`device_id`)
                        WHERE (`entPhysicalSerialNum` LIKE ? OR `entPhysicalModelName` LIKE ?) $query_permitted_device
                        ORDER BY `entPhysicalName` LIMIT $query_limit", array($query_param, $query_param));

if (count($results))
{
  foreach ($results as $result)
  {
    $name = $result['entPhysicalPhysicalName'];
    if (strlen($name) > 35) { $name = substr($name, 0, 35) . "..."; }

    if(strlen($result['entPhysicalModelName']))
    {
      $model = $result['entPhysicalModelName'];
    } elseif(strlen($result['entPhysicalDescr'])) {
      $model = $result['entPhysicalDescr'];
    } elseif(strlen($result['entPhysicalName'])) {
      $model = $result['entPhysicalName'];
    } else {
      $model = "";
    }

    /// FIXME: once we have alerting, colour this to the sensor's status
    $tab_colour = '#194B7F'; // FIXME: This colour pulled from functions.inc.php humanize_device, maybe set it centrally in definitions?

    $status_search_results[] = array('url' => generate_device_url($result, array('tab' => 'entphysical')),
      'name' => $name, 'colour' => $tab_colour,
      'icon' => $config['icon']['inventory'],
      'data' => array(
        escape_html($result['hostname']),
        html_highlight(escape_html($model) . ' | SN: ' . escape_html($result['entPhysicalSerialNum']), $queryString)
        )
    );

  }

  $search_results['status'] = array('descr' => 'Inventory entry found', 'results' => $status_search_results);
}

// EOF

