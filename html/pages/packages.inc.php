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

foreach ($vars as $var => $value)
{
  if ($value != "")
  {
    switch ($var)
    {
      case 'name':
        $where .= generate_query_values($value, $var);
        break;
    }
  }
}

echo generate_box_open();

echo '<table class="table table-condensed table-striped">';
echo '  <thead>';
echo '    <tr>';
echo '      <th style="width: 300px;">Package</th>';
echo '      <th>Version</th>';
echo '    </tr>';
echo '  </thead>';
echo '  <tbody>';

// Build array of packages - faster than SQL
// foreach (dbFetchRows("SELECT * FROM `packages`", $param) as $entry)
// {
// }

foreach (dbFetchRows("SELECT * FROM `packages` WHERE 1 $where GROUP BY `name`", $param) as $package)
{
  echo '    <tr>'.PHP_EOL;
  echo '      <td><a href="'. generate_url($vars, array('name' => $package['name'])).'" class="entity">'.$package['name'].'</a></td>'.PHP_EOL;
  echo '      <td>';

  foreach (dbFetchRows("SELECT * FROM `packages` WHERE `name` = ? ORDER BY version, build", array($package['name'])) as $v)
  {
    $package['versions'][$v['version']][$v['build']][] = $v;
  }

  //r($package);

  if (!empty($vars['name']))
  {
    echo '<table class="'.OBS_CLASS_TABLE_STRIPED.'">';
    echo '<tbody>';
  }

  foreach ($package['versions'] as $version => $builds)
  {

    $content = "";
    foreach ($builds as $build => $device_ids)
    {
      if ($build) { $dbuild = '-' . $build; } else { $dbuild = ''; }
      $content .= $version.$dbuild;

      foreach ($device_ids as $entry)
      {
        $this_device = device_by_id_cache($entry['device_id']);

  switch($entry['arch'])
  {
    case "amd64":
      $entry['arch_class'] = 'label-success';
      break;
    case "i386":
      $entry['arch_class'] = 'label-info';
      break;
    default:
      $entry['arch_class'] = '';
  }

  switch($entry['manager'])
  {
    case "deb":
      $entry['manager_class'] = 'label-warning';
      break;
    case "rpm":
      $entry['manager_class'] = 'label-important';
      break;
    default:
      $entry['manager_class'] = '';
  }

        if ($build != '') { $dbuild = '-'.$entry['build']; } else { $dbuild = ''; }

        if (!empty($this_device['hostname'])) {
          if (!empty($vars['name']))
          {
            echo '<tr>';
            echo '<td>'.$entry['version'].$dbuild.'</td><td><span class="label '.$entry['arch_class'].'">'.$entry['arch'].'</span></td>
                  <td><span class="label '.$entry['manager_class'].'">'.$entry['manager'].'</span></td>
                  <td class="entity">'.generate_device_link($this_device).'</td><td></td><td>'.format_si($entry['size']).'</td>';
            echo '</tr>';
          } else {
            $hosts[] = '<span class="entity">' . $this_device['hostname'].'</span>';
          }
        }

      }
    }

    if (empty($vars['name']))
    {
      #if ($first) { $first = false; $middot = ""; } else { $middot = "&nbsp;&nbsp;&middot;&nbsp;&nbsp;"; }
      $vers[] = generate_tooltip_link('', $version . $dbuild, implode($hosts, '<br />'));
    }
    unset($hosts);
  }

  if (!empty($vars['name']))
  {
    echo '</tbody>';
    echo '</table>';
  } else {
    echo implode($vers, ' - ');
  }

  unset($vers);

  echo '      </td>'.PHP_EOL;
  echo '    </tr>'.PHP_EOL;
}

echo '  </tbody>';
echo '</table>';

echo generate_box_close();

// EOF
