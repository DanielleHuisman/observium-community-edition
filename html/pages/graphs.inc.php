<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

unset($vars['page']);

// Setup here

//if (isset($_SESSION['widescreen']))
//{
//  $graph_width=1700;
//  $thumb_width=180;
//} else {
  $graph_width=1152;
  $thumb_width=113;
//}

$timestamp_pattern = '/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/';
if (isset($vars['timestamp_from']) && preg_match($timestamp_pattern, $vars['timestamp_from']))
{
  $vars['from'] = strtotime($vars['timestamp_from']);
  unset($vars['timestamp_from']);
}
if (isset($vars['timestamp_to'])   && preg_match($timestamp_pattern, $vars['timestamp_to']))
{
  $vars['to'] = strtotime($vars['timestamp_to']);
  unset($vars['timestamp_to']);
}

// Validate rrdtool compatible time string and set to now/day if it's not valid
if (preg_match('/^(\-?\d+|[\-\+]\d+[dwmysh]|now)$/i', $vars['to']))   { $to   = $vars['to'];   } // else { $to     = $config['time']['now']; }
if (preg_match('/^(\-?\d+|[\-\+]\d+[dwmysh]|now)$/i', $vars['from'])) { $from = $vars['from']; } // else { $from   = $config['time']['day']; }

preg_match('/^(?P<type>[a-z0-9A-Z-]+)_(?P<subtype>.+)/', $vars['type'], $graphtype);

if (OBS_DEBUG) { print_vars($graphtype); }

$type = $graphtype['type'];
$subtype = $graphtype['subtype'];

if (is_numeric($vars['device']))
{
  $device = device_by_id_cache($vars['device']);
} elseif (!empty($vars['device'])) {
  $device = device_by_name($vars['device']);
}

if (is_file($config['html_dir']."/includes/graphs/".$type."/auth.inc.php"))
{
  include($config['html_dir']."/includes/graphs/".$type."/auth.inc.php");
}

