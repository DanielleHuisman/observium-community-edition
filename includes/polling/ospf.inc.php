<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

/// FIXME. Rewrite (clean), clean stale entries (after disable)
// Pre-polling checks
if (!$config['enable_ospf']) {
  // OSPF not enabled
  return;
}

$ospf_stats = [
  'instances'  => 0,
  'ports'      => 0,
  'areas'      => 0,
  'neighbours' => 0
];

// This module not have discovery (only mibs discovery)
$mib_exist = FALSE;
foreach ([ 'OSPF-MIB', 'OSPFV3-MIB' ] as $mib) {
  if (is_device_mib($device, $mib)) {
    $ospf_enabled = FALSE; // TRUE, when ospf enabled

    include $config['install_dir'] . '/includes/polling/ospf/'.strtolower($mib).'.inc.php';
  }
}

if (!$mib_exist) {
  // OSPF MIBs excluded
  unset($mib_exist, $ospf_enabled, $ospf_stats);
  return;
}

// Create device-wide statistics RRD
rrdtool_update_ng($device, 'ospf-statistics', [
  'instances'  => $ospf_stats['instances'],
  'areas'      => $ospf_stats['areas'],
  'ports'      => $ospf_stats['ports'],
  'neighbours' => $ospf_stats['neighbours'],
]);

$graphs['ospf_neighbours'] = TRUE;
$graphs['ospf_areas']      = TRUE;
$graphs['ospf_ports']      = TRUE;

// EOF
