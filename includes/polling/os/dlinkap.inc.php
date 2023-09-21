yy<?php

/**
 * Observium
 *
 * This file is part of Observium.
 *
 * @package       observium
 * @subpackage    poller
 * @copyright (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (preg_match('/^(?:dlink |d-link )?(?<hardware>[a-z]+\-\d+[\w\/-]+)/i', $poll_device['sysDescr'], $matches)) {

    //D-Link DAP-3520
    //D-Link Access Point

    $hardware = $matches['hardware'];

    //.1.3.6.1.4.1.171.10.37.37.5.1.1.0 = STRING: "1.15 09:34:09 01/21/2013"
    //DAP-3520-v115::deviceInformationFirmwareVersion.0 = STRING: "1.15 09:34:09 01/21/2013"

    $version = snmp_get($device, '.1.3.6.1.4.1.171.10.37.37.5.1.1.0', '-Ovq', 'RMON2-MIB');

} else {

    //.1.3.6.1.4.1.171.10.37.20.1.9.0 = STRING: "v2.58RU"
    //DWL-3200::systemFirmwareVersion.0 = STRING: "v2.58RU"
    //$version = snmp_get($device, 'systemFirmwareVersion.0', '-Ovq', 'APMII-DWL-3200AP-MIB');

    $version = snmp_get($device, '.1.3.6.1.4.1.171.10.37.20.5.1.1.0', '-Ovq', 'RMON2-MIB');

    //$hardware = snmp_get($device, 'systemModelName.0', '-Ovq', 'APMII-DWL-3200AP-MIB');

    $hardware = snmp_get($device, '.1.3.6.1.4.1.171.10.37.20.1.8.0', '-Ovq', 'RMON2-MIB');

}
// EOF
