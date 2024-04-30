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
 * Humanize sensor.
 *
 * Returns a the $sensor array with processed information:
 * sensor_state (TRUE: state sensor, FALSE: normal sensor)
 * human_value, sensor_symbol, state_name, state_event, state_class
 *
 * @param array $sensor
 *
 * @return array $sensor
 *
 */
// TESTME needs unit testing
function humanize_sensor(&$sensor)
{
    global $config;

    // Exit if already humanized
    if ($sensor['humanized']) {
        return;
    }

    $sensor['sensor_symbol'] = $config['sensor_types'][$sensor['sensor_class']]['symbol'];
    $sensor['sensor_format'] = (string)$config['sensor_types'][$sensor['sensor_class']]['format'];
    $sensor['state_class']   = ''; //'text-success';

    // Generate "pretty" thresholds
    if (is_numeric($sensor['sensor_limit_low'])) {
        $sensor_threshold_low = format_value($sensor['sensor_limit_low'], $sensor['sensor_format']) . $sensor['sensor_symbol'];
    } else {
        $sensor_threshold_low = "&infin;";
    }

    if (is_numeric($sensor['sensor_limit_low_warn'])) {
        $sensor_warn_low = format_value($sensor['sensor_limit_low_warn'], $sensor['sensor_format']) . $sensor['sensor_symbol'];
    } else {
        $sensor_warn_low = NULL;
    }

    if ($sensor_warn_low) {
        $sensor_threshold_low = $sensor_threshold_low . " (" . $sensor_warn_low . ")";
    }

    if (is_numeric($sensor['sensor_limit'])) {
        $sensor_threshold_high = format_value($sensor['sensor_limit'], $sensor['sensor_format']) . $sensor['sensor_symbol'];
    } else {
        $sensor_threshold_high = "&infin;";
    }

    if (is_numeric($sensor['sensor_limit_warn'])) {
        $sensor_warn_high = format_value($sensor['sensor_limit_warn'], $sensor['sensor_format']) . $sensor['sensor_symbol'];
    } else {
        $sensor_warn_high = "&infin;";
    }

    if ($sensor_warn_high) {
        $sensor_threshold_high = "(" . $sensor_warn_high . ") " . $sensor_threshold_high;
    }

    $sensor['sensor_thresholds'] = $sensor_threshold_low . ' - ' . $sensor_threshold_high;

    // generate pretty value
    if (!is_numeric($sensor['sensor_value'])) {
        $sensor['human_value']   = 'NaN';
        $sensor['sensor_symbol'] = '';
    } else {
        $sensor['human_value'] = format_value($sensor['sensor_value'], $sensor['sensor_format'], 2, 4);
    }

    if (isset($config['entity_events'][$sensor['sensor_event']])) {
        $sensor = array_merge($sensor, $config['entity_events'][$sensor['sensor_event']]);
    } else {
        $sensor['event_class'] = 'label label-primary';
        $sensor['row_class']   = '';
    }

    if (device_is_ignored($sensor['device_id']) &&
        ($sensor['row_class'] === "error" || $sensor['row_class'] === "warning")) {
        $sensor['row_class'] = 'suppressed';
    }

    if ($sensor['sensor_deleted']) {
        $sensor['row_class'] = 'disabled';
    }

    // Set humanized entry in the array so we can tell later
    $sensor['humanized'] = TRUE;
}

