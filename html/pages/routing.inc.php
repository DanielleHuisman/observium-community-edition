<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

register_html_title("Routing");

if ($_GET['optb'] == "graphs" || $_GET['optc'] == "graphs") {
    $graphs = "graphs";
} else {
    $graphs = "nographs";
}

// $routing is populated by cache-data.inc.php

$navbar['brand'] = "Routing";
$navbar['class'] = "navbar-narrow";

foreach ($cache['routing'] as $type => $value) {
    if ($value['count'] > 0) {
        if (!$vars['protocol']) {
            $vars['protocol'] = $type;
        }
        if ($vars['protocol'] == $type) {
            $navbar['options'][$type]['class'] = "active";
        }

        $navbar['options'][$type]['url']  = generate_url(['page' => 'routing', 'protocol' => $type]);
        $navbar['options'][$type]['text'] = nicecase($type) . ' (' . $value['count'] . ')';
    }
}

if (isset($vars['protocol']) && $vars['protocol'] == "ospf") {
    $navbar['options_right']['show_disabled']['text'] = 'Show Disabled';
    if (isset($vars['show_disabled']) && ($vars['show_disabled'])) {
        $navbar['options_right']['show_disabled']['url'] = generate_url($vars, ['show_disabled' => NULL]);
    } else {
        $navbar['options_right']['show_disabled']['url'] = generate_url($vars, ['show_disabled' => 1]);
    }
}

print_navbar($navbar);
unset($navbar);

switch ($vars['protocol']) {
    case 'bgp':
    case 'vrf':
    case 'cef':
    case 'eigrp':
    case 'ospf':
        include($config['html_dir'] . '/pages/routing/' . $vars['protocol'] . '.inc.php');
        break;
    default:
        bug();
        break;
}

// EOF
