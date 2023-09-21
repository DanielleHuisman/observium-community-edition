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

// Here DOM sensors for OcNOS os, currently not possible convert to definitions
// because used multichannel sensors (ie for 40G/100G interfaces)

$cmmTrans    = snmpwalk_multipart_oid($device, "cmmTransEEPROMTable", [], "CMM-CHASSIS-MIB");
$cmmTransDDM = snmpwalk_multipart_oid($device, "cmmTransDDMTable", [], "CMM-CHASSIS-MIB");
print_debug_vars($cmmTrans);
print_debug_vars($cmmTransDDM);

foreach ($cmmTrans as $unit => $unit_entry) {
    foreach ($unit_entry as $trans_index => $trans_entry) {
        // Skip not installed
        if ($trans_entry['cmmTransPresence'] == 'notpresent') {
            continue;
        }

        // Single channel transceivers, detect port
        if ($trans_entry['cmmTransNoOfChannels'] === '1') {
            $trans_port = dbFetchRow('SELECT * FROM `ports` WHERE `device_id` = ? AND `ifType` = ? AND `ifName` REGEXP ?', [$device['device_id'], 'ethernetCsmacd', '[^[:digit:][:punct:]]' . $trans_index . '$']);
            // Hrm, this is better query, but port_label_num fills only after first polling (after first discovery)
            //$trans_port = dbFetchRow('SELECT * FROM `ports` WHERE `device_id` = ? AND `ifType` = ? AND `port_label_num` = ?', array($device['device_id'], 'ethernetCsmacd', $trans_index));
            //print_vars($port);
        } else {
            $trans_port = NULL;
        }

        foreach ($cmmTransDDM[$unit][$trans_index] as $channel_index => $entry) {
            // Multichannel tranceivers
            if ($trans_entry['cmmTransNoOfChannels'] > 1 || empty($trans_port)) {
                $port = dbFetchRow('SELECT * FROM `ports` WHERE `device_id` = ? AND `ifType` = ? AND `ifName` REGEXP ?', [$device['device_id'], 'ethernetCsmacd', '[^[:digit:][:punct:]]' . $trans_index . '/' . $channel_index . '$']);
            } else {
                $port = $trans_port;
            }

            $options = ['entPhysicalIndex' => $trans_index];
            if ($port) {
                $name                       = $port['ifName'];
                $options['measured_class']  = 'port';
                $options['measured_entity'] = $port['port_id'];
            } else {
                $name = "Port $trans_index Channel $channel_index";
            }
            // Append extended transceiver info
            $name_ext = ' (' . $trans_entry['cmmTransVendorName'] . ' ' . $trans_entry['cmmTransVendorPartNumber'] . ' ' . $trans_entry['cmmTransLengthKmtrs'] . 'km)';

            $index = "$unit.$trans_index.$channel_index";

            $descr    = $name . ' Temperature' . $name_ext;
            $oid_name = 'cmmTransTemperature';
            $oid_num  = '.1.3.6.1.4.1.36673.100.1.2.3.1.2.' . $index;
            $scale    = 0.01;
            //$type     = $mib . '-' . $oid_name;
            $value = $entry[$oid_name];

            if ($value > -100000) // '-100001' indicates unavailable
            {
                $limits = $options;
                if ($entry['cmmTransTempCriticalThresholdMax'] > -100000) {
                    $limits['limit_high'] = $entry['cmmTransTempCriticalThresholdMax'] * $scale;
                }
                if ($entry['cmmTransTempAlertThresholdMax'] > -100000) {
                    $limits['limit_high_warn'] = $entry['cmmTransTempAlertThresholdMax'] * $scale;
                }
                if ($entry['cmmTransTempAlertThresholdMin'] > -100000) {
                    $limits['limit_low_warn'] = $entry['cmmTransTempAlertThresholdMin'] * $scale;
                }
                if ($entry['cmmTransTempCriticalThresholdMin'] > -100000) {
                    $limits['limit_low'] = $entry['cmmTransTempCriticalThresholdMin'] * $scale;
                }

                discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $limits);
            }

            $descr    = $name . ' Voltage' . $name_ext;
            $oid_name = 'cmmTransVoltage';
            $oid_num  = '.1.3.6.1.4.1.36673.100.1.2.3.1.7.' . $index;
            $scale    = 0.001;
            //$type     = $mib . '-' . $oid_name;
            $value = $entry[$oid_name];

            if ($value > -100000) // '-100001' indicates unavailable
            {
                $limits = $options;
                if ($entry['cmmTransVoltCriticalThresholdMax'] > -100000) {
                    $limits['limit_high'] = $entry['cmmTransVoltCriticalThresholdMax'] * $scale;
                }
                if ($entry['cmmTransVoltAlertThresholdMax'] > -100000) {
                    $limits['limit_high_warn'] = $entry['cmmTransVoltAlertThresholdMax'] * $scale;
                }
                if ($entry['cmmTransVoltAlertThresholdMin'] > -100000) {
                    $limits['limit_low_warn'] = $entry['cmmTransVoltAlertThresholdMin'] * $scale;
                }
                if ($entry['cmmTransVoltCriticalThresholdMin'] > -100000) {
                    $limits['limit_low'] = $entry['cmmTransVoltCriticalThresholdMin'] * $scale;
                }

                discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $limits);
            }

            $descr    = $name . ' Bias Current' . $name_ext;
            $oid_name = 'cmmTransLaserBiasCurrent';
            $oid_num  = '.1.3.6.1.4.1.36673.100.1.2.3.1.12.' . $index;
            $scale    = 0.001;
            //$type     = $mib . '-' . $oid_name;
            $value = $entry[$oid_name];

            if ($value > -100000) // '-100001' indicates unavailable
            {
                $limits = $options;
                if ($entry['cmmTransLaserBiasCurrCriticalThresholdMax'] > -100000) {
                    $limits['limit_high'] = $entry['cmmTransLaserBiasCurrCriticalThresholdMax'] * $scale;
                }
                if ($entry['cmmTransLaserBiasCurrAlertThresholdMax'] > -100000) {
                    $limits['limit_high_warn'] = $entry['cmmTransLaserBiasCurrAlertThresholdMax'] * $scale;
                }
                if ($entry['cmmTransLaserBiasCurrAlertThresholdMin'] > -100000) {
                    $limits['limit_low_warn'] = $entry['cmmTransLaserBiasCurrAlertThresholdMin'] * $scale;
                }
                if ($entry['cmmTransLaserBiasCurrCriticalThresholdMin'] > -100000) {
                    $limits['limit_low'] = $entry['cmmTransLaserBiasCurrCriticalThresholdMin'] * $scale;
                }

                discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $limits);
            }

            $descr    = $name . ' Transmit Power' . $name_ext;
            $oid_name = 'cmmTransTxPower';
            $oid_num  = '.1.3.6.1.4.1.36673.100.1.2.3.1.17.' . $index;
            $scale    = 0.001;
            //$type     = $mib . '-' . $oid_name;
            $value = $entry[$oid_name];

            if ($value > -100000) // '-100001' indicates unavailable
            {
                $limits = $options;
                if ($entry['cmmTransTxPowerCriticalThresholdMax'] > -100000) {
                    $limits['limit_high'] = $entry['cmmTransTxPowerCriticalThresholdMax'] * $scale;
                }
                if ($entry['cmmTransTxPowerAlertThresholdMax'] > -100000) {
                    $limits['limit_high_warn'] = $entry['cmmTransTxPowerAlertThresholdMax'] * $scale;
                }
                if ($entry['cmmTransTxPowerAlertThresholdMin'] > -100000) {
                    $limits['limit_low_warn'] = $entry['cmmTransTxPowerAlertThresholdMin'] * $scale;
                }
                if ($entry['cmmTransTxPowerCriticalThresholdMin'] > -100000) {
                    $limits['limit_low'] = $entry['cmmTransTxPowerCriticalThresholdMin'] * $scale;
                }

                discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $limits);
            }

            $descr    = $name . ' Receive Power' . $name_ext;
            $oid_name = 'cmmTransRxPower';
            $oid_num  = '.1.3.6.1.4.1.36673.100.1.2.3.1.22.' . $index;
            $scale    = 0.001;
            //$type     = $mib . '-' . $oid_name;
            $value = $entry[$oid_name];

            if ($value > -100000) // '-100001' indicates unavailable
            {
                $limits = $options;
                if ($entry['cmmTransRxPowerCriticalThresholdMax'] > -100000) {
                    $limits['limit_high'] = $entry['cmmTransRxPowerCriticalThresholdMax'] * $scale;
                }
                if ($entry['cmmTransRxPowerAlertThresholdMax'] > -100000) {
                    $limits['limit_high_warn'] = $entry['cmmTransRxPowerAlertThresholdMax'] * $scale;
                }
                if ($entry['cmmTransRxPowerAlertThresholdMin'] > -100000) {
                    $limits['limit_low_warn'] = $entry['cmmTransRxPowerAlertThresholdMin'] * $scale;
                }
                if ($entry['cmmTransRxPowerCriticalThresholdMin'] > -100000) {
                    $limits['limit_low'] = $entry['cmmTransRxPowerCriticalThresholdMin'] * $scale;
                }

                discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $limits);
            }

        }
    }
}

// EOF
