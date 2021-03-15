<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
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
 * @return array $sensor
 *
 */
// TESTME needs unit testing
function humanize_sensor(&$sensor)
{
  global $config;

  // Exit if already humanized
  if ($sensor['humanized']) { return; }

  $sensor['sensor_symbol'] = $GLOBALS['config']['sensor_types'][$sensor['sensor_class']]['symbol'];
  $sensor['sensor_format'] = strval($GLOBALS['config']['sensor_types'][$sensor['sensor_class']]['format']);
  $sensor['state_class']   = ''; //'text-success';

  // Generate "pretty" thresholds
  if (is_numeric($sensor['sensor_limit_low']))
  {
    $sensor_threshold_low = format_value($sensor['sensor_limit_low'], $sensor['sensor_format']) . $sensor['sensor_symbol'];
  } else {
    $sensor_threshold_low = "&infin;";
  }

  if (is_numeric($sensor['sensor_limit_low_warn']))
  {
    $sensor_warn_low = format_value($sensor['sensor_limit_low_warn'], $sensor['sensor_format']) . $sensor['sensor_symbol'];
  } else {
    $sensor_warn_low = NULL;
  }

  if ($sensor_warn_low) { $sensor_threshold_low = $sensor_threshold_low . " (".$sensor_warn_low.")"; }

  if (is_numeric($sensor['sensor_limit']))
  {
    $sensor_threshold_high = format_value($sensor['sensor_limit'], $sensor['sensor_format']) . $sensor['sensor_symbol'];
  } else {
    $sensor_threshold_high = "&infin;";
  }

  if (is_numeric($sensor['sensor_limit_warn']))
  {
    $sensor_warn_high = format_value($sensor['sensor_limit_warn'], $sensor['sensor_format']) . $sensor['sensor_symbol'];
  } else {
    $sensor_warn_high = "&infin;";
  }

  if ($sensor_warn_high) { $sensor_threshold_high = "(".$sensor_warn_high.") " . $sensor_threshold_high; }

  $sensor['sensor_thresholds'] = $sensor_threshold_low . ' - ' . $sensor_threshold_high;

  // generate pretty value
  if (!is_numeric($sensor['sensor_value']))
  {
    $sensor['human_value'] = 'NaN';
    $sensor['sensor_symbol'] = '';
  } else {
    $sensor['human_value'] = format_value($sensor['sensor_value'], $sensor['sensor_format'], 2, 4);
  }

  if (isset($config['entity_events'][$sensor['sensor_event']]))
  {
    $sensor = array_merge($sensor, $config['entity_events'][$sensor['sensor_event']]);
  } else {
    $sensor['event_class'] = 'label label-primary';
    $sensor['row_class']   = '';
  }
  //r($sensor);
  if ($sensor['sensor_deleted'])
  {
    $sensor['row_class']   = 'disabled';
  }

  $device = &$GLOBALS['cache']['devices']['id'][$sensor['device_id']];
  if ((isset($device['status']) && !$device['status']) || (isset($device['disabled']) && $device['disabled']))
  {
    $sensor['row_class']     = 'error';
  }

  // Set humanized entry in the array so we can tell later
  $sensor['humanized'] = TRUE;
}

