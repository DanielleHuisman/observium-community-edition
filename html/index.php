<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

include("../includes/sql-config.inc.php");

include_once($config['html_dir'] . "/includes/functions.inc.php");

// Preflight checks

if (!is_dir($config['rrd_dir'])) {
  print_error("RRD Directory is missing ({$config['rrd_dir']}).  Graphing may fail.");
}

if (!is_dir($config['log_dir'])) {
  print_error("Log Directory is missing ({$config['log_dir']}).  Logging may fail.");
}

if (!is_dir($config['temp_dir'])) {
  print_error("Temp Directory is missing ({$config['temp_dir']}).  Graphing may fail.");
} elseif (!is_writable($config['temp_dir'])) {
  print_error("Temp Directory is not writable ({$config['tmp_dir']}).  Graphing may fail.");
}

// verify if PHP supports session, die if it does not
check_extension_exists('session', '', TRUE);

ob_start('html_callback');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?php echo(escape_html($config['base_url'])); ?>"/>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>

    <!-- ##META_CACHE## -->
    <!-- ##CSS_CACHE## -->
    <!-- ##STYLE_CACHE## -->
    <!-- ##JS_CACHE## -->
  <?php /* html5.js below from https://github.com/aFarkas/html5shiv */ ?>
    <!--[if lt IE 9]>
    <script src="js/html5shiv.min.js"></script><![endif]-->
  <?php

  $runtime_start = utime();

  ini_set('allow_url_fopen', 0);
  ini_set('display_errors', 0);

  $_SERVER['PATH_INFO'] = (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : $_SERVER['ORIG_PATH_INFO']);

  // Clean global $vars variable, it populated only after correct authenticate
  unset($vars);

  include($config['html_dir'] . "/includes/authenticate.inc.php");

  $theme_default = isset($_SESSION['theme_default']) ? $_SESSION['theme_default'] : $config['web_theme_default'];
  if (isset($_SESSION['theme'], $_COOKIE['screen_scheme']) &&
      $theme_default === 'system' &&
      $_SESSION['theme'] !== $_COOKIE['screen_scheme'])
  {
    // Reset session theme if system theme changed
    session_unset_var('theme');
  }
  if (!isset($_SESSION['theme']) || !isset($config['themes'][$_SESSION['theme']]))
  {
    // Set default theme
    if ($theme_default === 'system' && isset($_COOKIE['screen_scheme']))
    {
      // Cookie screen_scheme sets by js, only light or dark
      $theme = $_COOKIE['screen_scheme'];
    } else {
      $theme = $theme_default;
    }
    //$_SESSION['theme'] = isset($config['themes'][$theme]) ? $theme : 'light';
    session_set_var('theme', isset($config['themes'][$theme]) ? $theme : 'light');
  }
  $_SESSION['mode'] = $config['themes'][$_SESSION['theme']]['type'];

  if (isset($config['themes'][$_SESSION['theme']]['css']))
  {
    register_html_resource('css', $config['themes'][$_SESSION['theme']]['css']);
  } else {
    // Fallback in community edition
    register_html_resource('css', 'observium.css');
  }

  //register_html_resource('css', 'jquery.qtip.min.css');
  register_html_resource('css', 'sprite.css');

  register_html_resource('js', 'jquery.min.js');
  register_html_resource('js', 'jquery-migrate.min.js'); // required for unsupported js libs (ie qtip2)
  // register_html_resource('js', 'jquery-ui.min.js'); // FIXME. We don't use JQueryUI or am I wrong? (mike)
  register_html_resource('js', 'bootstrap.min.js');


  ?>
    <title>##TITLE##</title>
    <link rel="shortcut icon" href="<?php echo(escape_html($config['favicon'])); ?>"/>
  <?php

  if ($_SESSION['authenticated'])
  {
    // Register additional html resources after auth
    register_html_resource('css', 'flags.css');
    register_html_resource('css', 'c3.min.css');

    register_html_resource('js', 'observium.js');
    register_html_resource('js', 'd3.min.js');
    register_html_resource('js', 'c3.min.js');

    $vars = get_vars(); // Parse vars from GET/POST/URI

    if ($vars['export'] === 'yes') // This is for display XML on export pages
    {
      // Code prettify (but it's still horrible)
      register_html_resource('js', 'google-code-prettify.js');
      register_html_resource('css', 'google-code-prettify.css');
    }

    $page_refresh = print_refresh($vars); // $page_refresh used in navbar for refresh menu

    $feeds = array('eventlog');
    //if ($config['enable_syslog']) { $feeds[] = 'syslog'; }
    foreach ($feeds as $feed)
    {
      $feed_href = generate_feed_url(array('feed' => $feed));
      if ($feed_href)
      {
        echo($feed_href . PHP_EOL);
      }
    }
  }

  if ($vars['widescreen'] === "yes") {
    session_set_var('widescreen', 1);
    unset($vars['widescreen']);
  } elseif ($vars['widescreen'] === "no") {
    session_unset_var('widescreen');
    unset($vars['widescreen']);
  }

  if ($vars['big_graphs'] === "yes") {
    session_set_var('big_graphs', 1);
    unset($vars['big_graphs']);
  } elseif ($vars['big_graphs'] === "no") {
    session_unset_var('big_graphs');
    unset($vars['big_graphs']);
  }

  // FIXME this block still needed?
  if ($_SESSION['widescreen'])
  {
    // Widescreen style additions
    register_html_resource('css', 'styles-wide.css');
  }

  ?>

