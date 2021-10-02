<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

/**
 * @var array $config
 * @var array $device
 * @global array $valid
 * @global string $module
 */

$discovery_vrf = [];
$vrf_contexts = [];

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

  $include_dir   = 'includes/discovery/vrf';
  //$include_order = 'default'; // Use MIBs from default os definitions by first!
  include($config['install_dir']."/includes/include-dir-mib.inc.php");

  print_debug_vars($discovery_vrf);

  foreach ($discovery_vrf as $vrf_name => $entry) {
    discover_vrf($device, $entry);
  }

  // Clean removed VRFs
  print_debug_vars($valid['vrf']);
  $where = '`device_id` = ?';
  $where .= generate_query_values(array_keys((array)$valid['vrf']), 'vrf_name', '!=');
  if ($count = dbFetchCell("SELECT COUNT(*) FROM `vrfs` WHERE $where", [ $device['device_id'] ])) {
    $GLOBALS['module_stats'][$module]['deleted'] = $count;
    dbDelete('vrfs', $where, [ $device['device_id'] ]);
  }

  // Clean Removed ports
  print_debug_vars($valid['vrf-ports']);
  $vrf_ports = [];
  foreach ($valid['vrf-ports'] as $vrf_name => $entry) {
    $vrf_ports = array_merge($vrf_ports, array_keys($entry));
  }
  print_debug_vars($vrf_ports);
  $where = '`device_id` = ? AND `ifVrf` IS NOT NULL';
  $where .= generate_query_values($vrf_ports, 'port_id', '!=');
  if ($count = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE $where", [ $device['device_id'] ])) {
    //$GLOBALS['module_stats'][$module]['deleted'] = $count;
    dbUpdate([ 'ifVrf' => [ 'NULL' ] ], 'ports', $where, [ $device['device_id'] ]);
  }
}
/* End VRF discovery */

// Currently only on Nexus and IOX-XR devices (probably some other Cisco), which not support common MPLS MIBs
/* Start detect SNMP contexts for VRFs */
if (empty($device['snmp_context']) && // Device not already with context
    isset($config['os'][$device['os']]['snmp']['context']) && $config['os'][$device['os']]['snmp']['context']) { // Context permitted for os

  //$vrf_contexts = [];

  if (is_device_mib($device, 'CISCO-CONTEXT-MAPPING-MIB')) {
    // For enable context mapping on Nexus device:
    // snmp-server context <context_name> vrf <vrf_name>

    // CISCO-CONTEXT-MAPPING-MIB::cContextMappingVrfName."1" = STRING:
    // CISCO-CONTEXT-MAPPING-MIB::cContextMappingVrfName."test" = STRING: management
    // CISCO-CONTEXT-MAPPING-MIB::cContextMappingVrfName."vlan-1" = STRING:
    // CISCO-CONTEXT-MAPPING-MIB::cContextMappingRowStatus."1" = INTEGER: active(1)
    // CISCO-CONTEXT-MAPPING-MIB::cContextMappingRowStatus."test" = INTEGER: active(1)
    // CISCO-CONTEXT-MAPPING-MIB::cContextMappingRowStatus."vlan-1" = INTEGER: active(1)
    $vrf_snmp_contexts = snmpwalk_cache_oid($device, 'cContextMappingVrfName',                   [], 'CISCO-CONTEXT-MAPPING-MIB');
    $vrf_snmp_contexts = snmpwalk_cache_oid($device, 'cContextMappingRowStatus', $vrf_snmp_contexts, 'CISCO-CONTEXT-MAPPING-MIB');

    foreach ($vrf_snmp_contexts as $snmp_context => $entry) {
      if ($entry['cContextMappingVrfName'] === '' ||
          (preg_match('/^(vlan\-)?(?<vlan>\d{1,4})$/', $snmp_context, $matches) &&
           $matches['vlan'] > 0 && $matches['vlan'] < 4096)) {
        continue;
      }

      if (snmp_context_exist($device, $snmp_context)) {
        // prevent adding unnecessary vrfs
        $vrf_contexts[$entry['cContextMappingVrfName']] = $snmp_context;
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
        continue;
      }

      if (snmp_context_exist($device, $snmp_context)) {
        // Not really VRF, not know how there VRFs, need unique SNMP Contexts
        $vrf_contexts[$snmp_context] = $snmp_context;
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

  if (safe_count($contexts_db) && empty($vrf_contexts)) {
    del_entity_attrib('device', $device, 'vrf_contexts');
    log_event("VRF SNMP contexts removed.", $device, 'device', $device['device_id']);
  } elseif (safe_count($update_array)) {
    set_entity_attrib('device', $device, 'vrf_contexts', safe_json_encode($vrf_contexts));
    $contexts_list = [];
    foreach ($update_array as $vrf_name => $context)
    {
      $contexts_list[] = "'$context' (VRF $vrf_name)";
    }
    log_event("VRF SNMP contexts found: " . implode(", ", $contexts_list), $device, 'device', $device['device_id']);
  } elseif (safe_count($delete_array)) {
    set_entity_attrib('device', $device, 'vrf_contexts', safe_json_encode($vrf_contexts));
    $contexts_list = [];
    foreach ($delete_array as $vrf_name => $context) {
      $contexts_list[] = "'$context' (VRF $vrf_name)";
    }
    log_event("VRF SNMP contexts removed: " . implode(", ", $contexts_list), $device, 'device', $device['device_id']);
  }
}
/* End detect SNMP contexts for VRFs */

unset($discovery_vrf, $vrf_ports, $where, $vrf_contexts);

// EOF
