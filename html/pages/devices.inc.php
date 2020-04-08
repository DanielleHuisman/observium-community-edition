<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage webui
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Set Defaults here

if (!isset($vars['format'])) { $vars['format'] = "detail"; }
if (!$config['web_show_disabled'] && !isset($vars['disabled'])) { $vars['disabled'] = '0'; }
if ($vars['format'] != 'graphs')
{
  // reset all from/to vars if not use graphs
  unset($vars['from'], $vars['to'], $vars['timestamp_from'], $vars['timestamp_to'], $vars['graph']);
}

$query_permitted = generate_query_permitted(array('device'), array('device_table' => 'devices'));

$where_array = build_devices_where_array($vars);

$where = ' WHERE 1 ';
$where .= implode('', $where_array);

register_html_title("Devices");

foreach ($config['device_types'] as $entry)
{
  $types[$entry['type']] = $entry;
}

// Generate array with form elements
$form_items = array();
foreach (array('os', 'hardware', 'vendor', 'version', 'features', 'type', 'distro') as $entry)
{
  $query  = "SELECT `$entry` FROM `devices`";
  if (isset($where_array[$entry]))
  {
    $tmp = $where_array[$entry];
    unset($where_array[$entry]);
    $query .= ' WHERE 1 ' . implode('', $where_array);
    $where_array[$entry] = $tmp;
  } else {
    $query .= $where;
  }
  $query .= " AND `$entry` != '' $query_permitted GROUP BY `$entry` ORDER BY `$entry`";
  foreach (dbFetchColumn($query) as $item)
  {
    if ($entry == 'os')
    {
      $name = $config['os'][$item]['text'];
    }
    else if ($entry == 'type')
    {
      if (isset($types[$item]))
      {
        $name = array('name' => $types[$item]['text'], 'icon' => $types[$item]['icon']);
      } else {
        $name = array('name' => nicecase($item),       'icon' => $config['icon']['exclamation']);
      }
    } else {
      $name = nicecase($item);
    }
    $form_items[$entry][$item] = $name;
  }
}

asort($form_items['os']);

foreach (get_locations() as $entry)
{
  if ($entry === '') { $entry = OBS_VAR_UNSET; }
  $form_items['location'][$entry] = $entry;
}

foreach (get_type_groups('device') as $entry)
{
  $form_items['group'][$entry['group_id']] = $entry['group_name'];
}

$form_items['sort'] = array('hostname' => 'Hostname',
                            'domain'   => 'Hostname in Domain Order',
                            'location' => 'Location',
                            'os'       => 'Operating System',
                            'version'  => 'Version',
                            'features' => 'Featureset',
                            'type'     => 'Device Type',
                            'uptime'   => 'Uptime');

$form = array('type'  => 'rows',
              'space' => '10px',
              'submit_by_key' => TRUE,
              'url'   => generate_url($vars));
// First row
$form['row'][0]['hostname'] = array(
                                'type'        => 'text',
                                'name'        => 'Hostname',
                                'value'       => $vars['hostname'],
                                'width'       => '100%', //'180px',
                                'placeholder' => TRUE);
$form['row'][0]['location'] = array(
                                'type'        => 'multiselect',
                                'name'        => 'Select Locations',
                                'width'       => '100%', //'180px',
                                'encode'      => TRUE,
                                'value'       => $vars['location'],
                                'values'      => $form_items['location']);
$form['row'][0]['os']       = array(
                                'type'        => 'multiselect',
                                'name'        => 'Select OS',
                                'width'       => '100%', //'180px',
                                'value'       => $vars['os'],
                                'values'      => $form_items['os']);
$form['row'][0]['hardware'] = array(
                                'type'        => 'multiselect',
                                'name'        => 'Select Hardware',
                                'width'       => '100%', //'180px',
                                'value'       => $vars['hardware'],
                                'values'      => $form_items['hardware']);
$form['row'][0]['vendor']   = array(
                                'type'        => 'multiselect',
                                'name'        => 'Select Vendor',
                                'width'       => '100%', //'180px',
                                'value'       => $vars['vendor'],
                                'values'      => $form_items['vendor']);
$form['row'][0]['group']    = array(
                                'type'        => 'multiselect',
                                'name'        => 'Select Groups',
                                'width'       => '100%', //'180px',
                                'value'       => $vars['group'],
                                'values'      => $form_items['group']);