function build_sensor_query($vars, $query_count = FALSE)
{

    if ($query_count) {
        $sql = "SELECT COUNT(*) FROM `sensors`";
    } else {
        $sql = "SELECT * FROM `sensors`";

        if ($vars['sort'] === 'hostname' || $vars['sort'] === 'device' || $vars['sort'] === 'device_id') {
            $sql .= ' LEFT JOIN `devices` USING(`device_id`)';
        }
    }

    // Build query
    $where_array = [];
    foreach ($vars as $var => $value) {
        switch ($var) {
            case "group":
            case "group_id":
                $values = get_group_entities($value);
                $where_array[] = generate_query_values($values, 'sensors.sensor_id');
                break;

            case 'device_group_id':
            case 'device_group':
                $values = get_group_entities($value, 'device');
                $where_array[] = generate_query_values($values, 'sensors.device_id');
                break;

            case "device":
            case "device_id":
                $where_array[] = generate_query_values($value, 'sensors.device_id');
                break;

            case "id":
            case "sensor_id":
                $where_array[] = generate_query_values($value, 'sensors.sensor_id');
                break;

            case "entity_id":
                $where_array[] = generate_query_values($value, 'sensors.measured_entity');
                break;

            case "entity_type":
                $where_array[] = generate_query_values($value, 'sensors.measured_class');
                break;

            case 'entity_state':
            case "measured_state":
                $where_array[] = generate_query_entity_measured('sensor', [ 'measured_state' => $value ]);
                break;

            case "metric":
                // old metric param don't allow an array
                if (!isset($GLOBALS['config']['sensor_types'][$value])) {
                    break;
                }
            case 'class':
            case "sensor_class":
                $where_array[] = generate_query_values($value, 'sensor_class');
                break;

            case "descr":
            case "sensor_descr":
                $where_array[] = generate_query_values($value, 'sensors.sensor_descr', '%LIKE%');
                break;

            case "type":
            case "sensor_type":
                $where_array[] = generate_query_values($value, 'sensor_type', '%LIKE%');
                break;

            case "event":
            case "sensor_event":
                $where_array[] = generate_query_values($value, 'sensor_event');
                break;
        }
    }
    $where_array[] = '`sensor_deleted` = 0';

    $sql .= generate_where_clause($where_array, generate_query_permitted_ng([ 'device', 'sensor' ]));

    // If need count, just return sql without sorting
    if ($query_count) {
        return $sql;
    }

    if (isset($vars['sort'])) {
        $sort_order = get_sort_order($vars);
        switch ($vars['sort']) {
            case 'device':
                $sql .= generate_query_sort('hostname', $sort_order);
                break;

            case 'descr':
            case 'event':
            case 'value':
            case 'last_change':
                $sql .= generate_query_sort('sensor_'. $vars['sort'], $sort_order);
                break;

            case 'class':
            case 'sensor_class':
                $sql .= generate_query_sort('sensor_class', $sort_order);
                break;

            case 'mib':
                $sql .= generate_query_sort([ 'sensor_mib', 'sensor_object', 'sensor_type', 'sensor_index:integer' ], $sort_order);
                break;
            default:
                // $sql .= generate_query_sort([ 'hostname', 'sensor_descr' ], $sort_order);
        }
    }

    if (isset($vars['pageno'])) {
        $sql .= generate_query_limit($vars);
    }

    return $sql;
}

function print_sensor_table($vars, $default_order = [])
{

    pagination($vars, 0, TRUE); // Get default pagesize/pageno

    $sql = build_sensor_query($vars);

    $sensors = [];
    foreach (dbFetchRows($sql) as $sensor) {

        // fixme - not clear what this is doing or why it's here. if it's for some special page, it should not be here

        $sensor['hostname']                 = get_device_hostname_by_id($sensor['device_id']);
        $sensors[$sensor['sensor_class']][] = $sensor;
    }

    // order dom sensors always by temperature, voltage, current, dbm, power
    $order = [];
    if (safe_count($sensors)) {
        if (safe_count($default_order)) {
            $types = array_keys($sensors);
            //r($types);
            $order = array_intersect($default_order, $types);
            $order = array_merge($order, array_diff($types, $order));
            //r($order);
        } else {
            $order = array_keys($sensors);
        }
    }

    //$sensors_count = count($sensors); // This is count incorrect, when pagination used!
    //$sensors_count = dbFetchCell(build_sensor_query($vars, TRUE), NULL, TRUE);
    $sensors_count = dbFetchCell(build_sensor_query($vars, TRUE));

    $pagination_html = pagination($vars, $sensors_count);
    echo $pagination_html;

    echo generate_box_open();

    print_sensor_table_header($vars);

    foreach ($order as $type) {
        foreach ($sensors[$type] as $sensor) {
            print_sensor_row($sensor, $vars);
        }
    }

    echo("</tbody></table>");

    echo generate_box_close();

    echo $pagination_html;
}

