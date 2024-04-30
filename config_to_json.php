<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Prints the entire $config array as a JSON block. Probably needs to be cut-down.

chdir(dirname($argv[0]));

// Get options before definitions!
$options = getopt("o:dt");
$cload_time = microtime(TRUE);

if (isset($options['o'])) {
    // Skip load full definitions, while not required on initial config
    define('OBS_DEFINITIONS_SKIP', TRUE);
}
require_once("includes/observium.inc.php");

if (!is_cli()) {
    return;
}

if (isset($options['t'])) {
    print_cli(OBS_PROCESS_NAME . ' Load time: ' . elapsed_time($cload_time, 4) . PHP_EOL);
    exit;
}

if (isset($options['o'])) {
    // get filtered options
    get_config_json($options['o']);
} else {
    // All config options
    get_config_json();
}

// EOF
