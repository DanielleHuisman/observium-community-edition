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

/**
 * @var array     $config
 * @var array     $device
 * @global array  $valid
 * @global string $module
 */

$discovery_vrf = [];
$vrf_contexts  = [];

/* Start VRF discovery */
if (!$config['enable_vrfs']) {
    print_debug("VRFs disabled globally.");
    //return; // Do not exit for context discovery below
} else {

    /*
      There are 2 common MIBs for VPNs: MPLS-VPN-MIB (oldest) and MPLS-L3VPN-STD-MIB (newest)
      Unfortunately, there is no simple way to find out which one to use, unless we reference
      all the possible devices and check what they support.
      Therefore we start testing the MPLS-L3VPN-STD-MIB that is preferred if available.
    */

    $include_dir = 'includes/discovery/vrf';
    //$include_order = 'default'; // Use MIBs from default os definitions by first!
    include($config['install_dir'] . "/includes/include-dir-mib.inc.php");

    print_debug_vars($discovery_vrf);

    foreach ($discovery_vrf as $vrf_name => $entry) {
        discover_vrf($device, $entry);
    }

    // Clean removed VRFs
    print_debug_vars($valid['vrf']);
    $where = '`device_id` = ?';
    $where .= generate_query_values_and(array_keys((array)$valid['vrf']), 'vrf_name', '!=');
    if ($count = dbFetchCell("SELECT COUNT(*) FROM `vrfs` WHERE $where", [$device['device_id']])) {
        $GLOBALS['module_stats'][$module]['deleted'] = $count;
        dbDelete('vrfs', $where, [$device['device_id']]);
    }

    // Clean Removed ports
    print_debug_vars($valid['vrf-ports']);
    $vrf_ports = [];
    foreach ($valid['vrf-ports'] as $vrf_name => $entry) {
        $vrf_ports[] = array_keys($entry);
        //$vrf_ports = array_merge($vrf_ports, array_keys($entry));
    }
    $vrf_ports = array_merge([], ...$vrf_ports);
    print_debug_vars($vrf_ports);
    $where = '`device_id` = ? AND `ifVrf` IS NOT NULL';
    $where .= generate_query_values_and($vrf_ports, 'port_id', '!=');
    if ($count = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE $where", [$device['device_id']])) {
        //$GLOBALS['module_stats'][$module]['deleted'] = $count;
        dbUpdate(['ifVrf' => ['NULL']], 'ports', $where, [$device['device_id']]);
    }
}
/* End VRF discovery */

