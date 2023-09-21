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

if (!empty($agent_data['app']['crashplan'])) {
    $app_id = discover_app($device, 'crashplan');

    $crashplan_data = json_decode($agent_data['app']['crashplan']['server'], TRUE);

    if (is_array($crashplan_data['data']['servers'])) {
        # [serverName] => crashplan.luciad.com
        # [totalBytes] => 16995951050752
        # [usedBytes] => 16322661449728
        # [usedPercentage] => 96
        # [freeBytes] => 673289601024
        # [freePercentage] => 4
        # [coldBytes] => 3762904182328
        # [coldPercentageOfUsed] => 23
        # [coldPercentageOfTotal] => 22
        # [archiveBytes] => 11678769817966
        # [selectedBytes] => 19313807393642
        # [remainingBytes] => 379281681813
        # [inboundBandwidth] => 53
        # [outboundBandwidth] => 67
        # [orgCount] => 1
        # [userCount] => 83
        # [computerCount] => 97
        # [onlineComputerCount] => 27
        # [backupSessionCount] => 0

        foreach ($crashplan_data['data']['servers'] as $crashplan_server) {
            $crashplan_servers[] = $crashplan_server['serverName'];

            update_application($app_id, $crashplan_data);

            rrdtool_update_ng($device, 'crashplan', [
              'totalBytes'          => $crashplan_server['totalBytes'],
              'usedBytes'           => $crashplan_server['usedBytes'],
              'usedPercentage'      => $crashplan_server['usedPercentage'],
              'freeBytes'           => $crashplan_server['freeBytes'],
              'freePercentage'      => $crashplan_server['freePercentage'],
              'coldBytes'           => $crashplan_server['coldBytes'],
              'coldPctOfUsed'       => $crashplan_server['coldPercentageOfUsed'],
              'coldPctOfTotal'      => $crashplan_server['coldPercentageOfTotal'],
              'archiveBytes'        => $crashplan_server['archiveBytes'],
              'selectedBytes'       => $crashplan_server['selectedBytes'],
              'remainingBytes'      => $crashplan_server['remainingBytes'],
              'inboundBandwidth'    => $crashplan_server['inboundBandwidth'],
              'outboundBandwidth'   => $crashplan_server['outboundBandwidth'],
              'orgCount'            => $crashplan_server['orgCount'],
              'userCount'           => $crashplan_server['userCount'],
              'computerCount'       => $crashplan_server['computerCount'],
              'onlineComputerCount' => $crashplan_server['onlineComputerCount'],
              'backupSessionCount'  => $crashplan_server['backupSessionCount'],
            ],                $crashplan_server['serverName']);
        }

        # Set list of servers as device attribute so we can use it in the web interface
        set_dev_attrib($device, 'crashplan_servers', json_encode($crashplan_servers));
    }
}

// EOF
