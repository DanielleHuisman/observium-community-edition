<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

if (strlen($vars['id']))
{
  $sql = 'SELECT * FROM `wifi_aps_members` LEFT JOIN `wifi_aps` USING (`ap_name`) WHERE `ap_index_member` = ?';
  $ap_member = dbFetchRow($sql, [ $vars['id']]);
  //$ap_member = dbFetchRow("SELECT * FROM `wifi_aps`, `wifi_aps_members` WHERE  `wifi_aps`.`ap_name` = `wifi_aps_members`.`ap_name` AND `wifi_aps_members`.`wifi_ap_member_id` = ? ", array($vars['id']));
  if (is_numeric($ap_member['device_id']) && ($auth || device_permitted($ap_member['device_id'])))
  {
    $device = device_by_id_cache($ap_member['device_id']);

    $title  = generate_device_link($device);
    $title .= " :: WIFI - Accesspoint :: " . escape_html($ap_member['ap_name']);
    $rrd_filename = get_rrd_path($device, "lwappmember-" . $ap_member['ap_index_member']);
    $auth = TRUE;
  }
}

// EOF