if (!$auth)
{
  print_error_permission();
  return;
}

  // If there is no valid device specified in the URL, generate an error.
  ## Not all things here have a device (multiple-port graphs or location graphs)
  //if (!is_array($device))
  //{
  //  print_error('<h4>No valid device specified</h4>
  //                  A valid device was not specified in the URL. Please retype and try again.');
  //  break;
  //}

  // Print the device header
  if (isset($device) && is_array($device))
  {
    print_device_header($device);
  }

  if (isset($config['graph_types'][$type][$subtype]['descr']))
  {
    $title .= " :: ".$config['graph_types'][$type][$subtype]['descr'];
  } else {
    $title .= " :: ".nicecase($subtype);
  }

  // Generate navbar with subtypes
  $graph_array = $vars;
  $graph_array['height'] = "60";
  $graph_array['width']  = $thumb_width;

  // Clear collectd argument forcing axis rendering
  if (isset($graph_array['draw_all'])) { unset($graph_array['draw_all']); }

  //$graph_array['legend'] = "no";
  //$graph_array['to']     = $config['time']['now'];

  $navbar = array('brand' => "Graph", 'class' => "navbar-narrow");

  switch ($type)
  {
    case 'device':
    case 'sensor':
    case 'cefswitching':
    case 'munin':
      $navbar['options']['graph'] = array('text' => nicecase($type).' ('.$subtype.')',
                                          'url' => generate_url($vars, array('type' => $type."_".$subtype, 'page' => "graphs")));
      break;
    default:
      # Load our list of available graphtypes for this object
      /// FIXME not all of these are going to be valid
      /// This is terrible. --mike
      /// The future solution is to keep a 'registry' of which graphtypes apply to which entities and devices.
      /// I'm not quite sure if this is going to be too slow. --adama 2013-11-11
      if ($handle = opendir($config['html_dir'] . "/includes/graphs/".$type."/"))
      {
        while (false !== ($file = readdir($handle)))
        {
          if ($file != "." && $file != ".." && $file != "auth.inc.php" && $file != "graph.inc.php" && $file != ".auth.swp" && strstr($file, ".inc.php"))
          {
            $types[] = str_replace(".inc.php", "", $file);
          }
        }
        closedir($handle);
      }

      foreach ($title_array as $key => $element)
      {
        $navbar['options'][$key] = $element;
      }

      $navbar['options']['graph']     = array('text' => 'Graph');

      sort($types);

      foreach ($types as $avail_type)
      {
        if ($subtype == $avail_type)
        {
          $navbar['options']['graph']['suboptions'][$avail_type]['class'] = 'active';
          $navbar['options']['graph']['text'] .= ' ('.$avail_type.')';
        }
        $navbar['options']['graph']['suboptions'][$avail_type]['text'] = nicecase($avail_type);
        $navbar['options']['graph']['suboptions'][$avail_type]['url'] = generate_url($vars, array('type' => $type."_".$avail_type, 'page' => "graphs"));
      }
  }


  // Adding to the dashboard

  if ($_SESSION['userlevel'] > 7) {

    $dashboards = dbFetchRows("SELECT * FROM `dashboards`");
    // FIXME - widget_exists() dashboard_exists(), widget_permitted(), dashboard_permitted(), etc.
    // FIXME - convert this to ajax call, maybe make the code usable on other pages too

    $valid = array('id', 'device', 'c_plugin', 'c_plugin_instance', 'c_type', 'c_type_instance');
    $add_array = [ 'type' => $vars['type'] ];
    if (isset($vars['period']) && is_numeric($vars['period'])) { $add_array['period'] = $vars['period']; }

    foreach($vars as $var => $value) { if(in_array($var, $valid))  { $add_array[$var] = $value; } }

    if (isset($vars['dash_add']) && dashboard_exists($vars['dash_add'])) {
      $widget_id = dbInsert(array('dash_id' => $vars['dash_add'], 'widget_config' => json_encode($add_array), 'widget_type' => 'graph', 'x' => 0, 'y' => 99, 'width' => 3, 'height' => 2), 'dash_widgets');
      print_message('Graph widget added to dashboard.', 'info');
      unset($vars['dash_add']);
    }

    if (isset($vars['dash_add_widget'])) {
      dbUpdate(array('widget_config' => json_encode($add_array)), 'dash_widgets', '`widget_id` = ?', array($vars['dash_add_widget']));
      if (dbAffectedRows() == 1) { print_message("Widget updated.", 'info'); }
      unset($vars['dash_add_widget']);
    }

    $navbar['options_right']['export']['text'] = "Export Data";

    foreach($config['graph_formats'] as $format => $entry)
    {

      $export_array = $graph_array;
      $export_array['format'] = $format;

      $navbar['options_right']['export']['suboptions'][$format]['text'] = $entry['descr'];
      $navbar['options_right']['export']['suboptions'][$format]['url'] = generate_graph_url($export_array);
      $navbar['options_right']['export']['suboptions'][$format]['link_opts'] = 'target="_blank"';
    }


    if (safe_count($dashboards)) {
      $navbar['options_right']['dash']['text'] = "Add to Dashboard";

      foreach($dashboards as $dash) {
        $navbar['options_right']['dash']['suboptions'][$dash['dash_id']]['text'] = "Add to " . $dash['dash_name'];
        $navbar['options_right']['dash']['suboptions'][$dash['dash_id']]['url'] = generate_url($vars, array('page' => "graphs", 'dash_add' => $dash['dash_id']));
/* Disable adding to specific widgets, the menu doesn't expand.
        $widgets = dbFetchRows("SELECT * FROM `dash_widgets` WHERE `dash_id` = ? AND widget_type = 'graph' AND `widget_config` = ?", array($dash['dash_id'], '[]'));
        foreach($widgets as $widget)
        {
          $navbar['options_right']['dash']['suboptions'][$dash['dash_id']]['entries'][$widget['widget_id']]['text'] = "Add to Widget #".$widget['widget_id']."";
          $navbar['options_right']['dash']['suboptions'][$dash['dash_id']]['entries'][$widget['widget_id']]['url'] = generate_url($vars, array('page' => "graphs", 'dash_add_widget' => $widget['widget_id']));
        }
*/
      }

    }
  }



  print_navbar($navbar);

  // Start form for the custom range.

  echo generate_box_open(array('box-style' => 'padding-bottom: 5px;'));

  $thumb_array = array('sixhour' => '6 Hours',
                       'day' => '24 Hours',
                       'twoday' => '48 Hours',
                       'week' => 'One Week',
                       //'twoweek' => 'Two Weeks',
                       'month' => 'One Month',
                       'threemonth' => 'Three Months',
                       'year' => 'One Year',
                       'threeyear' => 'Three Years'
                      );

  $periods = ['21600' => '6 Hours',
    '86400'    => '1 Day',
    '172800'   => '2 Days',
    '604800'   => 'One Week',
    //'1209600'  => 'Two Weeks',
    '2628000'  => 'One Month',
    '7884000'  => 'Three Months',
    '31536000' => 'One Year',
    '94608000' => 'Three Years'];


  echo('<table style="width: 100%; background: transparent;"><tr>');

  foreach ($periods as $period => $text)
  {
    //$graph_array['from']   = $config['time'][$period];

    $graph_array['period'] = $period;

    $remove_vars = ['from', 'to'];
    foreach($remove_vars as $remove_var) {
      if (isset($graph_array[$remove_var])) { unset($graph_array[$remove_var]); }
    }

    $link_array = $graph_array;
    //$link_array['from'] = $graph_array['from'];
    //$link_array['to'] = $graph_array['to'];
    $link_array['page'] = "graphs";
    $link = generate_url($link_array);

    echo('<td style="text-align: center;">');
    echo('<span class="device-head">'.$text.'</span><br />');
    echo('<a href="'.$link.'">');
    echo(generate_graph_tag($graph_array));
    echo('</a>');
    echo('</td>');

  }

  echo('</tr></table>');

  $graph_array = $vars;
  $graph_array['height'] = "300";
  $graph_array['width']  = $graph_width;

  echo generate_box_close();

  $form_vars = $vars;
  unset($form_vars['from']);
  unset($form_vars['to']);
  unset($form_vars['period']);

  $form = array('type'          => 'rows',
                'space'         => '5px',
                'submit_by_key' => TRUE,
                'url'           => 'graphs'.generate_url($form_vars));

  if (is_numeric($vars['from']) && $vars['from'] < 0) { $text_from = time() + $vars['from']; } else { $text_from = date('Y-m-d H:i:s', $vars['from']); }
  if ($vars['to'] === 'now' || $vars['to'] === "NOW") { $text_to = time() + $vars['to']; } else { $text_to = date('Y-m-d H:i:s', $vars['to']); }

  if (isset($vars['period']) && (!isset($vars['from']) || !isset($vars['to']))) {
      $text_to = date('Y-m-d H:i:s', time());
      $text_from = date('Y-m-d H:i:s', time() - $vars['period']);
  }

  // Datetime Field
  $form['row'][0]['timestamp'] = array(
                              'type'        => 'datetime',
                              'grid'        => 10,
                              'grid_xs'     => 10,
                              //'width'       => '70%',
                              //'div_class'   => 'text-nowrap col-sm-push-0', // Too hard, will fix later
                              //'div_class'   => 'col-lg-10 col-md-10 col-sm-10 col-xs-10',
                              'presets'     => TRUE,
                              'min'         => '2007-04-03 16:06:59',  // Hehe, who will guess what this date/time means? --mike
                                                                       // First commit! Though Observium was already 7 months old by that point. --adama
                              'max'         => date('Y-m-d 23:59:59'), // Today
                              'from'        => $text_from,
                              'to'          => $text_to);

  $search_grid = 2;
  if ($type == "port")
  {
    if ($subtype == "bits")
    {
      $speed_list = array('auto' => 'Autoscale', 'speed'  => 'Interface Speed ('.formatRates($port['ifSpeed'], 4, 4).')');
      foreach ($config['graphs']['ports_scale_list'] as $entry)
      {
        $speed = intval(unit_string_to_numeric($entry, 1000));
        $speed_list[$entry] = formatRates($speed, 4, 4);
      }
      $form['row'][0]['scale'] = array(
                                'type'    => 'select',          // Type
                                'name'    => 'Scale',           // Displayed title for item
                                'grid'        => 2,
                                'width'   => '100%',
                                'value'   => (isset($vars['scale']) ? $vars['scale'] : $config['graphs']['ports_scale_default']),
                                'values'  => $speed_list);
      //reduce timestamp element grid sizes
      $form['row'][0]['timestamp']['grid'] -= 2;
    }
    if (in_array($subtype, array('bits', 'percent', 'upkts', 'pktsize')))
    {
      $form['row'][0]['style'] = array(
                                'type'    => 'select',
                                'name'    => 'Graph style',
                                'grid'        => 2,
                                'width'   => '100%',
                                'value'   => (isset($vars['style']) ? $vars['style'] : $config['graphs']['style']),
                                'values'  => array('default' => 'Default', 'mrtg' => 'MRTG'));
      //reduce timestamp element grid sizes
      $form['row'][0]['timestamp']['grid'] -= 1;
      unset($form['row'][0]['timestamp']['grid_xs']);
      $search_grid = 1;
    }
  }

  // Update button
  $form['row'][0]['update'] = [ 'type'        => 'submit',
                                //'name'        => 'Search',
                                //'icon'        => 'icon-search',
                                //'div_class'   => 'col-lg-2 col-md-2 col-sm-2 col-xs-2',
                                'grid'        => $search_grid,
                                'grid_xs'     => ($search_grid > 1 ? $search_grid : 12),
                                'right'       => TRUE ];

  print_form($form);
  unset($form, $speed_list, $speed, $search_grid);

