<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$flags = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;

$pws = snmpwalk_cache_threepart_oid($device, "jnxVpnPwRowStatus", [], 'JUNIPER-VPN-MIB', NULL, $flags);
if ($GLOBALS['snmp_status'] === FALSE) {
    return;
}

$pws = snmpwalk_cache_threepart_oid($device, 'jnxVpnPwAssociatedInterface', $pws, 'JUNIPER-VPN-MIB', NULL, $flags);
$pws = snmpwalk_cache_threepart_oid($device, 'jnxVpnPwLocalSiteId', $pws, 'JUNIPER-VPN-MIB', NULL, $flags);                                  // pwID
$pws = snmpwalk_cache_threepart_oid($device, 'jnxVpnPwTunnelName', $pws, 'JUNIPER-VPN-MIB', NULL, $flags);                                   // pwDescr
$pws = snmpwalk_cache_threepart_oid($device, 'jnxVpnPwTunnelType', $pws, 'JUNIPER-VPN-MIB', NULL, $flags);                                   // pwPsnType
$pws = snmpwalk_cache_threepart_oid($device, 'jnxVpnRemotePeIdAddrType', $pws, 'JUNIPER-VPN-MIB', NULL, $flags);                             // pwPeerAddrType
$pws = snmpwalk_cache_threepart_oid($device, 'jnxVpnRemotePeIdAddress', $pws, 'JUNIPER-VPN-MIB', NULL, OBS_SNMP_ALL_HEX ^ OBS_QUOTES_STRIP); // pwPeerAddr
//$pws = snmpwalk_cache_oid($device, 'pwLocalIfMtu',     $pws, 'JUNIPER-VPN-MIB');
//$pws = snmpwalk_cache_oid($device, 'pwRemoteIfMtu',    $pws, 'JUNIPER-VPN-MIB');
$pws = snmpwalk_cache_threepart_oid($device, 'jnxVpnPwRemoteSiteId', $pws, 'JUNIPER-VPN-MIB', NULL, $flags);                                 // pwMplsPeerLdpID

if (OBS_DEBUG > 1) {
    echo('PWS_WALK: ' . count($pws) . "\n");
    print_vars($pws);
}

$peer_where = generate_query_values_and($device['device_id'], 'device_id', '!='); // Additional filter for exclude self IPs
foreach ($pws as $pw_type => $entry) {
    foreach ($entry as $pw_name => $entry2) {
        foreach ($entry2 as $pw_ifIndex => $pw) {
            //if (strlen($pw['jnxVpnPwRowStatus']) && $pw['jnxVpnPwRowStatus'] != 'active') { continue; } // Skip inactive (active, notinService, notReady, createAndGo, createAndWait, destroy)

            // Get full index
            $pw_index = snmp_translate('jnxVpnPwRowStatus.' . $pw_type . '."' . $pw_name . '".' . $pw_ifIndex, 'JUNIPER-VPN-MIB');
            $pw_index = str_replace('.1.3.6.1.4.1.2636.3.26.1.4.1.4.', '', $pw_index);

            $peer_addr         = hex2ip($pw['jnxVpnRemotePeIdAddress']);
            $peer_addr_version = get_ip_version($peer_addr);
            $peer_addr_type    = $pw['jnxVpnRemotePeIdAddrType'];

            if ($peer_addr_version) {
                $peer_addr_type = 'ipv' . $peer_addr_version; // Override address type, because snmp sometime return incorrect
                $peer_rdns      = gethostbyaddr6($peer_addr); // PTR name

                // Fetch all devices with peer IP and filter by UP
                if ($ids = get_entity_ids_ip_by_network('device', $peer_addr, $peer_where)) {
                    $remote_device = $ids[0];
                    if (count($ids) > 1) {
                        // If multiple same IPs found, get first NOT disabled or down
                        foreach ($ids as $id) {
                            $tmp_device = device_by_id_cache($id);
                            if (!$tmp_device['disabled'] && $tmp_device['status']) {
                                $remote_device = $id;
                                break;
                            }
                        }
                    }
                }
            } else {
                $peer_rdns = '';
                $peer_addr = ''; // Unset peer address
                print_debug("Not found correct peer address. See snmpwalk for 'jnxVpnRemotePeIdAddress'.");
            }
            if (empty($remote_device)) {
                $remote_device = ['NULL'];
            }

            if (!is_numeric($pw['jnxVpnPwAssociatedInterface']) || $pw['jnxVpnPwAssociatedInterface'] <= 0) {
                $pw['jnxVpnPwAssociatedInterface'] = $pw_ifIndex;
            }
            $port = get_port_by_index_cache($device, $pw['jnxVpnPwAssociatedInterface']);

            if (is_numeric($port['port_id'])) {
                $if_id = $port['port_id'];
            } else {
                $if_id = get_port_id_by_ifDescr($device['device_id'], $pw_name);
            }

            $pws_new = [
              'device_id'       => $device['device_id'],
              'mib'             => 'JUNIPER-VPN-MIB',
              'port_id'         => $if_id,
              'peer_device_id'  => $remote_device,
              'peer_addr'       => $peer_addr,
              'peer_rdns'       => $peer_rdns,
              'pwIndex'         => $pw_index,
              'pwType'          => $pw_type,
              'pwID'            => $pw['jnxVpnPwLocalSiteId'],
              'pwOutboundLabel' => $pw['jnxVpnPwLocalSiteId'],
              'pwInboundLabel'  => $pw['jnxVpnPwRemoteSiteId'],
              'pwPsnType'       => ($pw['jnxVpnPwTunnelType'] ? $pw['jnxVpnPwTunnelType'] : 'unknown'),
              //'pwLocalIfMtu'     => $pw['pwLocalIfMtu'],
              //'pwRemoteIfMtu'    => $pw['pwRemoteIfMtu'],
              'pwDescr'         => ($pw['jnxVpnPwTunnelName'] ? $pw['jnxVpnPwTunnelName'] : $pw_name),
              //'pwRemoteIfString' => '',
              'pwRowStatus'     => $pw['jnxVpnPwRowStatus'],
            ];
            if (OBS_DEBUG > 1) {
                print_vars($pws_new);
            }

            if (!empty($pws_cache['pws_db']['JUNIPER-VPN-MIB'][$pw_index])) {
                $pws_old       = $pws_cache['pws_db']['JUNIPER-VPN-MIB'][$pw_index];
                $pseudowire_id = $pws_old['pseudowire_id'];
                if (empty($pws_old['peer_device_id'])) {
                    $pws_old['peer_device_id'] = ['NULL'];
                }

                $update_array = [];
                //var_dump(array_keys($pws_new));
                foreach (array_keys($pws_new) as $column) {
                    if ($pws_new[$column] != $pws_old[$column]) {
                        $update_array[$column] = $pws_new[$column];
                    }
                }
                if (count($update_array) > 0) {
                    dbUpdate($update_array, 'pseudowires', '`pseudowire_id` = ?', [$pseudowire_id]);
                    $GLOBALS['module_stats'][$module]['updated']++; //echo("U");
                } else {
                    $GLOBALS['module_stats'][$module]['unchanged']++; //echo(".");
                }

            } else {
                $pseudowire_id = dbInsert($pws_new, 'pseudowires');
                $GLOBALS['module_stats'][$module]['added']++; //echo("+");
            }

            $valid['pseudowires']['JUNIPER-VPN-MIB'][$pseudowire_id] = $pseudowire_id;
        }
    }
}

// Clean
unset($pws, $pw, $update_array, $remote_device, $flags);

// EOF
