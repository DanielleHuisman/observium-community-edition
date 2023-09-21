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
$options = getopt("o:d");

require_once("includes/observium.inc.php");

if (is_cli()) {
    if (isset($options['o'])) {
        // get filtered options
        get_config_json($options['o']);
        //print_vars($options);
    } else {
        // All config options
        get_config_json();
        //print(safe_json_encode($config));
    }
}

// EOF
