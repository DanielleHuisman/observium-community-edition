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

// Generate a list of ports and then call the multi_bits grapher to generate from the list

$i=0;

$param = array();

$sql  = "SELECT `port_id`, `ifIndex`, `ifAlias`, `ifName`, `ifType`, `ifDescr`, `port_label`, `port_label_short`, `device_id`, `hostname`, `ports`.`ignore`";
$sql .= " FROM `ports`";
$sql .= " INNER JOIN `devices` USING (`device_id`)";
$sql .= " WHERE ";

if(isset($vars['port_type'])) { $sql .= " `port_descr_type` = ? "; $param[] = $vars['port_type'];  } else { $sql .= "1 "; }

$sql .= generate_query_permitted(array('port', 'device'));

$ports = dbFetchRows($sql, $param);

foreach ($ports as $port)
{
$ignore = 0;

  if (is_array($config['device_traffic_iftype']) && !isset($vars['port_type']))
  {
    foreach ($config['device_traffic_iftype'] as $iftype)
    {
      if (preg_match($iftype ."i", $port['ifType']))
      {
        $ignore = 1;
      }
    }
  }
  if (is_array($config['device_traffic_descr']) && !isset($vars['port_type']))
  {
    foreach ($config['device_traffic_descr'] as $ifdescr)
    {
      if (preg_match($ifdescr."i", $port['ifDescr']) || preg_match($ifdescr."i", $port['ifName']))
      {
        $ignore = 1;
      }
    }
  }

  $rrd_filename = get_port_rrdfilename($port, NULL, TRUE);
  if (!$ignore && is_file($rrd_filename))
  {
    $rrd_filenames[] = $rrd_filename;
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr'] = $port['hostname'] . " " . $port['ifDescr'];
    $rrd_list[$i]['descr_in'] = $port['hostname'];
    $rrd_list[$i]['descr_out'] = $port['port_label_short'];

    $i++;
  }

  unset($ignore);

}

$units ='bps';
$total_units ='B';
$colours_in ='greens';
$multiplier = "8";
$colours_out = 'blues';

$nototal = 1;

$ds_in  = "INOCTETS";
$ds_out = "OUTOCTETS";

$graph_title .= "::bits";

//$colour_line_in = "006600";
//$colour_line_out = "000099";
//$colour_area_in = "CDEB8B";
//$colour_area_out = "C3D9FF";

if($vars['separate'] == 'yes')
{
  include($config['html_dir']."/includes/graphs/generic_multi_bits_separated.inc.php");
} else {
  include($config['html_dir']."/includes/graphs/generic_multi_bits.inc.php");
}

?>
