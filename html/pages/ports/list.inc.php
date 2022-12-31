<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

// Pagination
echo(pagination($vars, $ports_count));

if ($vars['pageno']) {
  $ports = array_chunk($ports, $vars['pagesize']);
  $ports = $ports[$vars['pageno']-1];
}
// End Pagination

// Populate ports array (much faster for large systems)
$port_ids = array();
foreach ($ports as $p) {
  $port_ids[] = $p['port_id'];
}
$where = ' WHERE `ports`.`port_id` IN (' . implode(',', $port_ids) . ') ';

$select = "`ports`.*, `ports`.`port_id` AS `port_id`";
#$select = "*,`ports`.`port_id` as `port_id`";

include($config['html_dir']."/includes/port-sort-select.inc.php");

$sql  = "SELECT ".$select;
$sql .= " FROM `ports`";
$sql .= " INNER JOIN `devices` USING (`device_id`)";
//$sql .= " LEFT JOIN `ports-state` USING (`port_id`)";
$sql .= " ".$where;

unset($ports);

$ports = dbFetchRows($sql);

// Re-sort because the DB doesn't do that.
include($config['html_dir']."/includes/port-sort.inc.php");

// End populating ports array

echo generate_box_open();
echo '<table class="' . OBS_CLASS_TABLE_STRIPED . ' table-hover">' . PHP_EOL;

$cols = array(
  'state-marker'  => '',
                     [ NULL, 'style'  => "width: 1px;" ],
  'device'        => [ 'device' => 'Device', 'style' => "min-width: 150px;" ],
                     [ 'port'   => 'Port Name', 'descr' => 'Description', 'errors' => 'Errors', 'style' => "min-width: 250px;" ],
                     [ 'traffic' => ['Bits', 'subfields' => ['traffic_in' => 'In', 'traffic_out' => 'Out']], 'style' => "width: 100px;" ],
                     [ 'traffic_perc' => ['%', 'subfields' => ['traffic_perc_in' => 'In', 'traffic_perc_out' => 'Out']], 'style' => "width: 110px;" ],
                     [ 'packets' => ['Pkts', 'subfields' => ['packets_in' => 'In', 'packets_out' => 'Out']], 'style' => "width: 90px;" ],
                     [ 'speed' => 'Speed', 'mtu' => 'MTU', 'style' => "width: 90px;" ],
                     [ 'media' => 'Media', 'mac' => 'MAC', 'style' =>"width: 150px;" ]
);

//echo get_table_header_old($cols, $vars);
echo generate_table_header($cols, $vars);
echo '<tbody>' . PHP_EOL;

$ports_disabled = 0; $ports_down = 0; $ports_up = 0; $ports_total = 0;
foreach ($ports as $port) {
  $ports_total++;
  print_port_row($port, $vars);
}

echo '</tbody></table>';
echo generate_box_close();

echo pagination($vars, $ports_count);

// EOF
