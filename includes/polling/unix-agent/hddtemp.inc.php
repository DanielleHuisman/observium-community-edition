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

global $agent_sensors;

if ($agent_data['hddtemp'] != '|') {

    $agent_data['hddtemp'] = str_replace("\x10\x80", '', $agent_data['hddtemp']);

    $disks = explode('||', trim($agent_data['hddtemp'], '|'));

    if (count($disks)) {
        echo "hddtemp: ";
        foreach ($disks as $disk) {
            [$blockdevice, $descr, $value, $unit] = explode('|', $disk, 4);
            # FIXME: should not use diskcount as index; drive serial preferred but hddtemp does not supply it.
            # Device name itself is just as useless as the actual position however.
            # In case of change in index, please provide an rrd-rename upgrade-script.
            ++$diskcount;
            discover_sensor('temperature', $device, '', $diskcount, 'hddtemp', "$blockdevice: $descr", 1, $value, [], 'agent');
            $agent_sensors['temperature']['hddtemp'][$diskcount] = ['description' => "$blockdevice: $descr", 'current' => $value, 'index' => $diskcount];
        }
        echo "\n";
    }
}

// EOF
