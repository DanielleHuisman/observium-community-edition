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

// Fetch all MIBs we support for this specific OS
$mibs = [];
foreach (get_device_mibs($device) as $mib) {
    $mibs[$mib]++;
}

// Sort alphabetically
ksort($mibs);

$mibs_disabled = get_device_mibs_disabled($device);

if ($vars['submit']) {
    if ($readonly) {
        print_error_permission('You have insufficient permissions to edit settings.');
    } else {
        if ($vars['toggle_mib'] && isset($mibs[$vars['toggle_mib']])) {
            $mib = $vars['toggle_mib'];

            $mib_disabled = in_array($mib, $mibs_disabled);
            if (!$config['mibs'][$mib]['enable']) {
                // Globally disabled MIB
                $where    = "`device_id` = ? AND `use` = ? AND `mib` = ?";
                $params   = [$device['device_id'], 'mib', $mib];
                $disabled = dbFetchCell("SELECT `disabled` FROM `devices_mibs` WHERE $where", $params);
                //r($disabled);
                $mib_disabled = $disabled !== '0';
                if ($mib_disabled) {
                    set_device_mib_enable($device, $mib);
                } else {
                    set_device_mib_disable($device, $mib, TRUE); // really just remove
                }
            } else {
                if ($mib_disabled) {
                    set_device_mib_enable($device, $mib, TRUE); // really just remove
                } else {
                    set_device_mib_disable($device, $mib);
                }
            }

            // reload attribs
            unset($cache['devices']['mibs_disabled'][$device['device_id']]);
            $mibs_disabled = get_device_mibs_disabled($device);
        }
    }
}

// Count critical errors into DB (only for poller)
$mib_grid    = 12;
$snmp_errors = [];
if ($config['snmp']['errors']) {
    //$poll_period = 300;
    $error_codes = $GLOBALS['config']['snmp']['errorcodes'];
    $poll_period = $GLOBALS['config']['rrd']['step'];

    $sql = 'SELECT * FROM `snmp_errors` WHERE `device_id` = ?;';
    foreach (dbFetchRows($sql, [$device['device_id']]) as $entry) {
        $timediff   = $entry['updated'] - $entry['added'];
        $poll_count = round(float_div($timediff, $poll_period)) + 1;

        $entry['error_rate'] = float_div($entry['error_count'], $poll_count); // calculate error rate
        if ($oid = str_decompress($entry['oid'])) {
            // 512 long oid strings is compressed
            $entry['oid'] = $oid;
        }
        $snmp_errors[$entry['mib']][] = $entry;
    }

    if (count($snmp_errors)) {
        ksort($snmp_errors);
        $mib_grid = 5;
    }
}

print_warning("This page allows you to disable certain MIBs to be polled for a device. This configuration disables all discovery modules using this MIB.");

