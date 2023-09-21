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

$query = 'SELECT * FROM `sensors`
            WHERE `device_id` = ? AND `sensor_deleted` = 0
            ORDER BY `sensor_class`,`sensor_type`,`sensor_index`;';

$sensors = dbFetchRows($query, [$device['device_id']]);

//foreach ($limits_reset_array as $class => $descr)
//{
//  print_warning('Reset limits for ' . nicecase($class) . ' sensor' . (count($descr) > 1 ? 's' : '') . ' "' . implode('", "',$descr) . '"; they will be recalculated on the next discovery run.');
//}
//unset($limits_reset_array);

?>

    <form id="update-sensors">

        <input type="hidden" name="action" value="sensors_update">

        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title">Sensor Properties</h3>
            </div>
            <div class="box-body no-padding">
                <table class="table table-striped table-condensed vertical-align">

                    <thead>
                    <tr>
                        <th class="state-marker" rowspan="2"></th>
                        <th rowspan="2">Description &amp; MIB</th>
                        <th style="width: 100px;" rowspan="2">Class</th>
                        <th style="width: 60px;" rowspan="2">Current</th>
                        <th style="width: 250px; white-space: nowrap;" colspan="6">Limits</th>
                        <th style="width: 50px;" rowspan="2">Alerts</th>
                    </tr>
                    <tr>
                        <td>[ Min</td>
                        <td>Min Warn</td>
                        <td>Max Warn</td>
                        <td>Max</td>
                        <td style="width: 4%;">Custom</td>
                        <td style="width: 4%;">Reset ]</td>
                    </tr>
                    </thead>

                    <tbody>

                    <?php

                    // Add CSRF Token
                    if (isset($_SESSION['requesttoken'])) {
                        echo generate_form_element(['type' => 'hidden', 'id' => 'requesttoken', 'value' => $_SESSION['requesttoken']]) . PHP_EOL;
                    }

                    foreach ($sensors as $sensor) {
                        humanize_sensor($sensor);

                        if ($sensor['sensor_state']) {
                            $sensor_value       = $sensor['state_name'];
                            $limit_class        = 'input-mini hidden';
                            $limit_switch_class = 'hide';
                        } else {
                            $sensor_value       = $sensor['human_value'];
                            $limit_class        = 'input-mini';
                            $limit_switch_class = '';
                        }

                        echo('<tr class="' . $sensor['row_class'] . '">');
                        echo('<td class="state-marker"></td>');
                        //echo('<td>'.escape_html($sensor['sensor_index']).'</td>');
                        echo('<td><span class="entity text-nowrap">' . generate_entity_link('sensor', $sensor) . '</span><br /><i>' . $sensor['sensor_type'] . '</i></td>');
                        echo('<td><span class="label label-' . get_type_class($sensor['sensor_class']) . '">' . $sensor['sensor_class'] . '</span></td>');
                        echo('<td><span class="' . $sensor['state_class'] . '">' . $sensor_value . $sensor['sensor_symbol'] . '</span></td>');
                        $item = [
                          'id'       => 'sensors[' . $sensor['sensor_id'] . '][sensor_limit_low]',
                          'type'     => 'text',
                          //'grid'          => 1,
                          'class'    => 'input-mini',
                          'size'     => '4',
                          //'width'         => '58px',
                          'onchange' => "toggleOn('sensors[" . $sensor['sensor_id'] . "][sensor_custom_limit]');",
                          'readonly' => $readonly,
                          //'disabled'      => TRUE,
                          //'submit_by_key' => TRUE,
                          'value'    => $sensor['sensor_limit_low']
                        ];
                        echo('<td>' . generate_form_element($item) . '</td>');
                        $item = [
                          'id'       => 'sensors[' . $sensor['sensor_id'] . '][sensor_limit_low_warn]',
                          'type'     => 'text',
                          //'grid'          => 1,
                          'class'    => 'input-mini',
                          'size'     => '4',
                          //'width'         => '58px',
                          'onchange' => "toggleOn('sensors[" . $sensor['sensor_id'] . "][sensor_custom_limit]');",
                          'readonly' => $readonly,
                          //'disabled'      => TRUE,
                          //'submit_by_key' => TRUE,
                          'value'    => $sensor['sensor_limit_low_warn']
                        ];
                        echo('<td>' . generate_form_element($item) . '</td>');
                        $item = [
                          'id'       => 'sensors[' . $sensor['sensor_id'] . '][sensor_limit_warn]',
                          'type'     => 'text',
                          //'grid'          => 1,
                          'class'    => 'input-mini',
                          'size'     => '4',
                          //'width'         => '58px',
                          'onchange' => "toggleOn('sensors[" . $sensor['sensor_id'] . "][sensor_custom_limit]');",
                          'readonly' => $readonly,
                          //'disabled'      => TRUE,
                          //'submit_by_key' => TRUE,
                          'value'    => $sensor['sensor_limit_warn']
                        ];
                        echo('<td>' . generate_form_element($item) . '</td>');
                        $item = [
                          'id'       => 'sensors[' . $sensor['sensor_id'] . '][sensor_limit]',
                          'type'     => 'text',
                          //'grid'          => 1,
                          'class'    => 'input-mini',
                          'size'     => '4',
                          //'width'         => '58px',
                          'onchange' => "toggleOn('sensors[" . $sensor['sensor_id'] . "][sensor_custom_limit]');",
                          'readonly' => $readonly,
                          //'disabled'      => TRUE,
                          //'submit_by_key' => TRUE,
                          'value'    => $sensor['sensor_limit']
                        ];
                        echo('<td>' . generate_form_element($item) . '</td>');
                        //echo('<td><input type="text" class="'.$limit_class.'" name="sensors['.$sensor['sensor_id'].'][sensor_limit_low]" size="4" value="'.escape_html($sensor['sensor_limit_low']).'" /></td>');
                        //echo('<td><input type="text" class="'.$limit_class.'" name="sensors['.$sensor['sensor_id'].'][sensor_limit_low_warn]" size="4" value="'.escape_html($sensor['sensor_limit_low_warn']).'" /></td>');
                        //echo('<td><input type="text" class="'.$limit_class.'" name="sensors['.$sensor['sensor_id'].'][sensor_limit_warn]" size="4" value="'.escape_html($sensor['sensor_limit_warn']).'" /></td>');
                        //echo('<td><input type="text" class="'.$limit_class.'" name="sensors['.$sensor['sensor_id'].'][sensor_limit]" size="4" value="'.escape_html($sensor['sensor_limit']).'" /></td>');
                        if (OBS_DEBUG) { /*echo('<td>'.$sensor['sensor_multiplier'].'</td>'); */
                        }
                        $item = [
                            //'id'            => 'sensor_custom_limit_'.$sensor['sensor_id'],
                            'id'       => 'sensors[' . $sensor['sensor_id'] . '][sensor_custom_limit]',
                            //'type'          => 'switch',
                            'type'     => 'switch-ng',
                            'on-text'  => 'Custom',
                            //'on-color'      => 'danger',
                            //'on-icon'       => 'icon-trash',
                            'off-text' => 'No',
                            //'off-icon'      => 'icon-sitemap',
                            //'grid'          => 1,
                            'size'     => 'mini',
                            //'width'         => '58px',
                            //'title'         => 'Use custom limits',
                            //'placeholder'   => 'Removed',
                            'readonly' => $readonly,
                            //'disabled'      => TRUE,
                            //'submit_by_key' => TRUE,
                            'value'    => $sensor['sensor_custom_limit']];
                        echo('<td class="text-center">' . generate_form_element($item) . '</td>');

                        $item = [
                            //'id'            => 'sensor_reset_limit_'.$sensor['sensor_id'],
                            'id'       => 'sensors[' . $sensor['sensor_id'] . '][sensor_reset_limit]',
                            //'type'          => 'switch',
                            'type'     => 'switch-ng',
                            'on-text'  => 'Reset',
                            //'on-color'      => 'danger',
                            //'on-icon'       => 'icon-trash',
                            'off-text' => 'No',
                            //'off-icon'      => 'icon-sitemap',
                            //'grid'          => 1,
                            'size'     => 'mini',
                            //'class'         => 'text-center',
                            //'width'         => '58px',
                            //'title'         => 'Reset limits to auto',
                            //'placeholder'   => 'Removed',
                            'readonly' => $readonly,
                            //'disabled'      => TRUE,
                            //'submit_by_key' => TRUE,
                            'value'    => $sensor['sensor_reset_limit']];
                        echo('<td class="text-center">' . generate_form_element($item) . '</td>');

                        $item = [
                            //'id'            => 'sensor_ignore_'.$sensor['sensor_id'],
                            'id'        => 'sensors[' . $sensor['sensor_id'] . '][sensor_ignore]',
                            //'type'          => 'switch',
                            'type'      => 'switch-ng',
                            'off-text'  => 'Yes',
                            'off-color' => 'success',
                            'on-color'  => 'danger',
                            //'on-icon'       => 'icon-trash',
                            'on-text'   => 'No',
                            //'off-icon'      => 'icon-sitemap',
                            //'grid'          => 1,
                            'size'      => 'mini',
                            //'height'        => '15px',
                            //'title'         => 'Show/Hide Removed',
                            //'placeholder'   => 'Removed',
                            'readonly'  => $readonly,
                            //'disabled'      => TRUE,
                            //'submit_by_key' => TRUE,
                            'value'     => $sensor['sensor_ignore']];
                        echo('<td class="text-center">' . generate_form_element($item) . '</td>');
                        echo('</tr>');
                    }

                    ?>

                    </tbody>
                </table>
            </div>

            <div class="box-footer">
                <?php
                $item = [
                  'id'       => 'submit',
                  'name'     => 'Save Changes',
                  'class'    => 'btn-primary pull-right',
                  'icon'     => 'icon-ok icon-white',
                  'readonly' => $readonly,
                  'value'    => 'update-sensors'
                ];
                echo(generate_form_element($item, 'submit'));
                ?>
            </div>
        </div>
    </form>

<?php

register_html_resource('script', '$("#update-sensors").submit(processAjaxForm);');

// EOF
