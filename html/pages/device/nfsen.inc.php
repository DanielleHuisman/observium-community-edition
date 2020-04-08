<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage webui
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$datas = array(
  'Traffic' => 'nfsen_traffic',
  'Packets' => 'nfsen_packets',
  'Flows' => 'nfsen_flows'
);

foreach ($datas as $name => $type)
{
  $graph_title = nicecase($name);
  $graph_array['type'] = "device_".$type;
  $graph_array['device'] = $device['device_id'];
  #$graph_array['legend'] = no;

  $box_args = array('title' => $graph_title,
                    'header-border' => TRUE,
                    );

  echo generate_box_open($box_args);

  print_graph_row($graph_array);
  
  echo generate_box_close();
}

register_html_title("Netflow");

// EOF
