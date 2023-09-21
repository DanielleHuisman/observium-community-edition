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

echo('<tr>');

echo('<td style="width: 100px;" class="entity-title"> Vlan ' . $vlan['vlan_vlan'] . '</td>');
echo('<td style="width: 200px;" class="small">' . $vlan['vlan_name'] . '</td>');
echo('<td class="strong">');

$vlan_ports = [];
$otherports = dbFetchRows("SELECT * FROM `ports_vlans` AS V, `ports` as P WHERE V.`device_id` = ? AND V.`vlan` = ? AND P.port_id = V.port_id", [$device['device_id'], $vlan['vlan_vlan']]);
foreach ($otherports as $otherport) {
    $vlan_ports[$otherport['ifIndex']] = $otherport;
}
$otherports = dbFetchRows("SELECT * FROM ports WHERE `device_id` = ? AND `ifVlan` = ?", [$device['device_id'], $vlan['vlan_vlan']]);
foreach ($otherports as $otherport) {
    $vlan_ports[$otherport['ifIndex']] = array_merge($otherport, ['untagged' => '1']);
}
ksort($vlan_ports);

foreach ($vlan_ports as $port) {
    humanize_port($port);
    if ($vars['view'] === "graphs") {
        print_port_minigraph($port, $graph_type, 'twoday');
    } else {
        echo($vlan['port_sep'] . generate_port_link_short($port));
        $vlan['port_sep'] = ", ";
        if ($port['untagged']) {
            echo("(U)");
        }
    }
}

echo('</td></tr>');

// EOF
