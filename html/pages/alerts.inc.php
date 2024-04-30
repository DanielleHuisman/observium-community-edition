<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

include($config['html_dir'] . "/includes/alerting-navbar.inc.php");

if (!isset($vars['status'])) {
    $vars['status'] = 'failed';
}
if (!$vars['entity_type']) {
    $vars['entity_type'] = "all";
}

$navbar['class'] = "navbar-narrow";
$navbar['brand'] = "Alert Types";

$types = dbFetchRows("SELECT DISTINCT `entity_type` FROM `alert_table` " . generate_where_clause(generate_query_permitted_ng([ 'alert' ])));

$types_count = safe_count($types);

$navbar['options']['all']['url']  = generate_url($vars, ['page' => 'alerts', 'entity_type' => 'all']);
$navbar['options']['all']['text'] = escape_html(nicecase('all'));
if ($vars['entity_type'] === 'all') {
    $navbar['options']['all']['class'] = "active";
    $navbar['options']['all']['url']   = generate_url($vars, ['page' => 'alerts', 'entity_type' => NULL]);
}

foreach ($types as $thing) {
    if ($vars['entity_type'] == $thing['entity_type']) {
        $navbar['options'][$thing['entity_type']]['class'] = "active";
        $navbar['options'][$thing['entity_type']]['url']   = generate_url($vars, ['page' => 'alerts', 'entity_type' => NULL]);
    } else {
        if ($types_count > 6) {
            $navbar['options'][$thing['entity_type']]['class'] = "icon";
        }
        $navbar['options'][$thing['entity_type']]['url'] = generate_url($vars, ['page' => 'alerts', 'entity_type' => $thing['entity_type']]);
    }
    $navbar['options'][$thing['entity_type']]['icon'] = $config['entities'][$thing['entity_type']]['icon'];
    $navbar['options'][$thing['entity_type']]['text'] = $config['entities'][$thing['entity_type']]['names'];
}

// Add filters to navbar
navbar_alerts_filter($navbar, $vars);

// Print out the navbar defined above
print_navbar($navbar);

// Cache the alert_tests table for use later
$alert_rules = cache_alert_rules($vars);

// Print out a table of alerts matching $vars
if ($vars['status'] !== 'failed') {
    $vars['pagination'] = TRUE;
}

print_alert_table($vars);

register_html_title('Alerts');
if (is_string($vars['entity_type']) && isset($config['entities'][$vars['entity_type']]['names'])) {
    register_html_title($config['entities'][$vars['entity_type']]['names']);
}

// EOF
