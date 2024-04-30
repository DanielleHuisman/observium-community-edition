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

/**
 * Display neighbours.
 *
 * Display pages with device neighbours in some formats.
 * Examples:
 * print_neighbours() - display all neighbours from all devices
 * print_neighbours(array('pagesize' => 99)) - display 99 neighbours from all device
 * print_neighbours(array('pagesize' => 10, 'pageno' => 3, 'pagination' => TRUE)) - display 10 neighbours from page 3 with pagination header
 * print_neighbours(array('pagesize' => 10, 'device' = 4)) - display 10 neighbours for device_id 4
 *
 * @param array $vars
 *
 * @return none
 *
 */
function print_neighbours($vars)
{
    // Get neighbours array
    $neighbours = get_neighbours_array($vars);

    if (!$neighbours['count']) {
        // There have been no entries returned. Print the warning.
        print_warning('<h4>No neighbours found!</h4>');
    } else {
        // Entries have been returned. Print the table.
        $list = [ 'device' => FALSE ];
        if ($vars['page'] !== 'device') {
            $list['device'] = TRUE;
        }
        if (in_array($vars['graph'], ['bits', 'upkts', 'nupkts', 'pktsize', 'percent', 'errors', 'etherlike', 'fdb_count'])) {
            $graph_types = [$vars['graph']];
        } else {
            $graph_types = ['bits', 'upkts', 'errors'];
        }

        $string = generate_box_open($vars['header']);

        $string .= '<table class="table table-hover table-striped table-condensed">' . PHP_EOL;

        $cols = [
            [ NULL, 'class="state-marker"' ],
            'device_a'    => 'Local Device',
            'port_a'      => 'Local Port',
            'NONE'        => NULL,
            'device_b'    => 'Remote Device',
            'port_b'      => 'Remote Port',
            'protocol'    => 'Protocol',
            'last_change' => 'Last changed',
        ];
        if ($_SESSION['userlevel'] > 7) {
            $cols[] = ''; // 'Autodiscovery'
        }
        if (!$list['device']) {
            unset($cols[0], $cols['device_a']);
        }
        $string .= get_table_header($cols, $vars);

        $string .= '  <tbody>' . PHP_EOL;

        $protocol_classmap = [
            'cdp'  => 'success',
            'lldp' => 'warning',
            'amap' => 'primary',
            'mndp' => 'error',
            'fdp'  => 'delayed',
            'edp'  => 'suppressed'
        ];

        foreach ($neighbours['entries'] as $entry) {
            $string .= '  <tr class="' . $entry['row_class'] . '">' . PHP_EOL;

            if ($list['device']) {
                $string .= '   <td class="state-marker"></td>';
                $string .= '    <td><span class="entity">' . generate_device_link($entry, NULL, [ 'tab' => 'ports', 'view' => 'neighbours' ]) . '</span></td>' . PHP_EOL;
            }
            $port   = get_port_by_id_cache($entry['port_id']);
            $string .= '    <td><span class="entity">' . generate_port_link($port) . '</span><br />' . escape_html($port['ifAlias']) . '</td>' . PHP_EOL;
            $string .= '    <td><i class="icon-resize-horizontal text-success"></i></td>' . PHP_EOL;

            //$string  .= "<td></td><td></td>";

            //r($entry); //r($entry['remote_port']);

            if (is_intnum($entry['remote_device_id']) || is_intnum($entry['remote_port_id'])) {
                $remote_port = $entry['remote_port_id'] ? get_port_by_id_cache($entry['remote_port_id']) : [];
                if ($entry['remote_device_id']) {
                    $remote_device = device_by_id_cache($entry['remote_device_id']);
                } else {
                    $remote_device = device_by_id_cache($remote_port['device_id']);
                }
                $remote_info   = !safe_empty($remote_device['hardware']) ? '<br />' . escape_html($remote_device['hardware']) : '';
                if (!safe_empty($remote_device['version'])) {
                    $remote_info .= '<br /><small><i>' . $GLOBALS['config']['os'][$remote_device['os']]['text'] . '&nbsp;' .
                                    escape_html($remote_device['version']) . '</i></small>';
                }
                if (empty($remote_info)) {
                    $remote_info = '<br />' . escape_html(truncate($entry['remote_platform'], '100'));
                    if (!safe_empty($entry['remote_version'])) {
                        $remote_info .= '<br /><small><i>' . escape_html(truncate($entry['remote_version'], 150)) . '</i></small>';
                    }
                }
                $string .= '    <td><span class="entity">' . generate_device_link($remote_device) . '</span>' . $remote_info . '</td>' . PHP_EOL;
                if ($remote_port) {
                    $string .= '    <td><span class="entity">' . generate_port_link($remote_port) . '</span><br />' . escape_html($remote_port['ifAlias']) . '</td>' . PHP_EOL;
                } else {
                    $string .= '    <td><span class="entity">' . escape_html($entry['remote_port']) . '</span></td>' . PHP_EOL;
                }
            } else {
                $remote_ip      = !safe_empty($entry['remote_address']) ? ' (' . generate_popup_link('ip', $entry['remote_address']) . ')' : '';
                $remote_version = !safe_empty($entry['remote_version']) ? ' <br /><small><i>' . escape_html(truncate($entry['remote_version'], 150)) . '</i></small>' : '';

                $string .= '    <td><span class="entity">' . escape_html($entry['remote_hostname']) . $remote_ip . '</span><br />';
                $string .= escape_html(truncate($entry['remote_platform'], '100')) . $remote_version . PHP_EOL;
                $string .= '</td>';
                $string .= '    <td><span class="entity">' . escape_html($entry['remote_port']) . '</span></td>' . PHP_EOL;
            }

            if (isset($protocol_classmap[$entry['protocol']])) {
                $entry['protocol_class'] = 'label-' . $protocol_classmap[$entry['protocol']];
            }

            $string .= '    <td><span class="label ' . $entry['protocol_class'] . '">' . strtoupper($entry['protocol']) . '</span></td>' . PHP_EOL;
            $string .= '    <td class="text-nowrap">' . format_uptime(get_time() - $entry['last_change_unixtime'], 'shorter') . ' ago</td>' . PHP_EOL;
            if ($_SESSION['userlevel'] > 7) {
                $string .= '    <td>' . generate_popup_link('autodiscovery', $entry['autodiscovery_id']) . '</td>' . PHP_EOL;
            }
            $string .= '  </tr>' . PHP_EOL;
        }

        $string .= '  </tbody>' . PHP_EOL;
        $string .= '</table>';

        $string .= generate_box_close();

        // Print pagination header
        if ($neighbours['pagination_html']) {
            $string = $neighbours['pagination_html'] . $string . $neighbours['pagination_html'];
        }

        // Print
        echo $string;
    }
}

