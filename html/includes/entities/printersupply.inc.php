<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

function build_printersupplies_query($vars)
{
  $sql = 'SELECT * FROM `printersupplies`';
  $sql .= ' WHERE 1' . generate_query_permitted(array('device'));

  // Build query
  foreach($vars as $var => $value)
  {
    switch ($var)
    {
      case "group":
      case "group_id":
        $values = get_group_entities($value);
        $sql .= generate_query_values($values, 'printersupplies.supply_id');
        break;
      case "device":
      case "device_id":
        $sql .= generate_query_values($value, 'printersupplies.device_id');
        break;
      case "supply":
      case "supply_type";
        $sql .= generate_query_values($value, 'printersupplies.supply_type');
        break;
      case "colour":
      case "supply_colour";
        $sql .= generate_query_values($value, 'supply_colour');
        break;
      case "descr":
      case "supply_descr";
        $sql .= generate_query_values($value, 'supply_descr', '%LIKE%');
        break;
    }
  }

  return $sql;
}

function print_printersupplies_table($vars)
{
  $supplies = array();
  foreach(dbFetchRows(build_printersupplies_query($vars)) as $supply)
  {
    global $cache;

    if (isset($cache['devices']['id'][$supply['device_id']]))
    {
      $supply['hostname'] = $cache['devices']['id'][$supply['device_id']]['hostname'];
      $supply['html_row_class'] = $cache['devices']['id'][$supply['device_id']]['html_row_class'];
      $supplies[] = $supply;
    }
  }
  $supplies = array_sort_by($supplies, 'hostname', SORT_ASC, SORT_STRING, 'supply_descr', SORT_ASC, SORT_STRING);
  $supplies_count = count($supplies);

  echo generate_box_open();

  // Pagination
  $pagination_html = pagination($vars, $supplies_count);
  echo $pagination_html;

  if ($vars['pageno'])
  {
    $supplies = array_chunk($supplies, $vars['pagesize']);
    $supplies = $supplies[$vars['pageno'] - 1];
  }
  // End Pagination

  if ($vars['view'] == "graphs")
  {
    $stripe_class = "table-striped-two";
  } else {
    $stripe_class = "table-striped";
  }

  // Allow the table to be printed headerless for use in some places.
  if ($vars['headerless'] != TRUE)
  {
    echo('<table class="table ' . $stripe_class . '  table-condensed">');
    echo('  <thead>');

    echo '<tr class="strong">';
    echo '<th class="state-marker"></th>';
    if ($vars['page'] != "device" && $vars['popup'] != TRUE)
    {
      echo('      <th style="width: 250px;">Device</th>');
    }
    echo '<th>Toner</th>';
    if (!isset($vars['supply']))
    {
      echo '<th>Type</th>';
    }
    echo '<th></th>';
    echo '<th>Level</th>';
    echo '<th>Remaining</th>';
    echo '</tr>';

    echo '</thead>';
  }

  foreach($supplies as $supply)
  {
    print_printersupplies_row($supply, $vars);
  }

  echo("</table>");

  echo generate_box_close();

  echo $pagination_html;
}

function print_printersupplies_row($supply, $vars)
{
  echo generate_printersupplies_row($supply, $vars);
}

function generate_printersupplies_row($supply, $vars)
{
  $graph_type = "printersupply_usage";

  $table_cols = 5;

  $total = $supply['supply_capacity'];
  $perc = $supply['supply_value'];

  $graph_array['type'] = $graph_type;
  $graph_array['id'] = $supply['supply_id'];
  $graph_array['from'] = $GLOBALS['config']['time']['day'];
  $graph_array['to'] = $GLOBALS['config']['time']['now'];
  $graph_array['height'] = "20";
  $graph_array['width'] = "80";

  if ($supply['supply_colour'] != '')
  {
    $background = toner_to_colour($supply['supply_colour'], $perc);
  } else {
    $background = toner_to_colour($supply['supply_descr'], $perc);
  }

  /// FIXME - popup for printersupply entity.

  $output  = '<tr class="' . $supply['html_row_class'] . '">';
  $output .= '<td class="state-marker"></td>';
  if ($vars['popup'] == TRUE )
  {
    $output .= '<td style="width: 40px; text-align: center;">'.get_icon($GLOBALS['config']['entities']['printersupply']['icon']).'</td>';
  } else {
    //$output .= '<td style="width: 1px;"></td>';
  }

  if ($vars['page'] != "device" && $vars['popup'] != TRUE)
  {
    $output .= '<td class="entity">' . generate_device_link($supply) . '</td>';
    $table_cols++;
  }
  $output .=  '<td class="entity">' . generate_entity_link('printersupply', $supply) . '</td>';

  if (!isset($vars['supply']))
  {
    $output .=  '<td><span class="label">' . nicecase($supply['supply_type']) . '</label></td>';
  }

  $output .=  '<td style="width: 70px;">' . generate_graph_popup($graph_array) . '</td>';
  $output .=  '<td style="width: 200px;">' . print_percentage_bar(400, 20, $perc, $perc . '%', 'ffffff', $background['right'], NULL, "ffffff", $background['left']) . '</td>';
  if ($vars['popup'] != TRUE)
  {
    $output .= '<td style="width: 50px; text-align: right;"><span class="label">' . $perc . '%</span></td>';
  }
  $output .=  '</tr>';

  if ($vars['view'] == "graphs")
  {
    $output .= '<tr class="' . $supply['html_row_class'] . '">';
    $output .= '<td class="state-marker"></td>';
    $output .=  '<td colspan='.$table_cols.'>';

    unset($graph_array['height'], $graph_array['width'], $graph_array['legend']);
    $graph_array['to'] = $config['time']['now'];
    $graph_array['id'] = $supply['supply_id'];
    $graph_array['type'] = $graph_type;

    $output .= generate_graph_row($graph_array, TRUE);

    $output .= "</td></tr>";
  } # endif graphs

  return $output;
}

