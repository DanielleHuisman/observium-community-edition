<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$params = [];

$sql = "SELECT `port_id`, `ifIndex`, `ifAlias`, `ifName`, `ifType`, `ifDescr`, `port_label`, `port_label_short`, `device_id`, `hostname`, `ports`.`ignore`";
$sql = "SELECT `port_id` ";
$sql .= " FROM `ports`";
$sql .= " INNER JOIN `devices` USING (`device_id`)";
$sql .= " WHERE `port_descr_type` = ? ";

$sql .= generate_query_permitted(['port', 'device']);

$params = [$vars['type_a']];
foreach (dbFetchRows($sql, $params) as $port) {
    $transit_ports[] = $port['port_id'];
}

$sql = "SELECT `port_id`, `ifIndex`, `ifAlias`, `ifName`, `ifType`, `ifDescr`, `port_label`, `port_label_short`, `device_id`, `hostname`, `ports`.`ignore`";
$sql = "SELECT `port_id` ";
$sql .= " FROM `ports`";
$sql .= " INNER JOIN `devices` USING (`device_id`)";
$sql .= " WHERE `port_descr_type` = ? ";

$sql .= generate_query_permitted(['port', 'device']);

$params = [$vars['type_b']];
foreach (dbFetchRows($sql, $params) as $port) {
    $peering_ports[] = $port['port_id'];
}

$vars['id']  = $transit_ports;
$vars['idb'] = $peering_ports;

include($config['html_dir'] . '/includes/graphs/multi-port/bits_duo_separate.inc.php');

// EOF
