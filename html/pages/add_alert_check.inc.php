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

// Global write permissions required.
if ($_SESSION['userlevel'] < 9) {
    print_error_permission();
    return;
}

register_html_resource('css', 'query-builder.default.css');
register_html_resource('js', 'jQuery.extendext.min.js');
register_html_resource('js', 'doT.min.js');
register_html_resource('js', 'query-builder.js');
register_html_resource('js', 'bootbox.min.js');
//register_html_resource('js', 'bootstrap-select.min.js');
register_html_resource('js', 'interact.min.js');

include($config['html_dir'] . "/includes/alerting-navbar.inc.php");

// print_vars($vars);

if (!isset($vars['entity_type'])) {

    register_html_title('Add Alert Checker: Select Entity Type');
    print generate_box_open(['title' => 'Select Alert Checker Entity Type', 'padding' => TRUE, 'header-border' => TRUE]);

    //echo '<h4>Select Entity Type</h4>';

    ksort($config['entities']);

    foreach ($config['entities'] as $entity_type => $entity_type_array) {

        if (!$entity_type_array['hide']) {
            //echo '<option value="' . generate_url(array('page' => 'group_add', 'entity_type' => $entity_type)) . '" ' . ($entity_type == $vars['entity_type'] ? ' selected' : '') . '>' . $entity_type . '</option>';

            echo '<btn class="btn" style="margin: 5px;"><a href="' . generate_url(['page' => 'add_alert_check', 'entity_type' => $entity_type]) . '" ' . ($entity_type == $vars['entity_type'] ? ' selected' : '') . '">
                                        <i class="' . $config['entities'][$entity_type]['icon'] . '"></i> ' . nicecase($entity_type) . '</a></btn>';


        }
    }

    echo generate_box_close();


    echo '<script type="text/javascript">
    $(document).ready(function () {
      $("#selection").change(function () {
        location = $("#selection option:selected").val();
      });
    });
    </script>';

    /*
   // Allow duplication of existing checks
   echo generate_box_open(array('title' => 'Duplicate Existing Checker', 'padding' => true, 'header-border' => true));

   $alert_checks = cache_alert_rules($vars);
   $alert_checks = array_sort($alert_checks, 'alert_name');

   echo '<table class="table table-striped table-hover">
   <thead>
     <tr>
     <th class="state-marker"></th>
     <th style="width: 1px;"></th>
     <th style="width: 400px">Name</th>
     <th style="width: 140px"></th>
     <th></th>
     </tr>
   </thead>
   <tbody>', PHP_EOL;

   foreach ($alert_checks as $check) {

     // Process the alert checker to add classes and colours and count status.
     humanize_alert_check($check);

     echo('<tr class="' . $check['html_row_class'] . '">');

     echo('
     <td class="state-marker"></td>
     <td style="width: 1px;"></td>');

     // Print the conditions applied by this alert

     echo '<td><strong>';
     echo '<a href="', generate_url(array('page' => 'alert_check', 'alert_test_id' => $check['alert_test_id'])), '">' . escape_html($check['alert_name']) . '</a></strong><br />';
     echo '<small>', escape_html($check['alert_message']), '</small>';
     echo '</td>';

     echo '<td><i class="' . $config['entities'][$check['entity_type']]['icon'] . '"></i></td>';

     echo('</td>');

     echo('</tr>');

   }
   // End loop of associations

   echo '</table>';
   echo generate_box_close();
   // End duplication of existing checks
 */

} else {

    register_html_title('Add Alert Checker');

    if (isset($vars['duplicate_id']) && $alert_dupe = get_alert_test_by_id($vars['duplicate_id'])) {
        humanize_alert_check($alert_dupe);
        $conditions           = safe_json_decode($alert_dupe['conditions']);
        $condition_text_block = [];
        foreach ($conditions as $condition) {
            $condition_text_block[] = $condition['metric'] . ' ' . $condition['condition'] . ' ' .
                                      str_replace(',', ',&#x200B;', $condition['value']); // Add hidden space char (&#x200B;) for wrap long lists
        }
        $vars['alert_conditions'] = implode(PHP_EOL, $condition_text_block);
        $vars                     = array_merge($vars, $alert_dupe);
    }
    //r($vars);

    ?>

    <form name="form1" method="post" action="<?php echo(generate_url(['page' => 'add_alert_check'])); ?>"
          class="form-horizontal">

        <div class="row">
            <div class="col-md-5">

                <?php

                $box_args = ['title'         => 'New Checker Details',
                             'header-border' => TRUE,
                             'padding'       => TRUE,
                ];

                echo generate_box_open($box_args);

                ?>

                <fieldset>
                    <!--
                    <div class="control-group">
                        <label class="control-label" for="entity_type">Entity Type</label>
                        <div class="controls">
                           <?php
                    $item = ['id'          => 'entity_type',
                             'live-search' => FALSE,
                             'width'       => '220px',
                             'value'       => $vars['entity_type']];
                    foreach ($config['entities'] as $entity_type => $entity_type_array) {
                        if (!$entity_type_array['hide']) { // ignore this type if it's a meta-entity
                            if (!isset($entity_type_array['icon'])) {
                                $entity_type_array['icon'] = $config['entity_default']['icon'];
                            }
                            $item['values'][$entity_type] = ['name' => nicecase($entity_type),
                                                             'icon' => $entity_type_array['icon']];
                        }
                    }
                    echo(generate_form_element($item, 'select'));
                    ?>
                        </div>
                    </div>
                    -->

                    <div id="group_name_div" class="control-group"> <!-- START row-2 -->
                        <label class="control-label" for="group_name">Entity Type</label>

                        <div class="controls">
                            <?php echo '<i class="' . $config['entities'][$vars['entity_type']]['icon'] . '"></i> <span class="entity">' . nicecase($vars['entity_type']) . '</span>'; ?>

                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="alert_name">Alert Name</label>
                        <div class="controls">
                            <?php
                            $item = ['id'          => 'alert_name',
                                     'name'        => 'Alert name',
                                     'placeholder' => TRUE,
                                     'width'       => '220px',
                                     'value'       => $vars['alert_name']];
                            echo(generate_form_element($item, 'text'));
                            ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="alert_message">Message</label>
                        <div class="controls">
                            <?php
                            $item = ['id'          => 'alert_message',
                                     'name'        => 'Alert message',
                                     'placeholder' => TRUE,
                                     //'width'       => '220px',
                                     'class'       => 'col-md-11',
                                     'rows'        => 3,
                                     'value'       => $vars['alert_message']];
                            echo(generate_form_element($item, 'textarea'));
                            ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="alert_delay">Alert Delay</label>
                        <div class="controls">
                            <?php
                            $item = ['id'          => 'alert_delay',
                                     'name'        => '&#8470; of checks to delay alert',
                                     'placeholder' => TRUE,
                                     'width'       => '220px',
                                     'value'       => $vars['alert_delay']];
                            echo(generate_form_element($item, 'text'));
                            ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="alert_send_recovery">Send recovery</label>
                        <div class="controls">
                            <?php
                            $item = ['id'      => 'alert_send_recovery',
                                     'size'    => 'big',
                                     'view'    => 'toggle',
                                     'palette' => 'blue',
                                     'value'   => $vars['alert_send_recovery'] ?? 1 ]; // Set to on by default
                            echo(generate_form_element($item, 'toggle'));
                            ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="alert_severity">Severity</label>
                        <div class="controls">
                            <?php
                            $item = ['id'          => 'alert_severity',
                                     //'name'        => 'Severity',
                                     'live-search' => FALSE,
                                     'width'       => '220px',
                                     'value'       => $vars['alert_severity'],
                                     'values'      => $config['alerts']['severity'],
                            ];
                            echo(generate_form_element($item, 'select'));
                            ?>
                        </div>
                    </div>
                </fieldset>


                <?php echo generate_box_close(); ?>


            </div> <!-- col -->

            <div class="col-md-7">

                <?php

                $box_args = ['title'         => 'Test Conditions',
                             'header-border' => TRUE,
                             'padding'       => TRUE,
                ];


                $box_args['header-controls'] = ['controls' => ['tooltip' => ['icon'   => $config['icon']['info'],
                                                                             'anchor' => TRUE,
                                                                             'class'  => 'tooltip-from-element',
                                                                             //'url'    => '#',
                                                                             'data'   => 'data-tooltip-id="tooltip-help-conditions"']]];

                echo generate_box_open($box_args);

                ?>

                <div style="margin-bottom: 10px;">
                    <?php
                    $item = [ 'id'          => 'alert_and',
                              //'name'        => 'Severity',
                              'live-search' => FALSE,
                              'width'       => '220px',
                              'value'       => $vars['alert_and'] ?? 1, // Set to and by default
                              'values'      => [ '0' => [ 'name' => 'Require any condition',
                                                          'icon' => $config['icon']['or-gate'] ],
                                                 '1' => [ 'name' => 'Require all conditions',
                                                          'icon' => $config['icon']['and-gate'] ],
                             ]
                    ];
                    echo(generate_form_element($item, 'select'));

                    echo(PHP_EOL . '          </div>' . PHP_EOL);

                    $metrics_box = generate_alert_metrics_table($vars['entity_type'], $metrics_list);

                    $item = [ 'id'          => 'alert_conditions',
                              'name'        => 'Metric Conditions',
                              'placeholder' => TRUE,
                              //'width'       => '220px',
                              'class'       => 'col-md-10',
                              'style'       => 'margin-right: 10px',
                              'rows'        => max(count($metrics_list), 3),
                              'value'       => $vars['alert_conditions']
                    ];
                    echo generate_form_element($item, 'textarea');

                    echo $metrics_box;
                    unset($metrics_box, $metrics_list);

                    echo generate_box_close();

                    $box_args = ['title'         => 'Association Ruleset',
                                 'header-border' => TRUE,
                                 'padding'       => TRUE,
                    ];

                    $box_args['header-controls'] = ['controls' => ['tooltip' => ['icon'   => $config['icon']['info'],
                                                                                 'anchor' => TRUE,
                                                                                 'class'  => 'tooltip-from-element',
                                                                                 //'url'    => '#',
                                                                                 'data'   => 'data-tooltip-id="tooltip-help-associations"']]];
                    echo generate_box_open($box_args);


                    $form_id = 'rules-' . random_string(8);

                    echo '<div id="' . $form_id . '"></div>';

                    generate_querybuilder_form($vars['entity_type'], 'attribs', $form_id, $alert_dupe['alert_assoc']);

                    // generate_querybuilder_form($vars['entity_type'], 'metrics');


                    $footer_content = '
                <div class="btn-group pull-right">
                    <btn class="btn btn-danger" id="btn-reset" data-target="' . $form_id . '"><i class="icon-trash"></i> Clear Rules</btn>
                    <btn class="btn btn-success" id="btn-save" data-target="' . $form_id . '"><i class="icon-plus-sign"></i> Add Checker</btn>
                </div>' . $script;

                    echo generate_box_close(['footer_content' => $footer_content]);

                    // echo generate_box_close();

                    ?>

                </div> <!-- col -->
            </div> <!-- row -->

            <!--

            <div class="form-actions">
               <?php
            $item = ['id'    => 'submit',
                     'name'  => 'Add Check',
                     'class' => 'btn-success',
                     'icon'  => $config['icon']['checked'],
                     'value' => 'add_alert_check'];
            echo(generate_form_element($item, 'submit'));
            ?>
            </div>

            -->

    </form>

    <?php

    $script
      = "<script>
  $('#btn-save').on('click', function() {
    var result = $('#" . $form_id . "').queryBuilder('getRules');
    var div = $('#output');

    if (!$.isEmptyObject(result)) {

      var formData = JSON.stringify({
                                action: 'alert_check_add',
                                alert_assoc: JSON.stringify(result),
                                entity_type: '" . $vars['entity_type'] . "',
                                alert_name: document.getElementById('alert_name').value,
                                alert_message: document.getElementById('alert_message').value,
                                alert_delay: document.getElementById('alert_delay').value,
                                alert_send_recovery: document.getElementById('alert_send_recovery').value,
                                alert_severity: document.getElementById('alert_severity').value,
                                alert_and: document.getElementById('alert_and').value,
                                alert_conditions: document.getElementById('alert_conditions').value,
                                requesttoken: document.getElementById('requesttoken').value
                            });
      
      var request = $.ajax({
      type: 'POST',
      url: 'ajax/actions.php',
      data: formData,
      dataType: 'json',
      contentType : 'application/json',
    });
      
    request.success(  function(json) {
      
        if (json.status === 'ok') 
        {
            div.html('<div class=\"alert alert-success\">Creation Succeeded. Redirecting!</div>')
            window.setTimeout(window.location.href = json.redirect,5000);
        } else {
            div.html('<div class=\"alert alert-warning\">Creation Failed: ' + json.message + '</div>')
        }
        
    });
  }
  });
  
  
  $('#btn-reset').on('click', function() {
    $('#" . $form_id . "').queryBuilder('reset');
  });
  
  </script>
  ";

    echo $script;

    ?>


    <div id="tooltip-help-conditions" style="display: none;">

        Conditions should be entered in this format
        <pre>metric_1 condition value_1
metric_2 condition value_2
metric_3 condition value_3</pre>

        For example to alert when an enabled port is down
        <pre>ifAdminStatus equals up
ifOperStatus equals down</pre>

    </div>

    <?php

}

register_html_title('Add alert checker');

// EOF
