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

/**
 * Generate Bootstrap-format Navbar
 *
 *   A little messy, but it works and lets us move to having no navbar markup on pages :)
 *   Examples:
 *   print_navbar(array('brand' => "Apps", 'class' => "navbar-narrow", 'options' => array('mysql' => array('text' => "MySQL", 'url' => generate_url($vars,
 *   'app' => "mysql")))))
 *
 * @param array $vars
 *
 * @return null
 *
 */
function print_tabbar($tabbar)
{
    $output = '<ul class="nav nav-tabs">';

    foreach ($tabbar['options'] as $option => $array) {
        if ($array['right'] == TRUE) {
            $array['class'] .= ' pull-right';
        }
        $output .= '<li class="' . $array['class'] . '">';
        $output .= '<a href="' . $array['url'] . '">';
        if (isset($array['icon'])) {
            $output .= '<i class="' . $array['icon'] . '"></i> ';
        }

        $output .= $array['text'] . '</a></li>';
    }
    $output .= '</ul>';

    echo $output;
}

/**
 * Generate Bootstrap-format navigation bar
 *
 *   A little messy, but it works and lets us move to having no navbar markup on pages :)
 *   Examples:
 *   print_navbar(array('brand' => "Apps", 'class' => "navbar-narrow", 'options' => array('mysql' => array('text' => "MySQL", 'url' => generate_url($vars,
 *   'app' => "mysql")))))
 *
 * @param array $vars
 *
 * @return void
 *
 */
