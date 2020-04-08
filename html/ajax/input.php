<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// FIXME, create api-internal for such

$config['install_dir'] = "../..";

include_once("../../includes/sql-config.inc.php");

include($config['html_dir'] . "/includes/functions.inc.php");
include($config['html_dir'] . "/includes/authenticate.inc.php");

if (!$_SESSION['authenticated']) { echo('<li class="nav-header">Session expired, please log in again!</li>'); exit; }

$vars = get_vars('GET');

if (strlen($vars['field']) && $vars['cache'] != 'no' && empty($vars['query']) &&
    isset($_SESSION['cache']['options_' . $vars['field']]))
{
  // Return cached data (if not set in vars cache = 'no')
  header("Content-type: application/json; charset=utf-8");
  echo json_encode(array('options' => $_SESSION['cache']['options_' . $vars['field']]));
} else {
  $query  = '';
  $params = array();
  $array_filter = FALSE;
  //print_vars($vars);
  switch ($vars['field'])
  {
    case 'ipv4_network':
    case 'ipv6_network':
      list($ip_version)  = explode('_', $vars['field']);
      $query_permitted   = generate_query_permitted('ports');
      $network_permitted = dbFetchColumn('SELECT DISTINCT(`' . $ip_version . '_network_id`) FROM `' . $ip_version . '_addresses` WHERE 1' . $query_permitted);
      $query = 'SELECT `' . $ip_version . '_network` FROM `' . $ip_version . '_networks` WHERE 1 ' . generate_query_values($network_permitted, $ip_version . '_network_id');
      if (strlen($vars['query']))
      {
        //$query .= ' AND `' . $ip_version . '_network` LIKE ?';
        //$params[] = '%' . $vars['query'] . '%';
        $query .= generate_query_values($vars['query'], $vars['field'], '%LIKE%');
      }
      $query .= ' ORDER BY `' . $ip_version . '_network`;';
      //print_vars($query);
      break;

    case 'ifspeed':
      $query_permitted   = generate_query_permitted('ports');
      $query = 'SELECT `ifSpeed`, COUNT(ifSpeed) as `count` FROM `ports` WHERE `ifSpeed` > 0 '. $query_permitted .' GROUP BY ifSpeed ORDER BY `count` DESC';
      $call_function = 'formatRates';
      $call_params   = array(4, 4);
      break;

    case 'syslog_program':
      //$query_permitted   = generate_query_permitted();
      $query = 'SELECT DISTINCT `program` FROM `syslog`';
      $array_filter = TRUE; // Search query string in array instead sql query (when this faster)
      break;

    case 'bgp_peer_as':
      $column = 'bgpPeerRemoteAs';
      $query_permitted = generate_query_permitted('devices');
      // Combinate AS number and AS text into string: ASXXXX: My AS text
      $query = 'SELECT DISTINCT CONCAT(?, CONCAT_WS(?, `'.$column.'`, `astext`)) AS `'.$vars['field'].'` FROM `bgpPeers` WHERE 1 ' . $query_permitted;
      $params[] = 'AS';
      $params[] = ': ';
      //$query = 'SELECT DISTINCT `' . $column . '`, `astext` FROM `bgpPeers` WHERE 1 ' . $cache['where']['devices_permitted'] . ' ORDER BY `' . $column . '`';
      if (strlen($vars['query']))
      {
        $query .= ' AND (`' . $column . '` LIKE ? OR `astext` LIKE ?)';
        $params[] = '%' . $vars['query'] . '%';
        $params[] = '%' . $vars['query'] . '%';
        //$query .= generate_query_values($vars['query'], $vars['field'], '%LIKE%');
      }
      break;

    case 'bgp_local_ip':
    case 'bgp_peer_ip':
      $columns = array('local_ip' => 'bgpPeerLocalAddr',
                       'peer_ip'  => 'bgpPeerRemoteAddr',
                 );
      $param  = str_replace('bgp_', '', $vars['field']);
      $column = $columns[$param];
      $query_permitted = generate_query_permitted('devices');
      $query = 'SELECT DISTINCT `' . $column . '` FROM `bgpPeers` WHERE 1 ' . $query_permitted;
      if (strlen($vars['query']))
      {
        $query .= generate_query_values($vars['query'], $column, '%LIKE%');
      }
      break;

    default:
      json_output('error', 'Search type unknown');
  }

  if (strlen($query))
  {
    $options = dbFetchColumn($query, $params);
    if (count($options))
    {
      if (isset($call_function))
      {
        $call_options = array();
        foreach ($options as $option)
        {
          $call_options[] = call_user_func_array($call_function, array_merge(array($option), $call_params));
        }
        $options = $call_options;
      }

      // Filter/search query string in array, instead sql query, when this is faster (ie syslog program)
      if ($array_filter)
      {
        $new_options = [];
        foreach ($options as $option)
        {
          if (stripos($option, $vars['query']) !== FALSE) { $new_options[] = $option; }
        }
        $options = $new_options;
      }

      // Cache request in session var (need convert to common caching lib)
      if ($vars['cache'] != 'no' && empty($vars['query']))
      {
        @session_start();
        $_SESSION['cache']['options_' . $vars['field']] = $options; // Cache query data in session for speedup
        session_commit();
      }

      header("Content-type: application/json; charset=utf-8");
      echo json_encode(array('options' => $options));
    } else {
      json_output('error', 'Data fields are empty');
    }
  }
}

// EOF
