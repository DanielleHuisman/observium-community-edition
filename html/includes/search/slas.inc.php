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
$results = dbFetchRows("SELECT * FROM `slas` LEFT JOIN `devices` USING (`device_id`)" .
                       generate_where_clause($GLOBALS['cache']['where']['device_permitted'], '(`sla_target` LIKE ? OR `sla_index` LIKE ? OR `sla_tag` LIKE ?)') .
                       " ORDER BY `sla_target` LIMIT $query_limit", [$query_param, $query_param, $query_param]);

if (!safe_empty($results)) {
    $max_len = 35;
    foreach ($results as $result) {
        humanize_sla($result);

        $device_name = truncate($result['hostname'], $max_len);
        if ($result['hostname'] != $result['sysName'] && $result['sysName']) {
            $device_name .= ' | ' . truncate($result['sysName'], $max_len);
        }
        $descr      = strlen($result['location']) ? escape_html($result['location']) . ' | ' : '';
        $descr      .= $result['rtt_label'];
        $tab_colour = '#194B7F';

        $sla_search_results[] = [
          'url'    => generate_url(['page' => 'device', 'device' => $result['device_id'], 'tab' => 'slas', 'id' => $result['sla_id']]),
          'name'   => $result['sla_descr'],
          'colour' => $tab_colour,
          'icon'   => $config['icon']['sla'],
          'data'   => ['| ' . escape_html($device_name), $descr]
        ];
    }

    $search_results['slas'] = ['descr' => 'SLAs found', 'results' => $sla_search_results];
}

// EOF