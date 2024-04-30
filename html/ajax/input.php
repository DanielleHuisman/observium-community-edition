<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// FIXME, create api-internal for such

include_once("../../includes/observium.inc.php");

include($config['html_dir'] . "/includes/authenticate.inc.php");

if (!$_SESSION['authenticated']) {
    echo('<li class="nav-header">Session expired, please log in again!</li>');
    exit;
}

$vars         = get_vars('GET');
$array_filter = in_array($vars['field'], ['syslog_program'], TRUE); // modules with cached field
if (!safe_empty($vars['field']) && $vars['cache'] !== 'no' && ($array_filter || safe_empty($vars['query']))) {
    $cache_key = 'options_' . $vars['field'];
    foreach ($vars as $param => $value) {
        if (in_array($param, ['field', 'query', 'cache'], TRUE)) {
            continue;
        }
        $cache_key .= "_$param=$value";
    }
} else {
    $cache_key = '';
}

$query = '';
if ($cache_key && $options = get_cache_session($cache_key)) {
    // Return cached data (if not set in vars cache = 'no')
    //header("Content-type: application/json; charset=utf-8");
    //echo safe_json_encode(array('options' => $_SESSION['cache'][$cache_key]));
    //$options = $_SESSION['cache'][$cache_key];
} else {
    $where  = [];
    $params = [];
    //print_vars($vars);
    switch ($vars['field']) {
        case 'ipv4_network':
        case 'ipv6_network':
            $ip_version        = explode('_', $vars['field'])[0];
            $query_permitted   = generate_query_permitted_ng('ports');
            $network_permitted = dbFetchColumn('SELECT DISTINCT(`' . $ip_version . '_network_id`) FROM `' . $ip_version . '_addresses` WHERE ' . $query_permitted);
            $where[]           = generate_query_values($network_permitted, $ip_version . '_network_id');
            if (!safe_empty($vars['query'])) {
                //$query .= ' AND `' . $ip_version . '_network` LIKE ?';
                //$params[] = '%' . $vars['query'] . '%';
                $where[] = generate_query_values($vars['query'], $vars['field'], '%LIKE%');
            }
            $query  = 'SELECT `' . $ip_version . '_network` FROM `' . $ip_version . '_networks` ';
            $query .= generate_where_clause($where);
            $query .= ' ORDER BY `' . $ip_version . '_network`;';
            //print_vars($query);
            break;

        case 'ifspeed':
            $query_permitted = generate_query_permitted('ports');
            $query           = 'SELECT `ifSpeed`, COUNT(`ifSpeed`) as `count` FROM `ports` WHERE `ifSpeed` > 0 ' .
                               $query_permitted . ' GROUP BY ifSpeed ORDER BY `count` DESC';
            $call_function   = 'formatRates';
            $call_params     = [4, 4];
            break;

        case 'syslog_program':
            //$query_permitted   = generate_query_permitted();
            $query = 'SELECT DISTINCT `program` FROM `syslog`';
            if (is_intnum($vars['device_id'])) {
                $query .= ' WHERE ' . generate_query_values($vars['device_id'], 'device_id');
            }
            $array_filter = TRUE; // Search query string in array instead sql query (when this faster)
            break;

        case 'bgp_peer_as':
            $column          = 'bgpPeerRemoteAs';
            $query_permitted = generate_query_permitted('devices');
            // Combine AS number and AS text into string: ASXXXX: My AS text
            $query    = 'SELECT DISTINCT CONCAT(?, CONCAT_WS(?, `' . $column . '`, `astext`)) AS `' . $vars['field'] . '` FROM `bgpPeers` WHERE 1 ' . $query_permitted;
            $params[] = 'AS';
            $params[] = ': ';
            if (!safe_empty($vars['query'])) {
                $query    .= ' AND (`' . $column . '` LIKE ? OR `astext` LIKE ?)';
                $params[] = '%' . $vars['query'] . '%';
                $params[] = '%' . $vars['query'] . '%';
                //$query .= generate_query_values_and($vars['query'], $vars['field'], '%LIKE%');
            }
            break;

        case 'bgp_local_ip':
        case 'bgp_peer_ip':
            $columns         = ['local_ip' => 'bgpPeerLocalAddr',
                                'peer_ip'  => 'bgpPeerRemoteAddr'];
            $param           = str_replace('bgp_', '', $vars['field']);
            $column          = $columns[$param];
            if (!safe_empty($vars['query'])) {
                $where[] = generate_query_values($vars['query'], $column, '%LIKE%');
            }
            $query           = 'SELECT DISTINCT `' . $column . '` FROM `bgpPeers`';
            $query          .= generate_where_clause($where, generate_query_permitted_ng('devices'));
            break;

        default:
            json_output('error', 'Search type unknown');
    }

    if (!safe_empty($query)) {
        $options = dbFetchColumn($query, $params);
        if (safe_count($options)) {
            if (isset($call_function)) {
                $call_options = [];
                foreach ($options as $option) {
                    $call_options[] = call_user_func_array($call_function, array_merge([$option], $call_params));
                }
                $options = $call_options;
            }

            // Cache request in session var (need convert to common caching lib)
            if ($cache_key) {
                set_cache_session($cache_key, $options);
                //@session_start();
                //$_SESSION['cache'][$cache_key] = $options; // Cache query data in session for speedup
                //session_write_close();
            }
        } else {
            json_output('error', 'Data fields are empty');
        }
    }
}

if (safe_count($options)) {
    // Filter/search query string in array, instead sql query, when this is faster (ie syslog program)
    if ($array_filter) {
        $new_options = [];
        foreach ($options as $option) {
            if (str_contains_array($option, $vars['query'])) {
                $new_options[] = $option;
            }
        }
        $options = $new_options;
    }

    header("Content-type: application/json; charset=utf-8");
    echo safe_json_encode(['options' => $options]);
} else {
    json_output('error', 'Data fields are empty');
}

// EOF
