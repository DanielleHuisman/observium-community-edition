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

if (preg_match('/ROS Version V(?<ros_version>\d[\w\.]+)(?<hw1>[\w\ -]+)? Software, Version (?<hw2>[\w\ \-&\(\)]+) V(?<version>\d[\w\.]+)/', $poll_device['sysDescr'], $matches)) {
    // ZXR10 ROS Version V4.8.11.01 ZXR10 T64G Software, Version ZXR10 G-Series&8900&6900 V2.8.01.C.27.P06 Copyright (c) 2001-2009 by ZTE Corporation Compiled Sep 11 2009, 17:20:19
    // ZXR10 ROS Version V4.8.12 ZXUAS 10800E Software, Version ZXUAS 10800E V2.8.01.B.36P2 Copyright (c) 2005-2010 by ZTE Corporation Compiled Jun 5 2009, 14:16:34
    // ZXR10 ROS Version V4.8.35 Software, Version (null) ZXA10 C300 V1.3.0 Copyright (c) by ZTE Corporation Compiled
    // ZXR10 ROS Version V4.08.24R2 ZXR10_5928E-FI Software, Version ZXR10 5900E&5100E V2.8.23.C.16.P11, Product Version V1.2, Copyright (c) 2010-2015 by ZTE Corporation Compiled Oct  8 2015, 15:35:19

    $version = $matches['version'] . ', ROS ' . $matches['ros_version'];
    if (isset($matches['hw1'])) {
        $hw_array = explode(' ', $matches['hw1']);
    } else {
        $hw_array = explode(' ', $matches['hw2']);
    }
    $hardware = end($hw_array);
} elseif (preg_match('/Router Operating System Software [\w\ ]*ZTE Corporation(?:\ ZXROS)?\ +V(?<ros_version>\d[\w\.]+) (?<hardware>\w+) V(?<version>\d[\w\.]+)/', $poll_device['sysDescr'], $matches)) {
    // ZXR10 Router Operating System Software Nanjing Institute of ZTE Corporation V4.6.02.A GER V2.6.02.A.53.P09 Compiled: Oct 15 2008, 09:53:12
    // ZXR10 Router Operating System Software ZTE Corporation ZXROS V4.6.02 ZXR10_3228 V2.6.02 Compiled: Dec 21 2005, 18:35:11

    $version  = $matches['version'] . ', ROS ' . $matches['ros_version'];
    $hardware = $matches['hardware'];
} elseif (preg_match('Router Operating System Software ZTE Corporation (?<hw1>[\w\ ]+)(?:\/(?<hw2>[\w\ ]+))?\ +V(?<version>\d[\w\.]+) [\w\ ]+ZXROS V(?<ros_version>\d[\w\.]+)/', $poll_device['sysDescr'], $matches)) {
    // ZXR10 Router Operating System Software ZTE Corporation Based on ZXROS Compiled:
    // ZXR10 Router Operating System Software ZTE Corporation ZXSS10 B100 V2.00.60.P4.e08 Based on ZXROS V4.6.02.a Compiled:Jul 1 2011, 04:06:56
    // ZXR10 Router Operating System Software ZTE Corporation ZXSS10 B200/ZXUN B200 ATCA V2.13.10.P21.B02 Based on ZXROS V4.6.02.a Compiled:Aug 18 2015, 00:36:54

    $version  = $matches['version'] . ', ROS ' . $matches['ros_version'];
    $hw_array = explode(' ', $matches['hw1']);
    $hardware = end($hw_array);
} elseif (preg_match('/^ZTE (ZXR10 )?(?<hardware>[\w-]+(?: STACK)?) Software, .+ Version: V(?<version>\d[\w\.]+)/', $poll_device['sysDescr'], $matches)) {
    // ZTE ZXR10 8905E Software, 8900&8900E Version: V3.01.01.B15, RELEASE SOFTWARE
    // ZTE 5952E Software, 5900 Version: V3.00.11.B18,  RELEASE SOFTWARE
    // ZTE 5950-56TM-H Software, 5900 Version: V3.00.11.B18,  RELEASE SOFTWARE
    // ZTE ZXR10 5960 STACK Software, 5900 Version: V3.02.20.B32.P02, RELEASE SOFTWARE

    $version  = $matches['version'];
    $hardware = $matches['hardware'];
} elseif (preg_match('/^ZX\w+ (?<hardware>[\w\-]+), .+? Software Version:(?: \w+)? [\w\(\)]+[vV\s]?(?<version>\d[\w\.]+)/', $poll_device['sysDescr'], $matches)) {
    // ZXR10 6802, ZTE ZXR10 Software Version: 6800v1.00.20R2(1.1.4)
    // ZXR10 M6000-08, ZTE ZXR10 Software Version: M6000v1.00.30(1.0.76)
    // ZXR10 T8000, ZTE ZXR10 Software Version: T8000v2.00.20(1.13.1)
    // ZXR10 xGW-16, ZTE ZXR10 Software Version: ZXUN xGW(CL)V4.13.11.P10.B1(1.0.0)
    // ZXUN xGW-16, ZTE ZXUN Software Version: ZXUN xGW(GUL)V4.14.20.P1.B3(4.1420.1)
    // ZXR10 1800-2S, ZTE ZXR10 Software Version: ZSRV2 V3.00.30(1.2.2)

    $version  = $matches['version'];
    $hardware = $matches['hardware'];
} elseif (preg_match('/Ethernet Switch(?: +\w+)? (?<hardware>[\w\-]+)(?:\/(?<hw2>[\w\-]+))?, Version: V(?<version>\d[\w\.]+)/', $poll_device['sysDescr'], $matches)) {
    // ZTE Ethernet Switch ZXR10 2826S/2826S-LE, Version: V1.1.12.S
    // ZTE Ethernet Switch ZXR10 2826S-EI, Version: V1.1.20.f
    // ZTE Ethernet Switch ZXR10 2928E, Version: V2.05.10B18
    // ZTE Ethernet Switch 2850-26TM, Version: V1.1.12.U
    // ZTE Ethernet Switch  ZXR10 2928E, Version: V2.05.10B18

    $version  = $matches['version'];
    $hardware = $matches['hardware'];
} elseif (preg_match('/^(?:(?<hw1>W\w+) )?(?:ZXV10_(?<hw2>W\w+)_)?V(?<version>\d[\w\.]+)/', $poll_device['sysDescr'], $matches)) {
    // ZXV10_W901_V1.0.01Z
    // W660A ZXV10_WLAN_V1.1.00Z
    // W812 V1.0.03_9A
    // W812 ZXV10_WLAN_V1.0.02_9A

    $version = $matches['version'];
    if (isset($matches['hw1'])) {
        $hardware = $matches['hw1'];
    } else {
        $hardware = $matches['hw2'];
    }
} elseif (preg_match('/^ZXR10 (?<hardware>[\w\-]+) NOS (?<version>\d[\w\.]+)/', $poll_device['sysDescr'], $matches)) {
    // ZXR10 3904 NOS 9.05
    // ZXR10 3904 NOS 9.07p5.ospf2/DS-P6-068-E0

    $version  = $matches['version'];
    $hardware = $matches['hardware'];
} elseif (preg_match('/^ZX\w+ (?<hardware>[\w\-]+)/', $poll_device['sysDescr'], $matches)) {
    // All other
    // ZXA10 F402 Ethernet Passive Optical Network Access Device
    // ZXA10 F803-8
    // ZXDSL 8426

    //$version  = $matches['version'];
    $hardware = $matches['hardware'];
}

// EOF
