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


if ($_SESSION['userlevel'] <= 7)
{
  print_error_permission();
  return;
}

print generate_box_open();
echo '<h2>Supported OS Types: '.count($config['os']).'</h2>';
print generate_box_close();

foreach (dbFetchRows('SELECT `os`, COUNT(*) AS `count` FROM `devices` GROUP BY `os`') AS $row)
{
  $oses[$row['os']] = $row['count'];
}

foreach ($config['device_types'] as $devtype)
{
  $config['device_types'][$devtype['type']] = $devtype;
}

//r($oses);

//r($config['os']);

echo generate_box_open();

echo '<table class="table table-condensed table-striped">';

echo '
  <thead>
    <tr>
      <th class="state-marker"></th>
      <th></th>
      <th>Operating System</th>
      <th>OS Type</th>
      <th>Type / Group</th>
      <th>Devices</th>
      <th>MIBs</th>
      <th>Rules</th>
    </tr>
  </thead>';

//r($cache);

//r($config['device_types']);


ksort($config['os']);

foreach($config['os'] as $os_name => $os)
{

  $devtype = $config['device_types'][$os['type']];

  //r($devtype);

  if (isset($oses[$os_name]))
  {
    echo '  <tr data-toggle="collapse" data-target="#hidden-'.$os_name.'" class="clickable">';
    echo '    <td class="state-marker"></td>';
    echo '    <td class="text-center vertical-align" style="width: 64px; text-align: center;">'.get_device_icon(array('os' => $os_name)).'</td>';
    echo '    <td><span class="entity">'.$os['text'].'</span></td>';
    echo '    <td><span class="label label-primary">'.$os_name.'</span></td>';
    echo '    <td><span class="entity"><span class="'. $config['device_types'][$os['type']]['icon'].'"> </span> '. $os['type'].'</span></td>';
    echo '    <td>'.(isset($oses[$os_name]) ? '<a href="'.generate_url(array('page' => 'devices', 'os' => $os_name)).'">'.$oses[$os_name].' devices</a>' : '').'</td>';
    echo '    <td>'.count($os['mibs']).'</td>';
    echo '    <td>'.count($os['mib_blacklist']).'</td>';
    echo '    <td>'.count($os['sysObjectID']).'</td>';
    echo '  </tr>';

//    echo '  <tr>';
//    echo '    <td colspan="8">';
//    echo '      <div id="hidden-'.$os_name.'" class="accordion-toggle">Hidden by default</div>';
//    echo '    </td>';
//    echo '  </tr>';


  }
}

echo '</table>';

echo generate_box_close();

?>
