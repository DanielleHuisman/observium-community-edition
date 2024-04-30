<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage functions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

/* WARNING. This file should be load after config.php! */

/**
 * The function returns content of specific template
 *
 * @param string $type    Type of template (currently only 'alert', 'group', 'notification')
 * @param string $subtype Subtype of template type, examples: 'email' for notification, 'device' for group or alert
 * @param string $name    Name for template, also can used as name for group/alert/etc (lowercase!)
 *
 * @return string $template Content of specific template
 */
function get_template($type, $subtype, $name = '')
{
    $template     = ''; // If template not found, return empty string
    $template_dir = $GLOBALS['config']['template_dir'];
    $default_dir  = $GLOBALS['config']['install_dir'] . '/includes/templates';

    if (empty($name)) {
        // If name empty, than seems as we use filename instead (ie: email_html.tpl, type_somename.xml)
        $basename = basename($subtype);
        [$subtype, $name] = explode('_', $basename, 2);
    }

    switch ($type) {
        case 'alert':
        case 'group':
        case 'notification':
            $name = preg_replace('/\.(tpl|xml)$/', '', strtolower($name));
            // Notifications used raw text templates (with mustache format),
            //  all other used XML templates
            // Examples:
            //  /opt/observium/templates/alert/device_myname.xml
            //  /opt/observium/templates/notification/email_html.tpl
            if ($type === 'notification') {
                $ext = '.tpl';
            } else {
                $ext = '.xml';
            }
            $template_file = $type . '/' . $subtype . '_' . $name . $ext;
            if (is_file($template_dir . '/' . $template_file)) {
                // User templates
                $template = file_get_contents($template_dir . '/' . $template_file);
            } elseif (is_file($default_dir . '/' . $template_file)) {
                // Default templates
                $template = file_get_contents($default_dir . '/' . $template_file);
            }
            break;
        default:
            print_debug("Template type '$type' with subtype '$subtype' and name '$name' not found!");
    }

    return $template;
}

/**
 * The function returns list of all template files for specific template type(s)
 *
 * @param mixed $types Type name of list of types as array
 *
 * @return array $template_list List of template files with type as array keys
 */
function get_templates_list($types)
{
    $template_list = []; // If templates not found, return empty list
    $template_dir  = $GLOBALS['config']['template_dir'];
    $default_dir   = $GLOBALS['config']['install_dir'] . '/includes/templates';

    if (!is_array($types)) {
        $types = [$types];
    }
    foreach ($types as $type) {
        switch ($type) {
            case 'alert':
            case 'group':
            case 'notification':
                if ($type === 'notification') {
                    $ext = '.tpl';
                } else {
                    $ext = '.xml';
                }
                foreach (glob($default_dir . '/' . $type . '/?*_?*' . $ext) as $filename) {
                    // Default templates, before user templates for override
                    $template_list[$type][] = $filename;
                }
                // Examples:
                //  /opt/observium/templates/alert/device_myname.xml
                //  /opt/observium/templates/notification/email_html.tpl
                foreach (glob($template_dir . '/' . $type . '/?*_?*' . $ext) as $filename) {
                    // User templates
                    $template_list[$type][] = $filename;
                }
                break;
            default:
                print_debug("Template type '$type' unknown!");
        }
    }

    return $template_list;
}

/**
 * The function returns array with all avialable templates
 *
 * @param mixed $types Type name of list of types as array
 *
 * @return array $template_array List of template with type and subtype as keys and name as values
 */
function get_templates_array($types)
{
    $template_array = []; // If templates not found, return empty array

    $template_list = get_templates_list($types); // Get templates file list

    foreach ($template_list as $type => $list) {
        foreach ($list as $filename) {
            $basename = basename($filename);
            $basename = preg_replace('/\.(tpl|xml)$/', '', $basename);
            [$subtype, $name] = explode('_', $basename, 2);
            $template_array[$type][$subtype] = strtolower($name);
        }
    }

    return $template_array;
}

