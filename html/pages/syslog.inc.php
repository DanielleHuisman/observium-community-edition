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

?>
    <div class="row">
        <div class="col-md-12">

            <?php

            // Note, this form have more complex grid and class elements for responsive datetime field
            $form = [
                'type'          => 'rows',
                'space'         => '5px',
                'submit_by_key' => TRUE,
                'url'           => generate_url($vars)
            ];

            $where_array = [];

            $where_array[] = $cache['where']['devices_permitted'];

            // Show devices only with syslog messages
            $form_devices          = dbFetchColumn('SELECT DISTINCT `device_id` FROM `syslog`' . generate_where_clause($where_array));
            $form_items['devices'] = generate_form_values('device', $form_devices);

            // Device field
            $form['row'][0]['device_id'] = [
              'type'      => 'multiselect',
              'name'      => 'Devices',
              'width'     => '100%',
              'div_class' => 'col-lg-1 col-md-2 col-sm-2',
              'value'     => $vars['device_id'],
              'groups'    => ['', 'UP', 'DOWN', 'DISABLED'], // This is optgroup order for values (if required)
              'values'    => $form_items['devices']];

            // Add device_id limit for other fields
            $where_dev_array   = [];
            $where_dev_array[] = generate_query_values($form_devices, 'device_id'); // Convert NOT IN to IN for correctly use indexes
            if (isset($vars['device_id'])) {
                $where_dev_array[] = generate_query_values($vars['device_id'], 'device_id');
                $where_array[]     = generate_query_values($vars['device_id'], 'device_id');
            }

            $where     = generate_where_clause($where_array);
            $where_dev = generate_where_clause($where_dev_array);

            // Message field
            $form['row'][0]['message'] = [
              'type'        => 'text',
              'name'        => 'Message',
              'placeholder' => 'Message',
              'width'       => '100%',
              'div_class'   => 'col-lg-3 col-md-4 col-sm-4',
              //'grid'        => 3,
              'value'       => $vars['message']];

            // Priority field
            $form_items['priorities']   = generate_form_values('syslog', NULL, 'priorities');
            $form['row'][0]['priority'] = [
              'type'      => 'multiselect',
              'name'      => 'Priorities',
              'width'     => '100%',
              'div_class' => 'col-lg-1 col-md-2 col-sm-2',
              'subtext'   => TRUE,
              'value'     => $vars['priority'],
              'values'    => $form_items['priorities']];

            // Program field
            dbSetVariable('MAX_EXECUTION_TIME', 5000); // Set 5 sec maximum query execution time
            $form_filter = dbFetchColumn('SELECT DISTINCT `program` FROM `syslog`' . $where_dev);
            dbSetVariable('MAX_EXECUTION_TIME', 0); // Reset maximum query execution time
            if (safe_count($form_filter)) {
                // Use full multiselect form
                $form_items['programs']    = generate_form_values('syslog', $form_filter, 'programs');
                $form['row'][0]['program'] = [
                  'type'      => 'multiselect',
                  'name'      => 'Programs',
                  'width'     => '100%',
                  'div_class' => 'col-lg-1 col-md-2 col-sm-2',
                  'size'      => '15',
                  'value'     => $vars['program'],
                  'values'    => $form_items['programs']];
            } else {
                // Use input form with speedup indexed ajax program list
                $form['row'][0]['program'] = [
                  'type'        => 'text',
                  'name'        => 'Programs',
                  'placeholder' => 'Program: type for hints',
                  'width'       => '100%',
                  'div_class'   => 'col-lg-1 col-md-2 col-sm-2',
                  //'grid'        => 3,
                  'ajax'        => TRUE,
                  'ajax_vars'   => ['field' => 'syslog_program'],
                  'value'       => $vars['program']];
            }

            // Datetime Field
            $form['row'][0]['timestamp'] = [
              'type'      => 'datetime',
              //'grid'        => 5,
              //'width'       => '70%',
              'div_class' => 'col-lg-5 col-md-7 col-sm-10 col-lg-push-0 col-md-push-2 col-sm-push-2',
              'presets'   => TRUE,
              'min'       => dbFetchCell('SELECT `timestamp` FROM `syslog`' . $where . ' ORDER BY `timestamp` LIMIT 0,1;'),
              'max'       => dbFetchCell('SELECT `timestamp` FROM `syslog`' . $where . ' ORDER BY `timestamp` DESC LIMIT 0,1;'),
              'from'      => $vars['timestamp_from'],
              'to'        => $vars['timestamp_to']];
            // Second row with timestamp for md and sm
            //$form['row_options'][1]  = array('class' => 'hidden-lg hidden-xs');
            //$form['row'][1]['timestamp'] = $form['row'][0]['timestamp'];
            //$form['row'][1]['timestamp']['div_class'] = 'text-nowrap col-md-7 col-sm-8 col-md-offset-2 col-sm-offset-2';

            // search button
            $form['row'][0]['search'] = [
              'type'      => 'submit',
              //'name'        => 'Search',
              //'icon'        => 'icon-search',
              'div_class' => 'col-lg-1 col-md-5 col-sm-2',
              //'grid'        => 1,
              'right'     => TRUE];

            print_form($form);
            unset($form, $form_items, $form_devices);

            // Pagination
            $vars['pagination'] = TRUE;

            // Print syslog
            print_syslogs($vars);

            register_html_title('Syslog');

            ?>

        </div> <!-- col-md-12 -->

    </div> <!-- row -->
<?php

// EOF
