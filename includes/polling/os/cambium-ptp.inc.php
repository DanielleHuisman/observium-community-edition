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

if (is_device_mib($device, 'CAMBIUM-PTP800-MIB')) {
    $version  = snmp_get_oid($device, 'softwareVersion.0', 'CAMBIUM-PTP800-MIB');
    $hardware = snmp_get_oid($device, 'productName.0', 'CAMBIUM-PTP800-MIB');
} elseif (is_device_mib($device, 'MOTOROLA-PTP-MIB')) {
    $version  = snmp_get_oid($device, 'softwareVersion.0', 'MOTOROLA-PTP-MIB');
    $hardware = snmp_get_oid($device, 'productName.0', 'MOTOROLA-PTP-MIB');
} else {
    $hardware = get_model_param($device, 'hardware', $poll_device['sysObjectID']);
}

// EOF
