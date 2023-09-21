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

if (!is_device_mib($device, 'CISCO-CEF-MIB')) {
    return;
}

echo('Cisco CEF Switching Path: ');

$cefs_db = [];
foreach (dbFetchRows('SELECT * FROM `cef_switching` WHERE `device_id` = ?', [$device['device_id']]) as $ceftmp) {
    $cef_id           = $device['device_id'] . '-' . $ceftmp['entPhysicalIndex'] . '-' . $ceftmp['afi'] . '-' . $ceftmp['cef_index'];
    $cefs_db[$cef_id] = $ceftmp;
}

$cef_pfxs_db = [];
foreach (dbFetchRows('SELECT * FROM `cef_prefix` WHERE `device_id` = ?', [$device['device_id']]) as $pfx) {
    $cef_pfxs_db[$pfx['entPhysicalIndex']][$pfx['afi']] = $pfx['cef_pfx_id'];
}

// FIXME. Switch to mibs discovery
$device_context = $device;
if (empty($cefs_db)) {
    // Set retries to 0 for speedup first walking, only if previously polling also empty (DB empty)
    $device_context['snmp_retries'] = 0;
}
$cefs = snmpwalk_cache_threepart_oid($device_context, 'cefSwitchingStatsEntry', [], 'CISCO-CEF-MIB');
unset($device_context);

if (snmp_status()) {
    $cef_pfxs = snmpwalk_cache_twopart_oid($device, 'cefFIBSummaryEntry', [], 'CISCO-CEF-MIB');
    $polled   = time();

    // This entity names need only for cli printing,
    // do not poll in normal usage! Reduce polling time
    if (OBS_DEBUG && !is_array($entity_array)) {
        echo('Caching OIDs: ');
        $entity_array = [];
        echo(' entPhysicalDescr');
        $entity_array = snmpwalk_cache_oid($device, 'entPhysicalDescr', $entity_array, 'ENTITY-MIB');
        echo(' entPhysicalName');
        $entity_array = snmpwalk_cache_oid($device, 'entPhysicalName', $entity_array, 'ENTITY-MIB');
        echo(' entPhysicalModelName');
        $entity_array = snmpwalk_cache_oid($device, 'entPhysicalModelName', $entity_array, 'ENTITY-MIB');
    }
}
print_debug_vars($cefs);

