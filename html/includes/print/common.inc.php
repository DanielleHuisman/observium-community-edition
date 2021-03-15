<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */


function build_table($array, $options = [])
{

   // start table
   $html = '<table class="table table-condensed table-striped">';
   // header row
   $html .= '<thead><tr>';
   foreach($array[0] as $key => $value)
   {
      $html .= '<th>' . $key . '</th>';
   }
   $html .= '</tr></thead>';

   // data rows
   foreach($array as $key => $value)
   {
      $html .= '<tr>';
      foreach($value as $key2 => $value2)
      {
        switch ($options[$key2])
        {
          case 'unixtime':
            $value2 = format_unixtime($value2);
            break;
          case 'device':
            $value2 = generate_device_link($value2);
            break;
        }
        $html .= '<td>' . $value2 . '</td>';
      }
      $html .= '</tr>';
   }

   // finish table and return it

   $html .= '</table>';
   return $html;
}

function build_table_row($array, $options = [])
{
  // Calculate max columns
  $cols = 0;
  foreach ($array as $entry)
  {
    $cols = max(count((array)$entry), $cols);
  }

  // start table
  $html = '<table class="table table-condensed table-striped">';
  // header row
  /*
  $html .= '<thead><tr>';
  foreach($array[0] as $key => $value)
  {
    $html .= '<th>' . $key . '</th>';
  }
  $html .= '</tr></thead>';
  */

  // data rows
  foreach($array as $key => $entry)
  {
    $html .= '<tr>';
    $html .= '<td><strong>' . $key . '</strong></td>';
    $values = array_values((array)$entry);

    for ($i = 0; $i <= $cols; $i++)
    {
      $value = $values[$i];
      switch ($options[$key])
      {
        case 'unixtime':
          $value = format_unixtime($value);
          break;
        case 'device':
          $value = generate_device_link($value);
          break;
      }
      $html .= '<td>' . $value . '</td>';
    }
    $html .= '</tr>';
  }

  // finish table and return it

  $html .= '</table>';
  return $html;
}

/**
 * Print refresh meta header
 * 
 * This function print refresh meta header and return status for current page
 * with refresh time, list and allowed refresh times.
 * Uses variables $vars['refresh'], $_SESSION['refresh'], $config['page_refresh']
 *
 * @global string $GLOBALS['config']['page_refresh']
 * @global string $_SESSION['refresh']
 * @param array $vars
 * @return array $return
 */
function print_refresh($vars)
{
  if (!$_SESSION['authenticated'])
  {
    // Do not print refresh header if not authenticated session, common use in logon page
    return array('allowed' => FALSE);
  }

  $refresh_array = $GLOBALS['config']['wui']['refresh_times']; // Allowed refresh times
  $refresh_time  = 300;                               // Default page reload time (5 minutes)
  if (isset($vars['refresh']))
  {
    if (is_numeric($vars['refresh']) && in_array($vars['refresh'], $refresh_array))
    {
      $refresh_time = (int)$vars['refresh'];
      // Store for SESSION
      session_set_var('page_refresh', $refresh_time);
    }
    // Unset refresh var after
    unset($GLOBALS['vars']['refresh']);
  }
  else if (isset($_SESSION['page_refresh']) && in_array($_SESSION['page_refresh'], $refresh_array))
  {
    $refresh_time = (int)$_SESSION['page_refresh'];
  }
  else if (is_numeric($GLOBALS['config']['page_refresh']) && in_array($GLOBALS['config']['page_refresh'], $refresh_array))
  {
    $refresh_time = (int)$GLOBALS['config']['page_refresh'];
  }

  // List vars where page refresh full disabled - FIXME move to definitions!
  $refresh_disabled = $GLOBALS['config']['wui']['refresh_disabled'];

  $refresh_allowed = TRUE;
  foreach ($refresh_disabled as $var_test)
  {
    $var_count = count($var_test);
    foreach ($var_test as $key => $value)
    {
      if (isset($vars[$key]) && $vars[$key] == $value) { $var_count--; }
    }
    if ($var_count === 0)
    {
      $refresh_allowed = FALSE;
      break;
    }
  }

  $return = array('allowed' => $refresh_allowed,
                  'current' => $refresh_time,
                  'list'    => $refresh_array);

  if ($refresh_allowed && $refresh_time)
  {
    register_html_resource('meta', array('http-equiv' => 'refresh', 'content' => $refresh_time));
    //echo('  <meta http-equiv="refresh" content="'.$refresh_time.'" />' . "\n");
    $return['nexttime'] = time() + $refresh_time; // Add unixtime for next refresh
  }

  return $return;
}

/**
 * Helper function for generate table header with sort links
 * This used in other print_* functions
 *
 * @param array $cols Array with column IDs, names and column styles
 * @param array $vars Array with current selected column ID and/or variables for generate column link
 * @return string $string
 */
