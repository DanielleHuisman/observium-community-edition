<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// F5 TEMP STUFF

function print_f5_lb_virtual_table_header($vars)
{
    if ($vars['view'] == "graphs") {
        $table_class = OBS_CLASS_TABLE_STRIPED_TWO;
    } else {
        $table_class = OBS_CLASS_TABLE_STRIPED;
    }

    echo('<table class="' . $table_class . '">' . PHP_EOL);
    $cols = [
      [NULL, 'class="state-marker"'],
      'device'     => ['Device', 'style="width: 200px;"'],
      'virt_name'  => ['Virtual'],
      ['', 'style="width: 100px;"'],
      'virt_ip'    => ['Address', 'style="width: 250px;"'],
      'virt_type'  => ['Type', 'style="width: 250px;"'],
      'virt_state' => ['Status', 'style="width: 250px;"'],
    ];

    if ($vars['page'] == "device") {
        unset($cols['device']);
    }

    echo(get_table_header($cols, $vars));
    echo('<tbody>' . PHP_EOL);
}

function get_customoid_by_id($oid_id)
{

    if (is_numeric($oid_id)) {
        $oid = dbFetchRow('SELECT * FROM `oids` WHERE `oid_id` = ?', [$oid_id]);
    }
    if (safe_count($oid)) {
        return $oid;
    }

    return FALSE;

} // end function get_customoid_by_id()

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_application_by_id($application_id)
{
    if (is_numeric($application_id)) {
        $application = dbFetchRow("SELECT * FROM `applications` WHERE `app_id` = ?", [$application_id]);
    }
    if (is_array($application)) {
        return $application;
    } else {
        return FALSE;
    }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function accesspoint_by_id($ap_id, $refresh = '0')
{
    $ap = dbFetchRow("SELECT * FROM `accesspoints` WHERE `accesspoint_id` = ?", [$ap_id]);

    return $ap;
}

function generate_entity_popup_graphs($entity, $vars)
{
    global $config;

    $entity_type = $vars['entity_type'];

    if (is_array($config['entities'][$entity_type]['graph'])) {
        if (isset($config['entities'][$entity_type]['graph']['type'])) {
            $graphs[] = $config['entities'][$entity_type]['graph'];
        } else {
            $graphs = $config['entities'][$entity_type]['graph'];
        }

        foreach ($graphs as $graph_array) {
            //$graph_array = $config['entities'][$entity_type]['graph'];
            // We can draw a graph for this type/metric pair!

            foreach ($graph_array as $key => $val) {
                // Check to see if we need to do any substitution
                if (substr($val, 0, 1) === "@") {
                    $nval              = substr($val, 1);
                    $graph_array[$key] = $entity[$nval];
                }
            }
            $graph_array['height'] = "100";
            $graph_array['width']  = "323";

            $content = '<div style="white-space: nowrap;">';
            $content .= "<div class=entity-title><h4>" . nicecase(str_replace("_", " ", $graph_array['type'])) . "</h4></div>";
            /*
            $content = generate_box_open(array('title' => nicecase(str_replace("_", " ", $graph_array['type'])),
                                               'body-style' => 'white-space: nowrap;'));
            */
            foreach ([ 'day', 'month' ] as $period) {
                $graph_array['from'] = get_time($period);
                $content             .= generate_graph_tag($graph_array);
            }
            $content .= "</div>";
            //$content .= generate_box_close();
        }

        //r($content);
        return $content;
    }
}

function generate_entity_popup_header($entity, $vars)
{
    $translate = entity_type_translate_array($vars['entity_type']);

    $vars['popup']       = TRUE;
    $vars['entity_icon'] = TRUE;
    $contents            = '';

    switch ($vars['entity_type']) {
        case "sensor":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_sensor_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        case "toner":
        case "printersupply":
        case "supply":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_printersupplies_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        case "bgp_peer":
            if ($entity['peer_device_id']) {
                $peer_dev  = device_by_id_cache($entity['peer_device_id']);
                $peer_name = '<br /><a class="entity" style="font-weight: bold;">' . $peer_dev['hostname'] . '</a>';
            } elseif ($entity['reverse_dns']) {
                $peer_name = '<br /><span style="font-weight: bold;">' . $entity['reverse_dns'] . '</span>';
            }
            $astext = '<span>AS' . $entity['human_remote_as'];
            if ($entity['astext']) {
                $astext .= '<br />' . $entity['astext'] . '</span>';
            }
            $astext   .= '</span>';
            $contents .= generate_box_open();
            $contents .= '
      <table class="' . OBS_CLASS_TABLE . '">
        <tr class="' . $entity['row_class'] . ' vertical-align" style="font-size: 10pt;">
          <td class="state-marker"></td>
          <td style="width: 10px;"></td>
          <td style="width: 10px;"><i class="' . $translate['icon'] . '"></i></td>
          <td><a class="entity-popup" style="font-size: 15px; font-weight: bold;">' . escape_html($entity['entity_shortname']) . '</a>' . $peer_name . '</td>
          <td class="text-nowrap" style="width: 20%;">' . $astext . '</td>
          <td></td>
        </tr>
      </table>';
            $contents .= generate_box_close();
            break;

        case "sla":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_sla_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        case "processor":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_processor_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        case "mempool":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_mempool_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        case "p2pradio":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_p2pradio_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        case "status":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_status_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        case "counter":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_counter_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        case "storage":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_storage_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        case "netscalervsvr":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_netscalervsvr_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        case "netscalersvc":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_netscalersvc_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        case "netscalersvcgrpmem":

            $contents .= generate_box_open();
            $contents .= '<table class="' . OBS_CLASS_TABLE . '">';
            $contents .= generate_netscalersvcmem_row($entity, $vars);
            $contents .= '</table>';
            $contents .= generate_box_close();

            break;

        default:
            entity_rewrite($vars['entity_type'], $entity);
            $contents = generate_box_open() . '
      <table class="' . OBS_CLASS_TABLE_STRIPED . '">
        <tr class="' . $entity['row_class'] . '" style="font-size: 10pt;">
          <td class="state-marker"></td>
          <td style="width: 10px;"></td>
          <td width="400"><i class="' . $translate['icon'] . '" style="margin-right: 10px;"></i> <a class="entity-popup" style="font-size: 15px; font-weight: bold;">' . escape_html($entity['entity_name']) . '</a></td>
          <td width="100"></td>
          <td></td>
        </tr>
      </table>' . generate_box_close();
    }

    return $contents;
}

function generate_entity_popup($entity, $vars)
{
    if (is_numeric($entity)) {
        $entity = get_entity_by_id_cache($vars['entity_type'], $entity);
    }
    $device = device_by_id_cache($entity['device_id']);

    $content = generate_device_popup_header($device);
    $content .= generate_entity_popup_header($entity, $vars);
    $content .= generate_entity_popup_graphs($entity, $vars);

    return $content;
}

function generate_entity_popup_multi($entities, $vars)
{
    // Note here limited only to one entity_type and one device_id

    $count = count($entities);
    // First element
    $entity = array_shift($entities);
    if (is_numeric($entity)) {
        $entity = get_entity_by_id_cache($vars['entity_type'], $entity);
    }
    $device = device_by_id_cache($entity['device_id']);

    $header = generate_entity_popup_header($entity, $vars);
    if ($count > 1) {
        // Multiple entities graph
        /// FIXME. Need add multi-graphs
        $graphs = generate_entity_popup_graphs($entity, $vars);// This is incorrect, only first graph
    } else {
        // Single entity graph
        $graphs = generate_entity_popup_graphs($entity, $vars);
    }

    // All other elements
    foreach ($entities as $entity) {
        if (is_numeric($entity)) {
            $entity = get_entity_by_id_cache($vars['entity_type'], $entity);
        }
        if ($entity['device_id'] != $device['device_id']) {
            // Skip if passed entity from different device
            continue;
        }

        $header .= generate_entity_popup_header($entity, $vars);
        //$graphs .= generate_entity_popup_graphs($entity, $vars); // Currently disabled, need multi graph
    }

    $content = generate_device_popup_header($device);
    $content .= $header;
    $content .= $graphs;

    return $content;
}

// Measured specific functions

function generate_query_entity_measured($entity_type, $vars) {

    $entity_array = entity_type_translate_array($entity_type);

    $column_measured_id   = $entity_array['table_fields']['measured_id'];
    $column_measured_type = $entity_array['table_fields']['measured_type'];
    $measure_array        = [];

    // Build query
    foreach ($vars as $var => $value) {
        if (!is_array($value)) {
            $value = explode(',', $value);
        }

        switch ($var) {
            case 'measured_group':
                foreach (dbFetchColumn('SELECT DISTINCT `' . $column_measured_type . '` FROM `' . $entity_array['table'] . '` WHERE `' . $entity_array['table_fields']['deleted'] . '` = ?', [0]) as $measured_type) {
                    if (!$measured_type) {
                        continue;
                    }

                    $entities    = get_group_entities($value, $measured_type);
                    $measure_sql = '';

                    switch ($measured_type) {
                        case 'port':
                        case 'printersupply':
                            $measure_sql = generate_query_values($measured_type, $column_measured_type);
                            $measure_sql .= generate_query_values_and($entities, $column_measured_id);
                            break;
                    }
                    if ($measure_sql) {
                        $measure_array[] = $measure_sql;
                    }
                }
                break;
            case 'measured_state':
                // UP / DOWN / STUTDOWN / NONE states
                //$value = (array)$value;
                // Select all without measured entities
                if (in_array('none', $value)) {
                    $measure_array[] = generate_query_values(1, $column_measured_id);
                    $value           = array_diff($value, ['none']);
                }
                if (count($value)) {
                    // Limit statuses with measured entities
                    foreach (dbFetchColumn('SELECT DISTINCT `' . $column_measured_type . '` FROM `' . $entity_array['table'] . '` WHERE `' . $entity_array['table_fields']['deleted'] . '` = ?', [0]) as $measured_type) {
                        if (!$measured_type) {
                            continue;
                        }

                        $measure_sql      = '';
                        $measure_entities = dbFetchColumn('SELECT DISTINCT `' . $column_measured_id . '` FROM `' . $entity_array['table'] . '` WHERE `' . $column_measured_type . '` = ? AND `' . $entity_array['table_fields']['deleted'] . '` = ?', [$measured_type, 0]);
                        switch ($measured_type) {
                            case 'port':
                                $where_array = build_ports_where_array(['port_id' => $measure_entities, 'state' => $value]);

                                $entity_sql = 'SELECT `port_id` FROM `ports` WHERE 1 ';
                                $entity_sql .= implode('', $where_array);
                                $entities   = dbFetchColumn($entity_sql);
                                //$entities = dbFetchColumn($entity_sql, NULL, TRUE);
                                //r($entities);
                                $measure_sql = generate_query_values($measured_type, $column_measured_type);
                                $measure_sql .= generate_query_values_and($entities, $column_measured_id);
                                break;
                            case 'printersupply':
                                break;
                        }
                        if ($measure_sql) {
                            $measure_array[] = $measure_sql;
                        }

                    }
                }
                break;
        }
    }

    switch (count($measure_array)) {
        case 0:
            return '';

        case 1:
            return $measure_array[0];
    }

    //r($measure_array);
    return '((' . implode(') OR (', $measure_array) . '))';
}

// EOF
