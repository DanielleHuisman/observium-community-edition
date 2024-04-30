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

// Get options before definitions!
$options = getopt("h:i:m:n:p:dqrMV");

include("includes/observium.inc.php");
include("includes/polling/functions.inc.php");
include("includes/discovery/functions.inc.php");

$cli = TRUE;

$poller_start = utime();

// get_versions();

if (isset($options['V'])) {
    // Print current version and exit
    print_message(OBSERVIUM_PRODUCT . " " . OBSERVIUM_VERSION);
    if (is_array($options['V'])) {
        print_versions();
    }
    exit;
}

if (isset($options['M'])) {
    // List available modules and exit
    print_message(OBSERVIUM_PRODUCT . " " . OBSERVIUM_VERSION);

    print_message('Enabled poller modules:');
    $m_disabled = [];
    foreach ($config['poller_modules'] as $module => $ok) {
        if ($ok) {
            print_message('  ' . $module);
        } else {
            $m_disabled[] = $module;
        }
    }
    if (count($m_disabled)) {
        print_message('Disabled poller modules:');
        print_message('  ' . implode("\n  ", $m_disabled));
    }
    exit;
}

if (!isset($options['q']) && !is_cron()) {

    print_cli_banner();

    $latest['version']  = get_obs_attrib('latest_ver');
    $latest['revision'] = get_obs_attrib('latest_rev');
    $latest['date']     = get_obs_attrib('latest_rev_date');

    if ($latest['revision'] > OBSERVIUM_REV) {
        print_message("%GThere is a newer revision of Observium available!%n", 'color');
        print_message("%GVersion %r" . $latest['version'] . "%G (" . format_unixtime(datetime_to_unixtime($latest['date']), 'jS F Y') . ") is %r" . ($latest['revision'] - OBSERVIUM_REV) . "%G revisions ahead.%n\n", 'color');
    }

    // print_message("%g".OBSERVIUM_PRODUCT." ".OBSERVIUM_VERSION."\n%WPoller%n\n", 'color');
    if (OBS_DEBUG) {
        print_versions();
    }
}

$where = '';
$doing_single = FALSE; // Poll single device? mostly from poller wrapper

if ($options['h'] === "pollers") {
    // Distributed pollers only
    if (OBS_DISTRIBUTED) {
        poll_pollers_distributed();
    } elseif (OBSERVIUM_EDITION === 'community') {
        print_debug("Distribution pollers not available in CE.");
    } else {
        print_debug("Distribution pollers not exist on install.");
    }
    exit(); // Silent exit
}
if ($options['h'] === "odd") {
    $options['n'] = "1";
    $options['i'] = "2";
} elseif ($options['h'] === "even") {
    $options['n'] = "0";
    $options['i'] = "2";
} elseif ($options['h'] === "all") {
    $where = " ";
    $doing = "all";
} elseif ($options['h']) {
    $params = [];
    if (is_numeric($options['h'])) {
        $where        = "AND `device_id` = ?";
        $doing        = $options['h'];
        $params[]     = $options['h'];
        $doing_single = TRUE; // Poll single device! (from wrapper)
    } else {
        $where    = "AND `hostname` LIKE ?";
        $doing    = $options['h'];
        $params[] = str_replace('*', '%', $options['h']);
    }
}

if (isset($options['i'], $options['n']) && $options['i']) {
    $where = TRUE; // FIXME

    $query    = 'SELECT `device_id` FROM (SELECT @rownum :=0) r,
              (
                SELECT @rownum := @rownum +1 AS rownum, `device_id`
                FROM `devices`
                WHERE `disabled` = 0 AND `poller_id` = ' . $config['poller_id'] . '
                ORDER BY `device_id` ASC
              ) temp
            WHERE MOD(temp.rownum, ' . $options['i'] . ') = ?;';
    $doing    = $options['n'] . "/" . $options['i'];
    $params[] = $options['n'];
    //print_vars($query);
    //print_vars($params);
}