</head>

<?php
// Determine type of web browser.
$browser_type = detect_browser_type();
if ($browser_type === 'mobile' || $browser_type === 'tablet') {
  session_set_var('touch', 'yes');
}
if ($vars['touch'] === "yes") {
  session_set_var('touch', 'yes');
} elseif ($vars['touch'] === "no") {
  unset($vars['touch']);
  session_unset_var('touch');
}

if ($_SESSION['authenticated']) {
  $allow_mobile = (in_array(detect_browser_type(), array('mobile', 'tablet')) ? $config['web_mouseover_mobile'] : TRUE);
  if ($config['web_mouseover'] && $allow_mobile)
  {
    register_html_resource('js', 'jquery.qtip.min.js');
    //register_html_resource('css', 'jquery.qtip.min.css');

    register_html_resource('script', 'jQuery(function ($) { entity_popups(); popups_from_data(); });');
    // All of this do same
    //register_html_resource('script', 'jQuery(document).ready(function () { entity_popups(); popups_from_data(); });');
    //register_html_resource('script', '$(window).on("load", function(){ entity_popups(); popups_from_data(); });');
    //register_html_resource('script', '$("a").on("mouseenter", function(){ entity_popups(); popups_from_data(); });');

  }
  // Do various queries which we use in multiple places
  include($config['html_dir'] . "/includes/cache-data.inc.php");
  // Add some cached notifications
  include($config['html_dir'] . "/includes/notifications.inc.php");

  // Include navbar
  if (!get_var_true($vars['bare'])) {
    include($config['html_dir'] . "/includes/navbar.inc.php");
  }

}
?>

