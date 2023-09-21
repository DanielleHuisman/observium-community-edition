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

// Common unix hardware
if (empty($hardware)) {
    $hw       = is_array($entPhysical) ? $entPhysical['entPhysicalDescr'] : '';
    $hardware = rewrite_unix_hardware($poll_device['sysDescr'], $hw);
}

/*
 * Fetch the VMware product version.
 *
 *  VMWARE-SYSTEM-MIB::vmwProdName.0 = STRING: VMware ESXi
 *  VMWARE-SYSTEM-MIB::vmwProdVersion.0 = STRING: 4.1.0
 *  VMWARE-SYSTEM-MIB::vmwProdBuild.0 = STRING: 348481
 *  VMWARE-SYSTEM-MIB::vmwProdUpdate.0 = STRING: 2
 *
 *  version:   ESXi 4.1.0 U2
 *  features:  build-348481
 *
 * ---
 *
 *  VMWARE-SYSTEM-MIB::vmwProdName.0 = STRING: VMware vCenter Server Appliance
 *  VMWARE-SYSTEM-MIB::vmwProdVersion.0 = STRING: 6.7.0.43000
 *  VMWARE-SYSTEM-MIB::vmwProdBuild.0 = STRING: 15976714
 *  VMWARE-SYSTEM-MIB::vmwProdUpdate.0 = No Such Object available on this agent at this OID
 *
 *  version:   vCenter Server 6.7.0.43000
 *  features:  build-15976714
 */
$oids = ['vmwProdName.0', 'vmwProdVersion.0', 'vmwProdBuild.0', 'vmwProdUpdate.0'];
$data = [];
if (str_icontains_array($poll_device['sysDescr'], ['VMware vCenter Server', 'VMware-vCenter-Server-Appliance'])) {
    // Use old method when VCSA detected, does not handle multiple oid request
    foreach ($oids as $oid) {
        $data = snmp_get_multi_oid($device, $oid, $data, 'VMWARE-SYSTEM-MIB');
    }

    $type = 'server';
} else {
    $data = snmp_get_multi_oid($device, $oids, $data, 'VMWARE-SYSTEM-MIB');
}
$data = $data[0];

$data['vmwProdName'] = str_replace('-', ' ', $data['vmwProdName']);
$data['vmwProdName'] = str_replace(['VMware ', ' Appliance'], '', $data['vmwProdName']);
$version             = $data['vmwProdName'] . ' ' . $data['vmwProdVersion'];
if ($data['vmwProdUpdate']) {
    // Only add update info if update > 0
    $version .= ' U' . $data['vmwProdUpdate'];
}
$features = 'build-' . $data['vmwProdBuild'];

// EOF
