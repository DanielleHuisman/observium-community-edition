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

// Detect old/new MIB used, this OIDs exist only for old firmware
if (!safe_empty(snmp_get_oid($device, '.1.3.6.1.4.1.89.53.15.1.18.1')) || // RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensor5Value.1
    !safe_empty(snmp_get_oid($device, '.1.3.6.1.4.1.89.53.15.1.19.1')) || // RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensor5Status.1
    snmp_get_oid($device, '.1.3.6.1.4.1.89.53.15.1.9.1') > 10) {          // RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensorValue.1
    /*
  RlPhdUnitEnvParamEntry ::= SEQUENCE {
      rlPhdUnitEnvParamStackUnit                   INTEGER,
      rlPhdUnitEnvParamMainPSStatus                RlEnvMonState,
      rlPhdUnitEnvParamRedundantPSStatus           RlEnvMonState,
      rlPhdUnitEnvParamFan1Status                  RlEnvMonState,
      rlPhdUnitEnvParamFan2Status                  RlEnvMonState,
      rlPhdUnitEnvParamFan3Status                  RlEnvMonState,
      rlPhdUnitEnvParamFan4Status                  RlEnvMonState,
      rlPhdUnitEnvParamFan5Status                  RlEnvMonState,
      rlPhdUnitEnvParamTempSensorValue             EntitySensorValue,
      rlPhdUnitEnvParamTempSensorStatus            EntitySensorStatus,
      rlPhdUnitEnvParamUpTime                      TimeTicks,
      rlPhdUnitEnvParamTempSensor2Value            EntitySensorValue,
      rlPhdUnitEnvParamTempSensor2Status           EntitySensorStatus,
      rlPhdUnitEnvParamTempSensor3Value            EntitySensorValue,
      rlPhdUnitEnvParamTempSensor3Status           EntitySensorStatus,
      rlPhdUnitEnvParamTempSensor4Value            EntitySensorValue,
      rlPhdUnitEnvParamTempSensor4Status           EntitySensorStatus,
      rlPhdUnitEnvParamTempSensor5Value            EntitySensorValue,
      rlPhdUnitEnvParamTempSensor5Status           EntitySensorStatus
  }
     */
    /*
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamStackUnit.1 = INTEGER: 1
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamMainPSStatus.1 = INTEGER: normal(1)
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamRedundantPSStatus.1 = INTEGER: normal(1)
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamFan1Status.1 = INTEGER: normal(1)
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamFan2Status.1 = INTEGER: normal(1)
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamFan3Status.1 = INTEGER: normal(1)
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamFan4Status.1 = INTEGER: normal(1)
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamFan5Status.1 = INTEGER: notPresent(5)
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensorValue.1 = INTEGER: 37
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensorStatus.1 = INTEGER: ok(1)
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamUpTime.1 = Timeticks: (405000) 1:07:30.00
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensor2Value.1 = INTEGER: 43
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensor2Status.1 = INTEGER: ok(1)
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensor3Value.1 = INTEGER: 33
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensor3Status.1 = INTEGER: ok(1)
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensor4Value.1 = INTEGER: 0
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensor4Status.1 = INTEGER: 4
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensor5Value.1 = INTEGER: 0
  RADLAN-Physicaldescription-old-MIB::rlPhdUnitEnvParamTempSensor5Status.1 = INTEGER: 4
     */
    $mib   = 'RADLAN-Physicaldescription-old-MIB';
    $oids  = snmpwalk_cache_oid($device, 'rlPhdUnitEnvParamEntry', [], $mib, mib_dirs('radlan')); // Leave mib_dirs() here!
    $count = safe_count($oids);

    foreach ($oids as $index => $entry) {
        // Temperature
        $name = 'Sensor';
        for ($i = 1; $i <= 5; $i++) {
            $descr = $name . ' ' . $i;
            if ($count > 1) {
                $descr .= ' Unit ' . $index;
            }
            if ($i == 1) {
                $oid_name = 'rlPhdUnitEnvParamTempSensorValue';
                $oid_num  = ".1.3.6.1.4.1.89.53.15.1.9.$index";
            } else {
                $oid_name = 'rlPhdUnitEnvParamTempSensor' . $i . 'Value';
                $oid_num  = '.1.3.6.1.4.1.89.53.15.1.' . (8 + $i * 2) . '.' . $index;
            }
            $type  = $mib . '-' . $oid_name;
            $scale = 1;
            $value = $entry[$oid_name];
            if ($value != 0) {
                discover_sensor('temperature', $device, $oid_num, $index, $type, $descr, $scale, $value);
            }
        }

        // Skip other if sensors detected by RADLAN-HWENVIROMENT
        if (isset($valid['status']['RADLAN-HWENVIROMENT']['radlan-hwenvironment-state']) ||
            isset($valid['status']['Dell-Vendor-MIB']['dell-vendor-state'])) {
            continue;
        }

        $descr = 'Power Supply 1';
        if ($count > 1) {
            $descr .= ' Unit ' . $index;
        }
        $oid_name = 'rlPhdUnitEnvParamMainPSStatus';
        $oid_num  = ".1.3.6.1.4.1.89.53.15.1.2.$index";
        $type     = 'RlEnvMonState';
        $value    = $entry[$oid_name];

        discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'powerSupply']);

        $descr = 'Power Supply 2';
        if ($count > 1) {
            $descr .= ' Unit ' . $index;
        }
        $oid_name = 'rlPhdUnitEnvParamRedundantPSStatus';
        $oid_num  = ".1.3.6.1.4.1.89.53.15.1.3.$index";
        $type     = 'RlEnvMonState';
        $value    = $entry[$oid_name];

        discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'powerSupply']);

        $name = 'Fan';
        for ($i = 1; $i <= 5; $i++) {
            $descr = $name . ' ' . $i;
            if ($count > 1) {
                $descr .= ' Unit ' . $index;
            }
            $oid_name = 'rlPhdUnitEnvParamFan' . $i . 'Status';
            $oid_num  = '.1.3.6.1.4.1.89.53.15.1.' . (3 + $i) . '.' . $index;
            $type     = 'RlEnvMonState';
            $value    = $entry[$oid_name];

            discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'fan']);
        }
    }

} else {
    /*
    RlPhdUnitEnvParamEntry ::= SEQUENCE {
        rlPhdUnitEnvParamStackUnit                                     INTEGER,
        rlPhdUnitEnvParamMainPSStatus                                  RlEnvMonState,
        rlPhdUnitEnvParamRedundantPSStatus                             RlEnvMonState,
        rlPhdUnitEnvParamFan1Status                                    RlEnvMonState,
        rlPhdUnitEnvParamFan2Status                                    RlEnvMonState,
        rlPhdUnitEnvParamFan3Status                                    RlEnvMonState,
        rlPhdUnitEnvParamFan4Status                                    RlEnvMonState,
        rlPhdUnitEnvParamFan5Status                                    RlEnvMonState,
        rlPhdUnitEnvParamFan6Status                                    RlEnvMonState,
        rlPhdUnitEnvParamTempSensorValue                               EntitySensorValue,
        rlPhdUnitEnvParamTempSensorStatus                              EntitySensorStatus,
        rlPhdUnitEnvParamTempSensorWarningThresholdValue               EntitySensorValue,
        rlPhdUnitEnvParamTempSensorCriticalThresholdValue              EntitySensorValue,
        rlPhdUnitEnvParamUpTime                                        TimeTicks,
        rlPhdUnitEnvParamMonitorAutoRecoveryEnable                     TruthValue,
        rlPhdUnitEnvParamMonitorTemperatureStatus                      INTEGER,
        rlPhdUnitEnvParamMonitorOperStatus                             TruthValue
    }
     */
    $mib  = 'RADLAN-Physicaldescription-MIB';
    $oids = snmpwalk_cache_oid($device, 'rlPhdUnitEnvParamEntry', [], $mib);

    foreach ($oids as $index => $entry) {
        // Temperature
        $descr = 'Unit ' . $index;

        $oid_name = 'rlPhdUnitEnvParamTempSensorValue';
        $oid_num  = ".1.3.6.1.4.1.89.53.15.1.10.$index";
        $type     = $mib . '-' . $oid_name;
        $scale    = 1;
        $value    = $entry[$oid_name];

        // Limits
        $options                    = [];
        $options['limit_high_warn'] = $entry['rlPhdUnitEnvParamTempSensorWarningThresholdValue'];
        $options['limit_high']      = $entry['rlPhdUnitEnvParamTempSensorCriticalThresholdValue'];
        if ($value != 0) {
            discover_sensor('temperature', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);
        }

        // Skip other if sensors detected by RADLAN-HWENVIROMENT
        if (isset($valid['status']['RADLAN-HWENVIROMENT']['radlan-hwenvironment-state']) ||
            isset($valid['status']['Dell-Vendor-MIB']['dell-vendor-state'])) {
            continue;
        }

        $descr = 'Power Supply 1';
        if ($count > 1) {
            $descr .= ' Unit ' . $index;
        }
        $oid_name = 'rlPhdUnitEnvParamMainPSStatus';
        $oid_num  = ".1.3.6.1.4.1.89.53.15.1.2.$index";
        $type     = 'RlEnvMonState';
        $value    = $entry[$oid_name];

        discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'powerSupply']);

        $descr = 'Power Supply 2';
        if ($count > 1) {
            $descr .= ' Unit ' . $index;
        }
        $oid_name = 'rlPhdUnitEnvParamRedundantPSStatus';
        $oid_num  = ".1.3.6.1.4.1.89.53.15.1.3.$index";
        $type     = 'RlEnvMonState';
        $value    = $entry[$oid_name];

        discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'powerSupply']);

        $name = 'Fan';
        for ($i = 1; $i <= 6; $i++) {
            $descr = $name . ' ' . $i;
            if ($count > 1) {
                $descr .= ' Unit ' . $index;
            }
            $oid_name = 'rlPhdUnitEnvParamFan' . $i . 'Status';
            $oid_num  = '.1.3.6.1.4.1.89.53.15.1.' . (3 + $i) . '.' . $index;
            $type     = 'RlEnvMonState';
            $value    = $entry[$oid_name];

            discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'fan']);
        }
    }

}

// EOF
