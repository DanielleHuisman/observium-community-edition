<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($_SESSION['userlevel'] < 7) {
    print_error_permission();
    return;
}

$mibs = [];

// Fetch defined MIBs
foreach ($config['mibs'] as $mib => $data) {
    if (isset($data['mib_dir'])) {
        $mibs[$mib] = 'def';
    }
}

// Fetch MIBs we support for specific OSes
foreach ($config['os'] as $os => $data) {
    foreach ($data['mibs'] as $mib) {
        if (!isset($mibs[$mib])) {
            $mibs[$mib] = 'os';
        }
    }
}

// Fetch all MIBs we support for specific OS groups
foreach ($config['os_group'] as $os => $data) {
    foreach ($data['mibs'] as $mib) {
        if (!isset($mibs[$mib])) {
            $mibs[$mib] = 'group';
        }
    }
}

ksort($mibs);

$defined_config = get_defined_settings(); // Used defined configs in config.php

// r($vars);

if ($vars['toggle_mib'] && isset($mibs[$vars['toggle_mib']]) &&
    !isset($defined_config['mibs'][$mib]['enable'])) { // Ignore if defined in config.php
    $mib = $vars['toggle_mib'];

    $mib_disabled = isset($config['mibs'][$mib]['enable']) && !$config['mibs'][$mib]['enable'];
    $set_mib      = $mib_disabled ? 1 : 0;

    $key = 'mibs|' . $mib . '|enable';
    set_sql_config($key, $set_mib);
    $config['mibs'][$mib]['enable'] = $set_mib; // one time override config var on page
}

print_message("This page allows you to globally disable individual MIBs. This configuration disables all discovery and polling using this MIB.");

?>

    <div class="row"> <!-- begin row -->

        <div class="col-md-12">

            <?php
            $box_args = ['title'         => 'Global MIB Configuration',
                         'header-border' => TRUE,
            ];

            echo generate_box_open($box_args);

            ?>


            <table class="table  table-striped table-condensed ">
                <thead>
                <tr>
                    <th>Module</th>
                    <th>Description</th>
                    <th></th>
                    <th style="width: 60px;">Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>

                <?php

                $db_config = dbFetchColumn('SELECT `config_key` FROM `config` WHERE `config_key` LIKE ?', ['mibs|%']);
                //r($db_config);
                foreach ($mibs as $mib => $data) {
                    $key     = 'mibs|' . $mib . '|enable';
                    $mib_set = in_array($key, $db_config);
                    $class   = $mib_set ? ' class="ignore"' : '';

                    echo('<tr' . $class . '><td><strong><a href="' . OBSERVIUM_MIBS_URL . '/' . $mib . '/" target="_blank">' . $mib . '</a></strong></td>');

                    if (isset($config['mibs'][$mib])) {
                        $descr = $config['mibs'][$mib]['descr'];
                    } else {
                        $descr = '';
                    }

                    /*
                    echo('<pre>

                    $mib = "'.$mib.'";
                    $config[\'mibs\'][ $mib ][\'mib_dir\'] = "";
                    $config[\'mibs\'][ $mib ][\'descr\']   = "";

                    </pre>');
                    */

                    echo '<td>' . $descr . '</td>';

                    // Highlight not defined MIBs
                    $label_class = $data != 'def' ? 'label label-warning' : 'label';
                    echo '<td><span class="' . $label_class . '">' . strtoupper($data) . '</span></td>';

                    echo '<td>';

                    $readonly    = FALSE;
                    $btn_value   = '';
                    $btn_tooltip = '';
                    if (isset($defined_config['mibs'][$mib]['enable']) && !$defined_config['mibs'][$mib]['enable']) {
                        // Disabled in config.php
                        $attrib_status = '<span class="label label-danger">disabled</span>';
                        $toggle        = 'Config';
                        $btn_class     = '';
                        $btn_tooltip   = 'Disabled in config.php, see: <mark>$config[\'mibs\'][\'' . $mib . '\'][\'enable\']</mark>';
                        $readonly      = TRUE;
                    } elseif (isset($config['mibs'][$mib]['enable']) && !$config['mibs'][$mib]['enable']) {
                        // Disabled in definitions or manually, can be re-enabled
                        $attrib_status = '<span class="label label-danger">disabled</span>';
                        $toggle        = 'Enable';
                        $btn_class     = 'btn-success';
                        $btn_value     = 'Toggle';
                    } else {
                        $attrib_status = '<span class="label label-success">enabled</span>';
                        $toggle        = 'Disable';
                        $btn_class     = 'btn-danger';
                    }

                    echo($attrib_status . '</td><td>');

                    $form = ['id'   => 'toggle_mib',
                             'type' => 'simple'];
                    // Elements
                    $form['row'][0]['toggle_mib'] = ['type'  => 'hidden',
                                                     'value' => $mib];
                    $form['row'][0]['submit']     = ['type'     => 'submit',
                                                     'name'     => $toggle,
                                                     'class'    => 'btn-mini ' . $btn_class,
                                                     'icon'     => '',
                                                     'tooltip'  => $btn_tooltip,
                                                     'right'    => TRUE,
                                                     'readonly' => $readonly,
                                                     'value'    => $btn_value];
                    print_form($form);
                    unset($form);

                    echo('</td></tr>');
                }
                ?>
                </tbody>
            </table>

            <?php echo generate_box_close(); ?>

            <?php

            echo "Total: " . count($mibs) . " MIBs";

            ?>

        </div> <!-- end row -->
    </div> <!-- end container -->

<?php

register_html_title('MIBs');

// EOF
