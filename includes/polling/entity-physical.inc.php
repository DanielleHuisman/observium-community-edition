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

if (is_device_mib($device, 'CISCO-CAT6K-CROSSBAR-MIB')) {
    if (preg_match('/[a-z](60|65|76)\d{2}/i', $device['hardware'])) {
        // MIBs supported by the Catalyst 6000 series switches (WS-C6006, WS-C6009, WS-C6503,
        // WS-C6506, WS-C6509, WS-C6509NEB, WS-C6513, CISCO7603, CISCO7606, OS-R7609)

        echo('Cisco Cat6xxx/76xx Crossbar: ');

        $mod_stats = snmpwalk_cache_oid($device, 'cc6kxbarModuleModeTable', [], 'CISCO-CAT6K-CROSSBAR-MIB');
        if ($GLOBALS['snmp_status']) {
            $chan_stats = snmpwalk_cache_oid($device, 'cc6kxbarModuleChannelTable', [], 'CISCO-CAT6K-CROSSBAR-MIB');
            $chan_stats = snmpwalk_cache_oid($device, 'cc6kxbarStatisticsTable', $chan_stats, 'CISCO-CAT6K-CROSSBAR-MIB');

            foreach ($mod_stats as $index => $entry) {
                $group = 'c6kxbar';
                foreach ($entry as $key => $value) {
                    $subindex                                           = NULL;
                    $entPhysical_state[$index][$subindex][$group][$key] = $value;
                }
            }

            foreach ($chan_stats as $index => $entry) {
                [$index, $subindex] = explode('.', $index, 2);
                $group = 'c6kxbar';
                foreach ($entry as $key => $value) {
                    $entPhysical_state[$index][$subindex][$group][$key] = $value;
                }

                rrdtool_update_ng($device, 'c6kxbar', [
                  'inutil'     => $entry['cc6kxbarStatisticsInUtil'],
                  'oututil'    => $entry['cc6kxbarStatisticsOutUtil'],
                  'outdropped' => $entry['cc6kxbarStatisticsOutDropped'],
                  'outerrors'  => $entry['cc6kxbarStatisticsOutErrors'],
                  'inerrors'   => $entry['cc6kxbarStatisticsInErrors'],
                ],                "$index-$subindex");
            }
            #print_vars($entPhysical_state);
        }
    }

    // Remove/Update Entity state
    foreach (dbFetchRows('SELECT * FROM `entPhysical-state` WHERE `device_id` = ?', [$device['device_id']]) as $entity) {
        if (!isset($entPhysical_state[$entity['entPhysicalIndex']][$entity['subindex']][$entity['group']][$entity['key']])) {
            dbDelete('entPhysical-state', '`device_id` = ? AND `entPhysicalIndex` = ? AND `subindex` = ? AND `group` = ? AND `key` = ?',
                     [$device['device_id'], $entity['entPhysicalIndex'], $entity['subindex'], $entity['group'], $entity['key']]);
        } else {
            if ($entPhysical_state[$entity['entPhysicalIndex']][$entity['subindex']][$entity['group']][$entity['key']] != $entity['value']) {
                #      echo('no match!');
                dbUpdate(['value' => $entPhysical_state[$entity['entPhysicalIndex']][$entity['subindex']][$entity['group']][$entity['key']]], 'entPhysical-state', '`entPhysical_state_id` = ?', [$entity['entPhysical_state_id']]);
            }
            unset($entPhysical_state[$entity['entPhysicalIndex']][$entity['subindex']][$entity['group']][$entity['key']]);
        }
    }
    // End Remove/Update Entity Attribs

    // Insert state
    foreach ($entPhysical_state as $epi => $entity) {
        foreach ($entity as $subindex => $si) {
            foreach ($si as $group => $ti) {
                foreach ($ti as $key => $value) {
                    dbInsert(['device_id' => $device['device_id'], 'entPhysicalIndex' => $epi, 'subindex' => $subindex, 'group' => $group, 'key' => $key, 'value' => $value], 'entPhysical-state');
                }
            }
        }
    }
    // End Insert Entity state

    echo(PHP_EOL);
}

// EOF
