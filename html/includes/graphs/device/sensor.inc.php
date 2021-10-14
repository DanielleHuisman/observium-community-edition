<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

//include_once($config['html_dir']."/includes/graphs/common.inc.php");

$i = 0;
foreach (dbFetchRows("SELECT * FROM `sensors` WHERE `sensor_class` = ? AND `device_id` = ? AND `sensor_deleted` = ? ORDER BY `sensor_index`", array($class, $device['device_id'], '0')) as $sensor)
{
  $rrd_filename = get_rrd_path($device, get_sensor_rrd($device, $sensor));

  if (($config['allow_unauth_graphs'] == TRUE || is_entity_permitted($sensor['sensor_id'], 'sensor')) && rrd_is_file($rrd_filename))
  {
    $descr = rewrite_entity_name($sensor['sensor_descr'], 'sensor');
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr'] = $descr;
    $rrd_list[$i]['ds'] = "sensor";
    $i++;
  }
}

$unit_text = $unit_long;

$units = '%';
$total_units = '%';
$colours ='mixed-10c';
$nototal = 1;
$scale_rigid = FALSE;

include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");

// EOF
