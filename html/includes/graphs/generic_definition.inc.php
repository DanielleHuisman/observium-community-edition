<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$graph_def = $config['graph_types'][$type][$subtype];

$graph_return['legend_lines'] = 0;

if (isset($graph_def['descr'])) {
    $graph_return['descr'] = $graph_def['descr'];
}

// Set some defaults and convert $graph_def values to global values for use by common.inc.php.
// common.inc.php needs converted to use $graph_def so we can remove this.

if (isset($graph_def['name'])) {
    $graph_title = $graph_def['name'];
}
if (isset($graph_def['step'])) {
    $step = $graph_def['step'];
}
if (isset($graph_def['unit_text'])) {
    $unit_text = $graph_def['unit_text'];
}
if (isset($graph_def['scale_min'])) {
    $scale_min = $graph_def['scale_min'];
}
if (isset($graph_def['scale_max'])) {
    $scale_max = $graph_def['scale_max'];
}
if (isset($graph_def['legend'])) {
    $legend = $graph_def['legend'];
}
if (isset($graph_def['log_y']) && $graph_def['log_y'] == TRUE) {
    $log_y = TRUE;
} else {
    unset($log_y);
} // Strange, if $log_y set to FALSE anyway legend logarithmic
if (isset($graph_def['no_mag']) && $graph_def['no_mag'] == TRUE) {
    $mag_unit = "' '";
} else {
    $mag_unit = '%S';
}
if (isset($graph_def['num_fmt'])) {
    $num_fmt = $graph_def['num_fmt'];
} else {
    $num_fmt = '6.1';
}
if (isset($graph_def['nototal'])) {
    $nototal = $graph_def['nototal'];
} else {
    $nototal = TRUE;
}
if (!isset($graph_def['colours'])) {
    $graph_def['colours'] = "mixed";
}
if (isset($graph_def['colour_offset'])) {
    $c_i = $graph_def['colour_offset'];
} else {
    $c_i = 0;
}

if (isset($graph_def['file']) && isset($graph_def['index'])) // Indexed graphs
{
    // Index can be TRUE/FALSE (for TRUE used global $index or $vars with key 'id') or name of used key from $vars
    if (is_bool($graph_def['index']) && $graph_def['index']) {
        // Don't overwrite an index set by the auth.inc.php
        if (!isset($index) && isset($vars['id'])) {
            $index         = $vars['id']; // Default index variable
            $tags['index'] = $index;
        } elseif (isset($index)) {
            // SLA echo for example
            $tags['index'] = $index;
        }
    } elseif (isset($vars[$graph_def['index']])) {
        $index         = $vars[$graph_def['index']];
        $tags['index'] = $index;
    } else {
        $index = FALSE;
    }

    if (strlen($index)) {
        // Rewrite RRD filename
        //$graph_def['file'] = str_replace('-index.rrd', '-'.$index.'.rrd', $graph_def['file']);
        $graph_def['file'] = array_tag_replace($tags, $graph_def['file']);
    }
} elseif (isset($graph_def['entity_type'])) {
    // Entity based RRD filename

    // Index can be TRUE/FALSE (for TRUE used global $index or $vars with key 'id') or name of used key from $vars
    if (is_bool($graph_def['index']) && $graph_def['index']) {
        // Don't overwrite an index set by the auth.inc.php
        if (!isset($index) && isset($vars['id'])) {
            $index         = $vars['id']; // Default index variable
            $tags['index'] = $index;
        }
    } elseif (isset($vars[$graph_def['index']])) {
        $index         = $vars[$graph_def['index']];
        $tags['index'] = $index;
    } else {
        $index = FALSE;
    }

    if (strlen($index)) {
        // Rewrite RRD filename
        $graph_def['file'] = get_entity_rrd_by_id($graph_def['entity_type'], $index);

        // Append entity array as tags
        $entity = get_entity_by_id_cache($graph_def['entity_type'], $index);
        $device = device_by_id_cache($entity['device_id']);
        $tags   = array_merge($tags, $device, $entity);

        // Some params required tag replaces
        if (isset($graph_def['name'])) {
            $graph_title = array_tag_replace($tags, $graph_def['name']);
        }
        if (isset($graph_def['unit_text'])) {
            $unit_text = array_tag_replace($tags, $graph_def['unit_text']);
        }
        if (isset($graph_def['descr'])) {
            $descr = array_tag_replace($tags, $graph_def['descr']);
        }
    }
}

include($config['html_dir'] . '/includes/graphs/common.inc.php');
include_once($config['html_dir'] . '/includes/graphs/legend.inc.php');

