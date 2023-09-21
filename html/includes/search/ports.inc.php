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

/// SEARCH PORTS
$where  = '`ifAlias` LIKE ? OR `port_label` LIKE ?';
$params = [$query_param, $query_param];
$by_mac = preg_match('/^[a-f0-9]{2}[a-f0-9\.\-:]{1,15}/i', $queryString);
if ($by_mac) { // Check if query string is like mac-address (maximal 17 chars)
    $where    .= ' OR `ifPhysAddress` LIKE ?';
    $params[] = '%' . str_replace(['.', '-', ':'], '', $queryString) . '%';
}
$results = dbFetchRows("SELECT * FROM `ports`
                        LEFT JOIN `devices` USING (`device_id`)
                        " . generate_where_clause("($where)", $GLOBALS['cache']['where']['ports_permitted']) . "
                        ORDER BY `ifDescr` LIMIT $query_limit", $params);

if (!safe_empty($results)) {
    $max_len = 35;
    foreach ($results as $result) {
        humanize_port($result);

        //FIXME - messy

        $name        = truncate($result['port_label'], $max_len);
        $device_name = truncate($result['hostname'], $max_len);
        if ($result['hostname'] != $result['sysName'] && $result['sysName']) {
            $device_name .= ' | ' . truncate($result['sysName'], $max_len);
        }

        $descr = !safe_empty($result['ifAlias']) ? truncate($result['ifAlias'], 80) : '';
        $type  = rewrite_iftype($result['ifType']);
        if (!safe_empty($result['ifPhysAddress'])) {
            $mac = ' | ' . html_highlight(format_mac($result['ifPhysAddress']), $queryString);
        } else {
            $mac = '';
        }

        $port_search_results[] = [
          'url'    => generate_port_url($result),
          'name'   => $name,
          'colour' => $result['table_tab_colour'],
          'icon'   => $config['icon']['port'],
          'data'   => [
            escape_html($device_name),
            $type . $mac . ' | ' . html_highlight(escape_html($descr), $queryString)],
        ];
    }

    $search_results['ports'] = ['descr' => 'Ports found', 'results' => $port_search_results];
}

// EOF
