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

if ($fn_hw = get_model_param($device, 'hardware', $poll_device['sysObjectID'])) {
    // Prefer defined hardware
    $hardware = $fn_hw;

    if ($fn_type = get_model_param($device, 'type', $poll_device['sysObjectID'])) {
        $type = $fn_type;
    }
} elseif (str_contains($hardware, 'WiFi')) {
    $type = 'wireless';
}

// EOF
