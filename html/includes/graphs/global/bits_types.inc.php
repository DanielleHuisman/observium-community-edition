<?php

$params = array();

$sql  = "SELECT `port_id`, `ifIndex`, `ifAlias`, `ifName`, `ifType`, `ifDescr`, `port_label`, `port_label_short`, `device_id`, `hostname`, `ports`.`ignore`";
$sql  = "SELECT `port_id` ";
$sql .= " FROM `ports`";
$sql .= " INNER JOIN `devices` USING (`device_id`)";
$sql .= " WHERE `port_descr_type` = ? ";

$sql .= generate_query_permitted(array('port', 'device'));

$params = array($vars['type_a']);
foreach(dbFetchRows($sql, $params) AS $port)
{
  $transit_ports[] = $port['port_id'];
}

$sql  = "SELECT `port_id`, `ifIndex`, `ifAlias`, `ifName`, `ifType`, `ifDescr`, `port_label`, `port_label_short`, `device_id`, `hostname`, `ports`.`ignore`";
$sql  = "SELECT `port_id` ";
$sql .= " FROM `ports`";
$sql .= " INNER JOIN `devices` USING (`device_id`)";
$sql .= " WHERE `port_descr_type` = ? ";

$sql .= generate_query_permitted(array('port', 'device'));

$params = array($vars['type_b']);
foreach(dbFetchRows($sql, $params) AS $port)
{
  $peering_ports[] = $port['port_id'];
}

$vars['id']  = $transit_ports;
$vars['idb'] = $peering_ports;

include($config['html_dir'] . '/includes/graphs/multi-port/bits_duo_separate.inc.php');

?>
