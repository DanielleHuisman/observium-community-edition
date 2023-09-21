<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package                       observium
 * @subpackage                    graphs
 *
 * @author                        Jens Brueckner <Discord: JTC#3678>
 * @copyright                     'aruba-cppm.inc.php'    (C) 2022 Jens Brueckner
 * @copyright                     'Observium'            (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// define rrd filename (static)
$rrd_filename = get_rrd_path($device, "graphs-aruba-cppm-mib-radiusServerTable-0.rrd");

// define variables
$i = 0;

// foreach the rrd data field
foreach (['radAuthRequestTime', 'radPolicyEvalTime'] as $timeStat) {
    $i++;
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = $timeStat;
    $rrd_list[$i]['ds']       = $timeStat;
    $rrd_list[$i]['area']     = 1;
}

// graph look
$unit_text   = 'Milliseconds';
$units       = 'ms';
$total_units = 'ms';
$colours     = 'mixed';
$nototal     = 1;
$scale_min   = "0";
$simple_rrd  = TRUE;

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF