<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

function generate_storage_query($vars)
{

    $sql  = "SELECT * FROM `storage`";

    if (in_array($vars['sort'], [ 'hostname', 'device', 'device_id' ]))
    {
      $sql .= ' LEFT JOIN `devices` USING(`device_id`)';
    }

    $sql .= ' WHERE 1' . generate_query_permitted(array('device'));

    // Build query
    if (!isset($vars['ignored'])) { $vars['ignored'] = 0; }
    foreach($vars as $var => $value)
    {
      switch ($var) {
        case "group":
        case "group_id":
          $values = get_group_entities($value);
          $sql .= generate_query_values($values, 'storage.storage_id');
          break;
        case 'device_group_id':
        case 'device_group':
          $values = get_group_entities($value, 'device');
          $sql .= generate_query_values($values, 'storage.device_id');
          break;
        case "device":
        case "device_id":
          $sql .= generate_query_values($value, 'storage.device_id');
          break;
        case "descr":
        case "storage_descr";
          $sql .= generate_query_values($value, 'storage_descr', '%LIKE%');
          break;
        case 'ignored':
          $sql .= generate_query_values($value, 'storage.storage_ignore');
          break;
      }
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
      case 'usage':
        $sql .= ' ORDER BY `storage_perc` '.$sort_neg;
        break;
      case 'descr':
      case 'mountpoint':
        $sql .= ' ORDER BY `storage_descr` '.$sort_order;
        break;
      case 'size':
      case 'free':
      case 'used':
        $sql .= ' ORDER BY `storage_'.$vars['sort'].'` '.$sort_order;
        break;
      case 'device':
      case 'hostname':
        $sql .= ' ORDER BY `hostname` '.$sort_order;
        break;
      default:
        $sql .= ' ORDER BY `storage_descr` '.$sort_order;
        break;
    }

    return $sql;

}

function print_storage_table($vars)
{

    global $cache, $config;

    $graph_type = "storage_usage";

    $sql = generate_storage_query($vars);

    $storages = array();
    foreach (dbFetchRows($sql) as $storage)
    {
      if (isset($cache['devices']['id'][$storage['device_id']]))
      {
        $storage['hostname']       = $cache['devices']['id'][$storage['device_id']]['hostname'];
        $storage['html_row_class'] = $cache['devices']['id'][$storage['device_id']]['html_row_class'];
        $storages[] = $storage;
      }
    }

    $storages_count = count($storages);

    // Pagination
    $pagination_html = pagination($vars, $storages_count);
    echo $pagination_html;

    if ($vars['pageno'])
    {
        $storages = array_chunk($storages, $vars['pagesize']);
        $storages = $storages[$vars['pageno']-1];
    }
    // End Pagination

    echo generate_box_open();

    print_storage_table_header($vars);

    foreach ($storages as $storage)
    {
      print_storage_row($storage, $vars);
    }

    echo("</tbody></table>");

    echo generate_box_close();

    echo $pagination_html;

}

function print_storage_table_header($vars)
{
  if ($vars['view'] === "graphs" || isset($vars['graph']))
  {
    $table_class = OBS_CLASS_TABLE_STRIPED_TWO;
  } else {
    $table_class = OBS_CLASS_TABLE_STRIPED;
  }

  echo('<table class="' . $table_class . '">' . PHP_EOL);
  $cols = array(
                    array(NULL, 'class="state-marker"'),
    'device'     => array('Device', 'style="width: 250px;"'),
    'mountpoint' => array('Mountpoint'),
    'size'       => array('Size', 'style="width: 100px;"'),
    'used'       => array('Used', 'style="width: 100px;"'),
    'free'       => array('Free', 'style="width: 100px;"'),
                    array('', 'style="width: 100px;"'),
    'usage'      => array('Usage %', 'style="width: 200px;"'),
  );

  if ($vars['page'] === "device")
  {
    unset($cols['device']);
  }

  echo(get_table_header($cols, $vars));
  echo('<tbody>' . PHP_EOL);
}

function print_storage_row($storage, $vars) {

  echo generate_storage_row($storage, $vars);

}

