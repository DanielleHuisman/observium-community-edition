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

/**
 * Display IPv4/IPv6 addresses.
 *
 * Display pages with IP addresses from device Interfaces.
 *
 * @param array $vars
 *
 * @return void
 *
 */
function print_addresses($vars)
{
    // With pagination? (display page numbers in header)
    $pagination = (isset($vars['pagination']) && $vars['pagination']);
    pagination($vars, 0, TRUE); // Get default pagesize/pageno
    $pageno   = $vars['pageno'];
    $pagesize = $vars['pagesize'];
    $start    = $pagesize * $pageno - $pagesize;

    if (in_array($vars['search'], ['6', 'v6', 'ipv6']) ||
        in_array($vars['view'], ['6', 'v6', 'ipv6'])) {
        $address_type = 'ipv6';
    } else {
        $address_type = 'ipv4';
    }

    $ip_array        = [];
    $param           = [];
    $where           = ' WHERE 1 ';
    $join_ports      = FALSE;
    $param_netscaler = [];
    $where_netscaler = " WHERE `vsvr_ip` != '0.0.0.0' AND `vsvr_ip` != '' ";
    foreach ($vars as $var => $value) {
        if ($value != '') {
            switch ($var) {
                case 'device':
                case 'device_id':
                    $where           .= generate_query_values_and($value, 'A.device_id');
                    $where_netscaler .= generate_query_values_and($value, 'N.device_id');
                    break;
                case 'interface':
                    $where      .= generate_query_values_and($value, 'I.ifDescr', 'LIKE%');
                    $join_ports = TRUE;
                    break;
                case 'type':
                    $where .= generate_query_values_and($value, 'A.ip_type');
                    break;
                case 'network':
                    if (!is_array($value)) {
                        $value = explode(',', $value);
                    }
                    if ($ids = get_entity_ids_ip_by_network($address_type, $value)) {
                        // Full network with prefix
                        $where .= generate_query_values_and($ids, 'A.ip_address_id');
                    } else {
                        // Part of network string
                        $where .= ' AND 0'; // Nothing!
                    }
                    $where_netscaler .= ' AND 0'; // Currently, unsupported for Netscaller
                    break;
                case 'address':
                    if (!is_array($value)) {
                        $value = explode(',', $value);
                    }
                    // Remove prefix part
                    $addr = [];
                    foreach ($value as $tmp) {
                        [$addr[], $mask] = explode('/', $tmp);
                    }
                    if ($ids = get_entity_ids_ip_by_network($address_type, $addr)) {
                        // Full network with prefix
                        $where .= generate_query_values_and($ids, 'A.ip_address_id');
                    } else {
                        $where .= ' AND 0'; // Nothing!
                    }
                    /// FIXME. Netscaller hack
                    if (count($addr) && get_ip_version($addr[0])) {
                        // Netscaller for valid IP address
                        $where_netscaler .= generate_query_values_and($addr, 'N.vsvr_ip');
                    } else {
                        $where_netscaler .= generate_query_values_and($addr, 'N.vsvr_ip', '%LIKE%');
                    }
                    break;
            }
        }
    }

    $query_device_permitted = generate_query_permitted(['device']);
    $query_port_permitted   = generate_query_permitted(['device', 'port']);

    // Also search netscaler Vserver IPs
    $query_netscaler = 'FROM `netscaler_vservers` AS N ';
    $query_netscaler .= 'LEFT JOIN `devices` USING(`device_id`) ';
    $query_netscaler .= $where_netscaler . $query_device_permitted;
    //$query_netscaler_count = 'SELECT COUNT(`vsvr_id`) ' . $query_netscaler;
    $query_netscaler = 'SELECT * ' . $query_netscaler;
    $query_netscaler .= ' ORDER BY `vsvr_ip`';
    // Override by address type
    if ($address_type == 'ipv6') {
        $query_netscaler = str_replace(['vsvr_ip', '0.0.0.0'], ['vsvr_ipv6', '0:0:0:0:0:0:0:0'], $query_netscaler);
        //$query_netscaler_count = str_replace(array('vsvr_ip', '0.0.0.0'), array('vsvr_ipv6', '0:0:0:0:0:0:0:0'), $query_netscaler_count);
    }

    $entries = dbFetchRows($query_netscaler, $param_netscaler);
    // Rewrite netscaler addresses
    foreach ($entries as $entry) {
        $ip_address = ($address_type == 'ipv4') ? $entry['vsvr_ip'] : $entry['vsvr_' . $address_type];
        $ip_network = ($address_type == 'ipv4') ? $entry['vsvr_ip'] . '/32' : $entry['vsvr_' . $address_type] . '/128';

        $ip_array[] = ['type'                     => 'netscalervsvr',
                       'device_id'                => $entry['device_id'],
                       'hostname'                 => $entry['hostname'],
                       'vsvr_id'                  => $entry['vsvr_id'],
                       'vsvr_label'               => $entry['vsvr_label'],
                       'ifAlias'                  => 'Netscaler: ' . $entry['vsvr_type'] . '/' . $entry['vsvr_entitytype'],
                       $address_type . '_address' => $ip_address,
                       $address_type . '_network' => $ip_network
        ];
    }
    //print_message($query_netscaler_count);

    $query = 'FROM `ip_addresses` AS A ';
    $query .= ' LEFT JOIN `ip_networks` AS N USING(`ip_network_id`)';
    if ($join_ports) {
        $query .= ' LEFT JOIN `ports`       USING(`port_id`)';
    }
    //$query .= ' LEFT JOIN `devices`     USING(`device_id`)';
    $query .= $where . $query_port_permitted;
    //$query_count = 'SELECT COUNT(`ip_address_id`) ' . $query;
    $query = 'SELECT A.*, N.* ' . $query;
    $query .= ' ORDER BY A.`ip_binary`';
    if ($ip_valid) {
        $pagination = FALSE;
    }

    // Override by address type
    //$query = str_replace(array('ip_address', 'ip_network'), array($address_type.'_address', $address_type.'_network'), $query);
    $query = preg_replace('/ip_(address|network|type|binary)/', $address_type . '_$1', $query);
    //$query_count = str_replace(array('ip_address', 'ip_network'), array($address_type.'_address', $address_type.'_network'), $query_count);

    // Query addresses
    $entries  = dbFetchRows($query, $param);
    $ip_array = array_sort($ip_array, $address_type . '_address'); // Sort netscaller
    $ip_array = array_merge($entries, $ip_array);

    // Query address count
    //if ($pagination) { $count = dbFetchCell($query_count, $param); }
    if ($pagination) {
        $count    = count($ip_array);
        $ip_array = array_slice($ip_array, $start, $pagesize);
    }

    $list = ['device' => FALSE];
    if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] == 'search') {
        $list['device'] = TRUE;
    }

    $string = generate_box_open($vars['header']);
    $string .= '<table class="' . OBS_CLASS_TABLE_STRIPED . '">' . PHP_EOL;
    if (!$short) {
        $string .= '  <thead>' . PHP_EOL;
        $string .= '    <tr>' . PHP_EOL;
        if ($list['device']) {
            $string .= '      <th>Device</th>' . PHP_EOL;
        }
        $string .= '      <th>Interface</th>' . PHP_EOL;
        $string .= '      <th>Address</th>' . PHP_EOL;
        $string .= '      <th>Type</th>' . PHP_EOL;
        $string .= '      <th>[VRF] Description</th>' . PHP_EOL;
        $string .= '    </tr>' . PHP_EOL;
        $string .= '  </thead>' . PHP_EOL;
    }
    $string .= '  <tbody>' . PHP_EOL;

    $vrf_cache = [];
    foreach ($ip_array as $entry) {
        $address_show = TRUE;
        if ($ip_valid) {
            // If address not in specified network, don't show entry.
            if ($address_type === 'ipv4') {
                $address_show = Net_IPv4 ::ipInNetwork($entry[$address_type . '_address'], $addr . '/' . $mask);
            } else {
                $address_show = Net_IPv6 ::isInNetmask($entry[$address_type . '_address'], $addr, $mask);
            }
        }

        if ($address_show) {
            [$prefix, $length] = explode('/', $entry[$address_type . '_network']);

            if ($entry['type'] == 'netscalervsvr') {
                $entity_link = generate_entity_link($entry['type'], $entry);
            } elseif ($port = get_port_by_id_cache($entry['port_id'])) {
                if ($port['ifInErrors_delta'] > 0 || $port['ifOutErrors_delta'] > 0) {
                    $port_error = generate_port_link($port, '<span class="label label-important">Errors</span>', 'port_errors');
                }
                // for port_label_short - generate_port_link($link_if, NULL, NULL, TRUE, TRUE)
                $entity_link      = generate_port_link_short($port) . ' ' . $port_error;
                $entry['ifAlias'] = $port['ifAlias'];
            } elseif ($vlan = dbFetchRow('SELECT * FROM `vlans` WHERE `device_id` = ? AND `ifIndex` = ?', [$entry['device_id'], $entry['ifIndex']])) {
                // Vlan ifIndex (without associated port)
                $entity_link      = 'Vlan ' . $vlan['vlan_vlan'];
                $entry['ifAlias'] = $vlan['vlan_name'];
            } else {
                $entity_link = 'ifIndex ' . $entry['ifIndex'];
            }

            // Query VRFs
            if ($entry['vrf_id']) {
                if (isset($vrf_cache[$entry['vrf_id']])) {
                    $vrf_name = $vrf_cache[$entry['vrf_id']];
                } else {
                    $vrf_name                    = dbFetchCell("SELECT `vrf_name` FROM `vrfs` WHERE `vrf_id` = ?", [$entry['vrf_id']]);
                    $vrf_cache[$entry['vrf_id']] = $vrf_name;
                }
                $entry['ifAlias'] = '<span class="label label-default">' . $vrf_name . '</span> ' . $entry['ifAlias'];
            }

            $device_link = generate_device_link($entry);
            $string      .= '  <tr>' . PHP_EOL;
            if ($list['device']) {
                $string .= '    <td class="entity" style="white-space: nowrap">' . $device_link . '</td>' . PHP_EOL;
            }
            $string .= '    <td class="entity">' . $entity_link . '</td>' . PHP_EOL;
            if ($address_type === 'ipv6') {
                $entry[$address_type . '_address'] = Net_IPv6 ::compress($entry[$address_type . '_address']);
            }
            $string     .= '    <td>' . generate_popup_link('ip', $entry[$address_type . '_address'] . '/' . $length) . '</td>' . PHP_EOL;
            $type       = strlen($entry[$address_type . '_type']) ? $entry[$address_type . '_type'] : get_ip_type($entry[$address_type . '_address'] . '/' . $length);
            $type_class = $GLOBALS['config']['ip_types'][$type]['label-class'];
            $string     .= '    <td><span class="label label-' . $type_class . '">' . $type . '</span></td>' . PHP_EOL;
            $string     .= '    <td>' . $entry['ifAlias'] . '</td>' . PHP_EOL;
            $string     .= '  </tr>' . PHP_EOL;

        }
    }

    $string .= '  </tbody>' . PHP_EOL;
    $string .= '</table>';
    $string .= generate_box_close();

    // Print pagination header
    if ($pagination) {
        $string = pagination($vars, $count) . $string . pagination($vars, $count);
    }

    // Print addresses
    echo $string;
}

// EOF
