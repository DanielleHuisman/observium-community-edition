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

//include_once($config['html_dir']."/includes/graphs/common.inc.php");

$i = 0;

$where  = '`device_id` = ? AND `counter_deleted` = ?';
$params = [$device['device_id'], '0'];
if ($class && isset($config['counter_types'][$class])) {
    $where    .= ' AND `counter_class` = ?';
    $params[] = $class;
}
foreach (dbFetchRows("SELECT * FROM `counters` WHERE $where ORDER BY `counter_index`", $params) as $counter) {
    $rrd_filename = get_rrd_path($device, get_counter_rrd($device, $counter));

    if (($config['allow_unauth_graphs'] == TRUE || is_entity_permitted($counter['counter_id'], 'counter')) && rrd_is_file($rrd_filename)) {
        $descr                    = rewrite_entity_name($counter['counter_descr']);
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $descr;
        $rrd_list[$i]['ds']       = "counter";
        //$rrd_list[$i]['ds'] = "sensor";
        $i++;
    }
}

$unit_text = $unit_long;

$units       = '%';
$total_units = '%';
$colours     = 'mixed-10c';
$nototal     = 1;
$scale_rigid = FALSE;

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
