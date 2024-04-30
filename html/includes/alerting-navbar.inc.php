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


if (!is_array($alert_rules)) {
    $alert_rules = cache_alert_rules();
}

/* Hardcode Device sysContact
if ($_SESSION['userlevel'] >= 7 &&
    !dbExist('alert_contacts', '`contact_method` = ?', [ 'syscontact' ])) {
  $syscontact = [
    'contact_descr'            => 'Device sysContact',
    'contact_method'           => 'syscontact',
    'contact_endpoint'         => '{"syscontact":"device"}',
    //'contact_disabled'         => '0',
    //'contact_disabled_until'   => NULL,
    //'contact_message_custom'   => 0,
    //'contact_message_template' => NULL
  ];
  dbInsert($syscontact, 'alert_contacts');
}
*/

$navbar['class'] = 'navbar-narrow';
$navbar['brand'] = 'Alerting';

$pages = [
    'alerts'            => 'Alerts',
    'alert_checks'      => 'Alert Checkers',
    'alert_log'         => 'Alert Logging',
    'alert_maintenance' => 'Scheduled Maintenance',
    'syslog_alerts'     => 'Syslog Alerts',
    'syslog_rules'      => 'Syslog Rules',
    'contacts'          => 'Contacts'
];

foreach ($pages as $page_name => $page_desc) {
    if ($vars['page'] == $page_name) {
        $navbar['options'][$page_name]['class'] = "active";
    }

    $navbar['options'][$page_name]['url']  = generate_url(['page' => $page_name]);
    $navbar['options'][$page_name]['text'] = escape_html($page_desc);

    if (in_array($page_name, ['alert_checks', 'alert_maintenance', 'contacts', 'syslog_rules'])) {
        $navbar['options'][$page_name]['userlevel'] = 5; // Minimum user level to display item
    }
}
$navbar['options']['alert_maintenance']['community'] = FALSE; // Not exist in Community Edition

/* No longer used. Alert table entries are managed automatically.
$navbar['options']['update']['url']       = generate_url(['page' => 'alert_regenerate', 'action' => 'update']);
$navbar['options']['update']['text']      = 'Rebuild';
$navbar['options']['update']['icon']      = $config['icon']['rebuild'];
$navbar['options']['update']['right']     = TRUE;
$navbar['options']['update']['userlevel'] = 10; // Minimum user level to display item
// We don't really need to highlight Regenerate, as it's not a display option, but an action.
// if ($vars['action'] == 'update') { $navbar['options']['update']['class'] = 'active'; }
*/

$navbar['options']['sadd']['url']       = generate_url(['page' => 'add_syslog_rule']);
$navbar['options']['sadd']['text']      = 'Add Syslog Rule';
$navbar['options']['sadd']['icon']      = $config['icon']['syslog-rule-add'];
$navbar['options']['sadd']['right']     = TRUE;
$navbar['options']['sadd']['userlevel'] = 9; // Minimum user level to display item

$navbar['options']['add']['url']       = generate_url(['page' => 'add_alert_check']);
$navbar['options']['add']['text']      = 'Add Checker';
$navbar['options']['add']['icon']      = $config['icon']['alert-rule-add'];
$navbar['options']['add']['right']     = TRUE;
$navbar['options']['add']['userlevel'] = 9; // Minimum user level to display item


// Print out the navbar defined above
print_navbar($navbar);
unset($navbar);


// EOF
