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

////// Global PSE Statistics

// pethMainPsePower.1 = Gauge32: 370 Watts
// pethMainPseOperStatus.1 = INTEGER: on(1)
// pethMainPseConsumptionPower.1 = Gauge32: 16 Watts
// pethMainPseUsageThreshold.1 = INTEGER: 80 %

$oids = snmpwalk_cache_oid($device, 'pethMainPseTable', [], 'POWER-ETHERNET-MIB');

foreach ($oids as $index => $entry) {
    $scale = 1;
    $descr = "PoE Group $index";
    $oid   = ".1.3.6.1.2.1.105.1.3.1.1.4.$index";
    $value = $entry['pethMainPseConsumptionPower'];

    $limits = ['limit_high' => $entry['pethMainPsePower'],];
    //'limit_low'       => 0 ]; // Hardcode 0 as lower limit. Low warning limit will be calculated.

    // Work around odd devices. 0 as threshold? Hah.
    // Juniper returns 'current usage in %' for this threshold, seriously guys. SNMP is hard.
    if ($entry['pethMainPseUsageThreshold'] > 0 && $entry['pethMainPseUsageThreshold'] < 100 &&
        $device['os'] !== 'junos') {
        $warning_threshold = $entry['pethMainPseUsageThreshold'] / 100;
    } else {
        $warning_threshold = 0.9; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
    }
    if ($limits['limit_high'] > 0) {
        $limits['limit_high_warn'] = $entry['pethMainPsePower'] * $warning_threshold;
    } else {
        unset($limits['limit_high']);
    }

    // If usage is >1000 and larger than the actual PSE power, this device is supplying data in mW.
    // Hello POWER-ETHERNET-MIB, this should be Watts obviously. Correct scale in this case.
    // If the device is not supplying power at discovery time, we will not know what the scale is and might get it wrong.
    // This will then only be corrected at the next discovery cycle, unfortunately.
    if (str_contains($device['os'], 'edgecore')) {
        // Edgecore incorrect scale for PoE Group
        // pethMainPsePower.1 = 780000
        // pethMainPseOperStatus.1 = on
        // pethMainPseConsumptionPower.1 = 4200
        // pethMainPseUsageThreshold.1 = 95
        $scale = 0.001;
        if (isset($limits['limit_high'])) {
            $limits['limit_high']      *= $scale;
            $limits['limit_high_warn'] *= $scale;
        }
    } elseif ($entry['pethMainPseConsumptionPower'] > 1000 && $entry['pethMainPseConsumptionPower'] > $entry['pethMainPsePower']) {
        $scale = 0.001;
        if ($entry['pethMainPseConsumptionPower'] > 1000000000 && is_device_mib($device, 'NETGEAR-POWER-ETHERNET-MIB')) {
            // Skip this sensor on netgear, I really don't know what in this metric here:
            // pethMainPsePower.1 = 180
            // pethMainPseConsumptionPower.1 = 1447362561
            // pethMainPseUsageThreshold.1 = 100
            $value = '';
            continue;
        }
    }

    if ($value != '') {
        $limits['rename_rrd'] = "power-ethernet-mib-pethMainPseConsumptionPower.$index";
        discover_sensor_ng($device, 'power', $mib, 'pethMainPseConsumptionPower', $oid, $index, 'power-ethernet-mib', $descr, $scale, $value, $limits);
    }

    /* Migrated to definition
    $descr   = "PoE Group $index";
    $oid     = ".1.3.6.1.2.1.105.1.3.1.1.3.$index";
    $value   = $entry['pethMainPseOperStatus'];

    if ($value != '')
    {
      discover_status($device, $oid, "pethMainPseOperStatus.$index", 'power-ethernet-mib-pse-state', $descr,  $value);
    }
    */
}

// Set warning if main peth table empty
if (safe_empty($warning_threshold)) {
    $warning_threshold = 0.9;
}

////// Per-port Statistics

if (is_device_mib($device, 'CISCO-POWER-ETHERNET-EXT-MIB') ||
    str_contains($device['os'], 'edgecore')) {
    // Cisco and Edgecore per-port statistics in own definitions
    return;
}

// pethPsePortAdminEnable.1.4 = INTEGER: true(1)
// pethPsePortPowerPairsControlAbility.1.4 = INTEGER: false(2)
// pethPsePortPowerPairs.1.4 = INTEGER: signal(1)
// pethPsePortDetectionStatus.1.4 = INTEGER: deliveringPower(3)
// pethPsePortPowerPriority.1.4 = INTEGER: low(3)
// pethPsePortMPSAbsentCounter.1.4 = Counter32: 0
// pethPsePortType.1.4 = STRING:
// pethPsePortPowerClassifications.1.4 = INTEGER: class1(2)
// pethPsePortInvalidSignatureCounter.1.4 = Counter32: 0
// pethPsePortPowerDeniedCounter.1.4 = Counter32: 0
// pethPsePortOverLoadCounter.1.4 = Counter32: 0
// pethPsePortShortCounter.1.4 = Counter32: 0

$oids = snmpwalk_cache_oid($device, 'pethPsePortTable', [], $mib);

if (is_device_mib($device, 'HH3C-POWER-ETH-EXT-MIB')) {
    // hh3cPsePortFaultDescription.1.4 = STRING:
    // hh3cPsePortPeakPower.1.4 = INTEGER: 2700
    // hh3cPsePortAveragePower.1.4 = INTEGER: 2400
    // hh3cPsePortCurrentPower.1.4 = INTEGER: 2400
    // hh3cPsePortPowerLimit.1.4 = INTEGER: 30000
    // hh3cPsePortProfileIndex.1.4 = INTEGER: 0

    $oids = snmpwalk_cache_oid($device, 'hh3cPsePortEntry', $oids, 'HH3C-POWER-ETH-EXT-MIB');
}

