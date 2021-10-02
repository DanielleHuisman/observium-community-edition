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

$sensor_types = array_keys($config['sensor_types']);

$measured_multi = [];
//foreach ($sensor_types as $sensor_type) {
  $sql  = "SELECT * FROM `sensors`";
  //$sql .= " WHERE `sensor_class` = ? AND `device_id` = ? AND `sensor_deleted` = 0 ORDER BY `sensor_type`, `entPhysicalIndex` * 1, `sensor_descr`"; // order numerically by entPhysicalIndex for ports
  $sql .= " WHERE `device_id` = ? " . generate_query_values($sensor_types, 'sensor_class') .
          " AND `sensor_deleted` = 0 ORDER BY `entPhysicalIndex_measured` * 1, `entPhysicalIndex` * 1, `sensor_descr`"; // order numerically by entPhysicalIndex for ports

  // Cache all sensors
  foreach (dbFetchRows($sql, [ $device['device_id'] ]) as $entry) {
    if (is_numeric($entry['measured_entity']) && strlen($entry['measured_class'])) {
      // Sensors bounded with measured class, mostly ports
      // array index -> ['measured']['port']['345'][] = sensor array
      switch ($entry['measured_class']) {
        case 'port':
          // Ports can be MultiLane, ie 100Gbit, 40Gbit, detect (currently by descriptions)
          if (preg_match('/(Lane|Channel) (?<lane>\d+)/i', $entry['sensor_descr'], $matches)) {
            $lane = $matches['lane'];
          } else {
            $lane = 0;
          }
          $sensors_db['measured'][$entry['measured_class']][$entry['measured_entity']][$entry['sensor_class']][] = $entry;
          $measured_multi[$entry['measured_class']][$entry['measured_entity']][$lane][$entry['sensor_class']][] = $entry;
          break;
        default:
          $sensors_db['measured'][$entry['measured_class']][$entry['measured_entity']][$entry['sensor_class']][] = $entry;
      }

    } else {
      $sensors_db[$entry['sensor_class']][$entry['sensor_id']] = $entry;
    }
  }
//}
//r($sensors_db['measured']);
//r($measured_multi);

// Print Multi sensors at single line for each entity
/* WiP
if (count($measured_multi)) {
  $vars['measured_icon'] = FALSE; // Hide measured icons
  foreach ($measured_multi as $measured_class => $measured_entity) {
    $box_args = ['title' => nicecase($measured_class) . ' sensors',
                 'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => $measured_class . 's', 'view' => 'sensors']),
                 'icon'  => $config['icon']['sensor']
    ];
    echo generate_box_open($box_args);
    echo ' <table class="table table-condensed table-striped">';
    echo ' </table>';
    echo generate_box_close();
  }
}
*/

// Now print founded bundle (measured_class+sensor)
if (isset($sensors_db['measured'])) {
  $vars['measured_icon'] = FALSE; // Hide measured icons

  foreach ($sensors_db['measured'] as $measured_class => $measured_entity) {
    $box_args = array('title' => nicecase($measured_class).' sensors',
                      'url'   => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => $measured_class.'s', 'view' => 'sensors')),
                      'icon'  => $config['icon']['sensor']
                      );
    echo generate_box_open($box_args);

    echo ' <table class="table table-condensed table-striped">';

    foreach ($measured_entity as $entity_id => $entry)
    {
      $entity      = get_entity_by_id_cache($measured_class, $entity_id);
      //r($entity);
      $entity_link = generate_entity_link($measured_class, $entity);
      $entity_type = entity_type_translate_array($measured_class);

      // Remove port name from sensor description
      $rename_from = [];
      if ($measured_class === 'port') {
        if (is_numeric($entity['port_label'])) {
          $rename_from[] = $entity['port_label'].' ';
        } else {
          $rename_from[] = $entity['port_label'];
          $rename_from[] = $entity['ifDescr'];
          $rename_from[] = $entity['port_label_short'];
        }
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

      // order dom sensors always by temperature, voltage, current, dbm, power
      $order = [];
      if (safe_count($entry) > 0) {
        $classes = array_keys($entry);
        //r($types);
        $order = array_intersect([ 'temperature', 'voltage', 'current', 'dbm', 'power' ], $classes);
        $order = array_merge($order, array_diff($classes, $order));
        //r($order);
      }
      foreach ($order as $class) {
        foreach ($entry[$class] as $sensor) {
          $sensor['sensor_descr'] = trim(str_ireplace($rename_from, '', $sensor['sensor_descr']), ":- \t\n\r\0\x0B");
          if (empty($sensor['sensor_descr'])) {
            // Some time sensor descriptions equals to entity name
            $sensor['sensor_descr'] = nicecase($sensor['sensor_class']);
          }

          print_sensor_row($sensor, $vars);
        }
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
  if ($sensor_type === 'measured') { continue; } // Just be on the safe side

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
