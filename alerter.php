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

$options = getopt("h:p:dqrsV");

include("includes/observium.inc.php");

include("includes/polling/functions.inc.php");
include("html/includes/functions.inc.php");

$start = utime();

if (isset($options['V'])) {
    print_message(OBSERVIUM_PRODUCT . " " . OBSERVIUM_VERSION);
    exit;
}

if (isset($options['s'])) {
    // User has asked for spam. LETS MAKE THE SPAM. (sends alerts even if they have already been sent)
    $spam = TRUE;
}

if (!isset($options['q'])) {
    print_cli_banner();
}

if ($options['h'] === "all") {
    $where = " ";
    $doing = "all";
} elseif ($options['h']) {
    $params = [];
    if (is_numeric($options['h'])) {
        $where    = "AND `device_id` = ?";
        $doing    = $options['h'];
        $params[] = $options['h'];
    } else {
        $where    = "AND `hostname` LIKE ?";
        $doing    = $options['h'];
        $params[] = str_replace('*', '%', $options['h']);
    }
}

if (isset($options['p'])) {
    print_cli_heading("%WConstrained to poller partition id " . $options['p']);
    $where    .= ' AND `poller_id` = ?';
    $params[] = $options['p'];
}

if (!$where) {
    print_message("%n
USAGE:
$scriptname [-drqV] [-p poller_id] [-h device]

EXAMPLE:
-h <device id> | <device hostname wildcard>  Poll single device
-h all                                       Poll all devices

-p <poller_id>                               Poll for specific poller_id

OPTIONS:
 -h                                          Device hostname, id or hostname or keys all.
 -p                                          Poller ID.
 -s                                          Sends alerts even if they have already been sent.
 -q                                          Quiet output.
 -V                                          Show version and exit.

DEBUGGING OPTIONS:
 -r                                          Do not create or update RRDs
 -d                                          Enable debugging output.
 -dd                                         More verbose debugging output.

%rInvalid arguments!%n", 'color');
    exit;
}

print_cli_heading("%WStarting alerter run at " . date("Y-m-d H:i:s"), 0);

$polled_devices = 0;

$alert_rules = cache_alert_rules();
$alert_assoc = cache_alert_assoc();

// Allow the URL building code to build URLs with proper links.
$_SESSION['userlevel'] = 10;

// FIXME. Not sure, should notifications will send on every node or on main node only?
//$where .= ' AND `poller_id` = ?';
//$params[] = $config['poller_id'];

$query = "SELECT * FROM `devices` WHERE `disabled` = 0 $where ORDER BY `device_id` ASC";
foreach (dbFetchRows($query, $params) as $device) {

    humanize_device($device);

    process_alerts($device);
    if ($config['poller-wrapper']['notifications'] || $spam) {
        process_notifications(['device_id' => $device['device_id']]); // Send all notifications (also for syslog from queue)
    }

    dbUpdate(['last_alerter' => ['NOW()']], 'devices', '`device_id` = ?', [$device['device_id']]);

}

print_cli_heading("%WFinished alerter run at " . date("Y-m-d H:i:s"), 0);

// EOF
