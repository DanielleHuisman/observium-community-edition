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

$vp_rows = dbFetchRows("SELECT * FROM `ports` AS P, `juniAtmVp` AS J WHERE P.`device_id` = ? AND J.port_id = P.port_id", [$device['device_id']]);

if (count($vp_rows)) {
    $vp_cache = [];
    $vp_cache = snmpwalk_cache_oid($device, "juniAtmVpStatsInCells", $vp_cache, "Juniper-UNI-ATM-MIB");
    $vp_cache = snmpwalk_cache_oid($device, "juniAtmVpStatsInPackets", $vp_cache, "Juniper-UNI-ATM-MIB");
    $vp_cache = snmpwalk_cache_oid($device, "juniAtmVpStatsInPacketOctets", $vp_cache, "Juniper-UNI-ATM-MIB");
    $vp_cache = snmpwalk_cache_oid($device, "juniAtmVpStatsInPacketErrors", $vp_cache, "Juniper-UNI-ATM-MIB");
    $vp_cache = snmpwalk_cache_oid($device, "juniAtmVpStatsOutCells", $vp_cache, "Juniper-UNI-ATM-MIB");
    $vp_cache = snmpwalk_cache_oid($device, "juniAtmVpStatsOutPackets", $vp_cache, "Juniper-UNI-ATM-MIB");
    $vp_cache = snmpwalk_cache_oid($device, "juniAtmVpStatsOutPacketOctets", $vp_cache, "Juniper-UNI-ATM-MIB");
    $vp_cache = snmpwalk_cache_oid($device, "juniAtmVpStatsOutPacketErrors", $vp_cache, "Juniper-UNI-ATM-MIB");

    echo("Checking JunOSe ATM vps: ");

    foreach ($vp_rows as $vp) {
        echo(".");

        rrdtool_update_ng($device, 'junos-atm-vp', [
          'incells'         => $vp_cache[$vp['ifIndex'] . "." . $vp['vp_id']]['juniAtmVpStatsInCells'],
          'outcells'        => $vp_cache[$vp['ifIndex'] . "." . $vp['vp_id']]['juniAtmVpStatsOutCells'],
          'inpackets'       => $vp_cache[$vp['ifIndex'] . "." . $vp['vp_id']]['juniAtmVpStatsInPackets'],
          'outpackets'      => $vp_cache[$vp['ifIndex'] . "." . $vp['vp_id']]['juniAtmVpStatsOutPackets'],
          'inpacketoctets'  => $vp_cache[$vp['ifIndex'] . "." . $vp['vp_id']]['juniAtmVpStatsInPacketOctets'],
          'outpacketoctets' => $vp_cache[$vp['ifIndex'] . "." . $vp['vp_id']]['juniAtmVpStatsOutPacketOctets'],
          'inpacketerrors'  => $vp_cache[$vp['ifIndex'] . "." . $vp['vp_id']]['juniAtmVpStatsInPacketErrors'],
          'outpacketerrors' => $vp_cache[$vp['ifIndex'] . "." . $vp['vp_id']]['juniAtmVpStatsOutPacketErrors'],
        ],                $vp['ifIndex'] . '-' . $vp['vp_id']);
    }

    echo("\n");

    unset($vp_cache);
}

// EOF
