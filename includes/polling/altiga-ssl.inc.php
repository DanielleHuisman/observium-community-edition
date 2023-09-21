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

if ($device['os'] == 'asa' || $device['os'] == 'pix') {
    echo('ALTIGA-MIB SSL VPN Statistics' . PHP_EOL);

    $data_array = snmpwalk_cache_oid($device, $proto, [], 'ALTIGA-SSL-STATS-MIB');

    // FIXME move to graph definition based poll!
    if ($data_array[0]['alSslStatsTotalSessions']) {
        rrdtool_update_ng($device, 'altiga-ssl', [
          'TotalSessions'     => $data_array[0]['alSslStatsTotalSessions'],
          'ActiveSessions'    => $data_array[0]['alSslStatsActiveSessions'],
          'MaxSessions'       => $data_array[0]['alSslStatsMaxSessions'],
          'PreDecryptOctets'  => $data_array[0]['alSslStatsPreDecryptOctets'],
          'PostDecryptOctets' => $data_array[0]['alSslStatsPostDecryptOctets'],
          'PreEncryptOctets'  => $data_array[0]['alSslStatsPreEncryptOctets'],
          'PostEncryptOctets' => $data_array[0]['alSslStatsPostEncryptOctets'],
        ]);
    }

    unset($data_array);
}

// EOF
