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

/// SEARCH SENSORS
$results = dbFetchRows("SELECT * FROM `slas` LEFT JOIN `devices` USING (`device_id`) WHERE (`sla_target` LIKE ? OR `sla_index` LIKE ? OR `sla_tag` LIKE ?) $query_permitted_device ORDER BY `sla_target` LIMIT $query_limit", array($query_param, $query_param, $query_param));


if (count($results))
{
  foreach ($results as $result)
  {
    humanize_sla($result);

    $descr = strlen($result['location']) ? escape_html($result['location']) . ' | ' : '';
    $descr .= $result['rtt_label'];
    $tab_colour = '#194B7F';

    $sla_search_results[] = array('url'  => generate_url(array('page' => 'device', 'device' => $result['device_id'], 'tab' => 'slas', 'id' => $result['sla_id'])),
                                     'name' => $result['sla_descr'],
                                     'colour' => $tab_colour,
                                     'icon' => $config['icon']['sla'],
                                     'data' => array(
                                       '| ' . escape_html($result['hostname']),
                                       $descr,
                                     )
    );
  }

  $search_results['slas'] = array('descr' => 'SLAs found', 'results' => $sla_search_results);
}

// EOF