<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Keep sensors order for base types static
$sensor_types = [ 'temperature', 'humidity', 'fanspeed', 'airflow', 'current', 'voltage', 'power', 'apower', 'rpower', 'frequency' ];
$other_types  = array_diff(array_keys($config['sensor_types']), $sensor_types);
$sensor_types = array_merge($sensor_types, $other_types);

// Classes without entity association, but with group by measured label
$measured_label_classes = array_keys($config['sensor_measured']);

$measured_entindex = [];  // collect measured entities
$measured_ifindex  = [];  // collect measured entities (by ifindex)
$measured_multi    = [];
$sensors_db        = [];

$sql = "SELECT * FROM `sensors`";
$sql .= generate_where_clause(["`device_id` = ? ", generate_query_values($sensor_types, 'sensor_class'), "`sensor_deleted` = 0"]);
$sql .= " ORDER BY CAST(`entPhysicalIndex_measured` AS SIGNED), `measured_entity_label`, CAST(`entPhysicalIndex` AS SIGNED), `sensor_descr`";

// Cache all sensors
foreach (dbFetchRows($sql, [ $device['device_id'] ]) as $entry) {
    if (is_numeric($entry['measured_entity']) && !safe_empty($entry['measured_class'])) {
        // Collect entPhysical entities
        if (!(isset($measured_entindex[$entry['measured_entity']]) || isset($measured_ifindex[$entry['measured_entity']]))) {
            if ($entry['entPhysicalIndex']) {
                $measured_entindex[$entry['measured_entity']] = $entry['entPhysicalIndex'];
            } elseif ($entry['measured_class'] === 'port') {
                // When not correct entPhysicalIndex stored
                $port                                        = get_port_by_id_cache($entry['measured_entity']);
                $measured_ifindex[$entry['measured_entity']] = $port['ifIndex'];
            }
        }

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
                $measured_multi[$entry['measured_class']][$entry['measured_entity']][$lane][$entry['sensor_class']][]  = $entry;
                break;
            default:
                $sensors_db['measured'][$entry['measured_class']][$entry['measured_entity']][$entry['sensor_class']][] = $entry;
        }

    } elseif (in_array($entry['measured_class'], $measured_label_classes, TRUE) && !safe_empty($entry['measured_entity_label'])) {
        // Outlet currently not have real entity associations
        //r($entry);
        $sensors_db['measured'][$entry['measured_class']][$entry['measured_entity_label']][$entry['sensor_class']][] = $entry;
    } else {
        $sensors_db[$entry['sensor_class']][$entry['sensor_id']] = $entry;
    }
}
//}
//r($sensors_db['measured']);
//r($measured_multi);

