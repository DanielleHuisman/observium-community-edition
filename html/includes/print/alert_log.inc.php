<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/**
 * Display events.
 *
 * Display pages with alert logs in multiple formats.
 * Examples:
 * print_alert_log() - display last 10 events from all devices
 * print_alert_log(array('pagesize' => 99)) - display last 99 events from all device
 * print_alert_log(array('pagesize' => 10, 'pageno' => 3, 'pagination' => TRUE)) - display 10 events from page 3 with pagination header
 * print_alert_log(array('pagesize' => 10, 'device' = 4)) - display last 10 events for device_id 4
 * print_alert_log(array('short' => TRUE)) - show small block with last events
 *
 * @param array $vars
 *
 * @return none
 *
 */
function print_alert_log($vars)
{
    global $alert_rules, $config;

    // This should be set outside, but do it here if it isn't
    if (!is_array($alert_rules)) {
        $alert_rules = cache_alert_rules();
    }

    // Get events array
    $events = get_alert_log($vars);

    if (!$events['count']) {
        if (!$vars['no_empty_message']) {
            // There have been no entries returned. Print the warning.
            print_message('<h4>No alert log entries found!</h4>', FALSE);
        }
    } else {
        // Entries have been returned. Print the table.
        $list = [
          'device' => FALSE,
          'entity' => FALSE,
          'info'   => !$events['short'] && get_db_version() >= 479 // informational icon
        ];
        if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] == 'alert_log') {
            $list['device'] = TRUE;
        }
        if ($events['short'] || !isset($vars['entity']) || empty($vars['entity'])) {
            $list['entity'] = TRUE;
        }

        if (!isset($vars['alert_test_id']) || empty($vars['alert_test_id']) || $vars['page'] == 'alert_check' || TRUE) {
            $list['alert_test_id'] = TRUE;
        }
        if (!isset($vars['entity_type']) || empty($vars['entity_type']) || $vars['page'] == 'alert_check' || TRUE) {
            $list['entity_type'] = TRUE;
        }

        $string = generate_box_open($vars['header']);

        $string .= '<table class="table table-striped table-hover table-condensed-more">' . PHP_EOL;

        if (!$events['short']) {
            $cols         = [];
            $cols[]       = [NULL, 'class="state-marker"'];
            $cols['date'] = ['Date', 'style="width: 160px"'];
            if ($list['device']) {
                $cols['device'] = 'Device';
            }
            if ($list['alert_test_id']) {
                $cols['alert_test_id'] = 'Alert Check';
            }
            if ($list['entity']) {
                $cols['entity'] = 'Entity';
            }
            $cols[]         = 'Message';
            $cols['status'] = 'Status';
            //$cols['notified'] = array('Notified', 'style="width: 40px"');
            if ($list['info']) {
                $cols['info'] = [NULL, 'style="width: 10px"'];
            }
            $string .= get_table_header($cols); // , $vars); // Actually sorting is disabled now
        }
        $string .= '  <tbody>' . PHP_EOL;

        foreach ($events['entries'] as $entry) {

            $alert_rule = $alert_rules[$entry['alert_test_id']];

            // Functionize?

            // Set colours and classes based on the status of the alert
            switch ($entry['log_type']) {
                case 'OK':
                    $entry['class']          = "green";
                    $entry['html_row_class'] = "success";
                    break;
                case 'RECOVER_NOTIFY':
                    $entry['class']          = "green";
                    $entry['html_row_class'] = "info";
                    break;
                case 'ALERT_NOTIFY':
                case 'FAIL':
                    $entry['class']          = "red";
                    $entry['html_row_class'] = "error";
                    break;
                case 'FAIL_DELAYED':
                    $entry['class']          = "purple";
                    $entry['html_row_class'] = "warning";
                    break;
                case 'FAIL_SUPPRESSED':
                case 'RECOVER_SUPPRESSED':
                    $entry['class']          = "purple";
                    $entry['html_row_class'] = "suppressed";
                    break;
                default:
                    // Anything else set the colour to grey and the class to disabled.
                    $entry['class']          = "gray";
                    $entry['html_row_class'] = "disabled";
            }

            $string .= '  <tr class="' . $entry['html_row_class'] . '">' . PHP_EOL;

            $string .= '<td class="state-marker"></td>' . PHP_EOL;

            if ($events['short']) {
                $string .= '    <td class="syslog text-nowrap">';
                $string .= generate_tooltip_time($entry['timestamp']) . '</td>' . PHP_EOL;
            } else {
                $string .= '    <td>';
                $string .= format_timestamp($entry['timestamp']) . '</td>' . PHP_EOL;
            }

            if ($list['device']) {
                $dev         = device_by_id_cache($entry['device_id']);
                $device_vars = [
                  'page'    => 'device',
                  'device'  => $entry['device_id'],
                  'tab'     => 'logs',
                  'section' => 'alertlog'
                ];
                $string      .= '    <td class="entity">' . generate_device_link_short($dev, $device_vars) . '</td>' . PHP_EOL;
            }
            if ($list['alert_test_id']) {
                $string .= '     <td class="entity"><a href="' . generate_url(['page' => 'alert_check', 'alert_test_id' => $alert_rule['alert_test_id']]) . '">' . escape_html($alert_rule['alert_name']) . '</a></td>';
            }

            if ($list['entity']) {
                $string .= '    <td class="entity">';
                if ($list['entity_type']) {
                    $string .= get_icon($config['entities'][$entry['entity_type']]['icon']) . ' ';
                }
                if ($events['short']) {
                    $string .= '    ' . generate_entity_link($entry['entity_type'], $entry['entity_id'], NULL, NULL, NULL, TRUE) . '</td>' . PHP_EOL;
                } else {
                    $string .= '    ' . generate_entity_link($entry['entity_type'], $entry['entity_id']) . '</td>' . PHP_EOL;
                }
            }

            $string .= '<td>' . escape_html($entry['message']) . '</td>' . PHP_EOL;
            if (!$vars['short']) {
                $string .= '<td>' . escape_html($entry['log_type']) . '</td>' . PHP_EOL;
                /*
                $string .= '<td class="text-right">';
                if ($entry['notified'])
                {
                  $string .= '<span class="label label-success">OK</span>';
                } else if (!stristr($entry['log_type'], 'notify')) {
                  $string .= '<span class="label">SKIP</span>';
                } else {
                  $string .= '<span class="label label-warning">NO</span>';
                }
                $string .= '</td>' . PHP_EOL;
                */
            }
            if ($list['info']) {
                $state = '';
                if ($log_state = safe_json_decode($entry['log_state'])) {
                    // Metrics
                    $state  = generate_box_open(['title'     => 'Polled values', 'header-border' => TRUE,
                                                 //'body-style' => 'padding: 0px !important;',
                                                 'box-style' => 'margin-bottom: 0px;']);
                    $state  .= '<table class="table table-striped table-condensed-more">';
                    $state  .= '<thead><tr><th>Metric</th><th>Value</th></tr><thead>';
                    $state  .= '<tbody>';
                    $failed = [];
                    foreach ($log_state['failed'] as $metric) {
                        $failed[$metric['metric']] = $metric['metric'];
                    }
                    //r($log_state['failed']);
                    foreach ($log_state['metrics'] as $metric => $value) {
                        $value = format_value($value);
                        if (isset($failed[$metric])) {
                            $value = '<i class="red">' . $value . '</i>';
                        }
                        $state .= "<tr><td>$metric</td><td>$value</td></tr>";
                    }
                    $state .= '<tbody></table>';
                    $state = generate_tooltip_link(NULL, get_icon('info'), $state);
                    $state .= generate_box_close();
                }
                $string .= '<td>' . $state . '</td>' . PHP_EOL;
            }

            $string .= '  </tr>' . PHP_EOL;
        }

        $string .= '  </tbody>' . PHP_EOL;
        $string .= '</table>';

        $string .= generate_box_close();

        // Print pagination header
        if ($events['pagination_html']) {
            $string = $events['pagination_html'] . $string . $events['pagination_html'];
        }

        // Print events
        echo $string;
    }
}

