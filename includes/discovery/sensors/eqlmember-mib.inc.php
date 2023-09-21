<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// We are interested in equallogic group members (devices), not in the group
// find group member id.

// NOTE. This mib should be discovered first (before storage)

// eqlMemberName.1.443914937 = hostname-1
// eqlMemberName.1.1664046123 = hostname-2
$eqlgrpmembers = snmpwalk_cache_oid($device, 'eqlMemberName', [], 'EQLMEMBER-MIB');
if (snmp_status()) {
    $sysName = strtolower(snmp_cache_oid($device, 'sysName.0', 'SNMPv2-MIB'));
}

foreach ($eqlgrpmembers as $index => $entry) {
    // Find member id and name in results
    if (!empty($entry['eqlMemberName']) && strtolower($entry['eqlMemberName']) == $sysName) {
        [, $eqlgrpmemid] = explode('.', $index);
        break;
    }
}

if (!isset($eqlgrpmemid)) {
    // Fall-back to old method.
    $eqlgrpmemid = snmp_get_oid($device, 'eqliscsiLocalMemberId.0', 'EQLVOLUME-MIB');
}

if (is_numeric($eqlgrpmemid) && $eqlgrpmemid != $attribs['eqlgrpmemid']) {
    // Store member id when detected
    set_dev_attrib($device, 'eqlgrpmemid', $eqlgrpmemid);
    $attribs['eqlgrpmemid'] = $eqlgrpmemid;
    print_debug("\neqlgrpmemid: $eqlgrpmemid");
}

print_debug_vars($eqlgrpmemid, 1);

if (is_numeric($eqlgrpmemid)) {
    $oids = snmpwalk_cache_oid($device, 'eqlMemberHealthDetailsFanTable', [], 'EQLMEMBER-MIB');

    // copy of eqlMemberHealthDetailsFanIndex
    $sensorname = [
      'emm0fan0',
      'emm0fan1',
      'emm1fan0',
      'emm1fan1',
      'emm2fan0',
      'emm2fan1',
      'emm3fan0',
      'emm3fan1'
    ];
    $sensorid   = [1, 2, 3, 4, 5, 6, 7, 8];

    foreach ($oids as $index => $entry) {
        # EQLMEMBER-MIB returns sensors for all members. only process sensors that match our member id
        if (strstr($index, $eqlgrpmemid)) {
            $numindex     = str_replace($sensorname, $sensorid, $index);
            $entry['oid'] = ".1.3.6.1.4.1.12740.2.1.7.1.3.$numindex";
            $limits       = ['limit_high'      => $entry['eqlMemberHealthDetailsFanHighCriticalThreshold'],
                             'limit_low'       => $entry['eqlMemberHealthDetailsFanLowCriticalThreshold'],
                             'limit_high_warn' => $entry['eqlMemberHealthDetailsFanHighWarningThreshold'],
                             'limit_low_warn'  => $entry['eqlMemberHealthDetailsFanLowWarningThreshold']];
            if ($entry['eqlMemberHealthDetailsFanValue'] <> 0) {
                discover_sensor('fanspeed', $device, $entry['oid'], $numindex, 'equallogic',
                                $entry['eqlMemberHealthDetailsFanName'], 1, $entry['eqlMemberHealthDetailsFanValue'], $limits);
            }
        }
    }

    $oids = snmpwalk_cache_oid($device, 'eqlMemberHealthDetailsTemperatureTable', [], 'EQLMEMBER-MIB');

    // copy of eqlMemberHealthDetailsTempSensorIndex
    $sensorname = [
      'integratedSystemTemperature',
      'backplaneSensor0',
      'backplaneSensor1',
      'controlModule0processor',
      'controlModule0chipset',
      'controlModule1processor',
      'controlModule1chipset',
      'controlModule0sasController',
      'controlModule0sasExpander',
      'controlModule0sesEnclosure',
      'controlModule1sasController',
      'controlModule1sasExpander',
      'controlModule1sesEnclosure',
      'sesOpsPanel',
      'cemi0',
      'cemi1',
      'controlModule0batteryThermistor',
      'controlModule1batteryThermistor'
    ];
    $sensorid   = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];

    foreach ($oids as $index => $entry) {
        # EQLMEMBER-MIB returns sensors for all members. only process sensors that match our member id
        if (strstr($index, $eqlgrpmemid)) {
            $numindex     = str_replace($sensorname, $sensorid, $index);
            $entry['oid'] = ".1.3.6.1.4.1.12740.2.1.6.1.3.$numindex";
            $limits       = ['limit_high'      => $entry['eqlMemberHealthDetailsTemperatureHighCriticalThreshold'],
                             'limit_low'       => $entry['eqlMemberHealthDetailsTemperatureLowCriticalThreshold'],
                             'limit_high_warn' => $entry['eqlMemberHealthDetailsTemperatureHighWarningThreshold'],
                             'limit_low_warn'  => $entry['eqlMemberHealthDetailsTemperatureLowWarningThreshold']];
            if ($entry['eqlMemberHealthDetailsTemperatureValue'] <> 0) {
                discover_sensor('temperature', $device, $entry['oid'], $numindex, 'equallogic',
                                $entry['eqlMemberHealthDetailsTemperatureName'], 1, $entry['eqlMemberHealthDetailsTemperatureValue'], $limits);
            }
        }
    }
}

// EOF
