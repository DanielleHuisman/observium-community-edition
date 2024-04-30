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

include("../includes/observium.inc.php");

// Preflight checks

if (!$config['web_iframe'] && is_iframe()) {
    display_error_http(403, 'Not allowed to run in a iframe');
}

if (preg_match('!^/(js|css)/.+\.map$!', $_SERVER['REQUEST_URI'])) {
    display_error_http(404, $_SERVER['REQUEST_URI']);
} elseif (preg_match('!^/error(?:/(\w+).*)?$!', $_SERVER['REQUEST_URI'], $matches)) {
    // Common error page
    display_error_http($matches[1]);
    //r($vars);
}

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

$runtime_start = utime();

ob_start('html_callback');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?php echo(escape_html($config['base_url'])); ?>"/>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>

    <!-- ##META-EQUIV_CACHE## -->
    <!-- ##META_CACHE## -->
    <!-- ##CSS_CACHE## -->
    <!-- ##STYLE_CACHE## -->
    <!-- ##JS_CACHE## -->
    <?php

    ini_set('allow_url_fopen', 0);
    ini_set('display_errors', 0);

    $_SERVER['PATH_INFO'] = $_SERVER['PATH_INFO'] ?? ($_SERVER['ORIG_PATH_INFO'] ?? '');

    // Clean global $vars variable, it populated only after correct authenticating
    unset($vars);

    include($config['html_dir'] . "/includes/authenticate.inc.php");

    // Default theme set in global or user setting
    if ($config['web_theme_default'] === 'system' && isset($_COOKIE['screen_scheme'])) {
        $theme = $_COOKIE['screen_scheme'];
    } else {
        $theme = $config['web_theme_default'];
    }

    // Fallback to 'light' theme if not in the configured themes
    $theme = isset($config['themes'][$theme]) ? $theme : 'light';

    if (!isset($_SESSION['theme']) || $_SESSION['theme'] !== $theme) {
        session_set_var('theme', $theme);
    }

    if (!isset($_SESSION['mode']) || $_SESSION['mode'] != $config['themes'][$_SESSION['theme']]['type']) {
        $_SESSION['mode'] = $config['themes'][$_SESSION['theme']]['type'];
    }


?>

    <script type="text/javascript">
        var themeName = "<?php echo $_SESSION['theme']; ?>";
        var themeMode = "<?php echo $_SESSION['mode']; ?>";
        console.log(themeName, themeMode);
    </script>

<?php


    register_html_resource('css', $config['themes'][$_SESSION['theme']]['css'] ?? 'observium.css');

    //register_html_resource('css', 'jquery.qtip.min.css');
    //register_html_resource('css', 'sprite.css');
    register_html_resource('css', 'svg-sprite.css');

    register_html_resource('js', 'jquery.min.js');
    register_html_resource('js', 'jquery-migrate.min.js'); // required for unsupported js libs (ie qtip2)
    // register_html_resource('js', 'jquery-ui.min.js'); // FIXME. We don't use JQueryUI or am I wrong? (mike)
    register_html_resource('js', 'bootstrap.min.js');


    ?>
    <title>##TITLE##</title>
    <link rel="shortcut icon" href="<?php echo(escape_html($config['favicon'])); ?>"/>
    <?php

    if ($_SESSION['authenticated']) {
        // Register additional html resources after auth
        register_html_resource('css', 'flags.css');
        register_html_resource('css', 'c3.min.css');

        register_html_resource('js', 'observium.js');
        register_html_resource('js', 'observium-entities.js');
        register_html_resource('js', 'd3.min.js');
        register_html_resource('js', 'c3.min.js');

        $vars = get_vars(); // Parse vars from GET/POST/URI

        if (get_var_true($vars['export'])) // This is for display XML on export pages
        {
            // Code prettify (but it's still horrible)
            register_html_resource('js', 'google-code-prettify.js');
            register_html_resource('css', 'google-code-prettify.css');
        }

        $page_refresh = print_refresh($vars); // $page_refresh used in navbar for refresh menu

        $feeds = [ 'eventlog' ];
        //if ($config['enable_syslog']) { $feeds[] = 'syslog'; }
        foreach ($feeds as $feed) {
            if ($feed_href = generate_feed_url([ 'feed' => $feed ])) {
                echo($feed_href . PHP_EOL);
            }
        }
    }

    if (get_var_true($vars['widescreen'])) {
        session_set_var('widescreen', 1);
        unset($vars['widescreen']);
    } elseif ($vars['widescreen'] === "no") {
        session_unset_var('widescreen');
        unset($vars['widescreen']);
    }

    // FIXME this block still needed?
    if ($_SESSION['widescreen']) {
        // Widescreen style additions
        register_html_resource('css', 'styles-wide.css');
    }

    ?>

