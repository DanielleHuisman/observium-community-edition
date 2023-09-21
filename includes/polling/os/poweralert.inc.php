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

// UPS-MIB::upsIdentManufacturer.0 = STRING: "TRIPP LITE"
// UPS-MIB::upsIdentModel.0 = STRING: "TRIPP LITE PDUMH20HVATNET"
// UPS-MIB::upsIdentUPSSoftwareVersion.0 = STRING: "6926600B"
// UPS-MIB::upsIdentAgentSoftwareVersion.0 = STRING: "12.04.0052"
// UPS-MIB::upsIdentName.0 = STRING: "sysname.company.com"
// .1.3.6.1.4.1.850.10.2.2.1.12.1 = STRING: "This Is My Location"

//$data = snmp_get_multi_oid($device, 'upsIdentModel.0', array(), 'UPS-MIB');
//if (is_array($data[0]))
//{
//  $hardware = trim(str_replace('TRIPP LITE', '', $data[0]['upsIdentModel']));
//  //$version  = $data[0]['upsIdentAgentSoftwareVersion'];
//} else {
if (empty($hardware)) {
    //$hardware = $poll_device['sysDescr'];
    $hw = snmp_get_oid($device, '.1.3.6.1.4.1.850.10.2.2.1.9.1', 'TRIPPLITE-12X');
    if ($hw) {
        $hardware = trim(str_replace('TRIPP LITE', '', $hw));
    }
}
if (empty($version)) {
    $version = snmp_get_oid($device, '.1.3.6.1.4.1.850.10.1.2.3.0', 'TRIPPLITE-12X');
}

// EOF
