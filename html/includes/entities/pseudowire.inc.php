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

function generate_pseudowire_query($vars)
{
    $sql = 'SELECT * FROM `pseudowires` ';

    $where = [];

    $where[] = $GLOBALS['cache']['where']['devices_permitted'];

    // Build query
    foreach ($vars as $var => $value) {
        switch ($var) {
            case "group":
            case "group_id":
                $values  = get_group_entities($value);
                $where[] = generate_query_values($values, 'pseudowire_id');
                break;
            case 'device_group_id':
            case 'device_group':
                $values  = get_group_entities($value, 'device');
                $where[] = generate_query_values($values, 'device_id');
                break;
            case "device":
            case "device_id":
                $where[] = generate_query_values($value, 'device_id');
                break;
            case "port":
            case "port_id":
                $where[] = generate_query_values($value, 'port_id');
                break;
            case "id":
                $where[] = generate_query_values($value, 'pseudowire_id');
                break;
            case "pwid":
            case "pwID":
                $where[] = generate_query_values($value, 'pwID');
                break;
            case "pwtype":
                $where[] = generate_query_values($value, 'pwType');
                break;
            case "psntype":
                $where[] = generate_query_values($value, 'pwPsnType');
                break;
            case "peer_id":
                $where[] = generate_query_values($value, 'peer_device_id');
                break;
            case "peer_addr":
                $where[] = generate_query_values($value, 'peer_addr');
                break;
            case "event":
                $where[] = generate_query_values($value, 'event');
                break;
        }
    }

    $sql .= generate_where_clause($where);

    return $sql;
}

function print_pseudowire_table_header($vars)
{
    if ($vars['view'] == "graphs" || isset($vars['graph']) || isset($vars['id'])) {
        $table_class = OBS_CLASS_TABLE_STRIPED_TWO;
    } else {
        $table_class = OBS_CLASS_TABLE_STRIPED;
    }

    echo('<table class="' . $table_class . '">' . PHP_EOL);
    $cols = [
      [NULL, 'class="state-marker"'],
      'pwid'        => ['pwID', 'style="width: 60px; text-align: right;"'],
      'pwtype'      => ['Type / PSN Type', 'style="width: 100px;"'],
      'device'      => ['Local Device', 'style="width: 180px;"'],
      'port'        => ['Local Port', 'style="width: 100px;"'],
      [NULL, 'style="width: 20px;"'], // arrow icon
      'peer_addr'   => ['Remote Peer', 'style="width: 180px;"'],
      'peer_port'   => ['Remote Port', 'style="width: 100px;"'],
      ['History', 'style="width: 100px;"'],
      'last_change' => ['Last&nbsp;changed', 'style="width: 60px;"'],
      'event'       => ['Event', 'style="width: 60px; text-align: right;"'],
      'status'      => ['Status', 'style="width: 60px; text-align: right;"'],
      'uptime'      => ['Uptime', 'style="width: 80px;"'],
    ];

    if ($vars['page'] == "device" || $vars['popup'] == TRUE) {
        unset($cols['device']);
    }

    echo(get_table_header($cols, $vars));
    echo('<tbody>' . PHP_EOL);
}

