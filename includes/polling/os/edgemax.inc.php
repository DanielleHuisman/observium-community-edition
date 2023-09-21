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


// EdgeSwitch XP
if (empty($hardware) && $poll_device['sysObjectID'] == '.1.3.6.1.4.1.10002.1' &&
    str_starts($poll_device['sysName'], 'Edge')) {
    $hardware = $poll_device['sysName'];
}

// EOF
