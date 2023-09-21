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

/// FIXME. Rewrite (clean), clean stale entries (after disable)
// Pre-polling checks
if (!$config['enable_ospf']) {
    // OSPF not enabled
    return;
}

// This module not have discovery (only mibs discovery)
$mib_exist = FALSE;
foreach (['OSPF-MIB', 'OSPFV3-MIB'] as $mib) {
    if (is_device_mib($device, $mib)) {
        $ospf_enabled = FALSE; // TRUE, when ospf enabled

        include $config['install_dir'] . '/includes/polling/ospf/' . strtolower($mib) . '.inc.php';
    }
}

if (!$ospf_enabled) {
    // OSPF MIBs excluded
    unset($mib_exist, $ospf_enabled, $ospf_stats);
    return;
}



// EOF
