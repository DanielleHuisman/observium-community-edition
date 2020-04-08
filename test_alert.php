#!/usr/bin/env php
<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage cli
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

chdir(dirname($argv[0]));

$options = getopt("a:c:rd");

include("includes/sql-config.inc.php");
include($config['html_dir']."/includes/functions.inc.php");

//var_dump(cli_is_piped());

$scriptname = basename($argv[0]);

$cli = TRUE;

$localhost = get_localhost();

print_message("%g".OBSERVIUM_PRODUCT." ".OBSERVIUM_VERSION."\n%WTest Alert Notification%n\n", 'color');

//print_versions();

// Allow the URL building code to build URLs with proper links.
$_SESSION['userlevel'] = 10;

if ($options['a'] || $options['c'])
{

  if ($config['alerts']['disable']['all'])
  {
    print_warning("All alert notifications disabled in config \$config['alerts']['disable']['all'], ignore it for testing!");
    $config['alerts']['disable']['all'] = FALSE;
  }

  $alert_rules = cache_alert_rules();
  $alert_assoc = cache_alert_assoc();

  if ($options['a'])
  {
    // Test by alert id

    $sql  = "SELECT * FROM `alert_table`";
    $sql .= " WHERE `alert_table_id` = ?";

    $entry = dbFetchRow($sql, array($options['a']));
    print_debug_vars($entry, 1);

    // Generate alerts and insert them into the queue
    $ids = alert_notifier($entry);
  }
  else if ($options['c'])
  {
    // Test by contact id
    $ids   = array();

    // Fetch notification example from json file
    if (isset($options['r']))
    {
      $type = 'RECOVER';
    } else {
      $type = 'ALERT';
    }
    $notification = json_decode(file_get_contents($config['install_dir'] . '/includes/templates/test/notification_'.$type.'.json'), TRUE);

    $sql  = "SELECT * FROM `alert_contacts`";
    //$sql .= " WHERE `contact_disabled` = 0 AND `contact_id` = ?";
    $sql .= " WHERE `contact_id` = ?";

    $contact = dbFetchRow($sql, array($options['c']));
    print_debug_vars($contact, 1);

    if ($contact)
    {
      // Overrides
      $notification['endpoints']          = json_encode($contact);
      $notification['notification_added'] = time();

      // Add this notification to queue
      $ids[] = dbInsert($notification, 'notifications_queue');
    } else {
      print_cli_data("Unknown contact", 'Contact ID '.$options['c'].' not exist.');
    }
  }

  // Sent alert notifications which were just inserted into the queue
  process_notifications(array('notification_id' => $ids));

}
else if ($options['c'])
{
  if ($config['alerts']['disable']['all'])
  {
    print_warning("All alert notifications disabled in config \$config['alerts']['disable']['all'], ignore it for testing!");
    $config['alerts']['disable']['all'] = FALSE;
  }

} else {

  print_cli("USAGE:
$scriptname -a alert_entry_id [-d debug]
$scriptname -c contact_id [-r] [-d debug]

", 'color');

  $arguments = new \cli\Arguments();
  $arguments->addFlag('-d',  'Turn on debug output');
  $arguments->addFlag('-dd', 'More verbose debug output');
  $arguments->addOption('-a', array(
    'default'     => '<alert entry id>',
    'description' => 'Send test notification to for an alert entry'));
  $arguments->addOption('-c', array(
    'default'     => '<contact id>',
    'description' => 'Send test notification to this contact id'));
  $arguments->addFlag('-r',  'With -c <ID> option, send RECOVER notification instead ALERT');
  echo $arguments->getHelpScreen();
  echo PHP_EOL . PHP_EOL;
}

// EOF
