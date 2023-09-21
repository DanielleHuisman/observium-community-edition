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

#CISCO-VPDN-MGMT-MIB::cvpdnTunnelTotal.0 = Gauge32: 0 tunnels
#CISCO-VPDN-MGMT-MIB::cvpdnSessionTotal.0 = Gauge32: 0 users
#CISCO-VPDN-MGMT-MIB::cvpdnDeniedUsersTotal.0 = Counter32: 0 attempts
#CISCO-VPDN-MGMT-MIB::cvpdnSystemTunnelTotal.l2tp = Gauge32: 437 tunnels
#CISCO-VPDN-MGMT-MIB::cvpdnSystemSessionTotal.l2tp = Gauge32: 1029 sessions
#CISCO-VPDN-MGMT-MIB::cvpdnSystemDeniedUsersTotal.l2tp = Counter32: 0 attempts
#CISCO-VPDN-MGMT-MIB::cvpdnSystemClearSessions.0 = INTEGER: none(1)

// FIXME. Candidate for migrate to graphs module with table_collect()
// ^ will need to be able to set $graphs[], not sure this is possible yet?
if (is_device_mib($device, 'CISCO-VPDN-MGMT-MIB')) {
    $data = snmpwalk_cache_oid($device, "cvpdnSystemEntry", NULL, "CISCO-VPDN-MGMT-MIB");

    foreach ($data as $type => $vpdn) {
        if ($vpdn['cvpdnSystemTunnelTotal'] || $vpdn['cvpdnSystemSessionTotal']) {
            rrdtool_update_ng($device, 'cisco-vpdn', [
              'tunnels'  => $vpdn['cvpdnSystemTunnelTotal'],
              'sessions' => $vpdn['cvpdnSystemSessionTotal'],
              'denied'   => $vpdn['cvpdnSystemDeniedUsersTotal'],
            ],                $type);

            $graphs['vpdn_sessions_' . $type] = TRUE;
            $graphs['vpdn_tunnels_' . $type]  = TRUE;

            echo(" Cisco VPDN ($type) ");
        }
    }
    unset($data, $vpdn, $type);
}

// EOF