function print_sensor_table_header($vars)
{
    if ($vars['view'] === "graphs" || get_var_true($vars['graph']) || isset($vars['id'])) {
        $stripe_class = "table-striped-two";
    } else {
        $stripe_class = "table-striped";
    }

    echo('<table class="table ' . $stripe_class . ' table-condensed ">' . PHP_EOL);
    $cols           = [];
    $cols[]         = [NULL, 'class="state-marker"'];
    $cols['device'] = ['Device', 'style="width: 250px;"'];
    //$cols[]              = array(NULL, 'class="no-width"'); // Measured entity link
    $cols['descr']       = ['Description'];
    $cols['sensor_class']       = ['Class', 'style="width: 100px;"'];
    $cols['mib']         = ['MIB::Object'];
    $cols[]              = ['Thresholds', 'style="width: 100px;"'];
    $cols[]              = ['History'];
    $cols['last_change'] = ['Last&nbsp;changed', 'style="width: 80px;"'];
    $cols['event']       = ['Event', 'style="width: 60px; text-align: right;"'];
    $cols['value']       = ['Value', 'style="width: 80px; text-align: right;"'];

    if ($vars['page'] == "device") {
        unset($cols['device']);
    }
    if ($vars['page'] != "device" || $vars['tab'] == "overview") {
        unset($cols['mib']);
    }

//    if (  ( isset($vars['sensor_class']) && !is_array($vars['sensor_class']) ) ||
//          ( isset($vars['class']) && !is_array($vars['class']) ) ||
//          ( $vars['page'] == "device" && $vars['tab'] == "overview") ||
//          ( isset($vars['metric']) && $vars['metric'] !== "sensors") ||
//          ( $vars['page'] == "device" && $vars['tab'] == "ports" )  ) {

 if ( !($vars['metric'] == "sensors" || // Display sensor_class on all-sensor pages
        ( !isset($vars['class']) || is_array($vars['class'])) &&
        ( !isset($vars['sensor_class']) || is_array($vars['sensor_class'])) &&
           ($vars['page'] != "device" && $vars['tab'] != "overview") )
    ) {

        unset($cols['sensor_class']);
    }

    if ($vars['tab'] == "overview") {
        unset($cols[1]);
    } // Thresholds

    echo(get_table_header($cols, $vars));
    echo('<tbody>' . PHP_EOL);
}

function generate_sensor_line($sensor, $vars)
{
    global $config;

    humanize_sensor($sensor);

    $graph_array           = [];
    $graph_array['to']     = get_time();
    $graph_array['id']     = $sensor['sensor_id'];
    $graph_array['type']   = "sensor_graph";
    $graph_array['width']  = 80;
    $graph_array['height'] = 20;
    $graph_array['bg']     = 'ffffff00';
    $graph_array['from']   = get_time('day');
    $graph_array['style']  = 'margin-top: 5px';

    if ($sensor['sensor_event'] && is_numeric($sensor['sensor_value'])) {
        $mini_graph = generate_graph_tag($graph_array);
    } else {
        $mini_graph = '';
    }

    $text = '<span class="' . $sensor['event_class'] . '">' . $sensor['human_value'] . $sensor['sensor_symbol'] . '</span>';

    $line = '<td class="entity ' . $sensor['row_class'] . '">';
    //$line = '<td class="state-marker"></td>';
    //$btn_class = str_replace('label', 'btn', $sensor['event_class']); // FIXME Need button-outline-* class from bs4+
    if (get_var_true($vars['compact'])) {
        $line .= '<button class="btn btn-default" style="width: 105px; text-align: right;">';
    } else {
        // fixed button size for keep size without images
        $line .= '<button class="btn btn-default" style="width: 105px; height: 55px;">';
    }

    $icon = get_icon($config['sensor_types'][$sensor['sensor_class']]['icon']);
    if ($sensor['sensor_class'] === 'power' || $sensor['sensor_class'] === 'dbm') {
        if (str_icontains_array($sensor['sensor_descr'], [' Rx', 'Rx ', 'Receive'])) {
            // rx
            $icon = get_icon('glyphicon-arrow-down text-primary') . '&nbsp;';
        } elseif (str_icontains_array($sensor['sensor_descr'], [' Tx', 'Tx ', 'Trans'])) {
            // tx
            $icon = get_icon('glyphicon-arrow-up text-danger') . '&nbsp;';
        }
    }

    $line .= $icon . '&nbsp;';
    $line .= generate_entity_link('sensor', $sensor, $text, NULL, FALSE);
    if (!get_var_true($vars['compact'])) {
        $line .= '<br />' . generate_entity_link('sensor', $sensor, $mini_graph, NULL, FALSE);
    }
    $line .= '</button>';
    $line .= '</td>';

    //r($line);
    return $line;
}

