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

/// SEARCH PORTS
$where   = '`ifAlias` LIKE ? OR `port_label` LIKE ?';
$params  = [ $query_param, $query_param ];
$by_mac = preg_match('/^[a-f0-9]{2}[a-f0-9\.\-:]{1,15}/i', $queryString);
if ($by_mac) // Check if query string is like mac-address (maximal 17 chars)
{
  $where .= ' OR `ifPhysAddress` LIKE ?';
  $params[] = '%' . str_replace([ '.', '-', ':' ], '', $queryString) . '%';
}
$results = dbFetchRows("SELECT * FROM `ports`
                        LEFT JOIN `devices` USING (`device_id`)
                        WHERE ($where) $query_permitted_port
                        ORDER BY `ifDescr` LIMIT $query_limit", $params);

if (safe_count($results)) {
  foreach ($results as $result) {
    humanize_port($result);

    //FIXME - messy

    $name = truncate($result['port_label'], 35);
    $description = strlen($result['ifAlias']) ? truncate($result['ifAlias'], 80) : '';
    $type = rewrite_iftype($result['ifType']);
    if (strlen($result['ifPhysAddress'])) {
      $mac = ' | ' . html_highlight(format_mac($result['ifPhysAddress']), $queryString);
    } else {
      $mac = '';
    }

    $port_search_results[] = array(
      'url'  => generate_port_url($result),
      'name' => $name,
      'colour' => $result['table_tab_colour'],
      'icon' => $config['icon']['port'],
      'data' => array(
        '' . escape_html($result['hostname']),
        $type . $mac . ' | ' . html_highlight(escape_html($description), $queryString)),
    );
  }

  $search_results['ports'] = array('descr' => 'Ports found', 'results' => $port_search_results);
 }

// EOF
