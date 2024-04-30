<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

function get_vlans($vars) {

    $query_permitted = generate_query_permitted_ng();
    $query_permitted_ports = generate_query_permitted_ng([ 'device', 'port' ]);

    // Known Vlans
    $vlans = [];
    foreach (dbFetchRows("SELECT * FROM `vlans`" . generate_where_clause($query_permitted)) as $vlan) {
        if (safe_empty($vlan['vlan_name'])) {
            // Empty vlan name
            $vlan['vlan_name'] = 'Vlan ' . $vlan['vlan_vlan'];
        }
        $vlans[$vlan['vlan_vlan']]['names'][$vlan['vlan_name']]++;
        $vlans[$vlan['vlan_vlan']]['devices'][$vlan['device_id']]++;
        $vlans[$vlan['vlan_vlan']]['counts']['devices']        = 0;
        $vlans[$vlan['vlan_vlan']]['counts']['ports_tagged']   = 0;
        $vlans[$vlan['vlan_vlan']]['counts']['ports_untagged'] = 0;
    }
    foreach ($vlans as $vlan => &$entry) {
        $entry['counts']['devices'] = safe_count($entry['devices']);
    }
    //r($vlans);

    // Untagged Vlans
    $sql = 'SELECT `ifVlan`, COUNT(`ifVlan`) AS `count` FROM `ports` ' .
           generate_where_clause('(`ifVlan` IS NOT NULL AND `ifVlan` != "") AND `deleted` != 1', $query_permitted_ports) . ' GROUP BY `ifVlan`';
    foreach (dbFetchRows($sql) as $otherport) {
        $vlans[$otherport['ifVlan']]['counts']['ports_untagged'] = $otherport['count'];
    }

    $sql = 'SELECT `vlan`, COUNT(`vlan`) AS `count` FROM `ports_vlans` '.generate_where_clause($query_permitted_ports).' GROUP BY `vlan`';
    foreach (dbFetchRows($sql) as $port_count) {
        if (!isset($vlans[$port_count['vlan']])) {
            //print_error("Unknown VLAN ID '" . $port_count['vlan'] . "' with " . $port_count['count'] . " ports count.");
            print_debug("Unknown VLAN ID '".$port_count['vlan']."' with ".$port_count['count']." ports count.");
            continue;
        }
        $vlans[$port_count['vlan']]['counts']['ports_tagged'] = $port_count['count'];
    }

    $sql = 'SELECT `vlan_id`, COUNT(DISTINCT(`mac_address`)) AS `count` FROM `vlans_fdb` ' .
           generate_where_clause('`deleted` != 1', $query_permitted_ports) . ' GROUP BY `vlan_id`';
    foreach (dbFetchRows($sql) as $mac_count) {
        if (!isset($vlans[$mac_count['vlan_id']])) {
            //print_error("Unknown VLAN ID '".$mac_count['vlan_id']."' with ".$mac_count['count']." mac count.");
            print_debug("Unknown VLAN ID '" . $mac_count['vlan_id'] . "' with " . $mac_count['count'] . " mac count.");
            continue;
        }
        $vlans[$mac_count['vlan_id']]['counts']['macs'] = $mac_count['count'];
    }

    ksort($vlans);

    return $vlans;

}

function print_vlan_ports_row($device, $vlan, $vars) {

    if (!is_numeric($vlan['vlan_vlan'])) {
        return;
    }

    if (!isset($device['device_id']) && isset($vlan['device_id'])) {
        $device = device_by_id_cache($vlan['device_id']);
    }

    $graph_type = isset($vars['graph']) ? 'port_' . $vars['graph'] : 'port_bits';

    echo('<tr>');

    if (FALSE && $vars['view'] === "graphs" && $vars['graph'] === 'fdb_count') {
        // FIXME. I not know, how to add this graph
        echo('<td style="width: 100px;" class="entity-title"> Vlan ' . $vlan['vlan_vlan'] . PHP_EOL);
        print_port_minigraph($vlan, 'vlan_fdbcount', 'twoday');
        echo('</td>');
    } else {
        echo('<td style="width: 100px;" class="entity-title"> Vlan ' . $vlan['vlan_vlan'] . '</td>');
    }
    echo('<td style="width: 200px;" class="small">' . $vlan['vlan_name'] . '</td>');
    echo('<td class="strong">');

    $params = [ $device['device_id'], $vlan['vlan_vlan'] ];
    $vlan_ports = [];
    $sql = "SELECT * FROM `ports_vlans` LEFT JOIN `ports` USING(`device_id`, `port_id`)";
    $sql .= generate_where_clause('`device_id` = ? AND `vlan` = ?' , build_ports_where_filter($device, $vars['filters']));
    foreach (dbFetchRows($sql, $params) as $otherport) {
        $vlan_ports[$otherport['ifIndex']] = $otherport;
    }

    $sql = "SELECT * FROM `ports`";
    $sql .= generate_where_clause('`device_id` = ? AND `ifVlan` = ?' , build_ports_where_filter($device, $vars['filters']));
    foreach (dbFetchRows($sql, $params) as $otherport) {
        $vlan_ports[$otherport['ifIndex']] = array_merge($otherport, [ 'untagged' => '1' ]);
    }
    //r($vlan_ports);
    ksort($vlan_ports);

    $port_links = [];
    foreach ($vlan_ports as $port) {
        //humanize_port($port);
        if ($vars['view'] === "graphs") {
            print_port_minigraph($port, $graph_type, 'twoday');
            continue;
        }

        $link = generate_port_link_short($port);
        if ($port['untagged']) {
            $link .= "(U)";
        }
        $port_links[] = $link;
    }
    echo implode(', ', $port_links);

    echo('</td></tr>');
}

// EOF
