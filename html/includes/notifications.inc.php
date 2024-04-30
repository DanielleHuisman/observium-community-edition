<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Variables:
// $notifications - display notifications tooltip in the lower right corner
// $alerts        - display urgent errors for admin users at the top of each page

// Notifications and alerts in bottom navbar
$notifications_item = get_cache_item('notifications');
$alerts_item        = get_cache_item('alerts');
//print_vars($notifications_item->isHit());
//print_vars($alerts_item->isHit());

if (!ishit_cache_item($notifications_item) || !ishit_cache_item($alerts_item)) {
    // Generate/collect notifications/alerts


    if ($_SESSION['userlevel'] > 7) {

        if ($config['version_check']) {
            $latest = [];
            $latest['version']   = get_obs_attrib('latest_ver');      // 23.11.13200
            $latest['revision']  = get_obs_attrib('latest_rev');      // 13200
            $latest['date']      = get_obs_attrib('latest_rev_date'); // 2023-11-28
            $latest['changelog'] = OBSERVIUM_CHANGELOG_URL . '/' . explode('-', $latest['date'])[0] .
                                   '/#r' . $latest['revision'];       // https://changelog.observium.org/2023/#r13200

            if ($latest['revision'] > (OBSERVIUM_REV + $config['version_check_revs'])) {
                $notifications[] = [
                    'title'    => 'There is a newer revision of Observium available!',
                    'text'     => 'Version ' . $latest['version'] . ' (' . format_unixtime(datetime_to_unixtime($latest['date']), 'jS F Y') . ') is ' .
                                  ($latest['revision'] - OBSERVIUM_REV) . ' revisions ahead. See [ChangeLog]('.$latest['changelog'].'){target=_blank}',
                    'markdown' => TRUE,
                    'severity' => 'warning',
                    'unixtime' => get_time()
                ];
                $alerts[] = [
                    'title'    => 'There is a newer revision of Observium available!',
                    'text'     => 'Version ' . $latest['version'] . ' (' . format_unixtime(datetime_to_unixtime($latest['date']), 'jS F Y') . ') is ' .
                                  ($latest['revision'] - OBSERVIUM_REV) . ' revisions ahead. See [ChangeLog]('.$latest['changelog'].'){target=_blank}',
                    'markdown' => TRUE,
                    'severity' => 'alert'
                ];
            }
        }

        if (OBS_DISTRIBUTED) {
            // Alerts about down and old pollers version (exclude never polled)
            $sql = 'SELECT *, UNIX_TIMESTAMP(`timestamp`) AS `unixtime` FROM `pollers` WHERE `poller_version` IS NOT NULL';
            $sql .= ' AND `poller_id` IN (SELECT DISTINCT `poller_id` FROM `devices`)';
            foreach (dbFetchRows($sql) as $poller) {
                $poller_version = explode(' ', $poller['poller_version'])[0]; // remove train
                $poller_revision = explode('.', $poller_version)[2];
                if ($poller_revision && $poller_revision < 13063) {
                    // Compatibility for old pollers (who not update last update time)
                    $poller['unixtime'] = rrdtool_last('poller-wrapper-id'.$poller['poller_id'].'.rrd');
                } elseif ($poller['unixtime'] < get_time('1year')) {
                    // Prevent false alert on incorrect db update or too stale poller
                    $poller['unixtime'] = rrdtool_last('poller-wrapper-id'.$poller['poller_id'].'.rrd');
                }
                if ($poller['unixtime'] < get_time('10year')) {
                    // Prevent false alert on incorrect db update or too stale poller
                    continue;
                }
                $poller['timediff'] = get_time() - $poller['unixtime'];
                //r($poller);

                if ($poller['timediff'] > 300) {
                    // Poller down
                    $notifications[] = [
                        'title'    => "WARNING. Remote Poller {$poller['poller_id']} ({$poller['poller_name']}) DOWN!",
                        'text'     => 'Unavailable since ' . format_unixtime($poller['unixtime']) . ' (down <strong>' . format_uptime($poller['timediff']) . '</strong>).',
                        'severity' => 'warning',
                        'unixtime' => $poller['unixtime']
                    ];

                    // Append head alert notification
                    if ($poller['timediff'] > 3600) {
                        // Poller down longer than 1hour
                        $alerts[] = [
                            'title' => "Remote Poller {$poller['poller_id']} ({$poller['poller_name']}) DOWN!",
                            'text' => 'Unavailable since ' . format_unixtime($poller['unixtime']) . ' (down <strong>' . format_uptime($poller['timediff']) . '</strong>).',
                            'severity' => 'warning'
                        ];
                    }

                    // Skip versions check for down pollers
                    continue;
                }

                if ($config['version_check']) {

                    if ($latest['revision'] > ($poller_revision + $config['version_check_revs'])) {
                        $notifications[] = [
                            'text' => "<h4>Remote Poller {$poller['poller_id']} ({$poller['poller_name']}) uses an old Observium version!</h4>
                                       Latest version " . $latest['version'] . ' (' . format_unixtime(datetime_to_unixtime($latest['date']), 'jS F Y') . ') is ' .
                                       ($latest['revision'] - $poller_revision) . " revisions ahead. (Poller uses <strong>$poller_version</strong>)",
                            'severity' => 'warning',
                            'unixtime' => $poller['unixtime']
                        ];
                        $alerts[] = [
                            'title' => "Remote Poller {$poller['poller_id']} ({$poller['poller_name']}) uses an old Observium version!",
                            'text' => 'Latest version ' . $latest['version'] . ' (' . format_unixtime(datetime_to_unixtime($latest['date']), 'jS F Y') . ') is ' .
                                      ($latest['revision'] - $poller_revision) . " revisions ahead. (Poller uses <strong>$poller_version</strong>)",
                            'severity' => 'alert'
                        ];
                    }
                } elseif ($config['poller_id'] == 0 && abs(OBSERVIUM_REV - $poller_revision) >= $config['version_check_revs']) {
                    // Notify when master poller too different from remote poller
                    $notifications[] = [
                        'text' => "<h4>Remote Poller {$poller['poller_id']} ({$poller['poller_name']}) is not synced with Main Poller 0.</h4>
                                  Remote Poller version: " . $poller_version . '. Main Poller version: ' . OBSERVIUM_VERSION .
                                  '. (' . abs(OBSERVIUM_REV - $poller_revision) . ' revisions ahead).',
                        'severity' => 'warning',
                        'unixtime' => $poller['unixtime']
                    ];
                }
            }
        }

//if (!function_exists('hash_exists'))
//{
//  $alerts[] = array('text' => '<b>The PHP `hash` module is missing</b> This will cause editing and other forms to fail. Please install it.', 'severity' => 'danger');
//}

        // Warn about lack of encryption unless told not to.
        if ($config['login_remember_me'] || isset($_SESSION['encrypt_required'])) {
            if (!OBS_ENCRYPT) {
                print_warning('For use the "remember me" feature, required php version not less than 7.2 (with "sodium" extension). Alternatively, you can disable this feature by setting $config[\'login_remember_me\'] = FALSE; in your config.');
            }
        }

        // Warning about web_url config, only for ssl
        if (is_ssl() && preg_match('/^http:/', $config['web_url'])) {
            $notifications[] = [
                'text'     => 'Setting \'web_url\' for "External Web URL" not set or incorrect, please update on ' .
                              generate_link('Global Settings Edit', ['page' => 'settings', 'section' => 'wui']) . ' page.',
                'severity' => 'warning',
                'unixtime' => get_time()
            ];
        }

        // Warning about need DB schema update
        $db_version = get_db_version();
        $db_version = sprintf("%03d", $db_version + 1);
        if (is_file($config['install_dir'] . "/update/$db_version.sql") ||
            is_file($config['install_dir'] . "/update/$db_version.php")) {
            $notifications[] = [
                'text'     => 'Your database schema is old and needs updating. Run from server console:
                               <pre style="padding: 3px" class="small">' . $config['install_dir'] . '/discovery.php -u</pre>',
                'severity' => 'alert',
                'unixtime' => get_time()
            ];
        }
        unset($db_version);

        // Get GLOBAL events for last 3 days
        foreach (dbFetchRows('SELECT * FROM `eventlog` WHERE `entity_type` = ? AND `timestamp` > (CURDATE() - INTERVAL 3 DAY)', ['global']) as $entry) {
            $notifications[] = [
                'text'     => $entry['message'],
                'severity' => $entry['severity'],
                'unixtime' => strtotime($entry['timestamp'])
            ];
        }
    }

    if (isset($config['alerts']['suppress']) && $config['alerts']['suppress']) {
        $notifications[] = [
            'text'     => '<h4>All Alert Notifications Suppressed</h4>' .
                          'All alert notifications have been suppressed in the configuration.',
            'severity' => 'warning',
            'unixtime' => get_time()
        ];
    }

    // Display warning for scheduled maintenance
    if (isset($cache['maint']['count']) && $cache['maint']['count'] > 0) {
        $notifications[] = [
            'text'     => '<h4>Scheduled Maintenance in Progress</h4>' .
                          'Some or all alert notifications have been suppressed due to a scheduled maintenance.',
            'severity' => 'warning',
            'unixtime' => get_time()
        ];

        $alerts[] = [
            'title'    => 'Scheduled Maintenance in Progress',
            'text'     => 'Some or all alert notifications have been suppressed due to a scheduled maintenance.',
            'severity' => 'warning'
        ];
    }

    if (!$config['web_show_notifications']) {
        // reset to empty for cache
        $alerts = [];
    }

    // Store $notifications and $alerts in fast caching
    set_cache_item($notifications_item, $notifications, ['ttl' => 900]); // 15 min
    set_cache_item($alerts_item, $alerts, ['ttl' => 900]);               // 15 min

    // Clear expired cache
    del_cache_expired();

    // End build cache
} else {

    //print_vars(get_cache_data($notifications_item));
    $notifications = array_merge(get_cache_data($notifications_item), $notifications);
    if ($config['web_show_notifications']) {
        $alerts    = array_merge(get_cache_data($alerts_item), $alerts);
    }
}

