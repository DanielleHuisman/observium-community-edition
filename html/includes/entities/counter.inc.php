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
 * Humanize counter.
 *
 * Returns a $counter array with processed information:
 * counter_state (TRUE: state counter, FALSE: normal counter)
 * human_value, counter_symbol, state_name, state_event, state_class
 *
 * @param array $counter
 *
 * @return array $counter
 *
 */
// TESTME needs unit testing
function humanize_counter(&$counter)
{
    global $config;

    // Exit if already humanized
    if ($counter['humanized']) {
        return;
    }

    $counter['counter_symbol'] = $GLOBALS['config']['counter_types'][$counter['counter_class']]['symbol'];
    $counter['counter_format'] = strval($GLOBALS['config']['counter_types'][$counter['counter_class']]['format']);
    $counter['state_class']    = ''; //'text-success';

    // Generate "pretty" thresholds
    $have_limit = FALSE;
    if (is_numeric($counter['counter_limit_low'])) {
        $counter_limit_low = format_value($counter['counter_limit_low'], $counter['counter_format']) . $counter['counter_symbol'];
        $have_limit        = TRUE;
    } else {
        $counter_limit_low = "&infin;";
    }

    if (is_numeric($counter['counter_limit_low_warn'])) {
        $counter_warn_low = format_value($counter['counter_limit_low_warn'], $counter['counter_format']) . $counter['counter_symbol'];
        $have_limit       = TRUE;
    } else {
        $counter_warn_low = NULL;
    }

    if ($counter_warn_low) {
        $counter_limit_low = $counter_limit_low . " (" . $counter_warn_low . ")";
    }

    if (is_numeric($counter['counter_limit'])) {
        $counter_limit_high = format_value($counter['counter_limit'], $counter['counter_format']) . $counter['counter_symbol'];
        $have_limit         = TRUE;
    } else {
        $counter_limit_high = "&infin;";
    }

    if (is_numeric($counter['counter_limit_warn'])) {
        $counter_warn_high = format_value($counter['counter_limit_warn'], $counter['counter_format']) . $counter['counter_symbol'];
        $have_limit        = TRUE;
    } else {
        $counter_warn_high = "&infin;";
    }

    if ($counter_warn_high) {
        $counter_limit_high = "(" . $counter_warn_high . ") " . $counter_limit_high;
    }

    switch ($counter['counter_limit_by']) {
        case 'sec':
            $limit_by = 'Rate /sec';
            break;
        case 'min':
            $limit_by = 'Rate /min';
            break;
        case '5min':
            $limit_by = 'Rate /5min';
            break;
        case 'hour':
            $limit_by = 'Rate /hour';
            break;
        case 'value':
            $limit_by = 'Value';
            break;
    }
    $counter['counter_thresholds'] = $have_limit ? "$limit_by: $counter_limit_low - $counter_limit_high" : '&infin;';

    // generate pretty value / rate
    foreach (['value', 'rate', 'rate_5min', 'rate_hour'] as $param) {
        if (!is_numeric($counter['counter_' . $param])) {
            //$counter['human_'.$param] = 'NaN';
            if ($param == 'value') {
                $counter['counter_symbol']  = '';
                $counter['human_' . $param] = 'NaN';
            } else {
                // Rates
                $counter['human_' . $param] = '-';
            }
        } else {
            // Rate can be negative (ie lifetime always grow down)
            if ($counter['counter_' . $param] < 0) {
                $counter['human_' . $param] = '-' . format_value(abs($counter['counter_' . $param]), $counter['counter_format']);
            } else {
                $counter['human_' . $param] = format_value($counter['counter_' . $param], $counter['counter_format']);
            }
        }
    }

    if (isset($config['entity_events'][$counter['counter_event']])) {
        $counter = array_merge($counter, $config['entity_events'][$counter['counter_event']]);
    } else {
        $counter['event_class'] = 'label label-primary';
        $counter['row_class']   = '';
    }
    //r($counter);
    if ($counter['counter_deleted']) {
        $counter['row_class'] = 'disabled';
    }

    $device = device_by_id_cache($counter['device_id']);
    if ((isset($device['status']) && !$device['status']) || (isset($device['disabled']) && $device['disabled'])) {
        $counter['row_class'] = 'error';
    }

    // Set humanized entry in the array so we can tell later
    $counter['humanized'] = TRUE;
}

