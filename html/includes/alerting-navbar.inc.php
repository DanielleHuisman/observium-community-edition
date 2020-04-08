<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage webui
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */


/// CONTACTS ACTIONS

$readonly = $_SESSION['userlevel'] < 10;

if (!$readonly)
{
  // FIXME: move all actions to separate include(s) with common options!
  if (isset($vars['submit']) && !isset($vars['action']))
  {
    // Convert submit to action (for compatibility)
    $vars['action'] = $vars['submit'];
  }
  if (isset($vars['action']) && isset($vars['contact_id']))
  {
    switch ($vars['action'])
    {
      case 'add_alert_checker_contact': // new ([action]_[type]_[action_target]_[param_to_change])
      case 'associate_alert_check':     // old
        if (is_numeric($vars['alert_checker_id']))
        {
          $vars['alert_test_id'] = $vars['alert_checker_id'];
        }
        else if (is_numeric($vars['alert_test_id']))
        {
          // ok
        } else {
          break;
        }

        //$vars['contact_id'] = (array)$vars['contact_id'];
        foreach ((array)$vars['contact_id'] as $contact_id)
        {
          if (!is_numeric($contact_id)) { continue; }

          $id = dbInsert('alert_contacts_assoc', array('aca_type'         => 'alert', // $vars['type']
                                                       'contact_id'       => $contact_id,
                                                       'alert_checker_id' => $vars['alert_test_id']));
          if ($id) { $rows_updated++; }
        }
        break;

      case 'add_alert_checker_contactall':
        if (!is_numeric($vars['alert_test_id']) || !$vars['confirm_add_all'])
        {
          break;
        }
        $exist_contacts = dbFetchColumn('SELECT `contact_id` FROM `alert_contacts_assoc` WHERE `aca_type` = ? AND `alert_checker_id` = ?', array('alert', $vars['alert_test_id']));
        //print_vars($exist_contacts);
        $sql = 'SELECT `contact_id` FROM `alert_contacts` WHERE `contact_disabled` = 0' .
               generate_query_values($exist_contacts, 'contact_id', '!='); // exclude exist contacts
        //print_vars($sql);
        foreach (dbFetchColumn($sql) as $contact_id)
        {
          $id = dbInsert('alert_contacts_assoc', array('aca_type'         => 'alert',
                                                       'contact_id'       => $contact_id,
                                                       'alert_checker_id' => $vars['alert_test_id']));
          if ($id) { $rows_updated++; }
        }
        unset($exist_contacts);
        break;

      case 'delete_alert_checker_contact': // new
      case 'delete_alert_contact_assoc':   // old
        if (!is_numeric($vars['alert_test_id']))
        {
          break;
        }
        //$vars['contact_id'] = (array)$vars['contact_id'];
        foreach ((array)$vars['contact_id'] as $contact_id)
        {
          if (!is_numeric($contact_id)) { continue; }

          $rows_updated += dbDelete('alert_contacts_assoc', '`aca_type` = ? AND `contact_id` = ? AND `alert_checker_id` = ?', array('alert', $contact_id, $vars['alert_test_id']));
        }
        break;

      case 'delete_alert_checker_contactall':
        if (!is_numeric($vars['alert_test_id']) || !$vars['confirm_delete_all'])
        {
          break;
        }
        $rows_updated += dbDelete('alert_contacts_assoc', '`aca_type` = ? AND `alert_checker_id` = ?', array('alert', $vars['alert_test_id']));
        break;

      case 'associate_syslog_rule':
        if (is_numeric($vars['la_id']))
        {
          //$vars['contact_id'] = (array)$vars['contact_id'];
          foreach ((array)$vars['contact_id'] as $contact_id)
          {
            $id = dbInsert('alert_contacts_assoc', array('aca_type'         => 'syslog', // $vars['type']
                                                         'contact_id'       => $vars['contact_id'],
                                                         'alert_checker_id' => $vars['la_id']));
            if ($id) { $rows_updated++; }
          }

          set_obs_attrib('syslog_rules_changed', time()); // Trigger reload syslog script

        }
        break;

      case 'delete_syslog_checker_contact': // new
        if (!is_numeric($vars['alert_test_id']))
        {
          break;
        }
        //$vars['contact_id'] = (array)$vars['contact_id'];
        foreach ((array)$vars['contact_id'] as $contact_id)
        {
          if (!is_numeric($contact_id)) { continue; }

          $rows_updated += dbDelete('alert_contacts_assoc', '`aca_type` = ? AND `contact_id` = ? AND `alert_checker_id` = ?', array('syslog', $contact_id, $vars['alert_test_id']));
        }

        set_obs_attrib('syslog_rules_changed', time()); // Trigger reload syslog script

        break;

      case 'update-contact-entry':
        $update_state = array();
        $contact = get_contact_by_id($vars['contact_id']);

        foreach (json_decode($contact['contact_endpoint']) as $field => $value)
        {
          $contact['endpoint_parameters'][$field] = $value;
        }

        $update_state['contact_disabled'] = $vars['contact_enabled'] == '1' ? 0 : 1;

        if (strlen($vars['contact_descr']) && $vars['contact_descr'] != $contact['contact_descr'])
        {
          $update_state['contact_descr'] = $vars['contact_descr'];
        }

        $data = $config['transports'][$contact['contact_method']];
        if (!count($data['parameters']['global']))   { $data['parameters']['global'] = array(); } // Temporary until we separate "global" out.
        if (!count($data['parameters']['optional'])) { $data['parameters']['optional'] = array(); }
        // Plan: add defaults for transport types to global settings, which we use by default, then be able to override the settings via this GUI
        // This needs supporting code in the transport to check for set variable and if not, use the global default

        $update_endpoint = $contact['endpoint_parameters'];
        foreach (array_merge($data['parameters']['required'], $data['parameters']['global'], $data['parameters']['optional']) as $parameter => $param_data)
        {
          if (strlen($vars['contact_endpoint_'.$parameter]) && $vars['contact_endpoint_'.$parameter] != $contact['endpoint_parameters'][$parameter])
          {
            $update_endpoint[$parameter] = $vars['contact_endpoint_'.$parameter];
          }
        }
        $update_endpoint = json_encode($update_endpoint);
        if ($update_endpoint != $contact['contact_endpoint'])
        {
          //r($update_endpoint);
          //r($contact['contact_endpoint']);
          $update_state['contact_endpoint'] = $update_endpoint;
        }

        $rows_updated = dbUpdate($update_state, 'alert_contacts', 'contact_id = ?', array($vars['contact_id']));
        break;
    }
    // Clean common action vars
    //unset($vars['submit'], $vars['action'], $vars['confirm']);
  }
}

