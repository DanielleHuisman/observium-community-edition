#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     cli
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

chdir(dirname($argv[0]));

$options = getopt("h:qdV");

// FIXME. Config & definitions cache for 5-10 min?
include("includes/observium.inc.php");

$start = utime();

if (isset($options['V'])) {
    print_message(OBSERVIUM_PRODUCT . " " . OBSERVIUM_VERSION);
    exit;
}
if ($config['poller-wrapper']['notifications']) {
    print_warning("Notifications set to send inside alerter wrapper.
  Disable it first in config.php: \$config['poller-wrapper']['notifications'] = FALSE;");
    exit;
}

if (!isset($options['q'])) {
    print_cli_banner();
}

$help   = FALSE;
$params = [];

if ($options['h'] && is_numeric($options['h'])) {
    $params['device_id'] = $options['h'];
} elseif ($options['h'] !== "all") {
    $help = TRUE;
}

/* Notifications not poller locked
if (isset($options['p'])) {
  print_cli_heading("%WConstrained to poller partition id ".$options['p']);
  $params['poller_id'] = $options['p'];
}
*/

if (!$help) {
    print_message("%n
USAGE:
$scriptname [-drqV] [-p poller_id] [-h device]

EXAMPLE:
-h <device id> | <device hostname wildcard>  Poll single device
-h all                                       Poll all devices

-p <poller_id>                               Poll for specific poller_id

OPTIONS:
 -h                                          Device hostname, id or hostname or keys all.
 -q                                          Quiet output.
 -V                                          Show version and exit.

DEBUGGING OPTIONS:
 -d                                          Enable debugging output.
 -dd                                         More verbose debugging output.

%rInvalid arguments!%n", 'color');
    exit;
}

print_cli_heading("%WStarting notifications run at " . date("Y-m-d H:i:s"), 0);

if ($res = process_notifications($params)) {
    $runtime = elapsed_time($start);
    // Send all notifications (also for syslog from queue)
    logfile('observium.log', count($res) . " notifications processed in " . substr($runtime, 0, 5) . "s.");
} else {
    // For debug:
    //logfile('observium.log', "No new notifications.");
}

print_cli_heading("%WFinished notifications run at " . date("Y-m-d H:i:s"), 0);

// EOF
