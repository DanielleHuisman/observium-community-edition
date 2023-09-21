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

$rrd_filename = get_rrd_path($device, "netscaler-stats-tcp.rrd");

$xre = ['ErrFirstRetransmiss' => 'First',
        'ErrSecondRetransmis' => 'Second',
        'ErrThirdRetransmiss' => 'Third',
        'ErrForthRetransmiss' => 'Fourth',
        'ErrFifthRetransmiss' => 'Fifth',
        'ErrSixthRetransmiss' => 'Sixth',
        'ErrSeventhRetransmi' => 'Seventh'];

foreach ($xre as $stat => $descr) {
    if (rrd_is_file($rrd_filename)) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $descr;
        $rrd_list[$i]['ds']       = $stat;
    }
}

$unit_text = "Retransmissions";

$units       = '';
$total_units = '';
$colours     = 'reds_8';

$scale_min = "0";
#$scale_max = "100";

$text_orig = 1;
$nototal   = 1;

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
