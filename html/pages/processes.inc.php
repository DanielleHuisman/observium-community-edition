<?php

if ($_SESSION['userlevel'] >= 10)
{
  echo generate_box_open();
  echo build_table(dbFetchRows("SELECT * FROM `observium_processes` ORDER BY `process_ppid`, `process_start`"), ['process_start' => 'unixtime', 'device_id' => 'device']);
  echo generate_box_close();
} else {
  print_error_permission();
}

// EOF

