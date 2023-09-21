<?php

/**
 * Observium Network Management and Monitoring System
 *
 * @package        observium
 * @subpackage     graphs
 * @author         Sander Steffann <sander@steffann.nl>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$colours      = "mixed";
$nototal      = (($width < 224) ? 1 : 0);
$unit_text    = "Count";
$rrd_filename = str_replace('%index%', $app['app_id'], $config['rrd_types']['dhcpkit-stats']['file']);
$rrd_filename = get_rrd_path($device, $rrd_filename);
$simple_rrd   = TRUE;

$array = [
  'msg_in_solicit'      => ['descr' => 'Solicit', 'invert' => FALSE],
  'msg_in_request'      => ['descr' => 'Request', 'invert' => FALSE],
  'msg_in_confirm'      => ['descr' => 'Confirm', 'invert' => FALSE],
  'msg_in_renew'        => ['descr' => 'Renew', 'invert' => FALSE],
  'msg_in_rebind'       => ['descr' => 'Rebind', 'invert' => FALSE],
  'msg_in_release'      => ['descr' => 'Release', 'invert' => FALSE],
  'msg_in_decline'      => ['descr' => 'Decline', 'invert' => FALSE],
  'msg_in_inf_req'      => ['descr' => 'Inform. Request', 'invert' => FALSE],
  'msg_out_advertise'   => ['descr' => 'Advertise', 'invert' => TRUE],
  'msg_out_reply'       => ['descr' => 'Reply', 'invert' => TRUE],
  'msg_out_reconfigure' => ['descr' => 'Reconfigure', 'invert' => TRUE],
];
$i     = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['invert']   = $data['invert'];
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

include($config['html_dir'] . "/includes/graphs/generic_multi.inc.php");

// EOF
