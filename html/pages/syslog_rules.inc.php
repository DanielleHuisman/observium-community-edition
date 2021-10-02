<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

if ($_SESSION['userlevel'] < 7) {
  print_error_permission();
  return;
}

include($config['html_dir'].'/includes/alerting-navbar.inc.php');

// Begin Actions
$readonly = $_SESSION['userlevel'] < 10; // Currently edit allowed only for Admins

// Hardcode Device sysContact
if (!dbExist('alert_contacts', '`contact_method` = ?', [ 'syscontact' ])) {
  $syscontact = [
    'contact_descr'            => 'Device sysContact',
    'contact_method'           => 'syscontact',
    'contact_endpoint'         => '{"syscontact":"device"}',
    //'contact_disabled'         => '0',
    //'contact_disabled_until'   => NULL,
    //'contact_message_custom'   => 0,
    //'contact_message_template' => NULL
  ];
  dbInsert($syscontact, 'alert_contacts');
}

if (!$readonly && isset($vars['action']) &&
    request_token_valid($vars['requesttoken']))
{
  switch ($vars['action'])
  {
    case 'edit_syslog_rule':
      $update_array = array('la_name'    => $vars['la_name'],
                            'la_descr'   => $vars['la_descr'],
                            'la_rule'    => $vars['la_rule'],
                            'la_disable' => (isset($vars['la_disable']) ? 1 : 0));
      $rows_updated = dbUpdate($update_array, 'syslog_rules', '`la_id` = ?', array($vars['la_id']));

      if ($rows_updated)
      {
        set_obs_attrib('syslog_rules_changed', time()); // Trigger reload syslog script
        print_message('Syslog Rule updated ('.$vars['la_id'].')');
      }
      unset($vars['la_id']);
      break;

    case 'delete_syslog_rule':
      if (get_var_true($vars['confirm'], 'confirm')) {
        $rows_deleted  = dbDelete('syslog_rules_assoc',   '`la_id` = ?', array($vars['la_id']));
        $rows_deleted += dbDelete('syslog_rules',         '`la_id` = ?', array($vars['la_id']));
        $rows_deleted += dbDelete('syslog_alerts',        '`la_id` = ?', array($vars['la_id']));
        $rows_deleted += dbDelete('alert_contacts_assoc', '`aca_type` = ? AND `alert_checker_id` = ?', array('syslog', $vars['la_id']));

        if ($rows_deleted)
        {
          set_obs_attrib('syslog_rules_changed', time()); // Trigger reload syslog script
          print_message('Deleted all traces of Syslog Rule ('.$vars['la_id'].')');
        }
      }
      unset($vars['la_id']);
      break;
  }

}

// End Actions

print_syslog_rules_table($vars);

