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

if ($vars['view'] === 'graphs' || $vars['view'] === 'minigraphs')
{
  if (isset($vars['graph']))
  {
    $graph_type = "port_" . $vars['graph'];
  } else {
    $graph_type = "port_bits";
  }
}

if (!$vars['view'])
{
  $vars['view'] = trim($config['ports_page_default'], '/');
}

/* Already set in device page
$link_array = array('page'    => 'device',
                    'device'  => $device['device_id'],
                    'tab' => 'ports');

$filters_array = (isset($vars['filters'])) ? $vars['filters'] : array('deleted' => TRUE);
$link_array['filters'] = $filters_array;
*/

$navbar = array('brand' => "Ports", 'class' => "navbar-narrow");

$navbar['options']['basic']['text']   = 'Basic';
$navbar['options']['details']['text'] = 'Details';

if (is_array($ports_exist['navbar']))
{
  $navbar['options'] = array_merge($navbar['options'], $ports_exist['navbar']);
}
/*
if (dbExist('ipv4_addresses', '`device_id` = ?', array($device['device_id'])))
{
  $navbar['options']['ipv4']['text'] = 'IPv4 addresses';
}
if (dbExist('ipv6_addresses', '`device_id` = ?', array($device['device_id'])))
{
  $navbar['options']['ipv6']['text'] = 'IPv6 addresses';
}

// FIXME, need add device_id field into table ip_mac
if (dbFetchCell("SELECT COUNT(*) FROM `ip_mac` LEFT JOIN `ports` USING(`port_id`) WHERE `device_id` = ?", array($device['device_id'])))
//if (dbExist('ip_mac', '`device_id` = ?', array($device['device_id'])))
{
  $navbar['options']['arp']['text'] = 'ARP/NDP Table';
}

if (dbExist('vlans_fdb', '`device_id` = ?', array($device['device_id'])))
{
  $navbar['options']['fdb']['text'] = 'FDB Table';
}

if (dbExist('sensors', '`device_id` = ? AND `measured_class` = ? AND `sensor_deleted` = ?', [ $device['device_id'], 'port', 0 ]))
{
  $navbar['options']['sensors']['text'] = 'Sensors';
}

if (dbExist('neighbours', '`device_id` = ?', array($device['device_id'])))
{
  $navbar['options']['neighbours']['text'] = 'Neighbours';
  $navbar['options']['map']['text']        = 'Map';
}

if (dbExist('ports', '`ifType` = ? AND `device_id` = ?', array('adsl', $device['device_id'])))
{
  $navbar['options']['adsl']['text'] = 'ADSL';
}
*/

$navbar['options']['graphs']     = array('text' => 'Graphs', 'class' => 'pull-right');
$navbar['options']['minigraphs'] = array('text' => 'Minigraphs', 'class' => 'pull-right');

foreach ($navbar['options'] as $option => $array)
{
  if ($vars['view'] == $option)
  {
    $navbar['options'][$option]['class'] .= " active";
  }
  $navbar['options'][$option]['url'] = generate_url($link_array, array('view' => $option));
}

//r($config['graph_types']['port']);
//r($device['graphs']);
foreach (array('graphs', 'minigraphs') as $type)
{
  foreach ($config['graph_types']['port'] as $option => $data)
  {
    // Skip unavailable port graphs
    //if (!isset($device['graphs']['port_'.$option])) { continue; } // device graphs array is not the place for this

    if ($vars['view'] == $type && $vars['graph'] == $option)
    {
      $navbar['options'][$type]['suboptions'][$option]['class'] = 'active';
      $navbar['options'][$type]['text'] .= ' ('.$data['name'].')';
    }
    $navbar['options'][$type]['suboptions'][$option]['text'] = $data['name'];
    $navbar['options'][$type]['suboptions'][$option]['url'] = generate_url($link_array, array('view' => $type, 'graph' => $option));
  }
}