// Run the graph to get data array out of it

$vars = array_merge($vars, $graph_array);
$vars['command_only'] = 1;

include($config['html_dir']."/includes/graphs/graph.inc.php");

unset($vars['command_only']);

// Print options navbar

$navbar = array();
$navbar['brand'] = "Options";
$navbar['class'] = "navbar-narrow";

$navbar['options']['legend']   =  array('text' => 'Show Legend', 'inverse' => TRUE);
$navbar['options']['previous'] =  array('text' => 'Graph Previous');

if (in_array('trend', $graph_return['valid_options'])) {
  $navbar['options']['trend']    =  array('text' => 'Graph Trend');
}
$navbar['options']['max']      =  array('text' => 'Graph Maximum');

if (in_array('inverse', $graph_return['valid_options'])) {
   $navbar['options']['inverse']    =  array('text' => 'Invert Graph');
}


$navbar['options_right']['showcommand'] =  array('text' => 'RRD Command');

foreach (array('options' => $navbar['options'], 'options_right' => $navbar['options_right'] ) as $side => $options)
{
  foreach ($options AS $option => $array)
  {
    if ($array['inverse'] == TRUE)
    {
      if ($vars[$option] == "no")
      {
        $navbar[$side][$option]['url'] = generate_url($vars, array('page' => "graphs", $option => NULL));
      } else {
        $navbar[$side][$option]['url'] = generate_url($vars, array('page' => "graphs", $option => 'no'));
        $navbar[$side][$option]['class'] .= " active";
      }
    } else {
      if ($vars[$option] == "yes")
      {
        $navbar[$side][$option]['url'] = generate_url($vars, array('page' => "graphs", $option => NULL));
        $navbar[$side][$option]['class'] .= " active";
      } else {
        $navbar[$side][$option]['url'] = generate_url($vars, array('page' => "graphs", $option => 'yes'));
      }
    }
  }
}

