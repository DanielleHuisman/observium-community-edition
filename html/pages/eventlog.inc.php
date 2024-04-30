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

            $where = generate_where_clause($cache['where']['devices_permitted']);

            $form_items['devices'] = generate_form_values('device');

            // Device field
            $form['row'][0]['device_id'] = [
                'type'      => 'multiselect',
                'name'      => 'Devices',
                'width'     => '100%',
                'div_class' => 'col-lg-1 col-md-2 col-sm-2',
                'value'     => $vars['device_id'],
                'groups'    => [ '', 'UP', 'DOWN', 'DISABLED' ], // This is optgroup order for values (if required)
                'values'    => $form_items['devices']];

            // Add device_id limit for other fields
            if (isset($vars['device_id']) && device_permitted($vars['device_id'])) {
                $where = generate_where_clause(generate_query_values($vars['device_id'], 'device_id'));
            }

            // Message field
            $form['row'][0]['message'] = [
                'type'        => 'text',
                'name'        => 'Message',
                'placeholder' => 'Message',
                'width'       => '100%',
                'div_class'   => 'col-lg-3 col-md-4 col-sm-4',
                //'grid'        => 3,
                'value'       => $vars['message']
            ];

            // Severities field
            $form_filter                = dbFetchColumn('SELECT DISTINCT `severity` FROM `eventlog`' . $where);
            $form_items['severities']   = generate_form_values('eventlog', $form_filter, 'severity');
            $form['row'][0]['severity'] = [
                'type'      => 'multiselect',
                'name'      => 'Severities',
                'width'     => '100%',
                'div_class' => 'col-lg-1 col-md-2 col-sm-2',
                'subtext'   => TRUE,
                'value'     => $vars['severity'],
                'values'    => $form_items['severities']
            ];

            // Types field
            $form_filter         = dbFetchColumn('SELECT DISTINCT `entity_type` FROM `eventlog` IGNORE INDEX (`type`)' . $where);
            $form_items['types'] = generate_form_values('eventlog', $form_filter, 'type');

            $form['row'][0]['type'] = [
                'type'      => 'multiselect',
                'name'      => 'Types',
                'width'     => '100%',
                'div_class' => 'col-lg-1 col-md-2 col-sm-2',
                'size'      => '15',
                'value'     => $vars['type'],
                'values'    => $form_items['types']
            ];

            // Datetime Field
            $form['row'][0]['timestamp'] = [
                'type'      => 'datetime',
                //'grid'        => 5,
                //'width'       => '70%',
                'div_class' => 'col-lg-5 col-md-7 col-sm-10 col-lg-push-0 col-md-push-2 col-sm-push-2',
                'presets'   => TRUE,
                'min'       => dbFetchCell('SELECT `timestamp` FROM `eventlog`' . $where . ' ORDER BY `timestamp` LIMIT 0,1;'),
                'max'       => dbFetchCell('SELECT `timestamp` FROM `eventlog`' . $where . ' ORDER BY `timestamp` DESC LIMIT 0,1;'),
                'from'      => $vars['timestamp_from'],
                'to'        => $vars['timestamp_to']
            ];

            // search button
            $form['row'][0]['search'] = [
                'type'      => 'submit',
                //'name'        => 'Search',
                //'icon'        => 'icon-search',
                'div_class' => 'col-lg-1 col-md-5 col-sm-2',
                //'grid'        => 1,
                'right'     => TRUE
            ];

            print_form($form);
            unset($form, $form_items, $form_devices);


            // Pagination
            $vars['pagination'] = TRUE;

            // Print events
            print_events($vars);

            register_html_title('Eventlog');

            ?>

        </div> <!-- col-md-12 -->

    </div> <!-- row -->

<?php

// EOF
