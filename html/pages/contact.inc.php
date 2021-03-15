<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

// Contact display and editing page.

if ($_SESSION['userlevel'] < 7)
{
  // Allowed only secure global read permission
  print_error_permission();
  return;
}

include($config['html_dir'].'/includes/alerting-navbar.inc.php');

include($config['html_dir'].'/includes/contacts-navbar.inc.php');

if ($contact = get_contact_by_id($vars['contact_id']))
{

?>

<div class="row">
  <div class="col-sm-6">
<?php

  foreach (json_decode($contact['contact_endpoint']) as $field => $value)
  {
    $contact['endpoint_parameters'][$field] = $value;
  }

  $transport = $contact['contact_method'];
  if (isset($config['transports'][$transport]))
  {
    $data = $config['transports'][$transport];
  } else {
    $data = [ 'name' => nicecase($transport) . ' (Missing)',
              'docs' => $transport];
  }

    if (isset($data['docs']))
    {
      // Known key in docs page (use if transport name is different with docs page)
      $docs_link = OBSERVIUM_DOCS_URL . '/alerting_transports/#' . $data['docs'];
    } else {
      $docs_link = OBSERVIUM_DOCS_URL . '/alerting_transports/#' . str_replace(' ', '-', strtolower($data['name']));
    }

    if (!count($data['parameters']['global']))   { $data['parameters']['global'] = array(); } // Temporary until we separate "global" out.
    // Plan: add defaults for transport types to global settings, which we use by default, then be able to override the settings via this GUI
    // This needs supporting code in the transport to check for set variable and if not, use the global default

    $form = array('type'      => 'horizontal',
                  'id'        => 'update_contact_status',
                  'title'     => 'Contact Information',
                  'space'     => '5px',
                  //'fieldset'  => array('edit' => ''),
                  );
    $row = 0;
    $form['row'][++$row]['contact_method'] = array(
                                    'type'        => 'html',
                                    //'fieldset'    => 'edit',
                                    'name'        => 'Transport Method',
                                    'class'       => 'label',
                                    'div_style'   => 'padding-top: 5px;',
                                    'readonly'    => $readonly,
                                    'value'       => $data['name']);

    $form['row'][++$row]['contact_doc'] = array(
                                    'type'        => 'html',
                                    'fieldset'    => 'body',
                                    'offset'      => TRUE,
                                    'html'        => '<a id="contact_doc" href="' . $docs_link . '" target="_blank">See documentation for this Transport (new page)</a>');

    $form['row'][++$row]['contact_enabled'] = array(
                                    'type'        => 'switch',
                                    //'fieldset'    => 'edit',
                                    'name'        => 'Contact Status',
                                    'size'        => 'small',
                                    'on-color'    => 'success',
                                    'off-color'   => 'danger',
                                    'on-text'     => 'Enabled',
                                    'off-text'    => 'Disabled',
                                    'readonly'    => $readonly,
                                    'value'       => !$contact['contact_disabled']);

    $form['row'][++$row]['contact_descr'] = array(
                                    'type'        => 'text',
                                    //'fieldset'    => 'edit',
                                    'name'        => 'Description',
                                    'width'       => '80%',
                                    'readonly'    => $readonly,
                                    'value'       => $contact['contact_descr']);

  if (count($data['parameters']['required']) || count($data['parameters']['global']))
  {
    // Pseudo item, just for additional title
    $form['row'][++$row]['contact_required'] = array(
                                    'type'        => 'html',
                                    //'fieldset'    => 'edit',
                                    'html'        => '<h3 id="contact_required">Required parameters</h3>');

    foreach (array_merge($data['parameters']['required'], $data['parameters']['global']) as $parameter => $param_data) // Temporary merge req & global
    {
      switch($param_data['type'])
      {
        case 'enum-freeinput':
          $form_param = [
            'type'     => 'tags',
            //'fieldset'    => 'edit',
            'name'     => $param_data['description'],
            'width'    => '100%',
            'readonly' => $readonly,
            'value'    => $contact['endpoint_parameters'][$parameter],
            'values'   => $param_data['params']
          ];
          break;
        case 'bool':
        case 'boolean':
          // Boolean type is just select with true/false string
          if (!isset($param_data['params']))
          {
            $param_data['params'] = ['' => 'Unset', 'true' => 'True', 'false' => 'False' ];
          }
        // do not break here
        case 'enum':
          $form_param = [
            'type'     => 'select',
            //'fieldset'    => 'edit',
            'name'     => $param_data['description'],
            'width'    => '80%',
            'readonly' => $readonly,
            'value'    => $contact['endpoint_parameters'][$parameter],
            'values'   => $param_data['params']
          ];
          break;
        default:
          $form_param = [
            'type'     => 'text',
            //'fieldset'    => 'edit',
            'name'     => $param_data['description'],
            'width'    => '80%',
            'readonly' => $readonly,
            'value'    => $contact['endpoint_parameters'][$parameter]
          ];
      }
      $form['row'][++$row]['contact_endpoint_'.$parameter] = $form_param;

      if (isset($param_data['tooltip']))
      {
        $form['row'][$row]['tooltip_'.$parameter] = array(
                                    'type'        => 'raw',
                                    //'fieldset'    => 'edit',
                                    'readonly'    => $readonly,
                                    'html'        => generate_tooltip_link(NULL, '<i class="'.$config['icon']['question'].'"></i>', $param_data['tooltip']));
      }

    }
  }

  if (count($data['parameters']['optional']))
  {
    // Pseudo item, just for additional title
    $form['row'][++$row]['contact_optional'] = array(
                                    'type'        => 'html',
                                    //'fieldset'    => 'edit',
                                    'html'        => '<h3 id="contact_optional">Optional parameters</h3>');

    foreach ($data['parameters']['optional'] as $parameter => $param_data)
    {
      switch($param_data['type'])
      {
        case 'enum-freeinput':
          $form_param = [
            'type'     => 'tags',
            //'fieldset'    => 'edit',
            'name'     => $param_data['description'],
            'width'    => '100%',
            'readonly' => $readonly,
            'value'    => $contact['endpoint_parameters'][$parameter],
            'values'   => $param_data['params']
          ];
          break;
        case 'bool':
        case 'boolean':
          // Boolean type is just select with true/false string
          if (!isset($param_data['params']))
          {
            $param_data['params'] = ['' => 'Unset', 'true' => 'True', 'false' => 'False' ];
          }
        // do not break here
        case 'enum':
          $form_param = [
            'type'     => 'select',
            //'fieldset'    => 'edit',
            'name'     => $param_data['description'],
            'width'    => '80%',
            'readonly' => $readonly,
            'value'    => $contact['endpoint_parameters'][$parameter],
            'values'   => $param_data['params']
          ];
          break;
        default:
          $form_param = [
            'type'     => 'text',
            //'fieldset'    => 'edit',
            'name'     => $param_data['description'],
            'width'    => '80%',
            'readonly' => $readonly,
            'value'    => $contact['endpoint_parameters'][$parameter]
          ];
      }
      $form['row'][++$row]['contact_endpoint_'.$parameter] = $form_param;

      if (isset($param_data['tooltip']))
      {
        $form['row'][$row]['tooltip_'.$parameter] = array(
                                    'type'        => 'raw',
                                    //'fieldset'    => 'edit',
                                    'readonly'    => $readonly,
                                    'html'        => generate_tooltip_link(NULL, '<i class="'.$config['icon']['question'].'"></i>', $param_data['tooltip']));
      }

    }
  }

  $form['row'][++$row]['action'] = array(
                                  'type'        => 'submit',
                                  'name'        => 'Save Changes',
                                  'icon'        => 'icon-ok icon-white',
                                  'right'       => TRUE,
                                  'class'       => 'btn-primary',
                                  'readonly'    => $readonly,
                                  'value'       => 'update_contact');

    //print_vars($form);
    print_form($form);
    unset($form, $row);
?>

  </div>

 <div class="col-sm-6">

<?php

    // Alert associations
    $assoc_exists = array();
    $assocs = dbFetchRows('SELECT * FROM `alert_contacts_assoc` AS A
                           LEFT JOIN `alert_tests` AS T ON T.`alert_test_id` = A.`alert_checker_id`
                           WHERE `aca_type` = ? AND `contact_id` = ?
                           ORDER BY `entity_type`, `alert_name` DESC', array('alert', $contact['contact_id']));
    //r($assocs);
    echo generate_box_open(array('title' => 'Associated Alert Checkers', 'header-border' => TRUE));
    if (count($assocs))
    {

      echo('<table class="'. OBS_CLASS_TABLE_STRIPED .'">');

      foreach ($assocs as $assoc)
      {

        $alert_test = get_alert_test_by_id($assoc['alert_checker_id']);

        $assoc_exists[$assoc['alert_checker_id']] = TRUE;

        echo('<tr>
                  <td width="150px"><i class="'.$config['entities'][$alert_test['entity_type']]['icon'].'"></i> '.nicecase($alert_test['entity_type']).'</td>
                  <td>'.escape_html($alert_test['alert_name']).'</td>
                  <td width="25px">');

        $form = array('type'       => 'simple',
                      //'userlevel'  => 10,          // Minimum user level for display form
                      'id'         => 'delete_alert_checker_'.$assoc['alert_checker_id'],
                      'style'      => 'display:inline;',
                     );
        $form['row'][0]['alert_test_id'] = array(
                                        'type'        => 'hidden',
                                        'value'       => $assoc['alert_checker_id']);
        $form['row'][0]['contact_id'] = array(
                                        'type'        => 'hidden',
                                        'value'       => $contact['contact_id']);

        $form['row'][99]['action'] = array(
                                        'type'        => 'submit',
                                        'icon_only'   => TRUE, // hide button styles
                                        'name'        => '',
                                        'icon'        => $config['icon']['cancel'],
                                        //'right'       => TRUE,
                                        //'class'       => 'btn-small',
                                        // confirmation dialog
                                        'attribs'     => array('data-toggle'            => 'confirm', // Enable confirmation dialog
                                                               'data-confirm-placement' => 'left',
                                                               'data-confirm-content'   => 'Delete associated checker "'.escape_html($alert_test['alert_name']).'"?',
                                                               //'data-confirm-content' => '<div class="alert alert-warning"><h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>
                                                               //                           This association will be deleted!</div>'),
                                                              ),
                                        'value'       => 'delete_alert_checker_contact');

        print_form($form);
        unset($form);

        echo('</td>
           </tr>');
      }

      echo('</table>');

    } else {
      echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This contact is not currently associated with any Alert Checkers</strong></p>');
    }

  // FIXME -- use NOT IN to mask already associated things.

  $alert_tests = dbFetchRows('SELECT * FROM `alert_tests` ORDER BY `entity_type`, `alert_name`');

  if (count($alert_tests))
  {
    foreach ($alert_tests as $alert_test)
    {
      if (!isset($assoc_exists[$alert_test['alert_test_id']]))
      {
        $form_items['alert_checker_id'][$alert_test['alert_test_id']] = array('name' => escape_html($alert_test['alert_name']),
                                                                              'icon' => $config['entities'][$alert_test['entity_type']]['icon']);
      }
    }

    $form = array('type'       => 'simple',
                  //'userlevel'  => 10,          // Minimum user level for display form
                  'id'         => 'associate_alert_check',
                  'style'      => 'padding: 7px; margin: 0px;',
                  'right'      => TRUE,
                  );
    $form['row'][0]['type'] = array(
                                      'type'        => 'hidden',
                                      'value'       => 'alert');
    $form['row'][0]['alert_checker_id'] = array(
                                      'type'        => 'select',
                                      'name'        => 'Associate Alert Checker',
                                      'live-search' => FALSE,
                                      'width'       => '250px',
                                      //'right'       => TRUE,
                                      'readonly'    => $readonly,
                                      'values'      => $form_items['alert_checker_id'],
                                      'value'       => $vars['alert_checker_id']);
    $form['row'][0]['action'] = array(
                                      'type'        => 'submit',
                                      'name'        => 'Associate',
                                      'icon'        => $config['icon']['plus'],
                                      //'right'       => TRUE,
                                      'readonly'    => $readonly,
                                      'class'       => 'btn-primary',
                                      'value'       => 'associate_alert_check');

    $box_close['footer_content'] = generate_form($form);
    $box_close['footer_nopadding'] = TRUE;
    unset($form, $form_items);

  } else {
    // print_warning('No unassociated alert checkers.');
  }

  echo generate_box_close($box_close);

  // Syslog associations
  $assoc_exists = array();
  $assocs = dbFetchRows('SELECT * FROM `alert_contacts_assoc` AS A
                         LEFT JOIN `syslog_rules` AS T ON T.`la_id` = A.`alert_checker_id`
                         WHERE `aca_type` = ? AND `contact_id` = ?
                         ORDER BY `la_severity`, `la_name` DESC', array('syslog', $contact['contact_id']));
  //r($assocs);
  echo generate_box_open(array('title' => 'Associated Syslog Rules', 'header-border' => TRUE));
  if (count($assocs))
  {

    echo('<table class="'. OBS_CLASS_TABLE_STRIPED .'">');

    foreach ($assocs as $assoc)
    {

      //$alert_test = get_alert_test_by_id($assoc['alert_checker_id']);

      $assoc_exists[$assoc['la_id']] = TRUE;

      echo('<tr>
                <td width="150"><i class="'.$config['icon']['syslog-alerts'].'"></i> '.escape_html($assoc['la_name']).'</td>
                <td>'.escape_html($assoc['la_rule']).'</td>
                <td width="25">');

      $form = array('type'       => 'simple',
                    //'userlevel'  => 10,          // Minimum user level for display form
                    'id'         => 'delete_syslog_checker_'.$assoc['la_id'],
                    'style'      => 'display:inline;',
                   );
      $form['row'][0]['alert_test_id'] = array(
                                      'type'        => 'hidden',
                                      'value'       => $assoc['la_id']);
      $form['row'][0]['contact_id'] = array(
                                      'type'        => 'hidden',
                                      'value'       => $contact['contact_id']);

      $form['row'][99]['action'] = array(
      //$form['row'][99]['submit'] = array(
                                      'type'        => 'submit',
                                      'icon_only'   => TRUE, // hide button styles
                                      'name'        => '',
                                      'icon'        => $config['icon']['cancel'],
                                      //'right'       => TRUE,
                                      //'class'       => 'btn-small',
                                      // confirmation dialog
                                      'attribs'     => array('data-toggle'            => 'confirm', // Enable confirmation dialog
                                                             'data-confirm-placement' => 'left',
                                                             'data-confirm-content'   => 'Delete associated rule "'.escape_html($assoc['la_name']).'"?',
                                                             //'data-confirm-content' => '<div class="alert alert-warning"><h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>
                                                             //                           This association will be deleted!</div>'),
                                                            ),
                                      'value'       => 'delete_syslog_checker_contact');

      print_form($form);
      unset($form);

      echo('</td>
           </tr>');

    }

    echo('</table>');

  } else {
    echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This contact is not currently associated with any Syslog Rules</strong></p>');
  }

  $alert_tests = dbFetchRows('SELECT * FROM `syslog_rules` ORDER BY `la_severity`, `la_name`');

  if (count($alert_tests))
  {
    foreach ($alert_tests as $alert_test)
    {
      if (!isset($assoc_exists[$alert_test['la_id']]))
      {
        $form_items['la_id'][$alert_test['la_id']] = array('name'    => escape_html($alert_test['la_name']),
                                                           'subtext' => escape_html($alert_test['la_rule']),
                                                           'icon'    => $config['icon']['syslog-alerts']);
      }
    }

    $form = array('type'       => 'simple',
                  //'userlevel'  => 10,          // Minimum user level for display form
                  'id'         => 'associate_syslog_rule',
                  'style'      => 'padding: 7px; margin: 0px;',
                  'right'      => TRUE,
                  );
    $form['row'][0]['type'] = array(
                                      'type'        => 'hidden',
                                      'value'       => 'syslog');
    $form['row'][0]['la_id'] = array(
                                      'type'        => 'select',
                                      'name'        => 'Associate Syslog Rule',
                                      'live-search' => FALSE,
                                      'width'       => '250px',
                                      //'right'       => TRUE,
                                      'readonly'    => $readonly,
                                      'values'      => $form_items['la_id'],
                                      'value'       => $vars['la_id']);
    $form['row'][0]['action'] = array(
                                      'type'        => 'submit',
                                      'name'        => 'Associate',
                                      'icon'        => $config['icon']['plus'],
                                      //'right'       => TRUE,
                                      'readonly'    => $readonly,
                                      'class'       => 'btn-primary',
                                      'value'       => 'associate_syslog_rule');

    $box_close['footer_content'] = generate_form($form);
    $box_close['footer_nopadding'] = TRUE;
    unset($form, $form_items);

  } else {
    // print_warning('No unassociated syslog rules.');
  }

  echo generate_box_close($box_close);

  echo('</div>');
} else {
  print_error("Contact doesn't exist.");
}

// EOF
