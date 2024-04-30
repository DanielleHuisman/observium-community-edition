<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
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

    switch ($_GET['entity_type']) {

        case "sensor":
            foreach (dbFetchRows("SELECT * FROM `sensors` WHERE device_id = ?", [$_GET['device_id']]) as $sensor) {
                if (is_entity_permitted($sensor, 'sensor')) {
                    $string = addslashes($sensor['sensor_descr']);
                    echo("obj.options[obj.options.length] = new Option('" . $string . "','" . $sensor['sensor_id'] . "');\n");
                }
            }
            break;

        case "netscalervsvr":
            foreach (dbFetchRows("SELECT * FROM `netscaler_vservers` WHERE `device_id` = ?", [$_GET['device_id']]) as $entity) {
                $string = addslashes($entity['vsvr_label']);
                echo("obj.options[obj.options.length] = new Option('" . $string . "','" . $entity['vsvr_id'] . "');\n");
            }
            break;


        case "port":
            foreach (dbFetchRows("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = '0'", [$_GET['device_id']]) as $port) {
                $string = addslashes($port['port_label_short'] . " - " . $port['ifAlias']);
                echo("obj.options[obj.options.length] = new Option('" . $string . "','" . $port['port_id'] . "');\n");
            }
            break;
    }

}

// EOF
