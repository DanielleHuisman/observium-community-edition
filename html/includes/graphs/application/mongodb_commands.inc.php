<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

include_once($config['html_dir']."/includes/graphs/common.inc.php");

$rrd_filename = get_rrd_path($device, 'app-mongodb-'.$app["app_id"].'.rrd');

$array = array('delete'  => array('descr' => 'Delete', 'colour' => 'FF0000'),
               'insert'  => array('descr' => 'Insert', 'colour' => '00AAAA'),
               'query' => array('descr' => 'Query', 'colour' => '0022FF'),
               'getmore'  => array('descr' => 'Getmore', 'colour' => '22FF22'),
               'update'  => array('descr' => 'Update', 'colour' => '0000CC'),
);

$i = 0;
if (rrd_is_file($rrd_filename))
{
  foreach ($array as $ds => $data)
  {
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr'] = $data['descr'];
    $rrd_list[$i]['ds'] = $ds;
    $rrd_list[$i]['colour'] = $data['colour'];
    $i++;
  }
} else { echo("file missing: $rrd_filename");  }

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Commands";

include($config['html_dir']."/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
