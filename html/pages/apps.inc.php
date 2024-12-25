<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     webui
 * @copyright  (C) Adam Armstrong
 *
 */

$navbar['class'] = 'navbar-narrow';
$navbar['brand'] = 'Apps';

$app_types = [];
foreach ($app_list as $app) {
    if ($vars['app'] == $app['app_type']) {
        $navbar['options'][$app['app_type']]['class'] = 'active';
    }
    $navbar['options'][$app['app_type']]['url']  = generate_url(['page' => 'apps', 'app' => $app['app_type']]);
    $navbar['options'][$app['app_type']]['text'] = nicecase($app['app_type']);

    // Detect and add application icon
    $icon  = $app['app_type'];
    $image = $config['html_dir'] . '/images/apps/' . $icon . '.png';
    if (is_file($image)) {
        // Icon found
        //$icon = $app['app_type'];
    } else {
        [$icon] = explode('-', str_replace('_', '-', $app['app_type']));
        $image = $config['html_dir'] . '/images/apps/' . $icon . '.png';
        if ($icon != $app['app_type'] && is_file($image)) {
            // 'postfix_qshape' -> 'postfix'
            // 'exim-mailqueue' -> 'exim'
        } else {
            $icon = 'apps'; // Generic
        }
    }
    $navbar['options'][$app['app_type']]['image'] = 'images/apps/' . $icon . '.png';
    if (is_file($config['html_dir'] . '/images/apps/' . $icon . '_2x.png')) {
        // HiDPI icon
        $navbar['options'][$app['app_type']]['image_2x'] = 'images/apps/' . $icon . '_2x.png';
    }

    $app_types[$app['app_type']] = [];
}

print_navbar($navbar);
unset($navbar);

if ($vars['app'] && is_alpha($vars['app'])) {
    $include = $config['html_dir'] . '/pages/apps/' . $vars['app'] . '.inc.php';
    if (is_file($include)) {
        include($include);
    } elseif (dbExist('applications', generate_where_clause([ '`app_type` = ?', generate_query_permitted_ng([ 'devices' ]) ]), [ $vars['app'] ])) {
        include($config['html_dir'] . '/pages/apps/default.inc.php');
    } else {
        print_warning('The application "' . $vars['app'] . '" does not exist.');
    }
} else {
    include($config['html_dir'] . '/pages/apps/overview.inc.php');
}

register_html_title('Applications');

// EOF
