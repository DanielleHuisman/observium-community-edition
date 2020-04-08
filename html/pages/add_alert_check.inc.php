<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage webui
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Global write permissions required.
if ($_SESSION['userlevel'] < 10)
{
  print_error_permission();
  return;
}

register_html_resource('css', 'query-builder.default.css');
register_html_resource('js', 'jQuery.extendext.min.js');
register_html_resource('js', 'doT.min.js');
register_html_resource('js', 'query-builder.js');
register_html_resource('js', 'bootbox.min.js');
register_html_resource('js', 'bootstrap-select.min.js');
register_html_resource('js', 'interact.min.js');

include($config['html_dir']."/includes/alerting-navbar.inc.php");

  // print_vars($vars);

  if (isset($vars['submit']) && $vars['submit'] == "add_alert_check")
  {
    $message = '<h4>Adding alert checker</h4> ';

    $ok = TRUE;
    foreach (array('entity_type', 'alert_name', 'alert_severity', 'check_conditions', 'assoc_device_conditions', 'assoc_entity_conditions') as $var)
    {
      if (!isset($vars[$var]) || strlen($vars[$var]) == '0') { $ok = FALSE; }
    }

    if ($ok)
    {
      $check_array = array();

      $conditions = array();
      foreach (explode("\n", trim($vars['check_conditions'])) AS $cond)
      {
        $condition = array();
        list($condition['metric'], $condition['condition'], $condition['value']) = explode(" ", trim($cond), 3);
        $conditions[] = $condition;
      }
      $check_array['conditions'] = json_encode($conditions);

      $check_array['entity_type'] = $vars['entity_type'];
      $check_array['alert_name'] = $vars['alert_name'];
      $check_array['alert_message'] = $vars['alert_message'];
      $check_array['severity'] = $vars['alert_severity'];
      $check_array['suppress_recovery'] = ($vars['alert_send_recovery'] == '1' || $vars['alert_send_recovery'] == 'on' ? 0 : 1);
      $check_array['alerter'] = NULL;
      $check_array['and'] = $vars['alert_and'];
      $check_array['delay'] = $vars['alert_delay'];
      $check_array['enable'] = '1';

      $check_id = dbInsert('alert_tests', $check_array);
      if (is_numeric($check_id))
      {
        $message .= '<p>Alert inserted as <a href="'.generate_url(array('page' => 'alert_check', 'alert_test_id' => $check_id)).'">'.$check_id.'</a></p>';

        $assoc_array = array();
        $assoc_array['alert_test_id'] = $check_id;
        $assoc_array['entity_type'] = $vars['entity_type'];
        $assoc_array['enable'] = '1';
        $dev_conds = array();
        foreach (explode("\n", trim($vars['assoc_device_conditions'])) AS $cond)
        {
          list($condition['attrib'], $condition['condition'], $condition['value']) = explode(" ", trim($cond), 3);
          $dev_conds[] = $condition;
        }
        $assoc_array['device_attribs'] = json_encode($dev_conds);
        if ($vars['assoc_device_conditions'] == "*") { $vars['assoc_device_conditions'] = json_encode(array()); }
        $ent_conds = array();
        foreach (explode("\n", trim($vars['assoc_entity_conditions'])) AS $cond)
        {
          list($condition['attrib'], $condition['condition'], $condition['value']) = explode(" ", trim($cond), 3);
          $ent_conds[] = $condition;
        }
        $assoc_array['entity_attribs'] = json_encode($ent_conds);
        if ($vars['assoc_entity_conditions'] == "*") { $vars['assoc_entity_conditions'] = json_encode(array()); }

        $assoc_id = dbInsert('alert_assoc', $assoc_array);
        if (is_numeric($assoc_id))
        {
          print_success($message . "<p>Association inserted as ".$assoc_id."</p>");
          set_obs_attrib('alerts_require_rebuild', '1');
          unset($vars); // Clean vars for use with new associations
        } else {
          print_warning($message . "<p>Association creation failed.</p>");
          dbDelete('alert_tests', "`alert_test_id` = ?", array($check_id)); // Undo alert checker create
        }
      } else {
        print_error($message . "<p>Alert creation failed. Please note that the alert name <b>must</b> be unique.</p>");
      }
    } else {
      print_warning($message . "Missing required data.");
    }

    if (OBS_DEBUG)
    {
      print_message("<h4>TEMPLATE:<h4> <pre>" . escape_html(generate_template('alert', array_merge($check_array, $vars))) . "</pre>", 'console', FALSE);
    }

  }

