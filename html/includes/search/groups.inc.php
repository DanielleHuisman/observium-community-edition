<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

/// Search Groups
$results = dbFetchRows('SELECT * FROM `groups`
                        WHERE `group_name` LIKE ? OR `group_descr` LIKE ?
                        ORDER BY `group_name` LIMIT ' . $query_limit, array($query_param, $query_param));

$group_search_results = [];

if (!safe_empty($results)) {
  $max_len = 35;
  foreach ($results as $result) {
    $name = truncate($result['group_name'], $max_len);

    $entity_type = $config['entities'][$result['entity_type']];

    /// FIXME: always blue
    $tab_colour = '#194B7F'; // FIXME: This colour pulled from functions.inc.php humanize_device, maybe set it centrally in definitions?

    $group_search_results[] = array('url' => generate_url(array('page' => 'group', 'group_id' => $result['group_id'])), 'name' => $name, 'colour' => $tab_colour, 'icon' => $entity_type['icon'], 'data' => array('', escape_html($result['group_descr']) . ' | '.nicecase($result['entity_type']).' Group'),);

  }

  $search_results['groups'] = array('descr' => 'Groups found', 'results' => $group_search_results);
}