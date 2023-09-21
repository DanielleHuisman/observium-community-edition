<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (is_numeric($vars['id'])) {
    $member = dbFetchRow("SELECT * FROM `lb_pool_members` AS I, `devices` AS D WHERE I.member_id = ? AND I.device_id = D.device_id", [$vars['id']]);
    $pool   = dbFetchRow("SELECT * FROM `lb_pools` WHERE `pool_name` = ?", [$member['pool_name']]);

    if (is_numeric($member['device_id']) && ($auth || device_permitted($member['device_id']))) {
        $device = device_by_id_cache($member['device_id']);

        $rrd_filename = get_rrd_path($device, "lb-pool-member-" . $member['member_name']);

        $title_array   = [];
        $title_array[] = ['text' => $device['hostname'], 'url' => generate_url(['page' => 'device', 'device' => $device['device_id']])];
        $title_array[] = ['text' => 'F5 Pools', 'url' => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'lb_pools'])];
        $title_array[] = ['text' => $member['pool_name'], 'url' => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'lb_pools', 'pool' => $pool['pool_id']])];
        $title_array[] = ['text' => 'Pool Members', 'url' => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'lb_pools', 'pool' => $pool['pool_id']])];
        $title_array[] = ['text' => $member['member_name'], 'url' => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'lb_pools', 'pool' => $pool['pool_id']])];

        $auth = TRUE;
    }
}

// EOF
 
