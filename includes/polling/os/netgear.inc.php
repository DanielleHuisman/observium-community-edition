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

// Linux netgear001000 2.4.27-devicescape.3 #1 Fri Jun 9 14:27:39 EDT 2006 armv5b

if (str_starts($poll_device['sysDescr'], 'Linux')) {
    $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
}

// EOF