function get_compact_sensors_line($measured_class, $entry, $vars) {

    // order dom sensors always by temperature, voltage, current, dbm, power
    $order = [];
    if (safe_count($entry) > 0) {
        $classes = array_keys($entry);
        //r($types);
        switch($measured_class) {
            case 'port':
                // always display all classes for dom (also if not exist)
                $order = [ 'temperature', 'voltage', 'current', /* 'dbm', 'power' */ ];
                // or dbm or power
                if (in_array('dbm', $classes, TRUE)) {
                    $order[] = 'dbm';
                } elseif (in_array('power', $classes, TRUE)) {
                    $order[] = 'power';
                } else {
                    $order[] = 'dbm';
                }
                break;

            case 'outlet':
                // always display all classes for dom (also if not exist)
                $order = [ 'voltage', 'current', 'power', 'load' ];
                break;

            default:
                $order = array_intersect([ 'temperature', 'voltage', 'current', 'dbm', 'power' ], $classes);
        }
        $order = array_merge($order, array_diff($classes, $order));
        //r($order);
    }

    $line = '';
    foreach ($order as $class) {
        if (!isset($entry[$class])) {
            // Add empty columns for port entities (for correct aligning)
            $line .= '<td class="entity" style="min-width: 60px;"></td>';
        }

        foreach ($entry[$class] as $sensor) {
            /*
            $sensor['sensor_descr'] = trim(str_ireplace($rename_from, '', $sensor['sensor_descr']), ":- \t\n\r\0\x0B");
            if (empty($sensor['sensor_descr'])) {
              // Some time sensor descriptions equals to entity name
              $sensor['sensor_descr'] = nicecase($sensor['sensor_class']);
            }
            */

            // Compact view per entity/lane
            $line .= generate_sensor_line($sensor, $vars);
        }
    }

    return $line;
}

function print_sensor_row($sensor, $vars)
{
    echo generate_sensor_row($sensor, $vars);
}

