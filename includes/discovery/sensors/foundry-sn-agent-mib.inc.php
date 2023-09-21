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

// Temperature sensors
$cache['fnsnagent'] = snmpwalk_cache_oid($device, 'snAgentTempEntry', [], 'FOUNDRY-SN-AGENT-MIB');
print_debug_vars($cache['fnsnagent']);

foreach ($cache['fnsnagent'] as $index => $entry) {
    if (!isset($entry['snAgentTempValue']) || !is_numeric($entry['snAgentTempValue']) || $entry['snAgentTempValue'] == 0) {
        continue;
    }
    $descr = str_replace(['temperature', 'sensor', 'Line module', 'Switch Fabric module', 'management module'],
                         ['', 'Sensor', 'Slot', 'Fabric', 'Mgmt Module'],
                         $entry['snAgentTempSensorDescr']);
    $descr = preg_replace('!\s+!', ' ', trim($descr));
    [$slot,] = explode('.', $index);
    if ($slot > 1) {
        $descr .= ' Slot ' . $slot;
    }

    $oid_name = 'snAgentTempValue';
    $oid_num  = ".1.3.6.1.4.1.1991.1.1.2.13.1.1.4.$index";
    $value    = $entry[$oid_name];
    $scale    = 0.5;

    discover_sensor_ng($device, 'temperature', $mib, 'snAgentTempValue', $oid_num, $index, 'ironware', $descr, $scale, $value, ['rename_rrd' => "ironware-$index"]);
}

// Module statuses
$cache['fnsnagent'] = snmpwalk_cache_oid($device, 'snAgentBrdModuleStatus', [], 'FOUNDRY-SN-AGENT-MIB');
if ($GLOBALS['snmp_status']) {
    $cache['fnsnagent'] = snmpwalk_cache_oid($device, 'snAgentBrdMainBrdDescription', $cache['fnsnagent'], 'FOUNDRY-SN-AGENT-MIB');
    $cache['fnsnagent'] = snmpwalk_cache_oid($device, 'snAgentBrdRedundantStatus', $cache['fnsnagent'], 'FOUNDRY-SN-AGENT-MIB');
}
print_debug_vars($cache['fnsnagent']);

foreach ($cache['fnsnagent'] as $index => $entry) {
    $name = trim(str_ireplace('Module', '', $entry['snAgentBrdMainBrdDescription']));

    // Module status
    $descr    = 'Module ' . $index . ': ' . $name;
    $oid_name = 'snAgentBrdModuleStatus';
    $oid_num  = ".1.3.6.1.4.1.1991.1.1.2.2.1.1.12.$index";
    $type     = 'snAgentBrdModuleStatus';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);

    // Module Redundant status
    $descr    = 'Redundant ' . $index . ': ' . $name;
    $oid_name = 'snAgentBrdRedundantStatus';
    $oid_num  = ".1.3.6.1.4.1.1991.1.1.2.2.1.1.13.$index";
    $type     = 'snAgentBrdRedundantStatus';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
}

// State sensors
$cache['fnsnagent'] = [];
$stackable          = FALSE;

// Power Suplies

// Stackable Switches
foreach (['snChasPwrSupply2Table'] as $table) {
    echo("$table ");
    $cache['fnsnagent'] = snmpwalk_cache_oid($device, $table, $cache['fnsnagent'], 'FOUNDRY-SN-AGENT-MIB:FOUNDRY-SN-ROOT-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
}
print_debug_vars($cache['fnsnagent']);

foreach ($cache['fnsnagent'] as $index => $entry) {
    $descr = empty($entry['snChasPwrSupply2Description']) ? "Power Supply $index" : $entry['snChasPwrSupply2Description'];
    if ($entry['snChasPwrSupply2Unit']) {
        $descr .= ' Unit ' . $entry['snChasPwrSupply2Unit'];
    }
    $oid   = ".1.3.6.1.4.1.1991.1.1.1.2.2.1.4.$index";
    $value = $entry['snChasPwrSupply2OperStatus'];
    discover_status($device, $oid, "snChasPwrSupply2OperStatus.$index", 'foundry-sn-agent-oper-state', $descr, $value, ['entPhysicalClass' => 'powerSupply']);
    $stackable = TRUE;
}

// Chassis and Non Stackable Switches
if (!$stackable) {
    $cache['fnsnagent'] = [];

    foreach (['snChasPwrSupplyTable'] as $table) {
        echo("$table ");
        $cache['fnsnagent'] = snmpwalk_cache_oid($device, $table, $cache['fnsnagent'], 'FOUNDRY-SN-AGENT-MIB:FOUNDRY-SN-ROOT-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    }
    print_debug_vars($cache['fnsnagent']);

    foreach ($cache['fnsnagent'] as $index => $entry) {
        $descr = empty($entry['snChasPwrSupplyDescription']) ? "Power Supply $index" : $entry['snChasPwrSupplyDescription'];
        $oid   = ".1.3.6.1.4.1.1991.1.1.1.2.1.1.3.$index";
        $value = $entry['snChasPwrSupplyOperStatus'];
        discover_status($device, $oid, "snChasPwrSupplyOperStatus.$index", 'foundry-sn-agent-oper-state', $descr, $value, ['entPhysicalClass' => 'powerSupply']);
    }
}

// Fans

$cache['fnsnagent'] = [];
$stackable          = FALSE;

// Stackable Switches
foreach (['snChasFan2Table'] as $table) {
    echo("$table ");
    $cache['fnsnagent'] = snmpwalk_cache_oid($device, $table, $cache['fnsnagent'], 'FOUNDRY-SN-AGENT-MIB:FOUNDRY-SN-ROOT-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
}
print_debug_vars($cache['fnsnagent']);

foreach ($cache['fnsnagent'] as $index => $entry) {
    $descr = empty($entry['snChasFan2Description']) ? "Fan $index" : $entry['snChasFan2Description'];
    if ($entry['snChasFan2Unit']) {
        $descr .= ' Unit ' . $entry['snChasFan2Unit'];
    }
    $oid   = ".1.3.6.1.4.1.1991.1.1.1.3.2.1.4.$index";
    $value = $entry['snChasFan2OperStatus'];
    discover_status($device, $oid, "snChasFan2OperStatus.$index", 'foundry-sn-agent-oper-state', $descr, $value, ['entPhysicalClass' => 'fan']);
    $stackable = TRUE;
}

// Chassis and Non Stackable Switches
if (!$stackable) {
    $cache['fnsnagent'] = [];

    foreach (['snChasFanEntry'] as $table) {
        echo("$table ");
        $cache['fnsnagent'] = snmpwalk_cache_oid($device, $table, $cache['fnsnagent'], 'FOUNDRY-SN-AGENT-MIB:FOUNDRY-SN-ROOT-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    }
    print_debug_vars($cache['fnsnagent']);

    foreach ($cache['fnsnagent'] as $index => $entry) {
        $descr = empty($entry['snChasFanDescription']) ? "Fan $index" : $entry['snChasFanDescription'];
        $oid   = ".1.3.6.1.4.1.1991.1.1.1.3.1.1.3.$index";
        $value = $entry['snChasFanOperStatus'];
        discover_status($device, $oid, "snChasFanOperStatus.$index", 'foundry-sn-agent-oper-state', $descr, $value, ['entPhysicalClass' => 'fan']);
    }
}

unset($stackable, $cache['fnsnagent']);

// EOF
