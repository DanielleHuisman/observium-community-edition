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
 * Display syslog messages.
 *
 * Display pages with device syslog messages.
 * Examples:
 * print_syslogs() - display last 10 syslog messages from all devices
 * print_syslogs(array('pagesize' => 99)) - display last 99 syslog messages from all device
 * print_syslogs(array('pagesize' => 10, 'pageno' => 3, 'pagination' => TRUE)) - display 10 syslog messages from page 3 with pagination header
 * print_syslogs(array('pagesize' => 10, 'device' = 4)) - display last 10 syslog messages for device_id 4
 * print_syslogs(array('short' => TRUE)) - show small block with last syslog messages
 *
 * @param array $vars
 *
 * @return null
 *
 */
function print_syslogs($vars)
{
    // Short events? (no pagination, small out)
    $short = (isset($vars['short']) && $vars['short']);
    // With pagination? (display page numbers in header)
    $pagination = (isset($vars['pagination']) && $vars['pagination']);
    pagination($vars, 0, TRUE); // Get default pagesize/pageno
    $pageno   = $vars['pageno'];
    $pagesize = $vars['pagesize'];
    $start    = $pagesize * $pageno - $pagesize;

    $device_single = FALSE; // Show syslog entries for single device or multiple (use approximate counts for multiple)

    $param = [];
    $where = ' WHERE 1 ';
    foreach ($vars as $var => $value) {
        if (!safe_empty($value)) {
            switch ($var) {
                case 'device':
                case 'device_id':
                    $device_single = is_numeric($value);
                    $where         .= generate_query_values_and($value, 'device_id');
                    break;
                case 'priority':
                    $value = get_var_csv($value);
                    foreach ($value as $k => $v) {
                        // Rewrite priority strings to numbers
                        $value[$k] = priority_string_to_numeric($v);
                    }
                    $where .= generate_query_values_and($value, $var);
                    break;
                case 'tag':
                case 'program':
                    $condition = str_contains($value, '*') ? 'LIKE' : '=';
                    $value     = get_var_csv($value);
                    $where     .= generate_query_values_and($value, $var, $condition);
                    break;
                case 'message':
                    //FIXME: this should just be a function used for all "text" searchable fields
                    if (preg_match(OBS_PATTERN_REGEXP, $value, $matches)) {
                        // Match regular expression
                        $where .= generate_query_values_and($matches['pattern'], 'msg', 'REGEXP');
                    } elseif (preg_match('/^!(=)?\s*(?<msg>.+)/', $value, $matches)) {
                        $where .= generate_query_values_and($matches['msg'], 'msg', '%!=LIKE%');
                    } else {
                        $where .= generate_query_values_and($value, 'msg', '%LIKE%');
                    }
                    break;
                case 'timestamp_from':
                    $where   .= ' AND `timestamp` > ?';
                    $param[] = $value;
                    break;
                case 'timestamp_to':
                    $where   .= ' AND `timestamp` < ?';
                    $param[] = $value;
                    break;
            }
        }
    }

    // Show entries only for permitted devices
    $query_permitted = generate_query_permitted();
    /*
    // Convert NOT IN to IN for correctly use indexes
    $devices_permitted = dbFetchColumn('SELECT DISTINCT `device_id` FROM `syslog` WHERE 1 '.$query_permitted, NULL, TRUE);
    $query_permitted = generate_query_values_and($devices_permitted, 'device_id');
    //r($devices_permitted);
    */

    $query              = 'FROM `syslog` ';
    $query              .= $where . $query_permitted;
    $query_count        = 'SELECT COUNT(*) ' . $query;
    $query_count_approx = 'EXPLAIN SELECT * ' . $query; // Fast approximate count

    $query = 'SELECT * ' . $query;
    $query .= ' ORDER BY `seq` DESC ';
    $query .= "LIMIT $start,$pagesize";

    // Query syslog messages
    $entries = dbFetchRows($query, $param);
    // Query syslog count
    if ($pagination && !$short) {
        dbSetVariable('MAX_EXECUTION_TIME', 500); // Set 0.5 sec maximum query execution time
        // Exactly count, but it's very SLOW on huge tables
        $count = dbFetchCell($query_count, $param);
        dbSetVariable('MAX_EXECUTION_TIME', 0); // Reset maximum query execution time
        //r($count);
        if (!is_numeric($count)) {
            // Approximate count correctly around 100-80%
            dbQuery('ANALYZE TABLE `syslog`;'); // Update INFORMATION_SCHEMA for more correctly count
            $tmp            = dbFetchRow($query_count_approx, $param);
            $count          = $tmp['rows'];
            $count_estimate = TRUE;
        }
    } else {
        $count = safe_count($entries);
    }

    if (!$count) {
        // There have been no entries returned. Print the warning.

        print_warning('<h4>No syslog entries found!</h4>
Check that the syslog daemon and Observium configuration options are set correctly, that your devices are configured to send syslog to Observium and that there are no firewalls blocking the messages.

See <a href="' . OBSERVIUM_DOCS_URL . '/syslog/" target="_blank">Syslog Integration</a> guide and <a href="' . OBSERVIUM_DOCS_URL . '/config_options/#syslog-settings" target="_blank">configuration options</a> for more information.');

    } else {
        // Entries have been returned. Print the table.

        $list = ['device' => FALSE, 'priority' => TRUE]; // For now (temporarily) priority always displayed
        if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] === 'syslog') {
            $list['device'] = TRUE;
        }
        if ($short || !isset($vars['priority']) || empty($vars['priority'])) {
            $list['priority'] = TRUE;
        }


        $string = generate_box_open($vars['header']);
        if ((isset($vars['short']) && $vars['short'])) {
            $string .= '<table class="' . OBS_CLASS_TABLE_STRIPED_MORE . '">' . PHP_EOL;
        } else {
            $string .= '<table class="' . OBS_CLASS_TABLE_STRIPED . '">' . PHP_EOL;

        }
        // Generate table header
        if (!$short) {
            $cols   = [];
            $cols[] = [NULL, 'class="state-marker"'];
            //$cols[]              = [ NULL, 'class="no-width"' ]; // Measured entity link
            $cols[] = ['Date'];
            if ($list['device']) {
                //$cols['device']    = [ 'Device' ];
                $cols[] = ['Device'];
            }
            if ($list['priority']) {
                //$cols['priority']  = [ 'Priority' ];
                $cols[] = ['Priority'];
            }
            $cols[] = ['[Program] [Tags] Message'];

            $string .= get_table_header($cols, $vars);
        }

        // Table body
        $string .= '  <tbody>' . PHP_EOL;
        foreach ($entries as $entry) {
            $string .= generate_syslog_row($entry, $vars, $list);
        }
        //print_vars($GLOBALS['cache']['syslog']);
        $string .= '  </tbody>' . PHP_EOL;

        $string .= '</table>' . PHP_EOL;

        $string .= generate_box_close();

        // Print pagination header
        if ($pagination && !$short) {
            $string = pagination($vars, $count) . $string . pagination($vars, $count);
        }

        if (isset($count_estimate) && $count_estimate == TRUE) {
            print_message("The syslog entry counts shown below are an estimate due to SQL query performance limitations. There may be many fewer results than indicated.", "info");
        }

        // Print syslog
        echo $string;
    }
}

