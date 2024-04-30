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

// Alert test display and editing page.

include($config['html_dir'] . "/includes/alerting-navbar.inc.php");

?>

    <div class="row">
        <div class="col-md-12">

            <?php

            // Note, this form have more complex grid and class elements for responsive datetime field
            $form = ['type'          => 'rows',
                     'space'         => '5px',
                     'submit_by_key' => TRUE,
                     'url'           => generate_url($vars)];

            $where = generate_where_clause($cache['where']['devices_permitted']);

            // Show devices only with alert logs
            $form_devices          = dbFetchColumn('SELECT DISTINCT `device_id` FROM `alert_log`' . $where);
            $form_items['devices'] = generate_form_values('device', $form_devices);

            // Device field
            $form['row'][0]['device_id'] = [
              'type'      => 'multiselect',
              'name'      => 'Devices',
              'width'     => '100%',
              'div_class' => 'col-lg-2 col-md-2 col-sm-2',
              'value'     => $vars['device_id'],
              'groups'    => ['', 'UP', 'DOWN', 'DISABLED'], // This is optgroup order for values (if required)
              'values'    => $form_items['devices']];

            // Add device_id limit for other fields
            if (isset($vars['device_id']) && device_permitted($vars['device_id'])) {
                $where = generate_where_clause(generate_query_values($vars['device_id'], 'device_id'));
            }

            // Checkers Field
            $form_filter                     = dbFetchColumn('SELECT DISTINCT `alert_test_id` FROM `alert_log`' . $where);
            $form_items['checkers']          = generate_form_values('alert_log', $form_filter, 'alert_test_id');
            $form['row'][0]['alert_test_id'] = [
              'type'      => 'multiselect',
              'name'      => 'Checkers',
              'width'     => '100%',
              'div_class' => 'col-lg-2 col-md-1 col-sm-3',
              'subtext'   => TRUE,
              'value'     => $vars['alert_test_id'],
              'values'    => $form_items['checkers']];

            // Status Type Field
            $form_filter                = dbFetchColumn('SELECT DISTINCT `log_type` FROM `alert_log`' . $where);
            $form_items['statuses']     = generate_form_values('alert_log', $form_filter, 'log_type');
            $form['row'][0]['log_type'] = [
              'type'      => 'multiselect',
              'name'      => 'Status Type',
              'width'     => '100%',
              'div_class' => 'col-lg-2 col-md-1 col-sm-3',
              'size'      => '15',
              'value'     => $vars['log_type'],
              'values'    => $form_items['statuses']];

            // Datetime Field
            $form['row'][0]['timestamp'] = [
              'type'      => 'datetime',
              //'grid'        => 5,
              //'width'       => '70%',
              'div_class' => 'col-lg-5 col-md-7 col-sm-9 col-md-push-0 col-sm-push-2',
              'presets'   => TRUE,
              'min'       => dbFetchCell('SELECT `timestamp` FROM `alert_log`' . $where . ' ORDER BY `timestamp` LIMIT 0,1;'),
              'max'       => dbFetchCell('SELECT `timestamp` FROM `alert_log`' . $where . ' ORDER BY `timestamp` DESC LIMIT 0,1;'),
              'from'      => $vars['timestamp_from'],
              'to'        => $vars['timestamp_to']];

            // search button
            $form['row'][0]['search'] = [
              'type'      => 'submit',
              //'name'        => 'Search',
              //'icon'        => 'icon-search',
              'div_class' => 'col-lg-1 col-md-1 col-sm-3',
              //'grid'        => 1,
              'right'     => TRUE];

            print_form($form);
            unset($form, $form_items, $form_devices, $form_filter);


            // Pagination
            $vars['pagination'] = TRUE;

            // Print Alert Log
            print_alert_log($vars);

            register_html_title('Alert Log');

            ?>
        </div> <!-- col-md-12 -->

    </div> <!-- row -->
<?php

// EOF
