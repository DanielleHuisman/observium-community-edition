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

// Keep this os detect here!
// for detect as last turn (when other definitions not detected)

if ($os) {
    return;
}

if (str_contains($sysDescr, 'FreeBSD') ||
    str_starts($sysObjectID, ['.1.3.6.1.4.1.8072.3.2.8',         // NET-SNMP
                              '.1.3.6.1.4.1.12325.1.1.2.1'])) { // BSNMP daemon
    $os = 'freebsd';
}

// EOF