function print_syslog_rules_table($vars)
{

  if (isset($vars['la_id']))
  {
    $las = dbFetchRows("SELECT * FROM `syslog_rules` WHERE `la_id` = ?", array($vars['la_id']));
  } else {
    $las = dbFetchRows("SELECT * FROM `syslog_rules` ORDER BY `la_name`");
  }

  if (is_array($las) && count($las))
  {

    $modals = '';
    $string = generate_box_open();
    $string .= '<table class="table table-striped table-hover table-condensed">' . PHP_EOL;

    $cols = array(
      array(NULL, 'class="state-marker"'),
      'name'         => array('Name',         'style="width: 160px;"'),
      'descr'        => array('Description',  'style="width: 400px;"'),
      'rule'         => 'Rule',
      'severity'     => array('Severity',     'style="width: 60px;"'),
      'disabled'     => array('Status',       'style="width: 60px;"'),
      'controls'     => array('',             'style="width: 60px;"'),
    );

    $string .= get_table_header($cols, $vars);

    foreach($las as $la)
    {

      if ($la['disable'] == 0) { $la['html_row_class'] = "up"; } else { $la['html_row_class'] = "disabled"; }

      $string .= '<tr class="' . $la['html_row_class'] . '">';
      $string .= '<td class="state-marker"></td>';

      $string .= '    <td><strong><a href="'.generate_url(array('page' => 'syslog_rules', 'la_id' => $la['la_id'])).'">' . escape_html($la['la_name']) . '</a></strong></td>' . PHP_EOL;
      $string .= '    <td><a href="'.generate_url(array('page' => 'syslog_rules', 'la_id' => $la['la_id'])).'">' . escape_html($la['la_descr']) . '</a></td>' . PHP_EOL;
      $string .= '    <td><code>' . escape_html($la['la_rule']) . '</code></td>' . PHP_EOL;
      $string .= '    <td>' . escape_html($la['la_severity']) . '</td>' . PHP_EOL;
      $string .= '    <td>' . ($la['la_disable'] ? '<span class="label label-error">disabled</span>' : '<span class="label label-success">enabled</span>') . '</td>' . PHP_EOL;
      $string .= '    <td style="text-align: right;">';
      if ($_SESSION['userlevel'] >= 10)
      {
        $string .= '
      <div class="btn-group btn-group-xs" role="group" aria-label="Rule actions">
        <a class="btn btn-default" role="group" title="Edit" href="#modal-edit_syslog_rule_'.$la['la_id'].'" data-toggle="modal"><i class="icon-cog text-muted"></i></a>
        <a class="btn btn-danger"  role="group" title="Delete" href="#modal-delete_syslog_rule_'.$la['la_id'].'" data-toggle="modal"><i class="icon-trash"></i></a>
      </div>';
      }
      $string .= '</td>';
      $string .= '  </tr>' . PHP_EOL;


      // Delete Rule Modal
      $modal_args = array(
        'id'    => 'modal-delete_syslog_rule_' . $la['la_id'],
        'title' => 'Delete Syslog Rule "'.escape_html($la['la_descr']).'"',
        //'hide'  => TRUE,
        //'fade'  => TRUE,
        //'role'  => 'dialog',
        //'class' => 'modal-md',
      );

      $form = array('type'      => 'horizontal',
                    'id'        => 'delete_syslog_rule_' . $la['la_id'],
                    'userlevel'  => 10,          // Minimum user level for display form
                    'modal_args' => $modal_args, // !!! This generate modal specific form
                    //'help'     => 'This will completely delete the rule and all associations and history.',
                    'class'     => '', // Clean default box class!
                    'url'       => generate_url(array('page' => 'syslog_rules'))
                    );
      $form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
      $form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

      $form['row'][0]['la_id'] = array(
                                      'type'        => 'hidden',
                                      'fieldset'    => 'body',
                                      'value'       => $la['la_id']);
      $form['row'][0]['action']     = array(
                                        'type'        => 'hidden',
                                        'fieldset'    => 'body',
                                        'value'       => 'delete_syslog_rule');

      $form['row'][5]['confirm'] = array(
                                      'type'        => 'checkbox',
                                      'fieldset'    => 'body',
                                      'name'        => 'Confirm',
                                      'placeholder' => 'Yes, please delete this rule.',
                                      'onchange'    => "javascript: toggleAttrib('disabled', 'delete_button_".$la['la_id']."'); showDiv(!this.checked, 'warning_".$la['la_id']."_div');",
                                      'value'       => 'confirm');
      $form['row'][6]['warning_'.$la['la_id']] = array(
                                      'type'        => 'html',
                                      'fieldset'    => 'body',
                                      'html'        => '<h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>' .
                                                       ' This rule and all history will be completely deleted!',
                                      'div_class'   => 'alert alert-warning',
                                      'div_style'   => 'display:none;');

      $form['row'][8]['close'] = array(
                                      'type'        => 'submit',
                                      'fieldset'    => 'footer',
                                      'div_class'   => '', // Clean default form-action class!
                                      'name'        => 'Close',
                                      'icon'        => '',
                                      'attribs'     => array('data-dismiss' => 'modal',
                                                             'aria-hidden'  => 'true'));
      $form['row'][9]['delete_button_'.$la['la_id']] = array(
                                      'type'        => 'submit',
                                      'fieldset'    => 'footer',
                                      'div_class'   => '', // Clean default form-action class!
                                      'name'        => 'Delete Rule',
                                      'icon'        => 'icon-trash icon-white',
                                      //'right'       => TRUE,
                                      'class'       => 'btn-danger',
                                      'disabled'    => TRUE,
                                      'value'       => 'delete_syslog_rule');

      $modals .= generate_form_modal($form);
      unset($form);

      // Edit Rule Modal

      $modal_args = array(
        'id'    => 'modal-edit_syslog_rule_' . $la['la_id'],
        'title' => 'Edit Syslog Rule "'.escape_html($la['la_descr']).'"',
        //'hide'  => TRUE,
        //'fade'  => TRUE,
        //'role'  => 'dialog',
        'class' => 'modal-lg',
      );

      $form = array('type'      => 'horizontal',
                    'id'        => 'edit_syslog_rule_' . $la['la_id'],
                    'userlevel'  => 10,          // Minimum user level for display form
                    'modal_args' => $modal_args, // !!! This generate modal specific form
                    //'help'     => 'This will completely delete the rule and all associations and history.',
                    'class'     => '', // Clean default box class!
                    'url'       => generate_url(array('page' => 'syslog_rules'))
                    );
      $form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
      $form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

      $form['row'][0]['la_id'] = array(
                                      'type'        => 'hidden',
                                      'fieldset'    => 'body',
                                      'value'       => $la['la_id']);

      $form['row'][3]['la_name'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'body',
                                      'name'        => 'Rule Name',
                                      'class'       => 'input-xlarge',
                                      'value'       => escape_html($la['la_name']));
      $form['row'][4]['la_descr'] = array(
                                      'type'        => 'textarea',
                                      'fieldset'    => 'body',
                                      'name'        => 'Description',
                                      'class'       => 'input-xxlarge',
                                      //'style'       => 'margin-bottom: 10px;',
                                      'value'       => escape_html($la['la_descr']));
      $form['row'][5]['la_rule'] = array(
                                      'type'        => 'textarea',
                                      'fieldset'    => 'body',
                                      'name'        => 'Regular Expression',
                                      'class'       => 'input-xxlarge',
                                      'value'       => escape_html($la['la_rule']));
      $form['row'][6]['la_disable'] = array(
                                      'type'        => 'switch-ng',
                                      'fieldset'    => 'body',
                                      'name'        => 'Status',
                                      'on-text'     => 'Disabled',
                                      'on-color'    => 'danger',
                                      'off-text'    => 'Enabled',
                                      'off-color'   => 'success',
                                      'size'        => 'small',
                                      'value'       => $la['la_disable']);

      $form['row'][8]['close'] = array(
                                      'type'        => 'submit',
                                      'fieldset'    => 'footer', 
                                      'div_class'   => '', // Clean default form-action class!
                                      'name'        => 'Close',
                                      'icon'        => '',
                                      'attribs'     => array('data-dismiss' => 'modal',
                                                             'aria-hidden'  => 'true'));
      $form['row'][9]['action'] = array(
                                      'type'        => 'submit',
                                      'fieldset'    => 'footer',
                                      'div_class'   => '', // Clean default form-action class!
                                      'name'        => 'Save Changes',
                                      'icon'        => 'icon-ok icon-white',
                                      //'right'       => TRUE,
                                      'class'       => 'btn-primary',
                                      'value'       => 'edit_syslog_rule');

      $modals .= generate_form_modal($form);
      unset($form);

    }

    $string .= '</table>';
    $string .= generate_box_close();

    echo $string;

  } else {

    print_warning("There are currently no Syslog alerting filters defined.");

  }

  echo $modals;

}

if (isset($vars['la_id']))
{
  // Pagination
  $vars['pagination'] = TRUE;

  print_logalert_log($vars);
}

register_html_title('Syslog Rules');

// EOF
