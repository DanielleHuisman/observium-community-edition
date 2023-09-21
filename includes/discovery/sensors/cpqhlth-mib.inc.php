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

// Power Supplies

$oids = snmpwalk_cache_oid($device, 'cpqHeFltTolPwrSupply', [], 'CPQHLTH-MIB');

foreach ($oids as $index => $entry) {
    if (in_array($entry['cpqHeFltTolPowerSupplyPresent'], ['absent', 'other'])) {
        continue;
    }

    $descr = "PSU " . $entry['cpqHeFltTolPowerSupplyBay'];

    $oid      = ".1.3.6.1.4.1.232.6.2.9.3.1.7.$index";
    $oid_name = 'cpqHeFltTolPowerSupplyCapacityUsed';
    $value    = $entry['cpqHeFltTolPowerSupplyCapacityUsed'];
    $options  = ['limit_high' => $entry['cpqHeFltTolPowerSupplyCapacityMaximum']];

    if ($entry['cpqHeFltTolPowerSupplyCapacityMaximum'] != 0) {
        $options['rename_rrd'] = "cpqhlth-cpqHeFltTolPwrSupply.$index";
        discover_sensor_ng($device, 'power', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $value, $options);
    }

    $oid   = ".1.3.6.1.4.1.232.6.2.9.3.1.4.$index";
    $value = $entry['cpqHeFltTolPowerSupplyCondition'];

    discover_status_ng($device, $mib, 'cpqHeFltTolPowerSupplyCondition', $oid, $index, 'cpqhlth-state', $descr . ' Status', $value, ['entPhysicalClass' => 'powersupply']);

    $oid   = ".1.3.6.1.4.1.232.6.2.9.3.1.18.$index";
    $value = $entry['cpqHeFltTolPowerSupplyErrorCondition'];

    discover_status_ng($device, $mib, 'cpqHeFltTolPowerSupplyErrorCondition', $oid, $index, 'cpqHeFltTolPowerSupplyErrorCondition', $descr . ' Condition', $value, ['entPhysicalClass' => 'powersupply']);

}

// Temperatures

$oids = snmpwalk_cache_oid($device, 'CpqHeTemperatureEntry', [], 'CPQHLTH-MIB');

$descPatterns = ['/Cpu/', '/PowerSupply/', '/IoBoard/i'];
$descReplace  = ['CPU', 'PSU', 'IO Board'];
$descCount    = ['CPU' => 1, 'PSU' => 1, 'IO Board' => 1];

foreach ($oids as $index => $entry) {
    if ($entry['cpqHeTemperatureThreshold'] > 0) {
        $descr = ucfirst($entry['cpqHeTemperatureLocale']);

        if ($descr === 'System' || $descr === 'Memory') {
            continue;
        }
        if ($descr === 'Cpu' || $descr === 'PowerSupply') {
            $descr = preg_replace($descPatterns, $descReplace, $descr);
            $descr = $descr . ' ' . $descCount[$descr]++;
        }

        $oid      = ".1.3.6.1.4.1.232.6.2.6.8.1.4.$index";
        $oid_name = 'cpqHeTemperatureCelsius';
        $value    = $entry['cpqHeTemperatureCelsius'];
        $options  = ['limit_high' => $entry['cpqHeTemperatureThreshold']];

        $options['rename_rrd'] = "cpqhlth-CpqHeTemperatureEntry.$index";
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $value, $options);
    }
}

// Memory Modules

// CPQHLTH-MIB::cpqHeResMem2ModuleHwLocation.0 = STRING: "PROC  1 DIMM  1 "
// CPQHLTH-MIB::cpqHeResMem2ModuleStatus.0 = INTEGER: good(4)
// CPQHLTH-MIB::cpqHeResMem2ModuleStatus.1 = INTEGER: notPresent(2)
// .1.3.6.1.4.1.232.6.2.14.13.1.19.0 = INTEGER: good(4)
// CPQHLTH-MIB::cpqHeResMem2ModuleCondition.1 = INTEGER: ok(2)