/**
 * Params:
 *
 * pagination, pageno, pagesize
 * device, port
 */
function get_neighbours_array($vars)
{
    $array = [];

    // With pagination? (display page numbers in header)
    $array['pagination'] = isset($vars['pagination']) && $vars['pagination'];
    pagination($vars, 0, TRUE); // Get default pagesize/pageno
    $array['pageno']   = $vars['pageno'];
    $array['pagesize'] = $vars['pagesize'];
    $start             = $array['pagesize'] * $array['pageno'] - $array['pagesize'];
    $pagesize          = $array['pagesize'];

    // Active by default
    if (!isset($vars['active'])) {
        $vars['active'] = '1';
    } elseif ($vars['active'] === 'any') {
        unset($vars['active']);
    }

    // Begin query generate
    $param = [];
    $where = ' WHERE 1 ';
    $where_array = [];
    foreach ($vars as $var => $value) {
        if (!safe_empty($value)) {
            switch ($var) {
                case 'device':
                case 'device_id':
                case 'device_a':
                    $where_array[] = generate_query_values($value, 'device_id');
                    break;
                case 'port':
                case 'port_id':
                case 'port_a':
                    $where_array[] = generate_query_values($value, 'port_id');
                    break;
                case 'remote':
                case 'device_b':
                    $where_array[] = generate_query_values($value, 'remote_hostname');
                    break;
                case 'port_b':
                    $where_array[] = generate_query_values($value, 'remote_port');
                    break;
                case 'protocol':
                    $where_array[] = generate_query_values($value, 'protocol');
                    break;
                case 'platform':
                    $where_array[] = generate_query_values($value, 'remote_platform');
                    break;
                case 'version':
                    $where_array[] = generate_query_values($value, 'remote_version');
                    break;
                case 'active':
                    $value = !get_var_false($value, 'no') ? 1 : 0;
                    $where_array[] = generate_query_values($value, 'active');
                    break;
                case 'known':
                    if ($value === 'NULL' || $value == 0) {
                        $where_array[] = 'ISNULL(`remote_port_id`)';
                    } else {
                        $where_array[] = '!ISNULL(`remote_port_id`)';
                    }
                    break;
            }
        }
    }

    // Show neighbours only for permitted devices and ports
    //$query_permitted = $GLOBALS['cache']['where']['ports_permitted'];
    $query_permitted = generate_query_permitted_ng([ 'ports', 'devices' ]);

    if ($vars['sort'] === 'port_a') {
        $query = 'SELECT `neighbours`.*, UNIX_TIMESTAMP(`last_change`) AS `last_change_unixtime`, `ports`.`port_label` ';
        $query .= 'FROM `neighbours` LEFT JOIN `ports` USING(`port_id`,`device_id`) ';
    } else {
        $query = 'SELECT `neighbours`.*, UNIX_TIMESTAMP(`last_change`) AS `last_change_unixtime` ';
        $query .= 'FROM `neighbours` ';
    }

    $query .= generate_where_clause($where_array, $query_permitted);

    // Query neighbours
    $array['entries'] = dbFetchRows($query, $param);
    //r($array['entries']);
    foreach ($array['entries'] as &$entry) {
        $device = device_by_id_cache($entry['device_id']);
        if (!$entry['active']) {
            $entry['row_class'] = 'disabled';
        } elseif ((isset($device['status']) && !$device['status'])) {
            $entry['row_class'] = 'error';
        } elseif (isset($device['disabled']) && $device['disabled']) {
            $entry['row_class'] = 'ignore';
        }
        $entry['hostname'] = $device['hostname'];
        //$entry['row_class'] = $device['row_class'];
    }

    // Sorting
    // FIXME. Sorting can be as function, but in must before print_table_header and after get table from db
    switch ($vars['sort_order']) {
        case 'desc':
            $sort_order = SORT_DESC;
            $sort_neg   = SORT_ASC;
            break;
        case 'reset':
            unset($vars['sort'], $vars['sort_order']);
        // no break here
        default:
            $sort_order = SORT_ASC;
            $sort_neg   = SORT_DESC;
    }
    switch ($vars['sort']) {
        case 'device_a':
            $array['entries'] = array_sort_by($array['entries'], 'hostname', $sort_order, SORT_STRING);
            break;
        case 'port_a':
            $array['entries'] = array_sort_by($array['entries'], 'port_label', $sort_order, SORT_STRING);
            break;
        case 'device_b':
            $array['entries'] = array_sort_by($array['entries'], 'remote_hostname', $sort_order, SORT_STRING);
            break;
        case 'port_b':
            $array['entries'] = array_sort_by($array['entries'], 'remote_port', $sort_order, SORT_STRING);
            break;
        case 'protocol':
            $array['entries'] = array_sort_by($array['entries'], 'protocol', $sort_order, SORT_STRING);
            break;
        case 'last_change':
            $array['entries'] = array_sort_by($array['entries'], 'last_change_unixtime', $sort_order, SORT_NUMERIC);
            break;
        default:
            // Not sorted
    }

    // Query neighbours count
    $array['count'] = safe_count($array['entries']);
    if ($array['pagination']) {
        $array['pagination_html'] = pagination($vars, $array['count']);
        $array['entries']         = array_chunk($array['entries'], $vars['pagesize']);
        $array['entries']         = $array['entries'][$vars['pageno'] - 1];
    }

    // Query for last timestamp
    //$array['updated'] = dbFetchCell($query_updated, $param);

    return $array;
}

// EOF