<div id="main_container" class="container" <?php echo(get_var_true($vars['bare']) ? 'style="padding-top: 10px;"' : ''); ?> >

  <?php

  if ($_SESSION['authenticated'])
  {

    // Execute form actions
    if (isset($vars['action']) && is_alpha($vars['action']) &&
        is_file($config['html_dir'] . "/includes/actions/" . $vars['action'] . ".inc.php"))
    {
      include($config['html_dir'] . "/includes/actions/" . $vars['action'] . ".inc.php");
    }

    // Output UI Alerts
    echo '##UI_ALERTS##';

    // Authenticated. Print a page.
    if (isset($vars['page']) && is_alpha($vars['page']) &&
        is_file($config['html_dir'] . "/pages/" . $vars['page'] . ".inc.php"))
    {
      $page_file = $config['html_dir'] . "/pages/" . $vars['page'] . ".inc.php";
    } else {
      $vars['page'] = 'dashboard';
      $page_file    = $config['html_dir'] . "/pages/" . $vars['page'] . ".inc.php";
    }


    //$test = dbFetchRows("SELECT * FROM `ports`");

    //include($page_file);

        if ($config['pages'][$vars['page']]['custom_panel'])
        {
          include($page_file);
        }
        else
        {

          echo '<div class="row">';

          if ($config['pages'][$vars['page']]['no_panel'])
          {
            echo '<div class="col-lg-12">';
          }
          else
          {
            echo '
      <div class="col-xl-4 visible-xl">
        <div id="myAffix" data-spy="affix" data-offset-top="60">
        ##PAGE_PANEL##
        </div>
      </div>
    <div class="col-xl-8 col-lg-12">';
          }

          include($page_file);
          echo '</div>';
        }


    // Register default panel if custom not set
    if (!isset($GLOBALS['cache_html']['page_panel']))
    {
      if (is_alpha($vars['page']) &&
          is_file($config['html_dir'] . "/includes/panels/" . $vars['page'] . ".inc.php")) {
        $panel_file = $config['html_dir'] . "/includes/panels/" . $vars['page'] . ".inc.php";
      } else {
        $panel_file = $config['html_dir'] . "/includes/panels/default.inc.php";
      }
      ob_start();
      include($panel_file);
      $panel_html = ob_get_contents();
      ob_end_clean();

      register_html_panel($panel_html);
    }

  } elseif ($config['auth_mechanism'] === 'cas' || $config['auth_mechanism'] === 'remote') {
    // Not Authenticated. CAS logon.
    echo('Not authorized.');
    exit;
  } else {
    // Not Authenticated. Print login.
    include($config['html_dir'] . "/pages/logon.inc.php");
    exit;
  }

  $gentime  = utime() - $runtime_start;
  $fullsize = memory_get_usage();
  unset($cache);
  $cachesize = $fullsize - memory_get_usage();

  if ($cachesize < 0)
  {
    $cachesize = 0;
  } // Silly PHP!

  ?>

</div>

<?php

