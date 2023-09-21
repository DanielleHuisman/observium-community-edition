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

// ArubaOS (MODEL: Aruba3600), Version 6.1.2.2 (29541)
// ArubaOS Version 6.1.2.3-2.1.0.0 // - AP135

//$badchars = array('(', ')', ',');
//list(,,$hardware,,$version,) = str_replace($badchars, '', explode (' ', $poll_device['sysDescr']));

$hardware = get_model_param($device, 'hardware', $poll_device['sysObjectID']);

// Stuff about the controller
$aruba_info = snmpwalk_cache_oid($device, 'wlsxSwitchRole', [], 'WLSX-SWITCH-MIB');
$aruba_info = snmpwalk_cache_oid($device, 'wlsxSwitchMasterIp', $aruba_info, 'WLSX-SWITCH-MIB');

if ($aruba_info[0]['wlsxSwitchRole'] === 'master') {
    $features = 'Master Controller';
} else {
    $features = 'Local Controller for ' . $aruba_info[0]['wlsxSwitchMasterIp'];
}

// EOF