$oids = snmpwalk_cache_oid($device, 'cpqHeResMem2ModuleStatus', [], 'CPQHLTH-MIB');
if (snmp_status()) {
    $oids = snmpwalk_cache_oid($device, 'cpqHeResMem2ModuleHwLocation', $oids, 'CPQHLTH-MIB');
    $oids = snmpwalk_cache_oid($device, 'cpqHeResMem2ModuleType', $oids, 'CPQHLTH-MIB');
    $oids = snmpwalk_cache_oid($device, 'cpqHeResMem2ModuleFrequency', $oids, 'CPQHLTH-MIB');
    $oids = snmpwalk_cache_oid($device, 'cpqHeResMem2ModulePartNo', $oids, 'CPQHLTH-MIB');
    $oids = snmpwalk_cache_oid($device, 'cpqHeResMem2ModuleSize', $oids, 'CPQHLTH-MIB');
    $oids = snmpwalk_cache_oid($device, 'cpqHeResMem2ModuleCondition', $oids, 'CPQHLTH-MIB');
}

foreach ($oids as $index => $entry) {
    if (isset($entry['cpqHeResMem2ModuleStatus']) && $entry['cpqHeResMem2ModuleStatus'] != 'notPresent') {
        if (empty($entry['cpqHeResMem2ModuleHwLocation'])) {
            $cpqHeResMem2ModuleType = [
                // other(1),
                // board(2),
                // cpqSingleWidthModule(3),
                // cpqDoubleWidthModule(4),
                'simm'        => 'SIMM',
                'pcmcia'      => 'PCMCIA',
                // compaq-specific(7),
                'dimm'        => 'DIMM',
                // smallOutlineDimm(9),
                'rimm'        => 'RIMM',
                'srimm'       => 'SRIMM',
                'fb-dimm'     => 'FB-DIMM',
                'dimmddr'     => 'DIMM DDR',
                'dimmddr2'    => 'DIMM DDR2',
                'dimmddr3'    => 'DIMM DDR3',
                'dimmfbd2'    => 'DIMM FBD2',
                'fb-dimmddr2' => 'FB-DIMM DDR2',
                'fb-dimmddr3' => 'FB-DIMM DDR3',
                'dimmddr4'    => 'DIMM DDR4',
                // hpe-specific(20)
            ];

            if (isset($cpqHeResMem2ModuleType[$entry['cpqHeResMem2ModuleType']])) {
                $descr = $cpqHeResMem2ModuleType[$entry['cpqHeResMem2ModuleType']];
            } else {
                $descr = 'DIMM';
            }
            $descr .= ' ' . $index;

        } else {
            $descr = $entry['cpqHeResMem2ModuleHwLocation'];
        }

        $addition = [];
        if (!empty($entry['cpqHeResMem2ModuleSize'])) {
            $addition[] = format_bi($entry['cpqHeResMem2ModuleSize'] * 1024) . 'b';
        }
        if ($entry['cpqHeResMem2ModuleFrequency'] > 0) {
            $addition[] = $entry['cpqHeResMem2ModuleFrequency'] . 'MHz';
        }
        if (!empty($entry['cpqHeResMem2ModulePartNo'])) {
            $addition[] = trim($entry['cpqHeResMem2ModulePartNo']);
        }

        if ($addition) {
            $descr .= ' (' . implode(', ', $addition) . ')';
        }

        $oid    = ".1.3.6.1.4.1.232.6.2.14.13.1.19." . $index;
        $status = $entry['cpqHeResMem2ModuleStatus'];

        discover_status_ng($device, $mib, 'cpqHeResMem2ModuleStatus', $oid, $index, 'cpqHeResMem2ModuleStatus', $descr . ' Status', $status, ['entPhysicalClass' => 'memory']);

        $oid    = ".1.3.6.1.4.1.232.6.2.14.13.1.20." . $index;
        $status = $entry['cpqHeResMem2ModuleCondition'];
        discover_status_ng($device, $mib, 'cpqHeResMem2ModuleCondition', $oid, $index, 'cpqHeResMem2ModuleCondition', $descr . ' Condition', $status, ['entPhysicalClass' => 'memory']);
    }
}

unset($oids);

// EOF
