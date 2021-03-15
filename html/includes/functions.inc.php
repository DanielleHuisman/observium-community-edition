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

// Notifications and alerts in bottom navbar
$notifications = array();
$alerts        = array();

// Enable caching, currently only for WUI
include_once($config['install_dir'] .'/includes/cache.inc.php');

include_once($config['html_dir'].'/includes/graphs/functions.inc.php');

$print_functions = array('addresses', 'events', 'mac_addresses', 'rows',
                         'status', 'arptable', 'fdbtable', 'navbar',
                         'search', 'syslogs', 'inventory', 'alert',
                         'authlog', 'dot1xtable', 'alert_log', 'logalert',
                         'common', 'routing', 'neighbours', 'billing');

foreach ($print_functions as $item)
{
  $print_path = $config['html_dir'].'/includes/print/'.$item.'.inc.php';
  if (is_file($print_path)) { include($print_path); }
}

// Load generic entity include
include($config['html_dir'].'/includes/entities/generic.inc.php');

// Load all per-entity includes
foreach ($config['entities'] as $entity_type => $item)
{
  $path = $config['html_dir'].'/includes/entities/'.$entity_type.'.inc.php';
  if (is_file($path)) { include($path); }
}

/**
 * Used for replace some strings at end of run all html scripts
 *
 * @param string $buffer HTML buffer from ob_start()
 * @return string Changed buffer
 */
function html_callback($buffer)
{
  global $config;

  // Install registered CSS/JS links
  $types = array(
    'css'    => '  <link href="%%STRING%%?v=' . OBSERVIUM_VERSION . '" rel="stylesheet" type="text/css" />' . PHP_EOL,
    'style'  => '  <style type="text/css">' . PHP_EOL . '%%STRING%%' . PHP_EOL . '  </style>' . PHP_EOL,
    'js'     => '  <script type="text/javascript" src="%%STRING%%?v=' . OBSERVIUM_VERSION . '"></script>' . PHP_EOL,
    'script' => '  <script type="text/javascript">' . PHP_EOL .
                '  <!-- Begin' . PHP_EOL . '%%STRING%%' . PHP_EOL .
                '  // End -->' . PHP_EOL . '  </script>' . PHP_EOL,
    'meta'   => '  <meta http-equiv="%%STRING_http-equiv%%" content="%%STRING_content%%" />' . PHP_EOL,
  );

  foreach ($types as $type => $string)
  {
    $uptype = strtoupper($type);
    if (isset($GLOBALS['cache_html']['resources'][$type]))
    {
      $$type = '<!-- ' . $uptype . ' BEGIN -->' . PHP_EOL;
      foreach (array_unique($GLOBALS['cache_html']['resources'][$type]) as $content) // Do not use global $cache variable, because it is reset before flush ob_cache
      {
        if (is_array($content))
        {
          // for meta
          foreach($content as $param => $value)
          {
            $string = str_replace('%%STRING_'.$param.'%%', $value, $string);
          }
          $$type .= $string;
        } else {
          $$type .= str_replace('%%STRING%%', $content, $string);
        }
      }
      $$type .= '  <!-- ' . $uptype . ' END -->' . PHP_EOL;
      $buffer = str_replace('<!-- ##' . $uptype . '_CACHE## -->' . PHP_EOL, $$type, $buffer);
    } else {
      // Clean template string
      $buffer = str_replace('<!-- ##' . $uptype . '_CACHE## -->', '', $buffer);
    }
  }

  // Replace page title as specified by the page modules
  if (!is_array($GLOBALS['cache_html']['title']))
  {
    // Title not set by any page, fall back to nicecase'd page name:
    if ($GLOBALS['vars']['page'] && $_SESSION['authenticated'])
    {
      $GLOBALS['cache_html']['title'] = array(nicecase($GLOBALS['vars']['page']));
    } else {
      // HALP. Likely main page, doesn't need anything else...
      $GLOBALS['cache_html']['title'] = array();
    }
  }

  // If suffix is set, put it in the back
  if ($config['page_title_suffix']) { $GLOBALS['cache_html']['title'][] = $config['page_title_suffix']; }

  // If prefix is set, put it in front
  if ($config['page_title_prefix']) { array_unshift($GLOBALS['cache_html']['title'], $config['page_title_prefix']); }

  // Build title with separators
  $title = implode($config['page_title_separator'], $GLOBALS['cache_html']['title']);

  // Replace title placeholder by actual title
  $buffer = str_replace('##TITLE##', escape_html($title), $buffer);

  // Page panel
  $buffer = str_replace('##PAGE_PANEL##', $GLOBALS['cache_html']['page_panel'], $buffer);

  // UI Alerts
  $buffer = str_replace('##UI_ALERTS##', $GLOBALS['ui_alerts'], $buffer);

  // Return modified HTML page source
  return $buffer;
}

/**
 * Parse $_GET, $_POST and REQUEST_URI into $vars array
 * 
 * @param array $vars_order Request variables order (POST, URI, GET)
 * @param boolean $auth this var or ($_SESSION['authenticated']) used for allow to use var_decode()
 * @return array array of vars
 */
function get_vars($vars_order = array(), $auth = FALSE)
{
  if (is_string($vars_order))
  {
    $vars_order = explode(' ', $vars_order);
  }
  elseif (empty($vars_order) || !is_array($vars_order))
  {
    $vars_order = array('POST', 'URI', 'GET'); // Default order
  }

  // Content-Type=>application/x-www-form-urlencoded
  $content_type = isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : $_SERVER['CONTENT_TYPE'];

  // https://github.com/swisskyrepo/PayloadsAllTheThings/tree/master/XSS%20Injection
  // XSS script regex
  // <sCrIpT> < / s c r i p t >
  // javascript:alert("Hello world");/
  // <svg onload=alert(document.domain)>
  $prevent_xss = '!(^\s*(J\s*A\s*V\s*A\s*)?S\s*C\s*R\s*I\s*P\s*T\s*:'.
                 '|<\s*/?\s*S\s*C\s*R\s*I\s*P\s*T\s*>'.
                 '|(<\s*s\s*v\s*g.*(o\s*n\s*l\s*o\s*a\s*d|s\s*c\s*r\s*i\s*p\s*t))'.
                 '|<\s*i\s*m\s*g.*o\s*n\s*e\s*r\s*r\s*o\s*r)!i';

  // Allow to use var_decode(), this prevent to use potentially unsafe serialize functions
  $auth = $auth || $_SESSION['authenticated'];

  $vars = array();
  foreach ($vars_order as $order)
  {
    $order = strtoupper($order);
    switch ($order)
    {
      case 'JSON':
        //r(getallheaders());
        //exit;

        // https://stackoverflow.com/questions/8893574/php-php-input-vs-post
        if (!in_array($content_type, [ 'application/x-www-form-urlencoded', 'multipart/form-data-encoded' ]))
        {
          $json = @json_decode(trim(file_get_contents("php://input")), TRUE, 512, OBS_JSON_DECODE);

          if (is_array_assoc($json))
          {
            //$vars = array_merge_indexed($vars, $json);
            $vars = $json; // Currently just override $vars, see ajax actions
            //$vars_got['JSON'] = 1;
          }
        }
        break;

      case 'POST':
        // Parse POST variables into $vars
        foreach ($_POST as $name => $value)
        {
          if (!isset($vars[$name]))
          {
            $vars[$name] = $auth ? var_decode($value) : $value;
            if (preg_match($prevent_xss, $vars[$name]))
            {
              // Prevent any <script> html tag inside vars, exclude any possible XSS with scripts
              unset($vars[$name]);
            }
            //$vars_got['POST'] = 1;
          }
        }
        break;

      case 'URI':
      case 'URL':
        // Parse URI into $vars
        $segments = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        foreach ($segments as $pos => $segment)
        {
          //$segment = urldecode($segment);
          if ($pos == "0" && !str_exists($segment, '='))
          {
            if (!preg_match($prevent_xss, $segment))
            {
              // Prevent any <script> html tag inside vars, exclude any possible XSS with scripts
              $segment = urldecode($segment);
              $vars['page'] = $segment;
            }
            //$vars_got['URI'] = 1;
          } else {
            list($name, $value) = explode('=', $segment, 2);
            if (!isset($vars[$name]))
            {
              if (!isset($value) || $value === '')
              {
                $vars[$name] = 'yes';
              } else {
                $value = str_replace('%7F', '/', urldecode($value)); // %7F (DEL, delete) - not defined in HTML 4 standard
                if (preg_match($prevent_xss, $value))
                {
                  // Prevent any <script> html tag inside vars, exclude any possible XSS with scripts
                  continue;
                }

                // Better to understand quoted vars
                $vars[$name] = get_var_csv($value, $auth);
                if (is_string($vars[$name]) && preg_match($prevent_xss, $vars[$name]))
                {
                  // Prevent any <script> html tag inside vars, exclude any possible XSS with scripts
                  unset($vars[$name]);
                }
              }
              //$vars_got['URI'] = 1;
            }
          }
        }
        break;

      case 'GET':
        // Parse GET variable into $vars
        foreach ($_GET as $name => $value)
        {
          if (!isset($vars[$name]))
          {
            $value = str_replace('%7F', '/', urldecode($value)); // %7F (DEL, delete) - not defined in HTML 4 standard
            if (preg_match($prevent_xss, $value))
            {
              // Prevent any <script> html tag inside vars, exclude any possible XSS with scripts
              continue;
            }
            
            // Better to understand quoted vars
            $vars[$name] = get_var_csv($value, $auth);
            if (is_string($vars[$name]) && preg_match($prevent_xss, $vars[$name]))
            {
              // Prevent any <script> html tag inside vars, exclude any possible XSS with scripts
              unset($vars[$name]);
            }
            //$vars_got['GET'] = 1;
          }
        }
        break;
    }
  }
  //print_success("Got [".implode(', ', array_keys($vars_got))."] vars ($content_type).");

  // Always convert location to array
  if (isset($vars['location']))
  {
    if ($vars['location'] === '')
    {
      // Unset location if is empty string
      unset($vars['location']);
    }
    else if (is_array($vars['location']))
    {
      // Additionally decode locations if array entries encoded
      foreach ($vars['location'] as $k => $location)
      {
        $vars['location'][$k] = $auth ? var_decode($location) : $location;
      }
    } else {
       // All other location strings covert to array
      $vars['location'] = array($vars['location']);
    }
  }

  //r($vars);
  return($vars);
}

/**
 * Validate requests by compare session and request tokens.
 * This prevents a CSRF attacks
 *
 * @param string|array $token Passed from request token or array with 'requesttoken' param inside.
 * @return boolean TRUE if session requesttoken same as passed from request
 */
function request_token_valid($token = NULL)
{
  if (is_array($token))
  {
    // If $vars array passed, fetch our default 'requesttoken' param
    $token = $token['requesttoken'];
  }

  //print_vars($_SESSION['requesttoken']);
  //print_vars($token);

  // See: https://stackoverflow.com/questions/6287903/how-to-properly-add-csrf-token-using-php
  // Session token generated after valid user auth in html/includes/authenticate.inc.php
  if (empty($_SESSION['requesttoken']))
  {
    // User not authenticated
    //print_warning("Request passed by unauthorized user.");
    return FALSE;
  }
  elseif (empty($token))
  {
    // Token not passed, WARNING seems as CSRF attack
    print_error("WARNING. Possible CSRF attack with EMPTY request token.");
    ///FIXME. need an user actions log
    return FALSE;
  }
  elseif (hash_equals($_SESSION['requesttoken'], $token))
  {
    // Correct session and request tokens, all good
    return TRUE;
  } else {
    // Passed incorrect request token,
    // WARNING seems as CSRF attack
    print_error("WARNING. Possible CSRF attack with INCORRECT request token.");
    ///FIXME. need an user actions log
    return FALSE;
  }
}

