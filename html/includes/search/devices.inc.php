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

/**
 * @var array  $config
 * @var string $query_permitted_device
 * @var string $query_limit
 * @var string $query_param
 * @var string $queryString
 */

/// SEARCH DEVICES
$where = '(`hostname` LIKE ? OR `sysName` LIKE ? OR `ip` LIKE ? OR `location` LIKE ? OR `sysDescr` LIKE ? OR `os` LIKE ? OR `vendor` LIKE ? OR `purpose` LIKE ?)';
$params = [ $query_param, $query_param, $query_param, $query_param, $query_param, $query_param, $query_param, $query_param ];
$results = dbFetchRows("SELECT * FROM `devices`
                        WHERE $where $query_permitted_device
                        ORDER BY `hostname` LIMIT $query_limit", $params);
if (safe_count($results)) {
  foreach ($results as $result) {
    humanize_device($result);

    $name = $result['hostname'];
    $max_len = 35;
    if (strlen($name) > 35) {
      $name = substr($name, 0, 35) . "...";
    }
    if ($_SESSION['userlevel'] >= 5 && !safe_empty($result['ip'])) {
      $name .= ' ('.$result['ip'].')';
    }

    $num_ports = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE device_id = ?", array($result['device_id']));

    $descr = '';
    if ($result['hostname'] != $result['sysName'] && $result['sysName']) {
      $descr .= strlen($result['sysName']) > 35 ? substr($result['sysName'], 0, 35) . "..." : $result['sysName'];
      $descr .= ' | ';
    }
    if ($result['location']) {
      $descr .= $result['location'] . ' | ';
    }
    if (strlen($result['purpose'])) {
      $descr .= $result['purpose'] . ' | ';
    }

    $device_search_results[] = array(
      'url'    => generate_device_url($result),
      'name'   => $name,
      'colour' => $result['html_tab_colour'], // FIXME. this colour removed from humanize_device in r6280
      'row_class' => $result['row_class'],
      'html_row_class' => $result['html_row_class'],
      'icon'   => get_device_icon($result),
      'data'   => array(
        escape_html($result['hardware'] . ' | ' . $config['os'][$result['os']]['text'] . ' ' . $result['version']),
        html_highlight(escape_html($descr), $queryString) . $num_ports . ' ports'),
    );
  }
  
  $search_results['devices'] = array('descr' => 'Devices found', 'results' => $device_search_results);
}

// EOF
