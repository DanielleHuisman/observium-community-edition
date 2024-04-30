<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($_SESSION['userlevel'] < 10) {
    print_error_permission();
    return;
}

register_html_title('Settings');

$navbar['brand'] = 'Settings';
$navbar['class'] = 'navbar-narrow';

$formats = ['default'        => 'Configuration',
            'changed_config' => 'Changed Configuration',
            'config'         => 'Dump of Configuration'];

if (isset($vars['format']) && $vars['format'] !== 'default' &&
    isset($formats[$vars['format']]) && is_file($config['html_dir'] . '/pages/settings/' . $vars['format'] . '.inc.php')) {
    include($config['html_dir'] . '/pages/settings/' . $vars['format'] . '.inc.php');
    return;
}

// print_warning('<strong>Experimental Feature!</strong> If you are uncomfortable using experimental code, please continue using config.php to configure Observium.');

// Load config variable descriptions into memory
include($config['install_dir'] . '/includes/config-variables.inc.php');

// Loop all variables and build an array with sections, subsections and variables
// This is only done on this page, so there is no performance issue for the rest of Observium
foreach ($config_variable as $varname => $variable) {
    $config_subsections[$variable['section']][$variable['subsection']][$varname] = $variable;
}

// Change/save config actions.

if (($vars['submit'] === 'save' || $vars['action'] === 'save') && request_token_valid($vars)) {
    //r($vars);

    foreach (process_sql_vars($vars) as $param => $entry) {
        // This sets:
        // $deletes = array();
        // $sets = array();
        // $errors = array();
        // $set_attribs = array(); // set obs_attribs
        //print_warning($param);
        //r($entry);

        //r([ $param, $entry]);

        // fixme: unreadable: unroll via array of variable names

        $$param = $entry;
    }

    $updates = 0;

    // Set fields that were submitted with custom value
    if (safe_count($sets)) {

        $query = 'SELECT * FROM `config` WHERE ' . generate_query_values(array_keys($sets), 'config_key');
        // Fetch current rows in config file so we know which one to UPDATE and which one to INSERT
        $in_db = [];
        foreach (dbFetchRows($query) as $row) {
            $in_db[$row['config_key']] = $row['config_value'];
        }

        foreach ($sets as $key => $value) {
            $serialize = serialize($value);
            if (isset($in_db[$key])) {
                // Already present in DB, update row
                if ($serialize !== $in_db[$key]) {
                    // Submitted value is different from current value
                    dbUpdate(['config_value' => $serialize], 'config', '`config_key` = ?', [$key]);
                    $updates++;
                }
            } else {
                // Not set in DB yet, insert row
                dbInsert(['config_key' => $key, 'config_value' => $serialize], 'config');
                $updates++;
            }
        }
    }

    // Delete fields that were reset to default
    if (safe_count($deletes)) {
        dbDelete('config', generate_query_values($deletes, 'config_key'));
        //r(generate_query_values_ng($deletes, 'config_key'));
        //r(dbError());
        $updates++;
    }

    // Print errors from validation above, if any
    foreach ($errors as $error) {
        print_error($error);
    }

    // Set obs attribs, example for syslog trigger
    //r($set_attribs);
    foreach ($set_attribs as $attrib => $value) {
        set_obs_attrib($attrib, $value);
    }

    if ($updates) {
        print_success("Settings updated.<br />Please note Web UI setting takes effect only when refreshing the page <i>after</i> saving the configuration. Please click <a href=\"" . $_SERVER['REQUEST_URI'] . "\">here</a> to reload the page.");
        //fixme -- save with ajax.
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        print_error('No changes made.');
    }
}

$link_array = ['page' => 'settings'];

foreach ($config_sections as $type => $section) {
    if (isset($section['edition']) && $section['edition'] != OBSERVIUM_EDITION) {
        // Skip sections not allowed for current Observium edition
        continue;
    }
    if (!isset($vars['section'])) {
        $vars['section'] = $type;
    }

    if ($vars['section'] == $type) {
        $navbar['options'][$type]['class'] = 'active';
    }
    $navbar['options'][$type]['url']  = generate_url($link_array, ['section' => $type]);
    $navbar['options'][$type]['text'] = $section['text'];
}

$navbar['options_right']['all']['url']  = generate_url($link_array, ['section' => 'all']);
$navbar['options_right']['all']['text'] = 'All';
$navbar['class']                        = 'navbar-narrow';

if ($vars['section'] == 'all') {
    $navbar['options_right']['all']['class'] = 'active';
}

print_navbar($navbar);

include($config['html_dir'] . '/pages/settings/default.inc.php');

// EOF
