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
 * Humanize status indicator.
 *
 * Returns a the $status array with processed information:
 * sensor_state (TRUE: state sensor, FALSE: normal sensor)
 * human_value, sensor_symbol, state_name, state_event, event_class
 *
 * @param array $status
 *
 * @return array $status
 *
 */
// TESTME needs unit testing
function humanize_status(&$status)
{
    global $config;

    // Exit if already humanized
    if ($status['humanized']) {
        return;
    }

    if (isset($config['entity_events'][$status['status_event']])) {
        $status = array_merge($status, $config['entity_events'][$status['status_event']]);
    } else {
        $status['event_class'] = 'label label-primary';
        $status['row_class']   = '';
    }
    if ($status['status_deleted']) {
        $status['row_class'] = 'disabled';
    }

    $device = device_by_id_cache($status['device_id']);
    if ((isset($device['status']) && !$device['status']) ||
        (isset($device['disabled']) && $device['disabled'])) {
        $status['row_class'] = $status['row_class'] !== 'disabled' ? 'error' : $status['row_class'];
    }
    // this is pseudo class only for js selectors
    $status['row_class'] .= ' entity-status';

    // Set humanized entry in the array so we can tell later
    $status['humanized'] = TRUE;
}

function generate_status_query($vars, $query_count = FALSE) {

    if ($query_count) {
        $sql = "SELECT COUNT(*) FROM `status`";
    } else {
        $sql = "SELECT * FROM `status`";

        if ($vars['sort'] == 'hostname' || $vars['sort'] == 'device' || $vars['sort'] == 'device_id') {
            $sql .= ' LEFT JOIN `devices` USING(`device_id`)';
        }
    }

    // Build query
    $where_array = [];
    foreach ($vars as $var => $value) {
        switch ($var) {
            case "group":
            case "group_id":
                $values = get_group_entities($value, 'status');
                $where_array[] = generate_query_values($values, 'status.status_id');
                break;

            case 'device_group_id':
            case 'device_group':
                $values = get_group_entities($value, 'device');
                $where_array[] = generate_query_values($values, 'status.device_id');
                break;

            case "device":
            case "device_id":
                $where_array[] = generate_query_values($value, 'status.device_id');
                break;

            case "id":
            case 'status_id':
                $where_array[] = generate_query_values($value, 'status.status_id');
                break;

            case "entity_id":
                $where_array[] = generate_query_values($value, 'measured_entity');
                break;

            case "entity_type":
                $where_array[] = generate_query_values($value, 'measured_class');
                break;

            case 'entity_state':
            case "measured_state":
                $where_array[] = generate_query_entity_measured('status', [ 'measured_state' => $value ]);
                break;

            case "class":
            case 'entPhysicalClass':
                $where_array[] = generate_query_values($value, 'entPhysicalClass');
                break;

            case "event":
            case "status_event":
                $where_array[] = generate_query_values($value, 'status_event');
                break;

            case "status":
            case "status_name":
                $where_array[] = generate_query_values($value, 'status_name');
                break;

            case "descr":
            case "status_descr":
                $where_array[] = generate_query_values($value, 'status_descr', '%LIKE%');
                break;

            case 'type':
            case "status_type":
                $where_array[] = generate_query_values($value, 'status_type', '%LIKE%');
                break;
        }
    }
    $where_array[] = '`status_deleted` = 0';

    $sql .= generate_where_clause($where_array, generate_query_permitted_ng([ 'device', 'status' ]));

    // If need count, just return sql without sorting
    if ($query_count) {
        return $sql;
    }

    if (isset($vars['sort'])) {
        switch ($vars['sort']) {
            case 'device':
                $sql .= generate_query_sort('hostname', get_sort_order($vars));
                break;

            case 'descr':
                $sql .= generate_query_sort('status_descr', get_sort_order($vars));
                break;

            case 'class':
            case 'entPhysicalClass':
                $sql .= generate_query_sort('entPhysicalClass', get_sort_order($vars));
                break;

            case 'event':
                $sql .= generate_query_sort('status_event', get_sort_order($vars));
                break;

            case 'status':
                $sql .= generate_query_sort('status_name', get_sort_order($vars));
                break;

            case 'last_change':
                $sql .= generate_query_sort('status_last_change', get_sort_order($vars, TRUE));
                break;

            case 'mib':
                $sql .= generate_query_sort([ 'status_mib', 'status_object', 'status_type', 'status_index:integer' ], get_sort_order($vars));
                break;

            default:
                $sql .= generate_query_sort([ 'measured_entity_label', 'status_descr' ], get_sort_order($vars));
        }
    } else {
        $sql .= generate_query_sort([ 'measured_entity_label', 'status_descr' ]);
    }

    if (isset($vars['pageno'])) {
        //$sql .= generate_query_limit($vars);
    }

    return $sql;
}


