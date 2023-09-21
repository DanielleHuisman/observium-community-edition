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

// This is populated by UNIX-Agent module. Don't reset it! :D

if (is_module_enabled($device, 'unix-agent')) {
    $agent_data = get_agent_data($device);

    if (is_array($agent_data['app'])) {
        foreach (array_keys($agent_data['app']) as $key) {
            if (file_exists('includes/polling/applications/' . $key . '.inc.php')) {
                //echo(" ");

                // FIXME Currently only used by drbd to get $app['app_instance'] - shouldn't the drbd parser get instances from somewhere else?
                // $app = @dbFetchRow("SELECT * FROM `applications` WHERE `device_id` = ? AND `app_type` = ?", array($device['device_id'],$key));

                // print_debug('Including: applications/'.$key.'.inc.php');

                // echo($key);

                //include('includes/polling/applications/'.$key.'.inc.php');

                $valid_applications[$key] = $key;

            }
        }
    }
}

foreach (dbFetchRows("SELECT * FROM `applications` WHERE `device_id` = ?", [$device['device_id']]) as $entry) {
    $valid_applications[$entry['app_type']] = $entry;
}

if (safe_count($valid_applications)) {
    print_cli_data_field("Applications", 2);
    foreach ($valid_applications as $app_type => $entry) {
        echo $app_type . ' ';

        // One include per application type. Multiple instances currently handled within the application code
        $app_include = $config['install_dir'] . '/includes/polling/applications/' . $app_type . '.inc.php';
        if (is_file($app_include)) {
            include($app_include);
        } else {
            echo($app_type . ' include missing! ');
        }

    }
    echo(PHP_EOL);
}

$app_rows = dbFetchRows("SELECT * FROM `applications` WHERE `device_id` = ? AND `app_lastpolled` < ?", [$device['device_id'], time() - 604800]);
foreach ($app_rows as $app) {
    dbDelete('applications', '`app_id` = ?', [$app['app_id']]);
    echo '-';
}


// EOF
