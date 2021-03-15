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

$navbar['class'] = 'navbar-narrow';
$navbar['brand'] = 'Services';

$navbar['options']['basic']['text']   = 'Basic';
$navbar['options']['details']['text'] = 'Details';

$service_types = array();
foreach ($service_list as $service)
{
  if ($vars['app'] == $service['service_type'])
  {
    $navbar['options'][$service['service_type']]['class'] = 'active';
  }
  $navbar['options'][$service['service_type']]['url']  = generate_url(array('page' => 'apps', 'app' => $service['service_type']));
  $navbar['options'][$service['service_type']]['text'] = nicecase($service['service_type']);

  $navbar['options'][$service['service_type']]['image'] = 'images/apps/'.$icon.'.png';
  if (is_file($config['html_dir'].'/images/apps/'.$icon.'_2x.png'))
  {
    // HiDPI icon
    $navbar['options'][$service['service_type']]['image_2x'] = 'images/apps/'.$icon.'_2x.png';
  }

  $service_types[$service['service_type']] = array();
}

print_navbar($navbar);
unset($navbar);

register_html_title("Services");

if ($_GET['status'] == '0') { $where = " AND service_status = '0'"; } else { unset ($where); }

if ($vars['view'] == "details") { $stripe_class = "table-striped-two"; } else { $stripe_class = "table-striped"; }

echo '<div class="box box-solid">';
echo '<table class="table table-condensed '.$stripe_class.'" style="margin-top: 10px;">';

//echo("<tr class=small bgcolor='#e5e5e5'><td>Device</td><td>Service</td><td>Status</td><td>Changed</td><td>Checked</td><td>Message</td></tr>");

if ($_SESSION['userlevel'] >= '5')
{
  $host_sql = "SELECT * FROM devices AS D, services AS S WHERE D.device_id = S.device_id GROUP BY D.hostname ORDER BY D.hostname";
  $host_par = array();
} else {
  $host_sql = "SELECT * FROM devices AS D, services AS S, devices_perms AS P WHERE D.device_id = S.device_id AND D.device_id = P.device_id AND P.user_id = ? GROUP BY D.hostname ORDER BY D.hostname";
  $host_par = array($_SESSION['user_id']);
}

foreach (dbFetchRows($host_sql, $host_par) as $device)
{
  $device_id = $device['device_id'];
  $device_hostname = $device['hostname'];
  foreach (dbFetchRows("SELECT * FROM `services` WHERE `device_id` = ?", array($device['device_id'])) as $service)
  {
    include($config['html_dir']."/includes/print-service.inc.php");

    if ($vars['view'] == "details")
    {
      $graph_array['height'] = "100";
      $graph_array['width']  = "215";
      $graph_array['to']     = $config['time']['now'];
      $graph_array['id']     = $service['service_id'];
      $graph_array['type']   = "service_availability";

      $periods = array('day', 'week', 'month', 'year');

      echo('<tr><td colspan=6>');

      print_graph_row($graph_array);

      echo("</td></tr>");
    }
  }
  unset ($samehost);
}

echo '</table></div>';

// EOF