function print_status_table($vars)
{

    pagination($vars, 0, TRUE); // Get default pagesize/pageno

    $sql = generate_status_query($vars);

    $status_list = [];
    foreach (dbFetchRows($sql) as $status) {
        $status['hostname'] = get_device_hostname_by_id($status['device_id']);
        $status_list[]      = $status;
    }

    //$status_count = count($status_list); // This is count incorrect, when pagination used!
    $status_count = dbFetchCell(generate_status_query($vars, TRUE));

    // Pagination
    $pagination_html = pagination($vars, $status_count);
    echo $pagination_html;

    if ($vars['pageno']) {
        $status_list = array_chunk($status_list, $vars['pagesize']);
        $status_list = $status_list[$vars['pageno'] - 1];
    }
    // End Pagination

    echo generate_box_open();

    print_status_table_header($vars);

    foreach ($status_list as $status) {
        print_status_row($status, $vars);
    }

    echo("</tbody></table>");

    echo generate_box_close();

    echo $pagination_html;
}

function print_status_table_header($vars)
{
    if ($vars['view'] == "graphs" || isset($vars['id'])) {
        $stripe_class = "table-striped-two";
    } else {
        $stripe_class = "table-striped";
    }

    echo('<table class="table ' . $stripe_class . ' table-condensed ">' . PHP_EOL);
    $cols = [
      [NULL, 'class="state-marker"'],
      'device'      => ['Device', 'style="width: 250px;"'],
      //array(NULL, 'class="no-width"'), // Measure entity link
      'descr'       => ['Description'],
      'mib'         => ['MIB::Object'],
      'entPhysicalClass'       => ['Phys&nbsp;Class', 'style="width: 100px;"'],
      ['History', 'style="width: 90px;"'],
      'last_change' => ['Last&nbsp;changed', 'style="width: 80px;"'],
      'event'       => ['Event', 'style="width: 60px; text-align: right;"'],
      'status'      => ['Status', 'style="width: 80px; text-align: right;"'],
    ];

    if ($vars['page'] == "device") {
        unset($cols['device']);
    }
    if ($vars['page'] != "device" || $vars['tab'] == "overview") {
        unset($cols['mib']);
        unset($cols['object']);
    }

    echo(get_table_header($cols, $vars));
    echo('<tbody>' . PHP_EOL);
}

function print_status_row($status, $vars)
{
    echo generate_status_row($status, $vars);
}

