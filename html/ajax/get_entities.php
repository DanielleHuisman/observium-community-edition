<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     ajax
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

include_once("../../includes/observium.inc.php");

include($config['html_dir'] . "/includes/authenticate.inc.php");

if (!$_SESSION['authenticated']) {
    echo("unauthenticated");
    exit;
}

if ($_SESSION['userlevel'] >= '5') {

    $options = [];
    $device_id = filter_input(INPUT_GET, 'device_id', FILTER_SANITIZE_NUMBER_INT);
    $entity_type = filter_input(INPUT_GET, 'entity_type', FILTER_SANITIZE_STRING);

    switch ($entity_type) {
        case "device":

            include($config['html_dir'] . '/includes/cache-data.inc.php');
            $options = generate_device_form_values(NULL, NULL, [ 'filter_mode' => 'exclude', 'subtext' => '%location%', 'show_disabled' => TRUE, 'show_icon' => TRUE ]);

            break;

        case "sensor":
            foreach (dbFetchRows("SELECT * FROM `sensors` WHERE device_id = ?", [ $device_id ]) as $sensor) {
                if (is_entity_permitted($sensor, 'sensor')) {

                    $nice_class = nicecase($sensor['sensor_class']);
                    $symbol = str_replace('&deg;', '°', $config['sensor_types'][$sensor['sensor_class']]['symbol']);

                    $options[] = [
                      'value'   => $sensor['sensor_id'],
                      'group'   => $nice_class,
                      'name'    => addslashes($sensor['sensor_descr']),
                      'subtext' => round($sensor['sensor_value'],2) . $symbol,
                      'icon'    => $config['sensor_types'][$sensor['sensor_class']]['icon'],
                      //'class'   => 'bg-info'
                    ];
                }
            }
            break;

        case "netscalervsvr":
            // Example for netscalervsvr type
            foreach (dbFetchRows("SELECT * FROM `netscaler_vservers` WHERE device_id = ?", [ $device_id ]) as $entity) {
                $options[] = [
                  'value'   => $entity['vsvr_id'],
                  'name'    => addslashes($entity['vsvr_label']),
                  //'subtext' => 'Extra details for netscalervsvr',
                  //'icon'    => 'netscaler-icon',
                  //'class'   => 'custom-class'
                ];
            }
            break;

        case "port":
            // Example for port type
            foreach (dbFetchRows("SELECT * FROM `ports` WHERE device_id = ? AND deleted = 0", [$_GET['device_id']]) as $port) {

                humanize_port($port);

                $port_type = $port['human_type'];

                $options[] = [
                  'value'   => $port['port_id'],
                  'group'   => $port_type,
                  'name'    => addslashes($port['port_label_short']),
                  //'content' => addslashes($port['port_label_short']) . ' <span class="label">'.format_si($port['ifSpeed']).'bps</span> ',
                  'subtext' => '['.format_si($port['ifSpeed']).'bps] ' . addslashes($port['ifAlias']),
                  'icon'    => $port['icon'],
                  //'class'   => 'port-class'
                ];
            }
            break;

    }

    echo json_encode($options, JSON_UNESCAPED_UNICODE); // Return JSON encoded data
}