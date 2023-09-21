<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($_SESSION['userlevel'] < 7) {
    print_error_permission();
    return;
}

echo generate_box_open();
$cols = [
  'Process ID',
  'PID', 'PPID', 'UID', 'Command', 'Name', 'Started',
  'Device', 'Poller ID'
];
echo build_table(dbFetchRows("SELECT * FROM `observium_processes` ORDER BY `process_ppid`, `process_start`"), ['columns' => $cols, 'process_start' => 'unixtime', 'device_id' => 'device']);
echo generate_box_close();

// EOF

