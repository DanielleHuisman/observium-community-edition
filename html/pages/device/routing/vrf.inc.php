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

$link_array = [
  'page'   => 'device',
  'device' => $device['device_id'],
  'tab'    => 'routing',
  'proto'  => 'vrf'
];

$navbar = ['brand' => "VRFs", 'class' => "navbar-narrow"];

$navbar['options']['basic']['text'] = 'Basic';
// $navbar['options']['details']['text'] = 'Details';
$navbar['options']['graphs'] = ['text' => 'Graphs', 'class' => 'pull-right', 'icon' => $config['icon']['graphs']];

foreach ($navbar['options'] as $option => $array) {
    if ($vars['view'] == $option) {
        $navbar['options'][$option]['class'] .= " active";
    }
    $navbar['options'][$option]['url'] = generate_url($link_array, ['view' => $option]);
}

foreach (['graphs'] as $type) {
    foreach ($config['graph_types']['port'] as $option => $data) {
        if ($vars['view'] == $type && $vars['graph'] == $option) {
            $navbar['options'][$type]['suboptions'][$option]['class'] = 'active';
            $navbar['options'][$type]['text']                         .= ' (' . $data['name'] . ')';
        }
        $navbar['options'][$type]['suboptions'][$option]['text'] = $data['name'];
        $navbar['options'][$type]['suboptions'][$option]['url']  = generate_url($link_array, ['view' => $type, 'graph' => $option]);
    }

}

print_navbar($navbar);
unset($navbar);

echo generate_box_open();

echo '<table class="table table-striped">';
foreach (dbFetchRows("SELECT * FROM `vrfs` WHERE `device_id` = ? ORDER BY `vrf_name`", [$device['device_id']]) as $vrf) {
    include($config['html_dir'] . "/includes/print-vrf.inc.php");
}

echo "</table>";

echo generate_box_close();

// EOF
