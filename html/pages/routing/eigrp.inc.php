<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) Adam Armstrong
 *
 */

echo generate_box_open();

echo('<table class="table table-hover table-striped table-condensed">');
echo('<thead><tr><th>Device</th><th>VPN</th><th>ASN</th><th>Router ID</th><th width="80">Ports</th></th><th width="80">Neighbours</th><th width="80">Routes</th></tr></thead>');

foreach (dbFetchRows("SELECT * FROM `eigrp_ases` LEFT JOIN `eigrp_vpns` USING (`device_id`, `eigrp_vpn`) WHERE " . $GLOBALS['cache']['where']['devices_permitted']) as $as) {

    $device = device_by_id_cache($as['device_id']);

    $port_count = dbFetchCell("SELECT COUNT(*) FROM `eigrp_ports` WHERE device_id = ? AND `eigrp_vpn` = ? AND `eigrp_as` = ?", [$as['device_id'], $as['eigrp_vpn'], $as['eigrp_as']]);
    //$peer_count = dbFetchCell("SELECT COUNT(*) FROM `eigrp_peers` WHERE device_id = ? AND `eigrp_vpn` = ? AND `eigrp_as` = ?", array($as['device_id'], $as['eigrp_vpn'], $as['eigrp_as']));

    echo('<tr class="' . $row_class . '">');
    echo('  <td class="entity-title">' . generate_device_link($device, NULL, ['tab' => 'routing', 'proto' => 'eigrp']) . '</td>');
    echo('  <td class="entity-title">' . $as['eigrp_vpn_name'] . '</td>');
    echo('  <td>' . $as['eigrp_as'] . '</td>');
    echo('  <td>' . $as['cEigrpAsRouterId'] . '</td>');
    echo('  <td>' . $port_count . '</td>');
    echo('  <td>' . $as['cEigrpNbrCount'] . '</td>');
    echo('  <td>' . $as['cEigrpTopoRoutes'] . '</td>');
    //echo('  <td>' . $peer_count . '</td>');
    echo('</tr>');

}

echo('</table>');

echo generate_box_close();

// EOF