function generate_status_row($status, $vars)
{

    $table_cols = 7;

    humanize_status($status);

    // FIXME - make this "four graphs in popup" a function/include and "small graph" a function.
    // FIXME - DUPLICATED IN device/overview/status

    $graph_array           = [];
    $graph_array['to']     = get_time();
    $graph_array['id']     = $status['status_id'];
    $graph_array['type']   = "status_graph";
    $graph_array['legend'] = "no";
    $graph_array['width']  = 80;
    $graph_array['height'] = 20;
    $graph_array['bg']     = 'ffffff00';
    $graph_array['from']   = get_time('day');

    //$status_misc = '<span class="label">' . $status['entPhysicalClass'] . '</span>';
    $short      = $vars['page'] === "device" && $vars['tab'] === "overview";
    $hide_style = $status['status_event'] === 'ignore' && $short ? ' style="display: none;"' : '';

    $row = '<tr class="' . $status['row_class'] . '"' . $hide_style . '>
        <td class="state-marker"></td>';

    if ($vars['page'] !== "device" && $vars['popup'] != TRUE) {
        $row .= '<td class="entity">' . generate_device_link($status) . '</td>';
        $table_cols++;
    }

    if ($status['status_event'] && $status['status_name']) {
        $mini_graph = generate_graph_tag($graph_array);
    } else {
        // Do not show "Draw Error" minigraph
        $mini_graph = '';
    }

    // Measured link & icon
    /* Disabled because it breaks the overview table layout
    $row .= '        <td style="padding-right: 0px;" class="no-width vertical-align">'; // minify column if empty
    if ($status['measured_entity'] &&
        (!isset($vars['measured_icon']) || $vars['measured_icon'])) // hide measured icon if not required
    {
      $row .= generate_entity_icon_link($status['measured_class'], $status['measured_entity']);
    }
    $row .= '</td>';
    $table_cols++;
    */

    $row .= '<td class="entity">' . generate_entity_link('status', $status) . '</td>';

    // FIXME -- Generify this. It's not just for sensors.
    if ($vars['page'] === "device" && $vars['tab'] !== "overview") {
        $row .= '      <td><span class="label-group">' . (!safe_empty($status['status_mib']) ? '<span class="label label-primary"><a href="' . OBSERVIUM_MIBS_URL . '/' . $status['status_mib'] . '/" target="_blank">' . nicecase($status['status_mib']) . '</a></span>' : '') .
            (!safe_empty($status['status_mib']) ? '<span class="label label-success"><a href="' . OBSERVIUM_MIBS_URL . '/' . $status['status_mib'] . '/#' . $status['status_object'] . '" target="_blank">' . $status['status_object'] . '</a></span>' : '') .
            '<span class="label label-delayed">' . $status['status_index'] . '</span></span></td>' . PHP_EOL;
        $table_cols++;
    }

    if ($vars['tab'] !== "overview") {
        $row .= '<td>' . get_type_class_label($status['entPhysicalClass'], 'physicalclass') . '</td>';
        $table_cols++;
    }
    $row .= '<td style="width: 90px; text-align: right;">' . generate_entity_link('status', $status, $mini_graph, NULL, FALSE) . '</td>';
    if ($vars['tab'] !== "overview") {
        $row .= '<td style="white-space: nowrap">' . generate_tooltip_time($status['status_last_change'], 'ago') . '</td>
        <td style="text-align: right;"><strong>' . generate_tooltip_link('', $status['status_event'], $status['event_descr'], $status['event_class']) . '</strong></td>';
        $table_cols++;
        $table_cols++;
    }
    $row .= '<td style="width: 80px; text-align: right;"><strong>' . generate_tooltip_link('', $status['status_name'], $status['event_descr'], $status['event_class']) . '</strong></td>
        </tr>' . PHP_EOL;

    if ($vars['view'] === "graphs") {
        $vars['graph'] = "status";
    }

    if ($vars['graph'] || $vars['id'] == $status['status_id']) {
        // If id set in vars, display only specific graphs
        $row .= '<tr class="' . $status['row_class'] . '">
      <td class="state-marker"></td>
      <td colspan="' . $table_cols . '">';

        unset($graph_array['height'], $graph_array['width'], $graph_array['legend']);
        $graph_array['to']   = get_time();
        $graph_array['id']   = $status['status_id'];
        $graph_array['type'] = "status_graph";

        $row .= generate_graph_row($graph_array, TRUE);

        $row .= '</td></tr>';
    } # endif graphs

    return $row;

}