if (!get_var_true($vars['bare'])) {
  ?>

    <footer class="navbar navbar-fixed-bottom">
        <div class="navbar-inner">
            <div class="container">
                <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                    <span class="oicon-bar"></span>
                    <span class="oicon-bar"></span>
                    <span class="oicon-bar"></span>
                </a>
                <div class="nav-collapse">
                    <ul class="nav">
                        <li class="dropdown"><?php

                          if (isset($config['web']['logo'])) {
                            echo '    <a class="brand brand-observium" href="/" class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown">&nbsp;</a> ' .
                                 OBSERVIUM_VERSION_LONG;
                          } else {
                            echo '<a href="' . OBSERVIUM_URL . '" class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown">';
                            echo OBSERVIUM_PRODUCT . ' ' . OBSERVIUM_VERSION_LONG;
                            echo '</a>';
                          }
                          ?>
                            <div class="dropdown-menu" style="padding: 10px;">
                                <div style="max-width: 145px;"><img src="images/login-hamster-large.png" alt=""/></div>

                            </div>
                        </li>
                    </ul>

                    <ul class="nav pull-right">
                        <!--<li><a id="poller_status"></a></li>-->

                        <?php if(isset($footer_entries)) { echo implode(PHP_EOL, $footer_entries); } ?>

                        <li class="dropdown">
                          <?php
                          $notification_count = safe_count($notifications);
                          if ($notification_count) // FIXME level 10 only, maybe? (answer: just do not add notifications for this users. --mike)
                          {
                            $div_class = 'dropdown-menu';
                            if ($notification_count > 5)
                            {
                              $div_class .= ' pre-scrollable';
                            }
                            ?>
                              <a href="<?php echo(generate_url(array('page' => 'overview'))); ?>" class="dropdown-toggle" data-hover="dropdown"
                                 data-toggle="dropdown">
                                  <i class="<?php echo $config['icon']['alert']; ?>"></i> <b class="caret"></b></a>
                              <div class="<?php echo($div_class); ?>" style="width: 700px; max-height: 500px; z-index: 2000; padding: 10px 10px 0px;">

                                  <h3>Notifications</h3>
                                <?php
                                foreach ($notifications as $notification)
                                {
                                  // FIXME handle severity parameter with colour or icon?
                                  if (isset($config['syslog']['priorities'][$notification['severity']]))
                                  {
                                    // Numeric severity to string
                                    $notification['severity'] = $config['syslog']['priorities'][$notification['severity']]['label-class'];
                                  }
                                  echo('<div width="100%" class="alert alert-' . $notification['severity'] . '">');
                                  $notification_title = '';
                                  if (isset($notification['unixtime']))
                                  {
                                    $timediff           = $GLOBALS['config']['time']['now'] - $notification['unixtime'];
                                    $notification_title .= format_uptime($timediff, "short-3") . ' ago: ';
                                  }
                                  if (isset($notification['title']))
                                  {
                                    $notification_title .= $notification['title'];
                                  }
                                  if ($notification_title)
                                  {
                                    echo('<h4>' . $notification_title . '</h4>');
                                  }
                                  echo($notification['text'] . '</div>');
                                }
                                ?>
                              </div>
                            <?php
                          }
                          else
                          {
                            // Dim the icon to 20% opacity, makes the red pretty much blend in to the navbar
                            ?>
                              <a href="<?php echo(generate_url(array('page' => 'overview'))); ?>" data-alt="Notification center" class="dropdown-toggle"
                                 data-hover="dropdown" data-toggle="dropdown">
                                  <i style="filter: opacity(30%);" class="sprite-checked"></i></a>
                            <?php
                          }
                          ?>
                        </li>

                        <li class="dropdown">
                            <a href="<?php echo(generate_url(array('page' => 'overview'))); ?>" class="dropdown-toggle" data-hover="dropdown"
                               data-toggle="dropdown">
                                <i class="sprite-clock"></i> <?php echo(number_format($gentime, 3)); ?>s <b class="caret"></b></a>
                            <div class="dropdown-menu" style="padding: 10px 10px 0px 10px;">
                                <table class="table table-condensed-more table-striped">
                                    <tr>
                                        <th>Page</th>
                                        <td><?php echo(number_format($gentime, 3)); ?>s</td>
                                    </tr>
                                    <tr>
                                        <th>Cache</th>
                                        <td><?php echo(number_format($cache_time, 3)); ?>s</td>
                                    </tr>
                                    <tr>
                                        <th>Menu</th>
                                        <td><?php echo(number_format($menu_time, 3)); ?>s</td>
                                    </tr>
                                  <?php
                                  if (isset($form_time))
                                  {
                                    ?>
                                      <tr>
                                          <th>Form</th>
                                          <td><?php echo(number_format($form_time, 3)); ?>s</td>
                                      </tr>
                                    <?php
                                  }
                                  ?>

                                </table>
                                <table class="table table-condensed-more table-striped">
                                    <tr>
                                        <th colspan=2>MySQL</th>
                                    </tr>
                                    <tr>
                                        <th>Cell</th>
                                        <td><?php echo(($db_stats['fetchcell'] + 0) . '/' . round($db_stats['fetchcell_sec'] + 0, 4) . 's'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Row</th>
                                        <td><?php echo(($db_stats['fetchrow'] + 0) . '/' . round($db_stats['fetchrow_sec'], 4) . 's'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Rows</th>
                                        <td><?php echo(($db_stats['fetchrows'] + 0) . '/' . round($db_stats['fetchrows_sec'] + 0, 4) . 's'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Column</th>
                                        <td><?php echo(($db_stats['fetchcol'] + 0) . '/' . round($db_stats['fetchcol_sec'] + 0, 4) . 's'); ?></td>
                                    </tr>
                                </table>
                                <table class="table  table-condensed-more  table-striped">
                                    <tr>
                                        <th colspan=2>Memory</th>
                                    </tr>
                                    <tr>
                                        <th>Cached</th>
                                        <td><?php echo formatStorage($cachesize); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Page</th>
                                        <td><?php echo formatStorage($fullsize); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Peak</th>
                                        <td><?php echo formatStorage(memory_get_peak_usage()); ?></td>
                                    </tr>
                                </table>
                              <?php
                              if ($_SESSION['userlevel'] >= 10 && function_exists('get_cache_stats'))
                              {
                                $phpfastcache            = get_cache_stats();
                                $phpfastcache['enabled'] = $phpfastcache['enabled'] ? '<span class="text-success">Yes</span>' :
                                  '<span class="text-danger">No</span>';
                                ?>
                                  <table class="table  table-condensed-more  table-striped">
                                      <tr>
                                          <th colspan=2>Fast Cache</th>
                                      </tr>
                                      <tr>
                                          <th>Enabled</th>
                                          <td><?php echo $phpfastcache['enabled']; ?></td>
                                      </tr>
                                      <tr>
                                          <th>Driver</th>
                                          <td><?php echo $phpfastcache['driver']; ?></td>
                                      </tr>
                                      <tr>
                                          <th>Total size</th>
                                          <td><?php echo formatStorage($phpfastcache['size']); ?></td>
                                      </tr>
                                  </table>
                                <?php
                              }
                              ?>
                            </div>
                        </li>

                      <?php if ($config['profile_sql'] == TRUE && $_SESSION['userlevel'] >= 10)
                      {
                        ?>
                          <li class="dropdown">
                              <a href="<?php echo(generate_url(array('page' => 'overview'))); ?>" class="dropdown-toggle" data-hover="dropdown"
                                 data-toggle="dropdown">
                                  <i class="<?php echo $config['icon']['databases']; ?>"></i> <b class="caret"></b></a>
                              <div class="dropdown-menu" style="padding: 10px 10px 0px 10px; width: 1150px; height: 700px; z-index: 2000; overflow: scroll;">
                                  <table class="table  table-condensed-more  table-striped">

                                    <?php

                                    $sql_profile = array_sort($sql_profile, 'time', 'SORT_DESC');
                                    $sql_profile = array_slice($sql_profile, 0, 15);
                                    foreach ($sql_profile AS $sql_query)
                                    {
                                      echo '<tr><td>', $sql_query['time'], '</td><td>';
                                      print_sql($sql_query['sql']);
                                      echo '</td></tr>';
                                    }

                                    ?>
                                  </table>
                              </div>
                          </li>
                        <?php
                      } // End profile_sql
                      ?>

                    </ul>
                </div>
            </div>
        </div>
    </footer>

  <?php

} // end if bare

clear_duplicate_cookies();

//  <script type="text/javascript">
//  $(document).ready(function()
//  {
//    $('#poller_status').load('ajax_poller_status.php');
//  });
//
//  var auto_refresh = setInterval(
//    function ()
//    {
//      $('#poller_status').load('ajax_poller_status.php');
//    }, 10000); // refresh every 10000 milliseconds
//  </script>


// Generate UI alerts to be inserted at ##UI_ALERTS##

// Display warning about requiring alerting rebuild
if (get_obs_attrib('alerts_require_rebuild')) {
  del_obs_attrib('alerts_require_rebuild');
  /* not required anymore
  $tmp_notif = array('text'     => '<h4>Alerting requires rebuild</h4>' .
                                   'Changes have been made to the alerting system which require a rebuild before they are effective. <a href="' .
                                   generate_url(array('page' => 'alert_regenerate', 'action' => 'update')) . '">Rebuild now.</a>',
                     'severity' => 'warning');

  $alerts[]        = $tmp_notif;
  $notifications[] = $tmp_notif;
  unset($tmp_notif);
  */
}

foreach ($alerts as $alert) {
  register_html_alert($alert['text'], $alert['title'], $alert['severity']);
}

// No dropdowns on touch gadgets
if (!get_var_true($_SESSION['touch'])) {
  register_html_resource('js', 'twitter-bootstrap-hover-dropdown.min.js');
}

// FIXME. change to register_html_resource(), but maybe better to keep them at the bottom? Function has no way to do this right now
register_html_resource('js', 'bootstrap-select.min.js');
?>

<script type="text/javascript">
    $('.selectpicker').selectpicker({
        iconBase: '', // reset iconbase from glyphicon
        tickIcon: 'glyphicon glyphicon-ok',
    });
</script>

<!-- ##SCRIPT_CACHE## -->
</body>
</html>

<?php ob_end_flush(); ?>
