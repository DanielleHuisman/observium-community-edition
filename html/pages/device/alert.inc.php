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

// User level 7-9 only can see config
$readonly = $_SESSION['userlevel'] < 8;

if ($entry = get_alert_entry_by_id($vars['alert_entry'])) {
    if ($entry['device_id'] != $device['device_id']) {
        print_error("This alert entry id does not match this device.");
        return;
    }

    // Run actions
    if ($vars['submit'] === 'update-alert-entry' && !$readonly) {

        if (isset($vars['ignore_until_ok']) && ($vars['ignore_until_ok'] == '1' || $entry['ignore_until_ok'] == '1')) {
            $update_state['ignore_until_ok'] = '1';
            if ($entry['alert_status'] == 0) {
                $update_state['alert_status'] = '3';
            }
        } else {
            $update_state['ignore_until_ok'] = '0';
        }

        // 2019-12-05 23:30:00

        if (isset($vars['ignore_until']) && $vars['ignore_until_enable']) {
            $vars['ignore_unixtime']      = strtotime($vars['ignore_until']);
            $update_state['ignore_until'] = $vars['ignore_until'];
            if ($entry['alert_status'] == 0 && $vars['ignore_unixtime'] > time()) {
                $update_state['alert_status'] = '3';
            }
        } else {
            $update_state['ignore_until'] = ['NULL'];
        }

        if (is_array($update_state)) {
            $up_s = dbUpdate($update_state, 'alert_table', '`alert_table_id` =  ?', [$vars['alert_entry']]);
        }

        // Refresh array because we've changed the database.
        $entry = get_alert_entry_by_id($vars['alert_entry']);
    }

    // End actions

    humanize_alert_entry($entry);

    $alert_rules = cache_alert_rules();
    $alert       = $alert_rules[$entry['alert_test_id']];
    $state       = safe_json_decode($entry['state']);
    $conditions  = safe_json_decode($alert['conditions']);
    $entity      = get_entity_by_id_cache($entry['entity_type'], $entry['entity_id']);

//  r($entry);
//  r($alert);

    ?>

    <div class="row">
    <div class="col-md-3">
        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title">Alert Details</h3>
            </div>
            <div class="box-body no-padding">
                <table class="table table-condensed  table-striped ">
                    <tbody>
                    <tr>
                        <th>Type</th>
                        <td><?php echo get_icon($config['entities'][$alert['entity_type']]['icon']) . nicecase($entry['entity_type']); ?></td>
                    </tr>
                    <tr>
                        <th>Entity</th>
                        <td><?php echo generate_entity_link($entry['entity_type'], $entry['entity_id'], $entity['entity_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Checker</th>
                        <td><a
                              href="<?php echo generate_url(['page' => 'alert_check', 'alert_test_id' => $alert['alert_test_id']]); ?>"><?php echo escape_html($alert['alert_name']); ?></a>
                        </td>
                    </tr>
                    <tr>
                        <th>Fail Msg</th>
                        <td><?php echo escape_html($alert['alert_message']); ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title">Status</h3>
            </div>
            <div class="box-body no-padding">

                <table class="table table-condensed">
                    <tr>
                        <th>Status</th>
                        <td><span class="<?php echo $entry['class']; ?>"><?php echo $entry['last_message']; ?></span></td>
                    </tr>
                    <tr>
                        <th>Last Changed</th>
                        <td><?php echo $entry['changed']; ?></td>
                    </tr>
                    <tr>
                        <td colspan=2>
                            <?php

                            $state = safe_json_decode($entry['state']);

                            $alert['state_popup'] = '';

                            // FIXME - rewrite this, it's shit

                            if ($alert['alert_status'] != '1' && safe_count($state['failed'])) {
                                $alert['state_popup'] .= '<table class="table table-striped table-condensed">';
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
                                                             format_value($test['value'], $entity['entity_format']) . $entity['entity_symbol'] . '</td><td><i class="red">' .
                                                             format_value($state['metrics'][$test['metric']], $entity['entity_format']) . $entity['entity_symbol'] . '</i></td></tr>';
                                }
                                $alert['state_popup'] .= '</table>';

                            } elseif (safe_count($state['metrics'])) {
                                $alert['state_popup'] .= '<table class="table table-striped table-condensed">';
                                $alert['state_popup'] .= '<thead><tr><th>Metric</th><th>Value</th></tr></thead>';
                                foreach ($state['metrics'] as $metric => $value) {
                                    $alert['state_popup'] .= '<tr><td><strong>' . $metric . '</strong></td><td>' . format_value($value, $entity['entity_format']) . $entity['entity_symbol'] . '</td></tr>';
                                }
                                $alert['state_popup'] .= '</table>';

                            }

                            echo $alert['state_popup'];

                            ?>
                        </td>
                    </tr>

                    <!--          <tr><th>Last Checked</th><td><?php echo $entry['checked']; ?></td></tr>
          <tr><th>Last Changed</th><td><?php echo $entry['changed']; ?></td></tr>
          <tr><th>Last Alerted</th><td><?php echo $entry['alerted']; ?></td></tr>
          <tr><th>Last Recovered</th><td><?php echo $entry['recovered']; ?></td></tr> -->
                </table>
            </div>
        </div>
    </div>


    <div class="col-md-5">
        <?php

        $form = [
          'type'     => 'horizontal',
          'id'       => 'update_alert_entry',
          'title'    => 'Alert Settings',
          //'icon'      => 'oicon-gear',
          'fieldset' => ['edit' => ''],
        ];

        $form['row'][0]['editing']             = [
          'type'  => 'hidden',
          'value' => 'yes'
        ];
        $form['row'][1]['ignore_until']        = [
          'type'        => 'datetime',
          //'fieldset'    => 'edit',
          'name'        => 'Ignore Until',
          'placeholder' => '',
          //'width'       => '250px',
          'readonly'    => $readonly,
          'disabled'    => empty($entry['ignore_until']),
          'min'         => 'current',
          'value'       => $entry['ignore_until'] ?: ''
        ];
        $form['row'][1]['ignore_until_enable'] = [
          'type'     => 'toggle',
          'view'     => 'toggle',
          'size'     => 'big',
          'palette'  => 'blue',
          'readonly' => $readonly,
          'onchange' => "toggleAttrib('disabled', 'ignore_until')",
          'value'    => !empty($entry['ignore_until'])
        ];
        $form['row'][2]['ignore_until_ok']     = [
          'type'     => 'toggle',
          'name'     => 'Ignore Until OK',
          //'fieldset'    => 'edit',
          'view'     => 'toggle',
          'size'     => 'big',
          'palette'  => 'blue',
          'readonly' => $readonly,
          'value'    => $entry['ignore_until_ok']
        ];

        if (!$readonly) { // Hide button for readonly
            $form['row'][7]['submit'] = [
              'type'      => 'submit',
              'name'      => 'Save Changes',
              'icon'      => 'icon-ok icon-white',
              'div_style' => 'padding-top: 10px; padding-bottom: 10px;',
              'right'     => TRUE,
              'class'     => 'btn-primary',
              'readonly'  => $readonly,
              'value'     => 'update-alert-entry'
            ];
        }

        print_form($form);
        unset($form);
        ?>
    </div>

    <div class="col-md-12">
    <?php echo generate_box_open(['title' => 'Historical Availability']); ?>

    <table class="table table-condensed  table-striped">

        <tr>
            <td>
                <?php
                $graph_array['id']   = $entry['alert_table_id'];
                $graph_array['type'] = 'alert_status';
                print_graph_row($graph_array);
                ?>
            </td>
        </tr>
    </table>
    <?php

    echo generate_box_close();
    echo("</div></div>"); // end row

    $vars['entity_type'] = $entry['entity_type'];
    $vars['entity_id']   = $entry['entity_id'];

    print_alert_log($vars);


} else {
    print_error("Unfortunately, this alert entry id does not seem to exist in the database!");
}

// EOF
