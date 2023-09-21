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

if (!is_array($alert_rules)) {
    $alert_rules = cache_alert_rules();
}

$navbar['class'] = 'navbar-narrow';
$navbar['brand'] = 'Contacts';

$pages = ['contacts' => 'Contact List'];

foreach ($pages as $page_name => $page_desc) {
    if ($vars['page'] == $page_name) {
        $navbar['options'][$page_name]['class'] = "active";
    }

    $navbar['options'][$page_name]['url']  = generate_url(['page' => $page_name]);
    $navbar['options'][$page_name]['text'] = escape_html($page_desc);
}

$navbar['options_right']['add']['url']       = '#modal-add_contact';
$navbar['options_right']['add']['link_opts'] = 'data-toggle="modal"';
$navbar['options_right']['add']['text']      = 'Add Contact';
$navbar['options_right']['add']['icon']      = $config['icon']['contact-add'];
$navbar['options_right']['add']['userlevel'] = 10;

// Print out the navbar defined above
print_navbar($navbar);
unset($navbar);

/* Begin Add contact */

/*
$modal_args = array(
  'id'    => 'modal-add_contact',
  'title' => 'Add New Contact',
  'icon'  => 'oicon-sql-join-inner',
  //'hide'  => TRUE,
  //'fade'  => TRUE,
  //'role'  => 'dialog',
  //'class' => 'modal-md',
);
*/

$form = [
  'type'      => 'horizontal',
  'userlevel' => 10,          // Minimum user level for display form
  'id'        => 'modal-add_contact',
  'title'     => 'Add New Contact',
  'icon'      => 'oicon-sql-join-inner',
  //'modal_args' => [ 'class' => 'modal-lg' ], // !!! This generate modal specific form
  //'class'      => '',          // Clean default box class!
  'url'       => 'contacts/'
];
//$form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
//$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

$row                                   = 0;
$form_params                           = [];
$form['row'][++$row]['contact_method'] = [
  'type'     => 'select',
  'fieldset' => 'body',
  'name'     => 'Transport',
  'width'    => '270px',
  //'live-search' => FALSE,
  //'values'      => $form_params['method'],
  'value'    => 'email'
];
$row_tmp                               = $row; // Store row number
foreach (array_keys($config['transports']) as $transport) {
    $form_params['method'][$transport] = $config['transports'][$transport]['name'];

    if (isset($config['transports'][$transport]['docs'])) {
        // Known key in docs page (use if transport name is different with docs page)
        $docs_link = OBSERVIUM_DOCS_URL . '/alerting_transports/#' . $config['transports'][$transport]['docs'];
    } else {
        $docs_link = OBSERVIUM_DOCS_URL . '/alerting_transports/#' . str_replace(' ', '-', strtolower($config['transports'][$transport]['name']));
    }
    $form['row'][++$row]['contact_' . $transport . '_doc'] = [
      'type'     => 'html',
      'fieldset' => 'body',
      'offset'   => TRUE,
      'html'     => '<a id="contact_' . $transport . '_doc" href="' . $docs_link . '" target="_blank">See documentation for this Transport (new page)</a>'
    ];
}
if (is_array($form_params['method'])) {
    asort($form_params['method']);
}
$form['row'][$row_tmp]['contact_method']['values'] = $form_params['method'];

$form['row'][++$row]['contact_descr'] = [
  'type'     => 'text',
  'fieldset' => 'body',
  'name'     => 'Description',
  'class'    => 'input-xlarge',
  'value'    => ''
];

