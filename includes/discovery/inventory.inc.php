<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$valid['inventory'] = [];

$include_dir = "includes/discovery/inventory";
$include_order = 'default'; // Use MIBs from default os definitions by first!
include($config['install_dir'] . "/includes/include-dir-mib.inc.php");

if (is_module_enabled($device, 'unix-agent', 'poller')) {

    print_cli_heading("UNIX Agent");

    $agent_data = get_agent_data($device);

    //print_vars($agent_data);

    if ($agent_data['lshw_devices']) {
        print_cli_data_field("LSHW Devices");

        $lshw_data = safe_json_decode($agent_data['lshw_devices']);

        process_lshw_node($device, $lshw_data);
    } else {
        print_cli_data("no agent data.");
    }
}

check_valid_inventory($device);

$GLOBALS['module_stats'][$module]['status'] = safe_count($valid[$module]);
if (OBS_DEBUG && $GLOBALS['module_stats'][$module]['status']) {
    print_vars($valid[$module]);
}

// EOF