foreach ($graph_def['ds'] as $ds_name => $ds) {
    if (!isset($ds['file'])) {
        $ds['file'] = $graph_def['file'];
    } elseif (isset($graph_def['index'])) {
        // Indexed graphs also replace %index% for specific DS
        $ds['file'] = array_tag_replace($tags, $ds['file']);
    }
    if (!isset($ds['draw'])) {
        $ds['draw'] = "LINE1.5";
    }
    if ($graph_def['rra_min'] === FALSE || $ds['rra_min'] === FALSE) {
        $ds['rra_min'] = FALSE;
    } else {
        $ds['rra_min'] = TRUE;
    }
    if ($graph_def['rra_max'] === FALSE || $ds['rra_max'] === FALSE) {
        $ds['rra_max'] = FALSE;
    } else {
        $ds['rra_max'] = TRUE;
    }

    $ds_data = $ds_name;

    $ds['file']        = get_rrd_path($device, $ds['file']);
    $ds['file_escape'] = rrdtool_escape($ds['file']);

    if (isset($ds['graph']) && !$ds['graph']) // Some time required skip graphs, only CDEF
    {
        if (!empty($ds['cdef'])) {
            //$ds_name = $ds_name."_c";
            //$ds_data = $ds_name;
            $cmd_cdef .= " CDEF:" . $ds_name . "=" . $ds['cdef'];
            //$cmd_cdef .= " CDEF:".$ds_name."_min=".$ds['cdef'];
            //$cmd_cdef .= " CDEF:".$ds_name."_max=".$ds['cdef'];
        }
        continue;
    }

    $cmd_def .= " DEF:" . $ds_name . "=" . $ds['file_escape'] . ":" . $ds_name . ":AVERAGE";
    if ($ds['rra_min']) {
        $cmd_def .= " DEF:" . $ds_name . "_min=" . $ds['file_escape'] . ":" . $ds_name . ":MIN";
    } else {
        $cmd_def .= " CDEF:" . $ds_name . "_min=" . $ds_name;
    }
    if ($ds['rra_max']) {
        $cmd_def .= " DEF:" . $ds_name . "_max=" . $ds['file_escape'] . ":" . $ds_name . ":MAX";
    } else {
        $cmd_def .= " CDEF:" . $ds_name . "_max=" . $ds_name;
    }

    //$graph_return['rrds'][$ds['file']][] = $ds_name;

    if (!empty($ds['cdef'])) {
        $ds_name  = $ds_name . "_c";
        $ds_data  = $ds_name;
        $cmd_cdef .= " CDEF:" . $ds_name . "=" . $ds['cdef'];
        $cmd_cdef .= " CDEF:" . $ds_name . "_min=" . $ds['cdef'];
        $cmd_cdef .= " CDEF:" . $ds_name . "_max=" . $ds['cdef'];
    }

    if (!empty($ds['invert'])) {
        $cmd_cdef .= " CDEF:" . $ds_name . "_i=" . $ds_name . ",-1,*";
        $cmd_cdef .= " CDEF:" . $ds_name . "_min_i=" . $ds_name . "_min,-1,*";
        $cmd_cdef .= " CDEF:" . $ds_name . "_max_i=" . $ds_name . "_max,-1,*";
        $ds_data  = $ds_name;
        $ds_name  = $ds_name . "_i";
    }

    //if ($ds['ds_graph'] != "yes") /// FIXME $ds['graph']
    //{
    if (empty($ds['colour'])) {
        if (!$config['graph_colours'][$graph_def['colours']][$c_i]) {
            $c_i = 0;
        }
        $colour = $config['graph_colours'][$graph_def['colours']][$c_i];
        $c_i++;
    } else {
        $colour = $ds['colour'];
    }

    // label with tag replaces
    $descr = array_tag_replace($tags, $ds['label']);
    $descr = rrdtool_escape($descr, $descr_len);

    if ($ds['draw'] == "AREASTACK") {
        $ds['draw']  = "AREA";
        $ds['stack'] = ":STACK";
    } elseif (preg_match("/^LINESTACK([0-9\.]*)/", $ds['ds_draw'], $m)) /// FIXME $ds['draw']
    {
        if ($i == 0) /// FIXME what is $i ?
        {
            $ds['draw'] = "LINE$m[1]";
        } else {
            $ds['draw'] = "STACK";
        }
    }

    $cmd_graph .= ' ' . $ds['draw'] . ':' . $ds_name . '#' . $colour . ':"' . $descr . '"' . $ds['stack'];

    $ds_unit = (strlen($ds['unit']) ? $ds['unit'] : '');      // Unit text per DS
    if (isset($ds['num_fmt'])) {
        $num_fmt = $ds['num_fmt'];
    }                                                         // Numeric format per DS

    if (is_array($data_show)) {
        if (in_array("lst", $data_show)) {
            $cmd_graph .= " GPRINT:" . $ds_data . ":LAST:%" . $num_fmt . "lf" . $mag_unit . $ds_unit;
        }
        if (in_array("avg", $data_show)) {
            $cmd_graph .= " GPRINT:" . $ds_data . ":AVERAGE:%" . $num_fmt . "lf" . $mag_unit . $ds_unit;
        }
        if (in_array("min", $data_show)) {
            $cmd_graph .= " GPRINT:" . $ds_data . "_min:MIN:%" . $num_fmt . "lf" . $mag_unit . $ds_unit;
        }
        if (in_array("max", $data_show)) {
            $cmd_graph .= " GPRINT:" . $ds_data . "_max:MAX:%" . $num_fmt . "lf" . $mag_unit . $ds_unit;
        }
    }
    $cmd_graph .= " COMMENT:'\\l'";
    $graph_return['legend_lines']++;

    if ($ds['line']) {
        if (is_numeric($ds['line'])) {
            $line = 'LINE' . $ds['line'];
        } else {
            $line = 'LINE1';
        }
        $colour_line = darken_color($colour);
        //$cmd_graph .= ' LINE1:'.$ds_name.'#'.$colour_line.':"'.$descr.'"';
        $cmd_graph .= " $line:$ds_name#$colour_line";
        $descr     = ''; // Reset descr
    }

    //}
}

$rrd_options .= $cmd_def . $cmd_cdef . $cmd_graph;

// EOF