foreach ($config['transports'] as $transport => $data) {
    $row++;
    if (safe_count($data['parameters']['required']) || safe_count($data['parameters']['global'])) {
        $form['row'][$row]['contact_' . $transport . '_required'] = [
          'type'     => 'html',
          'fieldset' => 'body',
          'html'     => '<h3 id="contact_' . $transport . '_required">Required parameters</h3>'
        ];
        $row++;

        // Temporary merge req & global
        foreach (array_merge((array)$data['parameters']['required'], (array)$data['parameters']['global']) as $parameter => $param_data) {

            switch ($param_data['type']) {
                case 'enum-freeinput':
                    $form_param = [
                      'type'     => 'tags',
                      'fieldset' => 'body',
                      'name'     => $param_data['description'],
                      'width'    => '270px',// '100%',
                      'value'    => isset($param_data['default']) ? $param_data['default'] : '',
                      'values'   => $param_data['params']
                    ];
                    break;
                case 'bool':
                case 'boolean':
                    // Boolean type is just select with true/false string
                    if (!isset($param_data['params'])) {
                        $param_data['params'] = ['' => 'Unset', 'true' => 'True', 'false' => 'False'];
                    }
                // do not break here
                case 'enum':
                    $form_param = [
                      'type'     => 'select',
                      'fieldset' => 'body',
                      'name'     => $param_data['description'],
                      'width'    => '270px', //'100%',
                      'value'    => isset($param_data['default']) ? $param_data['default'] : '',
                      'values'   => $param_data['params']
                    ];
                    break;
                case 'textarea':
                    $form_param = [
                      'type'     => 'textarea',
                      'fieldset' => 'body',
                      'name'     => $param_data['description'],
                      'width'    => '270px',
                      'rows'     => 5,
                      'value'    => isset($param_data['default']) ? $param_data['default'] : ''
                    ];
                    // Prettify JSON
                    if (isset($param_data['format']) && $param_data['format'] === 'json' &&
                        $json = safe_json_decode($form_param['value'])) {
                        $form_param['value'] = safe_json_encode($json, JSON_PRETTY_PRINT);
                    }
                    break;
                default:
                    $form_param = [
                      'type'     => 'text',
                      'fieldset' => 'body',
                      'name'     => $param_data['description'],
                      'class'    => 'input-xlarge',
                      'value'    => isset($param_data['default']) ? $param_data['default'] : ''
                    ];
            }
            $form['row'][$row]['contact_' . $transport . '_' . $parameter] = $form_param;

            if (isset($param_data['tooltip'])) {
                //r($param_data);
                if (isset($param_data['tooltip_url'])) {
                    $tooltip_url  = $param_data['tooltip_url'];
                    $tooltip_icon = 'info';
                } else {
                    $tooltip_url  = NULL;
                    $tooltip_icon = 'question';
                }
                $form['row'][$row]['contact_' . $transport . '_tooltip'] = [
                  'type'     => 'html',
                  'fieldset' => 'body',
                  'html'     => generate_tooltip_link($tooltip_url, get_icon($tooltip_icon), escape_html($param_data['tooltip']), NULL, ['target' => '_blank'])
                ];
            }

            $row++;
        }
    }

    if (safe_count($data['parameters']['optional'])) {
        $form['row'][$row]['contact_' . $transport . '_optional'] = [
          'type'     => 'html',
          'fieldset' => 'body',
          'html'     => '<h3 id="contact_' . $transport . '_optional">Optional parameters</h3>'
        ];
        $row++;

        foreach ($data['parameters']['optional'] as $parameter => $param_data) {

            switch ($param_data['type']) {
                case 'enum-freeinput':
                    $form_param = [
                      'type'     => 'tags',
                      'fieldset' => 'body',
                      'name'     => $param_data['description'],
                      'width'    => '270px',// '100%',
                      //'value'    => isset($param_data['default']) ? $param_data['default'] : '',
                      'value'    => isset($param_data['default']) ? $param_data['default'] : '',
                      'values'   => $param_data['params']
                    ];
                    break;
                case 'bool':
                case 'boolean':
                    // Boolean type is just select with true/false string
                    if (!isset($param_data['params'])) {
                        $param_data['params'] = ['' => 'Unset', 'true' => 'True', 'false' => 'False'];
                    }
                // do not break here
                case 'enum':
                    $form_param = [
                      'type'     => 'select',
                      'fieldset' => 'body',
                      'name'     => $param_data['description'],
                      'width'    => '270px', //'100%',
                      'value'    => isset($param_data['default']) ? $param_data['default'] : '',
                      'values'   => $param_data['params']
                    ];
                    break;
                case 'textarea':
                    $form_param = [
                      'type'     => 'textarea',
                      'fieldset' => 'body',
                      'name'     => $param_data['description'],
                      'width'    => '270px',
                      'rows'     => 5,
                      'value'    => isset($param_data['default']) ? $param_data['default'] : ''
                    ];
                    // Prettify JSON
                    if (isset($param_data['format']) && $param_data['format'] === 'json' &&
                        $json = safe_json_decode($form_param['value'])) {
                        $form_param['value'] = safe_json_encode($json, JSON_PRETTY_PRINT);
                    }
                    break;
                default:
                    $form_param = [
                      'type'     => 'text',
                      'fieldset' => 'body',
                      'name'     => $param_data['description'],
                      'class'    => 'input-xlarge',
                      'value'    => isset($param_data['default']) ? $param_data['default'] : ''
                    ];
            }
            $form['row'][$row]['contact_' . $transport . '_' . $parameter] = $form_param;

            if (isset($param_data['tooltip'])) {
                if (isset($param_data['tooltip_url'])) {
                    $tooltip_url  = $param_data['tooltip_url'];
                    $tooltip_icon = 'info';
                } else {
                    $tooltip_url  = NULL;
                    $tooltip_icon = 'question';
                }
                $form['row'][$row]['contact_' . $transport . '_tooltip'] = [
                  'type'     => 'html',
                  'fieldset' => 'body',
                  'html'     => generate_tooltip_link($tooltip_url, get_icon($tooltip_icon), escape_html($param_data['tooltip']), NULL, ['target' => '_blank'])
                ];
            }
            $row++;
        }
    }

    // Custom notification templates if allowed for transport
    // if (isset($data['notification']['message_template'])) {
    //   $form['row'][$row]['contact_' . $transport . '_notification'] = array(
    //     'type'        => 'html',
    //     'fieldset'    => 'body',
    //     'html'        => '<h3 id="contact_' . $transport . '_optional">Notification parameters</h3>');
    //   $row++;
    //
    //   $form_param = [
    //     'type'     => 'toggle',
    //     'view'     => 'toggle',
    //     'size'     => 'large',
    //     'palette'  => 'blue',
    //     'fieldset' => 'body',
    //     'name'     => 'Use custom template',
    //     'onchange' => "toggleAttrib('disabled', 'contact_" . $transport . "_message_template')",
    //     'value'    => 'off'
    //   ];
    //   $form['row'][$row]['contact_' . $transport . '_custom_template'] = $form_param;
    //   $row++;
    //
    //   $form['row'][$row++]['contact_' . $transport . '_doc_mustache'] = array(
    //     'type'        => 'html',
    //     'fieldset'    => 'body',
    //     'offset'      => TRUE,
    //     'html'        => 'See <a href="https://mustache.github.com/mustache.5.html" target="_blank">Mustache templates syntax</a>');
    //
    //   $template = get_template('notification', $data['notification']['message_template']);
    //   // Remove header comment(s)
    //   $template = preg_replace('!^\s*/\*[\*\s]+Observium\s.*?\*/\s!is', '', $template);
    //   $form_param = [
    //     'type'     => 'textarea',
    //     'fieldset' => 'body',
    //     'disabled' => TRUE,
    //     'name'     => 'Message template',
    //     'class'    => 'input-xlarge',
    //     'rows'     => 6,
    //     'value'    => $template
    //   ];
    //   $form['row'][$row]['contact_' . $transport . '_message_template'] = $form_param;
    //   $row++;
    // }
}