// Admin level notifications on settings/perf pages
if ($_SESSION['userlevel'] >= 10 && (in_array($vars['tab'], ['data', 'perf', 'edit', 'showtech']) ||
                                     in_array($vars['page'], ['about', 'pollerlog', 'settings', 'preferences']))) {

    if (version_compare(PHP_VERSION, OBS_MIN_PHP_VERSION, '<')) {
        $notifications[] = [
            'text'     => '<h4>Your PHP version is too old.</h4>
                           Your currently installed PHP version <b>' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION . '</b>
                           is older than the required minimum.
                           Please upgrade your version of PHP to prevent possible incompatibilities and security problems.<br/>
                           Currently recommended version(s): <b>7.2.x</b> and newer.',
            'severity' => 'danger',
            'unixtime' => get_time()
        ];
        if ($config['web_show_notifications']) {
            $alerts[] = [
                'title'    => 'PHP version is old',
                'text'     => 'Your currently installed PHP version <b>' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION .
                    "</b> is older than the recommended minimum <b>7.2.x</b>.",
                'severity' => 'alert'
            ];
        }
    }

    // Same for MySQL/MariaDB
    $versions = get_versions();
    if ($versions['mysql_old']) {
        $mysql_recommented = $versions['mysql_name'] === 'MariaDB' ? OBS_MIN_MARIADB_VERSION : OBS_MIN_MYSQL_VERSION;

        $notifications[] = [
            'text'     => '<h4>Your ' . $versions['mysql_name'] . ' version is old.</h4>
                           Your currently installed ' . $versions['mysql_name'] . ' version <b>' . $versions['mysql_version'] . '</b>
                           is older than the recommended minimum.
                           Please upgrade your version of ' . $versions['mysql_name'] . ' to prevent possible incompatibilities and security problems.<br/>
                           Currently recommended version is <b>' . $mysql_recommented . '</b> and newer.',
            'severity' => 'warning',
            'unixtime' => get_time()
        ];
        if ($config['web_show_notifications']) {
            $alerts[] = [
                'title'    => $versions['mysql_name'] . ' version is old',
                'text'     => 'Your currently installed ' . $versions['mysql_name'] . ' version <b>' . $versions['mysql_version'] .
                    "</b> is older than the recommended minimum <b>$mysql_recommented</b>.",
                'severity' => 'warning'
            ];
        }
    }

    // Warning about obsolete config on some pages
    if (OBS_DEBUG) {
        // FIXME move to notification center?
        print_obsolete_config();
    }

}
// Sort by unixtime
$notifications = array_sort_by($notifications, 'unixtime', SORT_DESC, SORT_NUMERIC);

// Clean cache item
unset($notifications_item, $alerts_item);
//del_cache_expired();
//print_vars(get_cache_items('__wui'));
//print_vars(get_cache_stats());

// EOF
