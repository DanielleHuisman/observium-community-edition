<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) Adam Armstrong
 *
 */

function generate_oid_template_link($entry) {
    $url = generate_url([ 'page' => 'customoid', 'oid_id' => $entry['oid_id'] ]);

    return '<a href="' . $url . '">' . escape_html($entry['oid_descr']) . '</a>';
}

function build_oid_query($vars) {
    $sql = 'SELECT * FROM `oids_entries` LEFT JOIN `oids` USING (`oid_id`)';

    //if ($vars['sort'] == 'hostname' || $vars['sort'] == 'device' || $vars['sort'] == 'device_id') {
    $sql .= ' LEFT JOIN `devices` USING(`device_id`)';
    //}

    // Build query
    $where_array = [];
    foreach ($vars as $var => $value) {
        switch ($var) {
            case "oid_id":
            case "oid_descr":
            case "oid":
            case "oid_name":
                $where_array[] = generate_query_values($value, $var);
                break;

            case "group":
            case "group_id":
                $values = get_group_entities($value);
                $where_array[] = generate_query_values($values, 'oid_entry_id');
                break;

            case 'device_group_id':
            case 'device_group':
                $values = get_group_entities($value, 'device');
                $where_array[] = generate_query_values($values, 'oids_entries.device_id');
                break;

            case "device":
            case "device_id":
                $where_array[] = generate_query_values($value, 'oids_entries.device_id');
                break;
        }
    }
    $sql .= generate_where_clause($where_array, generate_query_permitted_ng(['device']));

    if (isset($vars['sort'])) {
        $sort_order = get_sort_order($vars);
        switch ($vars['sort']) {
            case 'value':
            case 'oid_descr':
            case 'oid_name':
            case 'oid':
            case 'event':
                $sort = generate_query_sort($vars['sort'], $sort_order);
                break;

            default:
                $sort = generate_query_sort('hostname', $sort_order);
        }
    } else {
        $sort = generate_query_sort('hostname');
    }

    return $sql . $sort;
}

function print_oid_table_header($vars, $entries) {

    echo('<table class="' . (get_var_true($vars['graphs']) ? OBS_CLASS_TABLE_STRIPED_TWO : OBS_CLASS_TABLE_STRIPED) . '">');

    $cols[]           = ['', 'class="state-marker"'];
    $cols['hostname'] = ['Device', 'style="width: 280px;"'];
    if (!isset($vars['oid_id'])) {
        $cols['oid_descr'] = ['OID Description'];
    } else {
        $cols[] = [''];
    }
    $cols[]        = ['', 'style="width: 140px;"'];
    $cols[]        = ['Thresholds', 'style="width: 100px;"'];
    $cols['value'] = ['Value', 'style="width: 80px;"'];
    $cols['event'] = ['Event', 'style="width: 60px;"'];

    if ($entries[0]['oid_autodiscover'] == '0' && $vars['page'] === "customoid") {
        $cols['actions'] = ['', 'style="width: 40px;"'];
    }

    echo get_table_header($cols, $vars);
    echo '<tbody>' . PHP_EOL;
}

