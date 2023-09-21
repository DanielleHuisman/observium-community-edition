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

// Stats for S-Series

#F10-S-SERIES-CHASSIS-MIB::chStackUnitModelID.1 = STRING: S25-01-GE-24V
#F10-S-SERIES-CHASSIS-MIB::chStackUnitStatus.1 = INTEGER: ok(1)
#F10-S-SERIES-CHASSIS-MIB::chStackUnitDescription.1 = STRING: 24-port E/FE/GE with POE (SB)
#F10-S-SERIES-CHASSIS-MIB::chStackUnitCodeVersion.1 = STRING: 7.8.1.3
#F10-S-SERIES-CHASSIS-MIB::chStackUnitCodeVersionInFlash.1 = STRING:
#F10-S-SERIES-CHASSIS-MIB::chStackUnitSerialNumber.1 = STRING: DL2E9250002
#F10-S-SERIES-CHASSIS-MIB::chStackUnitUpTime.1 = Timeticks: (262804700) 30 days, 10:00:47.00

#chStackUnitCodeVersion.1 = 8.4.2.7
#chStackUnitProductOrder.1 = .........................
#chStackUnitProductOrder.1 = <FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF><FF>
#chStackUnitModelID.1 = S25-01-GE-24T
#chStackUnitSerialNumber.1 = DL2D8430012
#chStackUnitServiceTag.1 =

// Stats for C-Series

#F10-C-SERIES-CHASSIS-MIB::chType.0 = INTEGER: c300(7)
#F10-C-SERIES-CHASSIS-MIB::chChassisMode.0 = INTEGER: cseries1(4)
#F10-C-SERIES-CHASSIS-MIB::chSwVersion.0 = STRING: 8.2.1.2
#F10-C-SERIES-CHASSIS-MIB::chMacAddr.0 = STRING: 0:1:e8:3b:ea:b5
#F10-C-SERIES-CHASSIS-MIB::chSerialNumber.0 = STRING: TY000000491
#F10-C-SERIES-CHASSIS-MIB::chPartNum.0 = STRING: 7520029900
#F10-C-SERIES-CHASSIS-MIB::chProductRev.0 = STRING: 04
#F10-C-SERIES-CHASSIS-MIB::chVendorId.0 = STRING: 04
#F10-C-SERIES-CHASSIS-MIB::chDateCode.0 = STRING: "01182007"
#F10-C-SERIES-CHASSIS-MIB::chCountryCode.0 = STRING: "01"

// Stats for E-Series

#F10-CHASSIS-MIB::chSysSwRuntimeImgVersion.1.1 = STRING: 7.6.1.2
#F10-CHASSIS-MIB::chSysSwRuntimeImgVersion.8.1 = STRING: 7.6.1.2

// Dell
#DELL-NETWORKING-CHASSIS-MIB::dellNetStackUnitModelId.1 = INTEGER: s4048on(36)
#DELL-NETWORKING-CHASSIS-MIB::dellNetStackUnitStatus.1 = INTEGER: ok(1)
#DELL-NETWORKING-CHASSIS-MIB::dellNetStackUnitDescription.1 = STRING: 54-port TE/FG (SK-ON)
#DELL-NETWORKING-CHASSIS-MIB::dellNetStackUnitCodeVersion.1 = STRING: 9.10(0.1)
#DELL-NETWORKING-CHASSIS-MIB::dellNetStackUnitSerialNumber.1 = STRING: NA
#DELL-NETWORKING-CHASSIS-MIB::dellNetStackUnitUpTime.1 = Timeticks: (62055300) 7 days, 4:22:33.00

// FIXME. Z-Series

// Detect base version, since 9.10 Dell changed base MIBs, but NOT changed devices sysObjectID
// for 9.10 and above set $is_dell to TRUE
$is_dell = FALSE;
if (preg_match('/Application Software Version:\s+(?<version>(?<base>[\d\.]+)\S*)\s+Series: +(?<hardware>\S+)/', $poll_device['sysDescr'], $matches)) {
    //Dell Networking OS Operating System Version: 2.0 Application Software Version: 9.10(0.1P3) Series: S4810 Copyright (c) 1999-2016 by Dell Inc. All Rights Reserved. Build Time: Tue Jun 14 15:00:23 2016
    //Dell Networking OS Operating System Version: 2.0 Application Software Version: 9.10(0.1) Series: S4048-ON Copyright (c) 1999-2016 by Dell Inc. All Rights Reserved. Build Time: Wed May 11 23:07:56 2016
    //Dell Networking OS Operating System Version: 2.0 Application Software Version: 9.7(0.0P9) Series: S6000 Copyright (c) 1999-2015 by Dell Inc. All Rights Reserved. Build Time: Wed Jun 17 13:21:33 2015
    //Dell Force10 OS Operating System Version: 1.0 Application Software Version: 8.4.2.7 Series: S25N Copyright (c) 1999-2012 by Dell Inc. All Rights Reserved. Build Time: Thu Sep 27 14:03:07 PDT 2012
    $is_dell = version_compare($matches['base'], '9.10') >= 0;

    $hardware = $matches['hardware'];
    $version  = $matches['version'];
} else {
    $hardware = get_model_param($device, 'hardware', $poll_device['sysObjectID']);
}

