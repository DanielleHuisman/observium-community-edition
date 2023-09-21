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

// Poll mem for load memory utilisation stats on UNIX-like hosts running UCD/Net-SNMPd
#UCD-SNMP-MIB::memIndex.0 = INTEGER: 0
#UCD-SNMP-MIB::memErrorName.0 = STRING: swap
#UCD-SNMP-MIB::memTotalSwap.0 = INTEGER: 32762248 kB
#UCD-SNMP-MIB::memAvailSwap.0 = INTEGER: 32199396 kB
#UCD-SNMP-MIB::memTotalReal.0 = INTEGER: 8187696 kB
#UCD-SNMP-MIB::memAvailReal.0 = INTEGER: 1211056 kB
#UCD-SNMP-MIB::memTotalFree.0 = INTEGER: 33410452 kB
#UCD-SNMP-MIB::memMinimumSwap.0 = INTEGER: 16000 kB
#UCD-SNMP-MIB::memBuffer.0 = INTEGER: 104388 kB
#UCD-SNMP-MIB::memCached.0 = INTEGER: 2595556 kB
#UCD-SNMP-MIB::memShared.0 = INTEGER: 595556 kB
#UCD-SNMP-MIB::memSwapError.0 = INTEGER: noError(0)
#UCD-SNMP-MIB::memSwapErrorMsg.0 = STRING:

//$snmpdata = snmpwalk_cache_oid($device, "mem", array(), "UCD-SNMP-MIB");
//$data = $snmpdata[0];

$data = snmp_get_multi_oid($device, 'memTotalReal.0 memAvailReal.0 memBuffer.0 memCached.0', [], 'UCD-SNMP-MIB');
$data = $data[0];

//if(is_array($data) && isset($data['memTotalReal']) && isset($data['memBuffer']) && isset($data['memCached']) && isset($data['memAvailReal']) &&
//   $data['memCached'] >= 0 && $data['memBuffer'] >= 0 && $data['memAvailReal'] >= 0 && $data['memTotalReal'] >= 0)
if (is_array($data) && isset($data['memTotalReal']) && isset($data['memAvailReal']) &&
    $data['memAvailReal'] >= 0 && $data['memTotalReal'] >= 0) {

    $mempool['total'] = $data['memTotalReal'] * 1024;
    $mempool_hc       = 0;

    /* BEGIN REDHAT BUG */
    // CLEANME. remove in r11000, but not before CE 20.8
    /**
     * New RedHat net-snmp version updated memory calculations:
     * https://bugzilla.redhat.com/show_bug.cgi?id=1250060
     * https://bugzilla.redhat.com/show_bug.cgi?id=1779609
     * See: https://jira.observium.org/browse/OBS-3090
     *      https://jira.observium.org/browse/OBS-3100
     *
     * 2019-12-09 - Josef Ridky <jridky@redhat.com> - 1:5.7.2-47
     * - revert calculation of free space (#1779609)
     * ...
     * 2019-05-22 - Josef Ridky <jridky@redhat.com> - 1:5.7.2-43
     * - fix available memory calculation (#1250060)
     */
    $ucd_version = snmp_get_multi_oid($device, 'versionTag.0 versionCDate.0 versionConfigureOptions.0', [], 'UCD-SNMP-MIB');
    $ucd_version = $ucd_version[0];
    // Detect if there redhat locally changed net-snmp package, this patch was added at 2019-05-22
    // UCD-SNMP-MIB::versionTag.0 = 5.7.2
    // UCD-SNMP-MIB::versionCDate.0 = Wed Sep 18 15:12:36 2019
    // UCD-SNMP-MIB::versionConfigureOptions.0 =  '--build=x86_64-redhat-linux-gnu' '--host=x86_64-redhat-linux-gnu' '--program-prefix=' '--disable-dependency-tracking' '--prefix=/usr' '--exec-prefix=/usr' '--bindir=/usr/bin' '--sbindir=/usr/sbin' '--datadir=/usr/share' '--includedir=/usr/include' '--libdir=/usr/lib64' '--libexecdir=/usr/libexec' '--localstatedir=/var' '--sharedstatedir=/var/lib' '--mandir=/usr/share/man' '--infodir=/usr/share/info' '--disable-static' '--enable-shared' '--with-cflags=-O2 -g -pipe -Wall -Wp,-D_FORTIFY_SOURCE=2 -fexceptions -fstack-protector-strong --param=ssp-buffer-size=4 -grecord-gcc-switches   -m64 -mtune=generic -D_RPM_4_4_COMPAT' '--with-ldflags=-Wl,-z,relro -Wl,-z,now' '--with-sys-location=Unknown' '--with-logfile=/var/log/snmpd.log' '--with-persistent-directory=/var/lib/net-snmp' '--with-mib-modules=host agentx smux      ucd-snmp/diskio tcp-mib udp-mib mibII/mta_sendmail      ip-mib/ipv4InterfaceTable ip-mib/ipv6InterfaceTable      ip-mib/ipAddressPrefixTable/ipAddressPrefixTable      ip-mib/ipDefaultRouterTable/ipDef
    if (is_array($ucd_version) && str_contains_array($ucd_version['versionConfigureOptions'], '-redhat-') &&
        version_compare($ucd_version['versionTag'], '5.7.2', '>=')
        && strtotime($ucd_version['versionCDate']) > 1558483200) {
        if (strtotime($ucd_version['versionCDate']) >= 1575849600) {
            del_entity_attrib('device', $device['device_id'], 'ucd_memory_bad');
        } else {
            $mempool['free'] = $data['memAvailReal'] * 1024;
            $mempool_hc      = 1;
            set_entity_attrib('device', $device['device_id'], 'ucd_memory_bad', 1); // Set this attrib for poller/graph
        }
    }
    /* END REDHAT BUG */

    if ($mempool_hc === 0) {
        $mempool['free'] = ($data['memAvailReal'] + ($data['memBuffer'] + $data['memCached'])) * 1024;
    }
    $mempool['used'] = $mempool['total'] - $mempool['free'];
    $mempool['perc'] = percent($mempool['free'], $mempool['total'], FALSE);

    $index = '0';
    $descr = 'Physical Memory';

    discover_mempool($valid['mempool'], $device, $index, 'UCD-SNMP-MIB', $descr, 1, $mempool['total'], $mempool['used'], $mempool_hc);
}

/**
 *
 * This is already collected from HOST-RESOURCES-MIB accurately.
 *
 * $data = snmp_get_multi_oid($device, 'memTotalSwap.0 memAvailSwap.0', array(), 'UCD-SNMP-MIB');
 * $data = $data[0];
 *
 * if(is_array($data) && isset($data['memTotalSwap']) && isset($data['memAvailSwap']) && $data['memTotalSwap'] != 0)
 * {
 * $total = $data['memTotalSwap'] * 1024;
 * $free  = $data['memAvailSwap'] * 1024;
 * $used  = $total - $free;
 * $perc  = $free / $total * 100;
 *
 * $index = 'swap';
 * $descr = 'Swap memory';
 *
 * discover_mempool($valid['mempool'], $device, $index, 'UCD-SNMP-MIB', $descr, "1", $total, $used);
 * }
 **/

// EOF
