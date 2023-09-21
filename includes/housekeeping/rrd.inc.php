<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     housekeeping
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Minimum allowed age to delete RRDs is 24h
$cutoff = age_to_unixtime($config['housekeeping']['rrd']['age'], age_to_seconds('24h'));

// Validate rrdtool version before check invalid
if ($config['housekeeping']['rrd']['invalid']) {
    [, $rrdtool_version] = explode(' ', external_exec($GLOBALS['config']['rrdtool'] . ' --version | head -n1'));
    if (!$rrdtool_version || !preg_match('/^\d+(\.\d+)+/', $rrdtool_version)) {
        // Not valid rrdtool found, disable check invalid files
        $config['housekeeping']['rrd']['invalid'] = FALSE;
    }
}

if ($cutoff ||
    $config['housekeeping']['rrd']['invalid'] ||
    $config['housekeeping']['rrd']['deleted'] ||
    $config['housekeeping']['rrd']['disabled']) {
    if ($prompt) {
        $msg = "RRD files:" . PHP_EOL;
        if ($config['housekeeping']['rrd']['invalid']) {
            $msg .= " - not valid RRD" . PHP_EOL;
        }
        if ($config['housekeeping']['rrd']['deleted']) {
            $msg .= " - RRD dirs for deleted hosts" . PHP_EOL;
        }
        if ($config['housekeeping']['rrd']['disabled'] && $cutoff) {
            $msg .= " - RRD dirs for disabled devices since " . format_unixtime($cutoff) . PHP_EOL;
        }
        if ($config['housekeeping']['rrd']['notmodified'] && $cutoff) {
            $msg .= " - not modified since " . format_unixtime($cutoff) . PHP_EOL;
        }
        $answer = print_prompt($msg . "will be deleted");
    }
} else {
    print_message("RRD housekeeping disabled in configuration or less than 24h.");
    $answer = FALSE;
}