function generate_sensor_row($sensor, $vars)
{
    global $config;

    humanize_sensor($sensor);

    $table_cols = 4;

    $graph_array           = [];
    $graph_array['to']     = get_time();
    $graph_array['id']     = $sensor['sensor_id'];
    $graph_array['type']   = "sensor_graph";
    $graph_array['width']  = 80;
    $graph_array['height'] = 20;
    $graph_array['bg']     = 'ffffff00';
    $graph_array['from']   = get_time('day');

    if ($sensor['sensor_event'] && is_numeric($sensor['sensor_value'])) {
        $mini_graph = generate_graph_tag($graph_array);
    } else {
        // Do not show "Draw Error" minigraph
        $mini_graph = '';
    }

    $row = '
      <tr class="' . $sensor['row_class'] . '">
        <td class="state-marker"></td>';

    if ($vars['page'] != "device" && $vars['popup'] != TRUE) {
        $row .= '        <td class="entity">' . generate_device_link($sensor) . '</td>' . PHP_EOL;
        $table_cols++;
    }

    // Measured link & icon
    /* Disabled because it breaks the overview box layout
    $row .= '        <td style="padding-right: 0px;" class="no-width vertical-align">'; // minify column if empty
    if ($vars['entity_icon']) // this used for entity popup
    {
      $row .= get_icon($config['sensor_types'][$sensor['sensor_class']]['icon']);
    }
    else if ($sensor['measured_entity'] &&
             (!isset($vars['measured_icon']) || $vars['measured_icon'])) // hide measured icon if not required
    {
      //$row .= generate_entity_link($sensor['measured_class'], $sensor['measured_entity'], get_icon($sensor['measured_class']));
      $row .= generate_entity_icon_link($sensor['measured_class'], $sensor['measured_entity']);
    }
    $row .= '</td>';
    $table_cols++;
    */

    $row .= '        <td class="entity">' . generate_entity_link("sensor", $sensor) . '</td>';
    $table_cols++;

    if ( $vars['metric'] == "sensors" || // Display sensor_class on all-sensor pages
        ( !isset($vars['class']) || is_array($vars['class'])) &&
        ( !isset($vars['sensor_class']) || is_array($vars['sensor_class'])) &&
           ($vars['page'] != "device" && $vars['tab'] != "overview")) {
        $row .= '        <td>' . get_type_class_label($sensor['sensor_class'], 'sensor') . '</td>' . PHP_EOL;
        $table_cols++;
    }

    // FIXME -- Generify this. It's not just for sensors.
    if ($vars['page'] === "device" && $vars['tab'] !== "overview") {
        $row .= '      <td><span class="label-group">' . (!safe_empty($sensor['sensor_mib']) ? '<span class="label label-primary"><a href="' . OBSERVIUM_MIBS_URL . '/' . $sensor['sensor_mib'] . '/" target="_blank">' . nicecase($sensor['sensor_mib']) . '</a></span>' : '') .
                (!safe_empty($sensor['sensor_mib']) ? '<span class="label label-success"><a href="' . OBSERVIUM_MIBS_URL . '/' . $sensor['sensor_mib'] . '/#' . $sensor['sensor_object'] . '" target="_blank">' . $sensor['sensor_object'] . '</a></span>' : '') .
                '<span class="label label-delayed">' . $sensor['sensor_index'] . '</span></span></td>' . PHP_EOL;
        $table_cols++;
    }


    if ($vars['tab'] !== 'overview') {
        $sensor_thresholds_class = $sensor['sensor_custom_limit'] ? ' label-suppressed' : '';
        $sensor_thresholds       = $sensor['sensor_custom_limit'] ? generate_tooltip_link(NULL, $sensor['sensor_thresholds'], 'Custom thresholds') : $sensor['sensor_thresholds'];
        $row                     .= '        <td><span class="label' . $sensor_thresholds_class . '">' . $sensor_thresholds . '</span></td>' . PHP_EOL;
        $table_cols++;
    }
    $row .= '        <td style="width: 90px; text-align: right;">' . generate_entity_link('sensor', $sensor, $mini_graph, NULL, FALSE) . '</td>';

    if ($vars['tab'] !== 'overview') {
        $row .= '        <td style="white-space: nowrap">' . ($sensor['sensor_last_change'] == '0' ? 'Never' : generate_tooltip_link(NULL, format_uptime((get_time('now') - $sensor['sensor_last_change']), 'short-2') . ' ago', format_unixtime($sensor['sensor_last_change']))) . '</td>';
        $table_cols++;
        $row .= '        <td style="text-align: right;"><strong>' . generate_tooltip_link('', $sensor['sensor_event'], $sensor['event_descr'], $sensor['event_class']) . '</strong></td>';
        $table_cols++;
    }
    $sensor_tooltip = $sensor['event_descr'];

    // Append value in alternative units to tooltip
    if (isset($config['sensor_types'][$sensor['sensor_class']]['alt_units'])) {
        foreach (value_to_units($sensor['sensor_value'],
                                $config['sensor_types'][$sensor['sensor_class']]['symbol'],
                                $sensor['sensor_class'],
                                $config['sensor_types'][$sensor['sensor_class']]['alt_units']) as $unit => $unit_value) {
            if (is_numeric($unit_value)) {
                $sensor_tooltip .= "<br />{$unit_value}{$unit}";
            }
        }
    }

    $row .= '        <td style="width: 80px; text-align: right;"><strong>' . generate_tooltip_link('', $sensor['human_value'] . $sensor['sensor_symbol'], $sensor_tooltip, $sensor['event_class']) . '</strong>
        </tr>' . PHP_EOL;

    if ($vars['view'] == "graphs" || $vars['id'] == $sensor['sensor_id']) {
        $vars['graph'] = "graph";
    }
    if ($vars['graph']) {
        $row .= '
      <tr class="' . $sensor['row_class'] . '">
        <td class="state-marker"></td>
        <td colspan="' . $table_cols . '">';

        $graph_array         = [];
        $graph_array['to']   = get_time();
        $graph_array['id']   = $sensor['sensor_id'];
        $graph_array['type'] = 'sensor_' . $vars['graph'];

        $row .= generate_graph_row($graph_array, TRUE);

        $row .= '</td></tr>';
    } # endif graphs

    return $row;
}

