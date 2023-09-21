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

$hrDevice_oids  = ['hrDeviceType', 'hrDeviceDescr', 'hrProcessorLoad'];
$hrDevice_array = [];
foreach ($hrDevice_oids as $oid) {
    $hrDevice_array = snmpwalk_cache_oid($device, $oid, $hrDevice_array, 'HOST-RESOURCES-MIB:HOST-RESOURCES-TYPES');
}

$hr_cpus  = 0;
$hr_total = 0;

if (safe_count($hrDevice_array)) {
    foreach ($hrDevice_array as $index => $entry) {
        if (!is_numeric($entry['hrProcessorLoad'])) {
            continue;
        }

        if (!isset($entry['hrDeviceType'])) {
            $entry['hrDeviceType']  = 'hrDeviceProcessor';
            $entry['hrDeviceIndex'] = $index;
        } elseif ($entry['hrDeviceType'] === 'hrDeviceOther' &&
                  preg_match('/^cpu\d+:/', $entry['hrDeviceDescr'])) {
            // Workaround bsnmpd reporting CPUs as hrDeviceOther (FY FreeBSD.)
            $entry['hrDeviceType'] = 'hrDeviceProcessor';
        }

        if ($entry['hrDeviceType'] === 'hrDeviceProcessor') {

            $usage = $entry['hrProcessorLoad'];

            if ($device['os'] === 'arista_eos' && $index == 1) {
                continue;
            }

            if ($entry['hrDeviceDescr'] !== 'An electronic chip that makes the computer work.') {
                $hr_cpus++;
                $hr_total += $usage;
            }
        }
        unset($entry);
    }

    if ($hr_cpus) {
        $proc = $hr_total / $hr_cpus;
    }

    unset($hrDevice_oids, $hrDevice_array, $oid);
}

// EOF