// Currently only on Nexus and IOX-XR devices (probably some other Cisco), which not support common MPLS MIBs
/* Start detect SNMP contexts for VRFs */
if (safe_empty($device['snmp_context']) && // Device not already with context
    isset($config['os'][$device['os']]['snmp']['virtual']) && $config['os'][$device['os']]['snmp']['virtual']) { // Context permitted for os

    /* Types of Virtual Router tables:
        VRF (Virtual Router Forwarder) - Cisco routers and many others
        Context                        - Cisco ASA
        VR (Virtual Routers)           - JunOS
        VDOM (Virtual Domain)          - Fortigate
        VSYS (Virtual Systems)         - Palo Alto
        NETNS (Network NameSpace)      - Linux
    */

    //$vrf_contexts = [];

    $vrf_type = 'VRF';
    if (is_device_mib($device, 'CISCO-CONTEXT-MAPPING-MIB')) {
        // For enable context mapping on Nexus device:
        // snmp-server context <context_name> vrf <vrf_name>

        // CISCO-CONTEXT-MAPPING-MIB::cContextMappingVrfName."1" = STRING:
        // CISCO-CONTEXT-MAPPING-MIB::cContextMappingVrfName."test" = STRING: management
        // CISCO-CONTEXT-MAPPING-MIB::cContextMappingVrfName."vlan-1" = STRING:
        // CISCO-CONTEXT-MAPPING-MIB::cContextMappingRowStatus."1" = INTEGER: active(1)
        // CISCO-CONTEXT-MAPPING-MIB::cContextMappingRowStatus."test" = INTEGER: active(1)
        // CISCO-CONTEXT-MAPPING-MIB::cContextMappingRowStatus."vlan-1" = INTEGER: active(1)
        $vrf_snmp_contexts = snmpwalk_cache_oid($device, 'cContextMappingVrfName', [], 'CISCO-CONTEXT-MAPPING-MIB');
        if (snmp_status()) {
            $vrf_snmp_contexts = snmpwalk_cache_oid($device, 'cContextMappingRowStatus', $vrf_snmp_contexts, 'CISCO-CONTEXT-MAPPING-MIB');

            print_debug("Discovery VRF contexts by CISCO-CONTEXT-MAPPING-MIB..");
            foreach ($vrf_snmp_contexts as $snmp_context => $entry) {
                if (preg_match('/^(vlan\-)?(?<vlan>\d{1,4})$/', $snmp_context, $matches) &&
                    $matches['vlan'] > 0 && $matches['vlan'] < 4096) {
                    // Skip Vlan contexts
                    continue;
                }
                if ($entry['cContextMappingVrfName'] === '') {
                    // NX-OS can return empty cContextMappingVrfName
                    $entry['cContextMappingVrfName'] = $snmp_context;
                }

                if (snmp_virtual_exist($device, $snmp_context)) {
                    // prevent adding unnecessary vrfs
                    $vrf_contexts[$entry['cContextMappingVrfName']] = $snmp_context;
                }
            }
        } elseif (!empty($discovery_vrf)) {
            // Another derp case, when NX-OS not really return context maps, but discover VRFs by MPLS-L3VPN-STD-MIB
            // https://jira.observium.org/browse/OBS-4401
            print_debug("Discovery VRF contexts by know MIBs..");
            foreach ($discovery_vrf as $vrf_name => $entry) {
                $vrf_name_l = strtolower($vrf_name); // just for compare
                if (preg_match('/^(vlan\-)?(?<vlan>\d{1,4})$/', $vrf_name_l, $matches) &&
                    $matches['vlan'] > 0 && $matches['vlan'] < 4096) {
                    // Skip Vlan contexts
                    continue;
                }
                if ($vrf_name_l === 'default') {
                    // Skip default
                    continue;
                }

                if (snmp_virtual_exist($device, $vrf_name)) {
                    // prevent adding unnecessary vrfs
                    $vrf_contexts[$vrf_name] = $vrf_name;
                }
            }
        }

    } elseif (is_device_mib($device, 'RBN-BIND-MIB')) {
        // RBN-BIND-MIB::rbnBindContext[0:0:ffff-1400-1002] = STRING: local
        // RBN-BIND-MIB::rbnBindContext[1:0:ffff-1800-1005] = STRING: iBGP
        //$vrf_snmp_contexts = snmpwalk_cache_oid($device, 'rbnBindContext',                  [], 'RBN-BIND-MIB', NULL, OBS_SNMP_ALL_TABLE);
        //$vrf_snmp_contexts = snmpwalk_cache_oid($device, 'rbnBindType',     $vrf_snmp_contexts, 'RBN-BIND-MIB', NULL, OBS_SNMP_ALL_TABLE);
        $vrf_snmp_contexts = snmpwalk_values($device, 'rbnBindContext', [], 'RBN-BIND-MIB');

        foreach (array_unique($vrf_snmp_contexts) as $snmp_context) {
            if ($snmp_context === '' || $snmp_context === 'local') { // local is just main table (without context)
                print_debug("SNMP context '$snmp_context' skipped.");
                continue;
            }

            if (snmp_virtual_exist($device, $snmp_context)) {
                // Not really VRF, not know how there VRFs, need unique SNMP Contexts
                $vrf_contexts[$snmp_context] = $snmp_context;
            }
        }
    } elseif (is_device_mib($device, 'FORTINET-FORTIGATE-MIB') && snmp_get_oid($device, 'fgVdEnabled.0', 'FORTINET-FORTIGATE-MIB') === 'enabled') {
        // FORTINET-FORTIGATE-MIB::fgVdNumber.0 = INTEGER: 2
        // FORTINET-FORTIGATE-MIB::fgVdMaxVdoms.0 = INTEGER: 10
        // FORTINET-FORTIGATE-MIB::fgVdEnabled.0 = INTEGER: enabled(2)
        // FORTINET-FORTIGATE-MIB::fgVdEntIndex.1 = INTEGER: 1
        // FORTINET-FORTIGATE-MIB::fgVdEntIndex.2 = INTEGER: 2
        // FORTINET-FORTIGATE-MIB::fgVdEntName.1 = STRING: root
        // FORTINET-FORTIGATE-MIB::fgVdEntName.2 = STRING: test
        // FORTINET-FORTIGATE-MIB::fgVdEntOpMode.1 = INTEGER: nat(1)
        // FORTINET-FORTIGATE-MIB::fgVdEntOpMode.2 = INTEGER: nat(1)
        // FORTINET-FORTIGATE-MIB::fgVdEntHaState.1 = INTEGER: standalone(3)
        // FORTINET-FORTIGATE-MIB::fgVdEntHaState.2 = INTEGER: standalone(3)
        // FORTINET-FORTIGATE-MIB::fgVdEntCpuUsage.1 = Gauge32: 22
        // FORTINET-FORTIGATE-MIB::fgVdEntCpuUsage.2 = Gauge32: 0
        // FORTINET-FORTIGATE-MIB::fgVdEntMemUsage.1 = Gauge32: 58
        // FORTINET-FORTIGATE-MIB::fgVdEntMemUsage.2 = Gauge32: 0
        // FORTINET-FORTIGATE-MIB::fgVdEntSesCount.1 = Gauge32: 217
        // FORTINET-FORTIGATE-MIB::fgVdEntSesCount.2 = Gauge32: 1
        // FORTINET-FORTIGATE-MIB::fgVdEntSesRate.1 = Gauge32: 0 Sessions Per Second
        // FORTINET-FORTIGATE-MIB::fgVdEntSesRate.2 = Gauge32: 0 Sessions Per Second

        $vrf_type          = 'VDOM';
        $vrf_snmp_contexts = snmpwalk_values($device, 'fgVdEntName', [], 'FORTINET-FORTIGATE-MIB');
        foreach ($vrf_snmp_contexts as $vdom) {
            if ($vdom === '' || $vdom === 'root') { // root is just main table
                print_debug("SNMP in VDOM '$vdom' skipped.");
                continue;
            }

            if (snmp_virtual_exist($device, $vdom)) {
                $vrf_contexts[$vdom] = $vdom;
            }
        }
    }
    print_debug_vars($vrf_snmp_contexts);
    print_debug_vars($vrf_contexts);

    $contexts_db = safe_json_decode(get_entity_attrib('device', $device, 'vrf_contexts'));
    print_debug_vars($contexts_db);

    $update_array = array_diff_assoc($vrf_contexts, (array)$contexts_db);
    print_debug_vars($update_array);
    $delete_array = array_diff_assoc((array)$contexts_db, $vrf_contexts);
    print_debug_vars($delete_array);

    if (safe_count($contexts_db) && safe_empty($vrf_contexts)) {
        del_entity_attrib('device', $device, 'vrf_contexts');
        log_event("SNMP in Virtual Routing removed.", $device, 'device', $device['device_id']);
    } elseif (safe_count($update_array)) {
        set_entity_attrib('device', $device, 'vrf_contexts', safe_json_encode($vrf_contexts));
        $contexts_list = [];
        foreach ($update_array as $vrf_name => $context) {
            $contexts_list[] = "'$context' ($vrf_type $vrf_name)";
        }
        log_event("SNMP in Virtual Routing found: " . implode(", ", $contexts_list), $device, 'device', $device['device_id']);
    } elseif (safe_count($delete_array)) {
        set_entity_attrib('device', $device, 'vrf_contexts', safe_json_encode($vrf_contexts));
        $contexts_list = [];
        foreach ($delete_array as $vrf_name => $context) {
            $contexts_list[] = "'$context' ($vrf_type $vrf_name)";
        }
        log_event("SNMP in Virtual Routing removed: " . implode(", ", $contexts_list), $device, 'device', $device['device_id']);
    }
}
/* End detect SNMP contexts for VRFs */

unset($discovery_vrf, $vrf_ports, $where, $vrf_contexts);

// EOF