$form['row'][$row]['close']  = [
  'type'      => 'submit',
  'fieldset'  => 'footer',
  'div_class' => '', // Clean default form-action class!
  'name'      => 'Close',
  'icon'      => '',
  'attribs'   => ['data-dismiss' => 'modal',
                  'aria-hidden'  => 'true']
];
$form['row'][$row]['action'] = [
  'type'      => 'submit',
  'fieldset'  => 'footer',
  'div_class' => '', // Clean default form-action class!
  'name'      => 'Add Contact',
  'icon'      => 'icon-ok icon-white',
  //'right'       => TRUE,
  'class'     => 'btn-primary',
  'value'     => 'add_contact'
];

echo generate_form_modal($form);
unset($form, $form_params);

$script = '
$("#contact_method").change(function() {
  var select = this.value;
';

// Generate javascript function which hides all configuration part panels except the ones for the currently chosen transport
// Alternative would be to hide them all, then unhide the one selected. Hmm...
$count = 0;
foreach (array_keys($config['transports']) as $transport) {
    if ($count == 0) {
        $script .= "  if (select === '" . $transport . "') {" . PHP_EOL;
    } else {
        $script .= PHP_EOL . "  } else if (select === '" . $transport . "') {" . PHP_EOL;
    }
    $script .= "    \$('div[id^=\"contact_{$transport}_\"]').show();" . PHP_EOL . "   ";
    foreach (array_keys($config['transports']) as $ltransport) {
        if ($transport != $ltransport) {
            $script .= " \$('div[id^=\"contact_{$ltransport}_\"]').hide();";
        }
    }

    $count++;
}
$script .= '  }
}).change();';

register_html_resource('script', $script);

// End add contact

// Begin Actions
$readonly = $_SESSION['userlevel'] < 10;       // Currently, edit allowed only for Admins

