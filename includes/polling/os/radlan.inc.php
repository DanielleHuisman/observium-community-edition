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

if (!$hardware) {
    $hardware = trim(str_replace('ATI', '', $poll_device['sysDescr']));
}

// EOF
