<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Not sure why we check for VTP, but this data comes from that MIB, so... (I think just validate that here data exist)
$vtpversion = snmp_get_oid($device, 'vtpVersion.0', 'CISCO-VTP-MIB', NULL, OBS_SNMP_ALL_NUMERIC);

switch ($vtpversion) {
    case 'none':
    case '1':
    case '2':
    case '3':
    case 'one':
    case 'two':
    case 'three':
        // FIXME - can have multiple VTP domains.
        $vtpdomains = snmpwalk_cache_oid($device, 'vlanManagementDomains', [], 'CISCO-VTP-MIB');
        if (snmp_status()) {
            //$vtpvlans = snmpwalk_cache_twopart_oid($device, 'vtpVlanEntry', array(), 'CISCO-VTP-MIB');
            $vtpvlans = snmpwalk_cache_twopart_oid($device, 'vtpVlanState', [], 'CISCO-VTP-MIB');
            $vtpvlans = snmpwalk_cache_twopart_oid($device, 'vtpVlanType', $vtpvlans, 'CISCO-VTP-MIB');
            $vtpvlans = snmpwalk_cache_twopart_oid($device, 'vtpVlanName', $vtpvlans, 'CISCO-VTP-MIB');
            $vtpvlans = snmpwalk_cache_twopart_oid($device, 'vtpVlanMtu', $vtpvlans, 'CISCO-VTP-MIB');
            $vtpvlans = snmpwalk_cache_twopart_oid($device, 'vtpVlanIfIndex', $vtpvlans, 'CISCO-VTP-MIB');
        }

        foreach ($vtpdomains as $vtp_domain_index => $vtpdomain) {
            // Skip disabled vtp domains
            if (in_array($vtpdomain['managementDomainRowStatus'], [ 'notInService', 'notReady', 'destroy' ])) {
                continue;
            }

            if ($vtpdomain['managementDomainName']) {
                echo("(Domain $vtp_domain_index " . $vtpdomain['managementDomainName'] . ")");
            } else {
                echo("(Domain $vtp_domain_index" . ")");
            }

            foreach ($vtpvlans[$vtp_domain_index] as $vlan_id => $vlan) {
                // Skip extra entries with unknown state
                if (!isset($vlan['vtpVlanState'], $vlan['vtpVlanType'])) {
                    continue;
                }

                // Vlan context exist validated below
                $vlan_array = [
                    'ifIndex'      => $vlan['vtpVlanIfIndex'],
                    'vlan_domain'  => $vtp_domain_index,
                    'vlan_vlan'    => $vlan_id,
                    'vlan_name'    => $vlan['vtpVlanName'],
                    'vlan_mtu'     => $vlan['vtpVlanMtu'],
                    'vlan_type'    => $vlan['vtpVlanType'],
                    'vlan_status'  => $vlan['vtpVlanState'],
                    'vlan_context' => 0
                ];
                $discovery_vlans[$vtp_domain_index][$vlan_id] = $vlan_array;

            }
        }
        break;
}

// Check if per port vlans (with contexts) supported by Q-BRIDGE-MIB
$check_ports_vlans = isset($config['os'][$device['os']]['snmp']['virtual']) &&
                     $config['os'][$device['os']]['snmp']['virtual'];
if ($check_ports_vlans && is_device_mib($device, 'Q-BRIDGE-MIB')) {
    // This shit only seems to work on Cisco (probably only IOS/IOS-XE and NX-OS)
    // But don't worry, walking do only if vlans previously found

    $ios_version = explode('(', $device['version'])[0];

    if (!safe_empty($device['snmp_context'])) {
        // Already configured snmp context
        print_warning("WARNING: Device already configured with SNMP context, polling ports vlans not possible.");
        $check_ports_vlans = FALSE;
    } elseif ($device['snmp_version'] === 'v3' && $device['os'] === "ios" && ($ios_version * 10) <= 121) {
        // vlan context not worked on Cisco IOS <= 12.1 (SNMPv3)
        print_error("ERROR: For VLAN context to work on this device please use SNMP v2/v1 for this device (or upgrade IOS).");
        $check_ports_vlans = FALSE;
    }
}

