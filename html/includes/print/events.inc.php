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
 * Display pages with device/port/system events on some formats.
 * Examples:
 * print_events() - display last 10 events from all devices
 * print_events(array('pagesize' => 99)) - display last 99 events from all device
 * print_events(array('pagesize' => 10, 'pageno' => 3, 'pagination' => TRUE)) - display 10 events from page 3 with pagination header
 * print_events(array('pagesize' => 10, 'device' = 4)) - display last 10 events for device_id 4
 * print_events(array('short' => TRUE)) - show small block with last events
 *
 * @param array $vars
 *
 * @return null
 *
 */
function print_events($vars)
{
    global $config;

    // Get events array
    $events = get_events_array($vars);

    if (!$events['count']) {
        // There have been no entries returned. Print the warning.
        print_warning('<h4>No eventlog entries found!</h4>');
    } else {
        // Entries have been returned. Print the table.
        $list = ['device' => FALSE, 'port' => FALSE];
        if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] === 'eventlog') {
            $list['device'] = TRUE;
        }
        if ($events['short'] || !isset($vars['port']) || empty($vars['port'])) {
            $list['entity'] = TRUE;
        }

        $string = generate_box_open($vars['header']);

        $string .= '<table class="' . OBS_CLASS_TABLE_STRIPED_MORE . '">' . PHP_EOL;
        if (!$events['short']) {
            $string .= '  <thead>' . PHP_EOL;
            $string .= '    <tr>' . PHP_EOL;
            $string .= '      <th class="state-marker"></th>' . PHP_EOL;
            $string .= '      <th>Date</th>' . PHP_EOL;
            if ($list['device']) {
                $string .= '      <th>Device</th>' . PHP_EOL;
            }
            if ($list['entity']) {
                $string .= '      <th>Entity</th>' . PHP_EOL;
            }
            $string .= '      <th>Message</th>' . PHP_EOL;
            $string .= '    </tr>' . PHP_EOL;
            $string .= '  </thead>' . PHP_EOL;
        }
        $string .= '  <tbody>' . PHP_EOL;

        foreach ($events['entries'] as $entry) {
            switch ($entry['severity']) {
                case "0": // Emergency
                case "1": // Alert
                case "2": // Critical
                case "3": // Error
                    $entry['html_row_class'] = "error";
                    break;
                case "4": // Warning
                    $entry['html_row_class'] = "warning";
                    break;
                case "5": // Notification
                    $entry['html_row_class'] = "recovery";
                    break;
                case "6": // Informational
                    $entry['html_row_class'] = "up";
                    break;
                case "7": // Debugging
                    $entry['html_row_class'] = "suppressed";
                    break;
                default:
            }

            $string .= '  <tr class="' . $entry['html_row_class'] . '">' . PHP_EOL;
            $string .= '<td class="state-marker"></td>' . PHP_EOL;

            if ($events['short']) {
                $string .= '    <td class="syslog text-nowrap">';
                $string .= generate_tooltip_time($entry['timestamp']) . '</td>' . PHP_EOL;
            } else {
                $string .= '    <td style="width: 160px">';
                $string .= format_timestamp($entry['timestamp']) . '</td>' . PHP_EOL;
            }

            if ($entry['device_id'] == 0 && safe_empty($entry['entity_type'])) {
                // Compatibility for global events
                $entry['entity_type'] = 'global';
            }

            if ($list['device']) {
                if (in_array($entry['entity_type'], ['global', 'info'])) {
                    // Global, Info events
                    $string .= '    <td class="entity"><span class="label">' . strtoupper($entry['entity_type']) . '</span></td>' . PHP_EOL;
                } else {
                    $dev         = device_by_id_cache($entry['device_id']);
                    $device_vars = ['page'    => 'device',
                                    'device'  => $entry['device_id'],
                                    'tab'     => 'logs',
                                    'section' => 'eventlog'];
                    $string      .= '    <td class="entity">' . generate_device_link_short($dev, $device_vars) . '</td>' . PHP_EOL;
                }
            }
            if ($list['entity']) {
                if ($entry['entity_type'] === 'device' && !$entry['entity_id']) {
                    $entry['entity_id'] = $entry['device_id'];
                }
                if ($entry['entity_type'] === 'port') {
                    $this_if       = get_port_by_id_cache($entry['entity_id']);
                    $entry['link'] = '<span class="entity">' . get_icon('port') . ' ' . generate_port_link_short($this_if) . '</span>';
                } elseif ($entry['entity_type'] === 'global') {
                    $entry['link'] = get_icon('info');
                } elseif ($entry['entity_type'] === 'info') {
                    $entry['link'] = get_icon('important');
                } else {
                    if (!empty($config['entities'][$entry['entity_type']]['icon'])) {
                        $entry['link'] = '<i class="' . $config['entities'][$entry['entity_type']]['icon'] . '"></i> <span class="entity">' . generate_entity_link($entry['entity_type'], $entry['entity_id']) . '</span>';
                    } else {
                        $entry['link'] = nicecase($entry['entity_type']);
                    }

                }
                if (!$events['short']) {
                    $string .= '    <td style="white-space: nowrap">' . $entry['link'] . '</td>' . PHP_EOL;
                }
            }
            if ($events['short']) {
                $string .= '    <td class="syslog">';
                if (strpos($entry['message'], $entry['link']) !== 0) {
                    $string .= $entry['link'] . ' ';
                }
            } else {
                $string .= '    <td>';
            }
            // Use markdown parsed for Debug events, for allow links to docs (use Markdown Extra).
            $message_escape = $entry['severity'] >= 7 ? get_markdown($entry['message'], TRUE, TRUE) : escape_html($entry['message']);
            $string         .= $message_escape . '</td>' . PHP_EOL;
            //$string .= $entry['message'] . '</td>' . PHP_EOL;
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
 * print_events(array('short' => TRUE))
 *
 * @param array $vars
 *
 * @return none
 *
 */
function print_events_short($var)
{
    $var['short'] = TRUE;
    print_events($var);
}

/**
 * Params:
 * short
 * pagination, pageno, pagesize
 * device_id, entity_id, entity_type, message, timestamp_from, timestamp_to
 */
function get_events_array($vars)
{
    $array = [];

    // Short events? (no pagination, small out)
    $array['short'] = (isset($vars['short']) && $vars['short']);
    // With pagination? (display page numbers in header)
    $array['pagination'] = (isset($vars['pagination']) && $vars['pagination']);
    pagination($vars, 0, TRUE); // Get default pagesize/pageno
    $array['pageno']   = $vars['pageno'];
    $array['pagesize'] = $vars['pagesize'];
    $start             = $array['pagesize'] * $array['pageno'] - $array['pagesize'];
    $pagesize          = $array['pagesize'];

    // Begin query generate
    $param = [];
    $where = ' WHERE 1 ';
    foreach ($vars as $var => $value) {
        if ($value != '') {
            switch ($var) {
                case 'device':
                case 'device_id':
                    $where .= generate_query_values_and($value, 'device_id');
                    break;
                case 'port':
                case 'entity':
                case 'entity_id':
                    $where .= generate_query_values_and($value, 'entity_id');
                    break;
                case 'severity':
                    $where .= generate_query_values_and($value, 'severity');
                    break;
                case 'type':
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
                case "group":
                case "group_id":
                    $values = get_group_entities($value);
                    $where  .= generate_query_values_and($values, 'entity_id');
                    $where  .= generate_query_values_and(get_group_entity_type($value), 'entity_type');
                    break;
            }
        }
    }

    // Show events only for permitted devices
    $query_permitted = generate_query_permitted();

    $query         = 'FROM `eventlog` ';
    $query         .= $where . $query_permitted;
    $query_count   = 'SELECT COUNT(*) ' . $query;
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
    //$array['updated'] = dbFetchCell($query_updated, $param);

    return $array;
}

function generate_eventlog_form_values($form_filter = FALSE, $column = NULL)
{
    //global $cache;
    global $config;

    $form_items = [];
    $filter     = is_array($form_filter); // Use filer or not

    switch ($column) {
        case 'severities':
        case 'severity':
            // These complete same as syslog priorities
            return generate_form_values('syslog', $form_filter, 'priorities');
            break;
        case 'entity_type':
        case 'type':
        case 'types':
            // Use filter as items
            if ($filter) {
                if (in_array('device', $form_filter)) {
                    // Device always first
                    $form_filter = array_unique(array_merge(['device'], $form_filter));
                }
                if (in_array('global', $form_filter)) {
                    // Global always first
                    $form_filter = array_unique(array_merge(['global'], $form_filter));
                }
            }
            foreach ($form_filter as $type) {
                if (strlen($type) == 0) {
                    continue;
                }
                $form_items[$type]['name'] = nicecase($type);

                if ($type == 'global') {
                    $form_items[$type]['icon'] = $config['icon']['info'];
                } elseif (!isset($config['entities'][$type]['icon'])) {
                    $form_items[$type]['icon'] = $config['entity_default']['icon'];
                } else {
                    $form_items[$type]['icon'] = $config['entities'][$type]['icon'];
                }
            }
            break;
    }
    return $form_items;
}

// EOF