if ($answer) {
    $rrd_stat = ['notmodified' => 0, 'invalid' => 0, 'deleted' => 0, 'disabled' => 0];

    $table_rows  = [];
    $rrd_dir_len = strlen($config['rrd_dir']);
    foreach (glob($config['rrd_dir'] . '/*', GLOB_ONLYDIR) as $rrd_dir) {

        $rrd_host       = ltrim(substr($rrd_dir, $rrd_dir_len), '/');
        $rrd_host_valid = is_valid_hostname($rrd_host) || get_ip_version($rrd_host);

        $rrd_files  = [];
        $file_count = 0;
        foreach (get_recursive_directory_iterator($rrd_dir) as $file => $info) {
            if ($info -> getExtension() === 'rrd') {
                print_debug("Found file ending in '.rrd': " . $file);
                $rrd_files[] = $file;
            }
            $file_count++;
        }
        $rrd_count = count($rrd_files);

        if (!$rrd_count && !$rrd_host_valid) {
            print_debug("Incorrect hostname detected [$rrd_host] in directory [$rrd_dir]. Seems as not RRD dir.");
            $table_rows['incorrect'][] = [$rrd_host, $rrd_dir, 0, '%yIncorrect host%n'];
            continue;
        }

        if (!$rrd_count && $file_count) {
            print_debug("Not empty directory [$rrd_dir] not have RRD files. Skipped..");
            $table_rows['empty'][] = [$rrd_host, $rrd_dir, $rrd_count, '%yNot empty dir.%n'];

            // Skip other checks in dir
            continue;
        }

        if (FALSE) { // $rrd_host_valid) { // Disable this function, the utility of this is small, just delete empty dirs, it can delete ALL data in database failure scenarios.
            $rrd_device = device_by_name($rrd_host);

            if (!$rrd_device) {
                print_debug("Found stale RRD directory [$rrd_dir] for nonexistent host [$rrd_host]");
                if ($config['housekeeping']['rrd']['deleted']) {
                    $table_rows['deleted'][] = [$rrd_host, $rrd_dir, $rrd_count, '%rDeleted host%n'];
                    $rrd_stat['deleted']++;
                    if (!$test) {
                        if (delete_dir($rrd_dir)) {
                            logfile("housekeeping.log", "Stale RRD directory [$rrd_dir] for nonexistent host [$rrd_host] - deleted.");
                        } else {
                            logfile("housekeeping.log", "Stale RRD directory [$rrd_dir] for nonexistent host [$rrd_host] - NOT DELETED, because no access to dir.");
                            print_debug("Stale RRD directory [$rrd_dir] for nonexistent host [$rrd_host] - NOT DELETED, because no access to dir.");
                        }
                    }
                } else {
                    print_warning("Found stale RRD directory [$rrd_dir] for nonexistent host [$rrd_host]. To remove it, set \$config['housekeeping']['rrd']['deleted'] = TRUE;");
                    //log_event("Housekeeping: Found stale RRD directories for nonexistent devices. To remove, it set \$config['housekeeping']['rrd']['deleted'] = TRUE;", $rrd_device, 'device', $rrd_device, 7);
                }
                // Skip other checks in dir
                continue;
            }


            if ($rrd_device['disabled']) {
                $last_polled = $rrd_device['last_polled'] ? strtotime($rrd_device['last_polled']) : 0;
                if ($config['housekeeping']['rrd']['disabled'] && $last_polled < $cutoff) {
                    //print_cli($rrd_device['last_polled'] . ": $last_polled < $cutoff\n");
                    print_debug("Found old RRD directory [$rrd_dir] for disabled device [$rrd_host] - deleting");
                    $table_rows['disabled'][] = [$rrd_host, $rrd_dir, $rrd_count, '%mDisabled device%n (Last polled: ' . format_uptime(get_time() - $last_polled, 'short-2') . ' ago)'];
                    $rrd_stat['disabled']++;
                    if (!$test) {
                        if (delete_dir($rrd_dir)) {
                            logfile("housekeeping.log", "Old RRD directory [$rrd_dir] for disabled device [$rrd_host] - deleted.");
                            //log_event("Housekeeping: Removed RRD directory [$rrd_dir] for disabled device older than ".format_uptime(get_time() - $last_polled, 'short-2'), $rrd_device, 'device', $rrd_device, 7);
                        } else {
                            logfile("housekeeping.log", "Old RRD directory [$rrd_dir] for disabled device [$rrd_host] - NOT DELETED, because no access to dir.");
                            print_debug("Old RRD directory [$rrd_dir] for disabled device [$rrd_host] - NOT DELETED, because no access to dir.");
                        }
                    }
                }
                // Skip other checks in dir
                continue;
            }
        }

        // Now checks for every rrd
        foreach ($rrd_files as $file) {
            if ($cutoff && $config['housekeeping']['rrd']['notmodified']) {
                $file_data = stat($file);

                if ($file_data['mtime'] < $cutoff) {
                    print_debug("File modification time is " . format_unixtime($file_data['mtime']) . " - deleting");
                    $table_rows['notmodified'][] = [$rrd_host, $file, '%yRRD File not modified%n (Last modified: ' . format_uptime(get_time() - $file_data['mtime'], 'short-2') . ' ago)'];
                    if (!$test) {
                        if (unlink($file)) {
                            logfile("housekeeping.log", "RRD File $file modification time is " . format_unixtime($file_data['mtime']) . " - deleted.");
                            //log_event("Housekeeping: Removed RRD file [$file] not modified since ".format_unixtime($file_data['mtime']), $rrd_device, 'device', $rrd_device, 7);
                        } else {
                            logfile("housekeeping.log", "RRD File $file modification time is " . format_unixtime($file_data['mtime']) . " - NOT DELETED, because no access to file.");
                            print_debug("RRD File $file modification time is " . format_unixtime($file_data['mtime']) . " - NOT DELETED, because no access to file.");
                        }
                    }
                    $rrd_stat['notmodified']++;

                    // Skip other check for invalid
                    continue;
                }
            }

            if ($config['housekeeping']['rrd']['invalid'] && !rrdtool_file_valid($file)) {
                print_debug("Invalid RRD file $file - deleting");
                $table_rows['invalid'][] = [$rrd_host, $file, '%rRRD File invalid%n'];
                if (!$test) {
                    if (unlink($file)) {
                        logfile("housekeeping.log", "File $file is not valid RRD - deleted.");
                        //log_event("Housekeeping: Removed file [$file] not valid RRD", $rrd_device, 'device', $rrd_device, 7);
                    } else {
                        logfile("housekeeping.log", "File $file is not valid RRD - NOT DELETED, because no access to file.");
                        print_debug("File $file is not valid RRD - NOT DELETED, because no access to file.");
                    }
                }
                $rrd_stat['invalid']++;

                return;
            }
        }
    }

    if ($prompt) {
        foreach (['empty', 'incorrect', 'deleted', 'disabled', 'notmodified', 'invalid'] as $t) {
            if (isset($table_rows[$t])) {
                $all              = $t === 'notmodified' || $t === 'invalid' ? 'files' : 'all';
                $table_rows[$all] = array_merge((array)$table_rows[$all], $table_rows[$t]);
                unset($table_rows[$t]);
            }
        }
        print_cli_table($table_rows['all'], ['Host', 'RRD dir', 'RRD count', 'Reason']);
        print_cli_table($table_rows['files'], ['Host', 'RRD file', 'Reason']);
    }
    unset($table_rows);

    if ($prompt && $cutoff && $config['housekeeping']['rrd']['notmodified']) {
        if ($rrd_stat['notmodified']) {
            print_message("Deleted " . $rrd_stat['notmodified'] . " not modified RRD files older than " . format_unixtime($cutoff));
        } else {
            print_message("No RRD files found last modified before " . format_unixtime($cutoff));
        }
    }
    if ($prompt && $config['housekeeping']['rrd']['invalid']) {
        if ($rrd_stat['invalid']) {
            print_message("Deleted " . $rrd_stat['invalid'] . " invalid RRD files");
        } else {
            print_message("No invalid RRD files found");
        }
    }
}

// EOF
