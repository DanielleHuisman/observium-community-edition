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

if (device_permitted($device)) {

  // Only show aggregate graph if we have access to the entire device.

  $graph_title = nicecase($vars['metric']);
  $graph_array['type'] = "device_".$vars['metric'];
  $graph_array['device'] = $device['device_id'];
  $graph_array['legend'] = 'no';

  $box_args = array('title' => $graph_title,
                    'header-border' => TRUE,
                    );

  echo generate_box_open($box_args); 

  print_graph_row($graph_array);
  
  echo generate_box_close(); 

}

print_mempool_table($vars);

// EOF