$navbar['options_right']['graph_link']  =  array('text' => 'Link to Graph', 'url' => generate_graph_url($graph_array), 'link_opts' => 'target="_blank"');

print_navbar($navbar);
unset($navbar);

/*

?>


    <script type="text/javascript" src="js/jsrrdgraph/sprintf.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/strftime.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/RrdRpn.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/RrdTime.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/RrdGraph.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/RrdGfxCanvas.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/RrdGfxSvg.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/base64.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/RrdGfxPdf.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/binaryXHR.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/rrdFile.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/RrdDataFile.js"></script>
    <script type="text/javascript" src="js/jsrrdgraph/RrdCmdLine.js"></script>

<script type="application/x-javascript">
			var mouse_move = function (e) {
				if (this.rrdgraph.mousedown) {
					var factor = (this.rrdgraph.end - this.rrdgraph.start) / this.rrdgraph.xsize;
					var x = e.pageX - this.offsetLeft;
					var diff = x - this.rrdgraph.mousex;
					var difffactor = Math.abs(Math.round(diff*factor));
					if (diff > 0) {
						this.rrdgraph.end -= difffactor;
						this.rrdgraph.start -= difffactor;
					} else {
						this.rrdgraph.end += difffactor;
						this.rrdgraph.start += difffactor;
					}
					this.rrdgraph.mousex = x;
					var start = new Date();
					try {
						this.rrdgraph.graph_paint();
					} catch (e) {
						alert(e+"\n"+e.stack);
					}
					var end = new Date();
					document.getElementById("draw").innerHTML = 'Draw time: '+(end.getTime()-start.getTime())+"ms";
				}
			};
			var mouse_up = function (e) { 
				this.rrdgraph.mousedown = false;
				this.style.cursor="default";
			};
			var mouse_down = function (e) {
				var x = e.pageX - this.offsetLeft;
				this.rrdgraph.mousedown = true;
				this.rrdgraph.mousex = x;
				this.style.cursor="move";
			};
			var mouse_scroll = function (e) {
				e = e ? e : window.event;
				var wheel = e.detail ? e.detail * -1 : e.wheelDelta / 40;
				var cstime = this.stime[this.stidx];
				if (wheel > 0) {
					this.stidx++;
					if (this.stidx >= this.stlen) this.stidx = this.stlen-1;
				} else {
					this.stidx--;
					if (this.stidx < 0) this.stidx = 0;
				}
				if (cstime !== this.stime[this.stidx])  {
					var middle = this.rrdgraph.start + Math.abs(Math.round((this.rrdgraph.end - this.rrdgraph.start)/2));
					this.rrdgraph.start = Math.round(middle - this.stime[this.stidx]/2);
					this.rrdgraph.end = this.rrdgraph.start + this.stime[this.stidx];
					var start = new Date();
					try {
						this.rrdgraph.graph_paint();
					} catch (e) {
						alert(e+"\n"+e.stack);
					}
					var end = new Date();
					document.getElementById("draw").innerHTML = 'Draw time: '+(end.getTime()-start.getTime())+"ms";
				}
				if(e.stopPropagation)
					e.stopPropagation();
				if(e.preventDefault)
					e.preventDefault();
				e.cancelBubble = true;
				e.cancel = true;
				e.returnValue = false;
				return false; 
			};
			function draw() {
				RrdGraph.prototype.mousex = 0;
				RrdGraph.prototype.mousedown = false;
				var cmdline = document.getElementById("cmdline").value;
				var gfx = new RrdGfxCanvas("canvas");
        var fetch = new RrdDataFile();
        var rrdcmdline = null;
        var start = new Date();
        try {
          rrdcmdline = new RrdCmdLine(gfx, fetch, cmdline);
				} catch (e) {
					alert(e+"\n"+e.stack);
				}
				var rrdgraph = rrdcmdline.graph;
				
				gfx.canvas.stime = [ 300, 600, 900, 1200, 1800, 3600, 7200, 21600, 43200, 86400, 172800, 604800, 2592000, 5184000, 15768000, 31536000 ];
				gfx.canvas.stlen = gfx.canvas.stime.length;
				gfx.canvas.stidx = 0;
				gfx.canvas.rrdgraph = rrdgraph;
				gfx.canvas.removeEventListener('mousemove', mouse_move, false);
				gfx.canvas.addEventListener('mousemove', mouse_move, false);
				gfx.canvas.removeEventListener('mouseup', mouse_up, false);
				gfx.canvas.addEventListener('mouseup', mouse_up, false);
				gfx.canvas.removeEventListener('mousedown', mouse_down, false);
				gfx.canvas.addEventListener('mousedown', mouse_down, false);
				gfx.canvas.removeEventListener('mouseout', mouse_up, false);
				gfx.canvas.addEventListener('mouseout', mouse_up, false);
				gfx.canvas.removeEventListener('DOMMouseScroll', mouse_scroll, false);  
				gfx.canvas.addEventListener('DOMMouseScroll', mouse_scroll, false);  
				gfx.canvas.removeEventListener('mousewheel', mouse_scroll, false);
				gfx.canvas.addEventListener('mousewheel', mouse_scroll, false);
				var end = new Date();
				document.getElementById("parse").innerHTML = 'Parse time: '+(end.getTime()-start.getTime())+"ms";
				var diff = rrdgraph.end - rrdgraph.start;
				for (var i=0; i < gfx.canvas.stlen; i++) {
					if (gfx.canvas.stime[i] >= diff)  break;
				}
				if (i === gfx.canvas.stlen) gfx.canvas.stidx = gfx.canvas.stlen-1;
				else gfx.canvas.stidx = i;
				var start = new Date();
				try {
					rrdgraph.graph_paint();
				} catch (e) {
					alert(e+"\n"+e.stack);
				}
				var end = new Date();
				document.getElementById("draw").innerHTML = 'Draw time: '+(end.getTime()-start.getTime())+"ms";
			}
		</script>



<?php

 //list(,$cmd) = explode("png ", $graph_return['cmd']);

 $cmd = '
--start 1440149292 --end 1440235692 --width 1159 --height 300 -R normal
-c BACK#FFFFFF -c SHADEA#EEEEEE -c SHADEB#EEEEEE -c FONT#000000 -c CANVAS#FFFFFF -c GRID#a5a5a5 -c MGRID#FF9999 -c FRAME#EEEEEE -c ARROW#5e5e5e
--font-render-mode normal
-E
"COMMENT:Bits/s   Last       Avg      Max      95th\\n"
DEF:outoctets=/rrd/omega.memetic.org/port-2.rrd:OUTOCTETS:AVERAGE
DEF:inoctets=/rrd/omega.memetic.org/port-2.rrd:INOCTETS:AVERAGE
DEF:outoctets_max=/rrd/omega.memetic.org/port-2.rrd:OUTOCTETS:MAX
DEF:inoctets_max=/rrd/omega.memetic.org/port-2.rrd:INOCTETS:MAX
CDEF:alloctets=outoctets,inoctets,+
CDEF:wrongin=alloctets,UN,INF,UNKN,IF
CDEF:wrongout=wrongin,-1,*
"CDEF:octets=inoctets,outoctets,+"
CDEF:doutoctets=outoctets,-1,* CDEF:outbits=outoctets,8,* CDEF:outbits_max=outoctets_max,8,* CDEF:doutoctets_max=outoctets_max,-1,* CDEF:doutbits=doutoctets,8,* CDEF:doutbits_max=doutoctets_max,8,* CDEF:inbits=inoctets,8,* CDEF:inbits_max=inoctets_max,8,* 
"VDEF:totout=outoctets,TOTAL"
"VDEF:totin=inoctets,TOTAL"
"VDEF:tot=octets,TOTAL"
VDEF:95thin=inbits,95,PERCENT VDEF:95thout=outbits,95,PERCENT VDEF:d95thout=doutbits,5,PERCENT
"AREA:inbits#92B73F"
"LINE1.25:inbits#4A8328:In " "GPRINT:inbits:LAST:%6.2lf%s" "GPRINT:inbits:AVERAGE:%6.2lf%s" "GPRINT:inbits_max:MAX:%6.2lf%s" "GPRINT:95thin:%6.2lf%s\\n" "AREA:doutbits#7075B8" "LINE1.25:doutbits#323B7C:Out"
GPRINT:outbits:LAST:%6.2lf%s GPRINT:outbits:AVERAGE:%6.2lf%s GPRINT:outbits_max:MAX:%6.2lf%s "GPRINT:95thout:%6.2lf%s\\n"
"GPRINT:tot:Total %6.2lf%s"
"GPRINT:totin:(In %6.2lf%s"
"GPRINT:totout:Out %6.2lf%s)\\l"
LINE1:95thin#aa0000
LINE1:d95thout#aa0000';

 $cmd = str_replace("/mnt/ramdisk/observium_dev/", "rrd/", $cmd);
 $cmd = str_replace("'", '"', $cmd);
?>

<textarea id="cmdline" rows="10" cols="120" style="width: 800px"><?php echo $cmd; ?></textarea>

<canvas id="canvas"></canvas>

<p id="parse"></p>
<p id="draw"></p>

<script>javascript:draw();</script>

<?php
*/

