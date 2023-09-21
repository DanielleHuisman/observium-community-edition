<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

global $config, $device;

$cp_stats = [
  'timer'        => 'Timer',
  'snapshot'     => 'Snapshot',
  'low_water'    => 'Low Water',
  'high_water'   => 'High Water',
  'log_full'     => 'NV Log Full',
  'cp'           => 'Back to Back CPs',
  'flush'        => 'Write Flush',
  'sync'         => 'Sync',
  'low_vbuf'     => 'Low Virtual Buffers',
  'cp_deferred'  => 'Deferred CPs',
  'low_datavecs' => 'Low Datavecs'
];

foreach ($cp_stats as $stat => $descr) {
    $rrd_filename = get_rrd_path($device, 'netapp-cp.rrd');

    if (rrd_is_file($rrd_filename)) {
        #$descr = nicecase($stat);

        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $descr;
        $rrd_list[$i]['ds']       = $stat;
    }
}

$unit_text = 'Operations/s';

$units       = '';
$total_units = '';
$colours     = 'mixed';

$scale_min = '0';
#$scale_max = '100';

$text_orig = 1;
$nototal   = 1;

include('includes/graphs/generic_multi_simplex_separated.inc.php');