function generate_syslog_row($entry, $vars, $list = NULL)
{
    // Short events? (no pagination, small out)
    $short      = (isset($vars['short']) && $vars['short']);
    $priorities = $GLOBALS['config']['syslog']['priorities'];
    $is_alert   = isset($entry['la_id']); // This is syslog alert entry?

    // List of displayed columns
    if (is_null($list)) {
        $list = ['device' => FALSE, 'priority' => TRUE]; // For now (temporarily) priority always displayed
        if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] == 'syslog') {
            $list['device'] = TRUE;
        }
        if ($short || !isset($vars['priority']) || empty($vars['priority'])) {
            $list['priority'] = TRUE;
        }
    }

    $row_class = !safe_empty($entry['html_row_class']) ? $entry['html_row_class'] : $priorities[$entry['priority']]['row-class'];

    $string   = '  <tr class="' . $row_class . '">' . PHP_EOL;
    $string   .= '<td class="state-marker"></td>' . PHP_EOL;
    $timediff = get_time() - strtotime($entry['timestamp']);

    if ($short || $timediff < 3600) {
        $string .= '    <td class="syslog text-nowrap">';
        $string .= generate_tooltip_time($entry['timestamp']) . '</td>' . PHP_EOL;
    } else {
        //$string .= '    <td style="width: 130px">';
        $string .= '    <td>';
        $string .= format_timestamp($entry['timestamp']) . '</td>' . PHP_EOL;
    }

    // Device column
    if ($list['device']) {
        $dev         = device_by_id_cache($entry['device_id']);
        $device_vars = ['page'    => 'device',
                        'device'  => $entry['device_id'],
                        'tab'     => 'logs',
                        'section' => 'syslog'];
        if ($is_alert) {
            $device_vars['section'] = 'logalert';
        }
        $string .= '    <td class="entity">' . generate_device_link_short($dev, $device_vars) . '</td>' . PHP_EOL;
    }

    // Alert Rule column (in syslog alerts)
    if ($list['la_id']) {
        $syslog_rules = $GLOBALS['cache']['syslog']['syslog_rules']; // Cached syslog rules
        $string       .= '<td><strong><a href="' . generate_url(['page' => 'syslog_rules', 'la_id' => $entry['la_id']]) . '">' .
                         (is_array($syslog_rules[$entry['la_id']]) ? $syslog_rules[$entry['la_id']]['la_name'] : 'Rule Deleted') . '</td>' . PHP_EOL;
    }

    // Priority column
    if ($list['priority']) {
        if (!$short) {
            $string .= '    <td style="width: 95px"><span class="label label-' . $priorities[$entry['priority']]['label-class'] . '">' .
                       nicecase($priorities[$entry['priority']]['name']) . ' (' . $entry['priority'] . ')</span></td>' . PHP_EOL;
        }
    }

    // Program and Tags column
    $entry['program'] = (empty($entry['program'])) ? '[[EMPTY]]' : $entry['program'];
    $program_class    = get_type_class($entry['program'], 'program');
    if ($short) {
        $string .= '    <td class="syslog">';
        $string .= '<span class="label label-' . $program_class . '"><strong>' . escape_html($entry['program']) . '</strong></span>';
    } else {
        $string .= '    <td>';
        $string .= '<span class="label label-' . $program_class . '">' . escape_html($entry['program']) . '</span>';

        /* Show tags if not short */
        $tags = [];
        foreach (explode(',', $entry['tag']) as $tag) {
            if (!str_istarts($tag, $entry['program']) &&
                !preg_match('/^(\d+\:|[\da-f]{2})$/i', $tag) &&
                !preg_match('/^<(Emer|Aler|Crit|Err|Warn|Noti|Info|Debu)/i', $tag)) // Skip tags same as program or old numeric tags or syslog-ng 2x hex numbers
            {
                $tags[] = escape_html($tag);
            }
        }
        if ($tags) {
            foreach ($tags as $tag) {
                $tag_class = get_type_class($tag, 'tag');
                $string    .= ' <span class="label label-' . $tag_class . '">' . $tag . '</span>';
            }
            //$string .= '<span class="label">';
            //$string .= implode('</span><span class="label">', $tags);
            //$string .= '</span>';
        }
        /* End tags */
    }
    if ($list['program']) {
        // Program in separate column (from message)
        $string .= $short ? '</td><td class="syslog">' : '</td><td>';
    }

    // Highlight port links
    ports_links_cache($entry);
    $entity_links = $GLOBALS['cache']['entity_links']['ports'][$entry['device_id']];

    // Highlight bgp peer links (try only when program match BGP)
    if ($entry['program'] === 'RPD' || // RPD is program on JunOS
        str_icontains_array($entry['program'], 'bgp') || str_icontains_array($entry['tag'], 'bgp')) {

        bgp_links_cache($entry);
        $entity_links = array_merge($entity_links, $GLOBALS['cache']['entity_links']['bgp'][$entry['device_id']]);
    }

    // Linkify entities in syslog messages
    if (isset($entry['msg']) && !isset($entry['message'])) {
        // Different field in syslog alerts and syslog
        $entry['message'] = $entry['msg'];
    }

    // Restore escaped quotes (for old entries)
    $entry['message'] = str_replace([ '\"', "\'" ], [ '"', "'" ], $entry['message']);

    $string .= ' ' . html_highlight(escape_html($entry['message']), $entity_links, NULL, TRUE) . '</td>' . PHP_EOL;

    // if (!$short)
    // {
    //   //$string .= '<td>' . escape_html($entry['log_type']) . '</td>' . PHP_EOL;
    //   //$string .= '<td style="text-align: right">'. ($entry['notified'] == '1' ? '<span class="label label-success">YES</span>' : ($entry['notified'] == '-1' ? '<span class="label">SKIP</span>' : '<span class="label label-warning">NO</span>')) . '</td>' . PHP_EOL;
    // }

    $string .= '  </tr>' . PHP_EOL;

    return $string;
}

