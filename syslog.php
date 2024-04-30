#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage cli
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

chdir(dirname($argv[0]));

//$options['d'] = array(TRUE, TRUE);
include_once("includes/observium.inc.php");
include_once("html/includes/functions.inc.php");

// Disable sql profiling, this is a background process without any way to display it
$config['profile_sql'] = FALSE;

$rules        = cache_syslog_rules();
$device_rules = cache_syslog_rules_assoc();
$maint        = cache_alert_maintenance();
$config_unixtime = get_time();

$_SESSION['userlevel'] = 10; // Hardcode this to max to ensure links and the like are created

if (isset($config['syslog']['fifo']) && $config['syslog']['fifo'] !== FALSE) {
    // FIFO configured, try to grab logs from it
    #echo 'Opening FIFO: '.$config['syslog']['fifo'].PHP_EOL; //No any echo to STDOUT/STDERR!
    $s = fopen($config['syslog']['fifo'], 'r');
} else {
    // No FIFO configured, take logs from stdin
    #echo 'Opening STDIN'.PHP_EOL;                            //No any echo to STDOUT/STDERR!
    $s = fopen('php://stdin', 'r');
}

while ($line = fgets($s)) {
    /* Duplicate debugging
    if (isset($config['syslog']['debug']) && $config['syslog']['debug'])
    {
      // Store RAW syslog line into debug.log
      logfile('debug.log', $line);
    }
    */

    // Update syslog ruleset if they've changed. (this query should be cheap).
    $new_rules = get_obs_attrib('syslog_rules_changed');
    // Also detect if MySQL server has gone away
    if (empty($new_rules)) {
        if (function_exists('dbPing') && dbErrorNo() === 2006 && dbPing() === FALSE) {
            // MySQL server has gone away
            print_error('MySQL server has gone away. Need restart for script syslog.php');
            exit(1);
        }
    } elseif ($new_rules > $cur_rules) {
        $cur_rules    = $new_rules;
        $rules        = cache_syslog_rules();
        $device_rules = cache_syslog_rules_assoc();
        $maint        = cache_alert_maintenance();

        if ($config['syslog']['debug']) {
            logfile('debug.log', "Rules updated: " . $new_rules);
        }
    }


    // host || facility || priority || level || tag || timestamp || msg || program
    process_syslog($line, 1);
    unset($line);

    // Check if syslog config changed
    $config_changed = get_obs_attrib('syslog_config_changed');
    // if ($config['syslog']['debug']) {
    //     logfile('debug.log', "Syslog config time: " . $config_changed);
    // }

    if (!empty($config_changed) && ($config_changed > $config_unixtime)) {
        $config_unixtime = $config_changed;

        // if ($config['syslog']['debug']) {
        //     //logfile('debug.log', "Syslog config changed: " . $config_changed);
        //     logfile('debug_syslog.log', var_export($config['syslog']['filter'], TRUE));
        // }

        // Reload config changed
        load_sqlconfig($config);

        if ($config['syslog']['debug']) {
            logfile('debug.log', "Syslog config changed: " . $config_changed);
            //logfile('debug_syslog.log', var_export($config['syslog']['filter'], TRUE));
        }
        if (!$config['enable_syslog']) {
            // Disabled during update
            exit(0);
        }
    }
}

// EOF