function get_pseudowire_table($vars)
{
    $sql = generate_pseudowire_query($vars);

    $entries = [];
    foreach (dbFetchRows($sql) as $entry) {
        if (!$device = device_by_id_cache($entry['device_id'])) {
            continue;
        }

        // Device hostname
        $entry['hostname'] = $device['hostname'];

        // Remote Peer
        $peer_addr      = $entry['peer_addr'];
        $peer_addr_type = get_ip_version($peer_addr);
        if ($peer_addr_type && $entry['peer_device_id']) {
            if ($peer_addr_type == 6) {
                $peer_addr = Net_IPv6 ::uncompress($peer_addr, TRUE);
            }
            $peer_addr_type = 'ipv' . $peer_addr_type;
            //$entry['peer_addr']      = $peer_addr;
            //$entry['peer_addr_type'] = $peer_addr_type;

            if (!is_array($cache_pseudowires['ips'][$peer_addr])) {
                $cache_pseudowires['ips'][$peer_addr]['port_id'] = dbFetchCell('SELECT `port_id` FROM `' . $peer_addr_type . '_addresses` WHERE `' . $peer_addr_type . '_address` = ? ' . generate_query_values_and($GLOBALS['cache']['ports']['pseudowires'], 'port_id') . ' LIMIT 1;', [$peer_addr]);
                if (!is_numeric($cache_pseudowires['ips'][$peer_addr]['port_id'])) {
                    // Separate entry for find correct port
                    $cache_pseudowires['ips'][$peer_addr]['port_id_fix'] = dbFetchCell('SELECT `port_id` FROM `' . $peer_addr_type . '_addresses` ' . generate_where_clause($peer_addr_type . '_address` = ?', $GLOBALS['cache']['where']['ports_permitted']) . ' LIMIT 1;', [$peer_addr]);
                }
                //$cache_pseudowires['ips'][$peer_addr]['host'] = $entry['reverse_dns'];
            }
            $entry['peer_port_id'] = $cache_pseudowires['ips'][$peer_addr]['port_id'];
            if (is_numeric($cache_pseudowires['ips'][$peer_addr]['port_id_fix'])) {
                // If we found port on remote device, than both devices in DB and will try to fix real port
                $peer_port_tmp = get_port_by_id_cache($cache_pseudowires['ips'][$peer_addr]['port_id_fix']);
                $peer_port_fix = dbFetchCell('SELECT `port_id` FROM `pseudowires` WHERE `device_id` = ? AND `pwID` = ? LIMIT 1;', [$peer_port_tmp['device_id'], $entry['pwID']]);
                if (is_numeric($peer_port_fix)) {
                    $entry['peer_port_id'] = $peer_port_fix;
                } else {
                    $entry['peer_port_id'] = $cache_pseudowires['ips'][$peer_addr]['port_id_fix'];
                }
            }
            //r($entry['peer_port_id']);
            if ($entry['peer_port_id']) {
                $entry['peer_port'] = get_port_by_id_cache($entry['peer_port_id']);
                //r($entry['peer_port']);
                $entry['peer_device_id'] = $entry['peer_port']['device_id'];
                //r($entry['peer_device_id']);
                $entry['peer_device'] = device_by_id_cache($entry['peer_device_id']);
            }
        }

        $entries[] = $entry;
    }

    // Sorting
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
        case 'device':
            $entries = array_sort_by($entries, 'hostname', $sort_order, SORT_STRING);
            break;
        case 'pwid':
            $entries = array_sort_by($entries, 'pwID', $sort_order, SORT_NUMERIC);
            break;
        case 'pwtype':
            $entries = array_sort_by($entries, 'pwType', $sort_order, SORT_STRING, 'pwPsnType', $sort_order, SORT_STRING);
            //$pws = array_sort_by($pws, 'pwType',  $sort_order, SORT_STRING);
            break;
        case 'peer_addr':
            $entries = array_sort_by($entries, 'peer_addr', $sort_order, SORT_NUMERIC);
            break;
        case 'event':
            $entries = array_sort_by($entries, 'event', $sort_order, SORT_STRING);
            break;
        case 'uptime':
            $entries = array_sort_by($entries, 'pwUptime', $sort_order, SORT_NUMERIC);
            break;
        case 'last_change':
            $entries = array_sort_by($entries, 'last_change', $sort_neg, SORT_NUMERIC);
            break;
        case 'status':
            $entries = array_sort_by($entries, 'pwOperStatus', $sort_order, SORT_STRING);
            break;
        default:
            // Not sorted
    }

    return $entries;
}

function print_pseudowire_table($vars)
{
    $pws       = get_pseudowire_table($vars);
    $pws_count = count($pws);

    // Pagination
    $pagination_html = pagination($vars, $pws_count);
    echo $pagination_html;

    if ($vars['pageno']) {
        $pws = array_chunk($pws, $vars['pagesize']);
        $pws = $pws[$vars['pageno'] - 1];
    }
    // End Pagination

    echo generate_box_open();

    print_pseudowire_table_header($vars);

    foreach ($pws as $pw) {
        print_pseudowire_row($pw, $vars);
    }

    echo '</tbody></table>';

    echo generate_box_close();

    echo $pagination_html;
}