if ($is_dell) {
    // DELL-NETWORKING-CHASSIS-MIB::dellNetNumStackUnits.0 = INTEGER: 1
    // DELL-NETWORKING-CHASSIS-MIB::dellNetStackUnitIndexNext.0 = INTEGER: 1

    // YES, this not joke snmpwalk instead snmpget, since some Dell devices return wrong 'No Such Instance currently exists at this OID'
    //$data     = snmp_get_multi_oid($device, 'dellNetStackUnitCodeVersion.1 dellNetStackUnitProductOrder.1 dellNetStackUnitModelId.1 dellNetStackUnitSerialNumber.1 dellNetStackUnitServiceTag.1', array(), 'DELL-NETWORKING-CHASSIS-MIB');
    $oids = ['dellNetStackUnitSerialNumber', 'dellNetStackUnitServiceTag'];
    if (!$hardware) {
        $oids[] = 'dellNetStackUnitProductOrder';
        $oids[] = 'dellNetStackUnitModelId';
    }
    if (!$version) {
        $oids[] = 'dellNetStackUnitCodeVersion';
    }
    $data = [];
    foreach ($oids as $oid) {
        $data = snmpwalk_cache_oid($device, $oid, $data, 'DELL-NETWORKING-CHASSIS-MIB');
    }
    if ($data[1]['dellNetStackUnitProductOrder'] && $data[1]['dellNetStackUnitProductOrder'] != 'NA' &&
        strlen($data[1]['dellNetStackUnitProductOrder']) < 20) {
        $hardware = $data[1]['dellNetStackUnitProductOrder'];
    } elseif (!$hardware) {
        $hardware = $data[1]['dellNetStackUnitModelId'];
    }
    if ($data[1]['dellNetStackUnitCodeVersion']) {
        $version = $data[1]['dellNetStackUnitCodeVersion'];
    }
    if ($version) {
        $icon = 'dell';
    } // Switch icon to Dell

    // Serial
    if ($data[1]['dellNetStackUnitSerialNumber'] && $data[1]['dellNetStackUnitSerialNumber'] != 'NA') {
        $serial = $data[1]['dellNetStackUnitSerialNumber'];
    } else {
        $serial = $data[1]['dellNetStackUnitServiceTag'];
    }
} elseif (strstr($poll_device['sysObjectID'], '.1.3.6.1.4.1.6027.1.3.')) {
    $data = snmp_get_multi_oid($device, 'chStackUnitCodeVersion.1 chStackUnitProductOrder.1 chStackUnitModelID.1 chStackUnitSerialNumber.1 chStackUnitServiceTag.1', [], 'F10-S-SERIES-CHASSIS-MIB');
    if ($data[1]['chStackUnitProductOrder'] && !str_starts($data[1]['chStackUnitProductOrder'], ['NA', '.']) &&
        preg_match('/^[A-Z]/', $data[1]['chStackUnitProductOrder'])) // This Oid can return unprintable chars
    {
        $hardware = $data[1]['chStackUnitProductOrder'];
    } elseif (!$hardware) {
        $hardware = $data[1]['chStackUnitModelID'];
    }
    $version = $data[1]['chStackUnitCodeVersion'];

    // Serial
    if ($data[1]['chStackUnitSerialNumber'] && $data[1]['chStackUnitSerialNumber'] != 'NA') {
        $serial = $data[1]['chStackUnitSerialNumber'];
    } else {
        $serial = $data[1]['chStackUnitServiceTag'];
    }
} elseif (strstr($poll_device['sysObjectID'], '.1.3.6.1.4.1.6027.1.2.')) {
    $version = snmp_get($device, 'chSwVersion.0', '-Oqvn', 'F10-C-SERIES-CHASSIS-MIB');
    $serial  = snmp_get($device, 'chSerialNumber.0', '-Oqvn', 'F10-C-SERIES-CHASSIS-MIB');
} elseif (strstr($poll_device['sysObjectID'], '.1.3.6.1.4.1.6027.1.4.')) {
    $data = snmp_get_multi_oid($device, 'chStackUnitCodeVersion.1 chStackUnitProductOrder.1 chStackUnitModelID.1 chStackUnitSerialNumber.1 chStackUnitServiceTag.1', [], 'F10-M-SERIES-CHASSIS-MIB');
    if ($data[1]['chStackUnitProductOrder'] && $data[1]['chStackUnitProductOrder'] != 'NA') {
        $hardware = $data[1]['chStackUnitProductOrder'];
    } elseif (!$hardware) {
        $hardware = $data[1]['chStackUnitModelID'];
    }
    $version = $data[1]['chStackUnitCodeVersion'];
    if ($data[1]['chStackUnitSerialNumber'] && $data[1]['chStackUnitSerialNumber'] != 'NA') {
        $serial = $data[1]['chStackUnitSerialNumber'];
    } else {
        $serial = $data[1]['chStackUnitServiceTag'];
    }
} elseif (!$version) {
    $version = snmp_get($device, 'chSysSwRuntimeImgVersion.1.1', '-Oqvn', 'F10-CHASSIS-MIB');
}

unset($data, $is_dell);

// EOF