/**
 * This is very-very-very simple template engine (or not simple?),
 * only some basic conversions and uses Mustache/CTemplate syntax.
 *
 * no cache/logging and others, for now support only this tags:
 * standart php comments
 * {{! %^ }} - intext comments
 *  {{var}}  - escaped var
 * {{{var}}} - unescaped var
 * {{var.subvar}} - dot notation vars
 * {{.}}     - implicit iterator
 * {{#var}} some text {{/var}} - if/list condition
 * {{^var}} some text {{/var}} - inverted (negative) if condition
 * options:
 * 'is_file', if set to TRUE, than get template from file $config['install_dir']/includes/templates/$template.tpl
 *            if set to FALSE (default), than use template from variable.
 */
// NOTE, do NOT use this function for generate pages, as adama said!
function simple_template($template, $tags, $options = ['is_file' => FALSE, 'use_cache' => FALSE])
{
    if (!is_string($template) || !is_array($tags)) {
        // Return false if template not string (or filename) and tags not array
        return FALSE;
    }

    if (isset($options['is_file']) && $options['is_file']) {
        // Get template from file
        $template = get_template('notification', $template);

        // Return false if no file content or false file read
        if (!$template) {
            return FALSE;
        }
    }

    // Cache disabled for now, i think this can generate huge array
    /**
     * $use_cache = isset($options['use_cache']) && $options['use_cache'] && $tags;
     * if ($use_cache)
     * {
     * global $cache;
     *
     * $timestamp     = time();
     * $template_csum = md5($template);
     * $tags_csum     = md5(json_encode($tags));
     *
     * if (isset($cache['templates'][$template_csum][$tags_csum]))
     * {
     * if (($timestamp - $cache['templates'][$template_csum][$tags_csum]['timestamp']) < 600)
     * {
     * return $cache['templates'][$template_csum][$tags_csum]['string'];
     * }
     * }
     * }
     */

    // convert windows end lines to unix
    $string = preg_replace('/\r\n/', "\n", $template);

    // Removes multi-line comments and does not create
    // a blank line, also treats white spaces/tabs
    $string = preg_replace('![ \t]*/\*.*?\*/[ \t]*[\r\n]?!s', '', $string);

    // Removes single line '//' comments, treats blank characters
    $string = preg_replace('![ \t]*//.*[ \t]*[\r\n]?!', '', $string);

    // Removes in-text comments {{! any text }}
    $string = preg_replace('/{{!.*?}}/', '', $string);

    // Strip blank lines
    //$string = preg_replace('/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/', PHP_EOL, $string);

    // Replace keys, loops and other template syntax
    $string = simple_template_replace($string, $tags);

    /**
     * if ($use_cache)
     * {
     * $cache['templates'][$template_csum][$tags_csum] = array('timestamp' => $timestamp,
     * 'string'    => $string);
     * }
     */

    return $string;
}