function get_table_header($cols, $vars = array())
{
  // Always clean sort vars
  $sort       = $vars['sort'];
  $sort_order = strtolower($vars['sort_order']);
  if (!in_array($sort_order, array('asc', 'desc', 'reset')))
  {
    $sort_order = 'acs';
  }
  unset($vars['sort'], $vars['sort_order']);

  if (isset($vars['show_header']) && !$vars['show_header'])
  {
    // Do not show any table header if show_header == FALSE
    $string  = '  <thead style="line-height: 0; visibility: collapse;">' . PHP_EOL;
  } else {
    $string  = '  <thead>' . PHP_EOL;
  }
  $string .= '    <tr>' . PHP_EOL;
  foreach ($cols as $id => $col)
  {
    if (is_array($col))
    {
      $name  = $col[0];
      $style = ' '.$col[1]; // Column styles/classes
    } else {
      $name  = $col;
      $style = '';
    }
    $string .= '      <th'.$style.'>';
    if ($name == NULL)
    {
      $string .= '';         // Column without Name and without Sort
    }
    else if (is_int($id) || stristr($id, "!") != FALSE)
    {
      $string .= $name;      // Column without Sort
    }
    else if (!empty($vars) || $sort)
    {
      // Sort order cycle: asc -> desc -> reset
      if ($sort == $id)
      {
        switch ($sort_order)
        {
          case 'desc':
            $name .= '&nbsp;&nbsp;<i class="small glyphicon glyphicon-triangle-top"></i>';
            $sort_array = array();
            //$vars['sort_order'] = 'reset';
            break;
          case 'reset':
            //unset($vars['sort'], $vars['sort_order']);
            $sort_array = array();
            break;
          default:
            // ASC
            $name .= '&nbsp;&nbsp;<i class="small glyphicon glyphicon-triangle-bottom"></i>';
            $sort_array = array('sort' => $id, 'sort_order' => 'desc');
            //$vars['sort_order'] = 'desc';
        }
      } else{
        $sort_array = array('sort' => $id);
      }
      $string .= '<a href="'. generate_url($vars, $sort_array).'">'.$name.'</a>'; // Column now sorted (selected)
    } else {
      $string .= $name;      // Sorting is not available (if vars empty or FALSE)
    }
    $string .= '</th>' . PHP_EOL;
  }
  $string .= '    </tr>' . PHP_EOL;
  $string .= '  </thead>' . PHP_EOL;

  return $string;
}

function print_error_permission($text = NULL, $escape = TRUE)
{
  if (empty($text))
  {
    $text = 'You have insufficient permissions to view this page.';
  }
  else if ($escape)
  {
    $text = escape_html($text);
  }
  echo('<div style="margin:auto; text-align: center; margin-top: 50px; max-width:600px">');
  print_error('<h4>Permission error</h4>' . PHP_EOL . $text);
  echo('</div>');
}

/**
 * Generate html with label group
 *
 * @param array $params List of button items
 * @param array $opt Array with group params and styles
 * @param bool $escape Escape or not Item text
 *
 * @return string Generated html
 */
function get_label_group($params = [], $opt = [], $escape = TRUE) {
  $html = '<span class="label-group"';
  if ($opt['style']) {
    $html .= ' style="' . $opt['style'] . '"';
  }
  $html .= '>' . PHP_EOL;

  $items_count = count($params);
  $html_params = [];
  foreach ($params as $param_id => $param)
  {
    // If param is just string, convert to simple group
    if (is_string($param))
    {
      $param = ['text' => $param, 'event' => 'default'];
    }

    //$html_param = '<span id="'.$param_id.'"'; // open span
    $html_param = '<div id="'.$param_id.'"'; // open div, I use div for fix display label group in navbar
    // Item style
    if ($param['style']) {
      $html_param .= ' style="' . $param['style'] . '"';
    }
    // Item class
    if ($param['event']) {
      $class = 'label label-'.$param['event'];
    } else {
      $class = '';
    }
    if ($param['class']) {
      $class .= ' '.$param['class'];
    }
    if ($class) {
      $html_param .= ' class="' . $class . '"';
    } else {
      // Default
      $html_param .= ' class="label label-default"';
    }
    // Icons?
    // any custom data attribs?
    $html_param .= '>';
    // Item text
    if ($param['text']) {
      $html_param .= (bool)$escape ? escape_html($param['text']) : $param['text'];
    }
    //$html_param .= '</span>'; // close span
    $html_param .= '</div>'; // close div

    $html_params[] = $html_param;
  }

  // Return single label (without group), since label group for single item is incorrect
  if (count($params) === 1) {
    return array_shift($html_params);
  }

  $html .= implode('', $html_params) . PHP_EOL;
  $html .= '</span>'. PHP_EOL;

  return $html;
}

/**
 * Generate html with button group
 *
 * @param array $params List of button items
 * @param array $opt Array with group params and styles
 * @param bool $escape Escape or not Item text
 *
 * @return string Generated html
 */
