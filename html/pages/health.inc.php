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

$datas = ['processor' => ['icon' => $config['entities']['processor']['icon']],
          'mempool'   => ['icon' => $config['entities']['mempool']['icon']],
          'storage'   => ['icon' => $config['entities']['storage']['icon']]];

if ($cache['sensors']['stat']['count']) {
    $datas['sensor'] = ['icon' => $config['entities']['sensor']['icon']];
}
if ($cache['statuses']['stat']['count']) {
    $datas['status'] = ['icon' => $config['entities']['status']['icon']];
}
if ($cache['counters']['stat']['count']) {
    $datas['counter'] = ['icon' => $config['entities']['counter']['icon']];
}
if ($cache['printersupplies']['count']) {
    $datas['printersupply'] = ['icon' => $config['entities']['printersupply']['icon']];
}

if (isset($config['sensor_types'][$vars['metric']])) {
    // Override sensor specific metric to sensor_class
    $vars['sensor_class'] = $vars['metric'];
    $vars['metric']       = "sensor";
} elseif ($vars['metric'] == 'sensors') {
    // Compat with old metric
    $vars['metric'] = "sensor";
} elseif (isset($config['counter_types'][$vars['metric']])) {
    //$vars['counter_class'] = $vars['metric'];
    $vars['metric'] = "counter";
} elseif ($vars['metric'] == 'status') {
    // Status
    if (isset($vars['class']) && !isset($vars['entPhysicalClass'])) {
        $vars['entPhysicalClass'] = $vars['class'];
        unset($vars['class']);
    }
} elseif ($vars['metric'] == 'printersupplies') {
    // Compat with old metric
    $vars['metric'] = "printersupply";
} elseif (!isset($datas[$vars['metric']])) {
    // By default display processor
    $vars['metric'] = "processor";
}
//if (!$vars['view'])   { $vars['view']   = "detail"; }

$link_array = ['page' => 'health'];


$navbar          = [];
$navbar['brand'] = "Health";
$navbar['class'] = "navbar-narrow";

$navbar_count = count($datas);
foreach ($datas as $type => $options) {
    if ($vars['metric'] == $type) {
        $navbar['options'][$type]['class'] = "active";
    } elseif ($navbar_count > 7) {
        $navbar['options'][$type]['class'] = "icon";
    } // Show only icons if too many items in navbar
    if (isset($options['icon'])) {
        $navbar['options'][$type]['icon'] = $options['icon'];
    }
    $navbar['options'][$type]['url']  = generate_url($link_array, ['metric' => $type, 'view' => $vars['view']]);
    $navbar['options'][$type]['text'] = $config['entities'][$type]['names'];
}

//$navbar['options']['graphs']['text']  = 'Graphs';
$navbar['options']['graphs']['icon']  = $config['icon']['graphs'];
$navbar['options']['graphs']['right'] = TRUE;


if ($vars['metric'] == "storage") {

    foreach (['perc' => 'Percentage', 'bytes' => 'Bytes'] as $graph_type => $text) {
        $navbar['options']['graphs']['suboptions'][$graph_type]['text'] = $text;
        if ($vars['graph'] == $graph_type) {
            $navbar['options']['graphs']['text'] = '(' . $text . ')';
        }
        $navbar['options']['graphs']['suboptions'][$graph_type]['url'] = generate_url($vars, ['graph' => $graph_type]);
    }

    if (isset($vars['graph'])) {
        $navbar['options']['graphs']['suboptions']['none']['url']  = generate_url($vars, ['graph' => NULL]);
        $navbar['options']['graphs']['suboptions']['none']['text'] = "None";
    }

} else {
    if ($vars['view'] == "graphs") {
        $navbar['options']['graphs']['class'] = 'active';
        $navbar['options']['graphs']['url']   = generate_url($vars, ['view' => "detail"]);
    } else {
        $navbar['options']['graphs']['url'] = generate_url($vars, ['view' => "graphs"]);
    }
}


print_navbar($navbar);

register_html_title($config['entities'][$vars['metric']]['names']);

if ($vars['metric'] == "sensor") {
    if (is_string($vars['sensor_class']) && isset($config['sensor_types'][$vars['sensor_class']])) {
        register_html_title(nicecase($vars['sensor_class']));
    }

    include($config['html_dir'] . '/pages/health/sensor.inc.php');
} elseif ($vars['metric'] == "status") {
    include($config['html_dir'] . '/pages/health/status.inc.php');
} elseif ($vars['metric'] == "counter") {
    include($config['html_dir'] . '/pages/health/counter.inc.php');
} elseif (isset($datas[$vars['metric']]) && is_file('pages/health/' . $vars['metric'] . '.inc.php')) {
    include($config['html_dir'] . '/pages/health/' . $vars['metric'] . '.inc.php');
} else {
    print_warning("Unknown health metric " . $vars['metric'] . " found.");
}

// EOF
