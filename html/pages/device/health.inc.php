<?php

/**
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$datas = array('overview' => array('icon' => $config['icon']['overview']));

if ($health_exist['processors']) { $datas['processor'] = array('icon' => $config['entities']['processor']['icon']); }
if ($health_exist['mempools'])   { $datas['mempool']   = array('icon' => $config['entities']['mempool']['icon']); }
if ($health_exist['storage'])    { $datas['storage']   = array('icon' => $config['entities']['storage']['icon']); }
if ($health_exist['diskio'])     { $datas['diskio']    = array('icon' => $config['icon']['diskio']); }

if ($health_exist['status'])     { $datas['status']    = array('icon' => $config['entities']['status']['icon']); }

if ($health_exist['sensors'])
{
  $sensors_device = dbFetchRows("SELECT DISTINCT `sensor_class` FROM `sensors` WHERE `device_id` = ? AND `sensor_deleted` = ?", array($device['device_id'], 0));
  foreach ($sensors_device as $sensor)
  {
    if ($sensor['sensor_class'] == 'counter') { continue; } // DEVEL
    $datas[$sensor['sensor_class']] = array('icon' => $config['sensor_types'][$sensor['sensor_class']]['icon']);
  }
}

// All counters in single page?
if ($health_exist['counter'])    { $datas['counter']   = array('icon' => $config['entities']['counter']['icon']); }
/*
if ($health_exist['counter'])
{
  $counters_device = dbFetchRows("SELECT DISTINCT `counter_class` FROM `counters` WHERE `device_id` = ? AND `counter_deleted` = ?", array($device['device_id'], 0));
  foreach ($counters_device as $counter)
  {
    $datas[$counter['counter_class']] = array('icon' => $config['counter_types'][$counter['counter_class']]['icon']);
  }
}
*/

$link_array = array('page'    => 'device',
                    'device'  => $device['device_id'],
                    'tab'     => 'health');

if (!$vars['metric']) { $vars['metric'] = "overview"; }
if (!$vars['view'])   { $vars['view']   = "details"; }

$navbar['brand'] = "Health";
$navbar['class'] = "navbar-narrow";

$navbar_count = count($datas);
foreach ($datas as $type => $options)
{
  if ($vars['metric'] == $type) { $navbar['options'][$type]['class'] = "active"; }
  else if ($navbar_count > 8 && $type != 'overview') { $navbar['options'][$type]['class'] = "icon"; } // Show only icons if too many items in navbar
  if (isset($options['icon']))
  {
    $navbar['options'][$type]['icon'] = $options['icon'];
  }
  $navbar['options'][$type]['url']  = generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => $type));
  $navbar['options'][$type]['text'] = nicecase($type);
}

//$navbar['options']['graphs']['text']  = 'Graphs';
$navbar['options']['graphs']['icon']  = $config['icon']['graphs'];
$navbar['options']['graphs']['right'] = TRUE;

if ($vars['view'] == "graphs")
{
  $navbar['options']['graphs']['class'] = 'active';
  $navbar['options']['graphs']['url']   = generate_url($vars, array('view' => "detail"));
} else {
  $navbar['options']['graphs']['url']    = generate_url($vars, array('view' => "graphs"));
}

print_navbar($navbar);
unset($navbar);

if (isset($config['sensor_types'][$vars['metric']]) || $vars['metric'] == "sensors")
{
  include($config['html_dir']."/pages/device/health/sensors.inc.php");
}
elseif (isset($config['counter_types'][$vars['metric']]) || $vars['metric'] == "counter")
{
  include($config['html_dir']."/pages/device/health/counter.inc.php");
}
elseif (is_alpha($vars['metric']) && is_file($config['html_dir']."/pages/device/health/".$vars['metric'].".inc.php"))
{
  include($config['html_dir']."/pages/device/health/".$vars['metric'].".inc.php");
} else {

  echo generate_box_open();

  echo('<table class="table table-condensed table-striped table-hover ">');

  foreach ($datas as $type => $options)
  {
    if ($type != "overview")
    {
      $graph_title = nicecase($type);
      $graph_array['type'] = "device_".$type;
      $graph_array['device'] = $device['device_id'];

      echo('<tr><td>');
      echo('<h3>' . $graph_title . '</h3>');
      print_graph_row($graph_array);
      echo('</td></tr>');
    }
  }
  echo('</table>');

  echo generate_box_close();

}

register_html_title("Health");

// EOF
