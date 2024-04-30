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

// Contact display and editing page.

if ($_SESSION['userlevel'] < 7) {
    print_error_permission();
    return;
}

include($config['html_dir'] . '/includes/alerting-navbar.inc.php');
include($config['html_dir'] . '/includes/contacts-navbar.inc.php');

?>

    <div class="row">
        <div class="col-sm-12">

            <?php

            // Hardcode Device sysContact
            if (!dbExist('alert_contacts', '`contact_method` = ?', [ 'syscontact' ])) {
                $syscontact = [
                  'contact_descr'    => 'Device sysContact',
                  'contact_method'   => 'syscontact',
                  'contact_endpoint' => '{"syscontact":"device"}',
                  //'contact_disabled'         => '0',
                  //'contact_disabled_until'   => NULL,
                  //'contact_message_custom'   => 0,
                  //'contact_message_template' => NULL
                ];
                dbInsert($syscontact, 'alert_contacts');
            }

            if (isset($vars['sort'])) {
                $sort_order = get_sort_order($vars);
                switch ($vars['sort']) {
                    case "id":
                    case "contact_id":
                        $sort = generate_query_sort('contact_id', $sort_order);
                        break;

                    case "transport":
                    case "method":
                        $sort = generate_query_sort('contact_method', $sort_order);
                        break;

                    case "description":
                        $sort = generate_query_sort('contact_descr', $sort_order);
                        break;

                    case "status":
                        $sort = generate_query_sort('contact_disabled', $sort_order);
                        break;

                    default:
                        $sort = generate_query_sort([ 'contact_method', 'contact_descr' ], $sort_order);
                }
            } else {
                $sort = generate_query_sort([ 'contact_method', 'contact_descr' ]);
            }
            //r($sort);

            $contacts = dbFetchRows('SELECT * FROM `alert_contacts`' . $sort);

            if (!safe_empty($contacts)) {
                //r($contacts);
                // We have contacts, print the table.
                echo generate_box_open();

                echo '                <table class="table table-condensed table-striped table-rounded table-hover">' . PHP_EOL;
                $cols   = [
                    [ NULL,                           'style' => 'width: 1px;' ],
                    [ 'id'          => 'Id',          'style' => 'width: 50px;' ],
                    [ 'transport'   => 'Transport',   'style' => 'width: 100px;' ],
                    [ 'description' => 'Description', 'style' => 'width: 100px;' ],
                    [ 'Destination' ],
                    [ 'Used',                         'style' => 'width: 50px;' ],
                    [ 'status'      => 'Status',      'style' => 'width: 70px;' ],
                    [ NULL,                           'style' => 'width: 70px;' ],
                ];
                echo  generate_table_header($cols, $vars);
                ?>

                    <tbody>

                    <?php

                    $modals = '';

                    foreach ($contacts as $contact) {
                        if ($contact['contact_method'] === 'syscontact' && $config['email']['default_syscontact']) {
                            $num_assocs = dbFetchCell('SELECT COUNT(*) FROM `alert_tests`') + 0;
                            $num_assocs += dbFetchCell('SELECT COUNT(*) FROM `syslog_rules`') + 0;
                        } else {
                            $num_assocs = dbFetchCell("SELECT COUNT(*) FROM `alert_contacts_assoc` WHERE `contact_id` = ?", [$contact['contact_id']]) + 0;
                        }

                        if ($contact['contact_disabled'] == 1) {
                            $disabled = "";
                        }

                        // If we have "identifiers" set for this type of transport, use those to print a user friendly destination.
                        // If we don't, just dump the JSON array as we don't have a better idea what to do right now.
                        $transport = $contact['contact_method'];
                        if (isset($config['transports'][$transport]['identifiers'])) {
                            // Decode JSON for use below
                            $contact['endpoint_variables'] = json_decode($contact['contact_endpoint'], TRUE);

                            // Add all identifier strings to an array and implode them into the description variable
                            // We can't just foreach the identifiers array as we don't know what section the variable is in
                            foreach ($config['transports'][$contact['contact_method']]['identifiers'] as $key) {
                                foreach ($config['transports'][$contact['contact_method']]['parameters'] as $section => $parameters) {
                                    if (isset($parameters[$key]) && isset($contact['endpoint_variables'][$key])) {
                                        $contact['endpoint_identifiers'][] = escape_html($parameters[$key]['description'] . ': ' . $contact['endpoint_variables'][$key]);
                                    }
                                }
                            }

                            $contact['endpoint_descr'] = implode('<br />', $contact['endpoint_identifiers']);
                        } else {
                            $contact['endpoint_descr'] = escape_html($contact['contact_endpoint']);
                        }

                        if ($transport === 'syscontact') {
                            $transport_name            = 'sysContact';
                            $transport_status          = $contact['contact_disabled'] ? '<span class="label label-error">disabled</span>' : '<span class="label label-success">enabled</span>';
                            $contact['endpoint_descr'] = 'Device specified contact in sysContact field (email only)';
                        } elseif (!isset($config['transports'][$transport])) {
                            // Transport undefined (removed or limited to Pro)
                            $transport_name   = nicecase($transport) . ' (Missing)';
                            $transport_status = '<span class="label">missing</span>';
                        } else {
                            $transport_name   = $config['transports'][$transport]['name'];
                            $transport_status = $contact['contact_disabled'] ? '<span class="label label-error">disabled</span>' : '<span class="label label-success">enabled</span>';
                        }
                        echo '    <tr>';
                        echo '      <td></td>';
                        echo '      <td>' . $contact['contact_id'] . '</td>';
                        echo '      <td><span class="label">' . $transport_name . '</span></td>';
                        echo '      <td class="text-nowrap">' . escape_html($contact['contact_descr']) . '</td>';
                        echo '      <td><a href="' . generate_url(['page' => 'contact', 'contact_id' => $contact['contact_id']]) . '">' . $contact['endpoint_descr'] . '</a></td>';
                        echo '      <td><span class="label label-primary">' . $num_assocs . '</span></td>';
                        echo '      <td>' . $transport_status . '</td>';
                        echo '      <td style="text-align: right;">';
                        if ($_SESSION['userlevel'] >= 10 && $transport !== 'syscontact') {
                            $buttons = [
                                [ 'title' => 'Edit',   'event' => 'default', 'url' => generate_url([ 'page' => 'contact' ], [ 'contact_id' => $contact['contact_id'] ]), 'icon' => 'icon-cog text-muted' ],
                                [ 'event' => 'danger',  'icon' => 'icon-trash',
                                  'url' => generate_url(['page' => 'contacts'], [ 'action' => 'contact_delete', 'contact_id' => $contact['contact_id'], 'confirm_'.$contact['contact_id'] => 'confirm', 'requesttoken' => $_SESSION['requesttoken'] ]),
                                  // confirmation dialog
                                  'attribs'   => [
                                      'data-title'     => 'Delete Contact ['.$transport_name.'] "'.escape_html($contact['contact_descr']).'"?',
                                      'data-toggle'    => 'confirm', // Enable confirmation dialog
                                      'data-placement' => 'left',
                                      'data-content'   => '<div class="alert alert-warning"><h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>
                                                 <span class="text-nowrap">Are you sure you want to delete<br />this contact?</span></div>',
                                  ],
                                ],
                            ];
                            echo PHP_EOL . generate_button_group($buttons, [ 'title' => 'Contact actions' ]);
                        }
                        echo '</td>';
                        echo '    </tr>';

                    }

                    ?>

                    </tbody>
                </table>

                <?php

                echo generate_box_close();

            } else {
                // We don't have contacts. Say so.
                print_warning("There are currently no contacts configured.");
            }


            ?>
        </div> <!-- col-sm-12 -->
    </div> <!-- row -->

<?php

// EOF
