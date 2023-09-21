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

$oids = ['TotMiss' => 'Misses', 'TotHits' => 'Hits'];

$i = 0;

foreach ($oids as $oid => $descr) {
    $oid_ds                   = truncate($oid, 19, '');
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = $descr;
    $rrd_list[$i]['ds']       = $oid_ds;
    $i++;
}

$colours    = "mixed";
$nototal    = 1;
$unit_text  = "Hit/Miss";
$simple_rrd = 1;

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

?>