if (is_device_mib($device, 'ENTERASYS-POWER-ETHERNET-EXT-MIB')) {
    // etsysPsePortPowerLimit.1.7 = INTEGER: 32000 milliwatts
    // etsysPsePortPowerUsage.1.7 = Gauge32: 9200 milliwatts
    // etsysPsePortCapability.1.7 = BITS: 02 6
    // etsysPsePortCapabilitySelect.1.7 = INTEGER: ieee8023at(2)
    // etsysPsePortDetectionStatus.1.7 = INTEGER: deliveringPower(3)

    $oids = snmpwalk_cache_oid($device, 'etsysPsePortPowerManagementEntry', $oids, 'ENTERASYS-POWER-ETHERNET-EXT-MIB');
}

if (is_device_mib($device, 'HP-ICF-POE-MIB')) {
    // HP-ICF-POE-MIB::hpicfPoePethPsePortCurrent.1.2 = INTEGER: 37
    // HP-ICF-POE-MIB::hpicfPoePethPsePortVoltage.1.2 = INTEGER: 499
    // HP-ICF-POE-MIB::hpicfPoePethPsePortPower.1.2 = INTEGER: 1800
    // HP-ICF-POE-MIB::hpicfPoePethPsePortPowerAllocateBy.1.2 = INTEGER: usage(1)
    // HP-ICF-POE-MIB::hpicfPoePethPsePortPowerValue.1.2 = INTEGER: 17
    // HP-ICF-POE-MIB::hpicfPoePethPsePortLLDPDetect.1.2 = INTEGER: disabled(1)
    // HP-ICF-POE-MIB::hpicfPoePethPsePortPoePlusPowerValue.1.2 = INTEGER: 17

    $oids = snmpwalk_cache_oid($device, 'hpicfPoePethPsePortEntry', $oids, 'HP-ICF-POE-MIB');
}

if (is_device_mib($device, 'EXTREME-POE-MIB')) {
    // EXTREME-POE-MIB::extremePethPortOperatorLimit.1.1039 = INTEGER: 30000 Milliwatts
    // EXTREME-POE-MIB::extremePethPortReservedBudget.1.1039 = INTEGER: 0 Milliwatts
    // EXTREME-POE-MIB::extremePethPortViolationPrecedence.1.1039 = INTEGER: none(4)
    // EXTREME-POE-MIB::extremePethPortClearFault.1.1039 = INTEGER: clear(2)
    // EXTREME-POE-MIB::extremePethPortResetPower.1.1039 = INTEGER: clear(2)
    // EXTREME-POE-MIB::extremePethPortMeasuredPower.1.1039 = Gauge32: 4700 Milliwatts

    $oids = snmpwalk_cache_oid($device, 'extremePethPsePortEntry', $oids, 'EXTREME-POE-MIB');
}

if (is_device_mib($device, 'BAY-STACK-PETH-EXT-MIB')) {
    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtPowerLimit.1.1 = 32
    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtMeasuredVoltage.1.1 = 0
    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtMeasuredCurrent.1.1 = 0
    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtMeasuredPower.1.1 = 0
    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtPowerUpMode.1.1 = dot3at
    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtPowerPairs.1.1 = signal


    $oids = snmpwalk_cache_oid($device, 'bspePethPsePortExtEntry', $oids, 'BAY-STACK-PETH-EXT-MIB');
}

if (is_device_mib($device, 'ARUBAWIRED-POE-MIB')) {
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPowerAllocateBy.1.193 = INTEGER: usage(1)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPreStdDetect.1.193 = INTEGER: off(1)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortRpd.1.193 = INTEGER: off(1)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortCurrent.1.193 = INTEGER: 226 milliamperes
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortVoltage.1.193 = INTEGER: 547 deciVolts
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortReservedPower.1.193 = INTEGER: 13920 milliwatts
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPowerDrawn.1.193 = INTEGER: 12369 milliwatts
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortAveragePower.1.193 = INTEGER: 12451 milliwatts
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPeakPower.1.193 = INTEGER: 15270 milliwatts
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortOperStatus.1.193 = INTEGER: on(3)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPdSignature.1.193 = INTEGER: singleSignature(1)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPowerClassification.1.193 = INTEGER: class5(6)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPseAssignedClass.1.193 = INTEGER: class5(6)

    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPowerAllocateBy.1.523 = INTEGER: usage(1)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPreStdDetect.1.523 = INTEGER: off(1)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortRpd.1.523 = INTEGER: off(1)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortCurrent.1.523 = INTEGER: 0 milliamperes
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortVoltage.1.523 = INTEGER: 0 deciVolts
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortReservedPower.1.523 = INTEGER: 0 milliwatts
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPowerDrawn.1.523 = INTEGER: 0 milliwatts
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortAveragePower.1.523 = INTEGER: 0 milliwatts
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPeakPower.1.523 = INTEGER: 0 milliwatts
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortOperStatus.1.523 = INTEGER: off(2)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPdSignature.1.523 = INTEGER: unknownSignature(0)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPowerClassification.1.523 = INTEGER: class0(1)
    // ARUBAWIRED-POE-MIB::arubaWiredPoePethPsePortPseAssignedClass.1.523 = INTEGER: class0(1)

    $oids = snmpwalk_cache_oid($device, 'arubaWiredPoePethPsePortEntry', $oids, 'ARUBAWIRED-POE-MIB');
}

