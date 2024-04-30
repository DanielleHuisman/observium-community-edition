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

// Contact display and editing page.

if ($_SESSION['userlevel'] < 7) {
    // Allowed only secure global read permission
    print_error_permission();
    return;
}

include($config['html_dir'] . '/includes/alerting-navbar.inc.php');
include($config['html_dir'] . '/includes/contacts-navbar.inc.php');

if ($contact = get_contact_by_id($vars['contact_id'])) {

    ?>

    <div class="row">
    <div class="col-sm-7">
        <?php

        foreach (safe_json_decode($contact['contact_endpoint']) as $field => $value) {
            $contact['endpoint_parameters'][$field] = $value;
        }

        $transport = $contact['contact_method'];
        if ($transport === 'syscontact') {
            //$readonly = TRUE;
            $data = [
              'name' => 'sysContact',
              'docs' => $transport
            ];
        } elseif (isset($config['transports'][$transport])) {
            $data = $config['transports'][$transport];
        } else {
            $data = [
              'name' => nicecase($transport) . ' (Missing)',
              'docs' => $transport
            ];
        }

        if (isset($data['docs'])) {
            // Known key in docs page (use if transport name is different with docs page)
            $docs_link = OBSERVIUM_DOCS_URL . '/alerting_transports/#' . $data['docs'];
        } else {
            $docs_link = OBSERVIUM_DOCS_URL . '/alerting_transports/#' . str_replace(' ', '-', strtolower($data['name']));
        }

        if (!safe_count($data['parameters']['global'])) {
            $data['parameters']['global'] = [];
        } // Temporary until we separate "global" out.
        // Plan: add defaults for transport types to global settings, which we use by default, then be able to override the settings via this GUI
        // This needs supporting code in the transport to check for set variable and if not, use the global default

        $form                                  = ['type'  => 'horizontal',
                                                  'id'    => 'update_contact_status',
                                                  'title' => 'Contact Information',
                                                  'space' => '5px',
                                                  //'fieldset'  => array('edit' => ''),
        ];
        $row                                   = 0;
        $form['row'][++$row]['contact_method'] = [
          'type'      => 'html',
          //'fieldset'    => 'edit',
          'name'      => 'Transport Method',
          'class'     => 'label',
          'div_style' => 'padding-top: 5px;',
          'readonly'  => $readonly,
          'value'     => $data['name']];

        $form['row'][++$row]['contact_doc'] = [
          'type'     => 'html',
          'fieldset' => 'body',
          'offset'   => TRUE,
          'html'     => '<a id="contact_doc" href="' . $docs_link . '" target="_blank">See documentation for this Transport (new page)</a>'];

        $form['row'][++$row]['contact_enabled'] = [
          'type'      => 'switch-ng',
          //'fieldset'    => 'edit',
          'name'      => 'Contact Status',
          'size'      => 'small',
          'on-color'  => 'success',
          'off-color' => 'danger',
          'on-text'   => 'Enabled',
          'off-text'  => 'Disabled',
          'readonly'  => $readonly,
          'value'     => !$contact['contact_disabled']];

        $form['row'][++$row]['contact_descr'] = [
          'type'     => 'text',
          //'fieldset'    => 'edit',
          'name'     => 'Description',
          'width'    => '80%',
          'readonly' => $readonly || $transport === 'syscontact',
          'value'    => $contact['contact_descr']];

        if (safe_count($data['parameters']['required']) || safe_count($data['parameters']['global'])) {
            // Pseudo item, just for additional title
            $form['row'][++$row]['contact_required'] = [
              'type' => 'html',
              //'fieldset'    => 'edit',
              'html' => '<h3 id="contact_required">Required parameters</h3>'];

            foreach (array_merge($data['parameters']['required'], $data['parameters']['global']) as $parameter => $param_data) { // Temporary merge req & global
                switch ($param_data['type']) {
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
                        if (!isset($param_data['params'])) {
                            $param_data['params'] = ['' => 'Unset', 'true' => 'True', 'false' => 'False'];
                        }
                    // do not break here
                    case 'enum':
                        if (isset($contact['endpoint_parameters'][$parameter])) {
                            $value = $contact['endpoint_parameters'][$parameter];
                            if (isset($param_data['default']) &&
                                !(isset($param_data['params'][$value]) || in_array($value, (array)$param_data['params']))) {
                                $value = $param_data['default'];
                            }
                        } else {
                            $value = $param_data['default'];
                        }
                        $form_param = [
                          'type'     => 'select',
                          //'fieldset'    => 'edit',
                          'name'     => $param_data['description'],
                          'width'    => '80%',
                          'readonly' => $readonly,
                          'value'    => $value,
                          'values'   => $param_data['params']
                        ];
                        break;
                    case 'textarea':
                        $form_param = [
                          'type'     => 'textarea',
                          //'fieldset'    => 'edit',
                          'name'     => $param_data['description'],
                          'width'    => '80%',
                          'rows'     => 5,
                          'readonly' => $readonly,
                          'value'    => $contact['endpoint_parameters'][$parameter]
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
                          //'fieldset'    => 'edit',
                          'name'     => $param_data['description'],
                          'width'    => '80%',
                          'readonly' => $readonly,
                          'value'    => $contact['endpoint_parameters'][$parameter]
                        ];
                }
                $form['row'][++$row]['contact_endpoint_' . $parameter] = $form_param;

                if (isset($param_data['tooltip'])) {
                    if (isset($param_data['tooltip_url'])) {
                        $tooltip_url  = $param_data['tooltip_url'];
                        $tooltip_icon = 'info';
                    } else {
                        $tooltip_url  = NULL;
                        $tooltip_icon = 'question';
                    }
                    $form['row'][$row]['tooltip_' . $parameter] = [
                      'type'     => 'raw',
                      //'fieldset'    => 'edit',
                      'readonly' => $readonly,
                      'html'     => generate_tooltip_link($tooltip_url, get_icon($tooltip_icon), escape_html($param_data['tooltip']), NULL, ['target' => '_blank'])
                    ];
                }

            }
        }

        if (safe_count($data['parameters']['optional'])) {
            // Pseudo item, just for additional title
            $form['row'][++$row]['contact_optional'] = [
              'type' => 'html',
              //'fieldset'    => 'edit',
              'html' => '<h3 id="contact_optional">Optional parameters</h3>'
            ];

            foreach ($data['parameters']['optional'] as $parameter => $param_data) {
                switch ($param_data['type']) {
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
                        if (!isset($param_data['params'])) {
                            $param_data['params'] = ['' => 'Unset', 'true' => 'True', 'false' => 'False'];
                        }
                    // do not break here
                    case 'enum':
                        if (isset($contact['endpoint_parameters'][$parameter])) {
                            $value = $contact['endpoint_parameters'][$parameter];
                            if (isset($param_data['default']) &&
                                !(isset($param_data['params'][$value]) || in_array($value, (array)$param_data['params']))) {
                                $value = $param_data['default'];
                            }
                        } else {
                            $value = $param_data['default'];
                        }
                        $form_param = [
                          'type'     => 'select',
                          //'fieldset'    => 'edit',
                          'name'     => $param_data['description'],
                          'width'    => '80%',
                          'readonly' => $readonly,
                          'value'    => $value,
                          'values'   => $param_data['params']
                        ];
                        break;
                    case 'textarea':
                        $form_param = [
                          'type'     => 'textarea',
                          //'fieldset'    => 'edit',
                          'name'     => $param_data['description'],
                          'width'    => '80%',
                          'rows'     => 5,
                          'readonly' => $readonly,
                          'value'    => $contact['endpoint_parameters'][$parameter]
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
                          //'fieldset'    => 'edit',
                          'name'     => $param_data['description'],
                          'width'    => '80%',
                          'readonly' => $readonly,
                          'value'    => $contact['endpoint_parameters'][$parameter]
                        ];
                }
                $form['row'][++$row]['contact_endpoint_' . $parameter] = $form_param;

                if (isset($param_data['tooltip'])) {
                    if (isset($param_data['tooltip_url'])) {
                        $tooltip_url  = $param_data['tooltip_url'];
                        $tooltip_icon = 'info';
                    } else {
                        $tooltip_url  = NULL;
                        $tooltip_icon = 'question';
                    }
                    $form['row'][$row]['tooltip_' . $parameter] = [
                      'type'     => 'raw',
                      //'fieldset'    => 'edit',
                      'readonly' => $readonly,
                      'html'     => generate_tooltip_link($tooltip_url, get_icon($tooltip_icon), escape_html($param_data['tooltip']), NULL, ['target' => '_blank'])
                    ];
                }

            }
        }

        // User defined templates
        $message_template = '';
        $message_custom   = isset($contact['contact_message_custom']) && $contact['contact_message_custom'];
        if ($message_custom) {
            // user defined
            $message_template = $contact['contact_message_template'];
        } elseif (isset($data['notification']['message_template'])) {
            // file-based template
            // template can have tags (ie telegram)
            if (str_contains($data['notification']['message_template'], '%')) {
                //print_vars($data['notification']['message_template']);
                $template = array_tag_replace(generate_transport_tags($transport, $contact['endpoint_parameters']), $data['notification']['message_template']);
                $template = strtolower($template);
                //print_vars($template);
            } else {
                $template = $data['notification']['message_template'];
            }
            $message_template = get_template('notification', $template);

            // remove own comments
            $message_template = preg_replace('!^\s*/\*[\*\s]+Observium\s.*?\*/(\s*\n)?!is', '', $message_template);
        } elseif (isset($data['notification']['message_text'])) {
            // definition-based template
            $message_template = $data['notification']['message_text'];
        }
        if (strlen($message_template)) {
            // Pseudo item, just for additional title
            $form['row'][++$row]['message_title'] = [
              'type' => 'html',
              //'fieldset'    => 'edit',
              'html' => '<h3 id="message_title">Notification parameters</h3>'
            ];

            $form['row'][++$row]['contact_message_custom']   = [
              'type'        => 'toggle',
              'name'        => 'Custom template',
              'view'        => 'toggle',
              'size'        => 'large',
              'placeholder' => 'Set custom message, using Mustache formatting.',// : [Notification templates]('.OBSERVIUM_DOCS_URL.'/xxx/){target=_blank}',
              //'width'       => '250px',
              'readonly'    => $readonly,
              'onchange'    => "toggleAttrib('disabled', 'contact_message_template');",
              'value'       => $message_custom
            ];
            $form['row'][++$row]['contact_message_template'] = [
              'type'     => 'textarea',
              //'fieldset'    => 'edit',
              'name'     => 'Template',
              'rows'     => 6,
              'class'    => 'text-monospace small',
              //'style'       => 'font-size: 12px;',
              'width'    => '500px',
              //'placeholder' => '1-30. Default 10.',
              'readonly' => $readonly,
              'disabled' => !$message_custom,
              'value'    => $message_template
            ];
        }

        $form['row'][++$row]['action'] = [
          'type'     => 'submit',
          'name'     => 'Save Changes',
          'icon'     => 'icon-ok icon-white',
          'right'    => TRUE,
          'class'    => 'btn-primary',
          'readonly' => $readonly,
          'value'    => 'contact_edit'
        ];

        //print_vars($form);
        print_form($form);
        unset($form, $row);
        ?>

    </div>

    <div class="col-sm-5">

    <?php

    // Alert associations
    $assoc_exists = [];
    if ($transport === 'syscontact' && $config['email']['default_syscontact']) {
        $assocs = dbFetchRows('SELECT * FROM `alert_tests`
                           ORDER BY `entity_type`, `alert_name` DESC');
    } else {
        $assocs = dbFetchRows('SELECT * FROM `alert_contacts_assoc` AS A
                           LEFT JOIN `alert_tests` AS T ON T.`alert_test_id` = A.`alert_checker_id`
                           WHERE `aca_type` = ? AND `contact_id` = ?
                           ORDER BY `entity_type`, `alert_name` DESC', ['alert', $contact['contact_id']]);
    }
    //r($assocs);
    echo generate_box_open(['title' => 'Associated Alert Checkers', 'header-border' => TRUE]);
    if (safe_count($assocs)) {

        echo('<table class="' . OBS_CLASS_TABLE_STRIPED . '">');

        foreach ($assocs as $assoc) {

            $alert_test = get_alert_test_by_id($assoc['alert_test_id']);

            $assoc_exists[$assoc['alert_test_id']] = TRUE;

            echo('<tr>
                  <td width="150px"><i class="' . $config['entities'][$alert_test['entity_type']]['icon'] . '"></i> ' . nicecase($alert_test['entity_type']) . '</td>
                  <td>' . escape_html($alert_test['alert_name']) . '</td>
                  <td width="25px">');

            $form = [
                'type'  => 'simple',
                //'userlevel'  => 10,          // Minimum user level for display form
                'id'    => 'delete_alert_checker_' . $assoc['alert_test_id'],
                'style' => 'display:inline;',
            ];
            $form['row'][0]['alert_test_id'] = [
              'type'  => 'hidden',
              'value' => $assoc['alert_test_id']
            ];
            $form['row'][0]['contact_id']    = [
              'type'  => 'hidden',
              'value' => $contact['contact_id']
            ];
            $form['row'][99]['action'] = [
              'type'      => 'submit',
              //'icon_only' => TRUE, // hide button styles
              'name'      => '',
              'icon'      => 'icon-trash',
              'readonly'  => $transport === 'syscontact',
              'class'     => 'btn-xs btn-danger',
              // confirmation dialog
              'attribs'   => ['data-toggle'            => 'confirm', // Enable confirmation dialog
                              'data-confirm-placement' => 'left',
                              'data-confirm-content'   => 'Delete associated checker "' . escape_html($alert_test['alert_name']) . '"?',
                              //'data-confirm-content' => '<div class="alert alert-warning"><h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>
                              //                           This association will be deleted!</div>'),
              ],
              'value'     => 'contact_alert_checker_delete'
            ];

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

    if (safe_count($alert_tests)) {
        foreach ($alert_tests as $alert_test) {
            if (!isset($assoc_exists[$alert_test['alert_test_id']])) {
                $form_items['alert_checker_id'][$alert_test['alert_test_id']] = [
                  'name' => $alert_test['alert_name'],
                  'icon' => $config['entities'][$alert_test['entity_type']]['icon']
                ];
            }
        }

        $form = [
            'type'  => 'simple',
            //'userlevel'  => 10,          // Minimum user level for display form
            'id'    => 'associate_alert_check',
            'style' => 'padding: 7px; margin: 0px;',
            'right' => TRUE,
        ];
        $form['row'][0]['type'] = [
          'type'  => 'hidden',
          'value' => 'alert'
        ];
        $form['row'][0]['alert_checker_id'] = [
          'type'        => 'select',
          'name'        => 'Associate Alert Checker',
          'live-search' => FALSE,
          'width'       => '250px',
          //'right'       => TRUE,
          'readonly'    => $readonly,
          'values'      => $form_items['alert_checker_id'],
          'value'       => $vars['alert_checker_id']
        ];
        $form['row'][0]['action'] = [
          'type'     => 'submit',
          'name'     => 'Associate',
          'icon'     => 'icon-plus-sign',
          //'right'       => TRUE,
          'readonly' => $readonly,
          'class'    => 'btn-primary',
          'value'    => 'contact_alert_checker_add'
        ];

        $box_close['footer_content']   = generate_form($form);
        $box_close['footer_nopadding'] = TRUE;
        unset($form, $form_items);

    } else {
        // print_warning('No unassociated alert checkers.');
    }

    echo generate_box_close($box_close);

    // Syslog associations
    $assoc_exists = [];
    if ($transport === 'syscontact' && $config['email']['default_syscontact']) {
        $assocs = dbFetchRows('SELECT * FROM `syslog_rules`
                           ORDER BY `la_severity`, `la_name` DESC');
    } else {
        $assocs = dbFetchRows('SELECT * FROM `alert_contacts_assoc` AS A
                         LEFT JOIN `syslog_rules` AS T ON T.`la_id` = A.`alert_checker_id`
                         WHERE `aca_type` = ? AND `contact_id` = ?
                         ORDER BY `la_severity`, `la_name` DESC', [ 'syslog', $contact['contact_id'] ]);
    }
    //r($assocs);
    echo generate_box_open(['title' => 'Associated Syslog Rules', 'header-border' => TRUE]);
    if (safe_count($assocs)) {

        echo('<table class="' . OBS_CLASS_TABLE_STRIPED . '">');

        foreach ($assocs as $assoc) {

            //$alert_test = get_alert_test_by_id($assoc['alert_checker_id']);

            $assoc_exists[$assoc['la_id']] = TRUE;

            echo('<tr>
                <td width="150">' . get_icon('syslog-alerts') . ' ' . escape_html($assoc['la_name']) . '</td>
                <td>' . escape_html($assoc['la_rule']) . '</td>
                <td width="25">');

            $form = [
                'type'  => 'simple',
                //'userlevel'  => 10,          // Minimum user level for display form
                'id'    => 'delete_syslog_checker_' . $assoc['la_id'],
                'style' => 'display:inline;',
            ];

            $form['row'][0]['la_id'] = [
              'type'  => 'hidden',
              'value' => $assoc['la_id']
            ];
            $form['row'][0]['contact_id']    = [
              'type'  => 'hidden',
              'value' => $contact['contact_id']
            ];
            $form['row'][99]['action'] = [
                'type'      => 'submit',
                //'icon_only' => TRUE, // hide button styles
                'name'      => '',
                'icon'      => 'icon-trash',
                'readonly'  => $transport === 'syscontact',
                'class'     => 'btn-xs btn-danger',
                // confirmation dialog
                'attribs'   => ['data-toggle'            => 'confirm', // Enable confirmation dialog
                                'data-confirm-placement' => 'left',
                                'data-confirm-content'   => 'Delete associated rule "' . escape_html($assoc['la_name']) . '"?',
                                //'data-confirm-content' => '<div class="alert alert-warning"><h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>
                                //                           This association will be deleted!</div>'),
                ],
                'value'     => 'contact_syslog_rule_delete'
            ];

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

    if (safe_count($alert_tests)) {
        foreach ($alert_tests as $alert_test) {
            if (!isset($assoc_exists[$alert_test['la_id']])) {
                $form_items['la_id'][$alert_test['la_id']] = [
                  'name'    => $alert_test['la_name'],
                  'subtext' => $alert_test['la_rule'],
                  'icon'    => $config['icon']['syslog-alerts']
                ];
            }
        }

        $form = [
            'type'  => 'simple',
            //'userlevel'  => 10,          // Minimum user level for display form
            'id'    => 'contact_syslog_rule_add',
            'style' => 'padding: 7px; margin: 0px;',
            'right' => TRUE,
        ];
        $form['row'][0]['type']   = [
            'type'  => 'hidden',
            'value' => 'syslog'
        ];
        $form['row'][0]['la_id']  = [
            'type'        => 'select',
            'name'        => 'Associate Syslog Rule',
            'live-search' => FALSE,
            'width'       => '250px',
            //'right'       => TRUE,
            'readonly'    => $readonly,
            'values'      => $form_items['la_id'],
            'value'       => $vars['la_id']
        ];
        $form['row'][0]['action'] = [
            'type'     => 'submit',
            'name'     => 'Associate',
            'icon'     => 'icon-plus-sign',
            //'right'       => TRUE,
            'readonly' => $readonly,
            'class'    => 'btn-primary',
            'value'    => 'contact_syslog_rule_add'
        ];

        $box_close['footer_content']   = generate_form($form);
        $box_close['footer_nopadding'] = TRUE;
        unset($form, $form_items);

    } //else { print_warning('No unassociated syslog rules.'); }

    echo generate_box_close($box_close);

    echo('</div>');
} else {
    print_error("Contact doesn't exist.");
}

// EOF