?>

    <div class="row"> <!-- begin row -->

        <div class="col-md-<?php echo($mib_grid); ?>"> <!-- begin MIB options -->

            <div class="box box-solid">

                <div class="box-header with-border">
                    <h3 class="box-title">Device MIBs</h3>
                </div>
                <div class="box-body no-padding">

                    <table class="table table-striped table-condensed-more">
                        <thead>
                        <tr>
                            <th style="padding: 0px;"></th>
                            <th style="padding: 0px; width: 60px;"></th>
                            <th style="padding: 0px; width: 80px;"></th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php

                        foreach ($mibs as $mib => $count) {
                            $mib_disabled = in_array($mib, $mibs_disabled);
                            $mib_errors   = isset($snmp_errors[$mib]);

                            if (!$config['mibs'][$mib]['enable']) {
                                // Globally disabled MIB
                                $where    = "`device_id` = ? AND `use` = ? AND `mib` = ?";
                                $params   = [$device['device_id'], 'mib', $mib];
                                $disabled = dbFetchCell("SELECT `disabled` FROM `devices_mibs` WHERE $where", $params);
                                //r($disabled);
                                $mib_disabled = $disabled !== '0';
                            }
                            if ($mib_disabled) {
                                $attrib_status = '<span class="label label-error">disabled</span>';
                                $toggle        = 'Enable';
                                $btn_class     = 'btn-success';
                                $btn_icon      = 'icon-ok';
                                $class         = ' class="ignore"';
                            } else {
                                $attrib_status = '<span class="label label-success">enabled</span>';
                                $toggle        = "Disable";
                                $btn_class     = "btn-danger";
                                $btn_icon      = 'icon-remove';
                                $class         = $mib_errors ? ' class="error"' : '';
                            }

                            echo('<tr' . $class . '><td><strong>' . $mib . '</strong></td><td>' . $attrib_status . '</td><td>');

                            $form = ['type' => 'simple'];
                            // Elements
                            $form['row'][0]['toggle_mib'] = ['type'  => 'hidden',
                                                             'value' => $mib];
                            $form['row'][0]['submit']     = ['type'     => 'submit',
                                                             'name'     => $toggle,
                                                             'class'    => 'btn-mini ' . $btn_class,
                                                             'icon'     => $btn_icon,
                                                             'right'    => TRUE,
                                                             'readonly' => $readonly,
                                                             'value'    => 'mib_toggle'];
                            print_form($form);
                            unset($form);

                            echo('</td></tr>');
                        }
                        ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div> <!-- end MIB options -->
        <?php

        if (count($snmp_errors)) {
            //r($snmp_errors);

            ?>
            <div class="col-md-7 col-md-pull-0"> <!-- begin Errors options -->

                <div class="box box-solid">

                    <div class="box-header with-border">
                        <h3 class="box-title">SNMP errors</h3>
                    </div>
                    <div class="box-body no-padding">

                        <table class="table  table-striped-two table-condensed-more ">
                            <thead>
                            <tr>
                                <th style="padding: 0px; width: 40px;"></th>
                                <th style="padding: 0px;"></th>
                                <!--<th style="padding: 0px; width: 60px;"></th>-->
                            </tr>
                            </thead>
                            <tbody>

                            <?php

                            foreach ($snmp_errors as $mib => $entries) {
                                $attrib_set = isset($attribs['mib_' . $mib]);

                                echo('<tr><td><span class="label"><i class="icon-bell"></i> ' . count($entries) . '</span></td>');

                                //if ($attrib_set && $attribs['mib_'.$mib] == 0)
                                //{
                                //  $attrib_status = '<span class="label label-error">disabled</span>';
                                //} else {
                                //  $attrib_status = '<span class="label label-success">enabled</span>';
                                //}
                                //echo(<td>$attrib_status.'</td>');

                                echo('<td><strong>' . $mib . '</strong></td></tr>' . PHP_EOL);

                                // OIDs here
                                echo('<tr><td colspan="3">
  <table class="table table-condensed-more">');
                                foreach ($entries as $error_db) {
                                    // Detect if error rate is exceeded
                                    $error_both  = isset($error_codes[$error_db['error_code']]['count']) && isset($error_codes[$error_db['error_code']]['rate']);
                                    $error_count = isset($error_codes[$error_db['error_code']]['count']) && ($error_codes[$error_db['error_code']]['count'] < $error_db['error_count']);
                                    $error_rate  = isset($error_codes[$error_db['error_code']]['rate']) && ($error_codes[$error_db['error_code']]['rate'] < $error_db['error_rate']);
                                    if ($error_both) {
                                        $error_exceeded = $error_count && $error_rate;
                                    } else {
                                        $error_exceeded = $error_count || $error_rate;
                                    }

                                    if ($error_exceeded) {
                                        $error_class  = 'danger';
                                        $error_class2 = 'error';
                                    } else {
                                        $error_class = $error_class2 = 'warning';
                                    }
                                    $text_class = (count(explode(' ', $error_db['oid'])) > 3 ? '' : 'text-nowrap');
                                    echo('<tr width="100%" class="' . $error_class2 . '"><td style="width: 50%;" class="' . $text_class . '"><strong><i class="glyphicon glyphicon-exclamation-sign"></i>&nbsp;' . $error_db['oid'] . '</strong></td>' . PHP_EOL);
                                    echo('<td style="width: 100px; white-space: nowrap; text-align: right;">' . generate_tooltip_time($error_db['updated'], 'ago') . '</td>' . PHP_EOL);
                                    echo('<td style="width: 80px; white-space: nowrap;"><span class="text-' . $error_class . '">' . $error_codes[$error_db['error_code']]['reason'] . '</span></td>' . PHP_EOL);
                                    echo('<td style="width: 40px; text-align: right;"><span class="label">' . $error_db['error_count'] . '</span></td>' . PHP_EOL);
                                    echo('<td style="width: 80px; text-align: right;"><span class="label">' . round($error_db['error_rate'], 2) . '/poll</span></td>' . PHP_EOL);

                                    echo('<td>' . PHP_EOL);
                                    $form = ['type' => 'simple'];
                                    // Elements
                                    $form['row'][0]['mib']        = ['type'  => 'hidden',
                                                                     'value' => $mib];
                                    $form['row'][0]['toggle_oid'] = ['type'  => 'hidden',
                                                                     'value' => $error_db['oid']];
                                    $form['row'][0]['submit']     = ['type'     => 'submit',
                                                                     'name'     => '',
                                                                     'class'    => 'btn-mini btn-' . $error_class,
                                                                     'icon'     => $btn_icon,
                                                                     'right'    => TRUE,
                                                                     'readonly' => $readonly,
                                                                     'disabled' => TRUE, // This button disabled for now, because disabling oids in progress
                                                                     'value'    => 'toggle_oid'];
                                    print_form($form);
                                    unset($form);
                                    echo('</td>' . PHP_EOL);

                                    echo('</td></tr>' . PHP_EOL);
                                }
                                echo('  </table>
</td></tr>' . PHP_EOL);
                            }
                            ?>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div> <!-- end Errors options -->

        <?php } ?>

    </div> <!-- end row -->
    </div> <!-- end container -->
<?php

// EOF