/**
 * Display short events.
 *
 * This is use function:
 * print_alert_log(array('short' => TRUE))
 *
 * @param array $vars
 *
 * @return none
 *
 */

function print_alert_log_short($var)
{
    $var['short'] = TRUE;
    print_alert_log($var);
}

/**
 * Params:
 * short
 * pagination, pageno, pagesize
 * device_id, port, type, message, timestamp_from, timestamp_to
 */

function get_alert_log($vars)
{
    $array = [];

    // Short events? (no pagination, small out)
    $array['short'] = (isset($vars['short']) && $vars['short']);

    // With pagination? (display page numbers in header)
    $array['pagination'] = (isset($vars['pagination']) && $vars['pagination']);
    pagination($vars, 0, TRUE);                    // Get default pagesize/pageno
    $array['pageno']   = $vars['pageno'];
    $array['pagesize'] = $vars['pagesize'];
    $start             = $array['pagesize'] * $array['pageno'] - $array['pagesize'];
    $pagesize          = $array['pagesize'];

    // Begin query generate
    $param = [];
    $where = ' WHERE 1 ';
    foreach ($vars as $var => $value) {
        if (!safe_empty($value)) {
            switch ($var) {
//        case 'alert_entry':
//          $where .= generate_query_values_and($value, 'alert_table_id');
//          break;
                case 'log_type':
                    $where .= generate_query_values_and($value, 'log_type');
                    break;
                case 'alert_test_id':
                    $where .= generate_query_values_and($value, 'alert_test_id');
                    break;
                case 'device':
                case 'device_id':
                    $where .= generate_query_values_and($value, 'device_id');
                    break;
                case 'entity_id':
                    $where .= generate_query_values_and($value, 'entity_id');
                    break;
                case 'entity_type':
                    $where .= generate_query_values_and($value, 'entity_type');
                    break;
                case 'message':
                    $where .= generate_query_values_and($value, 'message', '%LIKE%');
                    break;
                case 'timestamp_from':
                    $where   .= ' AND `timestamp` >= ?';
                    $param[] = $value;
                    break;
                case 'timestamp_to':
                    $where   .= ' AND `timestamp` <= ?';
                    $param[] = $value;
                    break;
            }
        }
    }

    // Show events only for permitted devices
    $query_permitted = generate_query_permitted(); //generate_query_permitted(array('entity'));

    $query         = 'FROM `alert_log` ';
    $query         .= $where . $query_permitted;
    $query_count   = 'SELECT COUNT(`event_id`) ' . $query;
    $query_updated = 'SELECT MAX(`timestamp`) ' . $query;

    $query = 'SELECT * ' . $query;
    $query .= ' ORDER BY `event_id` DESC ';
    $query .= "LIMIT $start,$pagesize";

    // Query events
    $array['entries'] = dbFetchRows($query, $param);

    // Query events count
    if ($array['pagination'] && !$array['short']) {
        $array['count']           = dbFetchCell($query_count, $param);
        $array['pagination_html'] = pagination($vars, $array['count']);
    } else {
        $array['count'] = safe_count($array['entries']);
    }

    // Query for last timestamp
    $array['updated'] = dbFetchCell($query_updated, $param);

    return $array;
}

