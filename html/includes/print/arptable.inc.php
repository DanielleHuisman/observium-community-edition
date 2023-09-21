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
 * Display ARP/NDP table addresses.
 *
 * Display pages with ARP/NDP tables addresses from devices.
 *
 * @param array $vars
 *
 * @return none
 *
 */
function print_arptable($vars)
{
    // With pagination? (display page numbers in header)
    $pagination = (isset($vars['pagination']) && $vars['pagination']);
    pagination($vars, 0, TRUE);                                                             // Get default pagesize/pageno
    $pageno   = $vars['pageno'];
    $pagesize = $vars['pagesize'];
    $start    = $pagesize * $pageno - $pagesize;

    $param = [];
    $where = ' WHERE 1 ';
    foreach ($vars as $var => $value) {
        if ($value != '') {
            switch ($var) {
                case 'device':
                case 'device_id':
                    $where .= generate_query_values_and($value, 'device_id');
                    break;
                case 'port':
                case 'port_id':
                    $where .= generate_query_values_and($value, 'port_id');
                    break;
                case 'ip_version':
                    $where .= generate_query_values_and($value, 'ip_version');
                    break;
                case 'address':
                    if (isset($vars['searchby']) && $vars['searchby'] === 'ip') {
                        $value = trim($value);
                        if (str_contains($value, ':')) {
                            if (get_ip_version($value) === 5) {
                                $value = ip_uncompress($value);
                            } else {
                                // FIXME. Need another conversion ("2001:b08:b08" -> "2001:0b08:0b08") -- mike
                            }
                        }
                        $where .= generate_query_values_and($value, 'ip_address', '%LIKE%');
                    } else {
                        // MAC Addresses
                        $value = str_replace([':', ' ', '-', '.', '0x'], '', $value);
                        $where .= generate_query_values_and($value, 'mac_address', '%LIKE%');
                    }
                    break;
            }
        }
    }

    $sort_order = get_sort_order($vars);

    if (isset($vars['sort'])) {
        switch ($vars['sort']) {
            case "port":
                //$sort = " ORDER BY `port_label`, `mac_ifIndex`";
                $sort = generate_query_sort(['-port_label', 'mac_ifIndex'], $sort_order);
                break;

            case "ip_version":
                //$sort = " ORDER BY `ip_version`";
                $sort = generate_query_sort('ip_version', $sort_order);
                break;

            case "ip":
            case "address":
                //$sort = " ORDER BY `ip_address`";
                $sort = generate_query_sort('ip_address', $sort_order);
                break;

            case "mac":
            default:
                //$sort = " ORDER BY `mac_address`";
                $sort = generate_query_sort('mac_address', $sort_order);

        }
    }

    // Show ARP tables only for permitted ports
    $query_permitted = generate_query_permitted(['port', 'device'], ['port_null' => TRUE]); // allow empty port_id

    $query       = 'FROM `ip_mac` ';
    $query       .= 'LEFT JOIN `ports` USING (`device_id`, `port_id`) ';
    $query       .= $where . $query_permitted;
    $query_count = 'SELECT COUNT(`mac_id`) ' . $query;
    $query       = 'SELECT * ' . $query;
    $query       .= $sort;
    $query       .= " LIMIT $start,$pagesize";

    // Query ARP/NDP table addresses
    $entries = dbFetchRows($query, $param);
    // Query ARP/NDP table address count
    if ($pagination) {
        $count = dbFetchCell($query_count, $param);
    }

    $list = ['device' => FALSE, 'port' => FALSE];
    if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] == 'search') {
        $list['device'] = TRUE;
    }
    if (!isset($vars['port']) || empty($vars['port']) || $vars['page'] == 'search') {
        $list['port'] = TRUE;
    }

    $string = generate_box_open();

    $string .= '<table class="table  table-striped table-hover table-condensed">' . PHP_EOL;

    $cols = [
      'mac'            => 'MAC Address',
      'ip'             => 'IP Address',
      'device'         => 'Device',
      'port'           => 'Port',
      '!remote_device' => 'Remote Device',
      '!remote_port'   => 'Remote Port',
    ];

    if (!$list['device']) {
        unset($cols['device']);
    }
    if (!$list['port']) {
        unset($cols['port']);
    }

    if (!$short) {
        $string .= get_table_header($cols, $vars); // Currently sorting is not available
    }

    foreach ($entries as $entry) {
        humanize_port($entry);
        $ip_version = $entry['ip_version'];
        $ip_address = ip_compress($entry['ip_address']);

        // $arp_host = dbFetchRow('SELECT * FROM `ipv'.$ip_version.'_addresses` AS A
        //                        LEFT JOIN `ports` AS I ON A.`port_id` = I.`port_id`
        //                        LEFT JOIN `devices` AS D ON D.`device_id` = I.`device_id`
        //                        WHERE A.`ipv'.$ip_version.'_address` = ?', array($ip_address));
        $arp_host = dbFetchRow('SELECT * FROM `ipv' . $ip_version . '_addresses`
                           WHERE `ipv' . $ip_version . '_address` = ?', [$ip_address]);

        if ($arp_host['device_id'] == $entry['device_id']) {
            $arp_name = 'Self Device';
        } else {
            $arp_name = $arp_host ? generate_device_link($arp_host) : '';
        }
        if ($entry['port_id'] && $arp_host['port_id'] == $entry['port_id']) {
            $arp_if = 'Self Port';
        } else {
            $arp_if = $arp_host ? generate_port_link($arp_host) : '';
        }

        $string .= '  <tr>' . PHP_EOL;
        $string .= '    <td style="width: 160px;" class="entity">' . generate_popup_link('mac', format_mac($entry['mac_address'])) . '</td>' . PHP_EOL;
        $string .= '    <td style="width: 140px;">' . generate_popup_link('ip', $ip_address) . '</td>' . PHP_EOL;
        if ($list['device']) {
            $dev    = device_by_id_cache($entry['device_id']);
            $string .= '    <td class="entity" style="white-space: nowrap;">' . generate_device_link($dev) . '</td>' . PHP_EOL;
        }
        if ($list['port']) {
            if ($entry['ifInErrors_delta'] > 0 || $entry['ifOutErrors_delta'] > 0) {
                $port_error = generate_port_link($entry, '<span class="label label-important">Errors</span>', 'port_errors');
            }
            if ($entry['port_id']) {
                $port_string = generate_port_link_short($entry) . ' ' . $port_error;
            } else {
                // For VRF port_id can be unknown
                $port_string = 'ifIndex ' . $entry['mac_ifIndex'];
            }
            $string .= '    <td class="entity">' . $port_string . '</td>' . PHP_EOL;
        }
        $string .= '    <td class="entity" style="width: 200px;">' . $arp_name . '</td>' . PHP_EOL;
        $string .= '    <td class="entity">' . $arp_if . '</td>' . PHP_EOL;
        $string .= '  </tr>' . PHP_EOL;
    }

    $string .= '  </tbody>' . PHP_EOL;
    $string .= '</table>';

    $string .= generate_box_close();

    // Print pagination header
    if ($pagination) {
        $string = pagination($vars, $count) . $string . pagination($vars, $count);
    }

    // Print ARP/NDP table
    echo $string;
}

// EOF