foreach ($cefs as $entity => $afis) {
    if (OBS_DEBUG) {
        $entity_name = $entity_array[$entity]['entPhysicalName'] . ' - ' . $entity_array[$entity]['entPhysicalModelName'];
        print_cli("\n$entity $entity_name\n");
    } else {
        print_cli("\n$entity\n");
    }
    foreach ($afis as $afi => $paths) {
        print_cli(" |- $afi ");

        // Do Per-AFI entity summary

        if (!isset($cef_pfxs_db[$entity][$afi])) {
            $cef_pfx_id = dbInsert(['device_id' => $device['device_id'], 'entPhysicalIndex' => $entity, 'afi' => $afi], 'cef_prefix');
            echo('+');
        } else {
            $cef_pfx_id = $cef_pfxs_db[$entity][$afi];
        }
        unset($cef_pfxs_db[$entity][$afi]);

        $cef_pfx_update = [
          'cef_pfx_id' => $cef_pfx_id,
          'cef_pfx'    => $cef_pfxs[$entity][$afi]['cefFIBSummaryFwdPrefixes']
        ];
        dbUpdateRowMulti($cef_pfx_update, 'cef_prefix', 'cef_pfx_id');
        // $cef_pfx['update']['cef_pfx'] = $cef_pfxs[$entity][$afi]['cefFIBSummaryFwdPrefixes'];
        // dbUpdate($cef_pfx['update'], 'cef_prefix', '`device_id` = ? AND `entPhysicalIndex` = ? AND `afi` = ?', [ $device['device_id'], $entity, $afi ]);

        rrdtool_update_ng($device, 'cisco-cef-pfx', ['pfx' => $cef_pfxs[$entity][$afi]['cefFIBSummaryFwdPrefixes']], "$entity-$afi");

        //print_vars($cef_pfxs[$entity][$afi]);

        // Do Per-path statistics
        foreach ($paths as $path => $cef_stat) {
            print_cli(' | |-' . $path . ': ' . $cef_stat['cefSwitchingPath']);

            $cef_id = $device['device_id'] . '-' . $entity . '-' . $afi . '-' . $path;

            #if (dbFetchCell('SELECT COUNT(*) FROM `cef_switching` WHERE `device_id` = ? AND `entPhysicalIndex` = ? AND `afi` = ? AND `cef_index` = ?', array($device['device_id'], $entity, $afi, $path)) != '1')
            if (!isset($cefs_db[$cef_id])) {
                $cef_switching_id = dbInsert(['device_id' => $device['device_id'], 'entPhysicalIndex' => $entity, 'afi' => $afi, 'cef_index' => $path, 'cef_path' => $cef_stat['cefSwitchingPath']], 'cef_switching');
                $cef_entry        = dbFetchRow('SELECT * FROM `cef_switching` WHERE `cef_switching_id` = ?', [$cef_switching_id]);
                echo('+');
            } else {
                $cef_switching_id = $cefs_db[$cef_id]['cef_switching_id'];
                $cef_entry        = $cefs_db[$cef_id];
            }
            unset($cefs_db[$cef_id]);

            //$cef_entry = dbFetchRow('SELECT * FROM `cef_switching` WHERE `device_id` = ? AND `entPhysicalIndex` = ? AND `afi` = ? AND `cef_index` = ?', [ $device['device_id'], $entity, $afi, $path ]);

            // Copy HC to non-HC if they exist
            if (is_numeric($cef_stat['cefSwitchingHCDrop'])) {
                $cef_stat['cefSwitchingDrop'] = $cef_stat['cefSwitchingHCDrop'];
            }
            if (is_numeric($cef_stat['cefSwitchingHCPunt'])) {
                $cef_stat['cefSwitchingPunt'] = $cef_stat['cefSwitchingHCPunt'];
            }
            if (is_numeric($cef_stat['cefSwitchingHCPunt2Host'])) {
                $cef_stat['cefSwitchingPunt2Host'] = $cef_stat['cefSwitchingHCPunt2Host'];
            }

            $cef_stat['update']                   = ['cef_switching_id' => $cef_switching_id];
            $cef_stat['update']['drop']           = $cef_stat['cefSwitchingDrop'];
            $cef_stat['update']['punt']           = $cef_stat['cefSwitchingPunt'];
            $cef_stat['update']['punt2host']      = $cef_stat['cefSwitchingPunt2Host'];
            $cef_stat['update']['drop_prev']      = $cef_entry['drop'];
            $cef_stat['update']['punt_prev']      = $cef_entry['punt'];
            $cef_stat['update']['punt2host_prev'] = $cef_entry['punt2host'];
            $cef_stat['update']['updated']        = $polled;
            $cef_stat['update']['updated_prev']   = $cef_entry['updated'];

            dbUpdateRowMulti($cef_stat['update'], 'cef_switching', 'cef_switching_id');
            //dbUpdate($cef_stat['update'], 'cef_switching', '`device_id` = ? AND `entPhysicalIndex` = ? AND `afi` = ? AND `cef_index` = ?', [ $device['device_id'], $entity, $afi, $path ]);

            rrdtool_update_ng($device, 'cisco-cef-switching', [
              'drop'     => $cef_stat['cefSwitchingDrop'],
              'punt'     => $cef_stat['cefSwitchingPunt'],
              'hostpunt' => $cef_stat['cefSwitchingPunt2Host'],
            ],                "$entity-$afi-$path");

            echo(PHP_EOL);
        }
    }
}

//print_vars($cefs_db);

// Process Multi Update/Insert db
dbProcessMulti('cef_prefix');
dbProcessMulti('cef_switching');

foreach ($cefs_db as $cef_id => $ceftmp) {
    dbDelete('cef_switching', '`cef_switching_id` =  ?', [$ceftmp['cef_switching_id']]);
    echo('-');
}
foreach ($cef_pfxs_db as $afis) {
    foreach ($afis as $pfx_id) {
        dbDelete('cef_prefix', '`cef_pfx_id` =  ?', [$pfx_id]);
        echo('-');
    }
}

echo(PHP_EOL);

// EOF