if (is_device_mib($device, 'ZYXEL-POWER-ETHERNET-MIB')) {
    // POWER-ETHERNET-MIB::pethPsePortAdminEnable.1.8 = INTEGER: true(1)
    // POWER-ETHERNET-MIB::pethPsePortPowerPairsControlAbility.1.8 = INTEGER: false(2)
    // POWER-ETHERNET-MIB::pethPsePortPowerPairs.1.8 = INTEGER: signal(1)
    // POWER-ETHERNET-MIB::pethPsePortDetectionStatus.1.8 = INTEGER: deliveringPower(3)
    // POWER-ETHERNET-MIB::pethPsePortPowerPriority.1.8 = INTEGER: low(3)
    // POWER-ETHERNET-MIB::pethPsePortType.1.8 = STRING:
    // POWER-ETHERNET-MIB::pethPsePortPowerClassifications.1.8 = INTEGER: class0(1)

    // ZyXEL: This is the Way
    // ZYXEL-POWER-ETHERNET-MIB::zyPoePsePortMaxPower.8 = INTEGER: 0
    // ZYXEL-POWER-ETHERNET-MIB::zyPoePsePowerUp.8 = INTEGER: ieee802dot3at(3)
    // ZYXEL-POWER-ETHERNET-MIB::zyPoePsePortTimeRange.8 = STRING:
    // ZYXEL-POWER-ETHERNET-MIB::zyPoePsePortInfoPowerConsumption.8 = INTEGER: 2300
    // ZYXEL-POWER-ETHERNET-MIB::zyPoePsePortTimeRangeState.8 = INTEGER: none(0)
    // ZYXEL-POWER-ETHERNET-MIB::zyPoeAutoPdRecoveryPortState.8 = INTEGER: disabled(2)
    // ZYXEL-POWER-ETHERNET-MIB::zyPoeAutoPdRecoveryPortMode.8 = INTEGER: lldp(1)
    // ZYXEL-POWER-ETHERNET-MIB::zyPoeAutoPdRecoveryPortIpAddressType.8 = INTEGER: ipv4(1)
    // ZYXEL-POWER-ETHERNET-MIB::zyPoeAutoPdRecoveryPortIpAddress.8 = Hex-STRING: 00 00 00 00
    // ZYXEL-POWER-ETHERNET-MIB::zyPoeAutoPdRecoveryPollingInterval.8 = INTEGER: 20
    // ZYXEL-POWER-ETHERNET-MIB::zyPoeAutoPdRecoveryPortPollingCount.8 = INTEGER: 3
    // ZYXEL-POWER-ETHERNET-MIB::zyPoeAutoPdRecoveryPortAction.8 = INTEGER: reboot-alarm(1)
    // ZYXEL-POWER-ETHERNET-MIB::zyPoeAutoPdRecoveryPortResumePollingInterval.8 = INTEGER: 600
    // ZYXEL-POWER-ETHERNET-MIB::zyPoeAutoPdRecoveryPortPdRebootCount.8 = INTEGER: 1
    // ZYXEL-POWER-ETHERNET-MIB::zyPoeAutoPdRecoveryPortResumePowerInterval.8 = INTEGER: 10
    $port_oids = snmpwalk_cache_oid($device, 'zyPoePsePortInfoPowerConsumption', [], 'ZYXEL-POWER-ETHERNET-MIB');
    if (safe_count($port_oids)) {
        $port_oids = snmpwalk_cache_oid($device, 'zyPoePsePortMaxPower', $port_oids, 'ZYXEL-POWER-ETHERNET-MIB');
        //$port_oids = snmpwalk_cache_oid($device, 'zyPoePsePowerUp',      $port_oids, 'ZYXEL-POWER-ETHERNET-MIB');
        // Rewrite to common oids array
        foreach ($port_oids as $index => $entry) {
            $new_index = "1.$index";
            if (isset($oids[$new_index])) {
                $oids[$new_index] = array_merge($oids[$new_index], $entry);
            }
        }
        // Base port ifIndex association
        //$dot1d_baseports = snmp_cache_table($device, 'dot1dBasePortIfIndex', [], 'BRIDGE-MIB');
    }
}
// Radlan based MIBs

if (is_device_mib($device, 'CISCOSB-POE-MIB')) {
    // CISCOSB-POE-MIB::rlPethPsePortGroupIndex.1.57 = INTEGER: 1
    // CISCOSB-POE-MIB::rlPethPsePortIndex.1.57 = INTEGER: 57
    // CISCOSB-POE-MIB::rlPethPsePortOutputVoltage.1.57 = INTEGER: 49000
    // CISCOSB-POE-MIB::rlPethPsePortOutputCurrent.1.57 = INTEGER: 46
    // CISCOSB-POE-MIB::rlPethPsePortOutputPower.1.57 = INTEGER: 2100
    // CISCOSB-POE-MIB::rlPethPsePortPowerLimit.1.57 = INTEGER: 15400
    // CISCOSB-POE-MIB::rlPethPsePortStatus.1.57 = INTEGER: 1
    // CISCOSB-POE-MIB::rlPethPsePortStatusDescription.1.57 = STRING: Port is on - valid resistor detected
    // CISCOSB-POE-MIB::rlPethPsePortOperPowerLimit.1.57 = INTEGER: 15400
    // CISCOSB-POE-MIB::rlPethPsePortSupportPoePlus.1.57 = INTEGER: false(2)
    // CISCOSB-POE-MIB::rlPethPsePortTimeRangeName.1.57 = STRING:
    // CISCOSB-POE-MIB::rlPethPsePortOperStatus.1.57 = INTEGER: true(1)
    // CISCOSB-POE-MIB::rlPethPsePortMaxPowerAllocAllowed.1.57 = INTEGER: 16900

    // rlPethPsePortOutputVoltage.1.1 = 0
    // rlPethPsePortOutputCurrent.1.1 = 0
    // rlPethPsePortOutputPower.1.1 = 0
    // rlPethPsePortPowerLimit.1.1 = 30000
    // rlPethPsePortStatus.1.1 = 8
    // rlPethPsePortStatusDescription.1.1 = Port is off. Short condition
    // rlPethPsePortOperPowerLimit.1.1 = 30000
    // rlPethPsePortTimeRangeName.1.1 =
    // rlPethPsePortOperStatus.1.1 = false
    // rlPethPsePortMaxPowerAllocAllowed.1.1 = 30000
    $oids = snmpwalk_cache_oid($device, 'rlPethPsePortEntry', $oids, 'CISCOSB-POE-MIB');
    if (snmp_status()) {
        $radlan_base = '.1.3.6.1.4.1.9.6.1.101.108';
        $radlan_mib  = 'CISCOSB-POE-MIB';
    }
}

