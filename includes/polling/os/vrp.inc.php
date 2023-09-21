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

// See: https://jira.observium.org/browse/OBS-3737

/* Patch activated and installed
HUAWEI-SYS-MAN-MIB::hwPatchUsedFileName.3.250 = STRING: flash:/s12700-v200r010sph018.pat
HUAWEI-SYS-MAN-MIB::hwPatchUsedFileName.5.582 = STRING: flash:/s12700-v200r010sph018.pat
HUAWEI-SYS-MAN-MIB::hwPatchVersion.3.250 = STRING: V200R010SPH018
HUAWEI-SYS-MAN-MIB::hwPatchVersion.5.582 = STRING: V200R010SPH018
HUAWEI-SYS-MAN-MIB::hwPatchProgramVersion.3.250 = STRING: V200R010C00SPC600
HUAWEI-SYS-MAN-MIB::hwPatchProgramVersion.5.582 = STRING: V200R010C00SPC600
HUAWEI-SYS-MAN-MIB::hwPatchAdminStatus.3.250 = INTEGER: run(1)
HUAWEI-SYS-MAN-MIB::hwPatchAdminStatus.5.582 = INTEGER: run(1)
HUAWEI-SYS-MAN-MIB::hwPatchOperateState.3.250 = INTEGER: patchRunning(1)
HUAWEI-SYS-MAN-MIB::hwPatchOperateState.5.582 = INTEGER: patchRunning(1)
HUAWEI-SYS-MAN-MIB::hwPatchOperateDestType.3.250 = INTEGER: unused(5)
HUAWEI-SYS-MAN-MIB::hwPatchOperateDestType.5.582 = INTEGER: unused(5)
 */

/* Patch active but not installed
HUAWEI-SYS-MAN-MIB::hwPatchUsedFileName.3.250 = STRING: flash:/s12700-v200r010sph018.pat
HUAWEI-SYS-MAN-MIB::hwPatchUsedFileName.5.582 = STRING: flash:/s12700-v200r010sph018.pat
HUAWEI-SYS-MAN-MIB::hwPatchVersion.3.250 = STRING: V200R010SPH018
HUAWEI-SYS-MAN-MIB::hwPatchVersion.5.582 = STRING: V200R010SPH018
HUAWEI-SYS-MAN-MIB::hwPatchProgramVersion.3.250 = STRING: V200R010C00SPC600
HUAWEI-SYS-MAN-MIB::hwPatchProgramVersion.5.582 = STRING: V200R010C00SPC600
HUAWEI-SYS-MAN-MIB::hwPatchAdminStatus.3.250 = INTEGER: active(2)
HUAWEI-SYS-MAN-MIB::hwPatchAdminStatus.5.582 = INTEGER: active(2)
HUAWEI-SYS-MAN-MIB::hwPatchOperateState.3.250 = INTEGER: patchActive(2)
HUAWEI-SYS-MAN-MIB::hwPatchOperateState.5.582 = INTEGER: patchActive(2)
HUAWEI-SYS-MAN-MIB::hwPatchOperateDestType.3.250 = INTEGER: unused(5)
HUAWEI-SYS-MAN-MIB::hwPatchOperateDestType.5.582 = INTEGER: unused(5)
 */

/* Patch loaded but not activated
HUAWEI-SYS-MAN-MIB::hwPatchUsedFileName.3.250 = STRING: flash:/s12700-v200r010sph018.pat
HUAWEI-SYS-MAN-MIB::hwPatchUsedFileName.5.582 = STRING: flash:/s12700-v200r010sph018.pat
HUAWEI-SYS-MAN-MIB::hwPatchVersion.3.250 = STRING: V200R010SPH018
HUAWEI-SYS-MAN-MIB::hwPatchVersion.5.582 = STRING: V200R010SPH018
HUAWEI-SYS-MAN-MIB::hwPatchProgramVersion.3.250 = STRING: V200R010C00SPC600
HUAWEI-SYS-MAN-MIB::hwPatchProgramVersion.5.582 = STRING: V200R010C00SPC600
HUAWEI-SYS-MAN-MIB::hwPatchAdminStatus.3.250 = INTEGER: deactive(3)
HUAWEI-SYS-MAN-MIB::hwPatchAdminStatus.5.582 = INTEGER: deactive(3)
HUAWEI-SYS-MAN-MIB::hwPatchOperateState.3.250 = INTEGER: patchDeactive(3)
HUAWEI-SYS-MAN-MIB::hwPatchOperateState.5.582 = INTEGER: patchDeactive(3)
HUAWEI-SYS-MAN-MIB::hwPatchOperateDestType.3.250 = INTEGER: unused(5)
HUAWEI-SYS-MAN-MIB::hwPatchOperateDestType.5.582 = INTEGER: unused(5)
 */

if (($hwPatchOperateState = snmp_getnext_oid($device, 'hwPatchOperateState', 'HUAWEI-SYS-MAN-MIB')) &&
    $hwPatchOperateState === 'patchRunning') {
    $version = snmp_getnext_oid($device, 'hwPatchProgramVersion', 'HUAWEI-SYS-MAN-MIB');
    $version .= ' (' . snmp_getnext_oid($device, 'hwPatchVersion', 'HUAWEI-SYS-MAN-MIB') . ')';
}

if (safe_empty($version)) {
    $version = snmp_get_oid($device, 'hwSysImageVersion.1', 'HUAWEI-SYS-MAN-MIB');
}

// EOF