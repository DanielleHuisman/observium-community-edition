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

$diskstat = $agent_data['diskstat'];
unset($agent_data['diskstat']);

$timestamp = 'N';

print_cli_data("Diskstats");

foreach (explode("\n", $diskstat) as $line) {
    if (is_numeric($line)) {
        // timestamp
        //$timestamp = $line;
    } else {
        $data = preg_split('/\\s+/', $line);

        if (count($data) == 15) {
            $disk_name          = $data[3];
            $readcount          = $data[4];
            $readcount_merged   = $data[5];
            $readcount_sectors  = $data[6];
            $time_reading       = $data[7];
            $writecount         = $data[8];
            $writecount_merged  = $data[9];
            $writecount_sectors = $data[10];
            $time_writing       = $data[11];
            $pending_ios        = $data[12];
            $time_io            = $data[13];
            $time_wio           = $data[14];

            rrdtool_update_ng($device, 'diskstat', [
              'readcount'          => $readcount,
              'readcount_merged'   => $readcount_merged,
              'readcount_sectors'  => $readcount_sectors,
              'time_reading'       => $time_reading,
              'writecount'         => $writecount,
              'writecount_merged'  => $writecount_merged,
              'writecount_sectors' => $writecount_sectors,
              'time_writing'       => $time_writing,
              'pending_ios'        => $pending_ios,
              'time_io'            => $time_io,
              'time_wio'           => $time_wio,
            ],                $disk_name);
        }
    }
}

unset($data, $diskstats);

// EOF
