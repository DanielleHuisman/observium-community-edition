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

if (!$os) {
    if (str_starts($sysDescr, 'SunOS')) {
        $os = 'solaris';
        //SunOS PVX_MHD 5.10 Generic_141445-09 i86pc
        //SunOS QH-XN-ESN-PD-1.CDMA-EMS 5.10 Generic_137111-06 sun4v
        //SunOS router 5.11 11.3 i86pc
        //SunOS fenton.rubberduck.com.au 5.11 joyent_20131218T184706Z i86pc
        //SunOS release:5.9 version:Generic_118558-34 machine:sun4u
        //SunOS 5.10 sun4u sparc SUNW,Sun-Fire-V210, Class 5 Call Manager
        //list(,, $version) = explode (' ', $sysDescr);
        if (preg_match('/SunOS \S+ (?<version>\d[\d\.]+)/', $sysDescr, $matches) ||
            preg_match('/SunOS (release:)?(?<version>\d[\d\.]+)/', $sysDescr, $matches)) {
            $version = $matches['version'];

            if (version_compare($version, '5.10', '>')) {
                $os = 'opensolaris';
                if (str_contains_array($sysDescr, 'oi_')) {
                    $os = 'openindiana';
                }
            }
        }
    }
}

// EOF