function build_sensor_query($vars, $query_count = FALSE)
{

  if ($query_count)
  {
    $sql = "SELECT COUNT(*) FROM `sensors`";
  } else {
    $sql  = "SELECT * FROM `sensors`";

    if ($vars['sort'] == 'hostname' || $vars['sort'] == 'device' || $vars['sort'] == 'device_id')
    {
      $sql .= ' LEFT JOIN `devices` USING(`device_id`)';
    }
  }

  $sql .= " WHERE `sensor_deleted` = 0";

  // Build query
  foreach($vars as $var => $value)
  {
    switch ($var)
    {
      case "group":
      case "group_id":
        $values = get_group_entities($value);
        $sql .= generate_query_values($values, 'sensors.sensor_id');
        break;
      case "device":
      case "device_id":
        $sql .= generate_query_values($value, 'sensors.device_id');
        break;
      case "id":
      case "sensor_id":
        $sql .= generate_query_values($value, 'sensors.sensor_id');
        break;
      case "entity_id":
        $sql .= generate_query_values($value, 'sensors.measured_entity');
        break;
      case "entity_type":
        $sql .= generate_query_values($value, 'sensors.measured_class');
        break;
      case 'entity_state':
      case "measured_state":
        $sql .= build_entity_measured_where('sensor', ['measured_state' => $value]);
        break;
      case "metric":
        // old metric param not allow array
        if (!isset($GLOBALS['config']['sensor_types'][$value]))
        {
          break;
        }
      case 'class':
      case "sensor_class":
        $sql .= generate_query_values($value, 'sensor_class');
        break;
      case "descr":
      case "sensor_descr":
        $sql .= generate_query_values($value, 'sensors.sensor_descr', '%LIKE%');
        break;
      case "type":
      case "sensor_type":
        $sql .= generate_query_values($value, 'sensor_type', '%LIKE%');
        break;
      case "event":
      case "sensor_event":
        $sql .= generate_query_values($value, 'sensor_event');
        break;
    }
  }
  // $sql .= $GLOBALS['cache']['where']['devices_permitted'];

  $sql .= generate_query_permitted(array('device', 'sensor'));

  // If need count, just return sql without sorting
  if ($query_count)
  {
    return $sql;
  }

  switch ($vars['sort_order'])
  {
    case 'desc':
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


  switch($vars['sort'])
  {
    case 'device':
      $sql .= ' ORDER BY `hostname` '.$sort_order;
      break;
    case 'descr':
    case 'event':
      $sql .= ' ORDER BY `sensor_'.$vars['sort'].'` '.$sort_order;
      break;
    case 'value':
    case 'last_change':
      $sql .= ' ORDER BY `sensor_'.$vars['sort'].'` '.$sort_order;
      break;
    default:
      // $sql .= ' ORDER BY `hostname` '.$sort_order.', `sensor_descr` '.$sort_order;
  }

  if(isset($vars['pageno']))
  {
    $start = $vars['pagesize'] * ($vars['pageno'] - 1);
    $sql .= ' LIMIT '.$start.','.$vars['pagesize'];
  }

  return $sql;
}

function print_sensor_table($vars)
{

  pagination($vars, 0, TRUE); // Get default pagesize/pageno

  $sql = build_sensor_query($vars);

  //r($vars);
  //r($sql);

  $sensors = array();
  //foreach(dbFetchRows($sql, NULL, TRUE) as $sensor)
  foreach(dbFetchRows($sql) as $sensor)
  {
    //if (isset($GLOBALS['cache']['devices']['id'][$sensor['device_id']]))
    //{
      $sensor['hostname'] = $GLOBALS['cache']['devices']['id'][$sensor['device_id']]['hostname'];
      $sensors[] = $sensor;
    //}
  }

  //$sensors_count = count($sensors); // This is count incorrect, when pagination used!
  //$sensors_count = dbFetchCell(build_sensor_query($vars, TRUE), NULL, TRUE);
  $sensors_count = dbFetchCell(build_sensor_query($vars, TRUE));

  // Pagination
  $pagination_html = pagination($vars, $sensors_count);
  echo $pagination_html;

  echo generate_box_open();

  print_sensor_table_header($vars);

  foreach($sensors as $sensor)
  {
    print_sensor_row($sensor, $vars);
  }

  echo("</tbody></table>");

  echo generate_box_close();

  echo $pagination_html;
}

function print_sensor_table_header($vars)
{
  if ($vars['view'] == "graphs" || $vars['graph'] || isset($vars['id']))
  {
    $stripe_class = "table-striped-two";
  } else {
    $stripe_class = "table-striped";
  }

  echo('<table class="table ' . $stripe_class . ' table-condensed ">' . PHP_EOL);
  $cols = [];
  $cols[]              = array(NULL, 'class="state-marker"');
  $cols['device']      = array('Device', 'style="width: 250px;"');
  //$cols[]              = array(NULL, 'class="no-width"'); // Measured entity link
  $cols['descr']       = array('Description');
  $cols['class']       = array('Class', 'style="width: 100px;"');
  $cols['mib']         = array('MIB::Object');
  $cols[]              = array('Thresholds', 'style="width: 100px;"');
  $cols[]              = array('History');
  $cols['last_change'] = array('Last&nbsp;changed', 'style="width: 80px;"');
  $cols['event']       = array('Event', 'style="width: 60px; text-align: right;"');
  $cols['value']       = array('Value', 'style="width: 80px; text-align: right;"');

  if ($vars['page'] == "device")  { unset($cols['device']); }
  if ($vars['page'] != "device" || $vars['tab'] == "overview")  { unset($cols['mib']); unset($cols['object']); }
  if (!$vars['show_class'])       { unset($cols['class']); }
  if ($vars['tab'] == "overview") { unset($cols[2]); } // Thresholds

  echo(get_table_header($cols, $vars));
  echo('<tbody>' . PHP_EOL);
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

  $graph_array           = array();
  $graph_array['to']     = $config['time']['now'];
  $graph_array['id']     = $sensor['sensor_id'];
  $graph_array['type']   = "sensor_graph";
  $graph_array['width']  = 80;
  $graph_array['height'] = 20;
  $graph_array['bg']     = 'ffffff00';
  $graph_array['from']   = $config['time']['day'];

  if ($sensor['sensor_event'] && is_numeric($sensor['sensor_value']))
  {
    $mini_graph = generate_graph_tag($graph_array);
  } else {
    // Do not show "Draw Error" minigraph
    $mini_graph = '';
  }

  $row = '
      <tr class="'.$sensor['row_class'].'">
        <td class="state-marker"></td>';

  if ($vars['page'] != "device" && $vars['popup'] != TRUE)
  {
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

  if ($vars['show_class'])
  {
    $row .= '        <td>' . nicecase($sensor['sensor_class']). '</td>' . PHP_EOL;
    $table_cols++;
  }

  // FIXME -- Generify this. It's not just for sensors.
  if ($vars['page'] == "device" && $vars['tab'] != "overview")
  {
    $row .= '        <td>' .  (strlen($sensor['sensor_mib']) ? '<a href="https://mibs.observium.org/mib/'.$sensor['sensor_mib'].'/" target="_blank">' .nicecase($sensor['sensor_mib']) .'</a>' : '') . ( ( strlen($sensor['sensor_mib']) && strlen($sensor['sensor_object'])) ? '::' : '') .(strlen($sensor['sensor_mib']) ? '<a href="https://mibs.observium.org/mib/'.$sensor['sensor_mib'].'/#'.$sensor['sensor_object'].'" target="_blank">' .$sensor['sensor_object'] .'</a>' : ''). '.'.$sensor['sensor_index'].'</td>' . PHP_EOL;
    $table_cols++;

  }


  if ($vars['tab'] != 'overview')
  {
    $row .= '        <td><span class="label ' . ($sensor['sensor_custom_limit'] ? 'label-warning' : '') . '">' . $sensor['sensor_thresholds'] . '</span></td>' . PHP_EOL;
    $table_cols++;
  }
  $row .= '        <td style="width: 90px; text-align: right;">' . generate_entity_link('sensor', $sensor, $mini_graph, NULL, FALSE) . '</td>';

  if ($vars['tab'] != 'overview')
  {
    $row .= '        <td style="white-space: nowrap">' . ($sensor['sensor_last_change'] == '0' ? 'Never' : generate_tooltip_link(NULL, format_uptime(($config['time']['now'] - $sensor['sensor_last_change']), 'short-2') . ' ago', format_unixtime($sensor['sensor_last_change']))) . '</td>';
    $table_cols++;
    $row .= '        <td style="text-align: right;"><strong>' . generate_tooltip_link('', $sensor['sensor_event'], $sensor['event_descr'], $sensor['event_class']) . '</strong></td>';
    $table_cols++;
  }
  $sensor_tooltip = $sensor['event_descr'];

  // Append value in alternative units to tooltip
  if (isset($config['sensor_types'][$sensor['sensor_class']]['alt_units']))
  {
    foreach (value_to_units($sensor['sensor_value'],
                            $config['sensor_types'][$sensor['sensor_class']]['symbol'],
                            $sensor['sensor_class'],
                            $config['sensor_types'][$sensor['sensor_class']]['alt_units']) as $unit => $unit_value)
    {
      if (is_numeric($unit_value)) { $sensor_tooltip .= "<br />${unit_value}${unit}"; }
    }
  }

  $row .= '        <td style="width: 80px; text-align: right;"><strong>' . generate_tooltip_link('', $sensor['human_value'] . $sensor['sensor_symbol'], $sensor_tooltip, $sensor['event_class']) . '</strong>
        </tr>' . PHP_EOL;

  if ($vars['view'] == "graphs" || $vars['id'] == $sensor['sensor_id']) { $vars['graph'] = "graph"; }
  if ($vars['graph'])
  {
    $row .= '
      <tr class="'.$sensor['row_class'].'">
        <td class="state-marker"></td>
        <td colspan="'.$table_cols.'">';

    $graph_array = array();
    $graph_array['to']     = $config['time']['now'];
    $graph_array['id']     = $sensor['sensor_id'];
    $graph_array['type']   = 'sensor_'.$vars['graph'];

    $row .= generate_graph_row($graph_array, TRUE);

    $row .= '</td></tr>';
  } # endif graphs

  return $row;
}

function print_sensor_form($vars, $single_device = FALSE)
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
    $form_items['devices'] = generate_form_values('device', dbFetchColumn('SELECT DISTINCT `device_id` FROM `sensors`'));

    $form['row'][0]['device_id'] = array(
      'type'        => 'multiselect',
      'name'        => 'Device',
      'value'       => $vars['device_id'],
      'grid'        => 2,
      'width'       => '100%', //'180px',
      'values'      => $form_items['devices']);
  }

  $sensor_permitted = generate_query_permitted(array('device', 'sensor'));
  foreach (['sensor_class' => 'Sensor Class', 'sensor_event' => 'Sensor Event'] as $param => $param_name)
  {
    $sql = 'SELECT DISTINCT `'.$param.'` FROM `sensors` WHERE `sensor_deleted` = ?' . $sensor_permitted;
    $entries = dbFetchColumn($sql, [0]);
    asort($entries);
    foreach ($entries as $entry)
    {
      if ($entry == '') { $entry = OBS_VAR_UNSET; }
      if ($param == 'sensor_class')
      {
        $name = nicecase($entry);
        if (isset($config['sensor_types'][$entry]['icon']))
        {
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
    if (!isset($vars[$param]) && isset($vars[$short_param]))
    {
      $vars[$param] = $vars[$short_param];
    }

    $form['row'][0][$param]    = array(
      'type'        => 'multiselect',
      'name'        => $param_name,
      'width'       => '100%', //'180px',
      'grid'        => 2,
      'value'       => $vars[$param],
      'values'      => $form_items[$param]);
  }
  // Currently unused, just dumb space
  $form['row'][0]['sensor_value'] = array(
    'type'        => 'hidden',
    'name'        => 'Value',
    'width'       => '100%', //'180px',
    'grid'        => 2,
    'value'       => $vars['sensor_value']);

  // Measured entities
  $form['row'][0]['measured_state'] = array(
    'type'        => 'multiselect',
    'name'        => 'Measured State',
    'width'       => '100%', //'180px',
    'grid'        => 2,
    'value'       => $vars['measured_state'],
    'values'      => ['none'     => ['name' => 'Without Measure',   'icon' => $config['icon']['filter']],
                      'up'       => ['name' => 'Measured UP',       'icon' => $config['icon']['up']],
                      'down'     => ['name' => 'Measured DOWN',     'icon' => $config['icon']['down']],
                      'shutdown' => ['name' => 'Measured SHUTDOWN', 'icon' => $config['icon']['shutdown']]]);


  $form['row'][1]['sensor_descr']    = array(
    'type'        => 'text',
    'placeholder' => 'Sensor description',
    'width'       => '100%', //'180px',
    'grid'        => 4,
    'value'       => $vars['sensor_descr']);


  $form['row'][1]['sensor_type']    = array(
    'type'        => 'text',
    'placeholder' => 'Sensor type',
    'width'       => '100%', //'180px',
    'grid'        => 4,
    'value'       => $vars['status_descr']);

  // Groups
  foreach (get_type_groups('sensor') as $entry)
  {
    $form_items['group'][$entry['group_id']] = $entry['group_name'];
  }
  $form['row'][1]['group']    = array(
    'community'   => FALSE,
    'type'        => 'multiselect',
    'name'        => 'Select Groups',
    'width'       => '100%', //'180px',
    'grid'        => 2,
    'value'       => $vars['group'],
    'values'      => $form_items['group']);

  $form['row'][1]['search']   = array(
    'type'        => 'submit',
    'grid'        => 2,
    //'name'        => 'Search',
    //'icon'        => 'icon-search',
    'right'       => TRUE);


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
  $panel_form['row'][0]['sensor_class']   = $form['row'][0]['sensor_class'];

  $panel_form['row'][1]['sensor_event']   = $form['row'][0]['sensor_event'];
  $panel_form['row'][1]['sensor_value']   = $form['row'][0]['sensor_value'];

  $panel_form['row'][2]['measured_state'] = $form['row'][0]['measured_state'];
  $panel_form['row'][2]['group']          = $form['row'][1]['group'];

  $panel_form['row'][3]['sensor_type']    = $form['row'][1]['sensor_type'];

  $panel_form['row'][4]['sensor_descr']   = $form['row'][1]['sensor_descr'];

  //$panel_form['row'][5]['sort'] = $form['row'][0]['sort'];
  $panel_form['row'][5]['search'] = $form['row'][1]['search'];

  // Register custom panel
  register_html_panel(generate_form($panel_form));
}

// EOF
