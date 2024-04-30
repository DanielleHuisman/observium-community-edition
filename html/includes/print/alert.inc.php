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

/**
 * Build alert table query from $vars
 * Returns queries for data, an array of parameters and a query to get a count for use in paging
 *
 * @param array $vars
 *
 * @return array ($query, $param, $query_count)
 *
 */
// TESTME needs unit testing
function build_alert_table_query($vars) {

    // Loop through the vars building a sql query from relevant values
    $where_array = [];
    foreach ($vars as $var => $value) {
        if (!safe_empty($value)) {
            switch ($var) {
                // Search by device_id if we have a device or device_id
                case 'device_id':
                    $where_array[] = generate_query_values($value, 'device_id');
                    break;

                case 'entity_type':
                    if ($value !== 'all') {
                        $where_array[] = generate_query_values($value, 'entity_type');
                    }
                    break;

                case 'entity_id':
                    $where_array[] = generate_query_values($value, 'entity_id');
                    break;

                case 'alert_test_id':
                    $where_array[] = generate_query_values($value, 'alert_test_id');
                    break;
                case 'status':
                    $values = [];
                    foreach ((array)$value as $status) {
                        if ($status === 'failed') {
                            $values[] = 0;
                        } elseif ($status === 'ok') {
                            $values[] = 1;
                        } elseif ($status === 'delayed') {
                            $values[] = 2;
                        } elseif ($status === 'suppressed') {
                            $values[] = 3;
                        } elseif ($status === 'all') {
                            break 2;
                        }
                    }
                    $where_array[] = generate_query_values($values, 'alert_status');
                    break;
            }
        }
    }

    // Permissions query
    $query_permitted = generate_query_permitted_ng([ 'device', 'alert' ], [ 'hide_ignored' => TRUE ]);

    // Base query
    $query = 'FROM `alert_table` ' . generate_where_clause($where_array, $query_permitted);

    // Build the query to get a count of entries
    $query_count = 'SELECT COUNT(`alert_table_id`) ' . $query;

    // Build the query to get the list of entries
    $query = 'SELECT * ' . $query;

    //$sort_order = get_sort_order($vars);
    switch ($vars['sort']) {
        case 'device':
            // fix this to sort by hostname
            //$query .= generate_query_sort('hostname', get_sort_order($vars));
            $query .= generate_query_sort('device_id', get_sort_order($vars));
            break;

        case 'last_changed':
        case 'changed':
            $query .= generate_query_sort('last_changed', 'DESC');
            break;

        default:
            // default sort order
            $query .= generate_query_sort([ 'device_id', 'alert_test_id', 'entity_type', 'entity_id' ], 'DESC');
    }

    if (isset($vars['pagination']) && $vars['pagination']) {
        pagination($vars, 0, TRUE); // Get default pagesize/pageno
        $query .= generate_query_limit($vars);
    }

    return [ $query, [], $query_count ];
}

/**
 * Display alert_table entries.
 *
 * @param array $vars
 *
 * @return void
 *
 */
