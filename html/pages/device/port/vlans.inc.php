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

$vlans = dbFetchRows('SELECT * FROM `ports_vlans` AS PV, vlans AS V WHERE PV.`port_id` = ? and PV.`device_id` = ? AND V.`vlan_vlan` = PV.vlan AND V.device_id = PV.device_id', [$port['port_id'], $device['device_id']]);

echo generate_box_open();

echo('<table class="table  table-striped table-hover table-condensed">');

echo("<thead><tr><th>VLAN</th><th>Description</th><th>Cost</th><th>Priority</th><th>State</th><th>Other Ports</th></tr></thead>");

$row = 0;

foreach ($vlans as $vlan) {
    $row++;
    $row_colour = is_intnum($row / 2) ? OBS_COLOUR_LIST_A : OBS_COLOUR_LIST_B;
    echo('<tr>');

    echo('<td style="width: 100px;" class="entity-title"> Vlan ' . $vlan['vlan'] . '</td>');
    echo('<td style="width: 200px;" class="small">' . $vlan['vlan_name'] . '</td>');

    if ($vlan['state'] == "blocking") {
        $class = "red";
    } elseif ($vlan['state'] == "forwarding") {
        $class = "green";
    } else {
        $class = "none";
    }

    echo("<td>" . $vlan['cost'] . "</td><td>" . $vlan['priority'] . "</td><td class=$class>" . $vlan['state'] . "</td>");

    $vlan_ports = [];
    $otherports = dbFetchRows("SELECT * FROM `ports_vlans` AS V, `ports` as P WHERE V.`device_id` = ? AND V.`vlan` = ? AND P.port_id = V.port_id", [$device['device_id'], $vlan['vlan']]);
    foreach ($otherports as $otherport) {
        $vlan_ports[$otherport['ifIndex']] = $otherport;
    }
    $otherports = dbFetchRows("SELECT * FROM ports WHERE `device_id` = ? AND `ifVlan` = ?", [$device['device_id'], $vlan['vlan']]);
    foreach ($otherports as $otherport) {
        $vlan_ports[$otherport['ifIndex']] = array_merge($otherport, ['untagged' => '1']);
    }
    ksort($vlan_ports);

    echo("<td>");
    $vsep = '';
    foreach ($vlan_ports as $otherport) {
        echo($vsep . generate_port_link_short($otherport));
        if ($otherport['untagged']) {
            echo("(U)");
        }
        $vsep = ", ";
    }
    echo("</td>");
    echo("</tr>");
}

echo("</table>");

echo generate_box_close();

// EOF