function print_oid_table($vars) {
    global $config;

    $sql     = build_oid_query($vars);
    $entries = dbFetchRows($sql);

    if (safe_empty($entries)) {
        print_box("<strong>This Custom OID is not currently associated with any devices</strong>", 'warning');

        return;
    }

    echo generate_box_open();

    print_oid_table_header($vars, $entries);

    foreach ($entries as $device_id => $entry) {
        //$device = device_by_id_cache($device_id);

        if (!is_numeric($entry['value'])) {
            $entry['human_value'] = 'NaN';
        } else {
            if ($entry['oid_kibi'] == 1) {
                $entry['human_value'] = format_value($entry['value'], 'bi') . $entry['oid_symbol'];
            } else {
                $entry['human_value'] = format_value($entry['value'], 'si') . $entry['oid_symbol'];
            }
        }

        $graph_array           = [];
        $graph_array['to']     = get_time();
        $graph_array['id']     = $entry['oid_entry_id'];
        $graph_array['type']   = "customoid_graph";
        $graph_array['width']  = 100;
        $graph_array['height'] = 20;
        $graph_array['from']   = get_time('day');

        if (is_numeric($entry['value']) || TRUE) {
            $mini_graph = generate_graph_tag($graph_array);
        } else {
            // Do not show "Draw Error" minigraph
            $mini_graph = '';
        }

        $thresholds = threshold_string($entry['alert_low'], $entry['warn_low'], $entry['warn_high'],
                                       $entry['alert_high'], $entry['oid_symbol']);

        switch ($entry['event']) {
            case "ok";
                $entry['html_row_class'] = "up";
                $entry['event_class']    = "success";
                break;
            case "warn";
                $entry['html_row_class'] = "warning";
                $entry['event_class']    = "warning";
                break;
            case "alert";
                $entry['html_row_class'] = "error";
                $entry['event_class']    = "error";
                break;
            case "ignore";
            default:
                $entry['html_row_class'] = "ignore";
                $entry['event_class']    = "ignore";
                break;
        }

        $event = '<span class="label label-' . $entry['event_class'] . '">' . $entry['event'] . '</span>';

        echo '
          <tr class="' . $entry['html_row_class'] . '">
            <td class="state-marker"></td>
            <td><i class="' . $config['entities']['device']['icon'] . '"></i> <b>' .
            generate_device_link($entry, NULL, [ 'tab' => 'graphs', 'group' => 'custom' ]) . '</b></td>';
        if (!isset($vars['oid_id'])) {
            echo '
            <td>' . generate_oid_template_link($entry) . '</td> ';
        } else {
            echo '
            <td></td>';
        }

        echo '
            <td>' . $mini_graph . '</td>
            <td>' . $thresholds . '</td>
            <td><span class="label label-' . $entry['event_class'] . '">' . $entry['human_value'] . '</span></td>
            <td>' . $event . '</td>
            ';

        if ($entries[0]['oid_autodiscover'] == '0' && $vars['page'] == "customoid") {

            $form                             = ['type'  => 'simple',
                                                 //'userlevel'  => 10,          // Minimum user level for display form
                                                 'id'    => 'delete_customoid_device_' . $entry['device_id'],
                                                 'style' => 'display:inline;',
            ];
            $form['row'][0]['form_oid_id']    = [
              'type'  => 'hidden',
              'value' => $entry['oid_id']];
            $form['row'][0]['form_device_id'] = [
              'type'  => 'hidden',
              'value' => $entry['device_id']];

            $form['row'][99]['action'] = [
              'type'      => 'submit',
              'icon_only' => TRUE, // hide button styles
              'name'      => '',
              'icon'      => $config['icon']['cancel'],
              //'right'       => TRUE,
              //'class'       => 'btn-small',
              // confirmation dialog
              'attribs'   => ['data-toggle'            => 'confirm', // Enable confirmation dialog
                              'data-confirm-placement' => 'left',
                              'data-confirm-content'   => 'Delete associated device "' . escape_html($entry['hostname']) . '"?',
              ],
              'value'     => 'delete_customoid_device'];

            echo('<td>');
            print_form($form);
            unset($form);
            echo('</td>');
        }

        echo '
          </tr>';

        if ($vars['graphs'] == "yes") {
            $vars['graph'] = "graph";
        }

        if ($vars['graph']) {
            $graph_array          = [];
            $graph_array['title'] = $entry['oid_descr'];
            $graph_array['type']  = "customoid_" . $vars['graph'];
            $graph_array['id']    = $entry['oid_entry_id'];

            echo '<tr><td colspan=8>';
            print_graph_row($graph_array);
            echo '</td></tr>';
        }

    }

    echo '  </table>' . PHP_EOL;

    echo generate_box_close();
}

// EOF
