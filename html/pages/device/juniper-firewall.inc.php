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

echo generate_box_open();

if (isset($attribs['juniper-firewall-mib'])) {

  echo '<table class="table table-striped-two table-condensed">';

  if ($filters = safe_json_decode($attribs['juniper-firewall-mib'])) {
    ksort($filters);
  }
  foreach ($filters as $filter => $counters) {

    ksort($counters);

    foreach ($counters AS $counter => $types)
    {

      foreach($types as $type => $data)
      {
        echo '<tr><td><h3><i class="sprite-qos"></i> ' . $filter . ' | <i class="sprite-counter"></i>' . $counter . ' | '.$type.'</h3></td></tr>';

        echo '<tr>';
        echo '<td>';
        echo '<h4>Packets</h4>';

        $graph_array = array('type'    => 'device_juniper-firewall-pkts',
                             'device'  => $device['device_id'],
                             'filter'  => safename($filter),
                             'counter' => safename($counter),
                             'counter_type' => safename($type)
        );

        print_graph_row($graph_array);

        echo '<h4>Bytes</h4>';

        $graph_array = array('type'    => 'device_juniper-firewall-bits',
                             'device'  => $device['device_id'],
                             'filter'  => safename($filter),
                             'counter' => safename($counter),
                             'counter_type' => safename($type)
        );

        print_graph_row($graph_array);

        echo '</td></tr>';
      }
    }
  }

  echo '</table>';
}