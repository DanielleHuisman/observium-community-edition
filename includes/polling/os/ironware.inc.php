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

$hardware = get_model_param($device, 'hardware', $poll_device['sysObjectID']);
if (!$hardware) {
    $hardware = snmp_translate($poll_device['sysObjectID'], 'FOUNDRY-SN-AGENT-MIB:FOUNDRY-SN-ROOT-MIB');
}

// EOF