// Quick filters
function is_filtered()
{
  global $filters_array, $port;

  return ($filters_array['up']       && $port['ifOperStatus'] == 'up' && $port['ifAdminStatus'] == 'up' && !$port['ignore'] && !$port['deleted']) ||
         ($filters_array['down']     && $port['ifOperStatus'] != 'up' && $port['ifAdminStatus'] == 'up') ||
         ($filters_array['shutdown'] && $port['ifAdminStatus'] == 'down') ||
         ($filters_array['ignored']  && $port['ignore']) ||
         ($filters_array['deleted']  && $port['deleted']);
}

if (isset($vars['view']) && in_array($vars['view'], [ 'basic', 'details', 'graphs', 'minigraphs' ]))
{
  // List filters
  $filter_options = array('up'       => 'Hide UP',
                          'down'     => 'Hide DOWN',
                          'shutdown' => 'Hide SHUTDOWN',
                          'ignored'  => 'Hide IGNORED',
                          'deleted'  => 'Hide DELETED');
  // To be or not to be
  $filters_array['all'] = TRUE;
  foreach ($filter_options as $option => $text)
  {
    $filters_array['all'] = $filters_array['all'] && $filters_array[$option];
    $option_all[$option] = TRUE;
  }
  $filter_options['all'] = ($filters_array['all']) ? 'Reset ALL' : 'Hide ALL';

  // Generate filtered links
  $navbar['options_right']['filters']['text'] = 'Quick Filters';
  foreach ($filter_options as $option => $text)
  {
    $option_array = array_merge($filters_array, array($option => TRUE));
    $navbar['options_right']['filters']['suboptions'][$option]['text'] = $text;
    if ($filters_array[$option])
    {
      $navbar['options_right']['filters']['class'] .= ' active';
      $navbar['options_right']['filters']['suboptions'][$option]['class'] = 'active';
      if ($option === 'all')
      {
        $option_array = array('disabled' => FALSE);
      } else {
        $option_array[$option] = FALSE;
      }
    } elseif ($option === 'all') {
      $option_array = $option_all;
    }
    $navbar['options_right']['filters']['suboptions'][$option]['url'] = generate_url($vars, array('filters' => $option_array));
  }
}

print_navbar($navbar);
unset($navbar);

