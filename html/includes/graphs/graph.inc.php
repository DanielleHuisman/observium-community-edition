<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

#ob_clean();

// Init & clean
//print_vars($index);
unset($index); // Clean accidentally global vars
$total_start = utime();

// Init global var for information about generated graph
$graph_return = [
  'status'        => FALSE, // --> $GLOBALS['rrd_status']
  'command'       => '',    // --> $GLOBALS['exec_status']['command'] added in rrdtool_graph()
  'output'        => '',    // --> $GLOBALS['exec_status']['stdout']  added in rrdtool_graph()
  'runtime'       => 0,     // --> $GLOBALS['exec_status']['runtime'] added in rrdtool_graph()
  'total'         => 0,     // total runtime for graph script
  'rrds'          => [],    // list of used rrd files           added in get_rrd_path()
  'filename'      => '',    // Generated image filename
  'descr'         => '',    // graph description if exist
  'valid_options' => [],    // hrm, used somewhere
];

preg_match('/^(?P<type>[a-z0-9A-Z-]+)_(?P<subtype>[a-z0-9A-Z-_]+)/', $vars['type'], $graphtype);

if (isset($vars['format']) && array_key_exists($vars['format'], $config['graph_formats'])) {
    $extension  = $config['graph_formats'][$vars['format']]['extension'];
    $mimetype   = $config['graph_formats'][$vars['format']]['mimetype'];
    $img_format = strtoupper($vars['format']);
} else {
    $extension = 'png';
    $mimetype  = 'image/png';
}

$graphfile = $config['temp_dir'] . "/" . random_string() . "." . $extension;

if (OBS_DEBUG) {
    print_vars($vars);
    print_vars($graphtype);
}

if (isset($graphtype['type']) && isset($graphtype['subtype'])) {
    $type    = $graphtype['type'];
    $subtype = $graphtype['subtype'];
} else {
    graph_error("Invalid graph type format.");
    exit;
}

// Get device array
if (is_intnum($vars['device'])) {
    $device = device_by_id_cache($vars['device']);
} elseif (!safe_empty($vars['device'])) {
    $device = device_by_name($vars['device']);
} elseif ($type === 'device' && is_intnum($vars['id'])) {
    $device = device_by_id_cache($vars['id']);
}

// $from, $to - unixtime (or rrdgraph time interval, i.e. '-1d', '-6w')
// $timestamp_from, $timestamp_to - timestamps formatted as 'Y-m-d H:i:s'
if (isset($vars['timestamp_from']) && preg_match(OBS_PATTERN_TIMESTAMP, $vars['timestamp_from'])) {
    $vars['from'] = strtotime($vars['timestamp_from']);
}
if (isset($vars['timestamp_to']) && preg_match(OBS_PATTERN_TIMESTAMP, $vars['timestamp_to'])) {
    $vars['to'] = strtotime($vars['timestamp_to']);
}

// Validate rrdtool compatible time string and set to now/day if it's not valid
if (preg_match(OBS_PATTERN_RRDTIME, $vars['to'])) {
    $to = $vars['to'];
}              // else { $to     = get_time(); }
if (preg_match(OBS_PATTERN_RRDTIME, $vars['from'])) {
    $from = $vars['from'];
}              // else { $from   = get_time('day'); }

if (isset($vars['period']) && is_numeric($vars['period'])) {
    $to     = get_time();
    $from   = get_time() - $vars['period'];
    $period = $vars['period'];
} elseif (preg_match('/[\-]*\d+[s|w|m|d|y|h]/', $vars['from'])) {
    // It seems we have AT-style/timespec. Just pass it through (some features will break because we can't calculate period)
    $from = $vars['from'];
    if (preg_match('/[\-]*\d+[s|w|m|d|y|h]/', $vars['to'])) {
        $to = $vars['to'];
    } else {
        $to = 'now';
    }
} else {
    $from = (isset($vars['from']) && is_numeric($vars['from'])) ? $vars['from'] : get_time() - 86400;
    $to   = (isset($vars['to']) && is_numeric($vars['to'])) ? $vars['to'] : time();
    if ($from < 0) {
        $from = $to + $from;
    }
    $period = $to - $from;
}

// Set prev_from & prev_to if we have a period
if (isset($period)) {
    $prev_from = $from - $period;
    $prev_to   = $from;
}

$graph_include      = FALSE;
$definition_include = FALSE;
//print_message("Graph type: $type, subtype: $subtype");

