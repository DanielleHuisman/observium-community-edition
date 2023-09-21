#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     cli
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

use cli\Arguments;

chdir(dirname($argv[0]));

$options = getopt("a:c:rsd");

include("includes/observium.inc.php");
include($config['html_dir'] . "/includes/functions.inc.php");

//var_dump(cli_is_piped());

$cli = TRUE;

$localhost = get_localhost();

print_message("%g" . OBSERVIUM_PRODUCT . " " . OBSERVIUM_VERSION . "\n%WTest Alert Notification%n\n", 'color');

//print_versions();

// Allow the URL building code to build URLs with proper links.
$_SESSION['userlevel'] = 10;

if ($options['a'] || $options['c']) {

    if ($config['alerts']['disable']['all']) {
        print_warning("All alert notifications disabled in config \$config['alerts']['disable']['all'], ignore it for testing!");
        $config['alerts']['disable']['all'] = FALSE;
    }

    $alert_rules = cache_alert_rules();
    $alert_assoc = cache_alert_assoc();

    if ($options['a']) {
        // Test by alert id

        $sql = "SELECT * FROM `alert_table`";
        $sql .= " WHERE `alert_table_id` = ?";

        $entry = dbFetchRow($sql, [$options['a']]);
        print_debug_vars($entry, 1);

        // Generate alerts and insert them into the queue
        $ids = alert_notifier($entry);
    } elseif ($options['c']) {
        // Test by contact id
        $ids = [];

        // Fetch notification example from json file
        if (isset($options['s'])) {
            $type = 'SYSLOG';
        } elseif (isset($options['r'])) {
            $type = 'RECOVER';
        } else {
            $type = 'ALERT';
        }
        $notification = safe_json_decode(file_get_contents($config['install_dir'] . '/includes/templates/test/notification_' . $type . '.json'));

        $sql = "SELECT * FROM `alert_contacts`";
        //$sql .= " WHERE `contact_disabled` = 0 AND `contact_id` = ?";
        $sql .= " WHERE `contact_id` = ?";

        $contact = dbFetchRow($sql, [$options['c']]);
        print_debug_vars($contact, 1);

        if ($contact) {
            // Overrides
            $alert_unixtime                     = time();
            $notification['endpoints']          = safe_json_encode($contact);
            $notification['notification_added'] = $alert_unixtime;

            // Override time inside test notification
            $message_tags                            = safe_json_decode($notification['message_tags']);
            $message_tags['ALERT_UNIXTIME']          = $alert_unixtime;                        // Standard unixtime
            $message_tags['ALERT_TIMESTAMP']         = date('Y-m-d H:i:s P', $alert_unixtime); //           ie: 2000-12-21 16:01:07 +02:00
            $message_tags['ALERT_TIMESTAMP_RFC2822'] = date('r', $alert_unixtime);             // RFC 2822, ie: Thu, 21 Dec 2000 16:01:07 +0200
            $message_tags['ALERT_TIMESTAMP_RFC3339'] = date(DATE_RFC3339, $alert_unixtime);    // RFC 3339, ie: 2005-08-15T15:52:01+00:00
            $notification['message_tags']            = safe_json_encode($message_tags);
            unset($message_tags);

            // Add this notification to queue
            $ids[] = dbInsert($notification, 'notifications_queue');
        } else {
            print_cli_data("Unknown contact", 'Contact ID ' . $options['c'] . ' not exist.');
        }
    }

    // Sent alert notifications which were just inserted into the queue
    process_notifications(['notification_id' => $ids]);

} else {

    print_cli("USAGE:
$scriptname -a alert_entry_id [-d debug]
$scriptname -c contact_id [-r] [-s] [-d debug]

", 'color');

    $arguments = new Arguments();
    $arguments -> addFlag('-d', 'Turn on debug output');
    $arguments -> addFlag('-dd', 'More verbose debug output');
    $arguments -> addOption('-a', [
      'default'     => '<alert entry id>',
      'description' => 'Send test notification to for an alert entry']);
    $arguments -> addOption('-c', [
      'default'     => '<contact id>',
      'description' => 'Send test notification to this contact id']);
    $arguments -> addFlag('-r', 'With -c <ID> option, send RECOVER notification instead ALERT');
    $arguments -> addFlag('-s', 'With -c <ID> option, send SYSLOG notification');
    echo $arguments -> getHelpScreen();
    echo PHP_EOL . PHP_EOL;
}

// EOF
