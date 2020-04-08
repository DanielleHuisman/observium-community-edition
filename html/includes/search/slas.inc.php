<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage search
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

/// SEARCH SENSORS
$results = dbFetchRows("SELECT * FROM `slas` LEFT JOIN `devices` USING (`device_id`) WHERE `sla_target` LIKE ? $query_permitted_device ORDER BY `sla_target` LIMIT $query_limit", array($query_param));


if (count($results))
{
  foreach ($results as $result)
  {

    humanize_sla($result);

    $tab_colour = '#194B7F';

    $sla_search_results[] = array('url'  => generate_url(array('page' => 'device', 'device' => $result['device_id'], 'tab' => 'slas', 'id' => $result['sla_id'])),
                                     'name' => 'SLA-#'.$result['sla_index']. ' (' . $result['rtt_label'] .')', 'colour' => $tab_colour,
                                     'icon' => '<i class="' . $config['entities']['sla']['icon'] . '"></i>',
                                     'data' => array(
                                       escape_html($result['hostname']),
                                       highlight_search($result['sla_target']),
                                     )
    );
  }

  $search_results['slas'] = array('descr' => 'SLAs found', 'results' => $sla_search_results);
}