function build_counter_query($vars, $query_count = FALSE)
{

    if ($query_count) {
        $sql = "SELECT COUNT(*) FROM `counters`";
    } else {
        $sql = "SELECT * FROM `counters`";

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
                $values = get_group_entities($value);
                $where_array[] = generate_query_values($values, 'counters.counter_id');
                break;

            case 'device_group_id':
            case 'device_group':
                $values = get_group_entities($value, 'device');
                $where_array[] = generate_query_values($values, 'counters.device_id');
                break;

            case "device":
            case "device_id":
                $where_array[] = generate_query_values($value, 'counters.device_id');
                break;

            case "id":
            case "counter_id":
                $where_array[] = generate_query_values($value, 'counters.counter_id');
                break;

            case "entity_id":
                $where_array[] = generate_query_values($value, 'counters.measured_entity');
                break;

            case "entity_type":
                $where_array[] = generate_query_values($value, 'counters.measured_class');
                break;

            case 'entity_state':
            case "measured_state":
                $where_array[] = generate_query_entity_measured('counter', [ 'measured_state' => $value ]);
                break;

            case 'class':
            case "counter_class":
                $where_array[] = generate_query_values($value, 'counter_class');
                break;

            case "descr":
            case "counter_descr":
                $where_array[] = generate_query_values($value, 'counters.counter_descr', '%LIKE%');
                break;
            case "event":
            case "counter_event":
                $where_array[] = generate_query_values($value, 'counter_event');
                break;
        }
    }
    $where_array[] = '`counter_deleted` = 0';

    $sql .= generate_where_clause($where_array, generate_query_permitted_ng([ 'device', 'counter' ]));

    // If need count, just return sql without sorting
    if ($query_count) {
        return $sql;
    }

    $sort_order = get_sort_order($vars);

    switch ($vars['sort']) {
        case 'device':
            $sql .= generate_query_sort('hostname', $sort_order);
            break;

        case 'descr':
        case 'event':
        case 'value':
        case 'rate':
        case 'rate_hour':
        case 'last_change':
            $sql .= generate_query_sort('counter_' . $vars['sort'], $sort_order);
            break;

        default:
            // $sql .= ' ORDER BY `hostname` '.$sort_order.', `counter_descr` '.$sort_order;
            //$sql .= generate_query_sort([ 'hostname', 'counter_descr' ], $sort_order);
    }

    if (isset($vars['pageno'])) {
        $sql .= generate_query_limit($vars);
    }

    return $sql;
}

function print_counter_table($vars)
{

    pagination($vars, 0, TRUE); // Get default pagesize/pageno

    $sql = build_counter_query($vars);

    //r($vars);
    //r($sql);

    $counters = [];
    //foreach(dbFetchRows($sql, NULL, TRUE) as $counter)
    foreach (dbFetchRows($sql) as $counter) {
        //$device = device_by_id_cache($counter['device_id']);
        $counter['hostname'] = get_device_hostname_by_id($counter['device_id']);
        $counters[]          = $counter;
    }

    //$counters_count = count($counters); // This is count incorrect, when pagination used!
    //$counters_count = dbFetchCell(build_counter_query($vars, TRUE), NULL, TRUE);
    $counters_count = dbFetchCell(build_counter_query($vars, TRUE));

    // Pagination
    $pagination_html = pagination($vars, $counters_count);
    echo $pagination_html;

    echo generate_box_open();

    print_counter_table_header($vars);

    foreach ($counters as $counter) {
        print_counter_row($counter, $vars);
    }

    echo("</tbody></table>");

    echo generate_box_close();

    echo $pagination_html;
}

