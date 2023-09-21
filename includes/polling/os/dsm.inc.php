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

if (!$hardware) {
    // HOST-RESOURCES-MIB::hrSystemInitialLoadParameters.0 = STRING: "console=ttyS0,115200 ip=off initrd=0x00800040,4M root=/dev/md0 rw syno_hw_version=DS207+v10 ihd_num=2
    $hw = snmp_get($device, 'hrSystemInitialLoadParameters.0', '-Osqnv', 'HOST-RESOURCES-MIB');
    if (preg_match('/syno_hw_version=(?<hardware>[^\s\+]+)/', $hw, $matches)) {
        $hardware = $matches['hardware'];
    }
}

// EOF
