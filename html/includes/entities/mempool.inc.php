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

function build_mempool_query($vars)
{

  global $config, $cache;

  $sql = 'SELECT *, `mempools`.`mempool_id` AS `mempool_id` FROM `mempools`';
  //$sql .= ' LEFT JOIN `mempools-state` USING(`mempool_id`)';

  if ($vars['sort'] === 'hostname' || $vars['sort'] === 'device' || $vars['sort'] === 'device_id') {
    $sql .= ' LEFT JOIN `devices` USING(`device_id`)';
  }

  $sql .= ' WHERE 1' . generate_query_permitted(array('device'));

  // Build query
  foreach ($vars as $var => $value)
  {
    switch ($var)
    {
      case "group":
      case "group_id":
        $values = get_group_entities($value);
        $sql .= generate_query_values($values, 'mempools.mempool_id');
        break;
      case 'device_group_id':
      case 'device_group':
        $values = get_group_entities($value, 'device');
        $sql .= generate_query_values($values, 'mempools.device_id');
        break;
      case "device":
      case "device_id":
        $sql .= generate_query_values($value, 'mempools.device_id');
        break;
      case "descr":
      case "mempool_descr";
        $sql .= generate_query_values($value, 'mempool_descr', '%LIKE%');
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
      $sql .= ' ORDER BY `mempool_used` '.$sort_neg;
      break;
    case 'used':
      $sql .= ' ORDER BY `mempool_perc` '.$sort_neg;
      break;
    case 'hostname':
      $sql .= ' ORDER BY `hostname` '.$sort_order.', `mempool_descr` '.$sort_order;
      break;
    case 'descr':
    default:
      $sql .= ' ORDER BY `mempool_descr` '.$sort_order;
      break;
  }

  return $sql;
}


function print_mempool_table($vars)
{

  global $cache;

  $sql = build_mempool_query($vars);

  $mempools = array();
  foreach (dbFetchRows($sql) as $mempool)
  {
    if (isset($cache['devices']['id'][$mempool['device_id']]))
    {
      $mempool['hostname'] = $cache['devices']['id'][$mempool['device_id']]['hostname'];
      $mempool['html_row_class'] = $cache['devices']['id'][$mempool['device_id']]['html_row_class'];
      $mempools[] = $mempool;
    }
  }

  $mempools_count = count($mempools);

  // Pagination
  $pagination_html = pagination($vars, $mempools_count);
  echo $pagination_html;

  if ($vars['pageno'])
  {
    $mempools = array_chunk($mempools, $vars['pagesize']);
    $mempools = $mempools[$vars['pageno'] - 1];
  }
  // End Pagination

  echo generate_box_open();

  print_mempool_table_header($vars);

  foreach ($mempools as $mempool)
  {
    print_mempool_row($mempool, $vars);
  }

  echo("</tbody></table>");

  echo generate_box_close();

  echo $pagination_html;

}

function print_mempool_table_header($vars)
{
  if ($vars['view'] === "graphs")
  {
    $table_class = OBS_CLASS_TABLE_STRIPED_TWO;
  } else {
    $table_class = OBS_CLASS_TABLE_STRIPED;
  }

  echo('<table class="' . $table_class . '">' . PHP_EOL);
  $cols = array(
                   array(NULL, 'class="state-marker"'),
    'device'    => array('Device', 'style="width: 200px;"'),
    'descr'     => array('Memory'),
                   array('', 'style="width: 100px;"'),
    'usage'     => array('Usage', 'style="width: 280px;"'),                   
    'used'      => array('Used', 'style="width: 50px;"'),
  );

  if ($vars['page'] === "device")
  {
    unset($cols['device']);
  }

  echo(get_table_header($cols, $vars));
  echo('<tbody>' . PHP_EOL);
}

function print_mempool_row($mempool, $vars)
{
 echo generate_mempool_row($mempool, $vars);
}

function generate_mempool_row($mempool, $vars)
{

  global $config;

  $table_cols = 7;
  if ($vars['page'] !== "device" && $vars['popup'] != TRUE)  { $table_cols++; } // Add a column for device.

  $graph_array = array();
  $graph_array['to'] = $config['time']['now'];
  $graph_array['id'] = $mempool['mempool_id'];
  $graph_array['type'] = "mempool_usage";
  $graph_array['legend'] = "no";

  $link_array = $graph_array;
  $link_array['page'] = "graphs";
  unset($link_array['height'], $link_array['width'], $link_array['legend']);
  $link_graph = generate_url($link_array);

  $link = generate_url(array("page" => "device", "device" => $mempool['device_id'], "tab" => "health", "metric" => 'mempool'));

  $overlib_content = generate_overlib_content($graph_array, $mempool['hostname'] . " - " . rewrite_entity_name($mempool['mempool_descr'], 'mempool'));

  $graph_array['width'] = 80;
  $graph_array['height'] = 20;
  $graph_array['bg'] = 'ffffff00';
  $graph_array['from'] = $config['time']['day'];
  $mini_graph = generate_graph_tag($graph_array);

  if ($mempool['mempool_total'] != '100')
  {
    $total = formatStorage($mempool['mempool_total']);
    $used = formatStorage($mempool['mempool_used']);
    $free = formatStorage($mempool['mempool_free']);
  }
  else
  {
    // If total == 100, than memory not have correct size and uses percents only
    $total = $mempool['mempool_total'] . '%';
    $used = $mempool['mempool_used'] . '%';
    $free = $mempool['mempool_free'] . '%';
  }

  $background = get_percentage_colours($mempool['mempool_perc']);

  $mempool['html_row_class'] = $background['class'];

  $row .= '<tr class="' . $mempool['html_row_class'] . '">
            <td class="state-marker"></td>';
  if ($vars['page'] !== "device" && $vars['popup'] != TRUE)
  {
    $row .= '<td class="entity">' . generate_device_link($mempool) . '</td>';
  }

  $row .= '<td class="entity">' . generate_entity_link('mempool', $mempool) . '</td>
        <td>' . overlib_link($link_graph, $mini_graph, $overlib_content) . '</td>
        <td><a href="' . $link_graph . '">
          ' . print_percentage_bar(400, 20, $mempool['mempool_perc'], $used . '/' . $total . ' (' . $mempool['mempool_perc'] . '%)', "ffffff", $background['left'], $free . ' (' . (100 - $mempool['mempool_perc']) . '%)', "ffffff", $background['right']) . '
          </a>
        </td>
        <td>' . $mempool['mempool_perc'] . '%</td>
      </tr>
   ';

  if ($vars['view'] === "graphs")
  {
    $vars['graph'] = "usage";
  }

  if ($vars['graph'])
  {
    $row .= '<tr class="' . $mempool['html_row_class'] . '">';
    $row .= '<td class="state-marker"></td>';
    $row .= '<td colspan="' . $table_cols . '">';

    unset($graph_array['height'], $graph_array['width'], $graph_array['legend']);
    $graph_array['to'] = $config['time']['now'];
    $graph_array['id'] = $mempool['mempool_id'];
    $graph_array['type'] = 'mempool_' . $vars['graph'];

    $row .= generate_graph_row($graph_array, TRUE);

    $row .= '</td></tr>';
  } # endif graphs

  return $row;
}

function print_mempool_form($vars, $single_device = FALSE)
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
    $form_items['devices'] = generate_form_values('device', dbFetchColumn('SELECT DISTINCT `device_id` FROM `mempools`'));

    $form['row'][0]['device_id'] = array(
      'type'        => 'multiselect',
      'name'        => 'Device',
      'value'       => $vars['device_id'],
      'grid'        => 2,
      'width'       => '100%', //'180px',
      'values'      => $form_items['devices']);
  }

  //$sensor_permitted = generate_query_permitted(array('device', 'sensor'));
  $form['row'][0]['mempool_descr'] = array(
    'type'        => 'text',
    'placeholder' => 'Mempool',
    'width'       => '100%', //'180px',
    'grid'        => 6,
    'value'       => $vars['mempool_descr']);

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
                      'title' => 'Search Mempools',
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

  $panel_form['row'][3]['mempool_descr']  = $form['row'][0]['mempool_descr'];

  //$panel_form['row'][5]['sort'] = $form['row'][0]['sort'];
  $panel_form['row'][5]['search'] = $form['row'][0]['search'];

  // Register custom panel
  register_html_panel(generate_form($panel_form));
}

// EOF