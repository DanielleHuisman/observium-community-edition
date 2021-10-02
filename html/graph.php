<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

#ob_start(); // FIXME why no more?

// Define this is graph
define('OBS_GRAPH', TRUE);

include_once("../includes/sql-config.inc.php");

$start = utime(); // Needs common.php

include($config['html_dir'] . "/includes/functions.inc.php");

if (isset($config['allow_unauth_graphs']) && $config['allow_unauth_graphs']) {
  $auth = TRUE; // hardcode auth for all with config function
  print_debug('Authentication bypassed by $config[\'allow_unauth_graphs\'].');
} elseif (isset($config['allow_unauth_graphs_cidr']) && count($config['allow_unauth_graphs_cidr'])) {
  //if (match_network($_SERVER['REMOTE_ADDR'], $config['allow_unauth_graphs_cidr']))
  if (match_network(get_remote_addr($config['web_session_ip_by_header']), $config['allow_unauth_graphs_cidr'])) {
    $auth = TRUE; // hardcode authenticated for matched subnet
    print_debug("Authentication by matched CIDR.");
  }
}

if (!isset($auth) || !$auth) {
  // Normal auth
  include($config['html_dir'] . "/includes/authenticate.inc.php");
  $auth = $_SESSION['authenticated'];
}

// Push $_GET into $vars to be compatible with web interface naming
//r($_SESSION); exit;
$vars = get_vars('GET', $auth);

include($config['html_dir'] . "/includes/graphs/graph.inc.php");

$runtime = utime() - $start;

print_debug("Runtime ".$runtime." secs");

// EOF
