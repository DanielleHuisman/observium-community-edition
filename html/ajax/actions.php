<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     ajax
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$config['install_dir'] = "../..";

include_once("../../includes/sql-config.inc.php");

include($config['html_dir'] . "/includes/functions.inc.php");
include($config['html_dir'] . "/includes/authenticate.inc.php");

if (!$_SESSION['authenticated'])
{
  echo("unauthenticated");
  exit;
}

$vars = get_vars();

$json = json_decode(trim(file_get_contents("php://input")), TRUE);
if (isset($json['action']))
{
  $vars = $json;
} // Got a JSON payload. Replace $var.

switch ($vars['action'])
{

  case "group_edit":

    if (dbFetchRow("SELECT * FROM `groups` WHERE `group_id` = ?", array($vars['group_id'])))
    {

      $rows_updated = dbUpdate(array('group_descr' => $vars['group_descr'], 'group_name' => $vars['group_name'], 'group_assoc' => $vars['group_assoc']),
                               'groups', '`group_id` = ?',
                               array($vars['group_id']));
      if ($rows_updated)
      {
        update_group_table($vars['group_id']);
        print json_encode(array('id' => $group_id, 'status' => 'ok', 'redirect' => generate_url(array('page' => 'group', 'group_id' => $vars['group_id']))));
      } else
      {
        header('Content-Type: application/json');
        print json_encode(array('status' => 'failed'));
      }
    } else
    {
      print json_encode(array('status' => 'failed'));
    }
    break;

  case "alert_assoc_edit":

    if (dbFetchRow("SELECT * FROM `alert_tests` WHERE `alert_test_id` = ?", array($vars['alert_test_id'])))
    {

      $rows_updated = dbUpdate(array('alert_assoc' => $vars['alert_assoc']), 'alert_tests', '`alert_test_id` = ?',
                               array($vars['alert_test_id']));

      if ($rows_updated)
      {
        update_alert_table($vars['alert_test_id']);
        print json_encode(array('id' => $vars['alert_test_id'], 'status' => 'ok', 'redirect' => generate_url(array('page' => 'alert_check', 'alert_test_id' => $vars['alert_test_id']))));
      } else
      {
        header('Content-Type: application/json');
        print json_encode(array('status' => 'failed', 'message' => 'Database was not updated.'));
      }
    } else
    {
      print json_encode(array('status' => 'failed', 'message' => 'Alert Checker does not exist: [' . $vars['alert_test_id'] . ']'));
    }
    break;

  case "alert_check_add":

    //print json_encode(array($vars));

    $ok = TRUE;
    foreach (array('entity_type', 'alert_name', 'alert_severity', 'alert_conditions') as $var)
    {
      if (!isset($vars[$var]) || strlen($vars[$var]) == '0')
      {
        $ok = FALSE;
        $failed[] = $var;
      }
    }

    if ($ok)
    {
      $check_array = array();

      $conditions = array();
      foreach (explode("\n", trim($vars['alert_conditions'])) AS $cond)
      {
        $condition = array();
        list($condition['metric'], $condition['condition'], $condition['value']) = explode(" ", trim($cond), 3);
        $conditions[] = $condition;
      }
      $check_array['conditions'] = json_encode($conditions);
      $check_array['alert_assoc'] = $vars['alert_assoc'];
      $check_array['entity_type'] = $vars['entity_type'];
      $check_array['alert_name'] = $vars['alert_name'];
      $check_array['alert_message'] = $vars['alert_message'];
      $check_array['severity'] = $vars['alert_severity'];
      $check_array['suppress_recovery'] = ($vars['alert_send_recovery'] == '1' || $vars['alert_send_recovery'] == 'on' ? 0 : 1);
      $check_array['alerter'] = NULL;
      $check_array['and'] = $vars['alert_and'];
      $check_array['delay'] = $vars['alert_delay'];
      $check_array['enable'] = '1';

      $check_id = dbInsert('alert_tests', $check_array);

      if (is_numeric($check_id))
      {

        update_alert_table($check_id);

        header('Content-Type: application/json');
        print json_encode(array('id' => $check_id, 'status' => 'ok', 'redirect' => generate_url(array('page' => 'alert_check', 'alert_test_id' => $check_id))));

      } else
      {
        header('Content-Type: application/json');
        print json_encode(array('status' => 'failed', 'message' => 'Alert creation failed. Please note that the alert name <b>must</b> be unique.'));
      }
    } else
    {
      header('Content-Type: application/json');
      print json_encode(array('status' => 'failed', 'message' => 'Missing required data. (' . implode(", ",
                                                                                                      $failed) . ')'));
    }

    break;

  case "add_group":

    $ok = TRUE;
    $group_array = array();

    $missing_data = array();

    foreach (array('entity_type', 'group_name', 'group_descr', 'group_assoc') as $var)
    {
      if (!isset($vars[$var]) || strlen($vars[$var]) == '0')
      {
        $ok = FALSE;

        $missing_data[] = $var;

      } else
      {
        $group_array[$var] = $vars[$var];
      }
    }

    if ($ok)
    {
      $group_id = dbInsert('groups', $group_array);
      //print_r(dbError());
    }

    if ($group_id)
    {
      update_group_table($group_id);
      header('Content-Type: application/json');
      print json_encode(array('id' => $group_id, 'status' => 'ok', 'redirect' => generate_url(array('page' => 'group', 'group_id' => $group_id))));
    } else
    {

      if(count($missing_data)) { $message = "Missing data: ".implode($missing_data, ', '); }

      header('Content-Type: application/json');
      print json_encode(array('status' => 'failed', 'message' => ''));
    }

    break;

  case "save_grid": // Save current layout of dashboard grid

    foreach ($vars['grid'] as $w)
    {
      dbUpdate(array('x' => $w['x'], 'y' => $w['y'], 'width' => $w['width'], 'height' => $w['height'],), 'dash_widgets',
               '`widget_id` = ?', array($w['id']));
    }
    break;

  case "add_widget": // Add widget of 'widget_type' to dashboard 'dash_id'

    if (isset($vars['dash_id']) && isset($vars['widget_type']))
    {
      $widget_id = dbInsert(array('dash_id' => $vars['dash_id'], 'widget_config' => json_encode(array()), 'widget_type' => $vars['widget_type']),
                            'dash_widgets');
    }

    if ($widget_id)
    {
      header('Content-Type: application/json');
      print json_encode(array('id' => $widget_id));
    } else
    {
      //print_r($vars); // For debugging
    }
    break;

  case "delete_ap":

    if (is_numeric($vars['id']))
    {
      $rows_deleted = dbDelete('wifi_aps', '`wifi_ap_id` = ?', array($vars['id']));
    }

    if ($rows_deleted)
    {
      header('Content-Type: application/json');
      print json_encode(array('status' => 'ok', 'id' => $vars['id'], 'message' => 'AP Deleted'));
    }

    break;

  case "del_widget":

    if (is_numeric($vars['widget_id']))
    {
      $rows_deleted = dbDelete('dash_widgets', '`widget_id` = ?', array($vars['widget_id']));
    }

    if ($rows_deleted)
    {
      header('Content-Type: application/json');
      print json_encode(array('id' => $vars['widget_id'], 'message' => 'Widget Deleted'));
    }
    break;

  case "dash_rename":

    header('Content-Type: application/json');

    if (is_numeric($vars['dash_id']))
    {
      $rows_updated = dbUpdate(array('dash_name' => $vars['dash_name']), 'dashboards', '`dash_id` = ?', array($vars['dash_id']));
    } else
    {
      print json_encode(array('status' => 'error', 'message' => 'Invalid Dashboard ID'));
    }

    if ($rows_updated)
    {
      print json_encode(array('status' => 'ok', 'id' => $vars['dash_id'], 'message' => 'Dashboard Name Updated'));
    } else
    {
      print json_encode(array('status' => 'fail', 'message' => 'Update Failed.'));
    }

    break;

  case "dash_delete":

    header('Content-Type: application/json');

    if (is_numeric($vars['dash_id']))
    {
      $rows_deleted = dbDelete('dash_widgets', '`dash_id` = ?', array($vars['dash_id']));
      $rows_deleted = dbDelete('dashboards', '`dash_id` = ?', array($vars['dash_id']));
    } else
    {
      print json_encode(array('status' => 'error', 'message' => 'Invalid Dashboard ID'));
    }

    if ($rows_deleted)
    {
      print json_encode(array('status' => 'ok', 'id' => $vars['dash_id'], 'message' => 'Dashboard Deleted'));
    } else
    {
      print json_encode(array('status' => 'fail', 'message' => 'Deletion Failed.'));
    }

    break;

  case "edit_widget":
    include("actions/edit_widget.inc.php");
    break;

  case "update_widget_config":

    //print_r($vars);

    $widget = dbFetchRow("SELECT * FROM `dash_widgets` WHERE widget_id = ?", array($vars['widget_id']));
    $widget['widget_config'] = json_decode($widget['widget_config'], TRUE);

    // Verify config value applies to this widget here

    if(isset($vars['config_field']) && isset($vars['config_value']))
    {
      if(empty($vars['config_value'])) {
        unset($widget['widget_config'][$vars['config_field']]);
      } else
      {
        $widget['widget_config'][$vars['config_field']] = $vars['config_value'];
      }

      dbUpdate(array('widget_config' => json_encode($widget['widget_config'])), 'dash_widgets',
               '`widget_id` = ?', array($widget['widget_id']));

      echo dbError();

      print json_encode(array('status' => 'ok', 'message' => 'Widget Updated.'));

    } else {
      print json_encode(array('status' => 'fail', 'message' => 'Update Failed.'));
    }

    break;

}