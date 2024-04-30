<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Untagged/primary port vlans
$port_module = 'vlan';

if (!$ports_modules[$port_module]) {
    // Module disabled
    return;
}

// BISON-ROUTER-MIB::vifIndex.2 = INTEGER: 2
// BISON-ROUTER-MIB::vifIndex.3 = INTEGER: 3
// BISON-ROUTER-MIB::vifIndex.4 = INTEGER: 4
// BISON-ROUTER-MIB::vifIndex.5 = INTEGER: 5
// BISON-ROUTER-MIB::vifName.2 = STRING: vl4055
// BISON-ROUTER-MIB::vifName.3 = STRING: vl4054
// BISON-ROUTER-MIB::vifName.4 = STRING: vl2701
// BISON-ROUTER-MIB::vifName.5 = STRING: vl2523
// BISON-ROUTER-MIB::vifPort.2 = INTEGER: 2
// BISON-ROUTER-MIB::vifPort.3 = INTEGER: 2
// BISON-ROUTER-MIB::vifPort.4 = INTEGER: 2
// BISON-ROUTER-MIB::vifPort.5 = INTEGER: 2
// BISON-ROUTER-MIB::vifSvid.2 = INTEGER: 0
// BISON-ROUTER-MIB::vifSvid.3 = INTEGER: 0
// BISON-ROUTER-MIB::vifSvid.4 = INTEGER: 0
// BISON-ROUTER-MIB::vifSvid.5 = INTEGER: 0
// BISON-ROUTER-MIB::vifCvid.2 = INTEGER: 4055
// BISON-ROUTER-MIB::vifCvid.3 = INTEGER: 4054
// BISON-ROUTER-MIB::vifCvid.4 = INTEGER: 2701
// BISON-ROUTER-MIB::vifCvid.5 = INTEGER: 2523
// BISON-ROUTER-MIB::vifRxPkts.2 = Counter64: 1861
// BISON-ROUTER-MIB::vifRxPkts.3 = Counter64: 538547659
// BISON-ROUTER-MIB::vifRxPkts.4 = Counter64: 102280157
// BISON-ROUTER-MIB::vifRxPkts.5 = Counter64: 103714991
// BISON-ROUTER-MIB::vifTxPkts.2 = Counter64: 0
// BISON-ROUTER-MIB::vifTxPkts.3 = Counter64: 214985845
// BISON-ROUTER-MIB::vifTxPkts.4 = Counter64: 285038807
// BISON-ROUTER-MIB::vifTxPkts.5 = Counter64: 250449672
// BISON-ROUTER-MIB::vifRxOctets.2 = Counter64: 193544
// BISON-ROUTER-MIB::vifRxOctets.3 = Counter64: 698141921427
// BISON-ROUTER-MIB::vifRxOctets.4 = Counter64: 47489537318
// BISON-ROUTER-MIB::vifRxOctets.5 = Counter64: 50463194600
// BISON-ROUTER-MIB::vifTxOctets.2 = Counter64: 0
// BISON-ROUTER-MIB::vifTxOctets.3 = Counter64: 99772810113
// BISON-ROUTER-MIB::vifTxOctets.4 = Counter64: 380461278833
// BISON-ROUTER-MIB::vifTxOctets.5 = Counter64: 317404718753

// Base vlan IDs
$ports_vlans_oids = snmpwalk_cache_oid($device, 'vifCvid', [], 'BISON-ROUTER-MIB');

if (snmp_status()) {
    echo("vifCvid vifPort ");

    $ports_vlans_oids = snmpwalk_cache_oid($device, 'vifPort', $ports_vlans_oids, 'BISON-ROUTER-MIB');

    print_debug_vars($ports_vlans_oids);

    $vlan_rows = [];
    foreach ($ports_vlans_oids as $vifIndex => $vlan) {
        $ifIndex     = $vlan['vifPort'];
        $vlan_num    = $vlan['vifCvid'];
        $trunk       = 'access';
        $vlan_rows[] = [ $ifIndex, $vlan_num, $trunk ];

        // Set Vlan and Trunk
        $port_stats[$ifIndex]['ifVlan']  = $vlan_num;
        $port_stats[$ifIndex]['ifTrunk'] = $trunk;

    }

    $headers = ['%WifIndex%n', '%WVlan%n', '%WTrunk%n'];
    print_cli_table($vlan_rows, $headers);
}

// EOF