function generate_storage_row($storage, $vars) {

  global $config;

  $table_cols = 8;
  if ($vars['page'] !== "device" && $vars['popup'] != TRUE) { $table_cols++; } // Add a column for device.

  if(isset($vars['graph_type']) && $vars['graph_type'] == "perc") 

  $graph_array           = array();
  $graph_array['to']     = $config['time']['now'];
  $graph_array['id']     = $storage['storage_id'];
  $graph_array['type']   = 'storage_usage';
  $graph_array['legend'] = "no";

  $link_array = $graph_array;
  $link_array['page'] = "graphs";
  unset($link_array['height'], $link_array['width'], $link_array['legend']);
  $link_graph = generate_url($link_array);

  $link = generate_url( array("page" => "device", "device" => $storage['device_id'], "tab" => "health", "metric" => 'storage'));

  $overlib_content = generate_overlib_content($graph_array, $storage['hostname'] . ' - ' . $storage['storage_descr']);

  $graph_array['width'] = 80; $graph_array['height'] = 20; $graph_array['bg'] = 'ffffff00';
  $graph_array['from'] = $config['time']['day'];
  $mini_graph =  generate_graph_tag($graph_array);

  $total = formatStorage($storage['storage_size']);
  $used = formatStorage($storage['storage_used']);
  $free = formatStorage($storage['storage_free']);

  $background = get_percentage_colours($storage['storage_perc']);

  if ($storage['storage_ignore'])
  {
    $storage['row_class'] = 'suppressed';
  } else {
    $storage['row_class'] = $background['class'];
  }

  $row = '<tr class="ports ' . $storage['row_class'] . '">
          <td class="state-marker"></td>';

  if ($vars['page'] !== "device" && $vars['popup'] != TRUE) { $row .= '<td class="entity">' . generate_device_link($storage) . '</td>'; }

  $row .= '  <td class="entity">'.generate_entity_link('storage', $storage).'</td>
      <td>'.$total.'</td>
      <td>'.$used.'</td>
      <td>'.$free.'</td>
      <td>'.overlib_link($link_graph, $mini_graph, $overlib_content).'</td>
      <td><a href="'.$link_graph.'">
        ' . print_percentage_bar(400, 20, $storage['storage_perc'], $storage['storage_perc'].'%', "ffffff", $background['left'], 100-$storage['storage_perc']."%" , "ffffff", $background['right']).'
        </a>
      </td>
    </tr>
  ';

  if ($vars['view'] === "graphs" && !isset($vars['graph'])) { $vars['graph'] = "bytes,perc"; }



  if (isset($vars['graph']))
  {
      $graph_types = explode(',', $vars['graph']);

      foreach ($graph_types AS $graph_type) {
          $graph_type = 'storage_'.$graph_type;

          $row .= '<tr class="' . $storage['row_class'] . '">';
          $row .= '<td class="state-marker"></td>';
          $row .= '<td colspan="' . $table_cols . '">';

          unset($graph_array['height'], $graph_array['width'], $graph_array['legend']);
          $graph_array['to'] = $config['time']['now'];
          $graph_array['id'] = $storage['storage_id'];
          $graph_array['type'] = $graph_type;

          $row .= generate_graph_row($graph_array, TRUE);

          $row .= '</td></tr>';
      }
  } # endif graphs

  return $row;
}

function print_storage_form($vars, $single_device = FALSE)
{
  //global $config;

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
    $form_items['devices'] = generate_form_values('device', dbFetchColumn('SELECT DISTINCT `device_id` FROM `storage`'));

    $form['row'][0]['device_id'] = array(
      'type'        => 'multiselect',
      'name'        => 'Device',
      'value'       => $vars['device_id'],
      'grid'        => 2,
      'width'       => '100%', //'180px',
      'values'      => $form_items['devices']);
  }

  //$sensor_permitted = generate_query_permitted(array('device', 'sensor'));
  $form['row'][0]['storage_descr'] = array(
    'type'        => 'text',
    'placeholder' => 'Storage',
    'width'       => '100%', //'180px',
    'grid'        => 6,
    'value'       => $vars['storage_descr']);

  // Groups
  foreach (get_type_groups('storage') as $entry)
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
                      'title' => 'Search Storage',
                      'space' => '10px',
                      //'brand' => NULL,
                      //'class' => '',
                      'submit_by_key' => TRUE,
                      'url'   => generate_url($vars));

  // Clean grids
  foreach ($form['row'] as $row => $rows) {
    foreach (array_keys($rows) as $param) {
      if (isset($form['row'][$row][$param]['grid'])) { unset($form['row'][$row][$param]['grid']); }
    }
  }

  // Copy forms
  $panel_form['row'][0]['device_id']      = $form['row'][0]['device_id'];
  $panel_form['row'][0]['group']          = $form['row'][0]['group'];

  //$panel_form['row'][1]['supply_colour']  = $form['row'][0]['supply_colour'];
  //$panel_form['row'][1]['supply_type']    = $form['row'][0]['supply_type'];

  //$panel_form['row'][2]['measured_state'] = $form['row'][0]['measured_state'];
  //$panel_form['row'][2]['group']          = $form['row'][1]['group'];

  $panel_form['row'][3]['storage_descr']  = $form['row'][0]['storage_descr'];

  //$panel_form['row'][5]['sort'] = $form['row'][0]['sort'];
  $panel_form['row'][5]['search'] = $form['row'][0]['search'];

  // Register custom panel
  register_html_panel(generate_form($panel_form));
}

// EOF