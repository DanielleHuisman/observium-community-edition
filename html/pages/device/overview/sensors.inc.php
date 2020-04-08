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

$sensor_types = array_keys($config['sensor_types']);

foreach ($sensor_types as $sensor_type)
{
  $sql  = "SELECT * FROM `sensors`";
  //$sql .= " LEFT JOIN `sensors-state` USING(`sensor_id`)";
  $sql .= " WHERE `sensor_class` = ? AND `device_id` = ? AND `sensor_deleted` = 0 ORDER BY `sensor_type`, `entPhysicalIndex` * 1, `sensor_descr`"; // order numerically by entPhysicalIndex for ports

  // Cache all sensors
  foreach (dbFetchRows($sql, array($sensor_type, $device['device_id'])) as $entry)
  {
    if (strlen($entry['measured_class']) && is_numeric($entry['measured_entity']) && !in_array($entry['sensor_class'], array('counter', 'state')))
    {
      // Sensors bounded with measured class, mostly ports
      // array index -> ['measured']['port']['345'][] = sensor array
      $sensors_db['measured'][$entry['measured_class']][$entry['measured_entity']][] = $entry;
    } else {
      $sensors_db[$sensor_type][$entry['sensor_id']] = $entry;
    }
  }
}
//r($sensors_db['measured']);

// Now print founded bundle (measured_class+sensor)
if (isset($sensors_db['measured']))
{
  $vars['measured_icon'] = FALSE; // Hide measured icons
  foreach ($sensors_db['measured'] as $measured_class => $measured_entity)
  {
    $box_args = array('title' => nicecase($measured_class).' sensors',
                      'url'   => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => $measured_class.'s', 'view' => 'sensors')),
                      'icon'  => $config['icon']['sensor']
                      );
    echo generate_box_open($box_args);

    echo' <table class="table table-condensed table-striped">';

    foreach ($measured_entity as $entity_id => $entry)
    {
      $entity      = get_entity_by_id_cache($measured_class, $entity_id);
      //r($entity);
      $entity_link = generate_entity_link($measured_class, $entity);
      $entity_type = entity_type_translate_array($measured_class);

      // Remove port name from sensor description
      $rename_from = [];
      if ($measured_class == 'port')
      {
        $rename_from[] = $entity['port_label'];
        $rename_from[] = $entity['ifDescr'];
        $rename_from[] = $entity['port_label_short'];
        if (strlen($entity['port_label_base']) > 4) { $rename_from[] = $entity['port_label_base']; }
        $rename_from = array_unique($rename_from);
      } else {
          $rename_from[] = entity_rewrite($measured_class, $entity);
      }
      //r($rename_from);
      //echo('      <tr class="'.$port['row_class'].'">
      //  <td class="state-marker"></td>
      echo('      <tr>
        <td colspan="6" class="entity">' . get_icon($entity_type['icon']) . '&nbsp;' . $entity_link . '</td></tr>');
      foreach ($entry as $sensor)
      {
        $sensor['sensor_descr'] = trim(str_ireplace($rename_from, '', $sensor['sensor_descr']), ":- \t\n\r\0\x0B");
        if (empty($sensor['sensor_descr']))
        {
          // Some time sensor descriptions equals to entity name
          $sensor['sensor_descr'] = nicecase($sensor['sensor_class']);
        }

        print_sensor_row($sensor, $vars);
      }
    }

?>
      </table>
<?php
    echo generate_box_close();
  }
  // End for print bounds, unset this array
  unset($sensors_db['measured']);
}

foreach ($sensors_db as $sensor_type => $sensors)
{
  if ($sensor_type == 'measured') { continue; } // Just be on the safe side

  if (count($sensors))
  {
    $box_args = array('title' => nicecase($sensor_type), 
                      'url'   => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => $sensor_type)), 
                      'icon'  => $config['sensor_types'][$sensor_type]['icon'],
                      ); 
    echo generate_box_open($box_args);

    echo('<table class="table table-condensed table-striped">');
    foreach ($sensors as $sensor)
    {
      print_sensor_row($sensor, $vars);
    }

    echo("</table>");
    echo generate_box_close();
  }
}

// EOF