function print_printersupplies_form($vars, $single_device = FALSE)
{
  global $config;

  $form = array('type'  => 'rows',
                'space' => '10px',
                'submit_by_key' => TRUE,
                'url'   => generate_url($vars));

  $form_items = array();

  if ($single_device)
  {
    // Single device, just hidden field
    $form['row'][0]['device_id'] = array(
      'type'        => 'hidden',
      'name'        => 'Device',
      'value'       => $vars['device_id'],
      'grid'        => 2,
      'width'       => '100%');
  } else {
    $form_items['devices'] = generate_form_values('device', dbFetchColumn('SELECT DISTINCT `device_id` FROM `printersupplies`'));

    $form['row'][0]['device_id'] = array(
      'type'        => 'multiselect',
      'name'        => 'Device',
      'value'       => $vars['device_id'],
      'grid'        => 2,
      'width'       => '100%', //'180px',
      'values'      => $form_items['devices']);
  }

  //$sensor_permitted = generate_query_permitted(array('device', 'sensor'));
  $form['row'][0]['supply_descr']    = array(
    'type'        => 'text',
    'placeholder' => 'Toner',
    'width'       => '100%', //'180px',
    'grid'        => 3,
    'value'       => $vars['status_descr']);

  foreach (['supply_colour' => 'Colour', 'supply_type' => 'Type'] as $param => $param_name)
  {
    $sql = 'SELECT DISTINCT `'.$param.'` FROM `printersupplies` WHERE 1' . $GLOBALS['cache']['where']['devices_permitted'];
    $entries = dbFetchColumn($sql);
    asort($entries);
    foreach ($entries as $entry)
    {
      if ($entry == '') { $entry = OBS_VAR_UNSET; }
      $name = nicecase($entry);
      $form_items[$param][$entry] = $name;
    }

    $form['row'][0][$param]    = array(
      'type'        => 'multiselect',
      'name'        => $param_name,
      'width'       => '100%', //'180px',
      'grid'        => $param == 'supply_colour' ? 1: 2,
      'value'       => $vars[$param],
      'values'      => $form_items[$param]);
  }

  // Groups
  foreach (get_type_groups('printersupply') as $entry)
  {
    $form_items['group'][$entry['group_id']] = $entry['group_name'];
  }
  $form['row'][0]['group']    = array(
    'community'   => FALSE,
    'type'        => 'multiselect',
    'name'        => 'Select Groups',
    'width'       => '100%', //'180px',
    'grid'        => 2,
    'value'       => $vars['group'],
    'values'      => $form_items['group']);

  $form['row'][0]['search']   = array(
    'type'        => 'submit',
    'grid'        => 2,
    //'name'        => 'Search',
    //'icon'        => 'icon-search',
    'right'       => TRUE,
  );

  // Show search form
  echo '<div class="hidden-xl">';
  print_form($form);
  echo '</div>';

  // Custom panel form
  $panel_form = array('type'  => 'rows',
                      'title' => 'Search Sensors',
                      'space' => '10px',
                      //'brand' => NULL,
                      //'class' => '',
                      'submit_by_key' => TRUE,
                      'url'   => generate_url($vars));

  // Clean grids
  foreach (array_keys($form['row'][0]) as $param)
  {
    unset($form['row'][0][$param]['grid']);
  }
  foreach (array_keys($form['row'][1]) as $param)
  {
    unset($form['row'][1][$param]['grid']);
  }
  // Copy forms
  $panel_form['row'][0]['device_id']      = $form['row'][0]['device_id'];
  $panel_form['row'][0]['group']          = $form['row'][0]['group'];

  $panel_form['row'][1]['supply_colour']  = $form['row'][0]['supply_colour'];
  $panel_form['row'][1]['supply_type']    = $form['row'][0]['supply_type'];

  //$panel_form['row'][2]['measured_state'] = $form['row'][0]['measured_state'];
  //$panel_form['row'][2]['group']          = $form['row'][1]['group'];

  $panel_form['row'][3]['supply_descr']   = $form['row'][0]['supply_descr'];

  //$panel_form['row'][5]['sort'] = $form['row'][0]['sort'];
  $panel_form['row'][5]['search'] = $form['row'][0]['search'];

  // Register custom panel
  register_html_panel(generate_form($panel_form));
}

// EOF
