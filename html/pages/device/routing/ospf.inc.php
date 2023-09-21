<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

register_html_title("OSPF");

$navbar          = [];
$navbar['brand'] = "OSPF";
$navbar['class'] = "navbar-narrow";

// Loop Instances (There can only ever really be once instance at the moment, thanks to douchebags who decided we should use undiscoverable context names instead of just making tables.)
// There only 2 possible instances (V2/V3)
foreach (dbFetchRows("SELECT * FROM `ospf_instances` WHERE `device_id` = ?", [$device['device_id']]) as $instance) {

    echo generate_box_open();
    echo '<table class="table table-hover  table-striped table-condensed">';

    $ospf_version = $instance['ospfVersionNumber'];

    $area_count = dbFetchCell("SELECT COUNT(*) FROM `ospf_areas` WHERE `device_id` = ? AND `ospfVersionNumber` = ?", [$device['device_id'], $ospf_version]);
    if ($ospf_version !== 'version3') {
        $instance_id = $instance['ospfRouterId'];
        if ($device_routing_count['ospf'] > 1) {
            $instance_id .= ' <span class="label label-success">V2</span>';
        }
        // V2 always have 5 part index, ie: 95.130.232.140.0
        $port_params = [$device['device_id'], '^[[:digit:]]+(\.[[:digit:]]+){4}$'];
        // V2 always have 5 part index, ie: .95.130.232.130.0
        $nbr_params = [$device['device_id'], '^[[:digit:]]+(\.[[:digit:]]+){4}$'];
    } else {
        $instance_id = long2ip($instance['ospfRouterId']);
        if ($device_routing_count['ospf'] > 1) {
            $instance_id .= ' <span class="label label-primary">V3</span>';
        }
        // V3 always have 2 part index, ie: 6.0
        $port_params = [$device['device_id'], '^[[:digit:]]+\.[[:digit:]]+$'];
        // V3 always have 3 part index, ie: .4.0.1602414725
        $nbr_params = [$device['device_id'], '^[[:digit:]]+(\.[[:digit:]]+){2}$'];
    }
    $port_count         = 0;
    $port_count_enabled = 0;
    $sql                = 'SELECT `ospfIfAdminStat`, COUNT(*) AS `count` FROM `ospf_ports`' .
                          ' WHERE `device_id` = ? AND `ospf_port_id` REGEXP ? GROUP BY `ospfIfAdminStat`';
    foreach (dbFetchRows($sql, $port_params) as $entry) {
        if ($entry['ospfIfAdminStat'] === 'enabled') {
            $port_count_enabled = (int)$entry['count'];
        }
        $port_count += (int)$entry['count'];
    }
    $nbr_count = dbFetchCell("SELECT COUNT(*) FROM `ospf_nbrs` WHERE `device_id` = ? AND `ospf_nbr_id` REGEXP ?", $nbr_params);

    if ($instance['ospfAdminStat'] === "enabled") {
        $enabled   = '<span class="label label-success">enabled</span>';
        $row_class = 'up';
    } else {
        $enabled   = '<span class="label">disabled</span>';
        $row_class = "disabled";
    }
    if ($instance['ospfAreaBdrRtrStatus'] === "true") {
        $abr = '<span class="green">yes</span>';
    } else {
        $abr = '<span class="grey">no</span>';
    }
    if ($instance['ospfASBdrRtrStatus'] === "true") {
        $asbr = '<span class="green">yes</span>';
    } else {
        $asbr = '<span class="grey">no</span>';
    }

    $cols = [
      [NULL, 'class="state-marker"'],
      ['Router Id', 'style="width: 160px;"'],
      'Status',
      'ABR',
      'ASBR',
      'Areas',
      'Ports',
      'Neighbours'
      //'descr'        => array('Description',  'style="width: 400px;"'),
      //'rule'         => 'Rule',
    ];

    echo get_table_header($cols, $vars);
    //echo('<thead><tr><th class="state-marker"></th><th>Router Id</th><th>Status</th><th>ABR</th><th>ASBR</th><th>Areas</th><th>Ports</th><th>Neighbours</th></tr></thead>');
    echo('<tr class="' . $row_class . '">');
    echo('  <td class="state-marker"></td>');
    echo('  <td class="entity-title">' . $instance_id . '</td>');
    echo('  <td>' . $enabled . '</td>');
    echo('  <td>' . $abr . '</td>');
    echo('  <td>' . $asbr . '</td>');
    echo('  <td>' . $area_count . '</td>');
    echo('  <td>' . $port_count . '(' . $port_count_enabled . ')</td>');
    echo('  <td>' . $nbr_count . '</td>');
    echo('</tr>');

    echo '</table>';

    echo generate_box_close();


    /// Global Areas Table
    /// FIXME -- humanize_ospf_area()

    echo generate_box_open(['title' => 'Areas']);


    echo('<table class="table table-hover table-striped">');
    $cols = [
      [NULL, 'class="state-marker"'],
      ['Area Id', 'style="width: 160px;"'],
      'Status',
      'Auth Type',
      'AS External',
      'Area LSAs',
      'Area Summary',
      'Ports'
    ];
    echo get_table_header($cols, $vars);
    //echo('<thead><tr><th class="state-marker"></th><th>Area Id</th><th>Status</th><th>Auth Type</th><th>AS External</th><th>Area LSAs</th><th>Area Summary</th><th>Ports</th></tr></thead>');

    /// Loop Areas
    foreach (dbFetchRows("SELECT * FROM `ospf_areas` WHERE `device_id` = ? AND `ospfVersionNumber` = ?", [$device['device_id'], $ospf_version]) as $area) {

        $port_params[]           = $area['ospfAreaId'];
        $area_port_count         = dbFetchCell("SELECT COUNT(*) FROM `ospf_ports` WHERE `device_id` = ? AND `ospf_port_id` REGEXP ? AND `ospfIfAreaId` = ?", $port_params);
        $area_port_count_enabled = dbFetchCell("SELECT COUNT(*) FROM `ospf_ports` WHERE `ospfIfAdminStat` = 'enabled' AND `device_id` = ? AND `ospf_port_id` REGEXP ? AND `ospfIfAreaId` = ?", $port_params);

        $area_id        = $ospf_version === 'version3' ? long2ip($area['ospfAreaId']) : $area['ospfAreaId'];
        $area_row_class = $area['ospfAreaStatus'] === 'active' ? 'up' : 'disabled';
        $enabled        = $area['ospfAreaStatus'] === 'active' ? '<span class="label label-success">' . $area['ospfAreaStatus'] . '</span>' : '<span class="label">' . $area['ospfAreaStatus'] . '</span>';
        echo('<tr class="' . $area_row_class . '">');
        echo('  <td class="state-marker"></td>');
        echo('  <td class="entity-title">' . $area_id . '</td>');
        echo('  <td>' . $enabled . '</td>');
        echo '  <td>' . $area['ospfAuthType'] . '</td>';
        echo '  <td>' . $area['ospfImportAsExtern'] . '</td>';
        echo '  <td>' . $area['ospfAreaLsaCount'] . '</td>';
        echo '  <td>' . $area['ospfAreaSummary'] . '</td>';
        echo('  <td>' . $area_port_count . '(' . $area_port_count_enabled . ')</td>');
        echo('</tr>');

        echo('<tr>');
        echo('<td colspan=8>');

        /// Per-Area Ports Table
        /// FIXME -- humanize_ospf_port()

        echo generate_box_open();

        echo('<table class="table table-hover table-striped table-condensed">');
        $cols = [
          [NULL, 'class="state-marker"'],
          ['Port', 'style="width: 160px;"'],
          ['Status', 'style="width: 160px;"'],
          'Port Type',
          'Port State'
        ];
        echo get_table_header($cols, $vars);
        //echo('<thead><tr><th class="state-marker"></th><th>Port</th><th>Status</th><th>Port Type</th><th>Port State</th></tr></thead>');

        // Loop Ports
        $p_sql = 'SELECT * FROM `ospf_ports` LEFT JOIN `ports` USING (`device_id`, `port_id`)' .
                 ' WHERE `device_id` = ? AND `ospf_port_id` REGEXP ? AND `ospfIfAreaId` = ? AND `ospfIfAdminStat` = \'enabled\'';
        //$p_sql   = "SELECT * FROM `ospf_ports` AS O, `ports` AS P WHERE O.`ospfIfAdminStat` = 'enabled' AND O.`device_id` = ? AND O.`ospfIfAreaId` = ? AND P.port_id = O.port_id";
        foreach (dbFetchRows($p_sql, $port_params) as $ospfport) {

            if ($ospfport['ospfIfAdminStat'] === "enabled") {
                $port_enabled   = '<span class="label label-success">enabled</span>';
                $port_row_class = 'up';
            } else {
                $port_enabled   = '<span class="label">' . $ospfport['ospfIfAdminStat'] . '</span>';
                $port_row_class = 'disabled';
            }

            echo('<tr class="' . $port_row_class . '">');
            echo('  <td class="state-marker"></td>');
            echo('  <td><strong>' . generate_port_link($ospfport) . '</strong></td>');
            echo('  <td>' . $port_enabled . '</td>');
            echo('  <td>' . $ospfport['ospfIfType'] . '</td>');
            echo('  <td>' . $ospfport['ospfIfState'] . '</td>');
            echo('</tr>');
        } // End loop Ports

        echo('</table>');

        echo generate_box_close();

    } // End loop areas
    echo '</table>';

    echo generate_box_close();


    /// Global Neighbour Table
    /// FIXME -- humanize_ospf_neighbour()

    echo generate_box_open(['title' => 'Neighbours']);

    echo '<table class="table table-condensed table-hover table-striped">';
    $cols = [
      [NULL, 'class="state-marker"'],
      ['Router Id', 'style="width: 160px;"'],
      ['Device', 'style="width: 160px;"'],
      'IP Address',
      'Status'
    ];
    echo get_table_header($cols, $vars);
    //echo '<thead><tr><th class="state-marker"></th><th>Router Id</th><th>Device</th><th>IP Address</th><th>Status</th></tr></thead>';

    // Loop Neighbours
    foreach (dbFetchRows("SELECT * FROM `ospf_nbrs` WHERE `device_id` = ? AND `ospf_nbr_id` REGEXP ?", $nbr_params) as $nbr) {
        if ($ospf_version !== 'version3') {
            $nbr_router_id = $nbr['ospfNbrRtrId'];
        } else {
            $nbr_router_id = long2ip($nbr['ospfNbrRtrId']);
        }
        $host = dbFetchRow("SELECT `device_id`, `port_id` FROM `ipv4_addresses` WHERE `ipv4_address` = ?", [$nbr_router_id]);

        if (is_array($host)) {
            $rtr_id = generate_device_link($host);
        } else {
            $rtr_id = '<span class="label">unknown</span>';
        }

        echo('<tr class="' . $port_row_class . '">');
        echo('  <td class="state-marker"></td>');
        echo('  <td><span class="entity-title">' . $nbr_router_id . '</span></td>');
        echo('  <td>' . $rtr_id . '</td>');
        echo('  <td>' . ip_compress($nbr['ospfNbrIpAddr']) . '</td>');
        echo('  <td>');
        switch ($nbr['ospfNbrState']) {
            case 'full':
                echo('<span class="green">' . $nbr['ospfNbrState'] . '</span>');
                break;
            case 'down':
                echo('<span class="red">' . $nbr['ospfNbrState'] . '</span>');
                break;
            default:
                echo('<span class="blue">' . $nbr['ospfNbrState'] . '</span>');
                break;
        }
        echo('</td>');
        echo('</tr>');

    }

    echo('</table>');

    echo generate_box_close();

} // End loop instances

// EOF
