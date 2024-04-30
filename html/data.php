<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

include_once("../includes/observium.inc.php");

if (!$config['web_iframe'] && is_iframe()) {
    print_error_permission("Not allowed to run in a iframe!");
    die();
}

include($config['html_dir'] . "/includes/authenticate.inc.php");

if (is_numeric($_GET['id']) && ($config['allow_unauth_graphs'] || port_permitted($_GET['id']))) {
    $port   = get_port_by_id($_GET['id']);
    $device = device_by_id_cache($port['device_id']);
    //$title  = generate_device_link($device);
    //$title .= " :: Port  ".generate_port_link($port);
    $auth = TRUE;

    if ($device['os'] === 'netapp' && is_device_mib($device, "NETAPP-MIB")) {
        $oid_in_octets  = 'if64InOctets';
        $oid_out_octets = 'if64OutOctets';
        $mib = "NETAPP-MIB";
    } else {
        $oid_in_octets  = $port['port_64bit'] ? 'ifHCInOctets' : 'ifInOctets';
        $oid_out_octets = $port['port_64bit'] ? 'ifHCOutOctets' : 'ifOutOctets';
        $mib = "IF-MIB";
    }

    $data = snmp_get_multi_oid($device, [ $oid_in_octets . '.' . $port['ifIndex'], $oid_out_octets . '.' . $port['ifIndex'] ], [], $mib);

    printf("%lf|%s|%s\n", snmp_endtime(), $data[$port['ifIndex']][$oid_in_octets], $data[$port['ifIndex']][$oid_out_octets]);
} else {
    // not authenticated
    die("Unauthenticated");
}

// EOF