function print_counter_table_header($vars)
{
    if ($vars['view'] == "graphs" || $vars['graph'] || isset($vars['id'])) {
        $stripe_class = "table-striped-two";
    } else {
        $stripe_class = "table-striped";
    }

    echo('<table class="table ' . $stripe_class . ' table-condensed ">' . PHP_EOL);
    $cols           = [];
    $cols[]         = [NULL, 'class="state-marker"'];
    $cols['device'] = ['Device', 'style="width: 250px;"'];
    //$cols[]              = array(NULL, 'class="no-width"'); // Measure entity link
    $cols['descr'] = ['Description'];
    $cols['class'] = ['Class', 'style="width: 100px;"'];
    $cols['mib']   = ['MIB::Object'];
    //$cols[]              = array('Thresholds', 'style="width: 100px;"');
    $cols[]              = ['History', 'style="text-align: right;"'];
    $cols['last_change'] = ['Last&nbsp;changed', 'style="width: 80px;"'];
    $cols['event']       = ['Event', 'style="width: 60px; text-align: right;"'];
    $cols['rate']        = ['Rate', 'style="width: 80px; text-align: right;"'];
    $cols['value']       = ['Value', 'style="width: 70px; text-align: right;"'];

    if ($vars['page'] == "device") {
        unset($cols['device']);
    }
    if ($vars['page'] != "device" || $vars['tab'] == "overview") {
        unset($cols['mib']);
        unset($cols['object']);
    }
    if (!$vars['show_class']) {
        unset($cols['class']);
    }
    if ($vars['tab'] == "overview") {
        unset($cols[2]);
    } // Thresholds

    echo(get_table_header($cols, $vars));
    echo('<tbody>' . PHP_EOL);
}

function print_counter_row($counter, $vars)
{
    echo generate_counter_row($counter, $vars);
}

