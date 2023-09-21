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

// This is mostly derp MIB which I have seen

// ASCO-QEM-72EE2::real_time_hour.0 = INTEGER: 14
// ASCO-QEM-72EE2::real_time_minute.0 = INTEGER: 9
// ASCO-QEM-72EE2::real_time_second.0 = INTEGER: 37
// ASCO-QEM-72EE2::calendar_year.0 = INTEGER: 21
// ASCO-QEM-72EE2::calendar_month.0 = INTEGER: 12
// ASCO-QEM-72EE2::calendar_day_of_month.0 = INTEGER: 17
// ASCO-QEM-72EE2::calendar_day_of_week.0 = INTEGER: 5

// ASCO-QEM-72EE2::ts_data_gen_start_date.0 = INTEGER: 2
// ASCO-QEM-72EE2::ts_data_gen_start_month.0 = INTEGER: 12
// ASCO-QEM-72EE2::ts_data_gen_start_year.0 = INTEGER: 21
// ASCO-QEM-72EE2::ts_data_gen_start_hour.0 = INTEGER: 16
// ASCO-QEM-72EE2::ts_data_gen_start_minutes.0 = INTEGER: 23
// ASCO-QEM-72EE2::ts_data_gen_start_seconds.0 = INTEGER: 55
// ASCO-QEM-72EE2::ts_data_gen_start_10th_of_seconds.0 = INTEGER: 1

// ASCO-QEM-72EE2::total_number_of_days_CP_has_been_energized.0 = INTEGER: 40

// there is no other way to get the real uptime except approximate days with real time
if ($data = snmp_get_multi_oid($device, 'total_number_of_days_CP_has_been_energized.0 real_time_hour.0 real_time_minute.0 real_time_second.0', [], 'ASCO-QEM-72EE2')) {
    $poll_device['device_uptime'] = $data[0]['total_number_of_days_CP_has_been_energized'] * 86400 +
                                    $data[0]['real_time_hour'] * 3600 +
                                    $data[0]['real_time_minute'] * 60 +
                                    $data[0]['real_time_second'];
}

// EOF
