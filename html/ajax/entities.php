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

$result = [];

if ($_SESSION['userlevel'] >= '5') {

    switch ($_GET['entity_type']) {

        case "port":

            $where_array = build_ports_where_array($GLOBALS['vars']);

            $where = ' WHERE 1 ';
            $where .= implode('', $where_array);

            $query = 'SELECT *, `ports`.`port_id` AS `port_id` FROM `ports`';
            //$query .= ' LEFT JOIN `ports-state` AS S ON `ports`.`port_id` = S.`port_id`';
            $query .= $where;

            $ports_db = dbFetchRows($query, $param);
            port_permitted_array($ports_db);

            foreach ($ports_db as $port) {
                humanize_port($port);
                $device = device_by_id_cache($port['device_id']);
                array_push($result, [intval($port['port_id']), $device['hostname'], $port['port_label'], $port['ifAlias'], $port['ifOperStatus'] == 'up' ? 'up' : 'down']);
            }
            break;

    }

    header('Content-Type: application/json');
    print json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

}

?>
