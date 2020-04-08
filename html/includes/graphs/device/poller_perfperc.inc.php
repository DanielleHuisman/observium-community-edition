<?php

$scale_min = 0;

include_once($config['html_dir']."/includes/graphs/common.inc.php");

arsort($device['state']['poller_mod_perf']);
//r($device['state']['poller_mod_perf']);

$total_rrd = get_rrd_path($device, 'perf-poller.rrd');

$rrd_options .= " DEF:tot=".$total_rrd.":val:AVERAGE";

foreach($device['state']['poller_mod_perf'] as $mod => $perf)
{

  $colours = 'mixed';

  if (!isset($config['graph_colours'][$colours][$colour_iter])) { $colour_iter = 0; }
  $colour = $config['graph_colours'][$colours][$colour_iter];
  $colour_iter++;

  $this_rrd = get_rrd_path($device, 'perf-pollermodule-'.$mod.'.rrd');

  $descr = rrdtool_escape($mod, '15');

  $rrd_options .= " DEF:".$mod."=".$this_rrd.":val:AVERAGE";
  $rrd_options .= " CDEF:".$mod."_perc=".$mod.",tot,/,100,*";

  $rrd_options .= " AREA:".$mod."_perc#".$colour.":'".$descr."':STACK";

  $rrd_options .= " GPRINT:".$mod."_perc:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:".$mod."_perc:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:".$mod."_perc:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:".$mod."_perc:MAX:%6.2lf%s\\l";


}


?>