// Now print founded bundle (measured_class+sensor)
if (isset($sensors_db['measured'])) {
    $vars['measured_icon'] = FALSE; // Hide measured icons

    // Fetch entphysical serials and part/vendor names
    $measured_entphysical = [];
    if ($measured_entindex) {
        foreach (dbFetchRows('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `deleted` IS NULL' . generate_query_values_and($measured_entindex, 'entPhysicalIndex'), [$device['device_id']]) as $entry) {
            foreach ($measured_entindex as $entity_id => $entPhysicalIndex) {
                if ($entPhysicalIndex === $entry['entPhysicalIndex']) {
                    if (empty($entry['entPhysicalModelName']) && empty($entry['entPhysicalSerialNum'])) {
                        // Containers.. try to get inside
                        //r($entry);
                        $tmp_entry = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `deleted` IS NULL AND `entPhysicalIndex` = ?', [$device['device_id'], $entry['entPhysicalContainedIn']]);
                        if (!empty($tmp_entry['entPhysicalModelName']) || !empty($tmp_entry['entPhysicalSerialNum'])) {
                            $entry = $tmp_entry;
                            //r($tmp_entry);
                        }
                    }
                    $measured_entphysical[$entity_id] = $entry;
                    unset($measured_entindex[$entity_id]);
                    break;
                }
            }
        }
    }
    if ($measured_ifindex) {
        foreach (dbFetchRows('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `deleted` IS NULL' . generate_query_values_and($measured_ifindex, 'ifIndex'), [$device['device_id']]) as $entry) {
            foreach ($measured_ifindex as $entity_id => $ifIndex) {
                if ($ifIndex === $entry['ifIndex']) {
                    $measured_entphysical[$entity_id] = $entry;
                    unset($measured_ifindex[$entity_id]);
                    break;
                }
            }
        }
    }
    //r($measured_entphysical);

    $show_compact = $config['sensors']['web_measured_compact'];
    foreach ($sensors_db['measured'] as $measured_class => $measured_entity) {
        if (isset($config['sensor_measured'][$measured_class])) {
            $measured_title = $config['sensor_measured'][$measured_class]['text'];
            $measured_icon  = $config['sensor_measured'][$measured_class]['icon'];
        } else {
            $measured_title = $config['sensor_types'][$measured_class]['text'];
            $measured_icon  = $config['sensor_types'][$measured_class]['icon'];
        }
        $measured_title = isset($config['sensor_measured'][$measured_class]) ? $config['sensor_measured'][$measured_class]['text'] : $config['sensor_types'][$measured_class]['text'];
        $box_args       = [
            'title' => (empty($measured_title) ? nicecase($measured_class) : $measured_title) . ' sensors',
            'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => $measured_class . 's', 'view' => 'sensors']),
            'icon'  => empty($measured_icon) ? $config['icon']['sensor'] : $measured_icon
        ];
        $box_args['header-controls'] = [
            'controls' => [
                'compact' => [
                        'text'   => $show_compact ? 'Full View' : 'Compact View',
                        'icon'   => $show_compact ? 'glyphicon-th-list' : 'glyphicon-th',
                        'config' => 'sensors|web_measured_compact', // check this config
                        'value'  => $show_compact ? 'no' : 'yes', // toggle
                ]
            ]
        ];
        echo generate_box_open($box_args);

        echo ' <table class="table table-condensed table-striped">';

        //r($measured_entity);
        foreach ($measured_entity as $entity_id => $entry) {
            $rename_from = [];
            if (in_array($measured_class, $measured_label_classes, TRUE)) {
                // Outlets not have real entities association
                $entity       = [];
                $entity_link  = $entity_id; // Just Outlet Label
                $entity_type  = $config['sensor_measured'][$measured_class];
                $entity_parts = '';
                $rename_from[] = $entity_id . ' '; // remove measured_entity_label
            } else {
                // Common known entities
                $entity = get_entity_by_id_cache($measured_class, $entity_id);
                //r($entity);
                $entity_link  = generate_entity_link($measured_class, $entity);
                $entity_type  = entity_type_translate_array($measured_class);
                $entity_parts = '';
                if ($measured_entphysical[$entity_id]) {
                    // Entity Vendor/PartNum/Serial
                    if ($measured_entphysical[$entity_id]['entPhysicalMfgName']) {
                        $entity_parts .= ' <span class="label label-info">' . $measured_entphysical[$entity_id]['entPhysicalMfgName'] . '</span>';
                    }
                    if ($measured_entphysical[$entity_id]['entPhysicalModelName']) {
                        $entity_parts .= ' <span class="label label-warning">' . $measured_entphysical[$entity_id]['entPhysicalModelName'] . '</span>';
                    }
                    if ($measured_entphysical[$entity_id]['entPhysicalSerialNum']) {
                        $entity_parts .= ' <span class="label label-primary">SN: ' . $measured_entphysical[$entity_id]['entPhysicalSerialNum'] . '</span>';
                    }
                }

                // Remove port name from sensor description
                if ($measured_class === 'port') {
                    if (is_numeric($entity['port_label'])) {
                        $rename_from[] = $entity['port_label'] . ' ';
                    } else {
                        $rename_from[] = $entity['port_label'];
                        $rename_from[] = $entity['ifDescr'];
                        $rename_from[] = $entity['port_label_short'];
                    }
                    if (strlen($entity['port_label_base']) > 4) {
                        $rename_from[] = $entity['port_label_base'];
                    }
                    $rename_from = array_unique($rename_from);
                } else {
                    entity_rewrite($measured_class, $entity);
                }
                $entity['rename_from'] = $rename_from;
            }

            echo('      <tr>
        <td colspan="6" class="entity">' . get_icon($entity_type['icon']) . '&nbsp;' . $entity_link . $entity_parts . '</td></tr>');

            if ($show_compact && (!in_array($measured_class, $measured_label_classes, TRUE) ||
                    $config['sensor_measured'][$measured_class]['compact'])) {
                // In compact view need multi-lane split
                $lanes_count = safe_count($measured_multi[$measured_class][$entity_id]);
                if ($lanes_count > 1) {
                    if (isset($measured_multi[$measured_class][$entity_id][0]) && $lanes_count === 5) {
                        // Multilane with separated voltage or others
                        for ($i = 1; $i <= 4; $i++) {
                            $measured_multi[$measured_class][$entity_id][$i] = array_merge($measured_multi[$measured_class][$entity_id][$i],
                                                                                           $measured_multi[$measured_class][$entity_id][0]);
                        }
                        unset($measured_multi[$measured_class][$entity_id][0]);
                    }
                    foreach ($measured_multi[$measured_class][$entity_id] as $lane => $lane_entry) {
                        echo '<tr><td style="width: 10px;" class="vertical-align"><span class="label">L' . $lane . '</span></td>' .
                             get_compact_sensors_line($measured_class, $lane_entry, $vars) . '</tr>';
                    }
                } else {
                    echo '<tr><td style="width: 10px;"></td>' . get_compact_sensors_line($measured_class, $entry, $vars) . '</tr>';
                }
            } else {
                // order dom sensors always by temperature, voltage, current, dbm, power
                $order = [];
                if (safe_count($entry) > 0) {
                    $classes = array_keys($entry);
                    $order   = array_intersect(['temperature', 'voltage', 'current', 'dbm', 'power'], $classes);
                    $order   = array_merge($order, array_diff($classes, $order));
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

            //if ($show_compact) { echo '<tr>' . $line . '</tr>'; }
        }

        ?>
        </table>
        <?php
        echo generate_box_close();
    }
    // End for print bounds, unset this array
    unset($sensors_db['measured']);
}

foreach ($sensor_types as $sensor_type) {
    if (safe_empty($sensors_db[$sensor_type])) {
        continue;
    }

    $box_args = [
      'title' => nicecase($sensor_type),
      'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => $sensor_type]),
      'icon'  => $config['sensor_types'][$sensor_type]['icon'],
    ];
    echo generate_box_open($box_args);

    echo('<table class="table table-condensed table-striped">');
    foreach ($sensors_db[$sensor_type] as $sensor) {
        print_sensor_row($sensor, $vars);
    }

    echo("</table>");
    echo generate_box_close();
}

// EOF