function print_navbar($navbar)
{
    global $config;

    if (OBSERVIUM_EDITION === 'community' && isset($navbar['community']) && $navbar['community'] === FALSE) {
        // Skip nonexistent features on a community edition
        return;
    }

    $id = random_string();
    // Detect an allowed screen ratio for current browser, cached!
    $ua_info = detect_browser();

    ?>

    <div class="navbar <?php echo $navbar['class']; ?>" style="<?php echo $navbar['style']; ?>">
        <div class="navbar-inner">
            <div class="container">
                <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target="#nav-<?php echo $id; ?>">
                    <span class="oicon-bar"></span>
                </button>

                <?php

                if (isset($navbar['brand'])) {
                    echo ' <a class="brand ' . ($navbar['brand-class'] ?? '') . '">' . $navbar['brand'] . '</a>';
                }

                echo('<div class="nav-collapse" id="nav-' . $id . '">');

                //rewrite navbar (for class pull-right)
                $newbar = [];
                foreach (['options', 'options_right'] as $array_name) {
                    if (isset($navbar[$array_name])) {
                        foreach ($navbar[$array_name] as $option => $array) {
                            if (isset($array['userlevel'], $_SESSION['userlevel']) &&
                                $_SESSION['userlevel'] < $array['userlevel']) {
                                // skip not permitted menu items
                                continue;
                            }
                            if (OBSERVIUM_EDITION === 'community' &&
                                isset($array['community']) && $array['community'] === FALSE) {
                                // Skip not exist features on community
                                continue;
                            }

                            if ($array_name === 'options_right' || $array['right'] || (!empty($array['class']) && str_contains($array['class'], 'pull-right'))) {
                                $array['class']                   = str_replace('pull-right', '', $array['class']);
                                $newbar['options_right'][$option] = $array;
                            } else {
                                $newbar['options'][$option] = $array;
                            }
                        }
                    }
                }

                foreach (['options', 'options_right'] as $array_name) {
                    if ($array_name === 'options_right') {
                        if (!$newbar[$array_name]) {
                            break;
                        }
                        echo('<ul class="nav pull-right">');
                    } else {
                        echo('<ul class="nav">');
                    }

                    foreach ($newbar[$array_name] as $option => $array) {

                        // if($array['divider']) { echo '<li class="divider"></li>'; break;}

                        if (!is_array($array['suboptions'])) {
                            echo('<li class="' . $array['class'] . '">');

                            $attribs = [];
                            if (isset($array['tooltip'])) {
                                $array['alt'] = $array['tooltip'];
                            }
                            if (isset($array['alt'])) {
                                $attribs['data-rel']     = 'tooltip';
                                $attribs['data-tooltip'] = $array['alt'];
                            }
                            if (isset($array['id'])) {
                                $attribs['id'] = $array['id'];
                            }

                            $link_opts = generate_html_attribs($attribs);
                            if (isset($array['attribs']) && is_array($array['attribs'])) {
                                $link_opts .= ' ' . generate_html_attribs($array['attribs']);
                            } elseif (isset($array['link_opts'])) {
                                $link_opts .= ' ' . $array['link_opts'];
                            }

                            if (empty($array['url']) || $array['url'] === '#') {
                                $array['url'] = 'javascript:void(0)';
                            }
                            echo('<a href="' . $array['url'] . '" ' . $link_opts . '>');

                            if (isset($array['icon'])) {
                                echo(get_icon($array['icon']) . '&nbsp;');
                                $array['text'] = '<span>' . $array['text'] . '</span>'; // Added span for allow hide by class 'icon'
                            }

                            if (isset($array['image'])) {
                                if (isset($array['image_2x']) && $ua_info['screen_ratio'] > 1) {
                                    // Add hidpi image set
                                    $srcset = ' srcset="' . $array['image_2x'] . ' 2x"';
                                } else {
                                    $srcset = '';
                                }
                                echo('<img src="' . $array['image'] . '"' . $srcset . ' alt="" /> ');
                            }

                            echo($array['text'] . '</a>');

                            echo('</li>');
                        } else {
                            echo('  <li class="dropdown ' . $array['class'] . '">');

                            $attribs = [
                              'class'      => 'dropdown-toggle',
                              'data-hover' => "dropdown",
                              /*'data-toggle' => "dropdown", */ // -- Disables clicking the navbar entry. May need enabling only in touch mode?
                            ];
                            if (get_var_true($_SESSION['touch'])) {
                                // Enable dropdown click on mobile & tablets
                                $attribs['data-toggle'] = "dropdown";
                            }
                            if (isset($array['tooltip'])) {
                                $array['alt'] = $array['tooltip'];
                            }
                            if (isset($array['alt'])) {
                                $attribs['data-rel']     = 'tooltip';
                                $attribs['data-tooltip'] = $array['alt'];
                            }
                            if (isset($array['id'])) {
                                $attribs['id'] = $array['id'];
                            }
                            $link_opts = generate_html_attribs($attribs);
                            if (isset($array['attribs']) && is_array($array['attribs'])) {
                                $link_opts .= ' ' . generate_html_attribs($array['attribs']);
                            } elseif (isset($array['link_opts'])) {
                                $link_opts .= ' ' . $array['link_opts'];
                            }

                            if (empty($array['url']) || $array['url'] === '#') {
                                $array['url'] = 'javascript:void(0)';
                            }
                            echo('    <a href="' . $array['url'] . '" ' . $link_opts . '>');
                            if (isset($array['icon'])) {
                                echo(get_icon($array['icon']) . '&nbsp;');
                            }
                            echo($array['text'] . '
            <strong class="caret"></strong>
          </a>
        <ul class="dropdown-menu">');

                            foreach ($array['suboptions'] as $suboption => $subentry) {
                                if (safe_count($subentry['entries'])) {
                                    navbar_submenu($subentry, $level + 1);
                                } else {
                                    navbar_entry($subentry, $level + 2);
                                }
                            }
                            echo('    </ul>
      </li>');
                        }
                    }
                    echo('</ul>');
                }

                ?>
            </div>
        </div>
    </div>
    </div>

    <?php

}


// DOCME needs phpdoc block
function navbar_location_menu($array)
{
    if ($count = safe_count($array['entries'])) {
        ksort($array['entries']);
    }

    echo('<ul role="menu" class="dropdown-menu">');

    if ($count > 5) {
        foreach ($array['entries'] as $entry => $entry_data) {
            $icon = 'location';
            if ($entry_data['level'] === "location_country") {
                $entry = $entry === '' ? OBS_VAR_UNSET : country_from_code($entry);
                $icon = get_icon_country($entry);
            } elseif ($entry_data['level'] === "location") {
                $name = $entry === '' ? OBS_VAR_UNSET : $entry;
                echo('            <li>' . generate_menu_link_ng([ 'url' => generate_location_url($entry), 'icon' => $icon, 'count' => $entry_data['count'] ], $name) . '</li>');
                continue;
            }

            if ($entry_data['level'] === "location_country") {
                $url = $entry;
                // Attach country code to sublevel
                $entry_data['country'] = $entry;
            } else {
                $url = $entry;
                // Attach country code to sublevel
                $entry_data['country'] = $array['country'];
            }
            if ($url === '') {
                $url = [''];
            }
            $link_array = [
                'page'               => 'devices',
                $entry_data['level'] => var_encode($url)
            ];
            if (isset($array['country'])) {
                $link_array['location_country'] = var_encode($array['country']);
            }

            echo('<li class="dropdown-submenu">' . generate_menu_link_ng([ 'url' => generate_url($link_array), 'icon' => $icon, 'count' => $entry_data['count'] ], $entry));

            navbar_location_menu($entry_data);
            echo('</li>');
        }
    } else {
        $new_entry_array = [];

        foreach ($array['entries'] as $new_entry => $new_entry_data) {
            $icon = 'location';
            if ($new_entry_data['level'] === "location_country") {
                $new_entry = country_from_code($new_entry);
                $icon      = get_icon_country($new_entry);
            } elseif ($new_entry_data['level'] === "location") {
                $name = $new_entry === '' ? OBS_VAR_UNSET : $new_entry;
                //echo('            <li>' . generate_menu_link(generate_location_url($new_entry), $image . '&nbsp;' . $name, $new_entry_data['count']) . '</li>');
                echo('            <li>' . generate_menu_link_ng([ 'url' => generate_location_url($new_entry), 'icon' => $icon, 'count' => $new_entry_data['count'] ], $name) . '</li>');
                continue;
            }

            echo('<li class="nav-header">' . get_icon($icon) . $new_entry . '</li>');
            foreach ($new_entry_data['entries'] as $sub_entry => $sub_entry_data) {
                if (is_array($sub_entry_data['entries'])) {
                    $link_array = [
                      'page'                   => 'devices',
                      $sub_entry_data['level'] => var_encode($sub_entry)
                    ];
                    if (isset($array['country'])) {
                        $link_array['location_country'] = var_encode($array['country']);
                    }

                    //echo('<li class="dropdown-submenu">' . generate_menu_link(generate_url($link_array), $image . '&nbsp;' . $sub_entry, $sub_entry_data['count']));
                    echo('<li class="dropdown-submenu">' . generate_menu_link_ng([ 'url' => generate_url($link_array), 'icon' => $icon, 'count' => $sub_entry_data['count'] ], $sub_entry));
                    navbar_location_menu($sub_entry_data);
                } else {
                    $name = $sub_entry === '' ? OBS_VAR_UNSET : escape_html($sub_entry);
                    //echo('            <li>' . generate_menu_link(generate_location_url($sub_entry), $image . '&nbsp;' . $name, $sub_entry_data['count']) . '</li>');
                    echo('            <li>' . generate_menu_link_ng([ 'url' => generate_location_url($sub_entry), 'icon' => $icon, 'count' => $sub_entry_data['count'] ], $name) . '</li>');
                }
            }
        }
    }
    echo('</ul>');
}

// DOCME needs phpdoc block
function navbar_submenu($entry, $level = 1)
{

    // autoscroll set by navbar-narrow + dropdown-menu, but override max-height
    echo(str_pad('', ($level - 1) * 2) . '                <li class="dropdown-submenu">' .
         //generate_menu_link($entry['url'], get_icon($entry['icon']) . '&nbsp;' . escape_html($entry['title']), $entry['count'], 'label', NULL, $entry['alert_count']) . PHP_EOL);
         generate_menu_link_ng($entry) . PHP_EOL);
    echo(str_pad('', ($level - 1) * 2) . '                  <ul role="menu" class="dropdown-menu ' .
         ((isset($entry['scrollable']) && $entry['scrollable']) ? 'pre-scrollable' : '') . '" style="max-height: 85vh;">' . PHP_EOL);

    foreach ($entry['entries'] as $subentry) {
        if (isset($subentry['entries']) && $subentry['entries']) {
            navbar_submenu($subentry, $level + 1);
        } else {
            navbar_entry($subentry, $level + 2);
        }
    }

    echo(str_pad('', ($level - 1) * 2) . '                  </ul>' . PHP_EOL);
    echo(str_pad('', ($level - 1) * 2) . '                <li>' . PHP_EOL);
}

// DOCME needs phpdoc block
// FIXME. Move to print navbar
function navbar_entry($entry, $level = 1)
{
    global $cache;

    if (isset($entry['divider']) && $entry['divider']) {
        echo(str_pad('', ($level - 1) * 2) . '                <li class="divider"></li>' . PHP_EOL);
    } elseif (isset($entry['userlevel'], $_SESSION['userlevel']) && $_SESSION['userlevel'] < $entry['userlevel']) {
        // skip not permitted menu items
        return;
    } elseif (OBSERVIUM_EDITION === 'community' && isset($entry['community']) && !$entry['community']) {
        // Skip not exist features on community
        return;
    } elseif (isset($entry['locations']) && $entry['locations']) {// Workaround until the menu builder returns an array instead of echo()
        echo(str_pad('', ($level - 1) * 2) . '                <li class="dropdown-submenu">' . PHP_EOL);
        echo(str_pad('', ($level - 1) * 2) . '                  ' .
             generate_menu_link_ng([ 'url' => generate_url([ 'page' => 'locations' ]), 'icon' => 'location' ], 'Locations') . PHP_EOL);
        navbar_location_menu($cache['locations']);
        echo(str_pad('', ($level - 1) * 2) . '                </li>' . PHP_EOL);
    } else {

        if (isset($entry['class'])) {
            $entry_class = ' class="' . $entry['class'] . '"';
        } else {
            $entry_class = '';
        }

        echo(str_pad('', ($level - 1) * 2) . '                <li ' . $entry_class . '>' . generate_menu_link_ng($entry) . '</li>' . PHP_EOL);
    }
}

function print_navbar_stats_debug() {
    global $config, $sql_profile;

    if ($_SESSION['userlevel'] < 7) {
        return;
    }

    if (!safe_empty($GLOBALS['dump'])) {
        ?>
        <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown">
              <i class="sprite-notes"></i> <b class="caret"></b>
            </a>
            <div class="dropdown-menu pre-scrollable"
                 style="padding: 10px 10px 0px 10px; width: 600px; min-height: 200px; max-height: 90vh; z-index: 2000;">
            <?php

            foreach ($GLOBALS['dump'] as $item) {

                print_message(print_r($item['data'], TRUE) . '<br /><small>' . $item['caller_info'] . '</small>', 'info');

            }
            ?>
        </div>
      </li>
    <?php
    }


    if ($config['profile_sql'] && !safe_empty($sql_profile)) {
        ?>
        <li class="dropdown">
            <a href="<?php echo(generate_url(['page' => 'overview'])); ?>"
               class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown">
                <?php echo get_icon('databases'); ?> <b class="caret"></b></a>
                <div class="dropdown-menu pre-scrollable" style="padding: 10px 10px 0px 10px; width: 1150px; max-height: 90vh; z-index: 2000;">

            <?php

            $sql_profile = array_sort((array)$sql_profile, 'time', 'SORT_DESC');
            $sql_profile = array_slice($sql_profile, 0, 15);
            foreach ($sql_profile as $sql_query) {

                $sql_query['location'] = str_replace($config['install_dir'] . '/', '', $sql_query['location']);


                echo '<span class="label label-primary">' . $sql_query['function'] . '()</span> <span class="label label-info">' .
                     $sql_query['location'] . ':' . $sql_query['line'] . '</span> <span class="label">' . round($sql_query['time'], 3) . 's</span>
                                              ' . (new Doctrine\SqlFormatter\SqlFormatter())->format($sql_query['sql']);
            }

            ?>
                </div>
        </li>
        <?php
    } // End profile_sql
}

function print_navbar_stats() {
    global $gentime, $cache_time, $cache_data_time, $menu_time, $defs_time, $form_time,
    $db_stats, $cachesize, $defs_mem, $fullsize, $http_stats;

    ?>
                            <li class="dropdown">
                            <a href="<?php echo(generate_url(['page' => 'overview'])); ?>" class="dropdown-toggle" data-hover="dropdown"
                               data-toggle="dropdown">
                                <i class="sprite-clock"></i> <?php echo(number_format($gentime, 3)); ?>s <b class="caret"></b></a>
                            <div class="dropdown-menu" style="padding: 10px 10px 0px 10px;">
                                <table class="table table-condensed-more table-striped">
                                    <tr>
                                        <th>Page</th>
                                        <td><?php echo(number_format($gentime, 3)); ?>s</td>
                                    </tr><!--
                                    <tr>
                                        <th>Cache</th>
                                        <td><?php echo(number_format($cache_time, 3)); ?>s</td>
                                    </tr>-->
                                    <tr>
                                        <th>Cache Data</th>
                                        <td><?php echo(number_format($cache_data_time, 3)); ?>s</td>
                                    </tr>
                                    <tr>
                                        <th>Menu</th>
                                        <td><?php echo(number_format($menu_time, 3)); ?>s</td>
                                    </tr>

                                    <tr>
                                        <th>Definitions</th>
                                        <td><?php echo(number_format($defs_time, 3)); ?>s</td>
                                    </tr>

                                    <?php
                                    if (isset($form_time)) {
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
                                        <td><?php echo format_bytes($cachesize); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Definitions</th>
                                        <td><?php echo format_bytes($defs_mem); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Page</th>
                                        <td><?php echo format_bytes($fullsize); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Peak</th>
                                        <td><?php echo format_bytes(memory_get_peak_usage()); ?></td>
                                    </tr>
                                </table>
                                <?php
                                if ($_SESSION['userlevel'] >= 10 && !empty($http_stats)) {
                                    ?>
                                    <table class="table  table-condensed-more  table-striped">
                                        <tr>
                                            <th colspan=2>HTTP <?php echo nicecase(OBS_HTTP); ?></th>
                                        </tr>
                                        <tr>
                                            <th>Requests</th>
                                            <td><?php echo $http_stats['requests'] . '/' . round($http_stats['sec'], 4) . 's'; ?></td>
                                        </tr>
                                        <?php
                                        if ($http_stats['ok']) {
                                            ?>
                                            <tr>
                                                <th>Ok</th>
                                                <td><?php echo $GLOBALS['http_stats']['ok']; ?></td>
                                            </tr>
                                            <?php
                                        }

                                        if ($http_stats['error']) {
                                            ?>
                                            <tr>
                                                <th>Errors</th>
                                                <td><?php echo $http_stats['error']; ?></td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                        if ($http_stats['timeout']) {
                                            ?>
                                            <tr>
                                                <th>Timeout</th>
                                                <td><?php echo $http_stats['timeout']; ?></td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </table>
                                    <?php
                                }

                                if ($_SESSION['userlevel'] >= 10 && function_exists('get_cache_stats')) {
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
                                            <td><?php echo format_bytes($phpfastcache['size']); ?></td>
                                        </tr>
                                    </table>
                                    <?php
                                }
                                ?>
                            </div>
                        </li>
    <?php
}

// EOF
