<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

/*
 * Fetch the VMware product version.
 *
 *  VMWARE-SYSTEM-MIB::vmwProdName.0 = STRING: VMware ESXi
 *  VMWARE-SYSTEM-MIB::vmwProdVersion.0 = STRING: 4.1.0
 *  VMWARE-SYSTEM-MIB::vmwProdBuild.0 = STRING: 348481
 *  VMWARE-SYSTEM-MIB::vmwProdUpdate.0 = STRING: 2
 *
 *  version:   ESXi 4.1.0
 *  features:  build-348481
 */

$data     = snmp_get_multi_oid($device, 'vmwProdName.0 vmwProdVersion.0 vmwProdBuild.0 vmwProdUpdate.0', array(), 'VMWARE-SYSTEM-MIB');
$update   = ($data[0]['vmwProdUpdate'] ? ' U' . $data[0]['vmwProdUpdate'] : ''); // Only add update info if update > 0
$version  = preg_replace('/^VMware /', '', $data[0]['vmwProdName']) . ' ' . $data[0]['vmwProdVersion'] . $update;
$features = 'build-' . $data[0]['vmwProdBuild'];

if (is_array($entPhysical))
{
  $hw = $entPhysical['entPhysicalDescr'];
  if (!empty($entPhysical['entPhysicalSerialNum']))
  {
    $serial = $entPhysical['entPhysicalSerialNum'];
  }
}

$hardware = rewrite_unix_hardware($poll_device['sysDescr'], $hw);

// EOF
