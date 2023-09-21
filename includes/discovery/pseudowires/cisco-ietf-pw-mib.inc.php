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

$mib = "CISCO-IETF-PW-MIB";

echo("$mib ");

$pws = snmpwalk_cache_oid($device, "cpwVcID", [], $mib, mib_dirs('cisco'));
if ($GLOBALS['snmp_status'] === FALSE) {
    return;
}

$pws = snmpwalk_cache_oid($device, "cpwVcRowStatus", $pws, $mib);
$pws = snmpwalk_cache_oid($device, "cpwVcName", $pws, $mib);
$pws = snmpwalk_cache_oid($device, "cpwVcType", $pws, $mib);
$pws = snmpwalk_cache_oid($device, "cpwVcDescr", $pws, $mib);
$pws = snmpwalk_cache_oid($device, "cpwVcPsnType", $pws, $mib);
$pws = snmpwalk_cache_oid($device, "cpwVcPeerAddrType", $pws, $mib);
$pws = snmpwalk_cache_oid($device, "cpwVcPeerAddr", $pws, $mib, NULL, OBS_SNMP_ALL_HEX);
$pws = snmpwalk_cache_oid($device, "cpwVcOutboundVcLabel", $pws, $mib);
$pws = snmpwalk_cache_oid($device, "cpwVcInboundVcLabel", $pws, $mib);
$pws = snmpwalk_cache_oid($device, "cpwVcRemoteIfString", $pws, $mib);

// For MPLS pseudowires
$pws = snmpwalk_cache_oid($device, "cpwVcMplsLocalLdpID", $pws, "CISCO-IETF-PW-MPLS-MIB");
$pws = snmpwalk_cache_oid($device, "cpwVcMplsPeerLdpID", $pws, "CISCO-IETF-PW-MPLS-MIB");
//echo("PWS_WALK: ".count($pws)."\n"); var_dump($pws);

$peer_where = generate_query_values_and($device['device_id'], 'device_id', '!='); // Additional filter for exclude self IPs
foreach ($pws as $pw_id => $pw) {
    $peer_addr      = hex2ip($pw['cpwVcPeerAddr']);
    $peer_addr_type = $pw['cpwVcPeerAddrType'];

    if (!get_ip_version($peer_addr) && $pw['cpwVcMplsPeerLdpID']) {
        // Sometime return wrong peer addr (not hex string):
        // cpwVcPeerAddr.8 = "\\<h&"
        $peer_addr = preg_replace('/:\d+$/', '', $pw['cpwVcMplsPeerLdpID']);
    }

    $peer_addr_version = get_ip_version($peer_addr);
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
        $peer_addr = ''; // Unset peer address
        print_debug("Not found correct peer address. See snmpwalk for 'cpwVcPeerAddr' and 'cpwVcMplsPeerLdpID'.");
    }
    if (empty($remote_device)) {
        $remote_device = ['NULL'];
    }

    $if_id = dbFetchCell('SELECT `port_id` FROM `ports` WHERE `ifDescr` = ? AND `device_id` = ? LIMIT 1;', [$pw['cpwVcName'], $device['device_id']]);
    if (!is_numeric($if_id) && strpos($pw['cpwVcName'], '_')) {
        // IOS-XR some time use '_' instead '/'. http://jira.observium.org/browse/OBSERVIUM-246
        // cpwVcName.3221225526 = TenGigE0_3_0_6.438
        // ifDescr.84 = TenGigE0/3/0/6.438
        $if_id = dbFetchCell('SELECT `port_id` FROM `ports` WHERE `ifDescr` = ? AND `device_id` = ? LIMIT 1;', [str_replace('_', '/', $pw['cpwVcName']), $device['device_id']]);
    }
    if (!is_numeric($if_id) && strpos($pw['cpwVcMplsLocalLdpID'], ':')) {
        // Last (know) way for detect local port by MPLS LocalLdpID,
        // because IOS-XR some time use Remote IP instead ifDescr in cpwVcName
        // cpwVcName.3221225473 = STRING: 82.209.169.153,3055
        // cpwVcMplsLocalLdpID.3221225473 = STRING: 82.209.169.129:0
        [$local_addr] = explode(':', $pw['cpwVcMplsLocalLdpID']);
        $local_where = generate_query_values_and($device['device_id'], 'device_id'); // Filter by self IPs
        if ($ids = get_entity_ids_ip_by_network('port', $local_addr, $local_where)) {
            $if_id = $ids[0];
        }
    }

    // Note, Cisco experimental 'cpwVc' oid prefix converted to 'pw' prefix as in rfc PW-STD-MIB
    $pws_new = [
      'device_id'        => $device['device_id'],
      'mib'              => $mib,
      'port_id'          => $if_id,
      'peer_device_id'   => $remote_device,
      'peer_addr'        => $peer_addr,
      'peer_rdns'        => $peer_rdns,
      'pwIndex'          => $pw_id,
      'pwType'           => $pw['cpwVcType'],
      'pwID'             => $pw['cpwVcID'],
      'pwOutboundLabel'  => $pw['cpwVcOutboundVcLabel'],
      'pwInboundLabel'   => $pw['cpwVcInboundVcLabel'],
      //'pwMplsPeerLdpID'  => $pw['cpwVcMplsPeerLdpID'],
      'pwPsnType'        => $pw['cpwVcPsnType'],
      //'pwLocalIfMtu'     => $pw['cpwVcLocalIfMtu'],
      //'pwRemoteIfMtu'    => $pw['cpwVcRemoteIfMtu'],
      'pwDescr'          => $pw['cpwVcDescr'],
      'pwRemoteIfString' => $pw['cpwVcRemoteIfString'],
      'pwRowStatus'      => $pw['cpwVcRowStatus'],
    ];
    if (!empty($pws_cache['pws_db'][$mib][$pw_id])) {
        $pws_old       = $pws_cache['pws_db'][$mib][$pw_id];
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

    $valid['pseudowires'][$mib][$pseudowire_id] = $pseudowire_id;
}

// Clean
unset($pws, $pw, $update_array, $remote_device);

// EOF
