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

include_once($config['install_dir'] . '/includes/polling/functions.inc.php');
include_once($config['install_dir'] . '/includes/discovery/functions.inc.php');

$ports_ignored_count = (int)get_entity_attrib('device', $device, 'ports_ignored_count'); // Cache last ports ignored count
$ports_total_count   = $ports_ignored_count + dbFetchCell('SELECT COUNT(*) FROM `ports` WHERE `device_id` = ? AND `deleted` = ?', [$device['device_id'], 0]);

if ($vars['submit']) {
    if ($readonly) {
        print_error_permission('You have insufficient permissions to edit settings.');
    } else {
        if ($vars['toggle_poller'] && isset($config['poller_modules'][$vars['toggle_poller']])) {
            $module = $vars['toggle_poller'];
            if (isset($attribs['poll_' . $module]) && $attribs['poll_' . $module] != $config['poller_modules'][$module]) {
                del_dev_attrib($device, 'poll_' . $module);
            } elseif ($config['poller_modules'][$module] == 0) {
                set_dev_attrib($device, 'poll_' . $module, "1");
            } else {
                set_dev_attrib($device, 'poll_' . $module, "0");
            }
            $attribs = get_dev_attribs($device['device_id']);
        }

        if ($vars['toggle_ports'] && isset($config[$vars['toggle_ports']]) && str_starts_with($vars['toggle_ports'], 'enable_ports_')) {
            $module      = $vars['toggle_ports'];
            $module_name = str_replace('enable_', '', $module);

            del_entity_attrib('device', $device, $module);
            $config_val = is_module_enabled($device, $module_name, 'poller'); // Note, it's also with attrib check!

            if ($vars['submit'] === 'Disable' && $attribs[$module] !== '0') {
                //set_dev_attrib($device, $module, "0");
                set_entity_attrib('device', $device, $module, '0');
            } elseif (isset($attribs[$module]) && $attribs[$module] != $config_val) {
                //del_dev_attrib($device, $module);
            } elseif ($config_val == 0) {
                //set_dev_attrib($device, $module, "1");
                set_entity_attrib('device', $device, $module, '1');
            } else {
                //set_dev_attrib($device, $module, "0");
                set_entity_attrib('device', $device, $module, '0');
            }
            $attribs = get_entity_attribs('device', $device);
        }

        if ($vars['toggle_discovery'] && isset($config['discovery_modules'][$vars['toggle_discovery']])) {
            $module = $vars['toggle_discovery'];
            if (isset($attribs['discover_' . $module]) && $attribs['discover_' . $module] != $config['discovery_modules'][$module]) {
                del_dev_attrib($device, 'discover_' . $module);
            } elseif ($config['discovery_modules'][$module] == 0) {
                set_dev_attrib($device, 'discover_' . $module, "1");
            } else {
                set_dev_attrib($device, 'discover_' . $module, "0");
            }
            $attribs = get_dev_attribs($device['device_id']);
        }
    }
}

