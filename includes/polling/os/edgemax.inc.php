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


// EdgeSwitch XP
if (empty($hardware) && $poll_device['sysObjectID'] == '.1.3.6.1.4.1.10002.1' &&
    str_starts($poll_device['sysName'], 'Edge')) {
    $hardware = $poll_device['sysName'];
}

// EOF