function generate_alert_log_form_values($form_filter = FALSE, $column = NULL)
{
    //global $cache;

    $form_items = [];
    $filter     = is_array($form_filter); // Use filer or not

    switch ($column) {
        case 'alert_test_id':
            foreach ($GLOBALS['alert_rules'] as $alert_test_id => $entry) {
                if ($filter && !in_array($alert_test_id, $form_filter)) {
                    continue;
                } // Skip filtered entries

                $form_items[$alert_test_id] = $entry['alert_name'];
            }
            natcasesort($form_items);
            break;
        case 'log_type':
            foreach (['OK', 'FAIL', 'FAIL_DELAYED', 'FAIL_SUPPRESSED',
                      'ALERT_NOTIFY', 'RECOVER_NOTIFY', 'RECOVER_SUPPRESSED'] as $entry) {
                if ($filter && !in_array($entry, $form_filter)) {
                    continue;
                } // Skip filtered entries

                // Set colours and classes based on the status of the alert
                if (str_contains($entry, 'OK')) {
                    $class = "success";
                } elseif (str_contains($entry, 'SUPPRESSED')) {
                    $class = "suppressed";
                } elseif (str_contains($entry, 'DELAYED')) {
                    $class = "warning";
                } elseif (str_contains_array($entry, ['ALERT', 'FAIL'])) {
                    $class = "danger";
                } elseif (str_contains($entry, 'RECOVER')) {
                    $class = "info";
                } else {
                    $class = "disabled";
                }

                $form_items[$entry] = ['name' => $entry, 'class' => 'bg-' . $class];
            }
            break;
    }
    return $form_items;
}

// EOF
