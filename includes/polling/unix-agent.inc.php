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

global $valid, $agent_sensors;

//print_cli_heading("Observium UNIX Agent");

$agent_data = get_agent_data($device);

if (!empty($agent_data)) {

    $graphs['agent'] = TRUE;

    $agent_sensors = []; # Init to empty to be able to use array_merge() later on

    print_debug_vars($agent_data, 1);

    include("unix-agent/packages.inc.php");
    include("unix-agent/munin-plugins.inc.php");

    foreach (array_keys($agent_data) as $key) {
        if (file_exists($config['install_dir'] . '/includes/polling/unix-agent/' . $key . '.inc.php')) {
            print_debug('Including: unix-agent/' . $key . '.inc.php');

            include($config['install_dir'] . '/includes/polling/unix-agent/' . $key . '.inc.php');
        } else {
            //print_warning("No include: ".$key);
        }
    }

    // Processes
    // FIXME unused
    if (!empty($agent_data['ps'])) {
        echo("\nProcesses: ");
        foreach (explode("\n", $agent_data['ps']) as $process) {
            $process = preg_replace("/\((.*),(\d*),(\d*),([\d\.]*)\)\ (.*)/", "\\1|\\2|\\3|\\4|\\5", $process);
            [$user, $vsz, $rss, $pcpu, $command] = explode("|", $process, 5);
            $processlist[] = ['user' => $user, 'vsz' => $vsz, 'rss' => $rss, 'pcpu' => $pcpu, 'command' => $command];
        }
        #print_vars($processlist);
        echo("\n");
    }
}

print_cli_data_field("Sensors");
foreach (array_keys($config['sensor_types']) as $sensor_class) {
    check_valid_sensors($device, $sensor_class, $valid['sensor'], 'agent');
}
echo("\n");

print_cli_data_field("Virtual machines");
check_valid_virtual_machines($device, $valid['vm'], 'agent');
echo("\n");
//}

// EOF
