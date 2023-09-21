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

// BIANCA-BRICK-MIB::biboAdmSWVersion.0 = STRING: V.7.5 Rev. 7 (Patch 6) IPSec from 2010/02/11 00:00:00

$data    = snmp_get_multi_oid($device, 'biboAdmSWVersion.0', [], 'BIANCA-BRICK-MIB');
$version = $data[0]['biboAdmSWVersion'];
if (preg_match('/^(V\.)?(?<version>\d+[\.\d]+( Rev\. \d+)?( \(Patch \d+\))?)/', $version, $matches)) {
    $version = $matches['version'];
}

// EOF