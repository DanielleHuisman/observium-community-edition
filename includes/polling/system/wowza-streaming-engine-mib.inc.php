<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

//WOWZA-STREAMING-ENGINE-MIB::serverCounterCreationTime.1 = Counter64: 1476474933375
//WOWZA-STREAMING-ENGINE-MIB::serverCounterGetGUID.1 = STRING: 321b377f-1d50-4121-872a-c4846fc2d7d0
//WOWZA-STREAMING-ENGINE-MIB::serverCounterGetHTTPHeaderServer.1 = STRING: WowzaStreamingEngine/4.5.0.01
//WOWZA-STREAMING-ENGINE-MIB::serverCounterGetRTMPTHeaderServer.1 = STRING: FlashCom/3.5.7
//WOWZA-STREAMING-ENGINE-MIB::serverCounterGetServerGUID.1 = STRING: 321b377f-1d50-4121-872a-c4846fc2d7d0
//WOWZA-STREAMING-ENGINE-MIB::serverCounterGetSessionGUID.1 = STRING: 41b4bfd0-0690-4e92-977f-832a199e3dd9
//WOWZA-STREAMING-ENGINE-MIB::serverCounterGetTimeRunning.1 = STRING: 4 days 0 hours 51 minutes 27 seconds
//WOWZA-STREAMING-ENGINE-MIB::serverCounterGetVersion.1 = STRING: Wowza Streaming Engine 4 Monthly Edition 4.5.0.01 build18956

$data = snmp_get_multi_oid($device, 'serverCounterCreationTime.1', [], 'WOWZA-STREAMING-ENGINE-MIB');

if (is_array($data[1])) {
    $polled = round(snmp_endtime());

    // Override sysDescr, since it empty for wowza
    //$poll_device['sysDescr'] = $data[1]['serverCounterGetVersion'];

    if ($data[1]['serverCounterCreationTime'] > 0) {
        $poll_device['device_uptime'] = snmp_endtime() - ($data[1]['serverCounterCreationTime'] / 1000);
    }
}

//EOF
