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

// Print permission error and exit if the user doesn't have write permissions
if (!is_entity_write_permitted($device['device_id'], 'device')) {
    print_error_permission();
    return;
}

?>

    <div class="row">
    <div class="col-md-12">

        <?php

        $box_args = ['title'         => 'Duration',
                     'header-border' => TRUE,
                     'padding'       => TRUE,
        ];

        $box_args['header-controls'] = ['controls' => ['perf' => ['text'   => 'Performance Data',
                                                                  //'icon' => 'icon-trash',
                                                                  'anchor' => TRUE,
                                                                  'url'    => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'perf']),
        ]]];

        echo generate_box_open($box_args);

        $ptime  = array_values($device['state']['poller_history'])[0]; // note for self: PHP 5.4+
        $pstart = array_keys($device['state']['poller_history'])[0];

        echo "Last Polled: <b>" . format_unixtime($pstart) . '</b> (took ' . $ptime . 's) - <a href="' . generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'perf']) . '">Details</a>';

        $dtime  = array_values($device['state']['discovery_history'])[0]; // note for self: PHP 5.4+
        $dstart = array_keys($device['state']['discovery_history'])[0];

        echo "<p>Last discovered: <b>" . format_unixtime($dstart) . '</b> (took ' . $dtime . 's) - <a href="' . generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'perf']) . '">Details</a></p>';

        echo generate_box_close();

        $box_args = ['title'         => 'RANCID',
                     'header-border' => TRUE,
                     'padding'       => TRUE,
        ];

        $box_args['header-controls'] = ['controls' => ['perf' => ['text'   => 'Show Config',
                                                                  'anchor' => TRUE,
                                                                  'url'    => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'showconfig']),
        ]]];

        echo generate_box_open($box_args);

        if (safe_count($config['rancid_configs'])) {
            $device_config_file = get_rancid_filename($device['hostname'], 1);

            echo('<p />');

            if ($device_config_file) {
                print_success("Configuration file for device was found; will be displayed to users with level 7 or higher.");
            } elseif (!isset($config['os'][$device['os']]['rancid'])) {
                print_warning("Configuration file for device was not found.");
            } else {
                print_warning("Os not supported by RANCID.");
            }
        } else {
            print_warning("No RANCID directories configured.");
        }

        echo generate_box_close();

        $box_args = [
          'title'         => 'UNIX Agent',
          'header-border' => TRUE,
          'padding'       => TRUE,
        ];
        // show for allowed module
        if (is_module_enabled($device, 'unix-agent', 'poller')) {
            $box_args['header-controls'] = [
              'controls' => [
                'perf' => ['text' => 'Show Applications', 'anchor' => TRUE,
                           'url'  => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'apps'])]
              ]
            ];
            echo generate_box_open($box_args);
            echo '<pre>';
            if ($unixagent = get_dev_attrib($device, 'unixagent_raw')) {
                if ($tmp = str_decompress($unixagent)) {
                    // New compressed format
                    $unixagent = $tmp;
                    unset($tmp);
                }
                echo escape_html($unixagent);
            }
            echo '</pre>';
        } else {
            echo generate_box_open($box_args);
            print_warning("Unix-agent disabled for os.");
        }
        echo generate_box_close();

        $box_args = ['title'         => 'Smokeping',
                     'header-border' => TRUE,
                     'padding'       => TRUE,
        ];

        $box_args['header-controls'] = ['controls' => ['perf' => ['text'   => 'Show Latency',
                                                                  'anchor' => TRUE,
                                                                  'url'    => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'latency']),
        ]]];

        echo generate_box_open($box_args);

        if (isset($config['smokeping']['dir']) && is_dir($config['smokeping']['dir'])) {
            $smokeping_files = get_smokeping_files(1);

            echo('<p />');

            if ($smokeping_files['incoming'][$device['hostname']]) {
                print_success("RRD for incoming latency found.");
            } else {
                print_error("RRD for incoming latency not found.");
            }

            if ($smokeping_files['outgoing'][$device['hostname']]) {
                print_success("RRD for outgoing latency found.");
            } else {
                print_error("RRD for outgoing latency not found.");
            }
        } else {
            print_warning("No Smokeping directory configured.");
        }

        echo generate_box_close();

        $box_args = ['title'         => 'Device Graphs',
                     'header-border' => TRUE,
                     //'padding' => TRUE,
        ];

        $box_args['header-controls'] = ['controls' => ['perf' => ['text'   => 'Show Graphs',
                                                                  'anchor' => TRUE,
                                                                  'url'    => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'graphs']),
        ]]];

        echo generate_box_open($box_args);

        echo('<table class="' . OBS_CLASS_TABLE_STRIPED_MORE . '">');

        ?>
        <thead>
        <tr>
            <th>Graph Type</th>
            <th style="width: 80px;">Has File</th>
            <th style="width: 80px;">Has Array</th>
            <th style="width: 80px;">Enabled</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($device['graphs'] as $graph_entry) {
            echo('<tr><td>' . $graph_entry['graph'] . '</td>');

            if (is_file('includes/graphs/device/' . $graph_entry['graph'] . '.inc.php')) {
                echo('<td><i class="icon-ok-sign green"></i></td>');
            } else {
                echo('<td><i class="icon-remove-sign red"></i></td>');
            }

            if (is_array($config['graph_types']['device'][$graph_entry['graph']])) {
                echo('<td><i class="icon-ok-sign green"></i></td>');
            } else {
                echo('<td><i class="icon-remove-sign red"></i></td>');
            }

            if ($graph_entry['enabled']) {
                echo('<td><i class="icon-ok-sign green"></i></td>');
            } else {
                echo('<td><i class="icon-remove-sign red"></i></td>');
            }

            echo('<td>' . print_r($config['graph_types']['device'][$graph_entry['graph']], TRUE) . '</td>');

            echo('</tr>');
        }
        ?>
        </tbody>
        </table>
        <?php

        echo generate_box_close();

        echo generate_box_open();
        print_vars($device);
        echo generate_box_close();
        ?>
    </div>
<?php

// EOF
