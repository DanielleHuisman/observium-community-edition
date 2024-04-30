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

$i = 0;
foreach ($procs as $proc) {
    $rrd_filename = get_rrd_path($device, get_processor_rrd($device, $proc));

    if (rrd_is_file($rrd_filename)) {
        $descr                    = rewrite_entity_name($proc['processor_descr'], 'processor');
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $descr;
        $rrd_list[$i]['ds']       = "usage";
        $i++;
    }
}

$unit_text = "Load %";

$units       = '%';
$total_units = '%';
$colours     = 'oranges';

$scale_min = "0";
$scale_max = "100";

$divider   = $i;
$text_orig = 1;
$nototal   = 1;

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

?>
