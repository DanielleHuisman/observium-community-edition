<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

$config['install_dir'] = "../..";

include_once("../../includes/sql-config.inc.php");

include($config['html_dir'] . "/includes/functions.inc.php");
include($config['html_dir'] . "/includes/authenticate.inc.php");

if (!$_SESSION['authenticated'])
{
  print_json_status('failed', 'Unauthorized.');
  exit();
}

$vars = get_vars([ 'JSON', 'POST' ]); // Got a JSON payload. Replace $var.

$readonly = $_SESSION['userlevel'] < 7;
$readwrite = $_SESSION['userlevel'] >= 10;

switch ($vars['action']) {
  case "theme":
    if ($vars['value'] === 'reset') {
      session_unset_var("theme");
      if ($config['web_theme_default'] === 'system') {
        // Override default
        session_unset_var("theme_default");
      }
      print_json_status('ok', 'Theme reset.');
    } elseif (is_array($config['themes'][$vars['value']])) {
      session_set_var("theme", $vars['value']);
      if ($config['web_theme_default'] === 'system') {
        // Override default
        session_set_var("theme_default", $vars['value']);
      }
      print_json_status('ok', 'Theme set.');
    } else {
      print_json_status('failed', 'Invalid theme.');
    }
    break;

  case "big_graphs":
    session_set_var("big_graphs", TRUE);
    print_json_status('ok', 'Big graphs set.');
    break;

  case "small_graphs":
    session_unset_var("big_graphs");
    print_json_status('ok', 'Small graphs set.');
    break;

  case "touch_on":
    session_set_var("touch", TRUE);
    print_json_status('ok', 'Touch mode enabled.');
    break;

  case "touch_off":
    session_unset_var("touch");
    print_json_status('ok', 'Touch mode disabled.');
    break;

  case "set_refresh":
    session_set_var("dark_mode", TRUE);
    print_json_status('ok', 'Dark mode set.');
    break;

  case "alert_assoc_edit":

    // Currently edit allowed only for Admins
    if (!$readwrite) {
      print_json_status('failed', 'Action not allowed.');
      exit();
    }

    if (dbFetchRow("SELECT * FROM `alert_tests` WHERE `alert_test_id` = ?", array($vars['alert_test_id']))) {

      $rows_updated = dbUpdate([ 'alert_assoc' => $vars['alert_assoc'] ], 'alert_tests', '`alert_test_id` = ?', [ $vars['alert_test_id'] ]);

      if ($rows_updated) {
        update_alert_table($vars['alert_test_id']);
        print_json_status('ok', '', [ 'id'       => $vars['alert_test_id'],
                                      'redirect' => generate_url([ 'page' => 'alert_check', 'alert_test_id' => $vars['alert_test_id'] ]) ]);
      } else {
        print_json_status('failed', 'Database was not updated.');
      }
    } else {
      print_json_status('failed', 'Alert Checker does not exist: [' . $vars['alert_test_id'] . ']');
    }
    break;

  case "save_grid": // Save current layout of dashboard grid

    // Currently edit allowed only for Admins
    if ($readonly) {
      print_json_status('failed', 'Action not allowed.');
      exit();
    }

    foreach ($vars['grid'] as $w) {
      dbUpdate(array('x' => $w['x'], 'y' => $w['y'], 'width' => $w['width'], 'height' => $w['height'],), 'dash_widgets',
               '`widget_id` = ?', array($w['id'])
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
      $widget_id = dbInsert(array('dash_id' => $vars['dash_id'], 'widget_config' => json_encode(array()), 'widget_type' => $vars['widget_type']),
                            'dash_widgets'
      );
    }

    if ($widget_id) {
      print_json_status('ok', '', [ 'id' => $widget_id ]);
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
      $rows_deleted = dbDelete('wifi_aps', '`wifi_ap_id` = ?', array($vars['id']));
    }

    if ($rows_deleted) {
      print_json_status('ok', 'AP Deleted', [ 'id' => $vars['id'] ]);
    }

    break;

  case "del_widget":

    // Currently edit allowed only for Admins
    if ($readonly) {
      print_json_status('failed', 'Action not allowed.');
      exit();
    }

    if (is_numeric($vars['widget_id'])) {
      $rows_deleted = dbDelete('dash_widgets', '`widget_id` = ?', array($vars['widget_id']));
    }

    if ($rows_deleted) {
      print_json_status('ok', 'Widget Deleted.', [ 'id' => $vars['widget_id'] ]);
    }
    break;

  case "dash_rename":

    // Currently edit allowed only for Admins
    if ($readonly) {
      print_json_status('failed', 'Action not allowed.');
      exit();
    }

    if (is_numeric($vars['dash_id'])) {
      $rows_updated = dbUpdate(array('dash_name' => $vars['dash_name']), 'dashboards', '`dash_id` = ?', array($vars['dash_id']));
    } else {
      print_json_status('failed', 'Invalid Dashboard ID.');
    }

    if ($rows_updated) {
      print_json_status('ok', 'Dashboard Name Updated.', [ 'id' => $vars['dash_id'] ]);
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
      $rows_deleted  = dbDelete('dash_widgets', '`dash_id` = ?', array($vars['dash_id']));
      $rows_deleted += dbDelete('dashboards', '`dash_id` = ?', array($vars['dash_id']));
    } else {
      print_json_status('failed', 'Invalid Dashboard ID.');
    }

    if ($rows_deleted) {
      print_json_status('ok', 'Dashboard Deleted.', [ 'id' => $vars['dash_id'] ]);
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

    $widget                  = dbFetchRow("SELECT * FROM `dash_widgets` WHERE widget_id = ?", array($vars['widget_id']));
    $widget['widget_config'] = safe_json_decode($widget['widget_config']);

    // Verify config value applies to this widget here

    if (isset($vars['config_field']) && isset($vars['config_value'])) {
      if (empty($vars['config_value'])) {
        unset($widget['widget_config'][$vars['config_field']]);
      } else {
        $widget['widget_config'][$vars['config_field']] = $vars['config_value'];
      }

      dbUpdate(array('widget_config' => json_encode($widget['widget_config'])), 'dash_widgets',
               '`widget_id` = ?', array($widget['widget_id'])
      );

      //echo dbError();

      print_json_status('ok', 'Widget Updated.', [ 'id' => $widget['widget_id'] ]);
    } else {
      print_json_status('failed', 'Update Failed.');
    }

    break;

  default:

    // Validate CSRF Token
    //r($vars);
    $json = '';
    if (!str_contains_array($vars['action'], [ 'widget', 'dash' ]) && // widget & dashboard currently not send request token
        !request_token_valid($vars, $json)) {
      $json = safe_json_decode($json);
      $json['reload'] = TRUE;
      print_json_status('failed', 'CSRF Token missing. Reload page.', $json);
      exit();
    }
    unset($json);

    $action_path = __DIR__ . '/actions/'. $vars['action'] . '.inc.php';
    if (is_alpha($vars['action']) && is_file($action_path))
    {
      include $action_path;
    } else {
      print_json_status('failed', 'Unknown action requested.');
    }
}

// EOF
