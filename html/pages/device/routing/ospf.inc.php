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

register_html_title("OSPF");

$navbar          = [];
$navbar['brand'] = "OSPF";
$navbar['class'] = "navbar-narrow";

$ospf_version = $vars['proto'] == "ospfv3" ? "version3" : "version2";
$ospf_params  = [ $device['device_id'], $ospf_version ];

// Loop Instances (There can only ever really be once instance at the moment, thanks to douchebags who decided we should use undiscoverable context names instead of just making tables.)
// There only 2 possible instances (V2/V3)
foreach (dbFetchRows("SELECT * FROM `ospf_instances` WHERE `device_id` = ? AND `ospfVersionNumber` = ?", $ospf_params) as $instance) {

    //$ospf_version = $instance['ospfVersionNumber'];

    $area_count = dbFetchCell("SELECT COUNT(*) FROM `ospf_areas` WHERE `device_id` = ? AND `ospfVersionNumber` = ?", $ospf_params);
    if ($ospf_version !== 'version3') {
        $instance_id = $instance['ospfRouterId'];
        if ($device_routing_count['ospf'] > 1) {
            $instance_id .= ' <span class="label label-success">V2</span>';
        }
    } else {
        // V3
        $instance_id = long2ip($instance['ospfRouterId']);
        if ($device_routing_count['ospfv3'] > 1) {
            $instance_id .= ' <span class="label label-primary">V3</span>';
        }
    }
    $port_count         = 0;
    $port_count_enabled = 0;
    $sql                = 'SELECT `ospfIfAdminStat`, COUNT(*) AS `count` FROM `ospf_ports`' .
                          ' WHERE `device_id` = ? AND `ospfVersionNumber` = ? GROUP BY `ospfIfAdminStat`';
    foreach (dbFetchRows($sql, $ospf_params) as $entry) {
        if ($entry['ospfIfAdminStat'] === 'enabled') {
            $port_count_enabled = (int)$entry['count'];
        }
        $port_count += (int)$entry['count'];
    }
    $nbr_count = dbFetchCell("SELECT COUNT(*) FROM `ospf_nbrs` WHERE `device_id` = ? AND `ospfVersionNumber` = ?", $ospf_params);

    if ($instance['ospfAdminStat'] === "enabled") {
        $enabled   = '<span class="label label-success">enabled</span>';
        $row_class = 'up';
    } else {
        $enabled   = '<span class="label">disabled</span>';
        $row_class = "disabled";
    }
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

    echo generate_box_open();

    ?>
    <table class="table table-hover table-striped vertical-align">
        <tbody>
        <tr class="up">
            <td class="state-marker"></td>
            <td style="padding: 10px 14px;"><span style="font-size: 20px;">Router ID <?php echo($instance_id); ?></span>
</td>
<td>
</td>

<td style="text-align: right;">



    <div class="btn-group" style="margin: 5px;">
        <div class="btn btn-sm btn-default"><strong>Status</strong></div>
        <div class="btn btn-sm btn-<?php echo ($instance['ospfAdminStat'] == "enabled" ? 'success' : 'warning')  ?>"> <?php echo $instance['ospfAdminStat']; ?></div>
    </div>

    <?php

if ($instance['ospfAreaBdrRtrStatus'] === "true") {
    echo '
    <div class="btn-group" style="margin: 5px;">
        <div class="btn btn-sm btn-default"><strong>ABR</strong></div>
        <div class="btn btn-sm btn-success">yes</div>
    </div>';
}

if ($instance['ospfASBdrRtrStatus'] === "true") {
    echo '
    <div class="btn-group" style="margin: 5px;">
        <div class="btn btn-sm btn-default"><strong>ASBR</strong></div>
        <div class="btn btn-sm btn-success">yes</div>
    </div>';
}

    ?>
    <div class="btn-group" style="margin: 5px;">
        <div class="btn btn-sm btn-default"><strong>Areas</strong></div>
        <div class="btn btn-sm btn-info"> <?php echo $area_count; ?> </div>
    </div>

    <div class="btn-group" style="margin: 5px;">
        <div class="btn btn-sm btn-default"><strong>Ports</strong></div>
        <div class="btn btn-sm btn-info"> <?php echo $port_count; ?></div>
        <div class="btn btn-sm btn-success"> <?php echo $port_count_enabled; ?></div>
    </div>

    <div class="btn-group" style="margin: 5px;">
        <div class="btn btn-sm btn-default"><strong>Neighbours</strong></div>
        <div class="btn btn-sm btn-info"> <?php echo $nbr_count; ?></div>
    </div>
</td>

</tr>
</tbody>
</table>

<?php

    echo generate_box_close();

    /**
     * Old style


    echo generate_box_open();
    echo '<table class="table table-hover  table-striped table-condensed">';

    $cols = [
      [NULL, 'class="state-marker"'],
      ['Router Id', 'style="width: 160px;"'],
      'Status',
      'ABR',
      'ASBR',
      'Areas',
      'Ports',
      'Neighbours'
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
*/


    echo '<div class="row">';
    echo '<div class="col-md-6">';


    /// Global Areas Table
    /// FIXME -- humanize_ospf_area()

    echo generate_box_open(['title' => 'Areas']);


    echo('<table class="table table-hover table-striped">');
    $cols = [
      [NULL, 'class="state-marker"'],
      ['Area Id', 'style="width: 125px;"'],
      'Status',
      'Auth',
      'AS External',
      'LSAs',
      'Area Summary',
      'Ports'
    ];
    echo get_table_header($cols, $vars);
    //echo('<thead><tr><th class="state-marker"></th><th>Area Id</th><th>Status</th><th>Auth Type</th><th>AS External</th><th>Area LSAs</th><th>Area Summary</th><th>Ports</th></tr></thead>');

    /// Loop Areas
    foreach (dbFetchRows("SELECT * FROM `ospf_areas` WHERE `device_id` = ? AND `ospfVersionNumber` = ?", $ospf_params) as $area) {

        $where_area = [];
        $where_area[] = '`device_id` = ?';
        $where_area[] = '`ospfVersionNumber` = ?';
        $where_area[] = generate_query_values($area['ospfAreaId'], 'ospfIfAreaId');

        //$port_params[]           = $area['ospfAreaId'];
        $area_port_count         = dbFetchCell("SELECT COUNT(*) FROM `ospf_ports` " . generate_where_clause($where_area), $ospf_params);
        $area_port_count_enabled = dbFetchCell("SELECT COUNT(*) FROM `ospf_ports` " . generate_where_clause($where_area, "`ospfIfAdminStat` = 'enabled'"), $ospf_params);

        $area_id        = $ospf_version === 'version3' ? long2ip($area['ospfAreaId']) : $area['ospfAreaId'];
        $area_row_class = $area['ospfAreaStatus'] === 'active' ? 'up' : 'disabled';
        $enabled        = $area['ospfAreaStatus'] === 'active' ? '<span class="label label-success">' . $area['ospfAreaStatus'] . '</span>' : '<span class="label">' . $area['ospfAreaStatus'] . '</span>';
        echo('<tr class="' . $area_row_class . '">');
        echo('  <td class="state-marker"></td>');
        echo('  <td class="entity-title">' . $area_id . '</td>');
        echo('  <td>' . $enabled . '</td>');
        echo '  <td>' . get_type_class_label($area['ospfAuthType'], 'ospfAuthType') . '</td>';
        echo '  <td>' . get_type_class_label($area['ospfImportAsExtern'], 'ospfImportAsExtern') . '</td>';
        echo '  <td>' . $area['ospfAreaLsaCount'] . '</td>';
        echo '  <td>' . get_type_class_label($area['ospfAreaSummary'], 'ospfAreaSummary') . '</td>';
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
          ['Port', 'style="width: 250px;"'],
          ['Status', 'style="width: 125px;"'],
          ['Port Type', 'style="width: 125px;"'],
          'Port State'
        ];
        echo get_table_header($cols, $vars);
        //echo('<thead><tr><th class="state-marker"></th><th>Port</th><th>Status</th><th>Port Type</th><th>Port State</th></tr></thead>');

        // Loop Ports
        $p_sql = 'SELECT * FROM `ospf_ports` LEFT JOIN `ports` USING (`device_id`, `port_id`)' .
                 generate_where_clause($where_area, "`ospfIfAdminStat` = 'enabled'");
        foreach (dbFetchRows($p_sql, $ospf_params) as $ospfport) {

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
            echo('  <td>' . get_type_class_label($ospfport['ospfIfType'], 'ospfIfType') . '</td>');
            echo('  <td>' . get_type_Class_label($ospfport['ospfIfState'], 'ospfIfState') . '</td>');
            echo('</tr>');
        } // End loop Ports

        echo('</table>');

        echo generate_box_close();

    } // End loop areas
    echo '</table>';

    echo generate_box_close();

    echo '</div>'; // End Areas box

    echo '<div class="col-md-6">';

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
    foreach (dbFetchRows("SELECT * FROM `ospf_nbrs` WHERE `device_id` = ? AND `ospfVersionNumber` = ?", $ospf_params) as $nbr) {
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
        echo('  <td>' . get_type_class_label($nbr['ospfNbrState'], 'ospfNbrState') . '</td>');
        echo('</tr>');

    }

    echo('</table>');

    echo generate_box_close();

    echo '</div>';

} // End loop instances

// EOF
