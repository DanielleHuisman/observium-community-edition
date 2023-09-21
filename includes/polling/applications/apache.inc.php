<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!empty($agent_data['app']['apache'])) {
    $app_id = discover_app($device, 'apache');

    [$total_access, $total_kbyte, $cpuload, $uptime, $reqpersec, $bytespersec, $bytesperreq, $busyworkers, $idleworkers,
     $score_wait, $score_start, $score_reading, $score_writing, $score_keepalive, $score_dns, $score_closing, $score_logging,
     $score_graceful, $score_idle, $score_open] = explode("\n", $agent_data['app']['apache']);

    update_application($app_id, [
      'access'       => $total_access,
      'kbyte'        => $total_kbyte,
      'cpu'          => $cpuload,
      'uptime'       => $uptime,
      'reqpersec'    => $reqpersec,
      'bytespersec'  => $bytespersec,
      'byesperreq'   => $bytesperreq,
      'busyworkers'  => $busyworkers,
      'idleworkers'  => $idleworkers,
      'sb_wait'      => $score_wait,
      'sb_start'     => $score_start,
      'sb_reading'   => $score_reading,
      'sb_writing'   => $score_writing,
      'sb_keepalive' => $score_keepalive,
      'sb_dns'       => $score_dns,
      'sb_closing'   => $score_closing,
      'sb_logging'   => $score_logging,
      'sb_graceful'  => $score_graceful,
      'sb_idle'      => $score_idle,
      'sb_open'      => $score_open,
    ]);

    rrdtool_update_ng($device, 'apache', [
      'access'       => $total_access,
      'kbyte'        => $total_kbyte,
      'cpu'          => $cpuload,
      'uptime'       => $uptime,
      'reqpersec'    => $reqpersec,
      'bytespersec'  => $bytespersec,
      'byesperreq'   => $bytesperreq,
      'busyworkers'  => $busyworkers,
      'idleworkers'  => $idleworkers,
      'sb_wait'      => $score_wait,
      'sb_start'     => $score_start,
      'sb_reading'   => $score_reading,
      'sb_writing'   => $score_writing,
      'sb_keepalive' => $score_keepalive,
      'sb_dns'       => $score_dns,
      'sb_closing'   => $score_closing,
      'sb_logging'   => $score_logging,
      'sb_graceful'  => $score_graceful,
      'sb_idle'      => $score_idle,
      'sb_open'      => $score_open,
    ],                $app_id);
}

// EOF
