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

if (!empty($agent_data['app']['mongodb'])) {
    $app_id = discover_app($device, 'mongodb');

    $mongodb_data = json_decode($agent_data['app']['mongodb'], TRUE);
    $keys         = array_keys($mongodb_data);
    //some values are reported in pair of 2 separated by a "|"
    $command = explode("|", $mongodb_data[$keys[0]]['command']);
    $queue   = explode("|", $mongodb_data[$keys[0]]['qrw']);
    $clients = explode("|", $mongodb_data[$keys[0]]['arw']);
    //if the operation is replicated a * appears in the value
    $insert = str_replace("*", "", $mongodb_data[$keys[0]]['insert']);

    update_application($app_id, [
      'insert'         => $insert,
      'query'          => $mongodb_data[$keys[0]]['query'],
      'update'         => $mongodb_data[$keys[0]]['update'],
      'delete'         => $mongodb_data[$keys[0]]['delete'],
      'getmore'        => $mongodb_data[$keys[0]]['getmore'],
      'command_local'  => $command[0],
      'command_replic' => $command[1],
      'dirty'          => $mongodb_data[$keys[0]]['dirty'],
      'used'           => $mongodb_data[$keys[0]]['used'],
      'flushes'        => $mongodb_data[$keys[0]]['flushes'],
      'vsize'          => $mongodb_data[$keys[0]]['vsize'],
      'res'            => $mongodb_data[$keys[0]]['res'],
      'queue_read'     => $queue[0],
      'queue_write'    => $queue[1],
      'clients_read'   => $clients[0],
      'clients_write'  => $clients[1],
      'net_in'         => $mongodb_data[$keys[0]]['net_in'],
      'net_out'        => $mongodb_data[$keys[0]]['net_out'],
      'conn'           => $mongodb_data[$keys[0]]['conn']
    ]);

    rrdtool_update_ng($device, 'mongodb', [
      'insert'         => $mongodb_data[$keys[0]]['insert'],
      'query'          => $mongodb_data[$keys[0]]['query'],
      'update'         => $mongodb_data[$keys[0]]['update'],
      'delete'         => $mongodb_data[$keys[0]]['delete'],
      'getmore'        => $mongodb_data[$keys[0]]['getmore'],
      'command_local'  => $command[0],
      'command_replic' => $command[1],
      'dirty'          => $mongodb_data[$keys[0]]['dirty'],
      'used'           => $mongodb_data[$keys[0]]['used'],
      'flushes'        => $mongodb_data[$keys[0]]['flushes'],
      'vsize'          => $mongodb_data[$keys[0]]['vsize'],
      'res'            => $mongodb_data[$keys[0]]['res'],
      'queue_read'     => $queue[0],
      'queue_write'    => $queue[1],
      'clients_read'   => $clients[0],
      'clients_write'  => $clients[1],
      'net_in'         => $mongodb_data[$keys[0]]['net_in'],
      'net_out'        => $mongodb_data[$keys[0]]['net_out'],
      'conn'           => $mongodb_data[$keys[0]]['conn']
    ],                $app_id);
}

// EOF