if(!isset($vars['entity_type'])) {

   print generate_box_open(array('title' => 'Select Alert Checker Entity Type', 'padding' => true, 'header-border' => true));


   //echo '<h4>Select Entity Type</h4>';

   ksort($config['entities']);

   foreach ($config['entities'] as $entity_type => $entity_type_array) {

      if (!$entity_type_array['hide']) {
         //echo '<option value="' . generate_url(array('page' => 'group_add', 'entity_type' => $entity_type)) . '" ' . ($entity_type == $vars['entity_type'] ? ' selected' : '') . '>' . $entity_type . '</option>';

         echo '<btn class="btn" style="margin: 5px;"><a href="'.generate_url(array('page' => 'add_alert_check', 'entity_type' => $entity_type)) . '" ' . ($entity_type == $vars['entity_type'] ? ' selected' : '').'">
                                        <i class="'.$config['entities'][$entity_type]['icon'].'"></i> '.nicecase($entity_type).'</a></btn>';


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

} else
{

   ?>

    <form name="form1" method="post" action="<?php echo(generate_url(array('page' => 'add_alert_check'))); ?>"
          class="form-horizontal">

        <div class="row">
            <div class="col-md-4">

               <?php

               $box_args = array('title'         => 'New Checker Details',
                                 'header-border' => TRUE,
                                 'padding'       => TRUE,
               );

               echo generate_box_open($box_args);

               ?>

                <fieldset>
                    <!--
                    <div class="control-group">
                        <label class="control-label" for="entity_type">Entity Type</label>
                        <div class="controls">
                           <?php
                           $item = array('id'          => 'entity_type',
                                         'live-search' => FALSE,
                                         'width'       => '220px',
                                         'value'       => $vars['entity_type']);
                           foreach ($config['entities'] as $entity_type => $entity_type_array)
                           {
                              if (!$entity_type_array['hide'])
                              { // ignore this type if it's a meta-entity
                                 if (!isset($entity_type_array['icon']))
                                 {
                                    $entity_type_array['icon'] = $config['entity_default']['icon'];
                                 }
                                 $item['values'][$entity_type] = array('name' => nicecase($entity_type),
                                                                       'icon' => $entity_type_array['icon']);
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
                           <?php echo '<i class="'.$config['entities'][$vars['entity_type']]['icon'].'"></i> <span class="entity">'.nicecase($vars['entity_type']).'</span>'; ?>

                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="alert_name">Alert Name</label>
                        <div class="controls">
                           <?php
                           $item = array('id'          => 'alert_name',
                                         'name'        => 'Alert name',
                                         'placeholder' => TRUE,
                                         'width'       => '220px',
                                         'value'       => $vars['alert_name']);
                           echo(generate_form_element($item, 'text'));
                           ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="alert_message">Message</label>
                        <div class="controls">
                           <?php
                           $item = array('id'          => 'alert_message',
                                         'name'        => 'Alert message',
                                         'placeholder' => TRUE,
                                         //'width'       => '220px',
                                         'class'       => 'col-md-11',
                                         'rows'        => 3,
                                         'value'       => $vars['alert_message']);
                           echo(generate_form_element($item, 'textarea'));
                           ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="alert_delay">Alert Delay</label>
                        <div class="controls">
                           <?php
                           $item = array('id'          => 'alert_delay',
                                         'name'        => '&#8470; of checks to delay alert',
                                         'placeholder' => TRUE,
                                         'width'       => '220px',
                                         'value'       => $vars['alert_delay']);
                           echo(generate_form_element($item, 'text'));
                           ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="alert_send_recovery">Send recovery</label>
                        <div class="controls">
                           <?php
                           $item = array('id'        => 'alert_send_recovery',
                                         'size'      => 'small',
                                         'off-color' => 'danger',
                                         'value'     => (isset($vars['alert_send_recovery']) ? $vars['alert_send_recovery'] : 1)); // Set to on by default
                           echo(generate_form_element($item, 'switch'));
                           ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="alert_severity">Severity</label>
                        <div class="controls">
                           <?php
                           $item = array('id'          => 'alert_severity',
                                         //'name'        => 'Severity',
                                         'live-search' => FALSE,
                                         'width'       => '220px',
                                         'value'       => $vars['alert_severity'],
                                         'values'      => array('crit' => array('name' => 'Critical',
                                                                                'icon' => $config['icon']['exclamation']),
                                                                //'warn' => array('name' => 'Warning',
                                                                //                'icon' => 'oicon-warning'),
                                                                //'info' => array('name' => 'Informational',
                                                                //                'icon' => 'oicon-information'),
                                         )
                           );
                           echo(generate_form_element($item, 'select'));
                           ?>
                        </div>
                    </div>
                </fieldset>


                <?php echo generate_box_close(); ?>


            </div> <!-- col -->

            <div class="col-md-8">

               <?php

               $box_args = array('title'         => 'Test Conditions',
                                 'header-border' => TRUE,
                                 'padding'       => TRUE,
               );


               $box_args['header-controls'] = array('controls' => array('tooltip' => array('icon'   => $config['icon']['info'],
                                                                                           'anchor' => TRUE,
                                                                                           'class'  => 'tooltip-from-element',
                                                                                           //'url'    => '#',
                                                                                           'data'   => 'data-tooltip-id="tooltip-help-conditions"')));

               echo generate_box_open($box_args);

               ?>

                <div style="margin-bottom: 10px;">
                   <?php
                   $item = array('id'          => 'alert_and',
                                 //'name'        => 'Severity',
                                 'live-search' => FALSE,
                                 'width'       => '220px',
                                 'value'       => (isset($vars['alert_and']) ? $vars['alert_and'] : 1), // Set to and by default
                                 'values'      => array('0' => array('name' => 'Require any condition',
                                                                     'icon' => $config['icon']['or-gate']),
                                                        '1' => array('name' => 'Require all conditions',
                                                                     'icon' => $config['icon']['and-gate']),
                                 )
                   );
                   echo(generate_form_element($item, 'select'));

                   echo(PHP_EOL . '          </div>' . PHP_EOL);

                   $item = array('id'          => 'alert_conditions',
                                 'name'        => 'Metric Conditions',
                                 'placeholder' => TRUE,
                                 //'width'       => '220px',
                                 'class'       => 'col-md-12',
                                 'rows'        => 3,
                                 'value'       => $vars['alert_conditions']);
                   echo generate_form_element($item, 'textarea');

                   echo generate_box_close();

                   $box_args = array('title'         => 'Association Ruleset',
                                     'header-border' => TRUE,
                                     'padding'       => TRUE,
                   );

                   $box_args['header-controls'] = array('controls' => array('tooltip' => array('icon'   => $config['icon']['info'],
                                                                                               'anchor' => TRUE,
                                                                                               'class'  => 'tooltip-from-element',
                                                                                               //'url'    => '#',
                                                                                               'data'   => 'data-tooltip-id="tooltip-help-associations"')));
                   echo generate_box_open($box_args);


                   $form_id = 'rules-' . generate_random_string(8);

                   echo '<div id="' . $form_id . '"></div>';

                   generate_querybuilder_form($vars['entity_type'], 'attribs', $form_id);

                   // generate_querybuilder_form($vars['entity_type'], 'metrics');


                $footer_content = '
                <div class="btn-group pull-right">
                    <btn class="btn btn-danger" id="btn-reset" data-target="' . $form_id . '"><i class="icon-trash"></i> Clear Rules</btn>
                    <btn class="btn btn-success" id="btn-save" data-target="' . $form_id . '"><i class="icon-plus-sign"></i> Add Checker</btn>
                </div>'.$script;

                echo generate_box_close(array('footer_content' => $footer_content));

                // echo generate_box_close();

                ?>

                </div> <!-- col -->
            </div> <!-- row -->

            <!--

            <div class="form-actions">
               <?php
               $item = array('id'    => 'submit',
                             'name'  => 'Add Check',
                             'class' => 'btn-success',
                             'icon'  => $config['icon']['checked'],
                             'value' => 'add_alert_check');
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
                                alert_conditions: document.getElementById('alert_conditions').value
                               
                            });
      
      var request = $.ajax({
      type: 'POST',
      url: 'ajax/actions.php',
      data: formData,
      dataType: 'json',
      contentType : 'application/json',
    });
      
    request.success(  function(json) {
      
        if(json.status === 'ok') 
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