function humanize_pseudowire(&$pw)
{
    global $config;

    if (isset($pw['humanized'])) {
        return;
    }

    if (isset($config['entity_events'][$pw['event']])) {
        $pw = array_merge($pw, $config['entity_events'][$pw['event']]);
    } else {
        $pw['event_class'] = 'label label-primary';
        $pw['row_class']   = '';
    }
    if ($pw['pwRowStatus'] != 'active') {
        $pw['row_class'] = 'ignore';
    }

    $device = device_by_id_cache($pw['device_id']);

    if ((isset($device['status']) && !$device['status']) || (isset($device['disabled']) && $device['disabled'])) {
        $pw['row_class'] = 'error';
    }

    $translate = entity_type_translate_array('pseudowire');

    $pw['graph'] = $translate['graph']['type']; // Default graph

    $pw['humanized'] = TRUE;
}

function print_pseudowire_row($pw, $vars)
{
    echo generate_pseudowire_row($pw, $vars);
}

function generate_pseudowire_row($pw, $vars)
{
    global $config;

    humanize_pseudowire($pw);

    $table_cols = 11;

    $graph_array           = [];
    $graph_array['to']     = get_time();
    $graph_array['id']     = $pw['pseudowire_id'];
    $graph_array['type']   = $pw['graph'];
    $graph_array['legend'] = "no";
    $graph_array['width']  = 80;
    $graph_array['height'] = 20;
    $graph_array['bg']     = 'ffffff00';
    $graph_array['from']   = get_time('day');

    if ($pw['event'] && $pw['pwOperStatus']) {
        $mini_graph = generate_graph_tag($graph_array);
    } else {
        // Do not show "Draw Error" minigraph
        $mini_graph = '';
    }

    $out = '<tr class="' . $pw['row_class'] . '"><td class="state-marker"></td>';
    $out .= '<td class="entity" style="text-align: right;">' . generate_entity_link('pseudowire', $pw, NULL, NULL, TRUE, TRUE) . '</td>';
    $out .= '<td>' . nicecase($pw['pwType']) . '/' . nicecase($pw['pwPsnType']) . '</td>';
    if ($vars['page'] != "device" && $vars['popup'] != TRUE) {
        $out .= '<td class="entity">' . generate_device_link($pw, NULL, ['tab' => 'pseudowires']) . '</td>';
        $table_cols++;
    }
    $out .= '<td class="entity">' . generate_entity_link('port', $pw['port_id']) . '</td>';
    $out .= '<td><span class="text-success"><i class="glyphicon glyphicon-arrow-right"></i></span></td>';
    if ($pw['peer_port_id']) {
        $out .= '<td class="entity">' . generate_entity_link('device', $pw['peer_device_id']) . '</td>';
        $out .= '<td class="entity">' . generate_entity_link('port', $pw['peer_port_id']) . '</td>';
    } else {
        $out .= '<td class="entity">' . generate_popup_link('ip', $pw['peer_addr']) . '</td>';
        $out .= '<td>' . $pw['pwRemoteIfString'] . '</td>';
    }
    $out .= '<td>' . generate_entity_link('pseudowire', $pw, $mini_graph, NULL, FALSE) . '</td>';
    $out .= '<td style="white-space: nowrap">' . generate_tooltip_link(NULL, format_uptime((get_time('now') - $pw['last_change']), 'short-2') . ' ago', format_unixtime($pw['last_change'])) . '</td>';
    $out .= '<td style="text-align: right;"><strong>' . generate_tooltip_link('', $pw['event'], $pw['event_descr'], $pw['event_class']) . '</strong></td>';
    $out .= '<td style="text-align: right;"><strong>' . generate_tooltip_link('', $pw['pwOperStatus'], $pw['event_descr'], $pw['event_class']) . '</strong></td>';
    $out .= '<td>' . format_uptime($pw['pwUptime'], 'short-2') . '</td>';
    $out .= '</tr>';

    if ($vars['graph'] || $vars['view'] == "graphs" || $vars['id'] == $pw['pseudowire_id']) {
        // If id set in vars, display only specific graphs
        $graph_array         = [];
        $graph_array['type'] = ($vars['graph'] ? $vars['graph'] : $pw['graph']);
        $graph_array['id']   = $pw['pseudowire_id'];

        $out .= '<tr class="' . $pw['row_class'] . '">';
        $out .= '  <td class="state-marker"></td>';
        $out .= '  <td colspan="' . $table_cols . '">';
        $out .= generate_graph_row($graph_array, TRUE);
        $out .= '  </td>';
        $out .= '</tr>';
    }

    return $out;
}

