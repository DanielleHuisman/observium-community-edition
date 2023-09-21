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

$link_array = ['page'   => 'device',
               'device' => $device['device_id'],
               'tab'    => 'routing'];

#$type_text['overview'] = "Overview";
$type_text['ipsec_tunnels'] = "IPSEC Tunnels";

// Cisco ACE
$type_text['loadbalancer_rservers'] = "Rservers";
$type_text['lb_slb_vsvrs']          = "Serverfarms";

register_html_title("Routing");

$navbar          = [];
$navbar['brand'] = "Routing";
$navbar['class'] = "navbar-narrow";

foreach ($routing_tabs as $type) {

    if (!$vars['proto']) {
        $vars['proto'] = $type;
    }

    $navbar['options'][$type]['url']  = generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => $type]);
    $navbar['options'][$type]['text'] = nicecase($type);
    if ($vars['proto'] == $type) {
        $navbar['options'][$type]['class'] = "active";
    }

}
print_navbar($navbar);
unset($navbar);

if (isset($device_routing_count[$vars['proto']]) && $device_routing_count[$vars['proto']] == 0) {
    // Prevent direct empty links
    print_warning("Page not exist or data empty.");
    return;
}

if (is_alpha($vars['proto']) && is_file($config['html_dir'] . "/pages/device/routing/" . $vars['proto'] . ".inc.php")) {
    include($config['html_dir'] . "/pages/device/routing/" . $vars['proto'] . ".inc.php");
} else {
    $g_i = 0;
    foreach ($routing_tabs as $type) {
        if ($type != "overview") {
            if (is_file($config['html_dir'] . "/pages/device/routing/overview/" . $type . ".inc.php")) {
                $g_i++;
                $row_colour = !is_intnum($g_i / 2) ? OBS_COLOUR_LIST_A : OBS_COLOUR_LIST_B;

                echo('<div style="background-color: ' . $row_colour . ';">');
                echo('<div style="padding:4px 0px 0px 8px;"><span class=graphhead>' . $type_text[$type] . '</span>');

                include($config['html_dir'] . "/pages/device/routing/overview/" . $type . ".inc.php");

                echo('</div>');
                echo('</div>');
            } else {
                $graph_title = $type_text[$type];
                $graph_type  = "device_" . $type;

                include($config['html_dir'] . "/includes/print-device-graph.php");
            }
        }
    }
}

// EOF