// Select sort pull-rigth
//$form['row'][0]['sort']     = array(
//                                'type'        => 'select',
//                                'icon'        => $config['icon']['sort'],
//                                'right'       => TRUE,
//                                'width'       => '100%', //'150px',
//                                'value'       => $vars['sort'],
//                                'values'      => $form_items['sort']);

// Second row
$form['row'][1]['sysname']  = array(
                                'type'        => 'text',
                                'name'        => 'sysName',
                                'value'       => $vars['sysname'],
                                'width'       => '100%', //'180px',
                                'placeholder' => TRUE);
$form['row'][1]['location_text'] = array(
                                'type'        => 'text',
                                'name'        => 'Location',
                                'value'       => $vars['location_text'],
                                'width'       => '100%', //'180px',
                                'placeholder' => TRUE);
$form['row'][1]['version']  = array(
                                'type'        => 'multiselect',
                                'name'        => 'Select OS Version',
                                'width'       => '100%', //'180px',
                                'value'       => $vars['version'],
                                'values'      => $form_items['version']);
$form['row'][1]['features'] = array(
                                'type'        => 'multiselect',
                                'name'        => 'Select Featureset',
                                'width'       => '100%', //'180px',
                                'value'       => $vars['features'],
                                'values'      => $form_items['features']);
$form['row'][1]['type']     = array(
                                'type'        => 'multiselect',
                                'name'        => 'Select Device Type',
                                'width'       => '100%', //'180px',
                                'value'       => $vars['type'],
                                'values'      => $form_items['type']);
// Select sort pull-rigth
$form['row'][1]['sort']     = array(
                                'type'        => 'select',
                                'icon'        => $config['icon']['sort'],
                                'right'       => TRUE,
                                'width'       => '100%', //'150px',
                                'value'       => $vars['sort'],
                                'values'      => $form_items['sort']);

// Third row
$form['row'][2]['sysDescr']  = array(
                                'type'        => 'text',
                                'name'        => 'sysDescr',
                                'value'       => $vars['sysDescr'],
                                'width'       => '100%', //'180px',
                                'placeholder' => TRUE);


$form['row'][2]['purpose']  = array(
                                'type'        => 'text',
                                'name'        => 'Description / Purpose',
                                'value'       => $vars['purpose'],
                                'width'       => '100%', //'180px',
                                'placeholder' => TRUE);