function print_pseudowire_form($vars, $single_device = FALSE)
{
    //global $config;

    $form = ['type'          => 'rows',
             'space'         => '10px',
             'submit_by_key' => TRUE,
             'url'           => generate_url($vars)];

    $form_items = [];

    if ($single_device) {
        // Single device, just hidden field
        $form['row'][0]['device_id'] = [
          'type'  => 'hidden',
          'name'  => 'Device',
          'value' => $vars['device_id'],
          'grid'  => 2,
          'width' => '100%'];
    } else {
        $form_items['devices'] = generate_form_values('device', dbFetchColumn('SELECT DISTINCT `device_id` FROM `pseudowires`'));

        $form['row'][0]['device_id'] = [
          'type'   => 'multiselect',
          'name'   => 'Device',
          'value'  => $vars['device_id'],
          'grid'   => 2,
          'width'  => '100%', //'180px',
          'values' => $form_items['devices']];
    }

    // Ports
    $form_items['ports'] = generate_form_values('ports', dbFetchColumn('SELECT DISTINCT `port_id` FROM `pseudowires`'));

    $form['row'][0]['port_id'] = [
      'type'   => 'multiselect',
      'name'   => 'Local Port',
      'value'  => $vars['port_id'],
      'grid'   => 2,
      'width'  => '100%', //'180px',
      'values' => $form_items['ports']];

    // Peer Address
    $form_items['ports'] = generate_form_values('port', dbFetchColumn('SELECT DISTINCT `peer_addr` FROM `pseudowires`'));

    $form['row'][0]['port_id'] = [
      'type'   => 'multiselect',
      'name'   => 'Local Port',
      'value'  => $vars['port_id'],
      'grid'   => 2,
      'width'  => '100%', //'180px',
      'values' => $form_items['ports']];


    // Groups
    foreach (get_type_groups('pseudowire') as $entry) {
        $form_items['group'][$entry['group_id']] = $entry['group_name'];
    }
    $form['row'][0]['group'] = [
      'community' => FALSE,
      'type'      => 'multiselect',
      'name'      => 'Select Groups',
      'width'     => '100%', //'180px',
      'grid'      => 2,
      'value'     => $vars['group'],
      'values'    => $form_items['group']];

    $form['row'][0]['search'] = [
      'type'  => 'submit',
      'grid'  => 2,
      //'name'        => 'Search',
      //'icon'        => 'icon-search',
      'right' => TRUE,
    ];

    // Show search form
    echo '<div class="hidden-xl">';
    print_form($form);
    echo '</div>';

    // Custom panel form
    $panel_form = ['type'          => 'rows',
                   'title'         => 'Search Processors',
                   'space'         => '10px',
                   //'brand' => NULL,
                   //'class' => '',
                   'submit_by_key' => TRUE,
                   'url'           => generate_url($vars)];

    // Clean grids
    foreach ($form['row'] as $row => $rows) {
        foreach (array_keys($rows) as $param) {
            if (isset($form['row'][$row][$param]['grid'])) {
                unset($form['row'][$row][$param]['grid']);
            }
        }
    }

    // Copy forms
    $panel_form['row'][0]['device_id'] = $form['row'][0]['device_id'];
    $panel_form['row'][0]['group']     = $form['row'][0]['group'];

    $panel_form['row'][3]['processor_descr'] = $form['row'][0]['processor_descr'];

    $panel_form['row'][5]['search'] = $form['row'][0]['search'];

    // Register custom panel
    register_html_panel(generate_form($panel_form));
}


// EOF
