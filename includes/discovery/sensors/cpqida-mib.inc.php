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

// Controllers

$oids = snmpwalk_cache_oid($device, 'cpqDaCntlrEntry', [], 'CPQIDA-MIB');

foreach ($oids as $index => $entry) {
    if (isset($entry['cpqDaCntlrBoardStatus'])) {
        $hardware = rewrite_cpqida_hardware($entry['cpqDaCntlrModel']);
        $descr    = $hardware . ' (' . $entry['cpqDaCntlrHwLocation'] . ')';

        $oid    = ".1.3.6.1.4.1.232.3.2.2.1.1.10." . $index;
        $status = $entry['cpqDaCntlrBoardStatus'];

        discover_status_ng($device, $mib, 'cpqDaCntlrBoardStatus', $oid, $index, 'cpqDaCntlrBoardStatus', $descr . ' Board Status', $status, ['entPhysicalClass' => 'controller']);

        $oid    = ".1.3.6.1.4.1.232.3.2.2.1.1.6." . $index;
        $status = $entry['cpqDaCntlrCondition'];

        discover_status_ng($device, $mib, 'cpqDaCntlrCondition', $oid, $index, 'cpqDaCntlrCondition', $descr . ' Condition', $status, ['entPhysicalClass' => 'controller']);

        if ($entry['cpqDaCntlrCurrentTemp'] > 0) {
            $oid   = ".1.3.6.1.4.1.232.3.2.2.1.1.32." . $index;
            $value = $entry['cpqDaCntlrCurrentTemp'];
            $descr = $hardware . ' (' . $entry['cpqDaCntlrHwLocation'] . ')';

            $options = ['rename_rrd' => "cpqida-cntrl-temp-cpqDaCntlrEntry.%index%"];
            discover_sensor_ng($device, 'temperature', $mib, 'cpqDaCntlrCurrentTemp', $oid, $index, NULL, $descr, 1, $value, $options);
        }
    }
}

// Physical Disks

$oids = snmpwalk_cache_oid($device, 'cpqDaPhyDrv', [], 'CPQIDA-MIB');

foreach ($oids as $index => $entry) {

    $name = $entry['cpqDaPhyDrvLocationString'];
    if (!empty($entry['cpqDaPhyDrvModel'])) {
        $name .= ' (' . trim($entry['cpqDaPhyDrvModel']) . ')';
    }
    if (!empty($entry['cpqDaPhyDrvSerialNum'])) {
        $name .= ' (' . trim($entry['cpqDaPhyDrvSerialNum']) . ')';
    }

    if ($entry['cpqDaPhyDrvTemperatureThreshold'] > 0) {
        $descr   = $name; // "HDD ".$entry['cpqDaPhyDrvBay'];
        $oid     = ".1.3.6.1.4.1.232.3.2.5.1.1.70." . $index;
        $value   = $entry['cpqDaPhyDrvCurrentTemperature'];
        $options = ['limit_high' => $entry['cpqDaPhyDrvTemperatureThreshold']];

        $options['rename_rrd'] = "cpqida-cpqDaPhyDrv.%index%";
        discover_sensor_ng($device, 'temperature', $mib, 'cpqDaPhyDrvCurrentTemperature', $oid, $index, NULL, $descr, 1, $value, $options);

    }

    $oid   = '.1.3.6.1.4.1.232.3.2.5.1.1.6.' . $index;
    $state = $entry['cpqDaPhyDrvStatus'];

    discover_status_ng($device, $mib, 'cpqDaPhyDrvStatus', $oid, $index, 'cpqDaPhyDrvStatus', $name . ' Status', $state, ['entPhysicalClass' => 'physicalDrive']);

    $oid   = '.1.3.6.1.4.1.232.3.2.5.1.1.37.' . $index;
    $state = $entry['cpqDaPhyDrvCondition'];

    discover_status_ng($device, $mib, 'cpqDaPhyDrvCondition', $oid, $index, 'cpqDaPhyDrvCondition', $name . ' Condition', $state, ['entPhysicalClass' => 'physicalDrive']);

    $oid   = '.1.3.6.1.4.1.232.3.2.5.1.1.57.' . $index;
    $state = $entry['cpqDaPhyDrvSmartStatus'];

    discover_status_ng($device, $mib, 'cpqDaPhyDrvSmartStatus', $oid, $index, 'cpqDaPhyDrvSmartStatus', $name . ' S.M.A.R.T.', $state, ['entPhysicalClass' => 'physicalDrive']);

    $oid = '.1.3.6.1.4.1.232.3.2.5.1.1.9.' . $index;
    discover_counter($device, 'lifetime', $mib, 'cpqDaPhyDrvRefHours', $oid, $index, $name . ' Hours', 3600, $entry['cpqDaPhyDrvRefHours'], ['entPhysicalClass' => 'physicalDrive']);
}