/// END CONTACTS ACTIONS


if (!is_array($alert_rules)) { $alert_rules = cache_alert_rules(); }

$navbar['class'] = 'navbar-narrow';
$navbar['brand'] = 'Alerting';

$pages = array('alerts'            => 'Alerts',
               'alert_checks'      => 'Alert Checkers',
               'alert_log'         => 'Alert Logging',
               'alert_maintenance' => 'Scheduled Maintenance',
               'syslog_alerts'     => 'Syslog Alerts',
               'syslog_rules'      => 'Syslog Rules',
               'contacts'          => 'Contacts');

foreach ($pages as $page_name => $page_desc)
{
  if ($vars['page'] == $page_name)
  {
    $navbar['options'][$page_name]['class'] = "active";
  }

  $navbar['options'][$page_name]['url'] = generate_url(array('page' => $page_name));
  $navbar['options'][$page_name]['text'] = escape_html($page_desc);

  if (in_array($page_name, array('alert_checks', 'alert_maintenance', 'contacts', 'syslog_rules')))
  {
    $navbar['options'][$page_name]['userlevel'] = 5; // Minimum user level to display item
  }
}
$navbar['options']['alert_maintenance']['community'] = FALSE; // Not exist in Community Edition

$navbar['options']['update']['url']  = generate_url(array('page' => 'alert_regenerate', 'action' => 'update'));
$navbar['options']['update']['text'] = 'Rebuild';
$navbar['options']['update']['icon'] = $config['icon']['rebuild'];
$navbar['options']['update']['right'] = TRUE;
$navbar['options']['update']['userlevel'] = 10; // Minimum user level to display item
// We don't really need to highlight Regenerate, as it's not a display option, but an action.
// if ($vars['action'] == 'update') { $navbar['options']['update']['class'] = 'active'; }

$navbar['options']['sadd']['url']  = generate_url(array('page' => 'add_syslog_rule'));
$navbar['options']['sadd']['text'] = 'Add Syslog Rule';
$navbar['options']['sadd']['icon'] = $config['icon']['syslog-rule-add'];
$navbar['options']['sadd']['right'] = TRUE;
$navbar['options']['sadd']['userlevel'] = 10; // Minimum user level to display item

$navbar['options']['add']['url']  = generate_url(array('page' => 'add_alert_check'));
$navbar['options']['add']['text'] = 'Add Checker';
$navbar['options']['add']['icon'] = $config['icon']['alert-rule-add'];
$navbar['options']['add']['right'] = TRUE;
$navbar['options']['add']['userlevel'] = 10; // Minimum user level to display item


// Print out the navbar defined above
print_navbar($navbar);
unset($navbar);


// EOF