if (is_device_mib($device, 'DLINK-3100-POE-MIB')) {
    $oids = snmpwalk_cache_oid($device, 'rlPethPsePortEntry', $oids, 'DLINK-3100-POE-MIB');
    if (snmp_status()) {
        $radlan_base = '.1.3.6.1.4.1.171.10.94.89.89.108';
        $radlan_mib  = 'DLINK-3100-POE-MIB';
    }
}

if (is_device_mib($device, 'Dell-POE-MIB')) {
    // Dell-POE-MIB is old version of MARVELL-POE-MIB, here used other oids for power limit
    $oids = snmpwalk_cache_oid($device, 'rlPethPsePortEntry', $oids, 'Dell-POE-MIB');
    if (snmp_status()) {
        $radlan_base = '.1.3.6.1.4.1.89.108';
        $radlan_mib  = 'Dell-POE-MIB';
    }
} elseif (is_device_mib($device, 'MARVELL-POE-MIB')) {
    $oids = snmpwalk_cache_oid($device, 'rlPethPsePortEntry', $oids, 'MARVELL-POE-MIB');
    if (snmp_status()) {
        $radlan_base = '.1.3.6.1.4.1.89.108';
        $radlan_mib  = 'MARVELL-POE-MIB';
    }
}

// Broadcom based MIBs

if (is_device_mib($device, 'EdgeSwitch-POWER-ETHERNET-MIB')) {
    // NOTE. EdgeSwitch-POWER-ETHERNET-MIB is new version of BROADCOM-POWER-ETHERNET-MIB,
    //       but added agentPethPowerLimitMin and agentPethPowerLimitMax
    // agentPethPowerLimit.1.220 = Gauge32: 15200 Milliwatts
    // agentPethOutputPower.1.220 = Gauge32: 0 Milliwatts
    // agentPethOutputCurrent.1.220 = Gauge32: 0 Milliamps
    // agentPethOutputVolts.1.220 = Gauge32: 0 Volts
    // agentPethTemperature.1.220 = Gauge32: 35 DEGREES
    // agentPethPortReset.1.220 = INTEGER: none(0)

    $oids = snmpwalk_cache_oid($device, 'agentPethPsePortEntry', $oids, 'EdgeSwitch-POWER-ETHERNET-MIB');
    if (snmp_status()) {
        $fastpath_base = '.1.3.6.1.4.1.4413.1.1.15';
        $fastpath_mib  = 'EdgeSwitch-POWER-ETHERNET-MIB';
    }
} elseif (is_device_mib($device, 'BROADCOM-POWER-ETHERNET-MIB')) {
    // agentPethPowerLimit.1.220 = Gauge32: 15200 Milliwatts
    // agentPethOutputPower.1.220 = Gauge32: 0 Milliwatts
    // agentPethOutputCurrent.1.220 = Gauge32: 0 Milliamps
    // agentPethOutputVolts.1.220 = Gauge32: 0 Volts
    // agentPethTemperature.1.220 = Gauge32: 35 DEGREES
    // agentPethPortReset.1.220 = INTEGER: none(0)

    $fastpath_oids = snmpwalk_cache_oid($device, 'agentPethPsePortEntry', $oids, 'BROADCOM-POWER-ETHERNET-MIB');
    if (snmp_status()) {
        $fastpath_base = '.1.3.6.1.4.1.4413.1.1.15';
        $fastpath_mib  = 'BROADCOM-POWER-ETHERNET-MIB';
        $oids          = $fastpath_oids;
    }
}

if (is_device_mib($device, 'DNOS-POWER-ETHERNET-MIB')) {
    // BROADCOM-POWER-ETHERNET-MIB with different base OID
    $fastpath_oids = snmpwalk_cache_oid($device, 'agentPethPsePortEntry', $oids, 'DNOS-POWER-ETHERNET-MIB');
    if (snmp_status()) {
        $fastpath_base = '.1.3.6.1.4.1.674.10895.5000.2.6132.1.1.15';
        $fastpath_mib  = 'DNOS-POWER-ETHERNET-MIB';
        $oids          = $fastpath_oids;
    }
}

if (is_device_mib($device, 'NETGEAR-POWER-ETHERNET-MIB')) {
    // BROADCOM-POWER-ETHERNET-MIB with different base OID
    $fastpath_oids = snmpwalk_cache_oid($device, 'agentPethPsePortEntry', $oids, 'NETGEAR-POWER-ETHERNET-MIB');
    if (snmp_status()) {
        $fastpath_base = '.1.3.6.1.4.1.4526.10.15';
        $fastpath_mib  = 'NETGEAR-POWER-ETHERNET-MIB';
        $oids          = $fastpath_oids;
    }
}

if (is_device_mib($device, 'NG700-POWER-ETHERNET-MIB')) {
    // BROADCOM-POWER-ETHERNET-MIB with different base OID
    $fastpath_oids = snmpwalk_cache_oid($device, 'agentPethPsePortEntry', $oids, 'NG700-POWER-ETHERNET-MIB');
    if (snmp_status()) {
        $fastpath_base = '.1.3.6.1.4.1.4526.11.15';
        $fastpath_mib  = 'NG700-POWER-ETHERNET-MIB';
        $oids          = $fastpath_oids;
    }
}

