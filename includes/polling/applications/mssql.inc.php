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

/* TODO:
 * - Implement additional counters
 */

echo(" MSSQL:\n");
foreach ($wmi['mssql']['services'] as $instance) {
    if ($instance['Name'] !== "MSSQLSERVER") {
        $instance['Name'] = substr($instance['Name'], strpos($instance['Name'], "$") + 1);
        $instance['Name'] = preg_replace('/_/', "", $instance['Name']);

        $wmi_class_prefix = "Win32_PerfFormattedData_MSSQL" . $instance['Name'] . "_MSSQL" . $instance['Name'];
    } else {
        $wmi_class_prefix = "Win32_PerfFormattedData_MSSQLSERVER_SQLServer";
    }

    $app_found = FALSE;
    $app_data  = [];
    echo("  " . $instance['Name'] . " - PID: " . $instance['ProcessId'] . "\n   ");

    $wql                                      = "SELECT * FROM " . $wmi_class_prefix . "GeneralStatistics";
    $wmi['mssql'][$instance['Name']]['stats'] = wmi_parse(wmi_query($wql, $override), TRUE);

    if ($wmi['mssql'][$instance['Name']]['stats']) {
        $app_found['mssql'] = TRUE;
        echo("Stats; ");

        rrdtool_update_ng($device, 'mssql-stats', [
          'userconnections' => $wmi['mssql'][$instance['Name']]['stats']['UserConnections']
        ],                $instance['Name']);

        $app_data['stats']['UsrConn'] = $wmi['mssql'][$instance['Name']]['stats']['UserConnections'];
    }

    $wql                                       = "SELECT * FROM " . $wmi_class_prefix . "MemoryManager";
    $wmi['mssql'][$instance['Name']]['memory'] = wmi_parse(wmi_query($wql, $override), TRUE);

    if ($wmi['mssql'][$instance['Name']]['memory']) {
        $app_found['mssql'] = TRUE;
        echo("Memory; ");

        $wmi['mssql'][$instance['Name']]['memory']['TotalServerMemoryKB']  *= 1024;
        $wmi['mssql'][$instance['Name']]['memory']['TargetServerMemoryKB'] *= 1024;
        $wmi['mssql'][$instance['Name']]['memory']['SQLCacheMemoryKB']     *= 1024;

        rrdtool_update_ng($device, 'mssql-memory', [
          'totalmemory'       => $wmi['mssql'][$instance['Name']]['memory']['TotalServerMemoryKB'],
          'targetmemory'      => $wmi['mssql'][$instance['Name']]['memory']['TargetServerMemoryKB'],
          'cachememory'       => $wmi['mssql'][$instance['Name']]['memory']['SQLCacheMemoryKB'],
          'grantsoutstanding' => $wmi['mssql'][$instance['Name']]['memory']['MemoryGrantsOutstanding'],
          'grantspending'     => $wmi['mssql'][$instance['Name']]['memory']['MemoryGrantsPending'],
        ],                $instance['Name']);

        $app_data['memory']['used']  = $wmi['mssql'][$instance['Name']]['memory']['TotalServerMemoryKB'];
        $app_data['memory']['total'] = $wmi['mssql'][$instance['Name']]['memory']['TargetServerMemoryKB'];
        $app_data['memory']['cache'] = $wmi['mssql'][$instance['Name']]['memory']['SQLCacheMemoryKB'];
        $app_data['memory']['grnto'] = $wmi['mssql'][$instance['Name']]['memory']['MemoryGrantsOutstanding'];
        $app_data['memory']['grntp'] = $wmi['mssql'][$instance['Name']]['memory']['MemoryGrantsPending'];
    }

    $wql                                       = "SELECT * FROM " . $wmi_class_prefix . "BufferManager";
    $wmi['mssql'][$instance['Name']]['buffer'] = wmi_parse(wmi_query($wql, $override), TRUE);

    if ($wmi['mssql'][$instance['Name']]['buffer']) {
        $app_found['mssql'] = TRUE;
        echo("Buffer; ");

        $app_data['buffer']['LifeExp'] = $wmi['mssql'][$instance['Name']]['buffer']['Pagelifeexpectancy'];
        $app_data['buffer']['PgLook']  = $wmi['mssql'][$instance['Name']]['buffer']['PagelookupsPersec'];
        $app_data['buffer']['PgRead']  = $wmi['mssql'][$instance['Name']]['buffer']['PagereadsPersec'];
        $app_data['buffer']['PgWrite'] = $wmi['mssql'][$instance['Name']]['buffer']['PagewritesPersec'];
        $app_data['buffer']['Stalls']  = $wmi['mssql'][$instance['Name']]['buffer']['FreeliststallsPersec'];

        rrdtool_update_ng($device, 'mssql-buffer', [
          'pagelifeexpect' => $wmi['mssql'][$instance['Name']]['buffer']['Pagelifeexpectancy'],
          'pagelookupssec' => $wmi['mssql'][$instance['Name']]['buffer']['PagelookupsPersec'],
          'pagereadssec'   => $wmi['mssql'][$instance['Name']]['buffer']['PagereadsPersec'],
          'pagewritessec'  => $wmi['mssql'][$instance['Name']]['buffer']['PagewritesPersec'],
          'freeliststalls' => $wmi['mssql'][$instance['Name']]['buffer']['FreeliststallsPersec'],
        ],                $instance['Name']);
    }

    // CPU Usage

    $wql                                    = "SELECT * FROM Win32_PerfRawData_PerfProc_Process WHERE IDProcess=" . $instance['ProcessId'];
    $wmi['mssql'][$instance['Name']]['cpu'] = wmi_parse(wmi_query($wql, $override), TRUE);

    // Windows measures CPU usage using the PERF_100NSEC_TIMER_INV counter type, meaning measurements are in 100 nanosecond increments
    // http://msdn.microsoft.com/en-us/library/ms803963.aspx

    $cpu_ntime = sprintf('%u', utime() * 100000000);

    if ($wmi['mssql'][$instance['Name']]['cpu']) {
        $app_found['mssql'] = TRUE;
        echo("CPU; ");

        $app_data['cpu']['proc'] = $wmi['mssql'][$instance['Name']]['cpu']['PercentProcessorTime'];
        $app_data['cpu']['time'] = $cpu_ntime;

        rrdtool_update_ng($device, 'mssql-cpu', [
          'percproctime' => $wmi['mssql'][$instance['Name']]['cpu']['PercentProcessorTime'],
          'threads'      => $wmi['mssql'][$instance['Name']]['cpu']['ThreadCount'],
          'lastpoll'     => $cpu_ntime,
        ],                $instance['Name']);
    }

    if ($app_found['mssql'] == TRUE) {
        $app_id = discover_app($device, 'mssql', $instance['Name']);
        update_application($app_id, $app_data);
    }

    // FIXME state gone

    $sql       = "SELECT * FROM `applications` AS A LEFT JOIN `applications-state` AS S ON `A`.`app_id`=`S`.`application_id` WHERE `A`.`device_id` = ? AND `A`.`app_instance` = ? AND `A`.`app_type` = 'mssql'";
    $app_state = dbFetchRow($sql, [$device['device_id'], $instance['Name']]);
    $app_data  = serialize($app_data);

    if (empty($app_state['app_state'])) {
        dbInsert(['application_id' => $app_state['app_id'], 'app_last_polled' => time(), 'app_status' => 1, 'app_state' => $app_data], 'applications-state');
    } else {
        dbUpdate(['app_last_polled' => time(), 'app_status' => 1, 'app_state' => $app_data], 'applications-state', "`application_id` = ?", [$app_state['application_id']]);
    }

    echo("\n");
}

unset ($wmi['mssql']);

// EOF
