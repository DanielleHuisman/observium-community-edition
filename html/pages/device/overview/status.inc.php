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

  $sql  = "SELECT * FROM `status`";
  //$sql .= " LEFT JOIN `status-state` USING(`status_id`)";
  $sql .= " WHERE `device_id` = ? AND `status_deleted` = 0 ORDER BY `entPhysicalClass` DESC, `status_descr`;";

  $status = dbFetchRows($sql, array($device['device_id']));

  if (count($status))
  {
    $box_args = array('title' => 'Status Indicators',
                      'url' => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => 'status')),
                      'icon' => $config['entities']['status']['icon'],
                      );
    echo generate_box_open($box_args);

    echo('<table class="table table-condensed table-striped">');
    foreach ($status as $status)
    {
      //$status['status_descr'] = truncate($status['status_descr'], 48, '');

      print_status_row($status, $vars);
    }

    echo("</table>");
    echo generate_box_close();
  }

// EOF
