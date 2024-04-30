<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// MG-SNMP-UPS-MIB::upsmgIdentFamilyName.0 = STRING: "PULSAR M"
// MG-SNMP-UPS-MIB::upsmgIdentModelName.0 = STRING: "2200"
// MG-SNMP-UPS-MIB::upsmgIdentSerialNumber.0 = STRING: "AQ1H01024"

// MG-SNMP-UPS-MIB::upsmgAgentFirmwareVersion.0 = "GA"

// MG-SNMP-UPS-MIB::upsmgIdentModelName.0 = STRING: "5000_60"

$hardware = snmp_get_oid($device, 'upsmgIdentFamilyName.0', 'MG-SNMP-UPS-MIB');
$model    = snmp_get_oid($device, 'upsmgIdentModelName.0', 'MG-SNMP-UPS-MIB');

// "5000_60" -> "5000 (60 kVA)"
if (str_contains($model, '_')) {
    $hardware .= implode(' (', explode('_', $model)) . ' kVA)';
} elseif (is_numeric($model) && $model >= 10000) {
    $hardware .= ' (' . (int)($model / 1000) . ' kVA)';
} else {
    $hardware .= ' ' . $model;
}

$features = 'Firmware: ' . snmp_get_oid($device, 'upsmgAgentFirmwareVersion.0', 'MG-SNMP-UPS-MIB');

// EOF