function generate_counter_row($counter, $vars)
{
    global $config;

    humanize_counter($counter);

    $table_cols = 4;

    if ($counter['counter_event'] && is_numeric($counter['counter_value'])) {
        $graph_array           = [];
        $graph_array['to']     = get_time();
        $graph_array['id']     = $counter['counter_id'];
        $graph_array['type']   = "counter_graph";
        $graph_array['width']  = 80;
        $graph_array['height'] = 20;
        $graph_array['bg']     = 'ffffff00';
        $graph_array['from']   = get_time('day');
        $mini_graph            = generate_graph_tag($graph_array);

        $graph_array           = [];
        $graph_array['to']     = get_time();
        $graph_array['id']     = $counter['counter_id'];
        $graph_array['type']   = "counter_rate";
        $graph_array['width']  = 80;
        $graph_array['height'] = 20;
        $graph_array['bg']     = 'ffffff00';
        $graph_array['from']   = get_time('day');
        $mini_graph            .= generate_graph_tag($graph_array);

    } else {
        // Do not show "Draw Error" minigraph
        $mini_graph = '';
    }

    $row = '
      <tr class="' . $counter['row_class'] . '">
        <td class="state-marker"></td>';

    if ($vars['page'] != "device" && $vars['popup'] != TRUE) {
        $row .= '        <td class="entity">' . generate_device_link($counter) . '</td>' . PHP_EOL;
        $table_cols++;
    }

    // Measured link & icon
    /* Disabled because it breaks the overview table layout
    if ($vars['entity_icon']) // this used for entity popup
    {
      $row .= get_icon($config['counter_types'][$counter['counter_class']]['icon']);
    }
    elseif ($counter['measured_entity'] &&
             (!isset($vars['measured_icon']) || $vars['measured_icon'])) // hide measured icon if not required
    {
      //$row .= generate_entity_link($counter['measured_class'], $counter['measured_entity'], get_icon($counter['measured_class']));
      $row .= generate_entity_icon_link($counter['measured_class'], $counter['measured_entity']);
    }
    $row .= '</td>';
    $table_cols++;
    */

    $row .= '        <td class="entity">' . generate_entity_link("counter", $counter) . '</td>';
    $table_cols++;

    if ($vars['show_class']) {
        $row .= '        <td>' . nicecase($counter['counter_class']) . '</td>' . PHP_EOL;
        $table_cols++;
    }

    // FIXME -- Generify this. It's not just for counters.
    if ($vars['page'] === "device" && $vars['tab'] !== "overview") {
        $row .= '        <td>' . (!safe_empty($counter['counter_mib']) ? '<a href="' . OBSERVIUM_MIBS_URL . '/' . $counter['counter_mib'] . '/" target="_blank">' . nicecase($counter['counter_mib']) . '</a>' : '') .
                ((!safe_empty($counter['counter_mib']) && !safe_empty($counter['counter_object'])) ? '::' : '') .
                (!safe_empty($counter['counter_mib']) ? '<a href="' . OBSERVIUM_MIBS_URL . '/' . $counter['counter_mib'] . '/#' . $counter['counter_object'] . '" target="_blank">' . $counter['counter_object'] . '</a>' : '') .
                '.' . $counter['counter_index'] . '</td>' . PHP_EOL;
        $table_cols++;
    }

    // Disable show thresholds
    // if ($vars['tab'] != 'overview')
    // {
    //   $row .= '        <td><span class="label ' . ($counter['counter_custom_limit'] ? 'label-warning' : '') . '">' . $counter['counter_thresholds'] . '</span></td>' . PHP_EOL;
    //   $table_cols++;
    // }
    $row .= '        <td style="width: 180px; text-align: right;">' . generate_entity_link('counter', $counter, $mini_graph, NULL, FALSE) . '</td>';

    if ($vars['tab'] != 'overview') {
        $row .= '        <td style="white-space: nowrap">' . ($counter['counter_last_change'] == '0' ? 'Never' : generate_tooltip_link(NULL, format_uptime((get_time('now') - $counter['counter_last_change']), 'short-2') . ' ago', format_unixtime($counter['counter_last_change']))) . '</td>';
        $table_cols++;
        $row .= '        <td style="text-align: right;"><strong>' . generate_tooltip_link('', $counter['counter_event'], $counter['event_descr'], $counter['event_class']) . '</strong></td>';
        $table_cols++;
    }
    $counter_tooltip = $counter['event_descr'];

    // Append value in alternative units to tooltip
    if (isset($config['counter_types'][$counter['counter_class']]['alt_units'])) {
        foreach (value_to_units($counter['counter_value'],
                                $config['counter_types'][$counter['counter_class']]['symbol'],
                                $counter['counter_class'],
                                $config['counter_types'][$counter['counter_class']]['alt_units']) as $unit => $unit_value) {
            if (is_numeric($unit_value)) {
                $counter_tooltip .= "<br />{$unit_value}{$unit}";
            }
        }
    }

    // Set to TRUE if this counter in time based format (ie lifetime)
    $format_time = isset($config['counter_types'][$counter['counter_class']]['format']) &&
                   str_contains($config['counter_types'][$counter['counter_class']]['format'], 'time');
    $rates       = [];
    // FIXME. Probably do not show rates for time based counters?.. (it's always around 1s/5m/1h)
    if (!$format_time) {
        $rate_text = $format_time ? $counter['human_rate'] : $counter['human_rate'] . ' /s';
        if ($counter['counter_limit_by'] == 'sec' && $counter['counter_event'] != 'ok') {
            $rates[] = ['event' => $counter['event_class'], 'text' => $rate_text];
        } else {
            $rates[] = ['event' => 'success', 'text' => $rate_text];
        }
        $rate_text = $format_time ? $counter['human_rate_5min'] : $counter['human_rate_5min'] . ' /5min';
        if ($counter['counter_limit_by'] == '5min' && $counter['counter_event'] != 'ok') {
            $rates[] = ['event' => $counter['event_class'], 'text' => $rate_text];
        } else {
            $rates[] = ['event' => 'info', 'text' => $rate_text];
        }
        $rate_text = $format_time ? $counter['human_rate_hour'] : $counter['human_rate_hour'] . ' /h';
        if ($counter['counter_limit_by'] == 'hour' && $counter['counter_event'] != 'ok') {
            $rates[] = ['event' => $counter['event_class'], 'text' => $rate_text];
        } else {
            $rates[] = ['event' => 'primary', 'text' => $rate_text];
        }
    }
    $row .= '        <td style="width: 80px; text-align: right;">' . get_label_group($rates) . '</td>';
    $row .= '        <td style="width: 70px; text-align: right;"><strong>' . generate_tooltip_link('', $counter['human_value'] . $counter['counter_symbol'], $counter_tooltip, $counter['event_class']) . '</strong></td>';
    $row .= '      </tr>' . PHP_EOL;

    if ($vars['view'] == "graphs" || $vars['id'] == $counter['counter_id']) {
        $vars['graph'] = "graph";
    }
    if ($vars['graph']) {
        $row .= '
      <tr class="' . $counter['row_class'] . '">
        <td class="state-marker"></td>
        <td colspan="' . $table_cols . '">';

        $graph_array         = [];
        $graph_array['to']   = get_time();
        $graph_array['id']   = $counter['counter_id'];
        $graph_array['type'] = 'counter_' . $vars['graph'];

        $row .= generate_graph_row($graph_array, TRUE);

        $graph_array         = [];
        $graph_array['to']   = get_time();
        $graph_array['id']   = $counter['counter_id'];
        $graph_array['type'] = 'counter_rate';

        $row .= generate_graph_row($graph_array, TRUE);

        $row .= '</td></tr>';
    } # endif graphs

    return $row;
}