//r($device_state);
?>

    <div class="row"> <!-- begin row -->

        <div class="col-md-6"> <!-- begin poller options -->

            <div class="box box-solid">

                <div class="box-header with-border">
                    <h3 class="box-title">Poller Modules</h3>
                </div>
                <div class="box-body no-padding">

                    <table class="table table-striped table-condensed">
                        <thead>
                        <tr>
                            <th>Module</th>
                            <th style="width: 60px;">Last Poll</th>
                            <th style="width: 60px;">Global</th>
                            <th style="width: 60px;">Device</th>
                            <th style="width: 80px;"></th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php
                        foreach (array_merge(['os' => 1, 'system' => 1], $config['poller_modules']) as $module => $module_status) {
                            //$module_status = is_module_enabled($device, $module, 'poller');
                            $attrib_set = isset($attribs['poll_' . $module]);

                            // Last module poll time and row class
                            $module_row_class = '';
                            if (!isset($device_state['poller_mod_perf'][$module])) {
                                $module_time = '--';
                            } elseif ($device_state['poller_mod_perf'][$module] < 0.01) {
                                $module_time = $device_state['poller_mod_perf'][$module] . 's';
                            } else {
                                $module_time = format_value($device_state['poller_mod_perf'][$module]) . 's';

                                if ($device_state['poller_mod_perf'][$module] > 10) {
                                    $module_row_class = 'error';
                                } elseif ($device_state['poller_mod_perf'][$module] > 3) {
                                    $module_row_class = 'warning';
                                }
                            }

                            $attrib_status = '<span class="label label-important">disabled</span>';
                            $toggle        = 'Enable';
                            $btn_class     = 'btn-success';
                            $btn_icon      = 'icon-ok';
                            $disabled      = FALSE;
                            if ($module === 'os' || $module === 'system') {
                                $attrib_status = '<span class="label label-default">locked</span>';
                                $toggle        = "Locked";
                                $btn_class     = '';
                                $btn_icon      = 'icon-lock';
                                $disabled      = TRUE;
                            } elseif (poller_module_excluded($device, $module)) {
                                $attrib_status = '<span class="label label-default">excluded</span>';
                                $toggle        = "Excluded";
                                $btn_class     = '';
                                $btn_icon      = 'icon-lock';
                                $disabled      = TRUE;
                            } elseif (is_module_enabled($device, $module, 'poller')) {
                                $attrib_status = '<span class="label label-success">enabled</span>';
                                $toggle        = "Disable";
                                $btn_class     = "btn-danger";
                                $btn_icon      = 'icon-remove';
                            } elseif ($module_status) {
                                // highlight disabled module on device
                                $module_row_class = 'suppressed';
                            }


                            echo('<tr class="' . $module_row_class . '"><td><strong>' . $module . '</strong></td>');
                            echo('<td>' . $module_time . '</td><td>');
                            echo(($module_status ? '<span class="label label-success">enabled</span>' : '<span class="label label-important">disabled</span>'));
                            echo('</td><td>');
                            echo($attrib_status . '</td><td>');

                            $form = ['type' => 'simple'];
                            // Elements
                            $form['row'][0]['toggle_poller'] = ['type'  => 'hidden',
                                                                'value' => $module];
                            $form['row'][0]['submit']        = ['type'     => 'submit',
                                                                'name'     => $toggle,
                                                                'class'    => 'btn-mini ' . $btn_class,
                                                                'icon'     => $btn_icon,
                                                                'right'    => TRUE,
                                                                'readonly' => $readonly,
                                                                'disabled' => $disabled,
                                                                'value'    => 'Toggle'];
                            print_form($form);
                            unset($form);

                            echo('</td></tr>');
                        }
                        ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div> <!-- end poller options -->

        <div class="col-md-6"> <!-- begin ports options -->

            <div class="box box-solid">

                <div class="box-header with-border">
                    <h3 class="box-title">Ports polling options</h3>
                </div>
                <div class="box-body no-padding">

                    <table class="table table-striped table-condensed">
                        <thead>
                        <tr>
                            <th>Module</th>
                            <th style="width: 60px;">Last Poll</th>
                            <th style="width: 60px;">Global</th>
                            <th style="width: 60px;">Device</th>
                            <th style="width: 80px;"></th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php
                        foreach (array_keys($config) as $module) {
                            if (!str_starts($module, 'enable_ports_')) {
                                continue;
                            }

                            $attrib_set    = isset($attribs[$module]);
                            $module_name   = str_replace('enable_ports_', '', $module);
                            $module_status = is_module_enabled($device, 'ports_' . $module_name, 'poller');

                            // Last ports module poll time and row class
                            $module_row_class = '';
                            if ($module_name === 'separate_walk' || $module_name === '64bit') {
                                $module_time = ''; // nothing to show for this pseudo-module
                            } elseif (!isset($device_state['poller_ports_perf'][$module_name])) {
                                $module_time = '--';
                            } elseif ($device_state['poller_ports_perf'][$module_name] < 0.01) {
                                $module_time = $device_state['poller_ports_perf'][$module_name] . 's';
                            } else {
                                $module_time = format_value($device_state['poller_ports_perf'][$module_name]) . 's';

                                if ($device_state['poller_ports_perf'][$module_name] > 10) {
                                    $module_row_class = 'error';
                                } elseif ($device_state['poller_ports_perf'][$module_name] > 3) {
                                    $module_row_class = 'warning';
                                }
                            }

                            echo('<tr class="' . $module_row_class . '"><td><strong>' . $module_name . '</strong></td>');
                            echo('<td>' . $module_time . '</td><td>');
                            echo(($module_status ? '<span class="label label-success">enabled</span>' : '<span class="label label-important">disabled</span>'));
                            echo('</td><td>');

                            $attrib_status = '<span class="label label-important">disabled</span>';
                            $toggle        = 'Enable';
                            $btn_class     = 'btn-success';
                            $btn_icon      = 'icon-ok';
                            $value         = 'Toggle';
                            $disabled      = FALSE;
                            if ($module === 'enable_ports_junoseatmvp' && $device['os'] !== 'junose') { /// FIXME. see here includes/discovery/junose-atm-vp.inc.php
                                $attrib_status = '<span class="label label-default">excluded</span>';
                                $toggle        = "Excluded";
                                $btn_class     = '';
                                $btn_icon      = 'icon-lock';
                                $disabled      = TRUE;
                            } elseif (($attrib_set && $attribs[$module]) || (!$attrib_set && $module_status)) {
                                $attrib_status = '<span class="label label-success">enabled</span>';
                                $toggle        = "Disable";
                                $btn_class     = "btn-danger";
                                $btn_icon      = 'icon-remove';
                            } elseif ($module === 'enable_ports_separate_walk' && !$attrib_set) {
                                // Model definition can override os definition
                                $model_separate_walk = isset($model['ports_separate_walk']) && $model['ports_separate_walk'];
                                if ($model_separate_walk && $ports_total_count > 10) {
                                    $attrib_status = '<span class="label label-warning">FORCED</span>';
                                    $toggle        = "Disable";
                                    $btn_class     = "btn-danger";
                                    $btn_icon      = 'icon-remove';
                                    $value         = 'Disable';
                                } elseif ((int)$device['state']['poller_mod_perf']['ports'] < 20 && $ports_total_count <= 10) {
                                    $attrib_status = '<span class="label label-default">excluded</span>';
                                    $toggle        = "Excluded";
                                    $btn_class     = '';
                                    $btn_icon      = 'icon-lock';
                                    $disabled      = TRUE;
                                }
                            }

                            echo($attrib_status . '</td><td>');

                            $form = ['type' => 'simple'];
                            // Elements
                            $form['row'][0]['toggle_ports'] = ['type'  => 'hidden',
                                                               'value' => $module];
                            $form['row'][0]['submit']       = ['type'     => 'submit',
                                                               'name'     => $toggle,
                                                               'class'    => 'btn-mini ' . $btn_class,
                                                               'icon'     => $btn_icon,
                                                               'right'    => TRUE,
                                                               'readonly' => $readonly,
                                                               'disabled' => $disabled,
                                                               'value'    => $value];
                            print_form($form);
                            unset($form);

                            echo('</td></tr>');
                        }
                        ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div> <!-- end ports options -->

        <div class="col-md-6"> <!-- begin discovery options -->

            <div class="box box-solid">

                <div class="box-header with-border">
                    <h3 class="box-title">Discovery Modules</h3>
                </div>
                <div class="box-body no-padding">

                    <table class="table table-striped table-condensed">
                        <thead>
                        <tr>
                            <th>Module</th>
                            <th style="width: 60px;">Last</th>
                            <th style="width: 60px;">Global</th>
                            <th style="width: 60px;">Device</th>
                            <th style="width: 80px;"></th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php
                        foreach ($config['discovery_modules'] as $module => $module_status) {
                            //$module_status = is_module_enabled($device, $module, 'discovery');
                            $attrib_set = isset($attribs['discover_' . $module]);

                            // Last module discovery time and row class
                            $module_row_class = '';
                            if (!isset($device_state['discovery_mod_perf'][$module])) {
                                $module_time = '--';
                            } elseif ($device_state['discovery_mod_perf'][$module] < 0.01) {
                                $module_time = $device_state['discovery_mod_perf'][$module] . 's';
                            } else {
                                $module_time = format_value($device_state['discovery_mod_perf'][$module]) . 's';

                                if ($device_state['discovery_mod_perf'][$module] > 10) {
                                    $module_row_class = 'error';
                                } elseif ($device_state['discovery_mod_perf'][$module] > 3) {
                                    $module_row_class = 'warning';
                                }
                            }

                            echo('<tr class="' . $module_row_class . '"><td><strong>' . $module . '</strong></td>');
                            echo('<td>' . $module_time . '</td><td>');
                            echo(($module_status ? '<span class="label label-success">enabled</span>' : '<span class="label label-important">disabled</span>'));
                            echo('</td><td>');

                            $attrib_status = '<span class="label label-important">disabled</span>';
                            $toggle        = 'Enable';
                            $btn_class     = 'btn-success';
                            $btn_icon      = 'icon-ok';
                            $disabled      = FALSE;

                            if (in_array($module, (array)$config['os'][$device['os']]['discovery_blacklist'])) {
                                $attrib_status = '<span class="label label-disabled">excluded</span>';
                                $toggle        = "Excluded";
                                $btn_class     = '';
                                $btn_icon      = 'icon-lock';
                                $disabled      = TRUE;
                            } elseif (is_module_enabled($device, $module, 'discovery')) {
                                $attrib_status = '<span class="label label-success">enabled</span>';
                                $toggle        = "Disable";
                                $btn_class     = "btn-danger";
                                $btn_icon      = 'icon-remove';
                            }

                            echo($attrib_status . '</td><td>');

                            $form = ['type' => 'simple'];
                            // Elements
                            $form['row'][0]['toggle_discovery'] = ['type'  => 'hidden',
                                                                   'value' => $module];
                            $form['row'][0]['submit']           = ['type'     => 'submit',
                                                                   'name'     => $toggle,
                                                                   'class'    => 'btn-mini ' . $btn_class,
                                                                   'icon'     => $btn_icon,
                                                                   'right'    => TRUE,
                                                                   'readonly' => $readonly,
                                                                   'disabled' => $disabled,
                                                                   'value'    => 'Toggle'];
                            print_form($form);
                            unset($form);

                            echo('</td></tr>');
                        }
                        ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div> <!-- end discovery options -->

    </div> <!-- end row -->
<?php

// EOF
