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

/// SEARCH ACCESSPOINTS
$results = dbFetchRows("SELECT * FROM `groups`
                        WHERE `group_name` LIKE ? OR `group_descr` LIKE ?
                        ORDER BY `group_name` LIMIT $query_limit", array($query_param, $query_param));

if (safe_count($results)) {
  foreach ($results as $result) {
    $name = $result['group_name'];
    if (strlen($name) > 35) {
      $name = substr($name, 0, 35) . "...";
    }

    $entity_type           = $config['entities'][$result['entity_type']];

    /// FIXME: always blue
    $tab_colour = '#194B7F'; // FIXME: This colour pulled from functions.inc.php humanize_device, maybe set it centrally in definitions?

    $group_search_results[] = array('url' => generate_url(array('page' => 'group', 'group_id' => $result['group_id'])), 'name' => $name, 'colour' => $tab_colour, 'icon' => $entity_type['icon'], 'data' => array('', escape_html($result['group_descr']) . ' | '.nicecase($result['entity_type']).' Group'),);

  }

  $search_results['groups'] = array('descr' => 'Groups found', 'results' => $group_search_results);
}