$form['row'][2]['sysContact']  = array(
  'type'        => 'text',
  'name'        => 'sysContact',
  'value'       => $vars['sysContact'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE);

$form['row'][2]['distro'] = array(
  'type'        => 'multiselect',
  'name'        => 'Select Distro',
  'width'       => '100%', //'180px',
  'value'       => $vars['distro'],
  'values'      => $form_items['distro']);

$form['row'][2]['serial']  = array(
  'type'        => 'text',
  'name'        => 'Serial Number',
  'value'       => $vars['serial'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE);

// search button
$form['row'][2]['search']   = array(
                                'type'        => 'submit',
                                //'name'        => 'Search',
  //'class' => 'btn-primary',
                                //'icon'        => 'icon-search',
                                'right'       => TRUE,
                                );

$panel_form = array('type'          => 'rows',
                    'title'         => 'Search Devices',
                    'space'         => '10px',
                    'submit_by_key' => TRUE,
                    'url'           => generate_url($vars));

$panel_form['row'][0] = array('hostname'      => $form['row'][0]['hostname'],
                              'sysname'       => $form['row'][1]['sysname']);

$panel_form['row'][1] = array('sysDescr'      => $form['row'][2]['sysDescr'],
                              'purpose'       => $form['row'][2]['purpose']);

$panel_form['row'][2] = array('sysContact'    => $form['row'][2]['sysContact'],
                              'serial'        => $form['row'][2]['serial']);

$panel_form['row'][3] = array('location'      => $form['row'][0]['location'],
                              'location_text' => $form['row'][1]['location_text']);


$panel_form['row'][4]['os']            = $form['row'][0]['os'];
$panel_form['row'][4]['version']       = $form['row'][1]['version'];
$panel_form['row'][4]['distro']        = $form['row'][2]['distro'];

$panel_form['row'][5]['hardware']      = $form['row'][0]['hardware'];
$panel_form['row'][5]['vendor']        = $form['row'][0]['vendor'];

$panel_form['row'][6]['type']          = $form['row'][1]['type'];
$panel_form['row'][6]['features']      = $form['row'][1]['features'];

$panel_form['row'][7]['group']         = $form['row'][0]['group'];
$panel_form['row'][7]['sort']          = $form['row'][1]['sort'];
$panel_form['row'][7]['search']        = $form['row'][2]['search'];

// Register custom panel
register_html_panel(generate_form($panel_form));


if ($vars['searchbar'] != "hide")
{
  echo '<div class="hidden-xl">';
  print_form($form);
  echo '</div>';
}
unset($form, $panel_form, $form_items);

// Build Devices navbar

$navbar = array('brand' => "Devices", 'class' => "navbar-narrow");

$navbar['options']['basic']['text']  = 'Basic';
$navbar['options']['detail']['text'] = 'Details';
$navbar['options']['status']['text'] = 'Status';

if (FALSE && $_SESSION['userlevel'] >= "10")
{ // Hidden for now
  $navbar['options']['perf']['text'] = 'Polling Performance';
}
$navbar['options']['graphs']['text'] = 'Graphs';

foreach ($navbar['options'] as $option => $array)
{
  //if (!isset($vars['format'])) { $vars['format'] = 'basic'; }
  if ($vars['format'] == $option) { $navbar['options'][$option]['class'] .= " active"; }
  $navbar['options'][$option]['url'] = generate_url($vars, array('format' => $option));
}

// Set graph period stuff
if ($vars['format'] == 'graphs')
{
  $timestamp_pattern = '/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/';
  if (isset($vars['timestamp_from']) && preg_match($timestamp_pattern, $vars['timestamp_from']))
  {
    $vars['from'] = strtotime($vars['timestamp_from']);
    unset($vars['timestamp_from']);
  }
  if (isset($vars['timestamp_to'])   && preg_match($timestamp_pattern, $vars['timestamp_to']))
  {
    $vars['to'] = strtotime($vars['timestamp_to']);
    unset($vars['timestamp_to']);
  }

  if (!is_numeric($vars['from'])) { $vars['from'] = $config['time']['day']; }
  if (!is_numeric($vars['to']))   { $vars['to']   = $config['time']['now']; }
}

// Print options related to graphs.
//$menu_options = array('bits'      => 'Bits',
//                      'processor' => 'CPU',
//                      'mempool'   => 'Memory',
//                      'uptime'    => 'Uptime',
//                      'storage'   => 'Storage',
//                      'diskio'    => 'Disk I/O',
//                      'poller_perf' => 'Poll Time'
//                      );
foreach (array('graphs') as $type)
{
  /// FIXME. Weird graph menu, they too long and not actual for all devices,
  /// but here also not posible use sql query from `device_graphs` because here not stored all graphs
  /// FIXME - We need to register all graphs in `device_graphs` :D

  $vars_graphs = $vars;
  unset($vars_graphs['graph']);
  $where_graphs = build_devices_where_array($vars_graphs);

  $where_graphs = ' WHERE 1 ' . implode('', $where_graphs);

  $query  = 'SELECT `graph` FROM `device_graphs` LEFT JOIN `devices` USING (`device_id`)';
  $query .= $where_graphs . $query_permitted . ' AND `device_graphs`.`enabled` = 1 GROUP BY `graph`';

  foreach (dbFetchColumn($query) as $option)
  {
    $data = $config['graph_types']['device'][$option];
  /*
  foreach ($config['graph_types']['device'] as $option => $data)
  { */
    if (!isset($data['descr'])) { $data['descr'] = nicecase($option);}

    if ($vars['format'] == $type && $vars['graph'] == $option)
    {
      $navbar['options'][$type]['suboptions'][$option]['class'] = 'active';
      $navbar['options'][$type]['text'] .= " (".$data['descr'].')';
    }
    $navbar['options'][$type]['suboptions'][$option]['text'] = $data['descr'];
    $navbar['options'][$type]['suboptions'][$option]['url'] = generate_url($vars, array('view' => NULL, 'format' => $type, 'graph' => $option));
  }
}

if (isset($vars['pagination']) && (!$vars['pagination'] || $vars['pagination'] == 'no'))
{
  $navbar['options_right']['pagination']     = array('text' => 'Enable Pagination', 'url' => generate_url($vars, array('pagination' => NULL)));
} else {
  $navbar['options_right']['pagination']     = array('text' => 'Disable Pagination' , 'url' => generate_url($vars, array('pagination' => '0', 'pageno' => NULL, 'pagesize' => NULL)));
}

if ($vars['searchbar'] == "hide")
{
  $navbar['options_right']['searchbar']     = array('text' => 'Show Search', 'url' => generate_url($vars, array('searchbar' => NULL)));
} else {
  $navbar['options_right']['searchbar']     = array('text' => 'Hide Search' , 'url' => generate_url($vars, array('searchbar' => 'hide')));
}

if ($vars['bare'] == "yes")
{
  $navbar['options_right']['header']     = array('text' => 'Show Header', 'url' => generate_url($vars, array('bare' => NULL)));
} else {
  $navbar['options_right']['header']     = array('text' => 'Hide Header', 'url' => generate_url($vars, array('bare' => 'yes')));
}

$navbar['options_right']['reset']        = array('text' => 'Reset', 'url' => generate_url(array('page' => 'devices', 'section' => $vars['section'])));

print_navbar($navbar);
unset($navbar);

// Print period options for graphs

if ($vars['format'] == 'graphs')
{
  $form = array('type'          => 'rows',
                'space'         => '5px',
                'submit_by_key' => TRUE); //Do not use url here, because it discards all other vars from url

  // Datetime Field
  $form['row'][0]['timestamp'] = array(
                              'type'        => 'datetime',
                              'grid'        => 10,
                              'grid_xs'     => 10,
                              //'width'       => '70%',
                              //'div_class'   => 'col-lg-10 col-md-10 col-sm-10 col-xs-10',
                              'presets'     => TRUE,
                              'min'         => '2007-04-03 16:06:59',  // Hehe, who will guess what this date/time means? --mike
                                                                       // First commit! Though Observium was already 7 months old by that point. --adama
                              'max'         => date('Y-m-d 23:59:59'), // Today
                              'from'        => date('Y-m-d H:i:s', $vars['from']),
                              'to'          => date('Y-m-d H:i:s', $vars['to']));
  // Update button
  $form['row'][0]['update']   = array(
                              'type'        => 'submit',
                              //'name'        => 'Search',
                              //'icon'        => 'icon-search',
                              //'div_class'   => 'col-lg-2 col-md-2 col-sm-2 col-xs-2',
                              'grid'        => 2,
                              'grid_xs'     => 2,
                              'right'       => TRUE);

  print_form($form);
  unset($form);
}

$count = dbFetchCell("SELECT COUNT(*) FROM `devices` ".$where.$query_permitted);

$sort = build_devices_sort($vars);

if (isset($vars['sort']) && $vars['sort'] == 'domain')
{
  // Special Domain sort, require additional pseudo fields
  $query  = "SELECT *,";
  $query .= " SUBSTRING_INDEX(SUBSTRING_INDEX(`hostname`,'.',-3),'.',1) AS `leftmost`,";
  $query .= " SUBSTRING_INDEX(SUBSTRING_INDEX(`hostname`,'.',-2),'.',1) AS `middle`,";
  $query .= " SUBSTRING_INDEX(`hostname`,'.',-1) AS `rightmost`";
  $query .= " FROM `devices` ";
} else {
  $query = "SELECT * FROM `devices` ";
}

if ($config['geocoding']['enable'])
{
  $query .= " LEFT JOIN `devices_locations` USING (`device_id`) ";
}
$query .= $where . $query_permitted . $sort;

// Pagination
$pagination_html = '';
if (isset($vars['pagination']) && (!$vars['pagination'] || $vars['pagination'] == 'no')) {} // Skip if pagination set to false
elseif ($count)
{
  pagination($vars, 0, TRUE); // Get default pagesize/pageno
  $start = $vars['pagesize'] * $vars['pageno'] - $vars['pagesize'];
  $query .= ' LIMIT '.$start.','.$vars['pagesize'];
  $pagination_html = pagination($vars, $count);
}

list($format, $subformat) = explode("_", $vars['format'], 2);

$devices = dbFetchRows($query);
//$devices = dbFetchRows($query, NULL, TRUE);

if (count($devices))
{
  $include_file = $config['html_dir'].'/pages/devices/'.$format.'.inc.php';
  if (is_file($include_file))
  {
    echo $pagination_html;
    if ($format != 'graphs')
    {
      echo generate_box_open();
    }

    include($include_file);

    if ($vars['format'] != 'graphs')
    {
      echo generate_box_close();
    }
    echo $pagination_html;
  } else {
    print_error("<h4>Error</h4>
                 This should not happen. Please ensure you are on the latest release and then report this to the Observium developers if it continues.");
  }
} else {
  print_error("<h4>No devices found</h4>
               Please try adjusting your search parameters.");
}


// EOF