function print_sensor_form($vars, $single_device = FALSE)
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
        $form_items['devices'] = generate_form_values('device', dbFetchColumn('SELECT DISTINCT `device_id` FROM `sensors`'));

        $form['row'][0]['device_id'] = [
          'type'   => 'multiselect',
          'name'   => 'Device',
          'value'  => $vars['device_id'],
          'grid'   => 2,
          'width'  => '100%', //'180px',
          'values' => $form_items['devices']];
    }

    $sensor_permitted = generate_query_permitted(['device', 'sensor']);
    foreach (['sensor_class' => 'Sensor Class', 'sensor_event' => 'Sensor Event'] as $param => $param_name) {
        $sql = 'SELECT DISTINCT `' . $param . '` FROM `sensors` WHERE `sensor_deleted` = ?' . $sensor_permitted;
        if ($entries = dbFetchColumn($sql, [0])) {
            asort($entries);
        }
        foreach ($entries as $entry) {
            if (safe_empty($entry)) {
                $entry = OBS_VAR_UNSET;
            }
            if ($param === 'sensor_class') {
                $name = nicecase($entry);
                if (isset($config['sensor_types'][$entry]['icon'])) {
                    $name = ['name' => $name, 'icon' => $config['sensor_types'][$entry]['icon']];
                } else {
                    $name = ['name' => $name, 'icon' => $config['icon']['sensor']];
                }
            } else {
                $name = $entry;
            }
            $form_items[$param][$entry] = $name;
        }

        // Alternative param name, ie event
        $short_param = str_replace('sensor_', '', $param);
        if (!isset($vars[$param]) && isset($vars[$short_param])) {
            $vars[$param] = $vars[$short_param];
        }

        $form['row'][0][$param] = [
          'type'   => 'multiselect',
          'name'   => $param_name,
          'width'  => '100%', //'180px',
          'grid'   => 2,
          'value'  => $vars[$param],
          'values' => $form_items[$param]];
    }
    // Currently unused, just dumb space
    $form['row'][0]['sensor_value'] = [
      'type'  => 'hidden',
      'name'  => 'Value',
      'width' => '100%', //'180px',
      'grid'  => 2,
      'value' => $vars['sensor_value']];

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


    $form['row'][1]['sensor_descr'] = [
      'type'        => 'text',
      'placeholder' => 'Sensor description',
      'width'       => '100%', //'180px',
      'grid'        => 4,
      'value'       => $vars['sensor_descr']];


    $form['row'][1]['sensor_type'] = [
      'type'        => 'text',
      'placeholder' => 'Sensor type',
      'width'       => '100%', //'180px',
      'grid'        => 4,
      'value'       => $vars['status_descr']];

    // Groups
    foreach (get_type_groups('sensor') as $entry) {
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
      'right' => TRUE];


    // Show search form
    echo '<div class="hidden-xl">';
    print_form($form);
    echo '</div>';

    // Custom panel form
    $panel_form = ['type'          => 'rows',
                   'title'         => 'Search Sensors',
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
    $panel_form['row'][0]['device_id']    = $form['row'][0]['device_id'];
    $panel_form['row'][0]['sensor_class'] = $form['row'][0]['sensor_class'];

    $panel_form['row'][1]['sensor_event'] = $form['row'][0]['sensor_event'];
    $panel_form['row'][1]['sensor_value'] = $form['row'][0]['sensor_value'];

    $panel_form['row'][2]['measured_state'] = $form['row'][0]['measured_state'];
    $panel_form['row'][2]['group']          = $form['row'][1]['group'];

    $panel_form['row'][3]['sensor_type'] = $form['row'][1]['sensor_type'];

    $panel_form['row'][4]['sensor_descr'] = $form['row'][1]['sensor_descr'];

    //$panel_form['row'][5]['sort'] = $form['row'][0]['sort'];
    $panel_form['row'][5]['search'] = $form['row'][1]['search'];

    // Register custom panel
    register_html_panel(generate_form($panel_form));
}

