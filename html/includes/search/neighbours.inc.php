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

/// SEARCH NEIGHBOURS
$sql = "SELECT * FROM `neighbours` WHERE `active` = ? AND (`remote_hostname` LIKE ? OR `remote_address` LIKE ? OR `remote_platform` LIKE ?)" .
       " $query_permitted_device ORDER BY `last_change` DESC LIMIT $query_limit";
$params = [ 1, $query_param, $query_param, $query_param ];
$results = dbFetchRows($sql, $params);

if (safe_count($results)) {

  $protocol_classmap = [
    'cdp'  => 'success',
    'lldp' => 'warning',
    'amap' => 'primary',
    'mndp' => 'error',
    'fdp'  => 'delayed',
    'edp'  => 'suppressed'
  ];

  foreach ($results as $result) {
    $result_device = device_by_id_cache($result['device_id']);
    $result_port = get_port_by_id_cache($result['port_id']);

    $name = $result_device['hostname'];
    if (strlen($name) > 35) { $name = substr($name, 0, 35) . "..."; }

    //$num_ports = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE device_id = ?", array($result['device_id']));

    $remote_host = strlen($result['remote_hostname']) > 35 ? substr($result['remote_hostname'], 0, 35) . "..." : $result['remote_hostname'];
    $remote_host .= ' | ';
    if ($result['remote_address'] && $result['remote_address'] !== '0.0.0.0') {
      $remote_host .= $result['remote_address'] . ' | ';
    }
    if (strlen($result['remote_port'])) {
      $remote_host .= $result['remote_port'] . ' | ';
    }

    $remote_descr = $result['remote_platform'] . ' | ';
    if ($len = strlen($result['remote_version'])) {
      if ($len > 35) { $result['remote_version'] = substr($result['remote_version'], 0, 35) . "..."; }
      $remote_descr .= $result['remote_version'] . ' | ';
    }

    $protocol_class = isset($protocol_classmap[$result['protocol']]) ? 'label-'.$protocol_classmap[$result['protocol']] : '';

    $neighbours_search_results[] = array(
      'url'    => generate_device_url($result_device, [ 'tab' => 'ports', 'view' => 'neighbours' ]),
      'name'   => $name,
      'colour' => $result_device['html_tab_colour'], // FIXME. this colour removed from humanize_device in r6280
      'row_class' => $result_device['row_class'],
      'html_row_class' => $result_device['html_row_class'],
      'icon'   => get_device_icon($result_device),
      'data'   => [
        escape_html($result_port['port_label']) . ' <span class="label '.$protocol_class.'">' . strtoupper($result['protocol']) . '</span>',
        html_highlight(escape_html($remote_host), $queryString),
        '<span class="text-wrap">'.html_highlight(escape_html($remote_descr), $queryString) . '</span>',
      ],
    );
  }
  
  $search_results['neighbours'] = array('descr' => 'Neighbours found', 'results' => $neighbours_search_results);
}

// EOF
