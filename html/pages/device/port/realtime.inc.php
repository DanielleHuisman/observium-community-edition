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

if (!isset($vars['interval'])) {
    if (isset($config['os'][$device['os']]['realtime'])) {
        $vars['interval'] = $config['os'][$device['os']]['realtime'];
    } else {
        $vars['interval'] = $config['realtime_interval'];
    }
}

$navbar['class'] = "navbar-narrow";
$navbar['brand'] = "Polling Interval";

foreach ([ 0.25, 1, 2, 5, 10, 15, 30, 60 ] as $interval) {
    if ($vars['interval'] == $interval) {
        $navbar['options'][$interval]['class'] = "active";
    }
    $navbar['options'][$interval]['url']  = generate_url($link_array, [ 'view' => 'realtime', 'interval' => $interval ]);
    $navbar['options'][$interval]['text'] = $interval . "s";
}

print_navbar($navbar);

$realtime_link = 'graph-realtime.php?type=bits&amp;id=' . $port['port_id'] . '&amp;interval=' . $vars['interval'];
if (OBS_DEBUG) {
    $realtime_link .= '&amp;debug=yes';
}

echo generate_box_open();

?>

    <div style="padding: 30px; text-align: center;">
        <object data="<?php echo($realtime_link); ?>" type="image/svg+xml" width="1000" height="400">
            <param name="src" value="graph.php?type=bits&amp;id=<?php echo($port['port_id'] . "&amp;interval=" . $vars['interval']); ?>"/>
            Your browser does not support SVG! You need to either use Firefox or Chrome, or download the Adobe SVG plugin.
        </object>
    </div>
<?php

echo generate_box_close();

// EOF
