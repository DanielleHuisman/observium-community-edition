<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Pagination
echo(pagination($vars, $ports_count));

// Populate ports array (much faster for large systems)
//r($port_ids);
$where = generate_where_clause(generate_query_values($ports_ids, 'ports.port_id'));

$select = "`ports`.*, `ports`.`port_id` AS `port_id`";

include($config['html_dir'] . "/includes/port-sort-select.inc.php");

$sql = "SELECT " . $select;
$sql .= " FROM `ports`";
$sql .= " INNER JOIN `devices` USING (`device_id`)";
$sql .= " " . $where . generate_port_sort($vars) . generate_query_limit($vars);

unset($ports);

// End populating ports array

echo generate_box_open();
echo '<table class="' . OBS_CLASS_TABLE_STRIPED . ' table-hover">' . PHP_EOL;


if ($vars['view'] == "detail") {
    $cols = [
        'state-marker' => '',
        [ NULL, 'style' => "width: 1px;" ],
        'device'       => [ 'device' => 'Device', 'style' => "min-width: 150px;" ],
        [ 'port'    => 'Port Name', 'descr' => 'Description', 'errors' => 'Errors', 'style' => "min-width: 250px;" ],
        [ NULL ],
        [ 'traffic' => [ 'Bits', 'subfields' => [ 'traffic_in' => 'In', 'traffic_out' => 'Out' ] ],
        'packets'   => [ 'Pkts', 'subfields' => [ 'packets_in' => 'In', 'packets_out' => 'Out' ] ],
        'style'     => "width: 100px;" ],
        [ 'media'   => "Media", 'speed' => 'Speed' ],
        [ 'mac'     => "MAC" ],
    ];
} else {
    $cols = [
      'state-marker' => '',
      [NULL, 'style' => "width: 1px;"],
      'device'       => ['device' => 'Device', 'style' => "min-width: 150px;"],
      ['port' => 'Port Name', 'descr' => 'Description', 'errors' => 'Errors', 'style' => "min-width: 250px;"],
      ['traffic' => ['Bits', 'subfields' => ['traffic_in' => 'In', 'traffic_out' => 'Out']], 'style' => "width: 100px;"],
      ['traffic_perc' => ['%', 'subfields' => ['traffic_perc_in' => 'In', 'traffic_perc_out' => 'Out']], 'style' => "width: 110px;"],
      ['packets' => ['Pkts', 'subfields' => ['packets_in' => 'In', 'packets_out' => 'Out']], 'style' => "width: 90px;"],
      ['speed' => 'Speed', 'mtu' => 'MTU', 'style' => "width: 90px;"],
      ['media' => 'Media', 'mac' => 'MAC', 'style' => "width: 150px;"]
    ];
}

echo generate_table_header($cols, $vars);
echo '<tbody>' . PHP_EOL;

foreach (dbFetchRows($sql) as $port) {
    print_port_row($port, $vars);
}
/* Example of usage dbFetchFunc()
dbFetchFunc(static function ($port) use ($vars) {
    print_port_row($port, $vars);
}, $sql);
*/

echo '</tbody></table>';
echo generate_box_close();

echo pagination($vars, $ports_count);

// EOF
