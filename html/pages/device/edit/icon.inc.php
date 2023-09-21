<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($vars['editing']) {
    if ($readonly) {
        print_error_permission('You have insufficient permissions to edit settings.');
    } else {
        $param = ['icon' => $vars['icon']];

        $rows_updated = dbUpdate($param, 'devices', '`device_id` = ?', [$device['device_id']]);

        if ($rows_updated > 0 || $updated) {
            $update_message = "Device icon updated.";
            $updated        = 1;
            $device         = dbFetchRow("SELECT * FROM `devices` WHERE `device_id` = ?", [$device['device_id']]);
        } elseif ($rows_updated = '-1') {
            $update_message = "Device icon unchanged. No update necessary.";
            $updated        = -1;
        } else {
            $update_message = "Device icon update error.";
        }
    }

    if ($updated && $update_message) {
        print_message($update_message);
    } elseif ($update_message) {
        print_error($update_message);
    }
}

?>

    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">Device icon</h3>
        </div>

        <form id="edit" name="edit" method="post" action="" class="form form-inline">
            <div class="box-body" style="padding: 10px;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <input type="hidden" name="editing" value="yes">
                            <table border="0">
                                <tr>
                                    <?php

                                    $numicons = 1;

                                    // Default icon
                                    $icon_default = $config['os'][$device['os']]['icon'];

                                    echo('          <td width="64" align="center"><img src="images/os/' . $icon_default . '.png"><br /><i>' . nicecase($icon_default) . '</i><p />');
                                    echo('<input name="icon" type="radio" value="' . $icon_default . '"' . ($device['icon'] == '' || $device['icon'] == $icon_default ? ' checked="1"' : '') . ' /></td>' . "\n");

                                    foreach ($config['os'][$device['os']]['icons'] as $icon_new) {
                                        if ($icon_new != $icon) {
                                            echo('          <td align="center"><img src="images/os/' . $icon_new . '.png"><br /><i>' . ucwords(strtr($icon_new, '_', ' ')) . '</i><p />');
                                            echo('<input name="icon" type="radio" value="' . $icon_new . '"' . ($device['icon'] == $icon_new ? ' checked="1"' : '') . ' /></td>' . "\n");
                                            $numicons++;
                                        }
                                    }

                                    if ($numicons % 10 == 0) {
                                        echo("              </tr>\n");
                                        echo("              <tr>\n");
                                    }
                                    ?>
                                </tr>
                            </table>
                            <br/>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="submit" class="box-footer">
                <?php
                $item = ['id'    => 'submit',
                         'name'  => 'Save Changes',
                         'class' => 'btn-primary',
                         'icon'  => 'icon-ok icon-white',
                         'value' => 'save'];
                echo(generate_form_element($item, 'submit'));
                ?>
            </div>
        </form>
    </div>

<?php

// EOF