// Logical Disks

$oids = snmpwalk_cache_oid($device, 'cpqDaLogDrv', [], 'CPQIDA-MIB');

foreach ($oids as $index => $entry) {

    $controller = rewrite_cpqida_hardware($entry['cpqDaCntlrModel']) . ' #' . $entry['cpqDaLogDrvCntlrIndex'];
    $name       = 'Logical Drive ' . $entry['cpqDaLogDrvIndex'];
    if (!safe_empty($entry['cpqDaLogDrvOsName'])) {
        $name .= ' (' . $entry['cpqDaLogDrvOsName'] . $controller . ')';
    } else {
        $name .= ' (' . $controller . ')';
    }

    $oid   = '.1.3.6.1.4.1.232.3.2.3.1.1.4.' . $index;
    $state = $entry['cpqDaLogDrvStatus'];

    discover_status_ng($device, $mib, 'cpqDaLogDrvStatus', $oid, $index, 'cpqDaLogDrvStatus', $name . ' Status', $state, ['entPhysicalClass' => 'logicalDrive']);

    $oid   = '.1.3.6.1.4.1.232.3.2.3.1.1.11.' . $index;
    $state = $entry['cpqDaLogDrvCondition'];

    discover_status_ng($device, $mib, 'cpqDaLogDrvCondition', $oid, $index, 'cpqDaLogDrvCondition', $name . ' Condition', $state, ['entPhysicalClass' => 'logicalDrive']);

    // Do not ignore wrong 4294967295 value, because controller no other state when rebuild or initialization
    // CPQIDA-MIB::cpqDaLogDrvPercentRebuild.3.1 = Gauge32: 4294967295
    //if ($entry['cpqDaLogDrvPercentRebuild'] < 4294967295) {
    $descr   = $name . ' Rebuild';
    $oid     = ".1.3.6.1.4.1.232.3.2.3.1.1.12." . $index;
    $value   = $entry['cpqDaLogDrvPercentRebuild'];
    $options = ['limit_low' => 15, 'limit_low_warn' => 50];
    discover_sensor_ng($device, 'progress', $mib, 'cpqDaLogDrvPercentRebuild', $oid, $index, NULL, $descr, 1, $value, $options);
    //}
    // CPQIDA-MIB::cpqDaLogDrvRPIPercentComplete.3.1 = Gauge32: 0
    //if ($entry['cpqDaLogDrvRPIPercentComplete'] < 4294967295) {
    $descr   = $name . ' Undergoing Parity Initialization';
    $oid     = ".1.3.6.1.4.1.232.3.2.3.1.1.23." . $index;
    $value   = $entry['cpqDaLogDrvRPIPercentComplete'];
    $options = ['limit_low' => 15, 'limit_low_warn' => 50];
    discover_sensor_ng($device, 'progress', $mib, 'cpqDaLogDrvRPIPercentComplete', $oid, $index, NULL, $descr, 1, $value, $options);
    //}
}

// EOF
