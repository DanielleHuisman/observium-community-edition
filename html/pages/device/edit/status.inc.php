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

$query = 'SELECT * FROM `status`
            WHERE `device_id` = ? AND `status_deleted` = 0
            ORDER BY `status_mib`, `status_type`, `status_index`;';

$statuses = dbFetchRows($query, [$device['device_id']]);

?>

    <form id="update_statuses" class="form form-inline">

        <input type="hidden" name="action" value="statuses_update">

        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title">Status Properties</h3>
            </div>
            <div class="box-body no-padding">
                <table class="table table-striped table-condensed vertical-align">
                    <thead>
                    <tr>
                        <th class="state-marker"></th>
                        <!-- <th style="width: 60px;">Index</th> -->
                        <th>Description & MIB</th>
                        <th style="width: 100px;">Value</th>
                        <th style="width: 60px;">Event</th>
                        <th style="width: 50px;">Alerts</th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php

                    // Add CSRF Token
                    if (isset($_SESSION['requesttoken'])) {
                        echo generate_form_element(['type' => 'hidden', 'id' => 'requesttoken', 'value' => $_SESSION['requesttoken']]) . PHP_EOL;
                    }

                    foreach ($statuses as $status) {
                        humanize_status($status); //r($status);

                        echo '<tr class="' . $status['row_class'] . '">';
                        echo '<td class="state-marker"></td>';
                        //echo('<td>'.escape_html($sensor['sensor_index']).'</td>');
                        echo '<td><span class="entity text-nowrap">' . generate_entity_link('status', $status) . '</span><br /><i>' . $status['status_type'] . '</i></td>';
                        echo '<td><span class="' . $status['event_class'] . '">' . $status['status_name'] . '</span></td>';
                        echo '<td><span class="' . $status['event_class'] . '">' . $status['status_event'] . '</span></td>';

                        $item = [
                          'id'        => 'status[' . $status['status_id'] . '][status_ignore]',
                          'type'      => 'switch-ng',
                          'off-text'  => 'Yes',
                          'off-color' => 'success',
                          'on-color'  => 'danger',
                          'on-text'   => 'No',
                          'size'      => 'mini',
                          'readonly'  => $readonly,
                          'value'     => $status['status_ignore']
                        ];
                        echo '<td class="text-center">' . generate_form_element($item) . '</td>';
                        echo '</tr>';
                    }

                    ?>

                    </tbody>
                </table>
            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary pull-right" name="submit" value="update_statuses"><i class="icon-ok icon-white"></i> Save Changes
                </button>
            </div>
        </div>
    </form>

<?php

register_html_resource('script', '$("#update_statuses").submit(processAjaxForm);');

// EOF
