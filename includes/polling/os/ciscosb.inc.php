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

// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamStackUnit.1 = INTEGER: 1
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamSoftwareVersion.1 = STRING: 1.3.5.58
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamFirmwareVersion.1 = STRING: 1.3.5.06
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamHardwareVersion.1 = STRING: V02
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamServiceTag.1 = STRING: SRW2016-K9
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamSoftwareDate.1 = STRING:  10-Oct-2013
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamFirmwareDate.1 = STRING:  21-Jul-2013
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamManufacturer.1 = STRING: Cisco
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamModelName.1 = STRING: SG300-20
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamMd5ChksumBoot.1 = STRING: da44c9c583e5a8a274f911c4d16f501e
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamMd5ChksumImage1.1 = STRING: 482fea5c6731bc9d2739fcea78235720
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamMd5ChksumImage2.1 = STRING: 482fea5c6731bc9d2739fcea78235720
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamRegistrationDone.1 = INTEGER: false(2)
// CISCOSB-Physicaldescription-MIB::rlPhdUnitGenParamRegistrationSuppressed.1 = INTEGER: false(2)

$data = snmp_get_multi_oid($device, 'rlPhdUnitGenParamModelName.1 rlPhdUnitGenParamServiceTag.1 rlPhdUnitGenParamSoftwareVersion.1', array(), 'CISCOSB-Physicaldescription-MIB');
$data = $data[1];

$hardware  = $data['rlPhdUnitGenParamModelName'];
$features  = $data['rlPhdUnitGenParamServiceTag'];
$version   = $data['rlPhdUnitGenParamSoftwareVersion'];

// EOF
