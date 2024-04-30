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

/// SEARCH IP ADDRESSES

[$addr, $mask] = explode('/', $queryString);

if (preg_match('/^(?:(?<both>\d+)|(?<ipv6>[\d\:abcdef]+)|(?<ipv4>[\d\.]+))$/i', $addr, $matches)) {
    $query_ipv4 = 'SELECT `device_id`, `port_id`, `ipv4_address` AS `ip_address`, `ipv4_prefixlen` AS `ip_prefixlen` FROM `ipv4_addresses`';
    $query_ipv4 .= generate_where_clause($GLOBALS['cache']['where']['devices_permitted'], '`ipv4_address` LIKE ?');

    $query_ipv6 = 'SELECT `device_id`, `port_id`, `ipv6_compressed` AS `ip_address`, `ipv6_prefixlen` AS `ip_prefixlen` FROM `ipv6_addresses`';
    $query_ipv6 .= generate_where_clause($GLOBALS['cache']['where']['devices_permitted'], '(`ipv6_address` LIKE ? OR `ipv6_compressed` LIKE ?)');

    $query_end   = " ORDER BY `ip_address` LIMIT $query_limit";
    $query_param = "%$addr%";
    if (isset($matches['ipv4'])) {
        // IPv4 only
        $results = dbFetchRows($query_ipv4 . $query_end, [$query_param]);
    } elseif (isset($matches['ipv6'])) {
        // IPv6 only
        $results = dbFetchRows($query_ipv6 . $query_end, [$query_param, $query_param]);
    } else {
        // Both
        $results_ipv4 = dbFetchRows($query_ipv4 . $query_end, [$query_param]);
        $results_ipv6 = dbFetchRows($query_ipv6 . $query_end, [$query_param, $query_param]);
        $count_ipv6   = safe_count($results_ipv6);
        if ((safe_count($results_ipv4) + $count_ipv6) > $query_limit) {
            // Ya.. it's not simple
            $count_ipv4   = $query_limit - min($count_ipv6, (int)($query_limit / 2));
            $results_ipv4 = array_slice($results_ipv4, 0, $count_ipv4);
            $results_ipv6 = array_slice($results_ipv6, 0, $query_limit - $count_ipv4);
        }
        $results = array_merge($results_ipv4, $results_ipv6);
    }

} else {
    $results = [];
}

if (!safe_empty($results)) {
    $max_len = 35;
    foreach ($results as $result) {
        $port   = get_port_by_id_cache($result['port_id']);
        $device = device_by_id_cache($result['device_id']);

        $descr = strlen($device['location']) ? $device['location'] . ' | ' : '';
        $descr .= $port['port_label'];

        $name = $result['ip_address'] . '/' . $result['ip_prefixlen'];
        if (strlen($name) > 35) {
            $name = substr($name, 0, 35) . "...";
        }

        $device_name = truncate($device['hostname'], $max_len);
        if ($device['hostname'] != $device['sysName'] && $device['sysName']) {
            $device_name .= ' | ' . truncate($device['sysName'], $max_len);
        }

        $tab_colour = '#194B7F'; // FIXME: This colour pulled from functions.inc.php humanize_device, maybe set it centrally in definitions?

        $view                = str_contains($result['ip_address'], '.') ? 'ipv4' : 'ipv6';
        $ip_search_results[] = [
          'url'    => $port ? generate_port_url($port) : generate_device_url($device, ['tab' => 'ports', 'view' => $view]),
          'name'   => $name,
          'colour' => $tab_colour,
          'icon'   => $config['icon'][$view],
          'data'   => [
            '| ' . escape_html($device_name),
            escape_html($descr)],
        ];

    }

    // FIXME after array-ization, we're missing "on x ports"; is this important? need to amend the "framework" a little, then.
    // Counter data came from: foreach ($results as $result) {$addr_ports[$result['port_id']][] = $result; }
    // echo('<li class="nav-header">IPs found: '.count($results).' (on '.count($addr_ports).' ports)</li>');

    $search_results['ip-addresses'] = ['descr' => 'IPs found', 'results' => $ip_search_results];
}

// EOF