</head>

<?php

if ($_SESSION['authenticated']) {
    // Determine type of web browser.
    $browser = detect_browser();

    // FIXME. Old MS IE..Someone still use it (for observium)???
    // https://stackoverflow.com/questions/22059060/is-it-still-valid-to-use-ie-edge-chrome-1
    if ($browser['browser'] === 'MSIE') {
        register_html_resource('js', 'html5shiv.min.js');
        register_html_meta('X-UA-Compatible', 'IE=edge,chrome=1', 'http-equiv');
    }

    $browser_type = $browser['type'];
    if ($browser_type === 'mobile' || $browser_type === 'tablet') {
        session_set_var('touch', 'yes');
    }
    if ($vars['touch'] === "yes") {
        session_set_var('touch', 'yes');
    } elseif ($vars['touch'] === "no") {
        unset($vars['touch']);
        session_unset_var('touch');
    }

    $allow_mobile = (in_array($browser_type, [ 'mobile', 'tablet' ]) ? $config['web_mouseover_mobile'] : TRUE);
    if ($config['web_mouseover'] && $allow_mobile) {
        register_html_resource('js', 'jquery.qtip.min.js');
        register_html_resource('script', 'jQuery(function ($) { entity_popups(); popups_from_data(); });');
    }

    // Do various queries which we use in multiple places
    include($config['html_dir'] . "/includes/cache-data.inc.php");

    // Add some cached notifications
    include($config['html_dir'] . "/includes/notifications.inc.php");

    // Include navbar
    if (!get_var_true($vars['bare'])) {
        include($config['html_dir'] . "/includes/navbar.inc.php");
    }

} else {
    // Exit on non auth
    if ($config['auth_mechanism'] === 'cas' || $config['auth_mechanism'] === 'remote') {
        // Not Authenticated. CAS logon.
        display_error_http(401);
    }
    // Not Authenticated. Print login.
    include($config['html_dir'] . "/pages/logon.inc.php");
    exit;
}
?>

<div id="main_container" class="container" <?php echo(get_var_true($vars['bare']) ? 'style="padding-top: 10px;"' : ''); ?> >

<?php

// Execute (not ajax) form actions
form_action($vars);

// Output UI Alerts
echo '##UI_ALERTS##';

// Authenticated. Print a page.
if (isset($vars['page']) && is_alpha($vars['page']) &&
    is_file($config['html_dir'] . "/pages/" . $vars['page'] . ".inc.php")) {
    $page_file = $config['html_dir'] . "/pages/" . $vars['page'] . ".inc.php";
} else {
    $vars['page'] = 'dashboard';
    $page_file    = $config['html_dir'] . "/pages/" . $vars['page'] . ".inc.php";
}

if (is_alpha($vars['page'])) {
    if ($vars['page'] === 'graphs' && preg_match(OBS_PATTERN_GRAPH_TYPE, $vars['type'], $graphtype)) {
        $panel_name = $graphtype['type'];
    } else {
        $panel_name = $vars['page'];
    }
    //r($panel_name);
    if ($config['pages'][$panel_name]['custom_panel']) {
        include($page_file);
    } else {
        echo '<div class="row">';

        if ($config['pages'][$panel_name]['no_panel']) {
            echo '<div class="col-lg-12">';
        } else {
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
    if (!isset($GLOBALS['cache_html']['page_panel'])) {
        if (is_file($config['html_dir'] . "/includes/panels/" . $panel_name . ".inc.php")) {
            $panel_file = $config['html_dir'] . "/includes/panels/" . $panel_name . ".inc.php";
        } else {
            $panel_file = $config['html_dir'] . "/includes/panels/default.inc.php"; // default
        }
        ob_start();
        include($panel_file);
        $panel_html = ob_get_clean();

        register_html_panel($panel_html);
    }
}

// HTTP runtime and memory size
$gentime  = elapsed_time($runtime_start);
$fullsize = memory_get_usage();
unset($cache);
$cachesize = $fullsize - memory_get_usage();

if ($cachesize < 0) {
    $cachesize = 0;
} // Silly PHP!
?>

</div>

<?php

if (!get_var_true($vars['bare'])) {
    include($config['html_dir'] . "/includes/navbar_footer.inc.php");
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
}

foreach ($alerts as $alert) {
    if (isset($alert['markdown']) && $alert['markdown']) {
        $alert['text']  = get_markdown($alert['text'], TRUE, TRUE);
        $alert['title'] = get_markdown($alert['title'], TRUE, TRUE);
    }
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

<?php

ob_end_flush();

// EOF
