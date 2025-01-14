<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) Adam Armstrong
 *
 */

// DOCME needs phpdoc block
function graph_from_definition($vars, $type, $subtype, $device)
{
    global $config, $graph_defs;

    $graph_def = $graph_defs[$type][$subtype];

    include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

    // Here we scale the number of numerical columns shown to make sure we keep the text.
    if ($width > 600) {
        $data_show = ['lst', 'avg', 'min', 'max', 'tot'];
    } elseif ($width > 400) {
        $data_show = ['lst', 'avg', 'max', 'tot'];
    } elseif ($width > 300) {
        $data_show = ['lst', 'avg', 'max', 'tot'];
    } else {
        $data_show = ['lst', 'avg', 'max'];
    }

    // Drop total from view if requested not to show
    if ($args['nototal'] || $nototal) {
        if (($key = array_search('tot', $data_show)) !== FALSE) {
            unset($data_show[$key]);
        }
    }

    $data_len = safe_count($data_show) * 8;

    // Here we scale the length of the description to make sure we keep the numbers
    if ($width > 600) {
        $descr_len = 40;
    } elseif ($width > 300) {
        $descr_len = floor(($width + 42) / 8) - $data_len;
    } else {
        $descr_len = floor(($width + 42) / 7) - $data_len;
    }

    // Build the legend headers using the length values previously calculated
    if (!isset($unit_text)) {
        if ($format == "octets" || $format == "bytes") {
            $units     = "Bps";
            $format    = "bytes";
            $unit_text = "Bytes/s";
        } else {
            $units     = "bps";
            $format    = "bits";
            $unit_text = "Bits/s";
        }
    }

    if ($legend != 'no') {
        $rrd_options .= " COMMENT:'" . rrdtool_escape($unit_text, $descr_len) . "'";
        if (in_array("lst", $data_show)) {
            $rrd_options .= " COMMENT:'   Now'";
        }
        if (in_array("avg", $data_show)) {
            $rrd_options .= " COMMENT:'    Avg'";
        }
        if (in_array("min", $data_show)) {
            $rrd_options .= " COMMENT:'    Min'";
        }
        if (in_array("max", $data_show)) {
            $rrd_options .= " COMMENT:'    Max'";
        }
        if (in_array("tot", $data_show)) {
            $rrd_options .= " COMMENT:'  Total'";
        }
        $rrd_options .= " COMMENT:'\\l'";
    }

    foreach ($graph_def['ds'] as $ds_name => $ds) {

        if (!isset($ds['file'])) {
            $ds['file'] = $graph_def['file'];
        }
        if (!isset($ds['draw'])) {
            $ds['draw'] = "LINE1.5";
        }
        $ds['file'] = get_rrd_path($device, $ds['file']);

        $cmd_def .= " DEF:" . $ds_name . "=" . $ds['file'] . ":" . $ds_name . ":AVERAGE";
        $cmd_def .= " DEF:" . $ds_name . "_min=" . $ds['file'] . ":" . $ds_name . ":MIN";
        $cmd_def .= " DEF:" . $ds_name . "_max=" . $ds['file'] . ":" . $ds_name . ":MAX";

        if (!empty($ds['cdef'])) {
            $ds_name  = $ds_name . "_c";
            $cmd_cdef .= " CDEF:" . $ds_name . "=" . $ds['cdef'] . "";
            $cmd_cdef .= " CDEF:" . $ds_name . "_min=" . $ds['cdef'] . "";
            $cmd_cdef .= " CDEF:" . $ds_name . "_max=" . $ds['cdef'] . "";
        }

        if ($ds['ds_graph'] != "yes") {
            if (empty($ds['colour'])) {
                if (!$config['graph_colours'][$graph_def['colours']][$c_i]) {
                    $c_i = 0;
                }
                $colour = $config['graph_colours'][$graph_def['colours']][$c_i];
                $c_i++;
            } else {
                $colour = $ds['colour'];
            }

            $descr = rrdtool_escape($ds['label'], $descr_len);

            if ($ds['draw'] == "AREASTACK") {
                if ($i == 0) {
                    $ds['ds_draw'] = "AREA";
                } else {
                    $ds['ds_draw'] = "STACK";
                }
            } elseif (preg_match("/^LINESTACK([0-9\.]*)/", $ds['ds_draw'], $m)) {
                if ($i == 0) {
                    $data['ds_draw'] = "LINE$m[1]";
                } else {
                    $ds['draw'] = "STACK";
                }
            }

            $cmd_graph .= ' ' . $ds['draw'] . ':' . $ds_name . '#' . $colour . ':"' . $descr . '"';
            $cmd_graph .= ' GPRINT:' . $ds_name . ':LAST:"%6.2lf%s"';
            $cmd_graph .= ' GPRINT:' . $ds_name . ':AVERAGE:"%6.2lf%s"';
            $cmd_graph .= ' GPRINT:' . $ds_name . ':MAX:"%6.2lf%s\\n"';

        }

    }

    $rrd_options = $cmd_def . $cmd_cdef . $cmd_graph;

    return $rrd_options;
}