function print_alert_table($vars)
{
    global $alert_rules, $config;

    // We use this here.
    register_html_resource('js', 'bootstrap-confirmation.js');

    // This should be set outside, but do it here if it isn't
    if (!is_array($alert_rules)) {
        $alert_rules = cache_alert_rules();
    }
    /// WARN HERE

    if (isset($vars['device']) && !isset($vars['device_id'])) {
        $vars['device_id'] = $vars['device'];
    }
    if (isset($vars['entity']) && !isset($vars['entity_id'])) {
        $vars['entity_id'] = $vars['entity'];
    }

    // Short? (no pagination, small out)
    $short = isset($vars['short']) && $vars['short'];

    [ $query, $param, $query_count ] = build_alert_table_query($vars);

    // Fetch alerts
    //$count  = dbFetchCell($query_count, $param);
    $alerts = dbFetchRows($query, $param);

    // Set which columns we're going to show.
    // We hide the columns that have been given as search options via $vars
    $list = ['device_id' => FALSE, 'entity_id' => FALSE, 'entity_type' => FALSE, 'alert_test_id' => FALSE];
    foreach ($list as $argument => $nope) {
        if (!isset($vars[$argument]) || empty($vars[$argument]) || $vars[$argument] === "all") {
            $list[$argument] = TRUE;
        }
    }

    if ($vars['format'] !== "condensed") {
        $list['checked'] = TRUE;
        $list['changed'] = TRUE;
        $list['alerted'] = TRUE;
    }

    if ($vars['short']) {
        $list['checked'] = FALSE;
        $list['alerted'] = FALSE;
    }

    // Hide device if we know entity_id
    if (isset($vars['entity_id'])) {
        $list['device_id'] = FALSE;
    }
    // Hide entity_type if we know the alert_test_id
    if (isset($vars['alert_test_id']) || TRUE) {
        $list['entity_type'] = FALSE;
    } // Hide entity types in favour of icons to save space

    if ($vars['pagination'] && !$short) {
        $count  = dbFetchCell($query_count, $param);
        $pagination_html = pagination($vars, $count);
        echo $pagination_html;
    }

    echo generate_box_open($vars['header']);

    echo '<table class="table table-condensed  table-striped  table-hover">';

    if (!get_var_true($vars['no_header'])) {
        echo '
  <thead>
    <tr>
      <th class="state-marker"></th>
      <th style="width: 1px;"></th>';

        if ($list['device_id']) {
            echo('      <th style="width: 15%">Device</th>');
        }
        if ($list['entity_type']) {
            echo('      <th style="width: 10%">Type</th>');
        }
        if ($list['entity_id']) {
            echo('      <th style="">Entity</th>');
        }
        if ($list['alert_test_id']) {
            echo('      <th style="min-width: 15%;">Alert</th>');
        }

        echo '
      <th style="width: 100px;">Status</th>';

        if ($list['checked']) {
            echo '      <th style="width: 95px;">Checked</th>';
        }
        if ($list['changed']) {
            echo '      <th style="width: 95px;">Changed</th>';
        }
        if ($list['alerted']) {
            echo '      <th style="width: 95px;">Alerted</th>';
        }

        echo '    <th style="width: 70px;"></th>
    </tr>
  </thead>';
    }
    echo '<tbody>' . PHP_EOL;

    foreach ($alerts as $alert) {
        // Set the alert_rule from the prebuilt cache array
        $alert_rule = $alert_rules[$alert['alert_test_id']];
        //r($alert_rule);

        $alert['severity'] = $alert_rule['severity'];

        // Process the alert entry, generating colours and classes from the data
        humanize_alert_entry($alert);

        // Get the entity array using the cache
        $entity = get_entity_by_id_cache($alert['entity_type'], $alert['entity_id']);

        $entity_type = entity_type_translate_array($alert['entity_type']);

        // Get the device array using the cache
        $device = device_by_id_cache($alert['device_id']);

        // If our parent is an actual type, we need to use the type
        /* -- Currently unused.
        if(isset($entity_type['parent_type']))
        {
          $parent_entity_type = entity_type_translate_array($entity_type['parent_type']);
          $parent_entity = get_entity_by_id_cache($entity_type['parent_type'], $entity[$entity_type['parent_id_field']]);
        }
        */

        echo('<tr class="' . $alert['html_row_class'] . '" style="cursor: pointer;" onclick="openLink(\'' . generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'alert', 'alert_entry' => $alert['alert_table_id']]) . '\')">');
        echo('<td class="state-marker"></td>');
        echo('<td style="width: 1px;"></td>');

        // If we know the device, don't show the device
        if ($list['device_id']) {
            echo('<td><span class="entity-title">' . generate_device_link_short($device) . '</span></td>');
        }

        // If we're showing all entity types, print the entity type here
        if ($list['entity_type']) {
            echo('<td>' . nicecase($alert['entity_type']) . '</td>');
        }

        // Print a link to the entity
        if ($list['entity_id']) {
            echo '<td><span class="entity-title">';

            // If we have a parent type, display it here.
            // FIXME - this is perhaps messy. Find a better way and a better layout. We can't have a new table column because it would be empty 90% of the time!
            if (isset($entity_type['parent_type'])) {
                echo '  <i class="' . $config['entities'][$entity_type['parent_type']]['icon'] . '"></i> ' . generate_entity_link($entity_type['parent_type'], $entity[$entity_type['parent_id_field']]) . '</span> - ';
            }
            echo '  <i class="' . $config['entities'][$alert['entity_type']]['icon'] . '"></i> ' . generate_entity_link($alert['entity_type'], $alert['entity_id'], NULL, NULL, TRUE, $short) . '</span>';
            echo '</td>';
        }

        // Print link to the alert rule page
        if ($list['alert_test_id']) {
            echo '<td class="entity"><a href="', generate_url(['page' => 'alert_check', 'alert_test_id' => $alert_rule['alert_test_id']]), '">', escape_html($alert_rule['alert_name']), '</a></td>';
        }

        echo('<td>');
        echo('<span class="label label-' . ($alert['html_row_class'] !== 'up' ? $alert['html_row_class'] : 'success') . '">' . generate_tooltip_link('', $alert['status'], '<div class="small" style="max-width: 500px;"><strong>' . $alert['last_message'] . '</strong></div>', $alert['alert_class']) . '</span>');
        echo('</td>');


        // echo('<td class="'.$alert['class'].'">'.$alert['last_message'].'</td>');

        if ($list['checked']) {
            echo('<td>' . generate_tooltip_link('', $alert['checked'], format_unixtime($alert['last_checked'], 'r')) . '</td>');
        }
        if ($list['changed']) {
            echo('<td>' . generate_tooltip_link('', $alert['changed'], format_unixtime($alert['last_changed'], 'r')) . '</td>');
        }
        if ($list['alerted']) {
            echo('<td>' . generate_tooltip_link('', $alert['alerted'], format_unixtime($alert['last_alerted'], 'r')) . '</td>');
        }
        echo('<td>');

        // This stuff should go in an external entity popup in the future.

        $state = safe_json_decode($alert['state']);

        $alert['state_popup'] = '';

        if (safe_count($state['failed'])) {
            $alert['state_popup'] .= generate_box_open(['title' => 'Failed Tests']); //'<h4>Failed Tests</h4>';

            $alert['state_popup'] .= '<table style="min-width: 400px;" class="table   table-striped table-condensed">';
            $alert['state_popup'] .= '<thead><tr><th>Metric</th><th>Cond</th><th>Value</th><th>Measured</th></tr></thead>';

            foreach ($state['failed'] as $test) {
                $metric_def = $config['entities'][$alert['entity_type']]['metrics'][$test['metric']];

                $format = NULL;
                $symbol = '';
                if (!safe_empty($test['value'])) {
                    if (isset($metric_def['format'])) {
                        $format = isset($entity[$metric_def['format']]) ? $entity[$metric_def['format']] : $metric_def['format'];
                    }
                    if (isset($metric_def['symbol'])) {
                        $symbol = isset($entity[$metric_def['symbol']]) ? $entity[$metric_def['symbol']] : $metric_def['symbol'];
                    }
                }

                $alert['state_popup'] .= '<tr><td><strong>' . $test['metric'] . '</strong></td><td>' . $test['condition'] . '</td><td>' .
                                         format_value($test['value'], $format) . $symbol . '</td><td><i class="red">' .
                                         format_value($state['metrics'][$test['metric']], $format) . $symbol . '</i></td></tr>';
            }
            $alert['state_popup'] .= '</table>';
            $alert['state_popup'] .= generate_box_close();
        }

        $alert['state_popup'] .= generate_entity_popup_graphs($alert, ['entity_type' => 'alert_entry']);

        // Print (i) icon with popup of state.
        echo(overlib_link('', get_icon('info-sign', 'text-primary'), $alert['state_popup'], NULL));

        echo('&nbsp;&nbsp;<a href="' . generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'alert', 'alert_entry' => $alert['alert_table_id']]) . '">' .
             get_icon('edit', 'text-muted') . '</a>');
        //echo '&nbsp;&nbsp;<a onclick="alert_ignore_until_ok('.$alert['alert_table_id'].')"><i class="icon-pause text-warning"></i></a>';

        echo '&nbsp;&nbsp;';

        $form = [
          'type'  => 'simple',
          //'userlevel'  => 10,          // Minimum user level for display form
          'id'    => 'alert_entry_ignore_until_ok_' . $alert['alert_table_id'],
          'style' => 'display:inline;',
        ];

        $form['row'][0]['form_alert_table_id'] = [
          'type'  => 'hidden',
          'value' => $alert['alert_table_id']
        ];

        $form['row'][99]['form_alert_table_action'] = [
          'type'      => 'submit',
          'icon_only' => TRUE, // hide button styles
          'name'      => '',
          'readonly'  => get_var_true($alert['alert_status'], '3'), // alert_status == 3 mean suppressed
          'icon'      => get_var_true($alert['alert_status'], '3') ? 'icon-ok-circle text-muted' : 'icon-ok-sign text-muted',
          // confirmation dialog
          'attribs'   => [
            'data-toggle'            => 'confirm', // Enable confirmation dialog
            'data-confirm-placement' => 'left',
            'data-confirm-content'   => 'Ignore until ok?',
            //'data-confirm-content' => '<div class="alert alert-warning"><h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>
            //                           This association will be deleted!</div>'),
          ],
          'value'     => 'alert_entry_ignore_until_ok'
        ];

        // Only show ignore-until button if userlevel is above 8
        if ($_SESSION['userlevel'] >= 8) {
            //print_form($form);
            unset($form);

            echo '<i class="icon-ok-sign text-muted" data-toggle="confirmation" data-placement="left" data-title="Ignore until OK?" onclick="confirmAction(\'alert_entry_ignore_until_ok\', this, event)" data-value="'.$alert['alert_table_id'].'"></i>';

        }

        echo('</td>');
        echo('</tr>');

    }

    echo '  </tbody>' . PHP_EOL;
    echo '</table>' . PHP_EOL;

    echo generate_box_close();

    if ($vars['pagination'] && !$short) {
        echo $pagination_html;
    }
}

