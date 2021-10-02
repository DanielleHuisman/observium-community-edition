<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

if ($_SESSION['userlevel'] < 10)
{
  print_error_permission();
  return;
}

register_html_title('Settings');

/**
 * Convert amqp|conn|host into returning value of $arrayvar['amqp']['conn']['host']
 *
 * @param string $sqlname Variable name
 * @param array $arrayvar Array where to see param
 * @param Boolean $try_isset If True, return isset($sqlname) check, else return variable content
 * @return mixed
 */
function sql_to_array($sqlname, $arrayvar, $try_isset = TRUE) {

  list($key, $pop_sqlname) = explode('|', $sqlname, 2);

  if (!is_array($arrayvar)) { return FALSE; }

  $isset = array_key_exists($key, $arrayvar);

  if (safe_empty($pop_sqlname)) {
    // Reached the variable, return its content, or FALSE if it's not set
    if ($try_isset) {
      return $isset;
    }
    return $isset ? $arrayvar[$key] : NULL;
  }

  if ($isset) {
    // Recurse to lower level
    return sql_to_array($pop_sqlname, $arrayvar[$key], $try_isset);
  }
  return FALSE;
}

$navbar['brand'] = 'Settings';
$navbar['class'] = 'navbar-narrow';

$formats = array('default' => 'Configuration',
                 'changed_config' => 'Changed Configuration',
                 'config' => 'Dump of Configuration');

