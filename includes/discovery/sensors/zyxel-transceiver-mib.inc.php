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

$zyxelTransceiverSerialEntry = snmpwalk_cache_oid($device, 'zyxelTransceiverSerialEntry', [], 'ZYXEL-TRANSCEIVER-MIB');
print_debug_vars($zyxelTransceiverSerialEntry);
if (!snmp_status()) {
    return;
}

$dot1dBasePortIfIndex = snmp_cache_table($device, 'dot1dBasePortIfIndex', [], 'BRIDGE-MIB');
print_debug_vars($dot1dBasePortIfIndex);

$zyxelTransceiverDdmiEntry = snmpwalk_multipart_oid($device, 'zyxelTransceiverDdmiEntry', [], 'ZYXEL-TRANSCEIVER-MIB');
print_debug_vars($zyxelTransceiverDdmiEntry);

foreach ($zyxelTransceiverDdmiEntry as $baseport => $transeiver) {
    if ($zyxelTransceiverSerialEntry[$baseport]['zyTransceiverSerialModuleType'] === 'nonoperational' ||
        $zyxelTransceiverSerialEntry[$baseport]['zyTransceiverSerialTransceiver'] === 'okWithoutDdm') {
        continue;
    }
    $transeiver_name = $zyxelTransceiverSerialEntry[$baseport]['zyTransceiverSerialVendor'] . ' ' .
                       $zyxelTransceiverSerialEntry[$baseport]['zyTransceiverSerialPartNumber'] . ' ' .
                       $zyxelTransceiverSerialEntry[$baseport]['zyTransceiverSerialTransceiver'];

    // Detect port
    $ifIndex = isset($dot1dBasePortIfIndex[$baseport]['dot1dBasePortIfIndex']) ? $dot1dBasePortIfIndex[$baseport]['dot1dBasePortIfIndex'] : $baseport;
    $options = ['entPhysicalIndex' => $baseport];
    $port    = get_port_by_index_cache($device['device_id'], $ifIndex);
    if (is_array($port)) {
        $ifDescr                    = $port['ifDescr'];
        $options['measured_class']  = 'port';
        $options['measured_entity'] = $port['port_id'];
    } else {
        $ifDescr = snmp_get_oid($device, 'ifDescr.' . $index, 'IF-MIB');
    }

    foreach ($transeiver as $dmi_type => $entry) {
        $index    = "$baseport.$dmi_type";
        $oid_name = 'zyTransceiverDdmiCurrent';
        $oid_num  = '.1.3.6.1.4.1.890.1.15.3.84.1.2.1.6.' . $index;
        $value    = $entry[$oid_name];

        switch ($dmi_type) {
            case 1:
                $descr = $ifDescr . ' Temperature';
                $class = 'temperature';
                $scale = 0.01;
                break;
            case 2:
                $descr = $ifDescr . ' Voltage';
                $class = 'voltage';
                $scale = 0.01;
                break;
            case 3:
                $descr = $ifDescr . ' Tx Bias';
                $class = 'current';
                $scale = 0.00001;
                break;
            case 4:
                $descr = $ifDescr . ' Tx Power';
                if ($value > 100) {
                    // In MIB type and scale incorrect
                    $class = 'power';
                    $scale = 0.000000001;
                } else {
                    $class = 'dbm';
                    $scale = 0.01;
                }
                break;
            case 5:
                // In MIB type and scale incorrect
                $descr = $ifDescr . ' Rx Power';
                if ($value > 100) {
                    $class = 'power';
                    $scale = 0.000000001;
                } else {
                    $class = 'dbm';
                    $scale = 0.01;
                }
                break;
        }
        $descr .= " ($transeiver_name)";

        $limits = ['limit_high'      => $entry['zyTransceiverDdmiAlarmMax'] * $scale,
                   'limit_high_warn' => $entry['zyTransceiverDdmiWarnMax'] * $scale,
                   'limit_low_warn'  => $entry['zyTransceiverDdmiWarnMin'] * $scale,
                   'limit_low'       => $entry['zyTransceiverDdmiAlarmMin'] * $scale];

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
    }
}

// EOF
