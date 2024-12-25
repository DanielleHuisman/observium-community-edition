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
    $hardware = get_model_param($device, 'hardware', $poll_device['sysObjectID']);
}

// EOF