if (isset($vars['format']) && $vars['format'] !== 'default' &&
    isset($formats[$vars['format']]) && is_file($config['html_dir'] . '/pages/settings/'.$vars['format'].'.inc.php'))
{
  include($config['html_dir'] . '/pages/settings/'.$vars['format'].'.inc.php');
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
  $updates = 0;
  $deletes = array();
  $sets = array();
  $errors = array();
  $set_attribs = array(); // set obs_attribs

  // Submit button pressed
  foreach ($vars as $varname => $value)
  {
    if (str_starts($varname, 'varset_')) {
      $varname = substr($varname, 7);
      $sqlname = str_replace('__', '|', $varname);
      $content = $vars[$varname];
      $confname = '$config[\'' . implode("']['",explode('|',$sqlname)) . '\']';
      $section = $config_variable[$sqlname]['section'];

      if ($vars[$varname . '_custom'])
      {
        $ok = FALSE;

        if (isset($config_variable[$sqlname]['edition']) && $config_variable[$sqlname]['edition'] != OBSERVIUM_EDITION)
        {
          // Skip variables not allowed for current Observium edition
          continue;
        }
        else if (isset($config_sections[$section]['edition']) && $config_sections[$section]['edition'] != OBSERVIUM_EDITION)
        {
          // Skip sections not allowed for current Observium edition
          continue;
        }

        // Split enum|foo|bar into enum  foo|bar
        list($vartype, $varparams) = explode('|', $config_variable[$sqlname]['type'], 2);
        $params = array();

        // If a callback function is defined, use this to fill params.
        if ($config_variable[$sqlname]['params_call'] && function_exists($config_variable[$sqlname]['params_call']))
        {
          $params = call_user_func($config_variable[$sqlname]['params_call']);
        // Else if the params are defined directly, use these.
        } else if (is_array($config_variable[$sqlname]['params']))
        {
          $params = $config_variable[$sqlname]['params'];
        }
        // Else use parameters specified in variable type (e.g. enum|1|2|5|10)
        else if (!empty($varparams))
        {
          foreach (explode('|', $varparams) as $param)
          {
            $params[$param] = array('name' => nicecase($param));
          }
        }

        switch ($vartype)
        {
          case 'int':
          case 'integer':
          case 'float':
            if (is_numeric($content))
            {
              $ok = TRUE;
            } else {
              $errors[] = $config_variable[$sqlname]['name'] . " ($confname) should be of <strong>numeric</strong> type. Setting '" . escape_html($content) . "' ignored.";
            }
            break;
          case 'bool':
          case 'boolean':
            switch ($content)
            {
              case 'on':
              case '1':
                $content = 1;
                $ok = TRUE;
                break;
              case 'off': // Won't actually happen. When "unchecked" the field is simply not transmitted...
              case '0':
              case '':    // ... which we catch here.
                $content = 0;
                $ok = TRUE;
                break;
              default:
                $ok = FALSE;
                $errors[] = $config_variable[$sqlname]['name'] . " ($confname) should be of type <strong>bool</strong>. Setting '" . escape_html($content) . "' ignored.";
            }
            break;
          case 'enum':
            if (!in_array($content, array_keys($params)))
            {
              $ok = FALSE;
              $errors[] = $config_variable[$sqlname]['name'] . " ($confname) should be one of <strong>" . implode(', ', $params) . "</strong>. Setting '" . escape_html($content) . "' ignored.";
            } else {
              $ok = TRUE;
            }
            break;
          case 'enum-array':
            //r($content);
            //r($params);
            foreach ($content as $value)
            {
              // Check all values
              if (!in_array($value, array_keys($params)))
              {
                $ok = FALSE;
                $errors[] = $config_variable[$sqlname]['name'] . " ($confname) all values should be one of this list <strong>" . implode(', ', $params) . "</strong>. Settings '" . implode(', ', $content) . "' ignored.";
                break;
              } else {
                $ok = TRUE;
              }
            }
            break;
          case 'enum-key-value':
            //r($content);
            //r($params);
            if (isset($content['key'], $content['value'])) {
              $tmp     = $content;
              $content = [];
              foreach ($tmp['key'] as $i => $key) {
                if (safe_empty($key) && safe_empty($tmp['value'][$i])) { continue; } // skip empty key-value pair
                $content[$key] = $tmp['value'][$i];
              }
              $ok = TRUE;
              //r($content);
            }
            break;
          case 'enum-freeinput':
            //r($content);
            //r($params);
            // FIXME, need validate values
            if (is_null($content))
            {
              // Empty array allowed, for override defaults
              $content = array();
              $ok = TRUE;
            }
            foreach ($content as $value) {
              $ok = TRUE;
            }
            break;
          case 'password':
          case 'string':
            $ok = TRUE;
            break;
          default:
            $ok = FALSE;
            $errors[] = $config_variable[$sqlname]['name'] . " ($confname) is of unknown type (" . $config_variable[$sqlname]['type'] . ")";
            break;
        }

        if ($ok)
        {
          $sets[dbEscape($sqlname)] = $content;

          // Set an obs_attrib, example for syslog trigger
          //r($config_variable[$sqlname]);
          if (isset($config_variable[$sqlname]['set_attrib']) && strlen($config_variable[$sqlname]['set_attrib']))
          {
            $set_attribs[$config_variable[$sqlname]['set_attrib']] = $config['time']['now'];
          }
        }
      } else {
        $deletes[] = "'".dbEscape($sqlname)."'";

        // Set an obs_attrib, example for syslog trigger
        //r($config_variable[$sqlname]);
        if (isset($config_variable[$sqlname]['set_attrib']) && strlen($config_variable[$sqlname]['set_attrib']))
        {
          $set_attribs[$config_variable[$sqlname]['set_attrib']] = $config['time']['now'];
        }
      }
    }
  }

  // Set fields that were submitted with custom value
  if (count($sets))
  {
    // Escape variable names for save use inside below SQL IN query
    $sqlset = array(); foreach (array_keys($sets) as $var) { $sqlset[] = "'" . dbEscape($var) . "'"; }

    // Fetch current rows in config file so we know which one to UPDATE and which one to INSERT
    $in_db_rows = dbFetchRows('SELECT * FROM `config` WHERE `config_key` IN ('.implode(',',$sqlset).')');
    foreach ($in_db_rows as $index => $row)
    {
      $in_db[$row['config_key']] = $row['config_value'];
    }

    foreach ($sets as $key => $value)
    {
      if (isset($in_db[$key]))
      {
        // Already present in DB, update row
        if (serialize($value) != $in_db[$key])
        {
          // Submitted value is different from current value
          dbUpdate(array('config_value' => serialize($value)), 'config', '`config_key` = ?', array($key));
          $updates++;
        }
      } else {
        // Not set in DB yet, insert row
        dbInsert(array('config_key' => $key, 'config_value' => serialize($value)), 'config');
        $updates++;
      }
    }
  }

  // Delete fields that were reset to default
  if (count($deletes))
  {
    dbDelete('config', '`config_key` IN ('.implode(',',$deletes).')');
    $updates++;
  }

  // Print errors from validation above, if any
  foreach ($errors as $error)
  {
    print_error($error);
  }

  // Set obs attribs, example for syslog trigger
  //r($set_attribs);
  foreach ($set_attribs as $attrib => $value)
  {
    set_obs_attrib($attrib, $value);
  }

  if ($updates)
  {
    print_success("Settings updated.<br />Please note Web UI setting takes effect only when refreshing the page <i>after</i> saving the configuration. Please click <a href=\"" . $_SERVER['REQUEST_URI'] . "\">here</a> to reload the page.");
    // Reload $config now, or form below will show old settings still
    include($config['install_dir'].'/includes/sql-config.inc.php');
  } else {
    print_error('No changes made.');
  }
}

$link_array = array('page' => 'settings');

foreach ($config_sections as $type => $section)
{
  if (isset($section['edition']) && $section['edition'] != OBSERVIUM_EDITION)
  {
    // Skip sections not allowed for current Observium edition
    continue;
  }
  if (!isset($vars['section'])) { $vars['section'] = $type; }

  if ($vars['section'] == $type) { $navbar['options'][$type]['class'] = 'active'; }
  $navbar['options'][$type]['url']  = generate_url($link_array, array('section' => $type));
  $navbar['options'][$type]['text'] = $section['text'];
}

$navbar['options_right']['all']['url']  = generate_url($link_array, array('section' => 'all'));
$navbar['options_right']['all']['text'] = 'All';
$navbar['class'] = 'navbar-narrow';

if ($vars['section'] == 'all') { $navbar['options_right']['all']['class'] = 'active'; }

print_navbar($navbar);

include($config['html_dir'].'/pages/settings/default.inc.php');

// EOF
