<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) Adam Armstrong
 *
 */

include("memcached.inc.php");
include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$scale_min = 0;
$colours   = "mixed";
$nototal   = 0;
$unit_text = "Items";
$array     = [
  'curr_items' => ['descr' => 'Items', 'colour' => '555555'],
];

$i = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = $data['colour'];
        if (!empty($data['areacolour'])) {
            $rrd_list[$i]['areacolour'] = $vars['areacolour'];
        }
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
