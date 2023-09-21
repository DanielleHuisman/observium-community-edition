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

$rrd_filename = get_port_rrdfilename($port, "adsl", TRUE);

$rrd_list[0]['filename'] = $rrd_filename;
$rrd_list[0]['descr']    = "Downstream";
$rrd_list[0]['ds']       = "AturCurrAtn";

$rrd_list[1]['filename'] = $rrd_filename;
$rrd_list[1]['descr']    = "Upstream";
$rrd_list[1]['ds']       = "AtucCurrAtn";

$unit_text = "dB";

$units       = '';
$total_units = '';
$colours     = 'mixed';

$scale_min = "0";

$nototal = 1;

if ($rrd_list) {
    include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");
}

?>