function generate_alert_metrics_table($entity_type, &$metrics_list = []) {
    global $config;

    $metrics_list = [];
    foreach ($config['entities'][$entity_type]['metrics'] as $metric => $entry) {
        $metric_list           = [
            'metric'      => $metric,
            'description' => $entry['label'],
        ];
        $metric_list['values'] = '';
        if (is_array($entry['values'])) {
            if (is_array_list($entry['values'])) {
                $values = $entry['values'];
            } else {
                $values = [];
                foreach ($entry['values'] as $value => $descr) {
                    $values[] = "$value ($descr)";
                }
            }
            $metric_list['values'] = '<span class="label">' . implode('</span>  <span class="label">', $values) . '</span>';
        } elseif ($entry['type'] === 'integer') {
            $metric_list['values'] = escape_html('<numeric>');
            if (str_contains($metric, 'value')) {
                $metric_list['values'] .= '<br />';
                // some table fields
                foreach (['limit_high', 'limit_high_warn', 'limit_low', 'limit_low_warn'] as $field) {
                    if (isset($config['entities'][$entity_type]['table_fields'][$field])) {
                        $metric_list['values'] .= '<span class="label">@' . $config['entities'][$entity_type]['table_fields'][$field] . '</span>  ';
                    }
                }
            }
        } else {
            $metric_list['values'] = escape_html('<' . $entry['type'] . '>');
        }
        $metrics_list[] = $metric_list;
        //$metrics_list[] = '<span class="label">'.$metric.'</span>&nbsp;-&nbsp;'.$entry['label'];
    }

    // Common:
    $metrics_list[] = [
        'metric'      => '',
        'description' => '<Any metric>',
        'values'      => '<span class="label">@previous</span>'
    ];
    $metrics_list[] = [
        'metric'      => 'time',
        'description' => 'Time',
        'values'      => 'Format <code>HHdd</code> like <strong>1630</strong>'
    ];
    $metrics_list[] = [
        'metric'      => 'weekday',
        'description' => 'Weekday',
        'values'      => 'Day of the week as a number from Monday as <strong>1</strong> to Sunday as <strong>7</strong>'
    ];

    $metrics_opts = [
        'columns'     => [
            ['Metrics', 'style="width: 5%;"'],
            'Description',
            'Values'
        ],
        'metric'      => ['class' => 'label'],
        'description' => ['class' => 'text-nowrap'],
        'values'      => ['escape' => FALSE]
    ];

    return '<div class="col-md-12" style="padding: 0;">' . PHP_EOL .
           generate_box_open([ 'title' => 'List of known metrics:', 'title-style' => 'font-size: 16px;', 'box-style' => 'margin: 10px 0 0;' ]) . PHP_EOL .
           build_table($metrics_list, $metrics_opts) . PHP_EOL .
           generate_box_close() . PHP_EOL .
           '</div>';
}

// EOF
