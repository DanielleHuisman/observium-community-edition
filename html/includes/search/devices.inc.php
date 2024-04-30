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

/**
 * @var array  $config
 * @var string $query_limit
 * @var string $query_param
 * @var string $queryString
 */

/// SEARCH DEVICES
$where  = '(`hostname` LIKE ? OR `sysName` LIKE ? OR `ip` LIKE ? OR `location` LIKE ? OR `sysDescr` LIKE ? OR `os` LIKE ? OR `vendor` LIKE ? OR `purpose` LIKE ? OR `asset_tag` LIKE ?)';
$params = [$query_param, $query_param, $query_param, $query_param, $query_param, $query_param, $query_param, $query_param, $query_param];

$sql = "SELECT * FROM `devices`" .
       generate_where_clause($GLOBALS['cache']['where']['devices_permitted'], $where) . "
        ORDER BY `hostname` LIMIT $query_limit";

$results = dbFetchRows($sql, $params);

if (safe_count($results)) {
    $max_len = 35;
    foreach ($results as $result) {
        humanize_device($result);

        $name = truncate($result['hostname'], $max_len);
        if ($_SESSION['userlevel'] >= 5 && !safe_empty($result['ip'])) {
            $name .= ' (' . $result['ip'] . ')';
        }

        $num_ports = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE `device_id` = ?", [$result['device_id']]);

        $descr = '';
        if ($result['hostname'] != $result['sysName'] && $result['sysName']) {
            $descr .= truncate($result['sysName'], $max_len);
            $descr .= ' | ';
        }
        if ($result['location']) {
            $descr .= $result['location'] . ' | ';
        }
        if (strlen($result['purpose'])) {
            $descr .= $result['purpose'] . ' | ';
        }

        if(strlen($result['asset_tag'])) {
            $descr .= $result['asset_tag'] . ' | ';
        }

        $device_search_results[] = [
          'url'            => generate_device_url($result),
          'name'           => $name,
          'colour'         => $result['html_tab_colour'], // FIXME. this colour removed from humanize_device in r6280
          'row_class'      => $result['row_class'],
          'html_row_class' => $result['html_row_class'],
          'icon'           => get_device_icon($result),
          'data'           => [
            escape_html($result['hardware'] . ' | ' . $config['os'][$result['os']]['text'] . ' ' . $result['version']),
            html_highlight(escape_html($descr), $queryString) . $num_ports . ' ports'],
        ];
    }

    $search_results['devices'] = ['descr' => 'Devices found', 'results' => $device_search_results];
}

// EOF