if (is_file($config['html_dir'] . "/includes/graphs/$type/$subtype.inc.php")) {
    $graph_include = $config['html_dir'] . "/includes/graphs/$type/$subtype.inc.php";
} elseif (is_array($config['graph_types'][$type][$subtype]['ds'])) {
    // Init tags array
    $tags = [];

    // Additional include with define some graph variables like $unit_text, $graph_title
    // Currently only for indexed definitions
    if ($config['graph_types'][$type][$subtype]['index'] &&
        is_file($config['html_dir'] . "/includes/graphs/$type/definition.inc.php")) {
        $definition_include = $config['html_dir'] . "/includes/graphs/$type/definition.inc.php";
    }
    $graph_include = $config['html_dir'] . "/includes/graphs/generic_definition.inc.php";
} elseif (is_file($config['html_dir'] . "/includes/graphs/$type/graph.inc.php")) {
    $graph_include = $config['html_dir'] . "/includes/graphs/$type/graph.inc.php";
}

if ($graph_include) {
    include($config['html_dir'] . "/includes/graphs/$type/auth.inc.php");

    if (isset($auth) && $auth) {
        if ($definition_include) {
            include_once($definition_include);
        }
        include($graph_include);

        // Requested a rigid height graph, probably for the dashboard.
        // If we don't know the legend height, turn off legend.
        // If we know the height and it won't fit, turn it off.

        if (!(isset($vars['legend']) && $vars['legend'] == 'no') &&
            (isset($vars['rigid_height']) && $vars['rigid_height'] == 'yes')) {
            $line_height = ($width > 350 ? 14 : 12); // Set line height based on font size chosen by width
            if (!isset($graph_return['legend_lines'])) { // Don't know legend length
                print_debug('no legend height');
                $vars['legend'] = 'no';
            } elseif (($graph_return['legend_lines'] * $line_height) > ($height - 100)) { // Legend too long
                print_debug('legend too tall: ' . $graph_return['legend_lines'] * $line_height);
                $vars['legend'] = 'no';
            } else { // Legend fits
                $height = $height - ($graph_return['legend_lines'] * $line_height);
            }
        }

        $rrd_options .= ' --start ' . rrdtool_escape($from) .
                        ' --end ' . rrdtool_escape($to) .
                        ' --width ' . rrdtool_escape($width) .
                        ' --height ' . rrdtool_escape($height) . ' ';

        if ($vars['legend'] === 'no') {
            $rrd_options .= ' -g';
            $legend      = 'no';
        }

    }
} elseif (!isset($vars['command_only'])) {
    graph_error('no ' . $type . '_' . $subtype . ''); // Graph Template Missing
}

if ($error_msg) {
    // We have an error :(
    graph_error($graph_error);
} elseif (!$auth) {
    // We are unauthenticated :(
    if ($width < 200) {
        graph_error("No Auth");
    } else {
        graph_error("No Authorization");
    }
} else {
    #$rrd_options .= " HRULE:0#999999";
    if ($no_file) {
        if ($width < 200) {
            graph_error("No RRD");
        } else {
            graph_error("Missing RRD Datafile");
        }
    } elseif (isset($vars['command_only']) && $vars['command_only'] == TRUE) {
        $return = rrdtool_graph($graphfile, $rrd_options);
        //print_vars($GLOBALS['exec_status']);

        unlink($graphfile);

        if (isset($config['graph_types'][$type][$subtype]['long']) && empty($graph_return['descr'])) {
            $graph_return['descr'] = $config['graph_types'][$type][$subtype]['long'];
        }
    } else {
        if ($rrd_options) {
            rrdtool_graph($graphfile, $rrd_options);
            //print_debug($rrd_cmd);
            if (is_file($graphfile)) {
                if ($vars['image_data_uri'] == TRUE) {
                    $image_data_uri = data_uri($graphfile, $mimetype);
                } elseif (!OBS_DEBUG) {
                    //$fd = fopen($graphfile, 'rb');
                    header('Content-type: ' . $mimetype);
                    header('Content-Disposition: inline; filename="' . basename($graphfile) . '"');
                    header('Content-Length: ' . filesize($graphfile));
                    $out = readfile($graphfile);
                    //fpassthru($fd);
                    //fclose($fd);
                } else {
                    external_exec('/bin/ls -l ' . $graphfile);
                    echo('<img src="' . data_uri($graphfile, $mimetype) . '" alt="graph" />');
                }
                unlink($graphfile);
            } else {
                if ($width < 200) {
                    graph_error("Draw Error");
                } else {

                    if (isset($graph_return['output']) && strlen($graph_return['output']) > 10) {
                        $string = $graph_return['output'];
                    } else {
                        $string = "Error Drawing Graph";
                    }

                    graph_error($string);
                }
            }
        } else {
            if ($width < 200) {
                graph_error("Def Error");
            } else {
                graph_error("Graph Definition Error");
            }
        }
    }
}

// Total runtime and clean graph file
$graph_return['total'] = elapsed_time($total_start);
if (strlen($graph_return['filename']) && is_file($graph_return['filename'])) {
    unlink($graph_return['filename']);
}

// EOF