if ($check_ports_vlans && safe_count($discovery_vlans)) { // Per port vlans walking allowed (see above)
    // Fetch first domain index
    $vtp_domain_index = array_key_first($discovery_vlans);

    $vlans = array_keys($discovery_vlans[$vtp_domain_index]);
    shuffle($vlans); // Shuffle vlans, prevent vlan1 be first
    foreach ($vlans as $vlan_id) {
        /* Per port vlans */

        // /usr/bin/snmpbulkwalk -v2c -c kglk5g3l454@988  -OQUs  -m BRIDGE-MIB -M /opt/observium/mibs/ udp:sw2.ahf:161 dot1dStpPortEntry
        // /usr/bin/snmpbulkwalk -v2c -c kglk5g3l454@988  -OQUs  -m BRIDGE-MIB -M /opt/observium/mibs/ udp:sw2.ahf:161 dot1dBasePortEntry

        // FIXME - do this only when vlan type == ethernet?
        // Cisco IOS Vlans:
        // 0, 4095   For system use only. You cannot see or use these VLANs.
        // 1002-1005 Cisco defaults for FDDI and Token Ring. You cannot delete VLANs 1002-1005
        if (is_numeric($vlan_id) &&
            $vlan_id != 4095 && ($vlan_id < 1002 || $vlan_id > 1005)) { // Ignore reserved VLAN IDs
            $vlan_data = [];

            // Vlan specific context
            if ($device['snmp_version'] === 'v3') {
                $context = 'vlan-' . $vlan_id;
            } else {
                $context = $vlan_id;
            }

            $context_valid = snmp_virtual_exist($device, $context);
            if ($context_valid) {
                $device_context = $device;
                // Add vlan context for snmp auth
                $device_context['snmp_context'] = $context;
                // Set retries to 1 for speedup walking
                //$device_context['snmp_retries'] = 1;

                $vlan_data = snmpwalk_cache_oid($device_context, "dot1dBasePortIfIndex", $vlan_data, "BRIDGE-MIB");
                if (snmp_status()) {
                    // FIXME. disable STP by default?
                    $vlan_data = snmpwalk_cache_oid($device_context, "dot1dStpPortEntry", $vlan_data, "BRIDGE-MIB:Q-BRIDGE-MIB");
                    //$vlan_data = snmpwalk_cache_oid($device_context, "dot1dStpPortPriority", $vlan_data, "BRIDGE-MIB:Q-BRIDGE-MIB");
                    //$vlan_data = snmpwalk_cache_oid($device_context, "dot1dStpPortState", $vlan_data, "BRIDGE-MIB:Q-BRIDGE-MIB");
                    //$vlan_data = snmpwalk_cache_oid($device_context, "dot1dStpPortPathCost", $vlan_data, "BRIDGE-MIB:Q-BRIDGE-MIB");
                }
                unset($device_context);

                // At this point vlan context is validated and exist
                $discovery_vlans[$vtp_domain_index][$vlan_id]['vlan_context'] = 1;
            } elseif ($context_valid === FALSE &&
                !snmp_virtual_exist($device, $context, 'dot1dBaseNumPorts')) {
                // dot1dBaseBridgeAddress.0 = 0:0:0:0:0:0
                // dot1dBaseNumPorts.0 = 0
                // dot1dBaseType.0 = transparent-only

                print_debug("VLANs context failed, loop stopped.");

                // Stop loop for other vlans
                break;
            }

            if ($vlan_data) {
                print_debug(str_pad("dot1d id", 10) . str_pad("ifIndex", 10) . str_pad("Port Name", 25) .
                            str_pad("Priority", 10) . str_pad("State", 15) . str_pad("Cost", 10));
            }

            foreach ($vlan_data as $vlan_port_id => $vlan_port) {
                $ifIndex = $vlan_port['dot1dBasePortIfIndex'];
                $discovery_ports_vlans[$ifIndex][$vlan_id] = [
                    'vlan'     => $vlan_id,
                    // FIXME. move STP to separate table
                    'baseport' => $vlan_port_id,
                    'priority' => $vlan_port['dot1dStpPortPriority'],
                    'state'    => $vlan_port['dot1dStpPortState'],
                    'cost'     => $vlan_port['dot1dStpPortPathCost']
                ];
            }
        } else {
            unset($module_stats[$vlan_id]);
        }
        /* End per port vlans */
    }
}

echo(PHP_EOL);

// EOF