/// End options navbar

  echo generate_graph_js_state($graph_array);

  //r($graph_array);


  echo generate_box_open();
  echo generate_graph_tag($graph_array);
  echo generate_box_close();

  if (!empty($graph_return['descr']))
  {
    echo generate_box_open(array('title' => 'Description', 'padding' => TRUE));
    echo($graph_return['descr']);
    echo generate_box_close();
  }

  #print_vars($graph_return);

  if (isset($vars['showcommand']))
  {
    echo generate_box_open(array('title' => 'Performance &amp; Output', 'padding' => TRUE));
    echo("RRDTool Output: " . escape_html($graph_return['output'])."<br />
          RRDtool Runtime: ".number_format($graph_return['runtime'], 3)."s |
          Total time: "     .number_format($graph_return['total'], 3)."s");
    echo generate_box_close();
    
    echo generate_box_open(array('title' => 'RRDTool Command', 'padding' => TRUE));
    echo escape_html($graph_return['command']);
    echo generate_box_close();
    
    echo generate_box_open(array('title' => 'RRDTool Files Used', 'padding' => TRUE));
    if (is_array($graph_return['rrds']))
    {
      foreach ($graph_return['rrds'] as $rrd)
      {
        echo "$rrd <br />";
      }
    } else {
        echo "No RRD information returned. This may be because the graph module doesn't yet return this data. <br />";
    }
    echo generate_box_close();
  }

// EOF
