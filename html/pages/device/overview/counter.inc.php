<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

//$counter_types = array_keys($config['counter_types']);

$sql = "SELECT * FROM `counters` WHERE `device_id` = ? AND `counter_deleted` = 0 ORDER BY `counter_class`, `counter_descr`"; // order numerically by entPhysicalIndex for ports

// Cache all counters
foreach (dbFetchRows($sql, [$device['device_id']]) as $entry) {
    if (strlen($entry['measured_class']) && is_numeric($entry['measured_entity'])) {
        // Counters bounded with measured class, mostly ports
        // array index -> ['measured']['port']['345'][] = counter array
        $counters_db['measured'][$entry['measured_class']][$entry['measured_entity']][] = $entry;
    } else {
        // Know counters in separate boxes, all other in counter box
        $counter_type = isset($config['counter_types'][$entry['counter_class']]) ? $entry['counter_class'] : 'counter';
        //$counter_type = 'counter'; // Keep all counters in single box
        $counters_db[$counter_type][$entry['counter_id']] = $entry;
    }
}
//r($counters_db['measured']);

// Now print founded bundle (measured_class+counter)
if (isset($counters_db['measured'])) {
    $vars['measured_icon'] = FALSE; // Hide measured icons
    foreach ($counters_db['measured'] as $measured_class => $measured_entity) {
        $tab      = $measured_class == 'printersupply' ? 'printing' : $measured_class . 's';
        $box_args = ['title' => nicecase($measured_class) . ' Counters',
                     'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => $tab, 'view' => 'counters']),
                     'icon'  => $config['icon']['counter']
        ];
        echo generate_box_open($box_args);

        echo ' <table class="table table-condensed table-striped">';

        foreach ($measured_entity as $entity_id => $entry) {
            $entity = get_entity_by_id_cache($measured_class, $entity_id);
            //r($entity);
            $entity_link = generate_entity_link($measured_class, $entity);
            $entity_type = entity_type_translate_array($measured_class);

            // Remove port name from counter description
            $rename_from = [];
            if ($measured_class == 'port') {
                $rename_from[] = $entity['port_label'];
                $rename_from[] = $entity['ifDescr'];
                $rename_from[] = $entity['port_label_short'];
                if (strlen($entity['port_label_base']) > 4) {
                    $rename_from[] = $entity['port_label_base'];
                }
                $rename_from = array_unique($rename_from);
            } else {
                // FIXME. I not remember what should be here, but not its incorrect
                $rename_from[] = entity_rewrite($measured_class, $entity);
            }
            //r($rename_from);
            //echo('      <tr class="'.$port['row_class'].'">
            //  <td class="state-marker"></td>
            echo('      <tr>
        <td colspan="6" class="entity">' . get_icon($entity_type['icon']) . '&nbsp;' . $entity_link . '</td></tr>');
            foreach ($entry as $counter) {
                $counter['counter_descr'] = trim(str_ireplace($rename_from, '', $counter['counter_descr']), ":- \t\n\r\0\x0B");
                if (empty($counter['counter_descr'])) {
                    // Some time counter descriptions equals to entity name
                    $counter['counter_descr'] = nicecase($counter['counter_class']);
                }

                print_counter_row($counter, $vars);
            }
        }

        ?>
        </table>
        <?php
        echo generate_box_close();
    }
    // End for print bounds, unset this array
    unset($counters_db['measured']);
}

foreach ($counters_db as $counter_type => $counters) {
    if ($counter_type == 'measured') {
        continue;
    } // Just be on the safe side

    if (count($counters)) {
        $box_args = ['title' => nicecase($counter_type),
                     //'url'   => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => $counter_type)),
                     'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => 'counter']),
                     'icon'  => $config['counter_types'][$counter_type]['icon'],
        ];
        echo generate_box_open($box_args);

        echo('<table class="table table-condensed table-striped">');
        foreach ($counters as $counter) {
            print_counter_row($counter, $vars);
        }

        echo("</table>");
        echo generate_box_close();
    }
}

// EOF
