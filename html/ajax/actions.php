<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

include_once("../../includes/observium.inc.php");

include($config['html_dir'] . "/includes/authenticate.inc.php");

if (!$_SESSION['authenticated']) {
    print_json_status('failed', 'Unauthorized.');
    exit();
}

$vars = get_vars([ 'JSON', 'POST' ]); // Got a JSON payload. Replace $var.

$readonly  = $_SESSION['userlevel'] < 7;
$limitwrite = $_SESSION['userlevel'] >= 9;
$readwrite = $_SESSION['userlevel'] >= 10;

switch ($vars['action']) {
    case "theme":
        $pref = 'web_theme_default';
        if ($vars['value'] === 'reset') {
            session_unset_var("theme");
            if ($config['web_theme_default'] === 'system') {
                // Override default
                session_unset_var("theme_default");
            }

            if (del_user_pref($_SESSION['user_id'], $pref)) {
                print_json_status('ok', 'Theme reset.');
            }
        } elseif (isset($config['themes'][$vars['value']]) || $vars['value'] === 'system') {
            if (set_user_pref($_SESSION['user_id'], $pref, serialize($vars['value']))) {
                print_json_status('ok', 'Theme set.');
            }
        } else {
            print_json_status('failed', 'Invalid theme.');
        }
        break;

    case "big_graphs":
        $pref = 'graphs|size';
        if (set_user_pref($_SESSION['user_id'], $pref, serialize('big'))) {
            print_json_status('ok', 'Big graphs set.');
            session_unset_var("big_graphs"); // clear old
        }
        break;

    case "normal_graphs":
        $pref = 'graphs|size';
        if (set_user_pref($_SESSION['user_id'], $pref, serialize('normal'))) {
            print_json_status('ok', 'Normal graphs set.');
            session_unset_var("big_graphs"); // clear old
        }
        break;

    case "touch_on":
        session_set_var("touch", TRUE);
        print_json_status('ok', 'Touch mode enabled.');
        break;

    case "touch_off":
        session_unset_var("touch");
        print_json_status('ok', 'Touch mode disabled.');
        break;

    case "save_grid": // Save current layout of dashboard grid

        // Currently edit allowed only for Admins
        if ($readonly) {
            print_json_status('failed', 'Action not allowed.');
            exit();
        }

        foreach ($vars['grid'] as $w) {
            dbUpdate(['x' => $w['x'], 'y' => $w['y'], 'width' => $w['width'], 'height' => $w['height'],], 'dash_widgets',
                     '`widget_id` = ?', [$w['id']]
            );
        }
        break;

    case "add_widget": // Add widget of 'widget_type' to dashboard 'dash_id'

        // Currently edit allowed only for Admins
        if ($readonly) {
            print_json_status('failed', 'Action not allowed.');
            exit();
        }

        if (isset($vars['dash_id']) && isset($vars['widget_type'])) {
            $widget_id = dbInsert(['dash_id' => $vars['dash_id'], 'widget_config' => json_encode([]), 'widget_type' => $vars['widget_type']],
                                  'dash_widgets'
            );
        }

        if ($widget_id) {
            print_json_status('ok', '', ['id' => $widget_id]);
        } else {
            //print_r($vars); // For debugging
        }
        break;

    case "delete_ap":

        // Currently edit allowed only for Admins
        if ($readonly) {
            print_json_status('failed', 'Action not allowed.');
            exit();
        }

        if (is_numeric($vars['id'])) {
            $rows_deleted = dbDelete('wifi_aps', '`wifi_ap_id` = ?', [$vars['id']]);
        }

        if ($rows_deleted) {
            print_json_status('ok', 'AP Deleted', ['id' => $vars['id']]);
        }

        break;

    case "del_widget":

        // Currently edit allowed only for Admins
        if ($readonly) {
            print_json_status('failed', 'Action not allowed.');
            exit();
        }

        if (is_numeric($vars['widget_id'])) {
            $rows_deleted = dbDelete('dash_widgets', '`widget_id` = ?', [$vars['widget_id']]);
        }

        if ($rows_deleted) {
            print_json_status('ok', 'Widget Deleted.', ['id' => $vars['widget_id']]);
        }
        break;

    case "dash_rename":

        // Currently edit allowed only for Admins
        if ($readonly) {
            print_json_status('failed', 'Action not allowed.');
            exit();
        }

        if (is_numeric($vars['dash_id'])) {
            $rows_updated = dbUpdate(['dash_name' => $vars['dash_name']], 'dashboards', '`dash_id` = ?', [$vars['dash_id']]);
        } else {
            print_json_status('failed', 'Invalid Dashboard ID.');
        }

        if ($rows_updated) {
            print_json_status('ok', 'Dashboard Name Updated.', ['id' => $vars['dash_id']]);
        } else {
            print_json_status('failed', 'Update Failed.');
        }

        break;

    case "dash_delete":

        // Currently edit allowed only for Admins
        if ($readonly) {
            print_json_status('failed', 'Action not allowed.');
            exit();
        }

        if (is_numeric($vars['dash_id'])) {
            $rows_deleted = dbDelete('dash_widgets', '`dash_id` = ?', [$vars['dash_id']]);
            $rows_deleted += dbDelete('dashboards', '`dash_id` = ?', [$vars['dash_id']]);
        } else {
            print_json_status('failed', 'Invalid Dashboard ID.');
        }

        if ($rows_deleted) {
            print_json_status('ok', 'Dashboard Deleted.', ['id' => $vars['dash_id']]);
        } else {
            print_json_status('failed', 'Deletion Failed.');
        }

        break;

    case "update_widget_config":

        //print_r($vars);

        // Currently edit allowed only for Admins
        if ($readonly) {
            print_json_status('failed', 'Action not allowed.');
            exit();
        }

        $widget                  = dbFetchRow("SELECT * FROM `dash_widgets` WHERE `widget_id` = ?", [$vars['widget_id']]);
        $widget['widget_config'] = safe_json_decode($widget['widget_config']);

        // Verify config value applies to this widget here

        $default_on = ['legend'];

        if (isset($vars['config_field']) && isset($vars['config_value'])) {
            if (empty($vars['config_value']) ||
                (in_array($vars['config_field'], $default_on) && get_var_true($vars['config_value'])) ||
                (!in_array($vars['config_field'], $default_on) && get_var_false($vars['config_value']))) {
                // Just unset the value if it's empty or it's a default value.
                unset($widget['widget_config'][$vars['config_field']]);
            } else {
                $widget['widget_config'][$vars['config_field']] = $vars['config_value'];
            }

            dbUpdate(['widget_config' => json_encode($widget['widget_config'])], 'dash_widgets',
                     '`widget_id` = ?', [$widget['widget_id']]
            );

            //echo dbError();

            print_json_status('ok', 'Widget Updated.', ['id' => $widget['widget_id']]);
        } else {
            print_json_status('failed', 'Update Failed.');
        }

        break;

    default:

        // Validate CSRF Token
        //r($vars);
        $json = '';
        if (!str_contains_array($vars['action'], ['widget', 'dash', 'settings_user']) && // widget & dashboard currently not send request token
            !request_token_valid($vars, $json)) {
            $json           = safe_json_decode($json);
            $json['reload'] = TRUE;
            print_json_status('failed', 'CSRF Token missing. Reload page.', $json);
            exit();
        }
        unset($json);

        $action_path = __DIR__ . '/actions/' . $vars['action'] . '.inc.php';
        if (is_alpha($vars['action']) && is_file($action_path)) {
            include $action_path;
        } else {
            print_json_status('failed', 'Unknown action requested.');
        }
}

// EOF
