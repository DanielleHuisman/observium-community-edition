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


$units       = '%';
$unit_text   = 'Utilisation';
$total_units = '';

$i = 1;

$rrd_list = [];

foreach ($vars['id'] as $port_id) {

    $processor = dbFetchRow("SELECT * FROM `processors` WHERE `processor_id` = ?", [$port_id]);

    $device = device_by_id_cache($processor['device_id']);

    $rrd_filename = get_rrd_path($device, get_processor_rrd($device, $processor));

    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = rewrite_entity_name($processor['processor_descr'], 'processor');
    $rrd_list[$i]['ds']       = 'usage';

    $i++;
}

$colours = 'mixed';

$scale_min  = "0";
$scale_max  = "100";
$nototal    = 1;
$simple_rrd = TRUE;

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF

