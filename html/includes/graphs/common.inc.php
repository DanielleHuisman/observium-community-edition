<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

/**
 * @var array   $config
 * @var array   $vars
 * @var string  $rrd_options
 * @var string  $graph_title
 * @var boolean $scale_rigid
 * @var boolean $step
 * @var boolean $kibi
 */

if (isset($vars['width'])) {
    $width = $vars['width'];
}
if (!is_numeric($width)) {
    $width = 400;
}
if ($config['trim_tobias']) {
    $width += 12;
}

if ($vars['height']) {
    $height = $vars['height'];
}
if (!is_numeric($height)) {
    $height = 150;
}

if ($vars['inverse']) {
    $in      = 'out';
    $out     = 'in';
    $inverse = TRUE;
} else {
    $in      = 'in';
    $out     = 'out';
    $inverse = FALSE;
}

if (isset($img_format)) {
    $rrd_options .= " -a " . rrdtool_escape($img_format);
}

if (get_var_true($vars['title']) && !safe_empty($graph_title)) {
    if (!str_contains($graph_title, '::')) {
        $graph_title_addition = $config['graph_types'][$type][$subtype]['descr'] ?? str_replace('_', ' ', $subtype);
        // Add common subtype, ie for device graphs
        $graph_title .= ' :: ' . $graph_title_addition;
    }
    // Note, do not escape title by rrdtool escape
    //$rrd_options .= " --title='" . rrdtool_escape($graph_title) . "' ";
    $rrd_options .= " --title=" . escapeshellarg($graph_title) . " ";
}

if (isset($vars['graph_title']) && !safe_empty($vars['graph_title'])) {
    // Note, do not escape title by rrdtool escape
    //$rrd_options .= " --title='" . rrdtool_escape($vars['graph_title']) . "' ";
    $rrd_options .= " --title=" . escapeshellarg($vars['graph_title']) . " ";
}

// Vertical label
if (!safe_empty($graph_label)) {
    // Note, do not escape title by rrdtool escape
    $rrd_options .= " --vertical-label=" . escapeshellarg($graph_label) . " ";
}

if (isset($log_y)) {
    $rrd_options .= ' --logarithmic';
}  /// FIXME. Newer used

if ((isset($alt_y) && !$alt_y) || get_var_false($vars['alt_y'])) {
} else {
    $rrd_options .= ' -Y';
} // Use alternative Y axis if $alt_y not set to FALSE

if (isset($vars['zoom']) && is_numeric($vars['zoom'])) {
    $rrd_options .= " --zoom='" . $vars['zoom'] . "' ";
}

if (isset($vars['yscale']) && $vars['yscale'] === 'none') {
    $rrd_options .= " -y none";
}

// Alternative graph style (default|mrtg)
if (isset($vars['style']) && $vars['style']) {
    $graph_style = strtolower($vars['style']);
} else {
    $graph_style = strtolower($config['graphs']['style']);
}

// Autoscale
if (isset($vars['force_autoscale']) && get_var_true($vars['force_autoscale'])) {
    $rrd_options .= ' -A';
} elseif (!isset($scale_min) && !isset($scale_max)) {
    if ($graph_style === 'mrtg' && !isset($log_y)) { // Don't use this if we're doing logarithmic scale, else it breaks.
        $rrd_options .= ' --alt-autoscale-max';
    } else {
        $rrd_options .= ' --alt-autoscale';
    }
    if ($scale_rigid !== FALSE) {
        $rrd_options .= ' --rigid';
    }
} else {
    if (isset($scale_min) && is_numeric($scale_min)) {
        if ($graph_style === 'mrtg' && $scale_min < 0) {
            // Reset min scale for mrtg style, since it always above zero
            $scale_min = 0;
        }
        $rrd_options .= ' --lower-limit ' . $scale_min;
        if (!isset($scale_max)) {
            $rrd_options .= ' --alt-autoscale-max';
        }
    }
    if (isset($scale_max) && is_numeric($scale_max)) {
        $rrd_options .= ' --upper-limit ' . $scale_max;
        if (!isset($scale_min)) {
            $rrd_options .= ' --alt-autoscale-min';
        }
    }
    if (isset($scale_rigid) && $scale_rigid) {
        $rrd_options .= ' --rigid';
    }
}

if (isset($vars['max']) && get_var_true($vars['max'])) {
    $graph_max = TRUE;
}

if (!$config['graphs']['always_draw_max']) {
    // @adama please fix sensors graphs when always_draw_max FALSE (default) :P
    if (is_numeric($from)) {
        if ($to - $from <= 172800) {
            $graph_max = 0;
        } // Do not graph MAX areas for intervals less then 48 hours
    } elseif (preg_match('/\d(d(ay)?s?|h(our)?s?)$/', $from)) {
        $graph_max = 0; // Also for RRD style from (6h, 2day)
    }
}

// Parse pango markup. Breaks chevrons and other stuff.
//$rrd_options .= ' -P ';

if ($config['themes'][$_SESSION['theme']]['type'] === 'dark') {
    $rrd_options .= ' ' . str_replace("  ", " ", $config['rrdgraph']['dark']);
    $nan_colour  = "#FF000020";
} else {
    $rrd_options .= ' ' . str_replace("  ", " ", $config['rrdgraph']['light']);
    $nan_colour  = "#FFAAAA20";
}

if ($vars['bg']) {
    $rrd_options .= ' -c CANVAS#' . rrdtool_escape($vars['bg']) . ' ';
}

#$rrd_options .= ' -c BACK#FFFFFF';

if (($height < 90 && !get_var_true($vars['draw_all'])) || get_var_true($vars['graph_only'])) {
    $rrd_options .= ' --only-graph';
}

if ($width <= '350') {
    $rrd_options .= " --font LEGEND:7:'" . $config['mono_font'] . "' --font AXIS:6:'" . $config['mono_font'] . "'";
} else {
    $rrd_options .= " --font LEGEND:8:'" . $config['mono_font'] . "' --font AXIS:7:'" . $config['mono_font'] . "'";
}

$rrd_options .= ' --font-render-mode normal';
if ($config['graphs']['dynamic_labels']) {
    // dynamic-labels not supported in rrdtool < 1.4
    $rrd_options .= ' --dynamic-labels';
}

if ($step != TRUE) {
    $rrd_options .= ' -E';
}

if ($kibi == TRUE) {
    $rrd_options .= ' -b 1024';
}

/// DEBUG
//print_vars($rrd_options); exit;

// When IPv6 host is used, need escaping for filename path
if (isset($rrd_filename)) {
    $rrd_filename_escape = rrdtool_escape($rrd_filename);
}

// EOF
