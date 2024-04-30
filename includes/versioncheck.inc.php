<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     common
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Generate some statistics to send along with the version request.
//
// These stats are used to allow us to prioritise development resources
// to target features and devices that are used the most.
/// FIXME. Not used anymore..
function get_instance_stats()
{
    $stats = [];

    // Overall Ports/Devices statistics
    $stats['edition'] = OBSERVIUM_EDITION;
    $stats['version'] = OBSERVIUM_VERSION;
    $stats['uuid']    = get_unique_id();

    $stats['devices'] = dbFetchCell("SELECT COUNT(*) FROM devices");
    $stats['ports']   = dbFetchCell("SELECT COUNT(*) FROM ports");

    // Per-feature statistics
    $stats['sensors']       = dbFetchCell("SELECT COUNT(*) FROM `sensors`");
    $stats['statuses']      = dbFetchCell("SELECT COUNT(*) FROM `status`");
    $stats['counters']      = dbFetchCell("SELECT COUNT(*) FROM `counters`");
    $stats['applications']  = dbFetchCell("SELECT COUNT(*) FROM `applications`");
    $stats['bgp']           = dbFetchCell("SELECT COUNT(*) FROM `bgpPeers`");
    $stats['ospf']          = dbFetchCell("SELECT COUNT(*) FROM `ospf_ports`");
    $stats['eigrp']         = dbFetchCell("SELECT COUNT(*) FROM `eigrp_ports`");
    $stats['ipsec_tunnels'] = dbFetchCell("SELECT COUNT(*) FROM `ipsec_tunnels`");
    $stats['munin_plugins'] = dbFetchCell("SELECT COUNT(*) FROM `munin_plugins`");
    $stats['pseudowires']   = dbFetchCell("SELECT COUNT(*) FROM `pseudowires`");
    $stats['slas']          = dbFetchCell("SELECT COUNT(*) FROM `slas`");
    $stats['vrfs']          = dbFetchCell("SELECT COUNT(*) FROM `vrfs`");
    $stats['vminfo']        = dbFetchCell("SELECT COUNT(*) FROM `vminfo`");
    $stats['users']         = dbFetchCell("SELECT COUNT(*) FROM `users`");
    $stats['bills']         = dbFetchCell("SELECT COUNT(*) FROM `bills`");
    $stats['alerts']        = dbFetchCell("SELECT COUNT(*) FROM `alert_table`");
    $stats['alert_tests']   = dbFetchCell("SELECT COUNT(*) FROM `alert_tests`");
    $stats['groups']        = dbFetchCell("SELECT COUNT(*) FROM `groups`");
    $stats['group_members'] = dbFetchCell("SELECT COUNT(*) FROM `group_table`");

    $stats['poller_time']    = dbFetchCell("SELECT SUM(`last_polled_timetaken`) FROM devices");
    $stats['discovery_time'] = dbFetchCell("SELECT SUM(`last_discovered_timetaken`) FROM devices");
    $stats['php_version']    = phpversion();

    $os_text = external_exec("DISTROFORMAT=export " . $GLOBALS['config']['install_dir'] . "/scripts/distro");

    foreach (explode("\n", $os_text) as $part) {
        [$a, $b] = explode("=", $part);
        $stats['os'][$a] = $b;
    }

    // sysObjectID for Generic devices
    foreach (dbFetchRows("SELECT `sysObjectID`, COUNT(*) AS `count` FROM `devices` WHERE `os` = 'generic' GROUP BY `sysObjectID`") as $data) {
        $stats['generics'][$data['sysObjectID']] = $data['count'];
    }

    // Per-OS counts
    foreach (dbFetchRows("SELECT COUNT(*) AS `count`, `os` FROM `devices` GROUP BY `os`") as $data) {
        $stats['devicetypes'][$data['os']] = $data['count'];
    }

    // Per-type counts
    foreach (dbFetchRows("SELECT COUNT(*) AS `count`, `type` FROM `devices` GROUP BY `type`") as $data) {
        $stats['types'][$data['type']] = $data['count'];
    }

    // Per-apptype counts
    foreach (dbFetchRows("SELECT COUNT(*) AS `count`, `app_type` FROM `applications` GROUP BY `app_type`") as $data) {
        $stats['app_types'][$data['app_type']] = $data['count'];
    }

    $stats['misc']['max_len']['port_label']       = dbFetchCell("SELECT MAX(LENGTH(`port_label`)) FROM `ports`");
    $stats['misc']['max_len']['port_label_short'] = dbFetchCell("SELECT MAX(LENGTH(`port_label_short`)) FROM `ports`");
    $stats['misc']['max_len']['port_label_base']  = dbFetchCell("SELECT MAX(LENGTH(`port_label_base`)) FROM `ports`");
    $stats['misc']['max_len']['port_label_num']   = dbFetchCell("SELECT MAX(LENGTH(`port_label_num`)) FROM `ports`");

    return $stats;
}

$latest = [];
if ($config['poller_id'] === 0) {
    // On Remote pollers only show warning

    $last_checked = get_obs_attrib('last_versioncheck');

    if (isset($options['u']) || !is_numeric($last_checked) || $last_checked < (time() - 3600)) {
        //$stats = get_instance_stats();
        $stats = [ 'version' => OBSERVIUM_VERSION ];

        // Serialize and base64 encode stats array for transportation
        $stat_base64 = base64_encode(serialize($stats));

        $tags = [ 'stats' => $stat_base64 ];

        if ($versions = get_http_def('observium_versions', $tags)) {

            print_debug_vars($versions);
            if (OBSERVIUM_EDITION === "community") {
                $train = "ce";
            } elseif (OBSERVIUM_TRAIN === "stable") {
                $train = "stable";
            } else {
                $train = "current"; // this same as rolling
            }

            $latest = $versions[$train];

            set_obs_attrib('latest_ver', preg_replace('/^0\./', '', $latest['version']));
            set_obs_attrib('latest_rev', $latest['revision']);
            set_obs_attrib('latest_rev_date', $latest['date']);
        }

        set_obs_attrib('last_versioncheck', time());
    }
}

$latest['revision'] = get_obs_attrib('latest_rev');

if ($latest['revision'] > OBSERVIUM_REV) {
    $latest['version'] = get_obs_attrib('latest_ver');
    $latest['date']    = get_obs_attrib('latest_rev_date');

    print_message("%GThere is a newer revision of Observium available!%n", 'color');
    print_message("%GVersion %r" . $latest['version'] . "%G (" . format_unixtime(datetime_to_unixtime($latest['date']), 'jS F Y') . ") is %r" . ($latest['revision'] - OBSERVIUM_REV) . "%G revisions ahead.%n\n", 'color');
} elseif (isset($options['u']) && $latest['revision'] == OBSERVIUM_REV) {
    print_message("-- Observium is up to date.");
}

unset($latest, $versions, $train, $last_checked, $stats);

// EOF