/**
 * Detect if current URI is link to graph
 *
 * @return boolean TRUE if current script is graph
 */
// TESTME needs unit testing
function is_graph()
{
  if (!defined('OBS_GRAPH'))
  {
    // defined in html/graph.php
    define('OBS_GRAPH', FALSE);
  }

  return OBS_GRAPH;
  //return (realpath($_SERVER['SCRIPT_FILENAME']) === realpath($GLOBALS['config']['html_dir'].'/graph.php'));
}

// TESTME needs unit testing
/**
 * Generates base64 data uri with alert graph
 *
 * @return string
 */
function generate_alert_graph($graph_array)
{
  global $config;

  $vars = $graph_array;
  $auth = (is_cli() ? TRUE : $GLOBALS['auth']); // Always set $auth to true for cli
  $vars['image_data_uri'] = TRUE;
  $vars['height'] = '150';
  $vars['width']  = '400';
  $vars['legend'] = 'no';
  $vars['from']   = $config['time']['twoday'];
  $vars['to']     = $config['time']['now'];

  include($config['html_dir'].'/includes/graphs/graph.inc.php');

  return $image_data_uri;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function datetime_preset($preset)
{
  $begin_fmt = 'Y-m-d 00:00:00';
  $end_fmt   = 'Y-m-d 23:59:59';

  switch($preset)
  {
    case 'sixhours':
      $from = date('Y-m-d H:i:00', strtotime('-6 hours'));
      $to   = date('Y-m-d H:i:59');
      break;
    case 'today':
      $from = date($begin_fmt);
      $to   = date($end_fmt);
      break;
    case 'yesterday':
      $from = date($begin_fmt, strtotime('-1 day'));
      $to   = date($end_fmt,   strtotime('-1 day'));
      break;
    case 'tweek':
      $from = (date('l') === 'Monday') ? date($begin_fmt) : date($begin_fmt, strtotime('last Monday'));
      $to   = (date('l') === 'Sunday') ? date($end_fmt)   : date($end_fmt,   strtotime('next Sunday'));
      break;
    case 'lweek':
      $from = date($begin_fmt, strtotime('-6 days'));
      $to   = date($end_fmt);
      break;
    case 'tmonth':
      $tmonth = date('Y-m');
      $from = $tmonth.'-01 00:00:00';
      $to   = date($end_fmt, strtotime($tmonth.' next month - 1 hour'));
      break;
    case 'lmonth':
      $from = date($begin_fmt, strtotime('previous month + 1 day'));
      $to   = date($end_fmt);
      break;
    case 'tquarter':
    case 'lquarter':
      $quarter = ceil(date('m') / 3); // Current quarter
      if ($preset === 'lquarter')
      {
        $quarter = $quarter - 1; // Previous quarter
      }
      $year = date('Y');
      if ($quarter < 1)
      {
        $year   -= 1;
        $quarter = 4;
      }
      $tmonth = $quarter * 3;
      $fmonth = $tmonth - 2;

      $from = $year.'-'.zeropad($fmonth).'-01 00:00:00';
      $to   = date('Y-m-t 23:59:59', strtotime($year.'-'.$tmonth.'-01'));
      break;
    case 'tyear':
      $from = date('Y-01-01 00:00:00');
      $to   = date('Y-12-31 23:59:59');
      break;
    case 'lyear':
      $from = date($begin_fmt, strtotime('previous year + 1 day'));
      $to   = date($end_fmt);
      break;
  }

  return array('from' => $from, 'to' => $to);
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function bug()
{
  echo('<div class="alert alert-error">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <strong>Bug!</strong> Please report this to the Observium development team.
</div>');
}

/**
 * This function determines type of web browser for current User-Agent (mobile/tablet/generic).
 * For more detailed browser info and custom User-Agent use detect_browser()
 *
 * @return string Return type of browser (generic/mobile/tablet)
 */
function detect_browser_type()
{
  $ua_info = detect_browser();

  return $ua_info['type'];
}

/**
 * This function determines detailed info of web browser by User-Agent agent string.
 * If User-Agent not passed, used current from $_SERVER['HTTP_USER_AGENT'] 
 *
 * @param string $user_agent Custom User-Agent string, by default, the value of HTTP User-Agent header is used
 *
 * @return array Return detected browser info: user_agent, type, icon, platform, browser, version,
 *                                             browser_full - full browser name (ie: Chrome 43.0)
 *                                             svg          - supported or not svg images (TRUE|FALSE),
 *                                             screen_ratio - for HiDPI screens it more that 1,
 *                                             screen_resolution - full resolution of client screen (if exist),
 *                                             screen_size  - initial size of browser window (if exist)
 */
// TESTME! needs unit testing
function detect_browser($user_agent = NULL)
{
  $ua_custom = !is_null($user_agent); // Used custom user agent?

  if (!$ua_custom && isset($GLOBALS['cache']['detect_browser']))
  {
    //if (isset($_COOKIE['observium_screen_ratio']) && !isset($GLOBALS['cache']['detect_browser']['screen_resolution']))
    //{
    //  r($_COOKIE);
    //}
    // Return cached info
    return $GLOBALS['cache']['detect_browser'];
  }

  $detect = new Mobile_Detect;

  if ($ua_custom)
  {
    // Set custom User-Agent
    $detect->setUserAgent($user_agent);
  } else {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
  }

  // Default type and icon
  $type = 'generic';
  $icon = 'icon-laptop';
  if ($detect->isMobile())
  {
    // Any phone device (exclude tablets).
    $type = 'mobile';
    $icon = 'glyphicon glyphicon-phone';
    if ($detect->isTablet())
    {
      // Any tablet device.
      $type = 'tablet';
      $icon = 'icon-tablet';
    }
  }

  // Detect Browser name, version and platform
  $ua_info = [];
  if (!empty($user_agent))
  {

    //$ua_info = parse_user_agent($user_agent);
    $parser = new \donatj\UserAgent\UserAgentParser();
    $ua = $parser->parse($user_agent);
    //r($ua);
    $ua_info['browser']  = $ua->browser();
    $ua_info['version']  = $ua->browserVersion();
    $ua_info['platform'] = $ua->platform();
    $ua_info['browser_full'] = $ua_info['browser'] . ' ' . preg_replace('/^([^\.]+(?:\.[^\.]+)?).*$/', '\1', $ua_info['version']);
    //r($ua_info);
  }

  $detect_browser = array('user_agent' => $user_agent,
                          'type'       => $type,
                          'icon'       => $icon,
                          'browser_full' => $ua_info['browser_full'],
                          'browser'    => $ua_info['browser'],
                          'version'    => $ua_info['version'],
                          'platform'   => $ua_info['platform']);

   // For custom UA, do not cache and return only base User-Agent info
  if ($ua_custom)
  {
    return $detect_browser;
  }

  // Load screen and DPI detector. This set cookies with:
  //  $_COOKIE['observium_screen_ratio'] - if ratio >= 2, than HiDPI screen is used
  //  $_COOKIE['observium_screen_resolution'] - screen resolution 'width x height', ie: 1920x1080
  //  $_COOKIE['observium_screen_size'] - current window size (less than resolution) 'width x height', ie: 1097x456
  register_html_resource('js', 'observium-screen.js');

  // Additional browser info (screen_ratio, screen_size, svg)
  if ($ua_info['browser'] === 'Firefox' && version_compare($ua_info['version'], '47.0') < 0)
  {
    // Do not use srcset in FF, while issue open:
    // https://bugzilla.mozilla.org/show_bug.cgi?id=1149357
    // Update, seems as in 47.0 partially fixed
    $zoom = 1;
  }
  else if (isset($_COOKIE['observium_screen_ratio']))
  {
    // Note, Opera uses ratio 1.5
    $zoom = round($_COOKIE['observium_screen_ratio']); // Use int zoom
  } else {
    // If JS not supported or cookie not set, use default zoom 2 (for allow srcset)
    $zoom = 2;
  }
  $detect_browser['screen_ratio'] = $zoom;
  //$detect_browser['svg']          = ($ua_info['browser'] == 'Firefox'); // SVG supported or allowed
  if (isset($_COOKIE['observium_screen_resolution']))
  {
    $detect_browser['screen_resolution'] = $_COOKIE['observium_screen_resolution'];
    //$detect_browser['screen_size']       = $_COOKIE['observium_screen_size'];
  }

  $GLOBALS['cache']['detect_browser'] = $detect_browser; // Store to cache

  //r($GLOBALS['cache']['detect_browser']);
  return $GLOBALS['cache']['detect_browser'];
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function data_uri($file, $mime)
{
  $contents = file_get_contents($file);
  $base64   = base64_encode($contents);

  return ('data:' . $mime . ';base64,' . $base64);
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function toner_map($descr, $colour)
{
  return str_istarts($descr, $GLOBALS['config']['toner'][$colour]);
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function toner_to_colour($descr, $percent)
{
  if     (str_starts($descr, 'C') || toner_map($descr, "cyan"   )) { $colour['left'] = "B6F6F6"; $colour['right'] = "33B4B1"; }
  elseif (str_starts($descr, 'M') || toner_map($descr, "magenta")) { $colour['left'] = "FBA8E6"; $colour['right'] = "D028A6"; }
  elseif (str_starts($descr, 'Y') || toner_map($descr, "yellow" )) { $colour['left'] = "FFF764"; $colour['right'] = "DDD000"; }
  elseif (str_starts($descr, 'K') || toner_map($descr, "black"  )) { $colour['left'] = "888787"; $colour['right'] = "555555"; }
  elseif (str_starts($descr, 'R') || toner_map($descr, "red"    )) { $colour['left'] = "FB6A4A"; $colour['right'] = "CB181D"; }

  if (!isset($colour['left']))
  {
    $colour = get_percentage_colours(100-$percent);
    $colour['found'] = FALSE;
  } else {
    $colour['found'] = TRUE;
  }

  return $colour;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_link($text, $vars, $new_vars = array(), $escape = TRUE)
{
  if ($escape) { $text = escape_html($text); }
  return '<a href="'.generate_url($vars, $new_vars).'">'.$text.'</a>';
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function pagination(&$vars, $total, $return_vars = FALSE)
{
  $pagesizes = array(10,20,50,100,500,1000,10000,50000); // Permitted pagesizes
  if (is_numeric($vars['pagesize']))
  {
    $per_page = (int)$vars['pagesize'];
  }
  else if (isset($_SESSION['pagesize']))
  {
    $per_page = $_SESSION['pagesize'];
  } else {
    $per_page = $GLOBALS['config']['web_pagesize'];
  }
  if (!$vars['short'])
  {
    // Permit fixed pagesizes only (except $vars['short'] == TRUE)
    foreach ($pagesizes as $pagesize)
    {
      if ($per_page <= $pagesize) { $per_page = $pagesize; break; }
    }
    if (isset($vars['pagesize']) && $vars['pagesize'] != $_SESSION['pagesize'])
    {
      if ($vars['pagesize'] != $GLOBALS['config']['web_pagesize'])
      {
        session_set_var('pagesize', $per_page); // Store pagesize in session only if changed default
      }
      else if (isset($_SESSION['pagesize']))
      {
        session_unset_var('pagesize');          // Reset pagesize from session
      }
    }
  }
  $vars['pagesize'] = $per_page;       // Return back current pagesize

  $page     = (int)$vars['pageno'];
  $lastpage = ceil($total/$per_page);
  if ($page < 1) { $page = 1; }
  else if (!$return_vars && $lastpage < $page) { $page = (int)$lastpage; }
  $vars['pageno'] = $page; // Return back current pageno

  if ($return_vars) { return ''; } // Silent exit (needed for detect default pagesize/pageno)

  $start = ($page - 1) * $per_page;
  $prev  = $page - 1;
  $next  = $page + 1;
  $lpm1  = $lastpage - 1;

  $adjacents = 3;
  $pagination = '';

  if ($total > 99 || $total > $per_page)
  {

    if($total > 9999) { $total_text = format_si($total); } else { $total_text = $total; }


    $pagination .= '<div class="row">' . PHP_EOL .
                   '  <div class="col-lg-1 col-md-2 col-sm-2" style="display: inline-block;">' . PHP_EOL .
                   //'    <span class="btn disabled" style="line-height: 20px;">'.$total.'&nbsp;Items</span>' . PHP_EOL .
                   '    <div class="box box-solid" style="padding: 4px 12px;">'.$total_text.'&nbsp;Items</div>' . PHP_EOL .
                   '  </div>' . PHP_EOL .
                   '  <div class="col-lg-10 col-md-8 col-sm-8">' . PHP_EOL .
                   '    <div class="pagination pagination-centered"><ul>' . PHP_EOL;

    if ($prev)
    {
      //$pagination .= '      <li><a href="'.generate_url($vars, array('pageno' => 1)).'">First</a></li>' . PHP_EOL;
      $pagination .= '      <li><a href="'.generate_url($vars, array('pageno' => $prev)).'">Prev</a></li>' . PHP_EOL;
    }

    if ($lastpage < 7 + ($adjacents * 2))
    {
      for ($counter = 1; $counter <= $lastpage; $counter++)
      {
        if ($counter == $page)
        {
          $pagination.= "<li class='active'><a>$counter</a></li>";
        } else {
          $pagination.= "<li><a href='".generate_url($vars, array('pageno' => $counter))."'>$counter</a></li>";
        }
      }
    }
    elseif ($lastpage > 5 + ($adjacents * 2))
    {
      if ($page < 1 + ($adjacents * 2))
      {
        for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
        {
          if ($counter == $page)
          {
            $pagination .= "<li class='active'><a>$counter</a></li>";
          } else {
            $class = '';
            //if ($counter > 9)
            //{
            //  $class = ' class="hidden-md hidden-sm hidden-xs"';
            //}
            //else if ($counter > 6)
            //{
            //  $class = ' class="hidden-sm hidden-xs"';
            //}
            $pagination .= "<li$class><a href='".generate_url($vars, array('pageno' => $counter))."'>$counter</a></li>";
          }
        }

        $pagination.= "<li><a href='".generate_url($vars, array('pageno' => $lpm1))."'>$lpm1</a></li>";
        $pagination.= "<li><a href='".generate_url($vars, array('pageno' => $lastpage))."'>$lastpage</a></li>";
      }
      elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
      {
        $pagination.= "<li><a href='".generate_url($vars, array('pageno' => '1'))."'>1</a></li>";
        $pagination.= "<li><a href='".generate_url($vars, array('pageno' => '2'))."'>2</a></li>";

        for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
        {
          if ($counter == $page)
          {
            $pagination.= "<li class='active'><a>$counter</a></li>";
          } else {
            $pagination.= "<li><a href='".generate_url($vars, array('pageno' => $counter))."'>$counter</a></li>";
          }
        }

        $pagination.= "<li><a href='".generate_url($vars, array('pageno' => $lpm1))."'>$lpm1</a></li>";
        $pagination.= "<li><a href='".generate_url($vars, array('pageno' => $lastpage))."'>$lastpage</a></li>";
      } else {
        $pagination.= "<li><a href='".generate_url($vars, array('pageno' => '1'))."'>1</a></li>";
        $pagination.= "<li><a href='".generate_url($vars, array('pageno' => '2'))."'>2</a></li>";
        for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
        {
          if ($counter == $page)
          {
            $pagination.= "<li class='active'><a>$counter</a></li>";
          } else {
            $class = '';
            //if ($lastpage - $counter > 9)
            //{
            //  $class = ' class="hidden-md hidden-sm hidden-xs"';
            //}
            //else if ($lastpage - $counter > 6)
            //{
            //  $class = ' class="hidden-sm hidden-xs"';
            //}
            $pagination.= "<li$class><a href='".generate_url($vars, array('pageno' => $counter))."'>$counter</a></li>";
          }
        }
      }
    }

    if ($page < $counter - 1)
    {
      $pagination.= "<li><a href='".generate_url($vars, array('pageno' => $next))."'>Next</a></li>";
      # No need for "Last" as we don't have "First", 1, 2 and the 2 last pages are always in the list.
      #$pagination.= "<li><a href='".generate_url($vars, array('pageno' => $lastpage))."'>Last</a></li>";
    }
    else if ($lastpage > 1)
    {
      $pagination.= "<li class='active'><a>Next</a></li>";
      #$pagination.= "<li class='active'><a>Last</a></li>";
    }

    $pagination.= "</ul></div></div>";

    //$values = array('' => array('name'))
    foreach ($pagesizes as $pagesize)
    {
      $value = generate_url($vars, array('pagesize' => $pagesize, 'pageno' => floor($start / $pagesize)));
      $name  = ($pagesize == $GLOBALS['config']['web_pagesize'] ? "[ $pagesize ]" : $pagesize);
      $values[$value] = array('name' => $name, 'class' => 'text-center');
    }
    $element = array('type'     => 'select',
                     'class'    => 'pagination',
                     'id'       => 'pagesize',
                     'name'     => '# '.$per_page,
                     'width'    => '90px',
                     'onchange' => "window.open(this.options[this.selectedIndex].value,'_top')",
                     'value'    => $per_page,
                     'data-style' => 'box',
                     'values'   => $values);

    $pagination.= '
       <div class="col-lg-1 col-md-2 col-sm-2">
       <form class="pull-right pagination" action="#">';

    $pagination .= generate_form_element($element);

    $pagination .= '</form></div></div>';
  }

  return $pagination;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_url($vars, $new_vars = array())
{
  $vars = ($vars) ? array_merge($vars, $new_vars) : $new_vars;

  $url = urlencode($vars['page']);

  if ($url[strlen($url)-1] !== '/') { $url .= '/'; }
  unset($vars['page']);

  foreach ($vars as $var => $value)
  {
    if ($var == "username" || $var == "password")
    {
      // Ignore these vars. They shouldn't end up in URLs.
    }
    else if (is_array($value))
    {
      $url .= urlencode($var) . '=' . var_encode($value) . '/';
    }
    else if ($value == "0" || $value != "" && strstr($var, "opt") === FALSE && is_numeric($var) === FALSE)
    {
      $url .= urlencode($var) . '=' . urlencode(str_replace('/', '%7F', $value)).'/'; // %7F converted back to / in get_vars()
    }
  }

  // If we're being generated outside of the web interface, prefix the generated URL to make it work properly.
  if (is_cli())
  {
    if ($GLOBALS['config']['web_url'] == 'http://localhost:80/')
    {
      // override default web_url by http://localhost/
      $url = 'http://'.get_localhost().'/'.$url;
    } else {
      $url = $GLOBALS['config']['web_url'] . $url;
    }
  }

  return($url);
}

function generate_html_attribs($attribs)
{
  if (!is_array($attribs)) { return ''; }

  // Filter attributes (data-*, aria-*, role, style, class)
  $attrib_pattern = '/^(data\-[_\w\-]+|aria\-[_\w\-]+|role|class|style|onclick)$/';

  $elements = [];
  foreach ($attribs as $attr => $value)
  {
    if (is_array($value))
    {
      $value = implode(' ', $value);
    }
    elseif (!strlen($value))
    {
      continue;
    }

    if (preg_match($attrib_pattern, $attr))
    {
      $elements[] = escape_html($attr) . '="' . escape_html($value) . '"';
    }
  }

  if (count($elements))
  {
    return implode(' ', $elements);
  }
  return '';
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_feed_url($vars)
{
  $url = FALSE;
  if (!class_exists('SimpleXMLElement')) { return $url; } // Break if class SimpleXMLElement is not available.

  if (is_numeric($_SESSION['user_id']) && is_numeric($_SESSION['userlevel']))
  {
    $key = get_user_pref($_SESSION['user_id'], 'atom_key');
  }
  if ($key)
  {
    $param   = array(rtrim($GLOBALS['config']['base_url'], '/').'/feed.php?id='.$_SESSION['user_id']);
    $param[] = 'hash='.encrypt($_SESSION['user_id'].'|'.$_SESSION['userlevel'].'|'.$_SESSION['auth_mechanism'], $key);

    $feed_type = 'atom';
    foreach ($vars as $var => $value)
    {
      if ($value != '')
      {
        switch ($var)
        {
          case 'v':
            if ($value == 'rss')
            {
              $param[] = "$var=rss";
              $feed_type = 'rss';
            }
            break;
          case 'feed':
            $title = "Observium :: ".ucfirst($value)." Feed";
            $param[] = 'size='.$GLOBALS['config']['frontpage']['eventlog']['items'];
            // no break here
          case 'size':
            $param[] = "$var=$value";
            break;
        }
      }
    }

    $baseurl = implode('&amp;', $param);

    $url = '<link href="'.$baseurl.'" rel="alternate" title="'.escape_html($title).'" type="application/'.$feed_type.'+xml" />';
  }

  return $url;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_location_url($location, $vars = array())
{
  if ($location === '') { $location = OBS_VAR_UNSET; }
  $value = var_encode($location);
  return generate_url(array('page' => 'devices', 'location' => $value), $vars);
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_overlib_content($graph_array, $text = NULL, $escape = TRUE)
{
  global $config;

  $graph_array['height'] = "100";
  $graph_array['width']  = "210";
  
  if ($escape) { $text = escape_html($text); }

  $content = '<div style="width: 590px;"><span style="font-weight: bold; font-size: 16px;">'.$text.'</span><br />';
  /*
  $box_args = array('body-style' => 'width: 590px;');
  if (strlen($text))
  {
    $box_args['title'] = $text;
  }
  $content = generate_box_open($box_args);
  */
  foreach (array('day', 'week', 'month', 'year') as $period)
  {
    $graph_array['from'] = $config['time'][$period];
    $content .= generate_graph_tag($graph_array);
  }
  $content .= "</div>";
  //$content .= generate_box_close();

  return $content;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function get_percentage_colours($percentage)
{

  if     ($percentage > '90') { $background['left']='cb181d'; $background['right']='fb6a4a'; $background['class'] = 'error'; }
  elseif ($percentage > '80') { $background['left']='cc4c02'; $background['right']='fe9929'; $background['class'] = 'warning'; }
  elseif ($percentage > '60') { $background['left']='6a51a3'; $background['right']='9e9ac8'; $background['class'] = 'information'; }
  elseif ($percentage > '30') { $background['left']='045a8d'; $background['right']='74a9cf'; $background['class'] = 'information'; }
  else                        { $background['left']='4d9221'; $background['right']='7fbc41'; $background['class'] = 'information'; }

  return($background);
}

/**
 * Generate common popup links which uses ajax/entitypopup.php
 *
 * @param string $type Popup type, see possible types in html/ajax/entitypopup.php
 * @param string $text Text used as link name and ajax data
 * @param array $vars Array for generate url
 * @param string Additional css classes for link
 * @param boolean $escape Escape or not text in url
 * @return string Returns string with link, when hover on this link show popup message based on type
 */
function generate_popup_link($type, $text = NULL, $vars = array(), $class = NULL, $escape = TRUE)
{
  if (!is_string($type) || !is_string($text)) { return ''; }

  if ($type === 'ip')
  {
    $addresses = [];
    // Find networks
    if (preg_match_all(OBS_PATTERN_IP_NET_FULL, $text, $matches))
    {
      $addresses += $matches['ip_network'];
    }
    // Find single addresses
    if (preg_match_all(OBS_PATTERN_IP_FULL, $text, $matches))
    {
      $addresses += $matches['ip'];
    }
    //r($addresses);
    if (count($addresses))
    {
      $return = $escape ? escape_html($text) : $text; // escape before replace (ip nets not escaped anyway)

      foreach ($addresses as $address)
      {
        list($ip, $net) = explode('/', $address);

        $ip_version = get_ip_version($ip);
        if ($ip_version === 6)
        {
          // Compress IPv6
          $address_compressed = Net_IPv6::compress($ip);
          if (strlen($net)) { $address_compressed = $address_compressed . '/' . $net; }
        } else {
          // IPv4 always compressed :)
          $address_compressed = $address;
        }

        $ip_type = get_ip_type($ip);
        //print_warning("$address : $ip_type");
        // Do not linkify some types of ip addresses
        if (in_array($ip_type, [ 'loopback', 'unspecified', 'broadcast', 'private', 'link-local', 'reserved' ]))
        {
          if (strlen($class))
          {
            $link = '<span class="'.$class.'">'.$address_compressed.'</span>';
            $return = str_replace($address, $link, $return);
          }
          elseif ($ip_version === 6)
          {
            $return = str_replace($address, $address_compressed, $return);
          }
          continue;
        }

        $url  = (count($vars) ? generate_url($vars) : 'javascript:void(0)'); // If vars empty, set link not clickable
        $link = '<a href="'.$url.'" class="entity-popup'.($class ? " $class" : '').'" data-eid="'.$ip.'" data-etype="'.$type.'">'.$address_compressed.'</a>';
        $return = str_replace($address, $link, $return);
      }

      return $return;
    }
  }
  elseif ($type === 'autodiscovery')
  {
    $data = $text;
    $text = get_icon('error');
    $escape = FALSE;
  }

  $url  = (count($vars) ? generate_url($vars) : 'javascript:void(0)'); // If vars empty, set link not clickable
  $data = isset($data) ? $data : $text;
  if ($escape) { $text = escape_html($text); }

  return '<a href="'.$url.'" class="entity-popup'.($class ? " $class" : '').'" data-eid="'.$data.'" data-etype="'.$type.'">'.$text.'</a>';
}

/**
 * Generate mouseover links with static tooltip from URL, link text, contents and a class.
 *
 * Tooltips with static position and linked to current object.
 * Note, mostly same as overlib_link(), except tooltip position.
 * Always display tooltip if content not empty
 *
 * @param string $url URL string
 * @param string $text Text displayed as link
 * @param string $contents Text content displayed in mouseover tooltip (only for non-mobile devices)
 * @param string $class Css class name used for link
 * @param array  $attribs Url/link extended attributes (ie data-*, class, style)
 * @param boolean $escape Escape or not link text
 * @return string
 */
// TESTME needs unit testing
function generate_tooltip_link($url, $text, $contents = '', $class = NULL, $attribs = [], $escape = FALSE)
{
  global $config, $link_iter;

  $link_iter++;

  $href = (strlen($url) ? 'href="' . $url . '"' : '');
  if ($escape) { $text = escape_html($text); }

  $attribs['class'] = array_merge((array)$class, (array)$attribs['class']);

  // Allow the Grinch to disable popups and destroy Christmas.
  $allow_mobile = (in_array(detect_browser_type(), array('mobile', 'tablet')) ? $config['web_mouseover_mobile'] : TRUE);
  if ($config['web_mouseover'] && strlen($contents) && $allow_mobile)
  {
    $attribs['style']        = 'cursor: pointer;';
    $attribs['data-rel']     = 'tooltip';
    $attribs['data-tooltip'] = $contents;
    //$output  = '<a '.$href.' class="'.$class.'" style="cursor: pointer;" data-rel="tooltip" data-tooltip="'.escape_html($contents).'">'.$text.'</a>';
  }

  return '<a '.$href.' '.generate_html_attribs($attribs).'>'.$text.'</a>';
}

/**
 * Generate mouseover links from URL, link text, contents and a class.
 *
 * Tooltips followed by mouse cursor.
 * Note, by default text NOT escaped for compatability with many old magic code usage.
 *
 * @param string $url URL string
 * @param string $text Text displayed as link
 * @param string $contents Text content displayed in mouseover tooltip (only for non-mobile devices)
 * @param string $class Css class name used for link
 * @param array  $attribs Url/link extended attributes (ie data-*, class, style)
 * @param boolean $escape Escape or not link text
 */
// TESTME needs unit testing
function generate_mouseover_link($url, $text, $contents, $class = NULL, $attribs = [], $escape = FALSE)
{
  global $config, $link_iter;

  $link_iter++;

  $href = (strlen($url) ? 'href="' . $url . '"' : '');
  if ($escape) { $text = escape_html($text); }

  if ($class)
  {
    $attribs['class'] = array_merge((array)$class, (array)$attribs['class']);
  }

  // Allow the Grinch to disable popups and destroy Christmas.
  $allow_mobile = (in_array(detect_browser_type(), array('mobile', 'tablet')) ? $config['web_mouseover_mobile'] : TRUE);
  if ($config['web_mouseover'] && strlen($contents) && $allow_mobile)
  {
    $attribs['style']        = 'cursor: pointer;';
    $attribs['class']        = array_merge([ 'tooltip-from-data' ], (array)$attribs['class']);
    $attribs['data-tooltip'] = $contents;
    //$output  = '<a '.$href.' class="tooltip-from-data '.$class.'" style="cursor: pointer;" data-tooltip="'.escape_html($contents).'">'.$text.'</a>';
  }

  return '<a '.$href.' '.generate_html_attribs($attribs).'>'.$text.'</a>';
}

function overlib_link($url, $text, $contents, $class = NULL, $attribs = [], $escape = FALSE)
{
  return generate_mouseover_link($url, $text, $contents, $class, $attribs, $escape);
}

/**
 * Generate menu links with item counts from URL, link text, contents and a class.
 *
 * Tooltips with static position and linked to current object.
 * Note, mostly same as overlib_link(), except tooltip position.
 * Always display tooltip if content not empty
 *
 * @param string $url URL string
 * @param string $text Text displayed as link
 * @param string $count Counts displayed at right
 * @param string $class Css class name used for count (default is 'label')
 * @param boolean $escape Escape or not link text
 */
// TESTME needs unit testing
function generate_menu_link($url, $text, $count = NULL, $class = 'label', $escape = FALSE, $alert_count = NULL)
{
  $href = (strlen($url) ? 'href="' . $url . '"' : '');
  if ($escape) { $text = escape_html($text); }

  $output = '<a role="menuitem" ' . $href . '><span>' . $text . '</span>';

  if (is_numeric($alert_count))
  {
    $output .= '<span class="label label-danger">' . $alert_count . '</span>';
  }

  if (is_numeric($count))
  {
    $output .= '<span class="' . $class . '">' . $count . '</span>';
  }

  $output .= '</a>';

  return $output;
}


/**
 * Generate menu links with item counts from URL, link text, contents and a class.
 *
 * Replaces previous function with multiple arguments. Should be used for all navbar menus
 *
 * @param string $array Array of options
 */
// TESTME needs unit testing
function generate_menu_link_new($array)
{

  $array = array_merge(array(
                           'count' => NULL,
                           'escape' => FALSE,
                           'class' => 'label'
                         ), $array);

  $link_opts = '';
  if (isset($array['link_opts'])) { $link_opts .= ' ' . $array['link_opts']; }
  if (isset($array['alt']))       { $link_opts .= ' data-rel="tooltip" data-tooltip="'.$array['alt'].'"'; }
  if (isset($array['id']))        { $link_opts .= ' id="'.$array['id'].'"'; }

  if (empty($array['url']) || $array['url'] == '#') { $array['url'] = 'javascript:void(0)'; }

  if ($array['escape']) { $array['text'] = escape_html($array['text']); }

  $output  = '<a role="menuitem" href="'.$array['url'].'" '.$link_opts.'>';

  $output .= '<span>';
  if (isset($array['icon']))
  {
    $output .= '<i class="' . $array['icon'] . '"></i>&nbsp;';
  }
  $output .= $array['text'] . '</span>';

  // Counter label(s) in navbar menu
  if (isset($array['count_array']) && count($array['count_array'])) {
    // Multiple counts as group
    $count_items = [];
    // Ok/Up
    if      ($array['count_array']['ok'])       { $count_items[] = ['event' => 'success', 'text' => $array['count_array']['ok']]; }
    else if ($array['count_array']['up'])       { $count_items[] = ['event' => 'success', 'text' => $array['count_array']['up']]; }
    // Warning
    if      ($array['count_array']['warning'])  { $count_items[] = ['event' => 'warning', 'text' => $array['count_array']['warning']]; }
    // Alert/Down
    if      ($array['count_array']['alert'])    { $count_items[] = ['event' => 'danger',  'text' => $array['count_array']['alert']]; }
    else if ($array['count_array']['down'])     { $count_items[] = ['event' => 'danger',  'text' => $array['count_array']['down']]; }
    // Ignored
    if      ($array['count_array']['ignored'])  { $count_items[] = ['event' => 'default', 'text' => $array['count_array']['ignored']]; }
    // Disabled
    if      ($array['count_array']['disabled']) { $count_items[] = ['event' => 'inverse', 'text' => $array['count_array']['disabled']]; }
    // Fallback to just count
    if (!count($count_items) && strlen($array['count_array']['count'])) {
      $count_items[] = ['event' => 'default', 'text' => $array['count_array']['count']];
    }

    //r(get_label_group($count_items));
    $output .= get_label_group($count_items);
  } else {
    // single counts
    if (is_numeric($array['alert_count']))
    {
      $output .= ' <span class="label label-danger">' . $array['alert_count'] . '</span> ';
    }

    if (is_numeric($array['count']))
    {
      $output .= ' <span class="' . $array['class'] . '">' . $array['count'] . '</span>';
    }
  }

  $output .= '</a>';

  return $output;
}


// Generate a typical 4-graph popup using $graph_array
// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_graph_popup($graph_array)
{
  global $config;

  // Todo - this should have entity headers where appropriate, too.

  // Take $graph_array and print day,week,month,year graps in overlib, hovered over graph

  $original_from = $graph_array['from'];

  $graph = generate_graph_tag($graph_array);

  /*
  $box_args = array('body-style' => 'width: 850px;');
  if (strlen($graph_array['popup_title']))
  {
    $box_args['title'] = $graph_array['popup_title'];
  }
  $content = generate_box_open($box_args);
  */
  unset($graph_array['style']);
  $content =  '<div class=entity-title><h4>'.$graph_array['popup_title'].'</h4></div>';
  $content .= '<div style="width: 850px">';
  $graph_array['legend']   = "yes";
  $graph_array['height']   = "100";
  $graph_array['width']    = "340";
  $graph_array['from']     = $config['time']['day'];
  $content .= generate_graph_tag($graph_array);
  $graph_array['from']     = $config['time']['week'];
  $content .= generate_graph_tag($graph_array);
  $graph_array['from']     = $config['time']['month'];
  $content .= generate_graph_tag($graph_array);
  $graph_array['from']     = $config['time']['year'];
  $content .= generate_graph_tag($graph_array);
  $content .= "</div>";
  //$content .= generate_box_close();

  $graph_array['from'] = $original_from;

  $graph_array['link'] = generate_url($graph_array, array('page' => 'graphs', 'height' => NULL, 'width' => NULL, 'bg' => NULL));

  return overlib_link($graph_array['link'], $graph, $content, NULL);
}

// output the popup generated in generate_graph_popup();
// TESTME needs unit testing
// DOCME needs phpdoc block
function print_graph_popup($graph_array)
{
  echo(generate_graph_popup($graph_array));
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function permissions_cache($user_id)
{
  $permissions = array();

  // Get permissions from user-specific and role tables.
  $permission_where  = '`user_id` = ? AND `auth_mechanism` = ?';
  $permission_params = [ $user_id, $GLOBALS['config']['auth_mechanism'] ];
  $entity_permissions = dbFetchRows("SELECT * FROM `entity_permissions` WHERE " . $permission_where, $permission_params);
  $roles_entity_permissions = dbFetchRows("SELECT * FROM `roles_entity_permissions` LEFT JOIN `roles_users` USING (`role_id`) WHERE " . $permission_where, $permission_params);
  foreach (array_merge((array)$entity_permissions, (array)$roles_entity_permissions) as $entity)
  {
    // Set access to ro if it's not in the defined list.
    $access = (in_array($entity['access'], array('ro', 'rw')) ? $entity['access'] : 'ro');

    switch ($entity['entity_type'])
    {
      case "group": // this is a group, so expand its members into an array
        $group = get_group_by_id($entity['entity_id']);
        foreach (get_group_entities($entity['entity_id']) as $group_entity_id)
        {
          $permissions[$group['entity_type']][$group_entity_id] = $access;
        }
      //break; // And also store self group permission in cache
      default:
        $permissions[$entity['entity_type']][$entity['entity_id']] = $access;
        break;
    }
  }
  //r($permissions);

  // Cache platform permissions
  foreach (dbFetchRows("SELECT * FROM `roles_permissions` LEFT JOIN `roles_users` USING (`role_id`) WHERE " . $permission_where, $permission_params) as $perm)
  {
    $permissions['permission'][$perm['permission']] = TRUE;
  }

  // Alerts
  // FIXME - this seems like it would be slow on very large installs
  $alert = array();
  foreach (dbFetchRows('SELECT `alert_table_id`, `device_id`, `entity_id`, `entity_type` FROM `alert_table`') as $alert_table_entry)
  {
    //r($alert_table_entry);
    if (is_entity_permitted($alert_table_entry['entity_id'], $alert_table_entry['entity_type'], $alert_table_entry['device_id'], $permissions))
    {
      $alert[$alert_table_entry['alert_table_id']] = TRUE;
    }
  }
  if (count($alert))
  {
    $permissions['alert'] = $alert;
  }

  return $permissions;

}

/**
 * Return WEB client remote IP address.
 * In mostly cases (also by default) this is just $_SERVER['REMOTE_ADDR'],
 * but if config options ($config['web_remote_addr_header']) set, this can use specified HTTP headers
 *
 * @param boolean Use or not HTTP header specified in $config['web_remote_addr_header']
 * @return string IP address of remote client
 */
function get_remote_addr($use_http_header = FALSE)
{
  global $config;

  if ($use_http_header)
  {
    // Note, this headers is very dangerous for use as auth!
    $addr_headers = [ 'HTTP_CF_CONNECTING_IP', // CF-Connecting-IP (CloudFlare network)
                      'HTTP_X_REAL_IP',        // X-Real-IP
                      'HTTP_X_FORWARDED_FOR',  // X-Forwarded-For
                      'HTTP_CLIENT_IP' ];      // Client-IP

    if (!in_array($config['web_remote_addr_header'], [ 'default', 'detect', 'auto' ]))
    {
      $remote_addr_header = 'HTTP_' . str_replace('-', '_', strtoupper($config['web_remote_addr_header']));
      if (in_array($remote_addr_header, $addr_headers))
      {
        // Use only exact single header
        $addr_headers = [ $remote_addr_header ];
      } else {
        // Unknown config value passed, do not check any header
        $addr_headers = [];
      }
    }

    foreach ($addr_headers as $header)
    {
      if (!empty($_SERVER[$header]) && preg_match(OBS_PATTERN_IP_FULL, $_SERVER[$header], $matches))
      {
        // HTTP header found and it contains valid IP address
        return $matches[1];
      }
    }
  }

  // By default just use server remote address
  return $_SERVER['REMOTE_ADDR'];
}

/**
 * Every time you call session_start(), PHP adds another
 * identical session cookie to the response header. Do this
 * enough times, and your response header becomes big enough
 * to choke the web server.
 *
 * This method clears out the duplicate session cookies. You can
 * call it after each time you've called session_start(), or call it
 * just before you send your headers.
 */
function clear_duplicate_cookies() {
  // If headers have already been sent, there's nothing we can do
  if (headers_sent()) {
    return;
  }

  $cookies = array();
  foreach (headers_list() as $header) {
    // Identify cookie headers
    if (strpos($header, 'Set-Cookie:') === 0) {
      $cookies[] = $header;
    }
  }
  // Removes all cookie headers, including duplicates
  header_remove('Set-Cookie');

  // Restore one copy of each cookie
  foreach(array_unique($cookies) as $cookie) {
    header($cookie, false);
  }
}

/**
 * Store cached device/port/etc permitted IDs into $_SESSION['cache']
 *
 * IDs collected in html/includes/cache-data.inc.php
 * This function used mostly in print_search() or print_form(), see html/includes/print/search.inc.php
 * Cached IDs from $_SESSION used in ajax forms by generate_query_permitted()
 *
 * @return null
 */
function permissions_cache_session()
{
  if (!$_SESSION['authenticated']) { return; }

  if (isset($GLOBALS['permissions_cached_session'])) { return; } // skip if this function already run. FIXME?

  @session_start(); // Re-enable write to session

  // Store device IDs in SESSION var for use to check permissions with ajax queries
  foreach (array('permitted', 'disabled', 'ignored') as $key)
  {
    $_SESSION['cache']['devices'][$key] = $GLOBALS['cache']['devices'][$key];
  }

  // Store port IDs in SESSION var for use to check permissions with ajax queries
  foreach (array('permitted', 'deleted', 'errored', 'ignored', 'poll_disabled', 'device_disabled', 'device_ignored') as $key)
  {
    $_SESSION['cache']['ports'][$key] = $GLOBALS['cache']['ports'][$key];
  }

  $GLOBALS['permissions_cached_session'] = TRUE;

  session_commit(); // Write and close session
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function bill_permitted($bill_id)
{
  global $permissions;

  if ($_SESSION['userlevel'] >= "5")
  {
    $allowed = TRUE;
  } elseif ($permissions['bill'][$bill_id]) {
    $allowed = TRUE;
  } else {
    $allowed = FALSE;
  }

  return $allowed;
}



// TESTME needs unit testing
// MOVEME to includes/functions.inc.php
/**
 * Returns a device_id when given an entity_id and an entity_type. Returns FALSE if the device isn't found.
 *
 * @param $entity_id
 * @param $entity_type
 *
 * @return bool|integer
 */
function get_device_id_by_entity_id($entity_id, $entity_type)
{
  // $entity = get_entity_by_id_cache($entity_type, $entity_id);
  $translate = entity_type_translate_array($entity_type);

  if (isset($translate['device_id_field']) && $translate['device_id_field'] &&
      is_numeric($entity_id) && $entity_type)
  {
    $device_id = dbFetchCell('SELECT `' . $translate['device_id_field'] . '` FROM `' . $translate['table']. '` WHERE `' . $translate['id_field'] . '` = ?', array($entity_id));
  }
  if (is_numeric($device_id))
  {
    return $device_id;
  } else {
    return FALSE;
  }
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function port_permitted($port_id, $device_id = NULL)
{
  return is_entity_permitted($port_id, 'port', $device_id);
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function port_permitted_array(&$ports)
{
  // Strip out the ports the user isn't allowed to see, if they don't have global rights
  if ($_SESSION['userlevel'] < '7')
  {
    foreach ($ports as $key => $port)
    {
      if (!port_permitted($port['port_id'], $port['device_id']))
      {
        unset($ports[$key]);
      }
    }
  }
}

function entity_permitted_array(&$entities, $entity_type)
{

  $entity_type_data = entity_type_translate_array($entity_type);

  // Strip out the entities the user isn't allowed to see, if they don't have global view rights
  if (!isset($_SESSION['user_limited']) || $_SESSION['user_limited'])
  {
    foreach ($entities as $key => $entity)
    {
      if (!is_entity_permitted($entity[$entity_type_data['id_field']], $entity_type, $entity['device_id']))
      {
        unset($entities[$key]);
      }
    }
  }
}


// TESTME needs unit testing
// DOCME needs phpdoc block
function application_permitted($app_id, $device_id = NULL)
{
  global $permissions;

  if (is_numeric($app_id))
  {
    if (!$device_id) { $device_id = get_device_id_by_app_id ($app_id); }
    if ($_SESSION['userlevel'] >= "5") {
      $allowed = TRUE;
    } elseif (device_permitted($device_id)) {
      $allowed = TRUE;
    } elseif ($permissions['application'][$app_id]) {
      $allowed = TRUE;
    } else {
      $allowed = FALSE;
    }
  } else {
    $allowed = FALSE;
  }

  return $allowed;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function device_permitted($device_id)
{
  global $permissions;

  if ($_SESSION['userlevel'] >= "5")
  {
    $allowed = true;
  } elseif (isset($permissions['device'][$device_id])) {
    $allowed = true;
  } else {
    $allowed = false;
  }
  return $allowed;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function print_graph_tag($args)
{
  echo(generate_graph_tag($args));
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_graph_tag($args, $return_array = FALSE)
{

  if (empty($args)) { return ''; } // Quick return if passed empty array

  $style = 'max-width: 100%; width: auto; vertical-align: top;';
  if (isset($args['style']))
  {
    if (is_array($args['style']))
    {
      $style .= implode("; ", $args['style']) . ';';
    } else {
      $style .= $args['style'] . ';';
    }
    unset($args['style']);
  }

  if (isset($args['img_id']))
  {
      $i['img_id'] = $args['img_id'];
  } else {
      $i['img_id'] = generate_random_string(8);
  }

  // Detect allowed screen ratio for current browser
  $ua_info = detect_browser();
  $zoom = $ua_info['screen_ratio'];

  if ($zoom >= 2)
  {
    // Add img srcset for HiDPI screens
    $args_x = $args;
    $args_x['zoom'] = $zoom;
    $srcset = ' srcset="'.generate_graph_url($args_x).' '.$args_x['zoom'].'x"';
    $i['srcset'] = $attribs;
  } else{
    $srcset = '';
  }

  if(isset($args['class']))
  { $attribs .= ' class="'.$args['class'].'"'; unset($args['class']); }


  $img_url = generate_graph_url($args);

  $i['img_url'] = $img_url;
  $i['img_tag'] = '<img id="' . $i['img_id'] . '" src="' . $img_url . '"' . $srcset . $attribs.' style="' . $style . '" alt="" />';
  //$i['img_tag'] = '<img id="' . $i['img_id'] . '" src="' . $img_url . '"' . $srcset . $attribs.' style="' . $style . '" alt="" loading="lazy" />';

  if($return_array === TRUE)
  {
      return $i;
  } else {
      return $i['img_tag'];
  }
}


function generate_graph_url($args, $escape = TRUE)
{

  foreach ($args as $key => $arg)
  {
    if (is_array($arg))
    {
      // Encode arrays
      $arg = var_encode($arg);
    }
    //elseif (strpos($arg, '"') !== FALSE)
    elseif ($escape)
    {
      // Escape args
      $arg = escape_html($arg);
    }
    $urlargs[] = $key . "=" . $arg;
  }

  $url = 'graph.php?' . implode('&amp;', $urlargs);

  if (is_cli())
  {
    if ($GLOBALS['config']['web_url'] == 'http://localhost:80/')
    {
      // override default web_url by http://localhost/
      $url = 'http://' . get_localhost() . '/' . $url;
    } else {
      $url = $GLOBALS['config']['web_url'] . $url;
    }
  }

  return $url;

}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_graph_js_state($args)
{
  // we are going to assume we know roughly what the graph url looks like here.
  // TODO: Add sensible defaults
  $from   = (is_numeric($args['from'])   ? $args['from']   : 0);
  $to     = (is_numeric($args['to'])     ? $args['to']     : 0);
  $width  = (is_numeric($args['width'])  ? $args['width']  : 0);
  $height = (is_numeric($args['height']) ? $args['height'] : 0);
  $legend = str_replace("'", "", $args['legend']);

  $state = <<<STATE
<script type="text/javascript">
document.graphFrom = $from;
document.graphTo = $to;
document.graphWidth = $width;
document.graphHeight = $height;
document.graphLegend = '$legend';
</script>
STATE;

  return $state;
}

/**
 * Generate Percentage Bar
 *
 * This function generates an Observium percentage bar from a supplied array of arguments.
 * It is possible to draw a bar that does not work at all,
 * So care should be taken to make sure values are valid.
 *
 * @param array $args
 * @return string
 */

// TESTME needs unit testing
function percentage_bar($args)
{
  if (strlen($args['bg']))     { $style .= 'background-color:'.$args['bg'].';'; }
  if (strlen($args['border'])) { $style .= 'border-color:'.$args['border'].';'; }
  if (strlen($args['width']))  { $style .= 'width:'.$args['width'].';'; }
  if (strlen($args['text_c'])) { $style_b .= 'color:'.$args['text_c'].';'; }

  $total = '0';
  $output = '<div class="percbar" style="'.$style.'">';
  foreach ($args['bars'] as $bar)
  {
    $output .= '<div class="bar" style="width:'.$bar['percent'].'%; background-color:'.$bar['colour'].';"></div>';
    $total += $bar['percent'];
  }
  $left = '100' - $total;
  if ($left > 0) { $output .= '<div class="bar" style="width:'.$left.'%;"></div>'; }

  if ($left >= 0) { $output .= '<div class="bar-text" style="margin-left: -100px; margin-top: 0px; float: right; text-align: right; '.$style_b.'">'.$args['text'].'</div>'; }

  foreach ($args['bars'] as $bar)
  {
    $output .= '<div class="bar-text" style="width:'.$bar['percent'].'%; max-width:'.$bar['percent'].'%; padding-left: 4px;">'.$bar['text'].'</div>';
  }
#  if ($left > '0') { $output .= '<div class="bar-text" style="margin-left: -100px; margin-top: -16px; float: right; text-align: right; '.$style_b.'">'.$args['text'].'</div>'; }

  $output .= '</div>';

  return $output;
}

// Legacy function
// DO NOT USE THIS. Please replace instances of it with percentage_bar from above.
// TESTME needs unit testing
// DOCME needs phpdoc block
function print_percentage_bar($width, $height, $percent, $left_text, $left_colour, $left_background, $right_text, $right_colour, $right_background)
{

  if ($percent > "100") { $size_percent = "100"; } else { $size_percent = $percent; }

  $percentage_bar['border']  = "#".$left_background;
  $percentage_bar['bg']      = "#".$right_background;
  $percentage_bar['width']   = $width;
  $percentage_bar['text']    = $right_text;
  $percentage_bar['bars'][0] = array('percent' => $size_percent, 'colour' => '#'.$left_background, 'text' => $left_text);

  $output = percentage_bar($percentage_bar);

  return $output;
}

// DOCME needs phpdoc block
function print_optionbar_start($height = 0, $width = 0, $marginbottom = 5)
{
   echo(PHP_EOL . '<div class="box box-solid well-shaded">' . PHP_EOL);
}

// DOCME needs phpdoc block
function print_optionbar_end()
{
  echo(PHP_EOL . '  </div>' . PHP_EOL);
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function overlibprint($text)
{
  return "onmouseover=\"return overlib('" . $text . "');\" onmouseout=\"return nd();\"";
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function device_link_class($device)
{
  if (isset($device['status']) && $device['status'] == '0') { $class = "red"; } else { $class = ""; }
  if (isset($device['ignore']) && $device['ignore'] == '1')
  {
     $class = "grey";
     if (isset($device['status']) && $device['status'] == '1') { $class = "green"; }
  }
  if (isset($device['disabled']) && $device['disabled'] == '1') { $class = "grey"; }

  return $class;
}

/**
 * Return cached locations list
 *
 * If filter used, return locations available only for specified params.
 * Without filter return all available locations (cached)
 *
 * @param array $filter
 * @return array
 */
// TESTME needs unit testing
function get_locations($filter = array())
{
  foreach ($filter as $var => $value)
  {
    switch ($var)
    {
      case 'location_lat':
      case 'location_lon':
      case 'location_country':
      case 'location_state':
      case 'location_county':
      case 'location_city':
        // Check geo params only when GEO enabled globally
        if (!$GLOBALS['config']['geocoding']['enable']) { break; }
      case 'location':
        $where_array[$var] = generate_query_values($value, $var);
        break;
    }
  }

  if (count($where_array))
  {
    // Return only founded locations
    $where = implode('', $where_array) . $GLOBALS['cache']['where']['devices_permitted'];
    $locations = dbFetchColumn("SELECT DISTINCT `location` FROM `devices_locations` WHERE 1 $where;");
  } else {
    $locations = array();
    foreach ($GLOBALS['cache']['device_locations'] as $location => $count)
    {
      $locations[] = $location;
    }
  }
  sort($locations);

  return $locations;
}

// return the filename of the device RANCID config file
// TESTME needs unit testing
// DOCME needs phpdoc block
function get_rancid_filename($hostname, $rdebug = FALSE)
{
  global $config;

  $hostnames = array($hostname);

  if ($rdebug) { echo("Hostname: $hostname<br />"); }

  // Also check non-FQDN hostname.
  list($shortname) = explode('.', $hostname);

  if ($rdebug) { echo("Short hostname: $shortname<br />"); }

  if ($shortname != $hostname)
  {
    $hostnames[] = $shortname;
    if ($rdebug) { echo("Hostname different from short hostname, looking for both<br />"); }
  }

  // Addition of a domain suffix for non-FQDN device names.
  if (isset($config['rancid_suffix']) && $config['rancid_suffix'] !== '')
  {
    $hostnames[] = $hostname . '.' . trim($config['rancid_suffix'], ' .');
    if ($rdebug) { echo("RANCID suffix configured, also looking for " . $hostname . '.' . trim($config['rancid_suffix'], ' .') . "<br />"); }
  }

  foreach ($config['rancid_configs'] as $config_path)
  {
    if ($config_path[strlen($config_path)-1] != '/') { $config_path .= '/'; }
    if ($rdebug) { echo("Looking in configured directory: <b>$config_path</b><br />"); }

    foreach ($hostnames as $host)
    {
      if (is_file($config_path . $host))
      {
        if ($rdebug) { echo("File <b>" . $config_path . $host . "</b> found.<br />"); }
        return $config_path . $host;
      } else {
        if ($rdebug) { echo("File <b>" . $config_path . $host . "</b> not found.<br />"); }
      }
    }
  }

  return FALSE;
}

// return the filename of the device NFSEN rrd file
// TESTME needs unit testing
// DOCME needs phpdoc block
function get_nfsen_filename($hostname)
{
  global $config;

  $nfsen_rrds = (is_array($config['nfsen_rrds']) ? $config['nfsen_rrds'] : array($config['nfsen_rrds']));
  foreach ($nfsen_rrds as $nfsen_rrd)
  {
    if ($nfsen_rrd[strlen($nfsen_rrd)-1] != '/') { $nfsen_rrd .= '/'; }
    $basefilename_underscored = preg_replace('/\./', $config['nfsen_split_char'], $hostname);

    // Remove suffix and prefix from basename
    $nfsen_filename = $basefilename_underscored;
    if (isset($config['nfsen_suffix']) && strlen($config['nfsen_suffix']))
    {
      $nfsen_filename = (strstr($nfsen_filename, $config['nfsen_suffix'], TRUE));
    }
    if (isset($config['nfsen_prefix']) && strlen($config['nfsen_prefix']))
    {
      $nfsen_filename = (strstr($nfsen_filename, $config['nfsen_prefix']));
    }

    $nfsen_rrd_file = $nfsen_rrd . $nfsen_filename . '.rrd';
    if (is_file($nfsen_rrd_file))
    {
      return $nfsen_rrd_file;
    }
  }

  return FALSE;
}

// Note, by default text NOT escaped.
// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_ap_link($args, $text = NULL, $type = NULL, $escape = FALSE)
{
  global $config;

  humanize_port($args);

  if (!$text) { $text = escape_html($args['port_label']); }
  if ($type) { $args['graph_type'] = $type; }
  if (!isset($args['graph_type'])) { $args['graph_type'] = 'port_bits'; }

  if (!isset($args['hostname'])) { $args = array_merge($args, device_by_id_cache($args['device_id'])); }

  $content = "<div class=entity-title>". $args['text'] . " - " . escape_html($args['port_label']) . "</div>";
  if ($args['ifAlias']) { $content .= escape_html($args['ifAlias']) . "<br />"; }
  $content .= "<div style=\'width: 850px\'>";
  $graph_array['type']     = $args['graph_type'];
  $graph_array['legend']   = "yes";
  $graph_array['height']   = "100";
  $graph_array['width']    = "340";
  $graph_array['to']       = $config['time']['now'];
  $graph_array['from']     = $config['time']['day'];
  $graph_array['id']       = $args['accesspoint_id'];
  $content .= generate_graph_tag($graph_array);
  $graph_array['from']     = $config['time']['week'];
  $content .= generate_graph_tag($graph_array);
  $graph_array['from']     = $config['time']['month'];
  $content .= generate_graph_tag($graph_array);
  $graph_array['from']     = $config['time']['year'];
  $content .= generate_graph_tag($graph_array);
  $content .= "</div>";

  $url = generate_ap_url($args);
  if (port_permitted($args['interface_id'], $args['device_id']))
  {
    return overlib_link($url, $text, $content, $class, $escape);
  } else {
    return $text;
  }
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_ap_url($ap, $vars=array())
{
  return generate_url(array('page' => 'device', 'device' => $ap['device_id'], 'tab' => 'accesspoint', 'ap' => $ap['accesspoint_id']), $vars);
}

/**
 * Generate SQL WHERE string with check permissions and ignores for device_id, port_id and other
 *
 * Note, this function uses comparison operator IN. Max number of values in the IN list
 * is limited by the 'max_allowed_packet' option (default: 1048576)
 *
 * Usage examples:
 *  generate_query_permitted()
 *   ' AND `device_id` IN (1,4,8,33) AND `device_id` NOT IN (66) AND (`device_id` != '' AND `device_id` IS NOT NULL) '
 *  generate_query_permitted(array('device'), array('device_table' => 'D'))
 *   ' AND `D`.`device_id` IN (1,4,8,33) AND `D`.`device_id` NOT IN (66) AND (`D`.`device_id` != '' AND `D`.`device_id` IS NOT NULL) '
 *  generate_query_permitted(array('device', 'port'), array('port_table' => 'I')) ==
 *   ' AND `device_id` IN (1,4,8,33) AND `device_id` NOT IN (66) AND (`device_id` != '' AND `device_id` IS NOT NULL)
 *     AND `I`.`port_id` IN (1,4,8,33) AND `I`.`port_id` NOT IN (66) AND (`I`.`port_id` != '' AND `I`.`port_id` IS NOT NULL) '
 *  generate_query_permitted(array('device', 'port'), array('port_table' => 'I', 'hide_ignored' => TRUE))
 *    This additionaly exclude all ignored devices and ports
 *
 * @uses html/includes/cache-data.inc.php
 * @global integer $_SESSION['userlevel']
 * @global boolean $GLOBALS['config']['web_show_disabled']
 * @global array $GLOBALS['permissions']
 * @global array $GLOBALS['cache']['devices']
 * @global array $GLOBALS['cache']['ports']
 * @global string $GLOBALS['vars']['page']
 * @param array|string $type_array Array with permission types, currently allowed 'devices', 'ports'
 * @param array $options Options for each permission type: device_table, port_table, hide_ignored, hide_disabled
 * @return string
 */
// TESTME needs unit testing
function generate_query_permitted($type_array = array('device'), $options = array())
{
  if (!is_array($type_array)) { $type_array = array($type_array); }
  $user_limited = ($_SESSION['userlevel'] < 5 ? TRUE : FALSE);
  $page = $GLOBALS['vars']['page'];

  // If device IDs stored in SESSION use it (used in ajax)
  //if (!isset($GLOBALS['cache']['devices']) && isset($_SESSION['cache']['devices']))
  //{
  //  $GLOBALS['cache']['devices'] = $_SESSION['cache']['devices'];
  //}

  if (!isset($GLOBALS['permissions']))
  {
    // Note, this function must used after load permissions list!
    print_error("Function ".__FUNCTION__."() on page '$page' called before include cache-data.inc.php or something wrong with caching permissions. Please report this to developers!");
  }
  // Use option hide_disabled if passed or use config
  $options['hide_disabled'] = (isset($options['hide_disabled']) ? (bool)$options['hide_disabled'] : !$GLOBALS['config']['web_show_disabled']);

  //$query_permitted = '';
  $query_part = [];

  foreach ($type_array as $type)
  {
    switch ($type)
    {
      // Devices permission query
      case 'device':
      case 'devices':
        $column = '`device_id`';
        $query_permitted = array();
        if (isset($options['device_table'])) { $column = '`'.$options['device_table'].'`.'.$column; }

        // Show only permitted devices
        if ($user_limited)
        {
          if (count($GLOBALS['permissions']['device']))
          {
            $query_permitted[] = " $column IN (".
                                 implode(',', array_keys($GLOBALS['permissions']['device'])).
                                 ')';

          } else {
            // Exclude all entries, because there is no permitted devices
            $query_permitted[] = ' 0';
          }
        }

        // Also don't show ignored and disabled devices (except on 'device' and 'devices' pages)
        $devices_excluded = array();
        if (strpos($page, 'device') !== 0)
        {
          if ($options['hide_ignored'] && count($GLOBALS['cache']['devices']['ignored']))
          {
            $devices_excluded = array_merge($devices_excluded, $GLOBALS['cache']['devices']['ignored']);
          }
          if ($options['hide_disabled'] && count($GLOBALS['cache']['devices']['disabled']))
          {
            $devices_excluded = array_merge($devices_excluded, $GLOBALS['cache']['devices']['disabled']);
          }
        }
        if (count($devices_excluded))
        {
          // Set query with excluded devices
          $query_permitted[] = " $column NOT IN (".
                               implode(',', array_unique($devices_excluded)).
                               ')';
        }

        // At the end excluded entries with empty/null device_id (wrong entries)
        //$query_permitted[] = " ($column != '' AND $column IS NOT NULL)";
        $query_permitted[] = " $column IS NOT NULL"; // Note: SELECT '' = 0; is TRUE
        $query_part[] = implode(" AND ", $query_permitted);
        unset($query_permitted);
        break;
      // Ports permission query
      case 'port':
      case 'ports':
        $column = '`port_id`';
        if (isset($options['port_table'])) { $column = '`'.$options['port_table'].'`.'.$column; }

        // If port IDs stored in SESSION use it (used in ajax)
        //if (!isset($GLOBALS['cache']['ports']) && isset($_SESSION['cache']['ports']))
        //{
        //  $GLOBALS['cache']['ports'] = $_SESSION['cache']['ports'];
        //}

        // Show only permitted ports
        if ($user_limited)
        {
          if (count($GLOBALS['permissions']['port']))
          {
            $query_permitted[] = " $column IN (" .
                                 implode(',', array_keys($GLOBALS['permissions']['port'])) .
                                 ')';
          } else {
            // Exclude all entries, because there is no permitted ports
            $query_permitted[] = '0';
          }
        }

        $ports_excluded = array();
        // Don't show ports with disabled polling.
        if (count($GLOBALS['cache']['ports']['poll_disabled']))
        {
          $ports_excluded = array_merge($ports_excluded, $GLOBALS['cache']['ports']['poll_disabled']);
          //foreach ($GLOBALS['cache']['ports']['poll_disabled'] as $entry)
          //{
          //  $ports_excluded[] = $entry;
          //}
          //$ports_excluded = array_unique($ports_excluded);
        }
        // Don't show deleted ports (except on 'deleted-ports' page)
        if ($page != 'deleted-ports' && count($GLOBALS['cache']['ports']['deleted']))
        {
          $ports_excluded = array_merge($ports_excluded, $GLOBALS['cache']['ports']['deleted']);
          //foreach ($GLOBALS['cache']['ports']['deleted'] as $entry)
          //{
          //  $ports_excluded[] = $entry;
          //}
          //$ports_excluded = array_unique($ports_excluded);
        }
        if ($page != 'device' && !in_array('device', $type_array))
        {
          // Don't show ports for disabled devices (except on 'device' page or if 'device' permissions already queried)
          if ($options['hide_disabled'] && !$user_limited && count($GLOBALS['cache']['ports']['device_disabled']))
          {
            $ports_excluded = array_merge($ports_excluded, $GLOBALS['cache']['ports']['device_disabled']);
            //foreach ($GLOBALS['cache']['ports']['device_disabled'] as $entry)
            //{
            //  $ports_excluded[] = $entry;
            //}
            //$ports_excluded = array_unique($ports_excluded);
          }
          // Don't show ports for ignored devices (except on 'device' page)
          if ($options['hide_ignored'] && count($GLOBALS['cache']['ports']['device_ignored']))
          {
            $ports_excluded = array_merge($ports_excluded, $GLOBALS['cache']['ports']['device_ignored']);
            //foreach ($GLOBALS['cache']['ports']['device_ignored'] as $entry)
            //{
            //  $ports_excluded[] = $entry;
            //}
            //$ports_excluded = array_unique($ports_excluded);
          }
        }
        // Don't show ignored ports (only on some pages!)
        if (($page == 'overview' || $options['hide_ignored']) && count($GLOBALS['cache']['ports']['ignored']))
        {
          $ports_excluded = array_merge($ports_excluded, $GLOBALS['cache']['ports']['ignored']);
          //foreach ($GLOBALS['cache']['ports']['ignored'] as $entry)
          //{
          //  $ports_excluded[] = $entry;
          //}
          //$ports_excluded = array_unique($ports_excluded);
        }
        unset($entry);
        if (count($ports_excluded))
        {
          // Set query with excluded ports
          $query_permitted[] = $column . " NOT IN (".
                             implode(',', array_unique($ports_excluded)).
                             ')';

        }

        // At the end excluded entries with empty/null port_id (wrong entries)
        //$query_permitted[] = "($column != '' AND $column IS NOT NULL)";
        $query_permitted[] = "$column IS NOT NULL";

        $query_part[] = implode(" AND ", $query_permitted);
        unset($query_permitted);

        break;
      case 'sensor':
      case 'sensors':
        // For sensors
        // FIXME -- this is easily generifyable, just use translate_table_array()

        $column = '`sensor_id`';

        if (isset($options['sensor_table'])) { $column = '`'.$options['sensor_table'].'`.'.$column; }

        // If IDs stored in SESSION use it (used in ajax)
        //if (!isset($GLOBALS['cache']['sensors']) && isset($_SESSION['cache']['sensors']))
        //{
        //  $GLOBALS['cache']['sensors'] = $_SESSION['cache']['sensors'];
        //}

        // Show only permitted entities
        if ($user_limited)
        {
          if (count($GLOBALS['permissions']['sensor']))
          {
            $query_permitted .= " $column IN (";
            $query_permitted .= implode(',', array_keys($GLOBALS['permissions']['sensor']));
            $query_permitted .= ')';
          } else {
            // Exclude all entries, because there are no permitted entities
            $query_permitted .= '0';
          }
          $query_part[] = $query_permitted;
          unset($query_permitted);
        }

        break;

      case 'alert':
      case 'alerts':
        // For generic alert

        $column = '`alert_table_id`';

        // Show only permitted entities
        if ($user_limited)
        {
          if (count($GLOBALS['permissions']['alert']))
          {
            $query_permitted .= " $column IN (";
            $query_permitted .= implode(',', array_keys($GLOBALS['permissions']['alert']));
            $query_permitted .= ')';
          } else {
            // Exclude all entries, because there are no permitted entities
            $query_permitted .= '0';
          }
          $query_part[] = $query_permitted;
          unset($query_permitted);
        }

        break;
      case 'bill':
      case 'bills':
        // For bills
        break;
    }
  }
  if (count($query_part))
  {
    //r($query_part);
    if ($user_limited)
    {
      // Limited user must use OR for include multiple entities
      $query_permitted = " AND ((".implode(") OR (", $query_part)."))";
    } else {
      // Unlimited used must use AND for exclude multiple hidden entities
      $query_permitted = " AND ((".implode(") AND (", $query_part)."))";
    }
  }

  $query_permitted .= ' ';

  //r($query_permitted);

  return $query_permitted;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function dashboard_exists($dash_id)
{
  return dbExist('dashboards', '`dash_id` = ?', [ $dash_id ]);
  //return count(dbFetchRow("SELECT * FROM `dashboards` WHERE `dash_id` = ?", array($dash_id)));
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function get_user_prefs($user_id)
{
  $prefs = array();
  foreach (dbFetchRows("SELECT * FROM `users_prefs` WHERE `user_id` = ?", array($user_id)) as $entry)
  {
    $prefs[$entry['pref']] = $entry;
  }
  return $prefs;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function get_user_pref($user_id, $pref)
{
  if ($entry = dbFetchRow("SELECT `value` FROM `users_prefs` WHERE `user_id` = ? AND `pref` = ?", array($user_id, $pref)))
  {
    return $entry['value'];
  }
  else
  {
    return NULL;
  }
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function set_user_pref($user_id, $pref, $value)
{
  //if (dbFetchCell("SELECT COUNT(*) FROM `users_prefs` WHERE `user_id` = ? AND `pref` = ?", array($user_id, $pref)))
  if (dbExist('users_prefs', '`user_id` = ? AND `pref` = ?', array($user_id, $pref)))
  {
    $id = dbUpdate(array('value' => $value), 'users_prefs', '`user_id` = ? AND `pref` = ?', array($user_id, $pref));
  } else {
    $id = dbInsert(array('user_id' => $user_id, 'pref' => $pref, 'value' => $value), 'users_prefs');
  }
  return $id;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function del_user_pref($user_id, $pref)
{
  return dbDelete('users_prefs', "`user_id` = ? AND `pref` = ?", array($user_id, $pref));
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function get_smokeping_files($rdebug = 0)
{
  global $config;

  $smokeping_files = array();

  if ($rdebug) { echo('- Recursing through ' . $config['smokeping']['dir'] . '<br />'); }

  if (isset($config['smokeping']['master_hostname']))
  {
    $master_hostname = $config['smokeping']['master_hostname'];
  } else {
    $master_hostname = $config['own_hostname'];
  }

  if (is_dir($config['smokeping']['dir']))
  {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($config['smokeping']['dir'])) as $file)
    {
      if (basename($file) != "." && basename($file) != ".." && strstr($file, ".rrd"))
      {
        if ($rdebug) { echo('- Found file ending in ".rrd": ' . basename($file) . '<br />'); }

        if (strstr($file, "~"))
        {
          list($target,$slave) = explode("~", basename($file,".rrd"));
          if ($rdebug) { echo('- Determined to be a slave file for target <b>' . $target . '</b><br />'); }
          $target = str_replace($config['smokeping']['split_char'], ".", $target);
          if ($config['smokeping']['suffix']) { $target = $target.$config['smokeping']['suffix']; if ($rdebug) { echo('- Suffix is configured, target is now <b>' . $target . '</b><br />'); } }
          $smokeping_files['incoming'][$target][$slave] = $file;
          $smokeping_files['outgoing'][$slave][$target] = $file;
        } else {
          $target = basename($file,".rrd");
          if ($rdebug) { echo('- Determined to be a local file, for target <b>' . $target . '</b><br />'); }
          $target = str_replace($config['smokeping']['split_char'], ".", $target);
          if ($rdebug) { echo('- After replacing configured split_char ' . $config['smokeping']['split_char'] . ' by . target is <b>' . $target . '</b><br />'); }
          if ($config['smokeping']['suffix']) { $target = $target.$config['smokeping']['suffix']; if ($rdebug) { echo('- Suffix is configured, target is now <b>' . $target . '</b><br />'); } }
          $smokeping_files['incoming'][$target][$master_hostname] = $file;
          $smokeping_files['outgoing'][$master_hostname][$target] = $file;
        }
      }
    }
  } else {
    if ($rdebug) { echo("- Smokeping RRD directory not found: " . $config['smokeping']['dir']); }
  }

  return $smokeping_files;
}

/**
 * Darkens or lightens a colour
 * Found via http://codepad.org/MTGLWVd0
 *
 * First argument is the colour in hex, second argument is how dark it should be 1=same, 2=50%
 *
 * @return string
 * @param string $rgb
 * @param int $darker
 */
function darken_color($rgb, $darker=2)
{
  if (strpos($rgb, '#') !== FALSE)
  {
    $hash = '#';
    $rgb  = str_replace('#', '', $rgb);
  } else {
    $hash = '';
  }
  $len  = strlen($rgb);
  if ($len == 6) {} // Passed RGB
  else if ($len == 8)
  {
    // Passed RGBA, remove alpha channel
    $rgb = substr($rgb, 0, 6);
  } else {
    $rgb = FALSE;
  }

  if ($rgb === FALSE) { return $hash.'000000'; }

  $darker = ($darker > 1) ? $darker : 1;

  list($R16, $G16, $B16) = str_split($rgb, 2);

  $R = sprintf("%02X", floor(hexdec($R16) / $darker));
  $G = sprintf("%02X", floor(hexdec($G16) / $darker));
  $B = sprintf("%02X", floor(hexdec($B16) / $darker));

  return $hash.$R.$G.$B;
}

// Originally from http://stackoverflow.com/questions/6054033/pretty-printing-json-with-php/21162086#21162086
// FIXME : This is only required for PHP < 5.4, remove this when our requirements are >= 5.4
if (!defined('JSON_UNESCAPED_SLASHES')) { define('JSON_UNESCAPED_SLASHES', 64); }
if (!defined('JSON_PRETTY_PRINT'))      { define('JSON_PRETTY_PRINT', 128); }
if (!defined('JSON_UNESCAPED_UNICODE')) { define('JSON_UNESCAPED_UNICODE', 256); }

function json_output($status, $message)
{
  header("Content-type: application/json; charset=utf-8");
  echo json_encode(array("status" => $status, "message" => $message));

  exit();
}

function _json_encode($data, $options = 448)
{
  if (version_compare(PHP_VERSION, '5.4', '>='))
  {
    return json_encode($data, $options);
  } else {
    return _json_format(json_encode($data), $options);
  }
}

function _json_format($json, $options = 448)
{
  $prettyPrint = (bool) ($options & JSON_PRETTY_PRINT);
  $unescapeUnicode = (bool) ($options & JSON_UNESCAPED_UNICODE);
  $unescapeSlashes = (bool) ($options & JSON_UNESCAPED_SLASHES);
  if (!$prettyPrint && !$unescapeUnicode && !$unescapeSlashes)
  {
    return $json;
  }
  $result = '';
  $pos = 0;
  $strLen = strlen($json);
  $indentStr = ' ';
  $newLine = "\n";
  $outOfQuotes = true;
  $buffer = '';
  $noescape = true;
  for ($i = 0; $i < $strLen; $i++)
  {
    // Grab the next character in the string
    $char = substr($json, $i, 1);
    // Are we inside a quoted string?
    if ('"' === $char && $noescape)
    {
      $outOfQuotes = !$outOfQuotes;
    }
    if (!$outOfQuotes)
    {
      $buffer .= $char;
      $noescape = '\\' === $char ? !$noescape : true;
      continue;
    }
    elseif ('' !== $buffer)
    {
      if ($unescapeSlashes)
      {
        $buffer = str_replace('\\/', '/', $buffer);
      }
      if ($unescapeUnicode && function_exists('mb_convert_encoding'))
      {
        // http://stackoverflow.com/questions/2934563/how-to-decode-unicode-escape-sequences-like-u00ed-to-proper-utf-8-encoded-cha
        $buffer = preg_replace_callback('/\\\\u([0-9a-f]{4})/i',
          function ($match)
          {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
          }, $buffer);
      }
      $result .= $buffer . $char;
      $buffer = '';
      continue;
    }
    elseif(false !== strpos(" \t\r\n", $char))
    {
      continue;
    }
    if (':' === $char)
    {
      // Add a space after the : character
      $char .= ' ';
    }
    elseif (('}' === $char || ']' === $char))
    {
      $pos--;
      $prevChar = substr($json, $i - 1, 1);
      if ('{' !== $prevChar && '[' !== $prevChar)
      {
        // If this character is the end of an element,
        // output a new line and indent the next line
        $result .= $newLine;
        for ($j = 0; $j < $pos; $j++)
        {
          $result .= $indentStr;
        }
      }
      else
      {
        // Collapse empty {} and []
        $result = rtrim($result) . "\n\n" . $indentStr;
      }
    }
    $result .= $char;
    // If the last character was the beginning of an element,
    // output a new line and indent the next line
    if (',' === $char || '{' === $char || '[' === $char)
    {
      $result .= $newLine;
      if ('{' === $char || '[' === $char)
      {
        $pos++;
      }
      for ($j = 0; $j < $pos; $j++)
      {
        $result .= $indentStr;
      }
    }
  }
  // If buffer not empty after formating we have an unclosed quote
  if (strlen($buffer) > 0)
  {
    //json is incorrectly formatted
    $result = false;
  }
  return $result;
}

/**
 * Register an HTML resource
 *
 * Registers resource for use later (will be re-inserted via output buffer handler)
 * CSS and JS files default to the css/ and js/ directories respectively.
 * Scripts are inserted literally as passed in $name.
 *
 * @param string $type Type of resource (css/js/script)
 * @param string $content Filename or script content or array (for meta)
 */
// TESTME needs unit testing
function register_html_resource($type, $content)
{
  // If no path specified, default to subdirectory of resource type (for CSS and JS only)
  $type = strtolower($type);
  if (in_array($type, array('css', 'js')) && strpos($content, '/') === FALSE)
  {
    $content = $type . '/' . $content;
  }

  // Insert into global variable, used in html callback function
  $GLOBALS['cache_html']['resources'][$type][] = $content;
}

/**
 * Register an HTML title section
 *
 * Registers title section for use in the html <title> tag.
 * Calls can be stacked, and will be concatenated later by the HTML callback function.
 *
 * @param string $title Section title content
 */
// TESTME needs unit testing
function register_html_title($title)
{
  $GLOBALS['cache_html']['title'][] = $title;
}


/**
 * Register an HTML panel section
 *
 * Registers left panel section.
 * Calls can be stacked, and will be concatenated later by the HTML callback function.
 *
 * @param string $html Section panel content
 */
// TESTME needs unit testing
function register_html_panel($html = '')
{
  $GLOBALS['cache_html']['page_panel'] = $html;
}

/**
 * Redirect to specified URL
 *
 * @param string $url Redirecting URL
 */
function redirect_to_url($url)
{
  if (!strlen($url) || $url == '#') { return; } // Empty url, do not redirect

  $parse = parse_url($url);
  if (!isset($parse['scheme']) && !str_starts($url, '/'))
  {
    // When this is not full url or not started with /
    $url = '/' . $url;
  }

  if (headers_sent())
  {
    // HTML headers already sent, use JS than
    register_html_resource('script', "location.href='$url'");
  } else {
    // Just use headers
    header('Location: '.$url);
  }
}

function generate_colour_gradient($start_colour, $end_colour, $steps) {

  if($steps < 4) { $steps = 4; }

  $FromRGB['r'] = hexdec(substr($start_colour, 0, 2));
  $FromRGB['g'] = hexdec(substr($start_colour, 2, 2));
  $FromRGB['b'] = hexdec(substr($start_colour, 4, 2));

  $ToRGB['r'] = hexdec(substr($end_colour, 0, 2));
  $ToRGB['g'] = hexdec(substr($end_colour, 2, 2));
  $ToRGB['b'] = hexdec(substr($end_colour, 4, 2));

  $StepRGB['r'] = ($FromRGB['r'] - $ToRGB['r']) / ($steps - 1);
  $StepRGB['g'] = ($FromRGB['g'] - $ToRGB['g']) / ($steps - 1);
  $StepRGB['b'] = ($FromRGB['b'] - $ToRGB['b']) / ($steps - 1);

  $GradientColors = array();

  for($i = 0; $i < $steps; $i++) {
    $RGB['r'] = floor($FromRGB['r'] - ($StepRGB['r'] * $i));
    $RGB['g'] = floor($FromRGB['g'] - ($StepRGB['g'] * $i));
    $RGB['b'] = floor($FromRGB['b'] - ($StepRGB['b'] * $i));

    $HexRGB['r'] = sprintf('%02x', ($RGB['r']));
    $HexRGB['g'] = sprintf('%02x', ($RGB['g']));
    $HexRGB['b'] = sprintf('%02x', ($RGB['b']));

    $GradientColors[] = implode(NULL, $HexRGB);
  }
  $GradientColors = array_filter($GradientColors, "c_len");
  return $GradientColors;
}

function c_len($val){
  return (strlen($val) == 6 ? true : false );
}

function adjust_colour_brightness($hex, $steps) {
    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    $steps = max(-255, min(255, $steps));

    // Normalize into a six character long hex string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }

    // Split into three parts: R, G and B
    $color_parts = str_split($hex, 2);

    $return ='';
    foreach ($color_parts as $color) {
        $color   = hexdec($color); // Convert to decimal
        $color   = max(0,min(255,$color + $steps)); // Adjust color
        $return  .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
    }

    return $return;
}

/**
 * Highlight (or replace with specific strings) part of string.
 * Optionally can search full words of search strings.
 *
 * @param string $text    Text where need highlight search string
 * @param array  $search  Search array. Can be string, simple array or array with 'search', 'replace' pairs
 * @param string $replace Default is just
 * @param bool   $words   If True search full words
 *
 * @return string
 */
function html_highlight($text, $search = [], $replace = '', $words = FALSE)
{
  if (empty($replace))
  {
    // Default is highlight as danger class
    $replace = $words ? '<em class="text-danger">$1</em>' : '<em class="text-danger">$0</em>';
  }

  $entries = [];
  foreach ((array)$search as $entry)
  {
    if (isset($entry['search']))
    {
      if (!isset($entry['replace']))
      {
        $entry['replace'] = $replace;
      }
      $text = html_highlight($text, $entry['search'], $entry['replace'], $words);
      continue;
    }

    if (strlen($entry) == 0) { continue; }
    $entries[] = preg_quote($entry, '%');
  }
  if (!count($entries)) { return $text; }

  $search_pattern = '(' . implode('|', $entries) . ')';
  if ($words)
  {
    // Search full words
    $search_pattern = str_replace('(?:', '(', OBS_PATTERN_START) .
                      $search_pattern .
                      str_replace('(?:', '(', OBS_PATTERN_END);
    // append start and end in pattern search
    $replace = '$1' . $replace . '$3';
  } else {
    // Search any search string
    $search_pattern = '%' . $search_pattern . '%i';
  }

  return preg_replace($search_pattern, $replace, $text);
}

// EOF
