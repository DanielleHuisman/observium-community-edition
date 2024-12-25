<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) Adam Armstrong
 *
 */

if ($_SESSION['userlevel'] >= 5) {
/// Search Groups
    $results = dbFetchRows('SELECT * FROM `groups`
                        WHERE `group_name` LIKE ? OR `group_descr` LIKE ?
                        ORDER BY `group_name` LIMIT ' . $query_limit, [$query_param, $query_param]);

    $group_search_results = [];

    if (!safe_empty($results)) {
        $max_len = 35;
        foreach ($results as $result) {
            $name = truncate($result['group_name'], $max_len);

            $entity_type = $config['entities'][$result['entity_type']];

            /// FIXME: always blue
            $tab_colour = '#194B7F'; // FIXME: This colour pulled from functions.inc.php humanize_device, maybe set it centrally in definitions?

            $group_search_results[] = [
              'url'    => generate_url(['page' => 'group', 'group_id' => $result['group_id']]),
              'name'   => $name,
              'colour' => $tab_colour,
              'icon'   => $entity_type['icon'],
              'data'   => ['', escape_html($result['group_descr']) . ' | ' . nicecase($result['entity_type']) . ' Group']
            ];

        }

        $search_results['groups'] = ['descr' => 'Groups found', 'results' => $group_search_results];
    }
}