function print_status_form($vars, $single_device = FALSE)
{
    global $config;

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
        $form_items['devices'] = generate_form_values('device', dbFetchColumn('SELECT DISTINCT `device_id` FROM `status`'));

        $form['row'][0]['device_id'] = [
          'type'   => 'multiselect',
          'name'   => 'Device',
          'value'  => $vars['device_id'],
          'grid'   => 2,
          'width'  => '100%', //'180px',
          'values' => $form_items['devices']];
    }

    $status_permitted = generate_query_permitted(['device', 'status']);
    foreach (['entPhysicalClass' => 'Physical Class', 'status_event' => 'Status Event', 'status_name' => 'Status'] as $param => $param_name) {
        $sql = 'SELECT DISTINCT `' . $param . '` FROM `status` WHERE `status_deleted` = ?' . $status_permitted;
        if ($entries = dbFetchColumn($sql, [0])) {
            asort($entries);
        }
        foreach ($entries as $entry) {
            if (safe_empty($entry)) {
                $entry = OBS_VAR_UNSET;
            }
            if ($param === 'entPhysicalClass') {
                $name = nicecase($entry);
                if (isset($config['icon'][strtolower($entry)])) {
                    $name = ['name' => $name, 'icon' => $config['icon'][strtolower($entry)]];
                } else {
                    $name = ['name' => $name, 'icon' => $config['icon']['status']];
                }
            } else {
                $name = $entry;
            }
            $form_items[$param][$entry] = $name;
        }

        // Alternative param name, ie event
        $short_param = str_replace('status_', '', $param);
        if (!isset($vars[$param]) && isset($vars[$short_param])) {
            $vars[$param] = $vars[$short_param];
        }

        // Status specific forms
        $form['row'][0][$param] = [
          'type'   => 'multiselect',
          'name'   => $param_name,
          'width'  => '100%', //'180px',
          'grid'   => 2,
          'value'  => $vars[$param],
          'values' => $form_items[$param]];
    }

    // Measured entities
    $form['row'][0]['measured_state'] = [
      'type'   => 'multiselect',
      'name'   => 'Measured State',
      'width'  => '100%', //'180px',
      'grid'   => 2,
      'value'  => $vars['measured_state'],
      'values' => ['none'     => ['name' => 'Without Measure', 'icon' => $config['icon']['filter']],
                   'up'       => ['name' => 'Measured UP', 'icon' => $config['icon']['up']],
                   'down'     => ['name' => 'Measured DOWN', 'icon' => $config['icon']['down']],
                   'shutdown' => ['name' => 'Measured SHUTDOWN', 'icon' => $config['icon']['shutdown']]]];

    $form['row'][1]['status_descr'] = [
      'type'        => 'text',
      'placeholder' => 'Status description',
      'width'       => '100%', //'180px',
      'grid'        => 4,
      'value'       => $vars['status_descr']];

    $form['row'][1]['status_type'] = [
      'type'        => 'text',
      'placeholder' => 'Status type',
      'width'       => '100%', //'180px',
      'grid'        => 4,
      'value'       => $vars['status_descr']];

    // Groups
    foreach (get_type_groups('status') as $entry) {
        $form_items['group'][$entry['group_id']] = $entry['group_name'];
    }
    $form['row'][1]['group'] = [
      'community' => FALSE,
      'type'      => 'multiselect',
      'name'      => 'Select Groups',
      'width'     => '100%', //'180px',
      'grid'      => 2,
      'value'     => $vars['group'],
      'values'    => $form_items['group']];

    $form['row'][1]['search'] = [
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
                   'title'         => 'Search Statuses',
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
    $panel_form['row'][0]['device_id']        = $form['row'][0]['device_id'];
    $panel_form['row'][0]['entPhysicalClass'] = $form['row'][0]['entPhysicalClass'];

    $panel_form['row'][1]['status_event'] = $form['row'][0]['status_event'];
    $panel_form['row'][1]['status_name']  = $form['row'][0]['status_name'];

    $panel_form['row'][2]['measured_state'] = $form['row'][0]['measured_state'];
    $panel_form['row'][2]['group']          = $form['row'][1]['group'];

    $panel_form['row'][3]['status_type'] = $form['row'][1]['status_type'];

    $panel_form['row'][4]['status_descr'] = $form['row'][1]['status_descr'];

    //$panel_form['row'][5]['sort'] = $form['row'][0]['sort'];
    $panel_form['row'][5]['search'] = $form['row'][1]['search'];

    // Register custom panel
    register_html_panel(generate_form($panel_form));
}

// EOF
