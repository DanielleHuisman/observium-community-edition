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

echo generate_box_open();

echo('<table class="table table-hover table-striped table-condensed">');
echo('<thead><tr>
  <th>Device</th>
  <th>Router Id</th>
  <th style="width: 75px">Version</th>
  <th style="width: 75px">Status</th>
  <th style="width: 75px">ABR</th>
  <th style="width: 75px">ASBR</th>
  <th style="width: 75px">Areas</th>
  <th style="width: 75px">Ports</th>
  <th style="width: 75px">Neighbours</th>
</tr></thead>');

// Counts
$area_counts = [];
foreach (dbFetchRows('SELECT `device_id`, `ospfVersionNumber`, COUNT(*) AS `count` FROM `ospf_areas`' . generate_where_clause($GLOBALS['cache']['where']['devices_permitted']) . ' GROUP BY `device_id`, `ospfVersionNumber`') as $entry) {
    $area_counts[$entry['device_id']][$entry['ospfVersionNumber']] = $entry['count'];
}
$port_counts = [];
foreach (dbFetchRows('SELECT `device_id`, `ospfVersionNumber`, COUNT(*) AS `count` FROM `ospf_ports`' . generate_where_clause($GLOBALS['cache']['where']['devices_permitted']) . ' GROUP BY `device_id`, `ospfVersionNumber`') as $entry) {
    $port_counts[$entry['device_id']][$entry['ospfVersionNumber']] = $entry['count'];
}
$port_counts_enabled = [];
foreach (dbFetchRows('SELECT `device_id`, `ospfVersionNumber`, COUNT(*) AS `count` FROM `ospf_ports`' . generate_where_clause($GLOBALS['cache']['where']['devices_permitted'], '`ospfIfAdminStat` = "enabled"') . ' GROUP BY `device_id`, `ospfVersionNumber`') as $entry) {
    $port_counts_enabled[$entry['device_id']][$entry['ospfVersionNumber']] = $entry['count'];
}
$neighbour_counts = [];
foreach (dbFetchRows('SELECT `device_id`, `ospfVersionNumber`, COUNT(*) AS `count` FROM `ospf_nbrs`' . generate_where_clause($GLOBALS['cache']['where']['devices_permitted']) . ' GROUP BY `device_id`, `ospfVersionNumber`') as $entry) {
    $neighbour_counts[$entry['device_id']][$entry['ospfVersionNumber']] = $entry['count'];
}

// Loop Instances
foreach (dbFetchRows('SELECT * FROM `ospf_instances`' . generate_where_clause(generate_query_values([ 'enabled', 'disabled' ], 'ospfAdminStat'), $GLOBALS['cache']['where']['devices_permitted']) .
                     " ORDER BY `device_id`, `ospfVersionNumber`") as $instance) {
    $device = device_by_id_cache($instance['device_id']);
    $ospf_version = $instance['ospfVersionNumber'];
    if ($ospf_version === 'version3') {
        $instance['ospfRouterId'] = long2ip($instance['ospfRouterId']);
    }

    $row_class = '';
    if ($instance['ospfAdminStat'] === "enabled") {
        $enabled = '<span class="label label-success">enabled</span>';

        //$area_count         = dbFetchCell('SELECT COUNT(*) FROM `ospf_areas` WHERE `device_id` = ?', array($device['device_id']));
        //$port_count         = dbFetchCell('SELECT COUNT(*) FROM `ospf_ports` WHERE `device_id` = ?', array($device['device_id']));
        //$port_count_enabled = dbFetchCell("SELECT COUNT(*) FROM `ospf_ports` WHERE `ospfIfAdminStat` = 'enabled' AND `device_id` = ?", array($device['device_id']));
        //$neighbour_count    = dbFetchCell('SELECT COUNT(*) FROM `ospf_nbrs` WHERE `device_id` = ?', array($device['device_id']));
    } else {

        // Skip disabled OSPF processes
        if (!isset($vars['show_disabled']) && !$vars['show_disabled']) {
            continue;
        }

        $enabled   = '<span class="label label-disabled">disabled</span>';
        $row_class = 'error';
    }
    $area_count         = $area_counts[$device['device_id']][$ospf_version] ?? 0;
    $port_count         = $port_counts[$device['device_id']][$ospf_version] ?? 0;
    $port_count_enabled = $port_counts_enabled[$device['device_id']][$ospf_version] ?? 0;
    $neighbour_count    = $neighbour_counts[$device['device_id']][$ospf_version] ?? 0;
    if ((!$port_count_enabled || !$neighbour_count) && safe_empty($row_class)) {
        $row_class = 'warning';
    }

    /*
    $ip_query = "SELECT * FROM ipv4_addresses AS A, ports AS I WHERE ";
    $ip_query .= "(A.ipv4_address = ? AND I.port_id = A.port_id)";
    $ip_query .= " AND I.device_id = ?";
    $ipv4_host = dbFetchRow($ip_query, array($peer['bgpPeerIdentifier'], $device['device_id']));
    */

    if ($instance['ospfAreaBdrRtrStatus'] === "true") {
        $abr = '<span class="label label-success">ABR</span>';
    } else {
        $abr = '<span class="label">no</span>';
    }
    if ($instance['ospfASBdrRtrStatus'] === "true") {
        $asbr = '<span class="label label-success">ASBR</span>';
    } else {
        $asbr = '<span class="label">no</span>';
    }

    echo '<tr class="' . $row_class . '">';
    echo '  <td class="entity-title">' . generate_device_link($device, NULL, ['tab' => 'routing', 'proto' => ($instance['ospfVersionNumber'] == 'version3' ? 'ospfv3' : 'ospf')]) . '</td>';
    echo '  <td class="entity-title">' . $instance['ospfRouterId'] . '</td>';
    echo ' <td>'.($instance['ospfVersionNumber'] == "version3" ? '<span class="label label-success">v3</span>' : '<span class="label label-primary">v2</span>').'</td>';
    echo '  <td>' . $enabled . '</td>';
    echo '  <td>' . $abr . '</td>';
    echo '  <td>' . $asbr . '</td>';
    echo '  <td>' . $area_count . '</td>';
    echo '  <td>' . $port_count . '(' . $port_count_enabled . ')</td>';
    echo '  <td>' . $neighbour_count . '</td>';
    echo '</tr>';

} // End loop instances

echo('</table>');

echo generate_box_close();

// EOF
