<?php
/*
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// NETAPP-MIB

if (safe_empty($port_stats)) {
    // If not has standard IF-MIB table, use NETAPP specific tables
    $mib = 'NETAPP-MIB';

    //NETAPP-MIB::netifDescr.11 = STRING: "vega-01:MGMT_PORT_ONLY e0P"
    //NETAPP-MIB::netifDescr.12 = STRING: "vega-01:MGMT_PORT_ONLY e0M"
    //NETAPP-MIB::netifDescr.15 = STRING: "vega-01:a0m"
    //NETAPP-MIB::netifDescr.16 = STRING: "vega-01:a0m-40"
    print_cli($mib . '::netifDescr ');
    $netif_stat = snmpwalk_cache_oid($device, 'netifDescr', [], $mib);
    print_debug_vars($netif_stat);

    $flags        = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;
    $netport_stat = snmpwalk_cache_twopart_oid($device, 'netportLinkState', [], $mib, NULL, $flags);
    print_cli($mib . '::netportLinkState ');
    $netport_stat = snmpwalk_cache_twopart_oid($device, 'netportType', $netport_stat, $mib, NULL, $flags);
    print_cli($mib . '::netportType ');
    print_debug_vars($netport_stat);

    $mib_def = &$config['mibs'][$mib]['ports']['oids']; // Attach MIB options/translations
    //print_vars($mib_def);

    // Now rewrite to standard IF-MIB array
    foreach ($netif_stat as $ifIndex => $port) {
        if (str_contains($port['netifDescr'], ':')) {
            [$port['netportNode'], $port['netportPort']] = explode(':', $port['netifDescr'], 2);
        } else {
            $port['netportNode'] = '';
            $port['netportPort'] = $port['netifDescr'];
        }
        $port['netportPort'] = str_ireplace('MGMT_PORT_ONLY ', '', $port['netportPort']);

        if (isset($netport_stat[$port['netportNode']][$port['netportPort']])) {
            // ifDescr
            $oid                        = 'ifDescr';
            $port[$oid]                 = $port[$mib_def[$oid]['oid']];
            $port_stats[$ifIndex][$oid] = $port[$oid];

            // ifName, ifAlias
            $port_stats[$ifIndex]['ifName']  = strlen($port['netportNode']) ? $port['netportNode'] . ':' . $port['netportPort'] : $port['netportPort'];
            $port_stats[$ifIndex]['ifAlias'] = ''; // FIXME, I not found

            $netport = &$netport_stat[$port['netportNode']][$port['netportPort']];

            // ifType, ifOperStatus
            foreach (['ifType', 'ifOperStatus'] as $oid) {
                $port[$oid] = $netport[$mib_def[$oid]['oid']];
                if (isset($mib_def[$oid]['transform'])) {
                    // Translate to standard IF-MIB values
                    $port[$oid] = string_transform($port[$oid], $mib_def[$oid]['transform']);
                }
                $port_stats[$ifIndex][$oid] = $port[$oid];
            }
        }
    }
    //if (OBS_DEBUG > 1 && count($port_stats)) { print_vars($port_stats); }

    unset($netif_stat, $netport_stat, $netport, $flags, $ifIndex, $port);
}

// EOF
