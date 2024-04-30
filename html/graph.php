<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

#ob_start(); // FIXME why no more?

// Define this is graph
define('OBS_GRAPH', TRUE);

$gstart = microtime(TRUE); // Needs common.php

include_once("../includes/observium.inc.php");

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
} elseif (!isset($_SESSION['userlevel']) && $auth) {
    $_SESSION['userlevel'] = 7; // Set global read for session when $auth hardcoded
}

// Push $_GET into $vars to be compatible with web interface naming
//r($_SESSION); exit;
$vars = get_vars('GET', $auth);

include($config['html_dir'] . "/includes/graphs/graph.inc.php");

$runtime = elapsed_time($gstart);

print_debug("Runtime " . $runtime . " secs");

// EOF