function print_sensor_permission_box($mode, $perms, $params = []) {
    global $config;

    echo generate_box_open([ 'header-border' => TRUE, 'title' => 'Sensor Permissions' ]);

    if (!safe_empty($perms['sensor'])) {
        echo('<table class="' . OBS_CLASS_TABLE . '">' . PHP_EOL);

        foreach (array_keys($perms['sensor']) as $entity_id) {
            $sensor = get_entity_by_id_cache('sensor', $entity_id);
            $device = device_by_id_cache($sensor['device_id']);

            echo('<tr><td style="width: 1px;"></td>
                <td style="width: 200px; overflow: hidden;">' . get_icon($config['devicetypes'][$device['type']]['icon'] ?? $config['entities']['device']['icon']) . generate_entity_link('device', $device) . '</td>
                <td style="overflow: hidden;">' . get_icon($config['entities']['sensor']['icon']) . generate_entity_link('sensor', $sensor) . '
                <td width="25">');

            $form = [];
            $form['type'] = 'simple';

            $action_del = $mode === 'role' ? 'role_entity_del' : 'user_perm_del';
            // Elements
            $form['row'][0]['auth_secret'] = [
                'type'  => 'hidden',
                'value' => $_SESSION['auth_secret']
            ];
            $form['row'][0]['entity_id'] = [
                'type'  => 'hidden',
                'value' => $sensor['sensor_id']
            ];
            $form['row'][0]['entity_type'] = [
                'type'  => 'hidden',
                'value' => 'sensor'
            ];
            $form['row'][0]['submit'] = [
                'type'  => 'submit',
                'name'  => ' ',
                'class' => 'btn-danger btn-mini',
                'icon'  => 'icon-trash',
                'value' => $action_del
            ];
            print_form($form);
            unset($form);

            echo('</td>
              </tr>');
        }
        echo('</table>' . PHP_EOL);

    } else {
        echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This '.$mode.' currently has no permitted sensors</strong></p>');
    }

    // Sensors
    $permissions_list = array_keys((array)$perms['sensor']);

    // Display devices this user doesn't have Permissions to
    $form = [];
    $form['type'] = 'simple';
    $form['style'] = 'padding: 7px; margin: 0px;';

    // Elements
    $form['row'][0]['auth_secret'] = [
        'type'  => 'hidden',
        'value' => $_SESSION['auth_secret']
    ];
    if ($mode === 'role') {
        $action_add = 'role_entity_add';
        $form['row'][0]['role_id'] = [
            'type'  => 'hidden',
            'value' => $params['role_id']
        ];
    } else {
        $action_add = 'user_perm_add';
        $form['row'][0]['user_id'] = [
            'type'  => 'hidden',
            'value' => $params['user_id']
        ];
    }
    $form['row'][0]['entity_type'] = [
        'type'  => 'hidden',
        'value' => 'sensor'
    ];
    $form['row'][0]['action'] = [
        'type'  => 'hidden',
        'value' => $action_add
    ];

    // FIXME, limit devices list only with sensors?
    $form_items['devices'] = generate_form_values('device', array_keys((array)$perms['device']), NULL,
                                                  [ 'filter_mode' => 'exclude', 'subtext' => '%location%', 'show_disabled' => TRUE, 'show_icon' => TRUE ]);
    $form['row'][0]['device_id'] = [
        'type'     => 'select',
        'name'     => 'Select a device',
        'width'    => '150px',
        'onchange' => "getEntityList(this.value, 'sensor_entity_id', 'sensor')",
        //'value'    => $vars['device_id'],
        'groups'   => ['', 'UP', 'DOWN', 'DISABLED'], // This is optgroup order for values (if required)
        'values'   => $form_items['devices']];
    $form['row'][0]['sensor_entity_id'] = [
        'type'   => 'multiselect',
        'name'   => 'Permit Sensor',
        'width'  => '150px',
        //'value'    => $vars['sensor_entity_id'],
        'values' => []
    ];
    // add button
    $form['row'][0]['Submit'] = [
        'type'  => 'submit',
        'name'  => 'Add',
        'icon'  => $config['icon']['plus'],
        'right' => TRUE,
        'value' => 'Add'
    ];
    print_form($form);

    echo generate_box_close();
}

// EOF