function get_button_group($params = [], $opt = [], $escape = TRUE) {
  $html = '<div class="btn-group"';
  if ($opt['style']) {
    $html .= ' style="' . $opt['style'] . '"';
  }
  $html .= '>' . PHP_EOL;

  $html_params = [];
  foreach ($params as $param_id => $param)
  {
    // If param is just string, convert to simple group
    if (is_string($param))
    {
      $param = ['text' => $param, 'event' => 'default'];
    }

    $html_param = ' <div id="'.$param_id.'"'; // open span
    // Item style
    if ($param['style']) {
      $html_param .= ' style="' . $param['style'] . '"';
    }
    // Item class
    if ($param['event']) {
      $class = 'btn btn-'.$param['event'];
    } else {
      $class = '';
    }
    if ($param['size']) {
      $class .= ' btn-'.$param['size'];
    }
    if ($param['class']) {
      $class .= ' '.$param['class'];
    }
    if ($class) {
      $html_param .= ' class="' . $class . '"';
    } else {
      // Default
      $html_param .= ' class="btn btn-default"';
    }
    // Icons?
    // any custom data attribs?
    $html_param .= '>';
    // Item text
    if ($param['text']) {
      $html_param .= (bool)$escape ? escape_html($param['text']) : $param['text'];
    }
    $html_param .= '</div>'; // close span

    $html_params[] = $html_param;
  }

  $html .= implode(PHP_EOL, $html_params) . PHP_EOL;
  $html .= '</div>'. PHP_EOL;

  return $html;
}

/**
 * Generate icon html tag
 *
 * @param string $icon  Icon name in definitions (ie: flag) or by css class (ie: sprite-flag)
 * @param string $class Additional class(es) for changing main icon view
 * @param array  $attribs Url/link extended attributes (ie data-*, class, style)
 *
 * @return string HTML icon tag like <i class="sprite-flag"></i> or emoji style :flag-us: -> <span class="icon-emoji">&#x1F1FA;&#x1F1F8;</span>
 */
function get_icon($icon, $class = '', $attribs = [])
{
  global $config;

  // Passed already html icon tag, return as is
  if (str_exists($icon, [ '<', '>' ])) { return $icon; }

  $icon = trim(strtolower($icon));
  if (isset($config['icon'][$icon]))
  {
    // Defined icons
    $icon = $config['icon'][$icon];
  }
  elseif (!strlen($icon))
  {
    // Empty icon, return empty string
    return '';
  }

  // Emoji styled icons, ie :flag-us:
  if (preg_match('/^:[\w\-_]+:$/', $icon))
  {
    // icon-emoji is pseudo class, for styling emoji as other icons
    return '<span class="icon-emoji">' . get_icon_emoji($icon) . '</span>';
  }

  // Append glyphicon main class if these icons used
  if (str_starts($icon, 'glyphicon-'))
  {
    $icon = 'glyphicon '.$icon;
  }

  if ($class)
  {
    // Additional classes
    $attribs['class'] = array_merge((array)$class, (array)$attribs['class']);
  }
  $attribs['class'] = array_merge([ $icon ], (array)$attribs['class']);

  return '<i ' . generate_html_attribs($attribs) . '></i>';
}

/**
 * Generate emoji icon (ie html hex entity)
 *
 * @param string $emoji Emoji name in definitions (ie: flag-us or :flag-us:)
 * @param string $type  Type of emoji for return (currently only html supported)
 * @param array  $attribs Url/link extended attributes (currently unused)
 *
 * @return string Emoji in requested type, for html ie: :flag-us: -> &#x1F1FA;&#x1F1F8;
 */
function get_icon_emoji($emoji, $type = 'html', $attribs = [])
{
  global $config;

  // Emoji definitions not loaded by default!
  // Load of first request
  if (!isset($config['emoji']['zero']))
  {
    include_once $config['install_dir'] . '/includes/definitions/emoji.inc.php';
  }

  $emoji_name = strtolower(trim($emoji, ": \t\n\r\0\x0B"));

  // Unknown emoji name, return original string
  if (!isset($config['emoji'][$emoji_name]))
  {
    return $emoji;
  }
  $type  = strtolower($type);
  $entry = $config['emoji'][$emoji_name];

  switch ($type)
  {
    case 'unified':
    case 'non_qualified':
      $return = escape_html($entry[$type]);
      break;
    case 'html':
    default:
      // 26A0-FE0F -> &#x26A0;&#xFE0F;
      $emoji_html = explode('-', escape_html($entry['unified'])); // escaping for prevent pass to definitions html code
      $return = '&#x' . implode(';&#x', $emoji_html) . ';';
  }

  return $return;
}

/**
 * Generate icon html tag with country flag
 *
 * @param string $country Country name or code
 *
 * @return string HTML icon tag like <i class="flag flag-us"></i>
 */
function get_icon_country($country)
{
  global $config;

  // Unificate country name
  $country = country_from_code($country);
  // Find ISO 2 country code (must be first in definitions)
  $code = strtolower(array_search($country, $config['rewrites']['countries']));
  if (empty($code))
  {
    return get_icon('location');
  }

  return '<i class="flag flag-'.$code.'"></i>';
}

// EOF
