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

// Filthy hack to get software version. may not work on anything but 585v7 :)
$loop = snmp_get($device, 'ifDescr.101');

if ($loop) {
    preg_match('@([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)@i',
               $loop, $matches);
    $version = $matches[1];
}

// EOF
