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

if (preg_match('/Linux .*? (?<kernel>[\d\.]+-MELLANOXuni-\w+) EFM_(?<arch>[^_]+)_(?<hardware>\w+) EFM_(?<version>[\d\.]+)/', $poll_device['sysDescr'], $matches)) {
    // Linux switch-63014c 2.6.27-MELLANOXuni-m405ex EFM_PPC_M405EX EFM_1.1.3000 #1 2013-07-08 14:29:44 ppc
    // Linux c2-ibsw1 2.6.27-MELLANOXuni-m460ex EFM_PPC_M460EX EFM_1.1.2500 #1 2011-02-22 15:51:54 ppc

    //$hardware = $matches['hardware'];
    $hardware = 'IS50XX'; // FIXME. Required devices for tests
    $version  = $matches['version'];
    $kernel   = $matches['kernel'];
    $arch     = $matches['arch'];
} else {
    // FIXME. Use snmp here
}

unset($matches);

// EOF
