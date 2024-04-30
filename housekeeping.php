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

$options = getopt("A:VyaselurpdbiT");

include("includes/observium.inc.php");

$cli = is_cli();

if (isset($options['V'])) {
    print_message(OBSERVIUM_PRODUCT . " " . OBSERVIUM_VERSION);
    if (is_array($options['V'])) {
        print_versions();
    }
    exit;
}

// Prevent running housekeeping on remote pollers (not needed, won't work properly, potential data loss vector)
if (!isset($options['f']) && $config['poller_id'] !== 0) {
    print_message("%yHousekeeping only needs to be run on the main node. Do not run housekeeping on partitioned pollers.%n\n", 'color');
    exit;
}

print_message("%g" . OBSERVIUM_PRODUCT . " " . OBSERVIUM_VERSION . "\n%WHouseKeeping%n\n", 'color');
if (OBS_DEBUG) {
    print_versions();
}

// For interactive prompt/answer checks
// if it is started from crontab - prompt disabled and answer always 'yes'
if (is_cron()) {
    $prompt = FALSE;
} else {
    $prompt = !isset($options['y']);
}
$answer = TRUE;

$modules = [];

if (isset($options['a']) || isset($options['s'])) {
    $modules[] = 'syslog';
}
if (isset($options['a']) || isset($options['e'])) {
    $modules[] = 'eventlog';
}
if (isset($options['a']) || isset($options['l'])) {
    $modules[] = 'alertlog';
}
if (isset($options['a']) || isset($options['u'])) {
    $modules[] = 'authlog';
}
if (isset($options['a']) || isset($options['p'])) {
    $modules[] = 'ports';
}
if (isset($options['a']) || isset($options['b'])) {
    $modules[] = 'staledb';
}
if (isset($options['a']) || isset($options['i'])) {
    $modules[] = 'inventory';
}
if (isset($options['a']) || isset($options['r'])) {
    $modules[] = 'rrd';
}

// Get age from command line
if (isset($options['A'])) {
    $age = age_to_seconds($options['A']);
    if ($age) {
        foreach ($modules as $module) {
            if ($module === 'ports') {
                $module = 'deleted_ports';
            }
            $config['housekeeping'][$module]['age'] = $age;
        }
    } else {
        print_debug("Invalid age specified '" . $options['A'] . "', skipped.");
    }
    unset($age, $module);
}

if (!count($modules)) {
    print_message("%n
USAGE:
$scriptname [-Vyaselrptdbu] [-A <age>]

NOTE, by default $scriptname asks 'Are you sure want to delete (y/N)?'.
      To assume 'yes' as answer to all prompts and run non-interactively,
      add '-y' in command line.
      Not necessary when run from cron (determined automatically).

OPTIONS:
 -V                                          Show version and exit.
 -y                                          Automatically answer 'yes' to prompts
 -a                                          Maintain all modules as specified below.
 -s                                          Clean up syslog
 -e                                          Clean up event log
 -l                                          Clean up alert log
 -u                                          Clean up auth log
 -i                                          Clean up inventory
 -r                                          Clean up unused RRD files
 -p                                          Clean up deleted ports
 -b                                          Clean up stale database entries
 -A <age>                                    Specifies maximum age for all modules (overrides configuration)
 -f                                          Force run Housekeeping on Poller ID other than 0 (main)

DEBUGGING OPTIONS:
 -T                                          Testing, not do any actions, only show counts.
 -d                                          Enable debugging output.
 -dd                                         More verbose debugging output.

EXAMPLES:
  $scriptname -a                        Clean up by all modules interactively (with prompts!)
  $scriptname -ya                       Clean up by all modules without prompts

%rInvalid arguments!%n", 'color', FALSE);
    exit;
}

$test = isset($options['T']);
foreach ($modules as $module) {
    if (is_file($config['install_dir'] . "/includes/housekeeping/$module.inc.php")) {
        include($config['install_dir'] . "/includes/housekeeping/$module.inc.php");
        if (!$test) {
            set_obs_attrib("housekeeping_lastrun_$module", time());
        }
    } else {
        print_warning("Housekeeping module not found: $module");
    }
}
if (!$test) {
    set_obs_attrib("housekeeping_lastrun", time());
}

// EOF
