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

if (!empty($agent_data['app']['dovecot'])) {
    $app_id = discover_app($device, 'dovecot');

    [, , $num_logins, $num_cmds, $num_connected_sessions, $auth_successes, $auth_master_successes, $auth_failures, $auth_db_tempfails, $auth_cache_hits, $auth_cache_misses, $user_cpu, $sys_cpu, $clock_time, $min_faults, $maj_faults, $vol_cs, $invol_cs, $disk_input, $disk_output, $read_count, $read_bytes, $write_count, $write_bytes, $mail_lookup_path, $mail_lookup_attr, $mail_read_count, $mail_read_bytes, $mail_cache_hits] = explode("\n", $agent_data['app']['dovecot']);

    update_application($app_id, [
      'num_logins'          => $num_logins,
      'num_cmds'            => $num_cmds,
      'num_connected_sess'  => $num_connected_sessions,
      'auth_successes'      => $auth_successes,
      'auth_master_success' => $auth_master_successes,
      'auth_failures'       => $auth_failures,
      'auth_db_tempfails'   => $auth_db_tempfails,
      'auth_cache_hits'     => $auth_cache_hits,
      'auth_cache_misses'   => $auth_cache_misses,
      'user_cpu'            => $user_cpu,
      'sys_cpu'             => $sys_cpu,
      'clock_time'          => $clock_time,
      'min_faults'          => $min_faults,
      'maj_faults'          => $maj_faults,
      'vol_cs'              => $vol_cs,
      'invol_cs'            => $invol_cs,
      'disk_input'          => $disk_input,
      'disk_output'         => $disk_output,
      'read_count'          => $read_count,
      'read_bytes'          => $read_bytes,
      'write_count'         => $write_count,
      'write_bytes'         => $write_bytes,
      'mail_lookup_path'    => $mail_lookup_path,
      'mail_lookup_attr'    => $mail_lookup_attr,
      'mail_read_count'     => $mail_read_count,
      'mail_read_bytes'     => $mail_read_bytes,
      'mail_cache_hits'     => $mail_cache_hits
    ]);

    rrdtool_update_ng($device, 'dovecot', [
      'num_logins'          => $num_logins,
      'num_cmds'            => $num_cmds,
      'num_connected_sess'  => $num_connected_sessions,
      'auth_successes'      => $auth_successes,
      'auth_master_success' => $auth_master_successes,
      'auth_failures'       => $auth_failures,
      'auth_db_tempfails'   => $auth_db_tempfails,
      'auth_cache_hits'     => $auth_cache_hits,
      'auth_cache_misses'   => $auth_cache_misses,
      'user_cpu'            => $user_cpu,
      'sys_cpu'             => $sys_cpu,
      'clock_time'          => $clock_time,
      'min_faults'          => $min_faults,
      'maj_faults'          => $maj_faults,
      'vol_cs'              => $vol_cs,
      'invol_cs'            => $invol_cs,
      'disk_input'          => $disk_input,
      'disk_output'         => $disk_output,
      'read_count'          => $read_count,
      'read_bytes'          => $read_bytes,
      'write_count'         => $write_count,
      'write_bytes'         => $write_bytes,
      'mail_lookup_path'    => $mail_lookup_path,
      'mail_lookup_attr'    => $mail_lookup_attr,
      'mail_read_count'     => $mail_read_count,
      'mail_read_bytes'     => $mail_read_bytes,
      'mail_cache_hits'     => $mail_cache_hits,
    ],                $app_id);
}

// EOF