if (!$where) {
    print_message("%n
USAGE:
$scriptname [-drqV] [-i instances] [-n number] [-m module] [-h device]

EXAMPLE:
-h <device id> | <device hostname wildcard>  Poll single device
-h odd                                       Poll odd numbered devices  (same as -i 2 -n 0)
-h even                                      Poll even numbered devices (same as -i 2 -n 1)
-h all                                       Poll all devices
-h new                                       Poll all devices that have not had a discovery run before
-h pollers                                   Poll remote pollers (except devices)

-i <instances> -n <id/number>                Poll as instance <id/number> of <instances>
                                             Instance numbers start at 0. 0-3 for -i 4
                                             Example:
                                               -i 4 -n 0
                                               -i 4 -n 1
                                               -i 4 -n 2
                                               -i 4 -n 3

OPTIONS:
 -h                                          Device hostname, id or key odd/even/all/new.
 -i                                          Poll instances count.
 -n                                          Instance id (number), must start from 0 and to be less than instances count.
 -q                                          Quiet output.
 -M                                          Show globally enabled/disabled modules and exit.
 -V                                          Show observium version and exit.
 -VV                                         Show observium and used programs versions and exit.

DEBUGGING OPTIONS:
 -r                                          Do not create or update RRDs
 -d                                          Enable debugging output.
 -dd                                         More verbose debugging output.
 -m                                          Specify module(s) (separated by commas) to be run.

%rInvalid arguments!%n", 'color', FALSE);
    exit;
}

if (isset($options['r'])) {
    $config['norrd'] = TRUE;
}

$cache['maint'] = cache_alert_maintenance();

rrdtool_pipe_open($rrd_process, $rrd_pipes);

print_cli_heading("%WStarting polling run at " . date("Y-m-d H:i:s"), 0);
$polled_devices = 0;

if (!isset($query)) {
    $query    = "SELECT `device_id` FROM `devices` WHERE `disabled` = 0 $where AND `poller_id` = ? ORDER BY `device_id` ASC";
    $params[] = $config['poller_id'];
}

foreach (dbFetchColumn($query, $params) as $device_id) {
    $device = dbFetchRow("SELECT * FROM `devices` WHERE `device_id` = ?", [ $device_id ]);
    poll_device($device, $options);
    $polled_devices++;
}

$poller_time = elapsed_time($poller_start, 4);

if ($polled_devices) {
    if (is_numeric($doing)) {
        $doing = $device['hostname'];
    } // Single device ID convert to hostname for log
} else {
    print_warning("WARNING: 0 devices polled. Did you specify a device that does not exist?");
}

$string = OBS_SCRIPT_NAME . ": $doing - $polled_devices devices polled in $poller_time secs";
print_debug($string);

print_cli_heading("%WCompleted polling run at " . date("Y-m-d H:i:s"), 0);

// Total MySQL usage
$mysql_time  = 0;
$mysql_count = 0;
$mysql_times = [];
foreach ($db_stats as $cmd => $count) {
    if (isset($db_stats[$cmd . '_sec'])) {
        $mysql_times[] = ucfirst(str_replace("fetch", "", $cmd)) . "[" . $count . "/" . round($db_stats[$cmd . '_sec'], 3) . "s]";
        $mysql_time    += $db_stats[$cmd . '_sec'];
        $mysql_count   += $db_stats[$cmd];
    }
}
$db_stats['total']     = $mysql_count;
$db_stats['total_sec'] = $mysql_time;
print_debug_vars($db_stats);

// Store MySQL/Memory stats per device polling (only for single device poll)
if ($doing_single && !isset($options['m'])) {
    rrdtool_update_ng($device, 'perf-pollerdb', $db_stats);                                     // MySQL usage stats
    rrdtool_update_ng($device, 'perf-pollermemory', [ 'usage' => memory_get_usage(TRUE),        // Memory usage stats
                                                      'peak'  => memory_get_peak_usage(TRUE) ]);

    print_debug_vars($GLOBALS['snmp_stats']);
    $poller_snmp_stats  = ['total' => 0, 'total_sec' => 0];
    $poller_snmp_errors = ['total' => 0, 'total_sec' => 0];
    foreach ($GLOBALS['snmp_stats'] as $snmp_cmd => $entry) {
        if ($snmp_cmd === 'errors') {
            continue;
        }

        $poller_snmp_stats[$snmp_cmd]          = $entry['count']; // Count
        $poller_snmp_stats[$snmp_cmd . '_sec'] = $entry['time'];  // Runtime
        $poller_snmp_stats['total']            += $entry['count'];
        $poller_snmp_stats['total_sec']        += $entry['time'];
    }
    foreach ($GLOBALS['snmp_stats']['errors'] as $snmp_cmd => $entry) {
        $poller_snmp_errors[$snmp_cmd]          = $entry['count']; // Count
        $poller_snmp_errors[$snmp_cmd . '_sec'] = $entry['time'];  // Runtime
        $poller_snmp_errors['total']            += $entry['count'];
        $poller_snmp_errors['total_sec']        += $entry['time'];
    }
    rrdtool_update_ng($device, 'perf-pollersnmp', $poller_snmp_stats);                              // SNMP walk stats
    rrdtool_update_ng($device, 'perf-pollersnmp_errors', $poller_snmp_errors);                      // SNMP error stats
    // FIXME. RRDTool usage
}

if (!isset($options['q'])) {
    if ($config['snmp']['hide_auth']) {
        print_debug("NOTE, \$config['snmp']['hide_auth'] is set to TRUE, snmp community and snmp v3 auth hidden from debug output.");
    }

    print_cli_data('Devices Polled', $polled_devices, 0);
    print_cli_data('Poller Time', $poller_time . " secs", 0);
    print_cli_data('Definitions', $defs_time . " secs", 0);
    print_cli_data('Memory usage', format_bytes(memory_get_usage(TRUE), 2, 4) . ' (peak: ' . format_bytes(memory_get_peak_usage(TRUE), 2, 4) . ')', 0);
    print_cli_data('MySQL Usage', implode(" ", $mysql_times) . ' (' . round($mysql_time, 3) . 's ' . percent($mysql_time, $poller_time, 3) . '%)', 0);

    $rrd_time  = 0;
    $rrd_times = [];
    foreach ($GLOBALS['rrdtool'] as $cmd => $data) {
        $rrd_times[] = $cmd . "[" . $data['count'] . "/" . round($data['time'], 3) . "s]";
        $rrd_time    += $data['time'];
    }

    print_cli_data('RRDTool Usage', implode(" ", $rrd_times) . ' (' . round($rrd_time, 3) . 's ' . percent($rrd_time, $poller_time, 3) . '%)', 0);

    $snmp_time  = 0;
    $snmp_times = [];
    foreach ($GLOBALS['snmp_stats'] as $cmd => $data) {
        $snmp_times[] = $cmd . "[" . $data['count'] . "/" . round($data['time'], 3) . "s]";
        $snmp_time    += $data['time'];
    }

    print_cli_data('SNMP Usage', implode(" ", $snmp_times) . ' (' . round($snmp_time, 3) . 's ' . percent($snmp_time, $poller_time, 3) . '%)', 0);

    if ($GLOBALS['influxdb_stats']) {
        $s = $GLOBALS['influxdb_stats'];
        $t = $s['time'];
        print_cli_data('InfluxDB Usage', $s['count'] . ' data points (' . round($t, 3) . 's ' . percent($t, $poller_time, 3) . '%)', 0);
    }

}

logfile($string);
rrdtool_pipe_close($rrd_process, $rrd_pipes);
unset($config); // Remove this for testing

#print_vars(get_defined_vars());

echo("\n");

print_debug_vars($snmp_stats);

// EOF
