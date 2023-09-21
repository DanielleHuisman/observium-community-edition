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

/// YAH, leave this here, need run it as last :(

if (!$os) {
    if (str_starts($sysDescr, 'RTCS version')) {
        $os = 'nxp-mqx-rtcs';

        // Accuenergy Accuvim II
        /* Moved to os discovery definition
        if (!safe_empty(snmp_get_oid($device, '.1.3.6.1.4.1.39604.1.1.1.1.6.20.0'))) {
          $os = 'accuvimii';
        }
        */
    }
}

// EOF
