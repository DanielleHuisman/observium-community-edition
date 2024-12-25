<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

// ALVARION-DOT11-WLAN-MIB::brzaccVLUnitHwVersion.0 = STRING: "A"
// ALVARION-DOT11-WLAN-MIB::brzaccVLMainVersionNumber.0 = STRING: "6.6.2"
// ALVARION-DOT11-WLAN-MIB::brzaccVLUnitType.0 = INTEGER: auSA(2)
// ALVARION-DOT11-WLAN-TST-MIB::brzLighteOemProjectNameString.0 = STRING: "BreezeACCESS VL"

if ($unit_type = snmp_get_oid($device, 'brzaccVLUnitType.0', 'ALVARION-DOT11-WLAN-MIB')) {
    $features = rewrite_breeze_type($unit_type);
}

unset($unit_type);

// EOF
