<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

// Linux netgear001000 2.4.27-devicescape.3 #1 Fri Jun 9 14:27:39 EDT 2006 armv5b

if (str_starts($poll_device['sysDescr'], 'Linux')) {
    $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
}

// EOF