function generate_syslog_form_values($form_filter = FALSE, $column = NULL)
{
    //global $cache;

    $form_items = [];
    $filter     = is_array($form_filter); // Use filer or not

    switch ($column) {
        case 'priorities':
        case 'priority':
            foreach ($GLOBALS['config']['syslog']['priorities'] as $p => $priority) {
                if ($filter && !in_array($p, $form_filter)) {
                    continue;
                } // Skip filtered entries
                if ($p > 7) {
                    continue;
                }

                $form_items[$p]         = $priority;
                $form_items[$p]['name'] = nicecase($priority['name']);

                switch ($p) {
                    case 0: // Emergency
                    case 1: // Alert
                    case 2: // Critical
                    case 3: // Error
                        $form_items[$p]['class'] = "bg-danger";
                        break;
                    case 4: // Warning
                        $form_items[$p]['class'] = "bg-warning";
                        break;
                    case 5: // Notification
                        $form_items[$p]['class'] = "bg-success";
                        break;
                    case 6: // Informational
                        $form_items[$p]['class'] = "bg-info";
                        break;
                    case 7: // Debugging
                        $form_items[$p]['class'] = "bg-suppressed";
                        break;
                    default:
                        $form_items[$p]['class'] = "bg-disabled";
                }
            }
            krsort($form_items);
            break;
        case 'programs':
        case 'program':
            // Use filter as items
            foreach ($form_filter as $program) {
                $name                 = ($program != '' ? $program : OBS_VAR_UNSET);
                $form_items[$program] = $name;
            }
            break;
    }
    return $form_items;
}

