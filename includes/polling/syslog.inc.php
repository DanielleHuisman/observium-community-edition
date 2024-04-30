<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

if (!$config['enable_syslog']) {
    // Syslog disabled
    if (isset($attribs['syslog_stats'])) {
        del_entity_attrib('device', $device['device_id'], 'syslog_stats');
    }
    return;
}

$where = generate_query_values($device['device_id'], 'device_id');
if (!(isset($attribs['syslog_stats']) || dbExist('syslog', $where))) {
    // Exist much faster than count()
    // do not store stats when no syslog messages
    return;
}

$syslog_stats = safe_json_decode($attribs['syslog_stats']);
if (!is_array($syslog_stats)) {
    // init stats
    $syslog_stats = [
        'count'         => 0,     // total messages count
        'rate'          => 0,     // rate per second
        'last_id'       => 0,     // last message id (seq)
        'last_unixtime' => time() // last polled time
    ];
}
print_debug_vars($syslog_stats);


$sql = 'SELECT COUNT(*) AS `count`, MAX(`seq`) AS `seq` FROM `syslog` ';
$syslog_db = dbFetchRow($sql . generate_where_clause($where, '`seq` > ' . (int)$syslog_stats['last_id']));
// auto increment was reset?
if (!$syslog_db['count'] && ($auto_id = dbShowNextID('syslog')) && $auto_id < $syslog_stats['last_id']) {
    print_debug('Auto increment of syslog table was reset.');
    $syslog_db = dbFetchRow($sql . generate_where_clause($where));
}

$syslog_count  = int_add($syslog_stats['count'], $syslog_db['count']);
$syslog_time   = time();
$syslog_period = $syslog_time - $syslog_stats['last_unixtime'];
$syslog_rate   = float_div($syslog_db['count'], $syslog_period);

//check_entity('syslog', $device, [ 'count' => $syslog_count, 'rate' => $syslog_rate ]);
$alert_metrics['syslog_count_total'] = $syslog_count;
$alert_metrics['syslog_count']       = $syslog_stats['count'];
$alert_metrics['syslog_rate']        = $syslog_rate;

rrdtool_update_ng($device, 'syslog', [ 'count' => $syslog_count, 'messages' => $syslog_count ]);
$graphs['syslog_count']    = TRUE;
$graphs['syslog_messages'] = TRUE;

// store new stats
$syslog_stats = [
    'count'         => $syslog_count,     // total messages count
    'rate'          => $syslog_rate,      // rate per second
    'last_id'       => $syslog_db['seq'], // last message id (seq)
    'last_unixtime' => $syslog_time       // last polled time
];
print_debug_vars($syslog_stats, 1);
set_entity_attrib('device', $device['device_id'], 'syslog_stats', safe_json_encode($syslog_stats));

// EOF