function print_counter_form($vars, $single_device = FALSE)
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
        $form_items['devices'] = generate_form_values('device', dbFetchColumn('SELECT DISTINCT `device_id` FROM `counters`'));

        $form['row'][0]['device_id'] = [
          'type'   => 'multiselect',
          'name'   => 'Device',
          'value'  => $vars['device_id'],
          'grid'   => 2,
          'width'  => '100%', //'180px',
          'values' => $form_items['devices']];
    }

    $counter_permitted = generate_query_permitted(['device', 'counter']);
    foreach (['counter_class' => 'Counter Class', 'counter_event' => 'Counter Event'] as $param => $param_name) {
        $sql = 'SELECT DISTINCT `' . $param . '` FROM `counters` WHERE `counter_deleted` = ?' . $counter_permitted;
        if ($entries = dbFetchColumn($sql, [0])) {
            asort($entries);
        }
        foreach ($entries as $entry) {
            if (safe_empty($entry)) {
                $entry = OBS_VAR_UNSET;
            }
            if ($param === 'counter_class') {
                $name = nicecase($entry);
                if (isset($config['counter_types'][$entry]['icon'])) {
                    $name = ['name' => $name, 'icon' => $config['counter_types'][$entry]['icon']];
                } else {
                    $name = ['name' => $name, 'icon' => $config['icon']['counter']];
                }
            } else {
                $name = $entry;
            }
            $form_items[$param][$entry] = $name;
        }

        // Alternative param name, ie event
        $short_param = str_replace('counter_', '', $param);
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
    $form['row'][0]['counter_value'] = [
      'type'  => 'hidden',
      'name'  => 'Value',
      'width' => '100%', //'180px',
      'grid'  => 0,
      'value' => $vars['counter_value']];

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


    $form['row'][1]['counter_descr'] = [
      'type'        => 'text',
      'placeholder' => 'Counter description',
      'width'       => '100%', //'180px',
      'grid'        => 6,
      'value'       => $vars['counter_descr']];


    // $form['row'][1]['counter_type']    = array(
    //   'type'        => 'text',
    //   'placeholder' => 'Counter type',
    //   'width'       => '100%', //'180px',
    //   'grid'        => 4,
    //   'value'       => $vars['status_descr']);

    // Groups
    foreach (get_type_groups('counter') as $entry) {
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
      'grid'  => 4,
      //'name'        => 'Search',
      //'icon'        => 'icon-search',
      'right' => TRUE];


    // Show search form
    echo '<div class="hidden-xl">';
    print_form($form);
    echo '</div>';

    // Custom panel form
    $panel_form = ['type'          => 'rows',
                   'title'         => 'Search Counters',
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
    $panel_form['row'][0]['device_id']     = $form['row'][0]['device_id'];
    $panel_form['row'][0]['counter_class'] = $form['row'][0]['counter_class'];

    $panel_form['row'][1]['counter_event'] = $form['row'][0]['counter_event'];
    $panel_form['row'][1]['counter_value'] = $form['row'][0]['counter_value'];

    $panel_form['row'][2]['measured_state'] = $form['row'][0]['measured_state'];
    $panel_form['row'][2]['group']          = $form['row'][1]['group'];

    //$panel_form['row'][3]['counter_type']    = $form['row'][1]['counter_type'];

    $panel_form['row'][4]['counter_descr'] = $form['row'][1]['counter_descr'];

    //$panel_form['row'][5]['sort'] = $form['row'][0]['sort'];
    $panel_form['row'][5]['search'] = $form['row'][1]['search'];

    // Register custom panel
    register_html_panel(generate_form($panel_form));
}

// EOF