if (!$readonly && isset($vars['action'])) {
    switch ($vars['action']) {
        case 'add_contact':
            // Only proceed if the contact_method is valid in our transports array
            if (is_array($config['transports'][$vars['contact_method']])) {
                foreach ($config['transports'][$vars['contact_method']]['parameters'] as $section => $parameters) {
                    foreach ($parameters as $parameter => $param_data) {
                        if (isset($vars['contact_' . $vars['contact_method'] . '_' . $parameter])) {

                            $value = smart_quotes($vars['contact_' . $vars['contact_method'] . '_' . $parameter]);
                            // Validate if passed correct JSON
                            if ($param_data['format'] === 'json') {
                                safe_json_decode($value);
                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    // Incorrect JSON
                                    print_error('Contact not added. Incorrect JSON.');
                                    break 2;
                                }
                            }
                            $endpoint_data[$parameter] = $value;
                        }
                    }
                }

                if ($endpoint_data) {
                    dbInsert('alert_contacts', ['contact_descr' => $vars['contact_descr'], 'contact_endpoint' => safe_json_encode($endpoint_data), 'contact_method' => $vars['contact_method']]);
                }
            }
            break;

        case 'edit_contact':
        case 'update_contact':
            $update_state = [];
            $contact      = get_contact_by_id($vars['contact_id']);

            foreach (safe_json_decode($contact['contact_endpoint']) as $field => $value) {
                $contact['endpoint_parameters'][$field] = $value;
            }

            $update_state['contact_disabled'] = get_var_true($vars['contact_enabled']) ? 0 : 1;

            if (!safe_empty($vars['contact_descr']) && $vars['contact_descr'] != $contact['contact_descr']) {
                $update_state['contact_descr'] = $vars['contact_descr'];
            }

            $data = $config['transports'][$contact['contact_method']];
            if (!safe_count($data['parameters']['global'])) {
                // Temporary until we separate "global" out.
                $data['parameters']['global'] = [];
            }
            if (!safe_count($data['parameters']['optional'])) {
                $data['parameters']['optional'] = [];
            }
            // Plan: add defaults for transport types to global settings, which we use by default, then be able to override the settings via this GUI
            // This needs supporting code in the transport to check for set variable and if not, use the global default

            $update_endpoint = $contact['endpoint_parameters'];
            foreach (array_merge((array)$data['parameters']['required'],
                                 (array)$data['parameters']['global'],
                                 (array)$data['parameters']['optional']) as $parameter => $param_data) {
                if ((isset($data['parameters']['optional'][$parameter]) || // Allow optional param as empty
                     is_array($vars['contact_endpoint_' . $parameter]) || strlen($vars['contact_endpoint_' . $parameter])) &&
                    smart_quotes($vars['contact_endpoint_' . $parameter]) != $contact['endpoint_parameters'][$parameter]) {

                    $value = smart_quotes($vars['contact_endpoint_' . $parameter]);
                    // Validate if passed correct JSON
                    if ($param_data['format'] === 'json') {
                        //r($value);
                        //r($param_data);
                        safe_json_decode($value);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            // Incorrect JSON
                            print_error('Contact not updated. Incorrect JSON.');
                            break 2;
                        }
                    }
                    $update_endpoint[$parameter] = $value;
                }
            }
            //r($update_endpoint);
            $update_endpoint = safe_json_encode($update_endpoint);
            if ($update_endpoint != $contact['contact_endpoint']) {
                //r($update_endpoint);
                //r($contact['contact_endpoint']);
                $update_state['contact_endpoint'] = $update_endpoint;
            }

            // custom template
            $vars['contact_message_custom'] = get_var_true($vars['contact_message_custom']);
            if ($vars['contact_message_custom'] != (bool)$contact['contact_message_custom']) {
                $update_state['contact_message_custom'] = $vars['contact_message_custom'] ? '1' : '0';
            }
            if ($vars['contact_message_custom'] && $vars['contact_message_template'] != $contact['contact_message_template']) {
                $update_state['contact_message_template'] = $vars['contact_message_template'];
            }
            //r($contact);
            //r($vars);

            $rows_updated = dbUpdate($update_state, 'alert_contacts', 'contact_id = ?', [$vars['contact_id']]);
            break;

        case 'delete_contact':
            if (get_var_true($vars['confirm_' . $vars['contact_id']], 'confirm')) {
                $rows_deleted = dbDelete('alert_contacts', '`contact_id` = ?', [$vars['contact_id']]);
                $rows_deleted += dbDelete('alert_contacts_assoc', '`contact_id` = ?', [$vars['contact_id']]);

                if ($rows_deleted) {
                    print_success('Deleted contact and all associations (' . $vars['contact_id'] . ')');
                }
            }
            unset($vars['contact_id']);
            break;

    }
}

// End Actions

// EOF