function print_syslog_rules_table($vars)
{

    if (isset($vars['la_id'])) {
        $las = dbFetchRows("SELECT * FROM `syslog_rules` WHERE `la_id` = ?", [ $vars['la_id'] ]);
    } else {

        if (isset($vars['sort'])) {
            $sort_order = get_sort_order($vars);
            switch ($vars['sort']) {
                case "descr":
                    $sort = generate_query_sort('la_descr', $sort_order);
                    break;

                case "rule":
                    $sort = generate_query_sort('la_rule', $sort_order);
                    break;

                case "severity":
                    $sort = generate_query_sort('la_severity', $sort_order);
                    break;

                case "status":
                    $sort = generate_query_sort('la_disable', $sort_order);
                    break;

                case 'name':
                default:
                    $sort = generate_query_sort('la_name', $sort_order);
            }
        } else {
            $sort = generate_query_sort('la_name');
        }
        //r($sort);

        $las = dbFetchRows("SELECT * FROM `syslog_rules`" . $sort);
    }

    if (!safe_empty($las)) {

        $modals = '';
        $string = generate_box_open();
        $string .= '<table class="table table-striped table-hover table-condensed">' . PHP_EOL;

        $cols = [
            'state-marker' => '',
            [ 'name'     => 'Name',        'style' => 'width: 160px;' ],
            [ 'descr'    => 'Description', 'style' => 'width: 400px;'],
            [ 'rule'     => 'Rule' ],
            [ 'severity' => 'Severity',    'style' => 'width: 60px;' ],
            [ 'status'   => 'Status',      'style' => 'width: 60px;' ],
            [ NULL,                        'style' => 'width: 70px;' ],
        ];

        $string .= generate_table_header($cols, $vars);

        foreach ($las as $la) {

            if ($la['disable'] == 0) {
                $la['html_row_class'] = "up";
            } else {
                $la['html_row_class'] = "disabled";
            }

            $string .= '<tr class="' . $la['html_row_class'] . '">';
            $string .= '<td class="state-marker"></td>';

            $string .= '    <td><strong><a href="' . generate_url(['page' => 'syslog_rules', 'la_id' => $la['la_id']]) . '">' . escape_html($la['la_name']) . '</a></strong></td>' . PHP_EOL;
            $string .= '    <td><a href="' . generate_url(['page' => 'syslog_rules', 'la_id' => $la['la_id']]) . '">' . escape_html($la['la_descr']) . '</a></td>' . PHP_EOL;
            $string .= '    <td><code>' . escape_html($la['la_rule']) . '</code></td>' . PHP_EOL;
            $string .= '    <td>' . escape_html($la['la_severity']) . '</td>' . PHP_EOL;
            $string .= '    <td>' . ($la['la_disable'] ? '<span class="label label-error">disabled</span>' : '<span class="label label-success">enabled</span>') . '</td>' . PHP_EOL;
            $string .= '    <td style="text-align: right;">';
            if ($_SESSION['userlevel'] >= 10) {
                $buttons = [
                    [ 'title' => 'Edit',   'event' => 'default', 'url' => '#modal-edit_syslog_rule_' . $la['la_id'], 'icon' => 'icon-cog text-muted', 'attribs' => [ 'data-toggle' => 'modal' ] ],
                    [ 'event' => 'danger',  'icon' => 'icon-trash',
                      'url' => generate_url(['page' => 'syslog_rules'], [ 'action' => 'syslog_rule_delete', 'la_id' => $la['la_id'], 'confirm' => 'confirm', 'requesttoken' => $_SESSION['requesttoken'] ]),
                        // confirmation dialog
                        'attribs'   => [
                            'data-title'     => 'Delete Rule "'.escape_html($la['la_name']).'"?',
                            'data-toggle'    => 'confirm', // Enable confirmation dialog
                            'data-placement' => 'left',
                            'data-content'   => '<div class="alert alert-warning"><h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>
                                                 <span class="text-nowrap">This rule and all history<br />will be completely deleted!</span></div>',
                        ],
                        //'attribs' => [ 'data-toggle' => 'modal' ]
                    ],
                ];
                $string .= PHP_EOL . generate_button_group($buttons, [ 'title' => 'Rule actions' ]);
            }
            $string .= '</td>';
            $string .= '  </tr>' . PHP_EOL;


            // Edit Rule Modal

            $modal_args = [
              'id'    => 'modal-edit_syslog_rule_' . $la['la_id'],
              'title' => 'Edit Syslog Rule "' . escape_html($la['la_descr']) . '"',
              //'hide'  => TRUE,
              //'fade'  => TRUE,
              //'role'  => 'dialog',
              'class' => 'modal-lg',
            ];

            $form = [
                'type'       => 'horizontal',
                'id'         => 'edit_syslog_rule_' . $la['la_id'],
                'userlevel'  => 10,          // Minimum user level for display form
                'modal_args' => $modal_args, // !!! This generate modal specific form
                //'help'     => 'This will completely delete the rule and all associations and history.',
                'class'      => '', // Clean default box class!
                'url'        => generate_url(['page' => 'syslog_rules'])
            ];
            $form['fieldset']['body']   = ['class' => 'modal-body'];   // Required this class for modal body!
            $form['fieldset']['footer'] = ['class' => 'modal-footer']; // Required this class for modal footer!

            $form['row'][0]['la_id'] = [
              'type'     => 'hidden',
              'fieldset' => 'body',
              'value'    => $la['la_id']];

            $form['row'][3]['la_name']    = [
              'type'     => 'text',
              'fieldset' => 'body',
              'name'     => 'Rule Name',
              'class'    => 'input-xlarge',
              'value'    => $la['la_name']];
            $form['row'][4]['la_descr']   = [
              'type'     => 'textarea',
              'fieldset' => 'body',
              'name'     => 'Description',
              'class'    => 'input-xxlarge',
              //'style'       => 'margin-bottom: 10px;',
              'value'    => $la['la_descr']];
            $form['row'][5]['la_rule']    = [
              'type'     => 'textarea',
              'fieldset' => 'body',
              'name'     => 'Regular Expression',
              'class'    => 'input-xxlarge',
              'value'    => $la['la_rule']
            ];
            $form['row'][6]['la_disable'] = [
              'type'      => 'switch-ng',
              'fieldset'  => 'body',
              'name'      => 'Status',
              'on-text'   => 'Disabled',
              'on-color'  => 'danger',
              'off-text'  => 'Enabled',
              'off-color' => 'success',
              'size'      => 'small',
              'value'     => $la['la_disable']
            ];

            $form['row'][8]['close']  = [
              'type'      => 'submit',
              'fieldset'  => 'footer',
              'div_class' => '', // Clean default form-action class!
              'name'      => 'Close',
              'icon'      => '',
              'attribs'   => ['data-dismiss' => 'modal',
                              'aria-hidden'  => 'true']
            ];
            $form['row'][9]['action'] = [
              'type'      => 'submit',
              'fieldset'  => 'footer',
              'div_class' => '', // Clean default form-action class!
              'name'      => 'Save Changes',
              'icon'      => 'icon-ok icon-white',
              //'right'       => TRUE,
              'class'     => 'btn-primary',
              'value'     => 'syslog_rule_edit'
            ];

            $modals .= generate_form_modal($form);
            unset($form);

        }

        $string .= '</table>';
        $string .= generate_box_close();

        echo $string;

        echo $modals;

    } else {

        print_warning("There are currently no Syslog alerting filters defined.");

    }

}

// EOF