// DOCME needs phpdoc block
function graph_error($string) {
    global $vars, $config, $graphfile;

    $vars['bg'] = "FFBBBB";

    include($config['html_dir'] . "/includes/graphs/common.inc.php");

    $rrd_options .= " HRULE:0#555555";
    $rrd_options .= " --title='" . escapeshellarg($string) . "'";
    $rrd_options = preg_replace('/ --(start|end)(\s+\d+)?/', '', $rrd_options); // Remove start/end from error graph

    if (FALSE && $height > 99) {
        rrdtool_graph($graphfile, $rrd_options);

        if (is_file($graphfile)) {
            $mimetype = 'image/png';
            if ($vars['image_data_uri'] == TRUE) {
                $image_data_uri = data_uri($graphfile, $mimetype);
                unlink($graphfile);
                return $image_data_uri;
            }
            if (!OBS_DEBUG) {
                header('Content-type: ' . $mimetype);
                header('Content-Length: ' . filesize($graphfile));
                header('Content-Disposition: inline; filename="' . basename($graphfile) . '"');
                $fd = fopen($graphfile, 'r');
                fpassthru($fd);
                fclose($fd);
            } else {
                echo('<img src="' . data_uri($graphfile, $mimetype) . '" alt="graph" />');
            }
            unlink($graphfile);
#      exit();
        }
    } else {
        if (!OBS_DEBUG) {

            $font        = 2;
            $font_width  = 6;
            $font_height = 12;

            if ($height >= 90) {
                $width  = $width + 75;                            // RRD graphs are 75px wider than request value
                $height = $height + 37;                           // RRD graphs are taller than request value
            }

            if ($width > 350) {
                $width       += 6;
                $height      += 6;
                $font        = 4;
                $font_width  = 8;
                $font_height = 16;

            }

            $im = imagecreate($width, $height);
            imagecolorallocatealpha($im, 254, 0,0, 96);

            if (str_contains($string, "OK")) {

                if (isset($GLOBALS['graph_error'])) {
                    $string = $GLOBALS['graph_error'];
                } else {
                    $string = "RRDTool seems to have generated an empty graph. \nPlease ensure that RRDs are being populated for this device.";
                }
            }


            $lines      = wrap_text($string, $width, $font_width);
            $line_count = count($lines);

            for ($i = 0; $i < $line_count; $i++) {
                $px = (imagesx($im) - $font_width * strlen($lines[$i])) / 2;
                $py = ($height - $font_height * $line_count) / 2 + $i * $font_height;
                imagestring($im, $font, $px, $py, $lines[$i], imagecolorallocate($im, 254, 0, 0));
            }

            if ($vars['image_data_uri'] == TRUE) {
                ob_start();
                imagepng($im);
                $imagedata = ob_get_clean();
                $image_data_uri = 'data:image/png;base64,' . base64_encode($imagedata);

                imagedestroy($im);
                return $image_data_uri;
            }
            header('Content-type: image/png');
            imagepng($im);
            imagedestroy($im);
        }
#    exit();
    }
}

function wrap_text($string, $width, $font_width)
{
    $max_chars    = floor($width / $font_width);
    $wrapped_text = wordwrap($string, $max_chars, "\n");
    return explode("\n", $wrapped_text);
}

// EOF