$not_power_statuses = ['searching', 'disabled', 'otherFault', 'fault'];
print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    if ($entry['pethPsePortAdminEnable'] === 'false') {
        // Skip PoE disabled ports
        continue;
    }

    // Detect PoE Group and port
    [$pethPsePortGroupIndex, $pethPsePortIndex] = explode('.', $index);

    $group = $pethPsePortGroupIndex > 1 ? " Group $pethPsePortGroupIndex" : ''; // Add group name if group number greater than 1

    $options = ['entPhysicalIndex' => $pethPsePortIndex];
    $port    = get_port_by_ifIndex($device['device_id'], $pethPsePortIndex);
    // print_vars($port);

    if (is_array($port)) {
        $entry['ifDescr']                     = $port['port_label'];
        $entry['port_label']                  = $port['port_label'];
        $options['measured_class']            = 'port';
        $options['measured_entity']           = $port['port_id'];
        $options['measured_entity_label']     = $port['port_label'];
        $options['entPhysicalIndex_measured'] = $port['ifIndex'];
    } else {
        $entry['ifDescr'] = "Port $pethPsePortIndex";
    }

    $scale = 0.001; // Init scale

    // Skip not powered
    $deny = FALSE;

    // HH3C-POWER-ETH-EXT-MIB

    if (isset($entry['hh3cPsePortCurrentPower'])) {
        $descr    = $entry['ifDescr'] . ' PoE Power' . $group;
        $oid_name = 'hh3cPsePortCurrentPower';
        $oid_num  = ".1.3.6.1.4.1.25506.2.14.1.1.5.$index";
        $type     = 'HH3C-POWER-ETH-EXT-MIB-' . $oid_name;
        $value    = $entry[$oid_name];

        // Limits
        $options['limit_high'] = $entry['hh3cPsePortPowerLimit'] * $scale;
        if ($options['limit_high'] > 0) {
            $options['limit_high_warn'] = $options['limit_high'] * $warning_threshold; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
        } else {
            unset($options['limit_high']);
        }

        // Skip not powered
        $deny = in_array($entry['pethPsePortDetectionStatus'], $not_power_statuses, TRUE) &&
                $entry['hh3cPsePortPeakPower'] == '0' && $entry['hh3cPsePortAveragePower'] == '0' && $entry['hh3cPsePortCurrentPower'] == '0';
        if (!$deny) {
            discover_sensor_ng($device, 'power', 'HH3C-POWER-ETH-EXT-MIB', $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }
    }

    // ZYXEL-POWER-ETHERNET-MIB
    if (isset($entry['zyPoePsePortInfoPowerConsumption'])) {
        // note. different index
        $descr    = $entry['ifDescr'] . ' PoE Power' . $group;
        $oid_name = 'zyPoePsePortInfoPowerConsumption';
        $oid_num  = ".1.3.6.1.4.1.890.1.15.3.59.2.1.1.1.$pethPsePortIndex";
        $value    = $entry[$oid_name];

        // Limits
        $options['limit_high'] = $entry['zyPoePsePortMaxPower'] * $scale;
        if ($options['limit_high'] > 0) {
            $options['limit_high_warn'] = $options['limit_high'] * $warning_threshold; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
        } else {
            unset($options['limit_high']);
        }

        // Skip not powered
        $deny = in_array($entry['pethPsePortDetectionStatus'], $not_power_statuses, TRUE);
        if (!$deny) {
            discover_sensor_ng($device, 'power', 'ZYXEL-POWER-ETHERNET-MIB', $oid_name, $oid_num, $pethPsePortIndex, NULL, $descr, 0.001, $value, $options);
        }
    }

    // ENTERASYS-POWER-ETHERNET-EXT-MIB

    if (isset($entry['etsysPsePortPowerUsage'])) {
        $descr    = $entry['ifDescr'] . ' PoE Power' . $group;
        $oid_name = 'etsysPsePortPowerUsage';
        $oid_num  = ".1.3.6.1.4.1.5624.1.2.50.1.5.1.1.2.$index";
        $type     = 'ENTERASYS-POWER-ETHERNET-EXT-MIB-' . $oid_name;
        $value    = $entry[$oid_name];

        // Limits
        $options['limit_high'] = $entry['etsysPsePortPowerLimit'] * $scale;
        if ($options['limit_high'] > 0) {
            $options['limit_high_warn'] = $options['limit_high'] * $warning_threshold; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
        } else {
            unset($options['limit_high']);
        }

        // Skip not powered
        $deny = $entry['etsysPsePortDetectionStatus'] === 'searching' && $entry['etsysPsePortPowerUsage'] == '0';
        if (!$deny) {
            discover_sensor_ng($device, 'power', 'ENTERASYS-POWER-ETHERNET-EXT-MIB', $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }
    }

    // HP-ICF-POE-MIB

    if (isset($entry['hpicfPoePethPsePortPower'])) {
        $descr    = $entry['ifDescr'] . ' PoE Power' . $group;
        $oid_name = 'hpicfPoePethPsePortPower';
        $oid_num  = ".1.3.6.1.4.1.11.2.14.11.1.9.1.1.1.3.$index";
        $type     = 'HP-ICF-POE-MIB-' . $oid_name;
        $value    = $entry[$oid_name];

        // Limits
        $options['limit_high'] = $entry['hpicfPoePethPsePortPoePlusPowerValue'] > 0 ? $entry['hpicfPoePethPsePortPoePlusPowerValue'] : $entry['hpicfPoePethPsePortPowerValue'];
        if ($options['limit_high'] > 0) {
            $options['limit_high_warn'] = $options['limit_high'] * $warning_threshold; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
        } else {
            unset($options['limit_high']);
        }

        // Skip not powered
        $deny = in_array($entry['pethPsePortDetectionStatus'], $not_power_statuses, TRUE) &&
                $entry['hpicfPoePethPsePortPower'] == '0' && $entry['hpicfPoePethPsePortVoltage'] == '0' && $entry['hpicfPoePethPsePortCurrent'] == '0';

        if (!$deny) {
            discover_sensor_ng($device, 'power', 'HP-ICF-POE-MIB', $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }

        $descr    = $entry['ifDescr'] . ' PoE Current' . $group;
        $oid_name = 'hpicfPoePethPsePortCurrent';
        $oid_num  = ".1.3.6.1.4.1.11.2.14.11.1.9.1.1.1.1.$index";
        $type     = 'HP-ICF-POE-MIB-' . $oid_name;
        $value    = $entry[$oid_name];

        unset($options['limit_high'], $options['limit_high_warn']);

        if (!$deny) {
            discover_sensor_ng($device, 'current', 'HP-ICF-POE-MIB', $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }

        $descr    = $entry['ifDescr'] . ' PoE Voltage' . $group;
        $oid_name = 'hpicfPoePethPsePortVoltage';
        $oid_num  = ".1.3.6.1.4.1.11.2.14.11.1.9.1.1.1.2.$index";
        $type     = 'HP-ICF-POE-MIB-' . $oid_name;
        $value    = $entry[$oid_name];

        unset($options['limit_high'], $options['limit_high_warn']);

        if (!$deny) {
            discover_sensor_ng($device, 'voltage', 'HP-ICF-POE-MIB', $oid_name, $oid_num, $index, $type, $descr, 0.1, $value, $options);
        }
    }

    // EXTREME-POE-MIB

    if (isset($entry['extremePethPortMeasuredPower'])) {
        $descr    = $entry['ifDescr'] . ' PoE Power' . $group;
        $oid_name = 'extremePethPortMeasuredPower';
        $oid_num  = ".1.3.6.1.4.1.1916.1.27.2.1.1.6.$index";
        $type     = 'EXTREME-POE-MIB-' . $oid_name;
        $value    = $entry[$oid_name];

        // Limits
        $options['limit_high'] = $entry['extremePethPortOperatorLimit'] * $scale;
        if ($options['limit_high'] > 0) {
            $options['limit_high_warn'] = $options['limit_high'] * $warning_threshold; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
        } else {
            unset($options['limit_high']);
        }

        // Skip not powered
        $deny = in_array($entry['pethPsePortDetectionStatus'], $not_power_statuses, TRUE) && $entry['extremePethPortMeasuredPower'] == '0';
        if (!$deny) {
            discover_sensor_ng($device, 'power', 'EXTREME-POE-MIB', $oid_name, $oid_num, $index, $type, $descr, 0.001, $value, $options);
        }
    }

    // BAY-STACK-PETH-EXT-MIB

    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtPowerLimit.1.1 = 32
    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtMeasuredVoltage.1.1 = 0
    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtMeasuredCurrent.1.1 = 0
    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtMeasuredPower.1.1 = 0
    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtPowerUpMode.1.1 = dot3at
    // BAY-STACK-PETH-EXT-MIB::bspePethPsePortExtPowerPairs.1.1 = signal

    if (isset($entry['bspePethPsePortExtMeasuredPower'])) {
        $descr    = $entry['ifDescr'] . ' PoE Power' . $group;
        $oid_name = 'bspePethPsePortExtMeasuredPower';
        $oid_num  = ".1.3.6.1.4.1.45.5.8.1.1.1.7.$index";
        //$type     = 'BAY-STACK-PETH-EXT-MIB-' . $oid_name;
        $value = $entry[$oid_name];

        // Limits
        // bspePethPsePortExtPowerLimit.1.1 = 32
        $options['limit_high'] = $entry['bspePethPsePortExtPowerLimit'];
        if ($options['limit_high'] > 0) {
            $options['limit_high_warn'] = $options['limit_high'] * $warning_threshold; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
        } else {
            unset($options['limit_high']);
        }

        // Skip not powered
        $deny = in_array($entry['pethPsePortDetectionStatus'], $not_power_statuses, TRUE) &&
                $entry['bspePethPsePortExtMeasuredPower'] == '0' && $entry['bspePethPsePortExtMeasuredVoltage'] == '0' && $entry['bspePethPsePortExtMeasuredCurrent'] == '0';

        if (!$deny) {
            discover_sensor_ng($device, 'power', 'BAY-STACK-PETH-EXT-MIB', $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }

        unset($options['limit_high'], $options['limit_high_warn']);

        $descr    = $entry['ifDescr'] . ' PoE Current' . $group;
        $oid_name = 'bspePethPsePortExtMeasuredCurrent';
        $oid_num  = ".1.3.6.1.4.1.45.5.8.1.1.1.6.$index";
        //$type     = 'BAY-STACK-PETH-EXT-MIB-' . $oid_name;
        $value = $entry[$oid_name];

        if (!$deny) {
            discover_sensor_ng($device, 'current', 'BAY-STACK-PETH-EXT-MIB', $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }

        $descr    = $entry['ifDescr'] . ' PoE Voltage' . $group;
        $oid_name = 'bspePethPsePortExtMeasuredVoltage';
        $oid_num  = ".1.3.6.1.4.1.45.5.8.1.1.1.5.$index";
        //$type     = 'BAY-STACK-PETH-EXT-MIB-' . $oid_name;
        $value = $entry[$oid_name];

        if (!$deny) {
            discover_sensor_ng($device, 'voltage', 'BAY-STACK-PETH-EXT-MIB', $oid_name, $oid_num, $index, NULL, $descr, 0.1, $value, $options);
        }
    }

    // ARUBAWIRED-POE-MIB

    if (isset($entry['arubaWiredPoePethPsePortOperStatus'])) {
        // Skip not powered
        $deny = in_array($entry['pethPsePortDetectionStatus'], $not_power_statuses, TRUE) &&
                $entry['arubaWiredPoePethPsePortOperStatus'] !== 'on';

        $descr    = $entry['port_label'] . ' PoE Power' . $group;
        $oid_name = 'arubaWiredPoePethPsePortPowerDrawn';
        $oid_num  = ".1.3.6.1.4.1.47196.4.1.1.3.8.1.1.1.7.$index";
        $value    = $entry[$oid_name];

        // Limits
        $options['limit_high_warn'] = $entry['arubaWiredPoePethPsePortReservedPower'] * 0.001;
        if ($options['limit_high_warn'] > 0) {
            $options['limit_high'] = $options['limit_high_warn'] / $warning_threshold; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
        } else {
            unset($options['limit_high_warn']);
        }

        if (!$deny) {
            discover_sensor_ng($device, 'power', 'ARUBAWIRED-POE-MIB', $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }

        unset($options['limit_high'], $options['limit_high_warn']);

        $descr    = $entry['port_label'] . ' PoE Current' . $group;
        $oid_name = 'arubaWiredPoePethPsePortCurrent';
        $oid_num  = ".1.3.6.1.4.1.47196.4.1.1.3.8.1.1.1.4.$index";
        $value    = $entry[$oid_name];

        if (!$deny) {
            discover_sensor_ng($device, 'current', 'ARUBAWIRED-POE-MIB', $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }

        $descr    = $entry['port_label'] . ' PoE Voltage' . $group;
        $oid_name = 'arubaWiredPoePethPsePortVoltage';
        $oid_num  = ".1.3.6.1.4.1.47196.4.1.1.3.8.1.1.1.5.$index";
        $value    = $entry[$oid_name];

        if (!$deny) {
            discover_sensor_ng($device, 'voltage', 'ARUBAWIRED-POE-MIB', $oid_name, $oid_num, $index, NULL, $descr, 0.1, $value, $options);
        }
    }

    // CISCOSB-POE-MIB / MARVELL-POE-MIB

    if (isset($entry['rlPethPsePortOutputPower'])) {
        $descr    = $entry['ifDescr'] . ' PoE Power' . $group;
        $oid_name = 'rlPethPsePortOutputPower';
        $oid_num  = "$radlan_base.1.1.5.$index";
        $type     = $radlan_mib . '-' . $oid_name;
        $value    = $entry[$oid_name];

        // Limits
        if ($entry['rlPethPsePortMaxPowerAllocAllowed'] > $entry['rlPethPsePortPowerLimit']) {
            $options['limit_high']      = $entry['rlPethPsePortMaxPowerAllocAllowed'] * $scale;
            $options['limit_high_warn'] = $entry['rlPethPsePortPowerLimit'] * $scale;
        } else {
            $options['limit_high'] = $entry['rlPethPsePortPowerLimit'] * $scale;
            if ($options['limit_high'] > 0) {
                $options['limit_high_warn'] = $options['limit_high'] * $warning_threshold; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
            } else {
                unset($options['limit_high']);
            }
        }

        // Skip not powered
        $deny = in_array($entry['pethPsePortDetectionStatus'], $not_power_statuses, TRUE) &&
                $entry['rlPethPsePortOutputPower'] == '0' && $entry['rlPethPsePortOutputVoltage'] == '0' && $entry['rlPethPsePortOutputCurrent'] == '0';
        if (!$deny) {
            discover_sensor_ng($device, 'power', $radlan_mib, $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }

        $descr    = $entry['ifDescr'] . ' PoE Current' . $group;
        $oid_name = 'rlPethPsePortOutputCurrent';
        $oid_num  = "$radlan_base.1.1.4.$index";
        $type     = $radlan_mib . '-' . $oid_name;
        $value    = $entry[$oid_name];

        unset($options['limit_high'], $options['limit_high_warn']);

        if (!$deny) {
            discover_sensor_ng($device, 'current', $radlan_mib, $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }

        $descr    = $entry['ifDescr'] . ' PoE Voltage' . $group;
        $oid_name = 'rlPethPsePortOutputVoltage';
        $oid_num  = "$radlan_base.1.1.3.$index";
        $type     = $radlan_mib . '-' . $oid_name;
        $value    = $entry[$oid_name];

        unset($options['limit_high'], $options['limit_high_warn']);

        if (!$deny) {
            discover_sensor_ng($device, 'voltage', $radlan_mib, $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }
    }

    // BROADCOM-POWER-ETHERNET-MIB / EdgeSwitch-POWER-ETHERNET-MIB / DNOS-POWER-ETHERNET-MIB /
    // NETGEAR-POWER-ETHERNET-MIB / NG700-POWER-ETHERNET-MIB
    // These are copied MIBs with a different base OID, so using the same names... calls for annoying constructions!

    // Skip not powered (all except temperature)
    $fastpath_deny = (!isset($entry['pethPsePortDetectionStatus']) || in_array($entry['pethPsePortDetectionStatus'], $not_power_statuses, TRUE)) &&
                     $entry['agentPethOutputPower'] === '0' && $entry['agentPethOutputCurrent'] === '0' && $entry['agentPethOutputVolts'] === '0';

    if (isset($entry['agentPethOutputPower'])) {
        $descr    = $entry['ifDescr'] . ' PoE Power' . $group;
        $oid_name = 'agentPethOutputPower';
        $oid_num  = "$fastpath_base.1.1.1.2.$index";
        $type     = $fastpath_mib . '-' . $oid_name;
        $value    = $entry[$oid_name];
        $scale    = 0.001;

        // Limits
        if ((is_device_mib($device, 'NETGEAR-POWER-ETHERNET-MIB') || is_device_mib($device, 'NG700-POWER-ETHERNET-MIB')) &&
            $entry['agentPethPowerLimit'] < 100) {
            // Another Netgear hack, it return strange limit "18"
            $entry['agentPethPowerLimit'] = 0;
        }
        if (isset($entry['agentPethPowerLimitMax']) && $entry['agentPethPowerLimitMax'] > 0) {
            $options['limit_high'] = $entry['agentPethPowerLimitMax'] * $scale;
        } else {
            $options['limit_high'] = $entry['agentPethPowerLimit'] * $scale;
        }
        if ($options['limit_high'] > 0) {
            $options['limit_high_warn'] = $options['limit_high'] * $warning_threshold; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
        } else {
            unset($options['limit_high']);
        }
        if (isset($entry['agentPethPowerLimitMin']) && $entry['agentPethPowerLimitMin'] >= 0) {
            $options['limit_low'] = $entry['agentPethPowerLimitMin'] * $scale;
        }

        if (!$fastpath_deny) {
            discover_sensor_ng($device, 'power', $fastpath_mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
        }
    }

    if (isset($entry['agentPethOutputCurrent'])) {
        $descr    = $entry['ifDescr'] . ' PoE Current' . $group;
        $oid_name = 'agentPethOutputCurrent';
        $oid_num  = "$fastpath_base.1.1.1.3.$index";
        $type     = $fastpath_mib . '-' . $oid_name;
        $value    = $entry[$oid_name];

        unset($options['limit_high'], $options['limit_high_warn']);

        if (!$fastpath_deny) {
            discover_sensor_ng($device, 'current', $fastpath_mib, $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }
    }

    if (isset($entry['agentPethOutputVolts'])) {
        $descr    = $entry['ifDescr'] . ' PoE Voltage' . $group;
        $oid_name = 'agentPethOutputVolts';
        $oid_num  = "$fastpath_base.1.1.1.4.$index";
        $type     = $fastpath_mib . '-' . $oid_name;
        $value    = $entry[$oid_name];
        $scale    = 1;

        unset($options['limit_high'], $options['limit_high_warn']);

        if (!$fastpath_deny) {
            discover_sensor_ng($device, 'voltage', $fastpath_mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }
    }

    if (isset($entry['agentPethTemperature']) && $entry['agentPethTemperature'] > 0) {
        $descr    = $entry['ifDescr'] . ' Temperature' . $group;
        $oid_name = 'agentPethTemperature';
        $oid_num  = "$fastpath_base.1.1.1.5.$index";
        $type     = $fastpath_mib . '-' . $oid_name;
        $value    = $entry[$oid_name];
        $scale    = 1;

        unset($options['limit_high'], $options['limit_high_warn']);

        discover_sensor_ng($device, 'temperature', $fastpath_mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
    }

    // pethPsePortDetectionStatus.1.4 = INTEGER: deliveringPower(3)
    $descr    = $entry['ifDescr'] . ' PoE Status' . $group;
    $oid_name = 'pethPsePortDetectionStatus';
    $oid_num  = '.1.3.6.1.2.1.105.1.1.1.6.' . $index;
    $type     = 'pethPsePortDetectionStatus';
    $value    = $entry[$oid_name];

    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, $options);
    unset($options['entPhysicalClass']);

    if (isset($entry['pethPsePortDetectionStatus']) && !in_array($entry['pethPsePortDetectionStatus'], $not_power_statuses, TRUE)) {
        /* This is should be in graphs
    $descr    = $entry['ifDescr'] . ' PoE Invalid Signature' . $group;
    $oid_name = 'pethPsePortInvalidSignatureCounter';
    $oid_num  = "1.3.6.1.2.1.105.1.1.1.11.$index";
    $type     = 'POWER-ETHERNET-MIB-' . $oid_name;
    $value    = $entry[$oid_name];

    if (!$deny)
    {
         discover_counter($device, 'counter', 'POWER-ETHERNET-MIB', $oid_name, $oid_num, $index, $descr, 1, $value);
    }

       $descr    = $entry['ifDescr'] . ' PoE Power denied' . $group;
    $oid_name = 'pethPsePortPowerDeniedCounter';
    $oid_num  = "1.3.6.1.2.1.105.1.1.1.12.$index";
    $type     = 'POWER-ETHERNET-MIB-' . $oid_name;
    $value    = $entry[$oid_name];

    unset($options['limit_high'], $options['limit_high_warn']);

    if (!$deny)
    {
         discover_counter($device, 'counter', 'POWER-ETHERNET-MIB', $oid_name, $oid_num, $index, $descr, 1, $value);
    }

       $descr    = $entry['ifDescr'] . ' PoE Overload' . $group;
    $oid_name = 'pethPsePortOverLoadCounter';
    $oid_num  = "1.3.6.1.2.1.105.1.1.1.13.$index";
    $type     = 'POWER-ETHERNET-MIB-' . $oid_name;
    $value    = $entry[$oid_name];

    unset($options['limit_high'], $options['limit_high_warn']);

    if (!$deny)
    {
         discover_counter($device, 'counter', 'POWER-ETHERNET-MIB', $oid_name, $oid_num, $index, $descr, 1, $value);
    }

       $descr    = $entry['ifDescr'] . ' PoE Short' . $group;
    $oid_name = 'pethPsePortShortCounter';
    $oid_num  = "1.3.6.1.2.1.105.1.1.1.14.$index";
    $type     = 'POWER-ETHERNET-MIB-' . $oid_name;
    $value    = $entry[$oid_name];

    unset($options['limit_high'], $options['limit_high_warn']);

    if (!$deny)
    {
         discover_counter($device, 'counter', 'POWER-ETHERNET-MIB', $oid_name, $oid_num, $index, $descr, 1, $value);
    }
    */
    }
}

unset($warning_threshold);

// EOF
