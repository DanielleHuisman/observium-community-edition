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

$pws = snmpwalk_cache_oid($device, "pwID", [], 'PW-STD-MIB');
if ($GLOBALS['snmp_status'] === FALSE) {
    return;
}

$pws = snmpwalk_cache_oid($device, "pwRowStatus", $pws, 'PW-STD-MIB');
$pws = snmpwalk_cache_oid($device, "pwName", $pws, 'PW-STD-MIB');
$pws = snmpwalk_cache_oid($device, "pwType", $pws, 'PW-STD-MIB');
$pws = snmpwalk_cache_oid($device, "pwDescr", $pws, 'PW-STD-MIB');
$pws = snmpwalk_cache_oid($device, "pwPsnType", $pws, 'PW-STD-MIB');
$pws = snmpwalk_cache_oid($device, "pwPeerAddrType", $pws, 'PW-STD-MIB');
$pws = snmpwalk_cache_oid($device, "pwPeerAddr", $pws, 'PW-STD-MIB', NULL, OBS_SNMP_ALL_HEX);
$pws = snmpwalk_cache_oid($device, "pwOutboundLabel", $pws, 'PW-STD-MIB');
$pws = snmpwalk_cache_oid($device, "pwInboundLabel", $pws, 'PW-STD-MIB');
$pws = snmpwalk_cache_oid($device, "pwRemoteIfString", $pws, 'PW-STD-MIB');

// For MPLS pseudowires
$pws = snmpwalk_cache_oid($device, "pwMplsLocalLdpID", $pws, "PW-MPLS-STD-MIB");
$pws = snmpwalk_cache_oid($device, "pwMplsPeerLdpID", $pws, "PW-MPLS-STD-MIB");
//echo("PWS_WALK: ".count($pws)."\n"); var_dump($pws);

$peer_where = generate_query_values_and($device['device_id'], 'device_id', '!='); // Additional filter for exclude self IPs
foreach ($pws as $pw_id => $pw) {
    $peer_addr_type = $pw['pwPeerAddrType'];
    if ($peer_addr_type == "ipv4" || $peer_addr_type == "ipv6") {
        $peer_addr = hex2ip($pw['pwPeerAddr']);
    }
    if (!get_ip_version($peer_addr) && $pw['pwMplsPeerLdpID']) {
        // Sometime return wrong peer addr (not hex string):
        // pwPeerAddr.8 = "\\<h&"
        $peer_addr = preg_replace('/:\d+$/', '', $pw['pwMplsPeerLdpID']);
    }
    if (get_ip_version($peer_addr)) {
        $peer_rdns = gethostbyaddr6($peer_addr); // PTR name

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
        $peer_addr = ''; // Unset peer address
        print_debug("Not found correct peer address. See snmpwalk for 'pwPeerAddr' and 'pwMplsPeerLdpID'.");
    }
    if (empty($remote_device)) {
        $remote_device = ['NULL'];
    }

    // Clean some entries on Extreme devices, ie:
    // pwName.10001 = V_AKN_POP001....................
    // pwDescr.10001 = ................................
    // pwRemoteIfString.10001 = ................................
    $pw['pwName']           = rtrim($pw['pwName'], ". \t\n\r\0\x0B");
    $pw['pwDescr']          = rtrim($pw['pwDescr'], ". \t\n\r\0\x0B");
    $pw['pwRemoteIfString'] = rtrim($pw['pwRemoteIfString'], ". \t\n\r\0\x0B");

    $if_id = dbFetchCell('SELECT `port_id` FROM `ports` WHERE (`ifDescr` = ? OR `ifName` = ?) AND `device_id` = ? LIMIT 1;', [$pw['pwName'], $pw['pwName'], $device['device_id']]);
    if (!is_numeric($if_id) && strpos($pw['pwName'], '_')) {
        // IOS-XR some time use '_' instead '/'. http://jira.observium.org/browse/OBSERVIUM-246
        // pwName.3221225526 = TenGigE0_3_0_6.438
        // ifDescr.84 = TenGigE0/3/0/6.438
        $if_id = dbFetchCell('SELECT `port_id` FROM `ports` WHERE `ifDescr` = ? AND `device_id` = ? LIMIT 1;', [str_replace('_', '/', $pw['pwName']), $device['device_id']]);
    }
    if (!is_numeric($if_id) && strpos($pw['pwMplsLocalLdpID'], ':')) {
        // Last (know) way for detect local port by MPLS LocalLdpID,
        // because IOS-XR some time use Remote IP instead ifDescr in pwName
        // pwName.3221225473 = STRING: 82.209.169.153,3055
        // pwMplsLocalLdpID.3221225473 = STRING: 82.209.169.129:0
        [$local_addr] = explode(':', $pw['pwMplsLocalLdpID']);
        $local_where = generate_query_values_and($device['device_id'], 'device_id'); // Filter by self IPs
        if ($ids = get_entity_ids_ip_by_network('port', $local_addr, $local_where)) {
            $if_id = $ids[0];
        }
    }

    $pws_new = [
      'device_id'        => $device['device_id'],
      'mib'              => 'PW-STD-MIB',
      'port_id'          => $if_id,
      'peer_device_id'   => $remote_device,
      'peer_addr'        => $peer_addr,
      'peer_rdns'        => $peer_rdns,
      'pwIndex'          => $pw_id,
      'pwType'           => $pw['pwType'],
      'pwID'             => $pw['pwID'],
      'pwOutboundLabel'  => $pw['pwOutboundLabel'],
      'pwInboundLabel'   => $pw['pwInboundLabel'],
      //'pwMplsPeerLdpID'  => $pw['pwMplsPeerLdpID'],
      'pwPsnType'        => $pw['pwPsnType'],
      //'pwLocalIfMtu'     => $pw['pwLocalIfMtu'],
      //'pwRemoteIfMtu'    => $pw['pwRemoteIfMtu'],
      'pwDescr'          => $pw['pwDescr'],
      'pwRemoteIfString' => $pw['pwRemoteIfString'],
      'pwRowStatus'      => $pw['pwRowStatus'],
    ];

    if (!empty($pws_cache['pws_db']['PW-STD-MIB'][$pw_id])) {
        $pws_old       = $pws_cache['pws_db']['PW-STD-MIB'][$pw_id];
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

    $valid['pseudowires']['PW-STD-MIB'][$pseudowire_id] = $pseudowire_id;
}

// Clean
unset($pws, $pw, $update_array, $remote_device);

// EOF
