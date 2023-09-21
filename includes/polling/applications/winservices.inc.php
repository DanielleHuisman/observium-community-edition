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


//Windows Services
print_cli_data("Collecting", "Windows Services ", 3);

$table_rows    = [];
$table_headers = ['%WService Name%n', '%WService Display Name%n', '%WService State%n', '%WService Start Mode%n'];

$winservices_db = dbFetchRows('SELECT * FROM `winservices` WHERE `device_id` = ?', [$device['device_id']]);
foreach ($winservices_db as $service) {
    $winservices_db[$service['name']] = $service;
    $service_exist[$service['name']]  = $service['winsvc_id'];
}

print_debug_vars($wmi['winservices']);

foreach ($wmi['winservices'] as $service) {
    if ((!empty($service['Name'])) && ($service['Name'] != "Name")) {
        if (OBS_DEBUG) {
            print_r($service);
        }

        $name = $service['Name'];

        if (is_array($winservices_db[$name])) {

            $winsvc_id = $winservices_db[$name]['winsvc_id'];

            //echo("Service Name exists: $name\n");
            dbUpdate([
                       'displayname' => $service['DisplayName'],
                       'state'       => $service['State'],
                       'startmode'   => $service['StartMode'],
                     ], 'winservices', '`device_id` = ? AND `name` = ?',
              [$device['device_id'], $name]
            );

            unset($service_exist[$name]);
        } else {
            //echo("New Service Name: $name\n");
            $winsvc_id = dbInsert(['device_id' => $device['device_id'],
                                                                                                                                                                                                                                                                                                  'name' => $service['Name'],
                                                                                                                                                                                                                                                                                                  'displayname' => $service['DisplayName'],
                                   'state'     => $service['State'],
                                   'startmode' => $service['StartMode'],
                                  ], 'winservices'
            );
        }

        check_entity('winservice', ['winsvc_id' => $winsvc_id, 'device_id' => $device['device_id']], ['state' => $service['State'], 'startmode' => $service['StartMode']]);

        $table_row    = [];
        $table_row[]  = $service['Name'];
        $table_row[]  = $service['DisplayName'];
        $table_row[]  = $service['State'];
        $table_row[]  = $service['StartMode'];
        $table_rows[] = $table_row;
        unset($table_row);
    }
}

if (OBS_DEBUG) {
    print_cli_table($table_rows, $table_headers);
}

print_debug_vars($service_exist);

foreach ($service_exist as $name => $winsvc_id) {
    //echo("will delete service:$name with id:$winsvc_id");
    dbDelete('winservices', '`winsvc_id` =  ?', [$winsvc_id]);
}

unset($table_rows, $table_headers, $winservices_db, $service, $service_exist, $winservice, $winsvc_id, $name, $wmi);

// EOF
