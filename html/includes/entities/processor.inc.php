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

function generate_processor_query($vars)
{
    $sql = "SELECT * FROM `processors`";
    if (!isset($vars['sort']) || $vars['sort'] == 'hostname' || $vars['sort'] == 'device' || $vars['sort'] == 'device_id') {
        $sql .= ' LEFT JOIN `devices` USING(`device_id`)';
    }
    $sql .= ' WHERE ' . generate_query_permitted_ng(['device']);

    // Build query
    foreach ($vars as $var => $value) {
        switch ($var) {
            case "group":
            case "group_id":
                $values = get_group_entities($value);
                $sql    .= generate_query_values_and($values, 'processor_id');
                break;
            case 'device_group_id':
            case 'device_group':
                $values = get_group_entities($value, 'device');
                $sql    .= generate_query_values_and($values, 'processors.device_id');
                break;
            case "device":
            case "device_id":
                $sql .= generate_query_values_and($value, 'processors.device_id');
                break;
            case "descr":
            case "processor_descr";
                $sql .= generate_query_values_and($value, 'processor_descr', '%LIKE%');
                break;
        }
    }

    switch ($vars['sort_order']) {
        case 'descr':
            $sort_order = 'DESC';
            $sort_neg   = 'ASC';
            break;
        case 'reset':
            unset($vars['sort'], $vars['sort_order']);
        // no break here
        default:
            $sort_order = 'ASC';
            $sort_neg   = 'DESC';
    }

    switch ($vars['sort']) {
        case 'usage':
            $sql .= ' ORDER BY `processor_usage` ' . $sort_neg;
            break;
        case 'descr':
            $sql .= ' ORDER BY `processor_descr` ' . $sort_order;
            break;
        default:
            $sql .= ' ORDER BY `hostname` ' . $sort_order . ', `processor_descr` ' . $sort_order;
            break;
    }

    return $sql;
}


function print_processor_table($vars)
{

    $sql = generate_processor_query($vars);

    $processors = dbFetchRows($sql);

    $processors_count = safe_count($processors);

    // Pagination
    $pagination_html = pagination($vars, $processors_count);
    echo $pagination_html;

    if ($vars['pageno']) {
        $processors = array_chunk($processors, $vars['pagesize']);
        $processors = $processors[$vars['pageno'] - 1];
    }
    // End Pagination

    echo generate_box_open();

    print_processor_table_header($vars);

    foreach ($processors as $processor) {
        print_processor_row($processor, $vars);
    }

    echo("</tbody></table>");

    echo generate_box_close();

    echo $pagination_html;

}

function print_processor_table_header($vars)
{
    if ($vars['view'] == "graphs") {
        $table_class = OBS_CLASS_TABLE_STRIPED_TWO;
    } else {
        $table_class = OBS_CLASS_TABLE_STRIPED;
    }

    echo('<table class="' . $table_class . '">' . PHP_EOL);
    $cols = [
      [NULL, 'class="state-marker"'],
      'device' => ['Device', 'style="width: 200px;"'],
      'descr'  => ['Processor'],
      ['', 'style="width: 100px;"'],
      'usage'  => ['Usage', 'style="width: 250px;"'],
    ];

    if ($vars['page'] == "device") {
        unset($cols['device']);
    }

    echo(get_table_header($cols, $vars));
    echo('<tbody>' . PHP_EOL);
}

function print_processor_row($processor, $vars)
{
    echo generate_processor_row($processor, $vars);
}

function generate_processor_row($processor, $vars)
{
    global $config;

    $table_cols = 4;
    if ($vars['page'] != "device" && $vars['popup'] != TRUE) {
        $table_cols++;
    } // Add a column for device.

    // FIXME should that really be done here? :-)
    // FIXME - not it shouldn't. we need some per-os rewriting on discovery-time.
    $text_descr = rewrite_entity_name($processor['processor_descr'], 'processor');
    //$text_descr = $processor['processor_descr'];
    //$text_descr = str_replace("Routing Processor", "RP", $text_descr);
    //$text_descr = str_replace("Switching Processor", "SP", $text_descr);
    //$text_descr = str_replace("Sub-Module", "Module ", $text_descr);
    //$text_descr = str_replace("DFC Card", "DFC", $text_descr);

    $graph_array           = [];
    $graph_array['to']     = get_time();
    $graph_array['id']     = $processor['processor_id'];
    $graph_array['type']   = 'processor_usage';
    $graph_array['legend'] = "no";

    $link_array         = $graph_array;
    $link_array['page'] = "graphs";
    unset($link_array['height'], $link_array['width'], $link_array['legend']);
    $link_graph = generate_url($link_array);

    $link = generate_url(["page" => "device", "device" => $processor['device_id'], "tab" => "health", "metric" => 'processor']);

    $overlib_content = generate_overlib_content($graph_array, $processor['hostname'] . " - " . $text_descr);

    $graph_array['width']  = 80;
    $graph_array['height'] = 20;
    $graph_array['bg']     = 'ffffff00';
    $graph_array['from']   = get_time('day');
    $mini_graph            = generate_graph_tag($graph_array);

    $perc       = round($processor['processor_usage']);
    $background = get_percentage_colours($perc);

    $processor['html_row_class'] = $background['class'];

    $row = '<tr class="' . $processor['html_row_class'] . '">
          <td class="state-marker"></td>';

    if ($vars['page'] != "device" && $vars['popup'] != TRUE) {
        $row .= '<td class="entity">' . generate_device_link($processor) . '</td>';
    }

    $row .= '  <td class="entity">' . generate_entity_link('processor', $processor) . '</td>
      <td>' . overlib_link($link_graph, $mini_graph, $overlib_content) . '</td>
      <td><a href="' . $link_graph . '">
        ' . print_percentage_bar(400, 20, $perc, $perc . "%", "ffffff", $background['left'], (100 - $perc) . "%", "ffffff", $background['right']) . '
        </a>
      </td>
    </tr>
   ';

    if ($vars['view'] == "graphs" || in_array($processor['processor_id'], (array)$vars['processor_id'])) {

        $vars['graph'] = "usage";

        $row .= '<tr class="' . $processor['html_row_class'] . '">';
        $row .= '<td class="state-marker"></td>';
        $row .= '<td colspan=' . $table_cols . '>';

        unset($graph_array['height'], $graph_array['width'], $graph_array['legend']);
        $graph_array['to']   = get_time();
        $graph_array['id']   = $processor['processor_id'];
        $graph_array['type'] = 'processor_' . $vars['graph'];

        $row .= generate_graph_row($graph_array, TRUE);

        $row .= '</td></tr>';
    } # endif graphs

    return $row;
}

function print_processor_form($vars, $single_device = FALSE)
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
        $form_items['devices'] = generate_form_values('device', dbFetchColumn('SELECT DISTINCT `device_id` FROM `processors`'));

        $form['row'][0]['device_id'] = [
          'type'   => 'multiselect',
          'name'   => 'Device',
          'value'  => $vars['device_id'],
          'grid'   => 2,
          'width'  => '100%', //'180px',
          'values' => $form_items['devices']];
    }

    //$sensor_permitted = generate_query_permitted(array('device', 'sensor'));
    $form['row'][0]['processor_descr'] = [
      'type'        => 'text',
      'placeholder' => 'Processor',
      'width'       => '100%', //'180px',
      'grid'        => 6,
      'value'       => $vars['processor_descr']];

    // Groups
    foreach (get_type_groups('processor') as $entry) {
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
