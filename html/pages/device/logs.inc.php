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

// Allowed sections: eventlog, syslog, logalert, alertlog
$sections = [ 'eventlog' ];
if ($config['enable_syslog']) {
    $sections[] = 'syslog';
    if (OBSERVIUM_EDITION != 'community') {
        $sections[] = 'logalert';
    }
}

$sections[] = 'alertlog';

if (empty($vars['section'])) {
    $vars['section'] = 'eventlog';
} elseif (!is_alpha($vars['section']) || !in_array($vars['section'], $sections, TRUE)) {
    //r($vars['section']);
    print_error_permission("Unknown Logs section.");
    return;
}

$navbar['brand'] = "Logging";
$navbar['class'] = "navbar-narrow";

foreach ($sections as $section) {
    $type = strtolower($section);
    if (!isset($vars['section'])) {
        $vars['section'] = escape_html($section);
    }

    if ($vars['section'] == $section) {
        $navbar['options'][$section]['class'] = "active";
    }
    $navbar['options'][$section]['url']  = generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'logs', 'section' => $section]);
    $navbar['options'][$section]['text'] = nicecase($section);
}

print_navbar($navbar);

include($config['html_dir'] . '/pages/device/logs/' . $vars['section'] . '.inc.php');

// EOF
