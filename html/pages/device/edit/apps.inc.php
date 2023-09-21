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

print_warning("This page allows you to disable applications for this device that were previously enabled. " .
              "Observium agent applications are automatically detected by the poller system.");

# Check if the form was POSTed
if ($vars['device']) {
    if ($readonly) {
        print_error_permission('You have insufficient permissions to edit settings.');
    } else {
        $updated = 0;
        $param[] = $device['device_id'];
        $enabled = [];
        foreach (array_keys($vars) as $key) {
            if (str_starts($key, 'app_')) {
                $param[]   = substr($key, 4);
                $enabled[] = substr($key, 4);
                $replace[] = "?";
            }
        }

        if (count($enabled)) {
            $updated += dbDelete('applications', "`device_id` = ? AND `app_type` NOT IN (" . implode(",", $replace) . ")", $param);
        } else {
            $updated += dbDelete('applications', "`device_id` = ?", [$param]);
        }

        foreach (dbFetchRows("SELECT `app_type` FROM `applications` WHERE `device_id` = ?", [$device['device_id']]) as $row) {
            $app_in_db[] = $row['app_type'];
        }

        foreach ($enabled as $app) {
            if (!in_array($app, $app_in_db)) {
                $updated += dbInsert(['device_id' => $device['device_id'], 'app_type' => $app], 'applications');
            }
        }

        if ($updated) {
            print_message("Applications updated!");
        } else {
            print_message("No changes.");
        }
    }
}

# Show list of apps with checkboxes

$apps_enabled = dbFetchRows("SELECT * from `applications` WHERE `device_id` = ? ORDER BY app_type", [ $device['device_id'] ]);
if (safe_count($apps_enabled)) {
    $app_enabled = [];
    foreach ($apps_enabled as $application) {
        $app_enabled[] = $application['app_type'];
    }
    ?>

    <form id="appedit" name="appedit" method="post" action="" class="form-inline">
        <input type="hidden" name="device" value="<?php echo $device['device_id']; ?>">

        <?php echo generate_box_open(['title' => 'Applications', 'header-border' => TRUE]); ?>

        <table class="table table-striped table-condensed">
            <thead>
            <tr>
                <th style="width: 100px;">Enable</th>
                <th>Application</th>
            </tr>
            </thead>
            <tbody>

            <?php

            # Load our list of available applications
            $applications = [];
            foreach (get_recursive_directory_iterator($config['install_dir'] . "/includes/polling/applications/") as $file => $info) {
                if (!str_ends_with($info->getFilename(), '.inc.php')) { continue; }
                $applications[] = str_replace(".inc.php", "", $info->getFilename());

            }
            //r($applications);

            foreach ($applications as $app) {
                if (in_array($app, $app_enabled)) {
                    echo("    <tr>");
                    //echo("      <td>");
                    $item = [
                      'id'        => 'app_' . $app,
                      'type'      => 'switch-ng',
                      'off-text'  => 'Yes',
                      'off-color' => 'success',
                      'on-color'  => 'danger',
                      'on-text'   => 'No',
                      'size'      => 'mini',
                      //'height'        => '15px',
                      //'title'         => 'Show/Hide Removed',
                      //'placeholder'   => 'Removed',
                      'readonly'  => $readonly,
                      //'disabled'      => TRUE,
                      //'submit_by_key' => TRUE,
                      'value'     => 1
                    ];
                    echo('<td class="text-center">' . generate_form_element($item) . '</td>');
                    //echo("      </td>");
                    echo("      <td>" . nicecase($app) . "</td>");
                    echo("    </tr>");
                }
            }
            ?>
            </tbody>
        </table>
        </div>

        <div class="box-footer">
            <button type="submit" class="btn btn-primary pull-right" name="submit" value="save"><i class="icon-ok icon-white"></i> Save Changes</button>
        </div>
        </div>

    </form>
    <?php
} else {
    print_error("No applications found on this device.");
}

// EOF