function simple_template_replace($string, $tags)
{
    // Note for future: to match Unix LF (\n), MacOS<9 CR (\r), Windows CR+LF (\r\n) and rare LF+CR (\n\r)
    // EOL patern should be: /((\r?\n)|(\n?\r))/
    $patterns = [
        // {{#var}} some text {{/var}}
        'list_condition'     => '![ \t]*{{#[ \t]*([ \w[:punct:]]+?)[ \t]*}}[ \t]*[\r\n]?(.*?){{/[ \t]*\1[ \t]*}}[ \t]*([\r\n]?)!s',
        // {{^var}} some text {{/var}}
        'negative_condition' => '![ \t]*{{\^[ \t]*([ \w[:punct:]]+?)[ \t]*}}[ \t]*[\r\n]?(.*?){{/[ \t]*\1[ \t]*}}[ \t]*([\r\n]?)!s',
        // {{{var}}}
        'var_noescape'       => '!{{{[ \t]*([^}{#\^\?/]+?)[ \t]*}}}!',
        // {{var}}
        'var_escape'         => '!{{[ \t]*([^}{#\^\?/]+?)[ \t]*}}!',
    ];
    // Main loop
    foreach ($patterns as $condition => $pattern) {
        switch ($condition) {
            // LIST condition first!
            case 'list_condition':
                // NEGATIVE condition second!
            case 'negative_condition':
                if (preg_match_all($pattern, $string, $matches)) {
                    foreach ($matches[1] as $key => $var) {
                        $test_tags = isset($tags[$var]) && $tags[$var];
                        if (($condition == 'list_condition' && $test_tags) ||
                            ($condition == 'negative_condition' && !$test_tags)) {
                            $replace = preg_replace('/[\t\ ]+$/', '', $matches[2][$key]);
                            //if (!$matches[3][$key])
                            //{
                            //  // Remove last newline if condition at EOF
                            //  $replace = preg_replace('/[\r\n]$/', '', $replace);
                            //}
                            if ($condition == 'list_condition' && is_array($tags[$var])) {
                                // Additional remove first newline if pressent
                                $replace = preg_replace('/^[\r\n]/', '', $matches[2][$key]);
                                // If tag is array, use recurcive repeater
                                $repeate = [];
                                foreach ($tags[$var] as $item => $entry) {
                                    $repeate[] = simple_template_replace($replace, $entry);
                                }
                                $replace = implode('', $repeate);
                            }
                        } else {
                            $replace = '';
                        }
                        $string = str_replace($matches[0][$key], $replace, $string);
                    }
                }
                break;
            // Next var not escaped
            case 'var_noescape':
                // Next var escaped
            case 'var_escape':
                if (preg_match_all($pattern, $string, $matches)) {
                    foreach ($matches[1] as $key => $var) {
                        if ($var === '.' && is_string($tags)) {
                            // This conversion for implicit iterator {{.}}
                            $tags    = ['.' => $tags];
                            $subvars = [];
                        } else {
                            $subvars = explode('.', $var);
                        }

                        if (isset($tags[$var])) {
                            // {{ var }}, {{{ var_noescape }}}
                            $replace = ($condition === 'var_noescape' ? $tags[$var] : htmlspecialchars($tags[$var], ENT_QUOTES, 'UTF-8'));
                        } elseif (count($subvars) > 1 && is_array($tags[$subvars[0]])) {
                            // {{ var.with.iterator }}, {{{ var.with.iterator.noescape }}}
                            $replace = $tags[$subvars[0]];
                            array_shift($subvars);
                            foreach ($subvars as $subvar) {
                                if (isset($replace[$subvar])) {
                                    $replace = $replace[$subvar];
                                } else {
                                    unset($replace);
                                    break;
                                }
                            }
                            $replace = ($condition === 'var_noescape' ? $replace : htmlspecialchars($replace, ENT_QUOTES, 'UTF-8'));
                        } else {
                            // By default if tag not exist, remove var from template
                            $replace = '';
                        }
                        $string = str_replace($matches[0][$key], $replace, $string);
                    }
                }
                break;
        }
    }
    //var_dump($string);
    return $string;
}

function json_export($type, &$vars) {
    if (!get_var_true($vars['export'])) {
        unset($vars['export'], $vars['saveas'], $vars['filename']);
        return;
    }

    $saveas = FALSE;
    if (isset($vars['filename']) && get_var_true($vars['saveas'])) {
        // save requested
        if (!is_valid_param($vars['filename'], 'filename')) {
            // incorrect filename
            //bdump($vars);
            unset($vars['export'], $vars['saveas'], $vars['filename']);
            return;
        }
        $saveas = $vars['filename'];
    }
    //bdump($saveas);
    unset($vars['export'], $vars['saveas'], $vars['filename']);

    switch ($type) {
        case 'groups':
        case 'group':
            $for_export = groups_export($vars);
            break;

        case 'alerts':
        case 'alert':
            $for_export = alerts_export($vars);
            break;

        default:
            unset($vars['export'], $vars['saveas'], $vars['filename']);
            return;
    }

    $options = get_var_true($vars['formatted']) || !$saveas ? JSON_PRETTY_PRINT : 0;
    $json    = safe_json_encode($for_export, $options);

    if ($saveas) {
        // save as
        download_as_file($json, $saveas);
    } else {
        // print
        r($json);
    }

    return $json;
}

/**
 * Send any string to browser as file
 *
 * @param string $string   String content for save as file
 * @param string $filename Filename
 */
function download_as_file($string, $filename = "observium_export.json") {

    //$echo = ob_get_contents();
    ob_end_clean(); // Clean and disable buffer

    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if ($ext === 'xml') {
        header('Content-type: text/xml');
    } elseif ($ext === 'json') {
        header("Content-type: application/json; charset=utf-8");
    } else {
        header('Content-type: text/plain');
    }
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    header("Content-Length: " . strlen($string));
    header("Pragma: no-cache");
    header("Expires: 0");

    echo($string); // Send string content to browser output

    exit(0); // Stop any other output
}

// EOF
