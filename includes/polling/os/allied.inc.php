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

// Allied Telesis have somewhat messy MIBs. It's often hard to work out what is where. :)
if (!$hardware) {
    // AtiSwitch-MIB::atiswitchProductType.0 = INTEGER: at8024GB(2)
    // AtiSwitch-MIB::atiswitchSw.0 = STRING: AT-S39
    // AtiSwitch-MIB::atiswitchSwVersion.0 = STRING: v3.3.0

    $hardware = snmp_get($device, 'atiswitchProductType.0', '-OsvQU', 'AtiSwitch-MIB');
    if ($hardware) {
        $version  = snmp_get($device, 'atiswitchSwVersion.0', '-OsvQU', 'AtiSwitch-MIB');
        $features = snmp_get($device, 'atiswitchSw.0', '-OsvQU', 'AtiSwitch-MIB');

        $hardware = str_replace('at', 'AT-', $hardware);
        $version  = str_replace('v', '', $version);
    } else {
        // AtiL2-MIB::atiL2SwProduct.0 = STRING: "AT-8326GB"
        // AtiL2-MIB::atiL2SwVersion.0 = STRING: "AT-S41 v1.1.6 "
        $hardware = snmp_get($device, 'atiL2SwProduct.0', '-OsvQU', 'AtiL2-MIB');
        if ($hardware) {
            $version = snmp_get($device, 'atiL2SwVersion.0', '-OsvQU', 'AtiL2-MIB');

            [$features, $version] = explode(' ', $version);
            $version = str_replace('v', '', $version);
        }
    }
} elseif (!$version) {
    // Same as above
    $version = snmp_get($device, 'atiswitchSwVersion.0', '-OsvQU', 'AtiSwitch-MIB');
    if (!$version) {
        $version = snmp_get($device, 'atiL2SwVersion.0', '-OsvQU', 'AtiL2-MIB');
        [$features, $version] = explode(' ', $version);
    }
    $version = str_replace('v', '', $version);
}

// EOF
