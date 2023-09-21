<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @author         Kresimir Jurasovic, Tom Laermans
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!empty($agent_data['app']['jvmoverjmx'])) {
    foreach ($agent_data['app']['jvmoverjmx'] as $instance => $jvmoverjmx) {
        $app_id = discover_app($device, 'jvmoverjmx', $instance);

        echo(" jvmoverjmx statistics" . PHP_EOL);

        foreach (explode("\n", $jvmoverjmx) as $jmxdataValue) {
            [$key, $value] = explode(':', $jmxdataValue);
            $jvmoverjmx_data[trim($key)] = trim($value);
        }

        $data = [
          'UpTime'             => $jvmoverjmx_data['UpTime'],
          'HeapMemoryMaxUsage' => $jvmoverjmx_data['HeapMemoryMaxUsage'],
          'HeapMemoryUsed'     => $jvmoverjmx_data['HeapMemoryUsed'],
          'NonHeapMemoryMax'   => $jvmoverjmx_data['NonHeapMemoryMax'],
          'NonHeapMemoryUsed'  => $jvmoverjmx_data['NonHeapMemoryUsed'],
          'EdenSpaceMax'       => $jvmoverjmx_data['EdenSpaceMax'],
          'EdenSpaceUsed'      => $jvmoverjmx_data['EdenSpaceUsed'],
          'PermGenMax'         => $jvmoverjmx_data['PermGenMax'],
          'PermGenUsed'        => $jvmoverjmx_data['PermGenUsed'],
          'OldGenMax'          => $jvmoverjmx_data['OldGenMax'],
          'OldGenUsed'         => $jvmoverjmx_data['OldGenUsed'],
          'DaemonThreads'      => $jvmoverjmx_data['DaemonThreads'],
          'TotalThreads'       => $jvmoverjmx_data['TotalThreads'],
          'LoadedClassCount'   => $jvmoverjmx_data['LoadedClassCount'],
          'UnloadedClassCount' => $jvmoverjmx_data['UnloadedClassCount'],
          'G1OldGenCount'      => $jvmoverjmx_data['G1OldGenCollectionCount'],
          'G1OldGenTime'       => $jvmoverjmx_data['G1OldGenCollectionTime'],
          'G1YoungGenCount'    => $jvmoverjmx_data['G1YoungGenCollectionCount'],
          'G1YoungGenTime'     => $jvmoverjmx_data['G1YoungGenCollectionTime'],
          'CMSCount'           => $jvmoverjmx_data['CMSCollectionCount'],
          'CMSTime'            => $jvmoverjmx_data['CMSCollectionTime'],
          'ParNewCount'        => $jvmoverjmx_data['ParNewCollectionCount'],
          'ParNewTime'         => $jvmoverjmx_data['ParNewCollectionTime'],
          'CopyCount'          => $jvmoverjmx_data['CopyCollectionCount'],
          'CopyTime'           => $jvmoverjmx_data['CopyCollectionTime'],
          'PSMarkSweepCount'   => $jvmoverjmx_data['PSMarkSweepCollectionCount'],
          'PSMarkSweepTime'    => $jvmoverjmx_data['PSMarkSweepCollectionTime'],
          'PSScavengeCount'    => $jvmoverjmx_data['PSScavengeCollectionCount'],
          'PSScavengeTime'     => $jvmoverjmx_data['PSScavengeCollectionTime']];

        update_application($app_id, $data);

        rrdtool_update_ng($device, 'jvmoverjmx', $data, $app_id);

        unset($jvmoverjmx_data, $jmxdataValue);
    }
}

// EOF