if ($vars['view'] === 'minigraphs') {
  $timeperiods = array('-1d','-1w','-1m','-1y');
  $from = '-1d';
  echo '<div class="row">';
  unset ($seperator);

  // FIXME - FIX THIS. UGLY.
  foreach (dbFetchRows("SELECT * FROM `ports` WHERE `device_id` = ? ORDER BY `ifIndex`", array($device['device_id'])) as $port) {
    if (is_filtered()) { continue; }

    print_port_minigraph($port, $graph_type);
  }

  echo '</div>';
} elseif (is_alpha($vars['view']) && is_file($config['html_dir'] . '/pages/device/ports/' . $vars['view'] . '.inc.php')) {
  include($config['html_dir'] . '/pages/device/ports/' . $vars['view'] . '.inc.php');
} else {
  if ($vars['view'] === "details") {
    $port_details = 1;
  }

  $table_class = $vars['view'] === "graphs" ? OBS_CLASS_TABLE_STRIPED_TWO : OBS_CLASS_TABLE_STRIPED;

  $i = "1";

  // Make the port caches available easily to this code.
  global $port_cache, $port_index_cache;

  $sql  = "SELECT *, `ports`.`port_id` as `port_id`";
  $sql .= " FROM  `ports`";
  $sql .= " WHERE `device_id` = ? ORDER BY `ifIndex` ASC";
  $ports = dbFetchRows($sql, array($device['device_id']));

  // Sort ports, sharing code with global ports page.
  include($config['html_dir']."/includes/port-sort.inc.php");

  // As we've dragged the whole database, lets pre-populate our caches :)
  foreach ($ports as $port)
  {
    $port_cache[$port['port_id']] = $port;
    $port_index_cache[$port['device_id']][$port['ifIndex']] = $port;
  }

  // Collect port IDs and ifIndexes who has adsl/cbqos/pagp/ip and other.
  $cache['ports_option'] = array();
  $ext_tables = array('ports_adsl', 'ports_cbqos', 'mac_accounting', 'neighbours');
  if ($port_details) {
    $ext_tables = array_merge($ext_tables, array('ipv4_addresses', 'ipv6_addresses', 'pseudowires'));
    // Here stored ifIndex!
    //$cache['ports_option']['ports_pagp']       = dbFetchColumn("SELECT `pagpGroupIfIndex` FROM `ports`   WHERE `device_id` = ? GROUP BY `pagpGroupIfIndex`", array($device['device_id'])); // PAGP removed
    $cache['ports_option']['ports_stack_low']  = dbFetchColumn("SELECT `port_id_low`  FROM `ports_stack` WHERE `device_id` = ? AND `port_id_high` != ? AND `ifStackStatus` = ? GROUP BY `port_id_low`",  [ $device['device_id'], 0, 'active' ]);
    $cache['ports_option']['ports_stack_high'] = dbFetchColumn("SELECT `port_id_high` FROM `ports_stack` WHERE `device_id` = ? AND `port_id_low`  != ? AND `ifStackStatus` = ? GROUP BY `port_id_high`", [ $device['device_id'], 0, 'active' ]);
  }

  //$where = ' IN ('.implode(',', array_keys($port_cache)).')';
  //$where = generate_query_values(array_keys($port_cache), 'port_id');
  //$where = generate_query_permitted(array('ports', 'devices'));
  foreach ($ext_tables as $table) {
    // Here stored port_id!
    $sql = "SELECT DISTINCT `port_id` FROM `$table` WHERE 1 ";
    if ($table === 'neighbours') {
      // Show only active neighbours
      $sql .= 'AND `active` = 1 ';
    } elseif ($table === 'ports_adsl') {
      // FIXME. adsl table still not have device_id
      $cache['ports_option'][$table] = dbFetchColumn($sql . generate_query_permitted([ 'ports' ]));
      continue;
    }

    $cache['ports_option'][$table] = dbFetchColumn($sql . $cache['where']['ports_permitted']);

    //r("SELECT DISTINCT `port_id` FROM `$table` WHERE 1 " . generate_query_permitted(array('ports', 'devices')));

  }

  $cache['ports_vlan'] = array(); // Cache port vlans
  foreach (dbFetchRows('SELECT * FROM `ports_vlans` AS PV LEFT JOIN vlans AS V ON PV.`vlan` = V.`vlan_vlan` AND PV.`device_id` = V.`device_id`
                       WHERE PV.`device_id` = ? ORDER BY PV.`vlan`', array($device['device_id'])) as $entry)
  {
    $cache['ports_vlan'][$entry['port_id']][$entry['vlan']] = $entry;
  }

  echo generate_box_open();
  echo '<table class="' . $table_class . ' table-hover">' . PHP_EOL;

  if ($vars['view'] === 'basic')
  {
    $cols = [
                   [ NULL, 'class="state-marker"' ],
                   [ NULL ],
      'port'    => [ 'Port' ],
                   //[ NULL ],
      'traffic' => [ 'Traffic' ],
                   [ NULL ],
                   [ NULL ],
      'speed'   => [ 'Speed' ],
      'mac'     => [ 'MAC Address' ],
                   //[ NULL ],
    ];
  } else {
    $cols = [
                   [ NULL, 'class="state-marker"' ],
                   [ NULL ],
      'port'    => [ 'Port' ],
                   [ NULL ],
      'traffic' => [ 'Traffic' ],
      'speed'   => [ 'Speed' ],
      'mac'     => [ 'MAC Address' ],
                   [ NULL ],
    ];
  }

  echo get_table_header($cols, $vars);
  echo '<tbody>' . PHP_EOL;

  foreach ($ports as $port)
  {
    if (is_filtered()) { continue; }

    print_port_row($port, $vars);
  }

  echo '</tbody></table>';
  echo generate_box_close();
}

register_html_title("Ports");

unset($where, $ext_tables, $cache['ports_option'], $cache['ports_vlan']);

// EOF
