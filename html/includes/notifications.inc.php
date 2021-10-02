<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
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
    $latest = [];
    $latest['version']  = get_obs_attrib('latest_ver');
    $latest['revision'] = get_obs_attrib('latest_rev');
    $latest['date']     = get_obs_attrib('latest_rev_date');

    if ($config['version_check'] && $latest['revision'] > OBSERVIUM_REV + $config['version_check_revs']) {
      $notifications[] = array('text' => '<h4>There is a newer revision of Observium available!</h4> Version '. $latest['version'] .' ('.format_unixtime(datetime_to_unixtime($latest['date']), 'jS F Y').') is ' .($latest['revision']-OBSERVIUM_REV) .' revisions ahead.', 'severity' => 'warning',
                               'unixtime' => $config['time']['now']);
      $alerts[] = [
        'title' => 'There is a newer revision of Observium available!',
        'text' => 'Version '. $latest['version'] . ' (' . format_unixtime(datetime_to_unixtime($latest['date']), 'jS F Y').') is ' . ($latest['revision'] - OBSERVIUM_REV) .' revisions ahead.',
        'severity' => 'warning'
      ];
    }


    if (version_compare(PHP_VERSION, OBS_MIN_PHP_VERSION, '<')) {
      $notifications[] = array('text' => '<h4>Your PHP version is too old.</h4>
                                          Your currently installed PHP version <b>' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION . '</b>
                                          is older than the required minimum.
                                          Please upgrade your version of PHP to prevent possible incompatibilities and security problems.<br/>
                                          Currently recommended version(s): <b>7.2.x</b> and newer.',
                               'severity' => 'danger',
                               'unixtime' => $config['time']['now']);
    }

//if (!function_exists('hash_exists'))
//{
//  $alerts[] = array('text' => '<b>The PHP `hash` module is missing</b> This will cause editing and other forms to fail. Please install it.', 'severity' => 'danger');
//}

    // Warn about lack of encryption unless told not to.
    if ($config['login_remember_me'] || isset($_SESSION['encrypt_required'])) {
      if (!OBS_ENCRYPT) {
        print_warning('For use the "remember me" feature, required php version not less than 7.2 (with "sodium" extension) or "mcrypt" extension. Alternatively, you can disable this feature by setting $config[\'login_remember_me\'] = FALSE; in your config.');
      }
    }

    // Warning about web_url config, only for ssl
    if (is_ssl() && preg_match('/^http:/', $config['web_url']))
    {
      $notifications[] = array('text' => 'Setting \'web_url\' for "External Web URL" not set or incorrect, please update on ' . generate_link('Global Settings Edit', array('page' => 'settings', 'section' => 'wui')) . ' page.', 'severity' => 'warning',
                               'unixtime' => $config['time']['now']);
    }

    // Warning about need DB schema update
    $db_version = get_db_version();
    $db_version = sprintf("%03d", $db_version+1);
    if (is_file($config['install_dir'] . "/update/$db_version.sql") || is_file($config['install_dir'] . "/update/$db_version.php")) {
      $notifications[] = array('text' => 'Your database schema is old and needs updating. Run from server console:
                                          <pre style="padding: 3px" class="small">' . $config['install_dir'] . '/discovery.php -u</pre>', 'severity' => 'alert',
                               'unixtime' => $config['time']['now']);
    }
    unset($db_version);

    // Check mysqli extension
    if (OBS_DB_EXTENSION !== 'mysqli' && check_extension_exists('mysqli', '')) {
      $notifications[] = array('title'    => 'Deprecated MySQL Extension',
                               'text'     => 'The deprecated mysql extension is still in use, we recommend using mysqli.<br />To switch, add the following to your config.php: <pre>$config[\'db_extension\']  = \'mysqli\';</pre>',
                               'severity' => 'warning',
                               'unixtime' => $config['time']['now']);
    }
    //$notifications[] = array('text' => dbHostInfo(), 'severity' => 'debug');

    // Get GLOBAL events for last 3 days
    foreach (dbFetchRows('SELECT * FROM `eventlog` WHERE `entity_type` = ? AND `timestamp` > (CURDATE() - INTERVAL 3 DAY)', array('global')) as $entry) {
      $notifications[] = array('unixtime' => strtotime($entry['timestamp']),
                               'text'     => $entry['message'],
                               'severity' => $entry['severity']);
    }

    // Warning about obsolete config on some pages
    if (OBS_DEBUG ||
        in_array($vars['tab'], array('data', 'perf', 'edit', 'showtech')) ||
        in_array($vars['page'], array('pollerlog', 'settings', 'preferences')))
    {
      // FIXME move to notification center?
      print_obsolete_config();
    }
  }

  if (isset($config['alerts']['suppress']) && $config['alerts']['suppress']) {
    $notifications[] = array('text' => '<h4>All Alert Notifications Suppressed</h4>'.
                                       'All alert notifications have been suppressed in the configuration.',
                             'severity' => 'warning',
                             'unixtime' => $config['time']['now']);
  }

  // Display warning for scheduled maintenance
  if (isset($cache['maint']['count']) && $cache['maint']['count'] > 0) {
    $notifications[] = array('text' => '<h4>Scheduled Maintenance in Progress</h4>'.
                                     'Some or all alert notifications have been suppressed due to a scheduled maintenance.',
                             'severity' => 'warning',
                             'unixtime' => $config['time']['now']);


    $alerts[] = [
      'title'    => 'Scheduled Maintenance in Progress',
      'text'     => 'Some or all alert notifications have been suppressed due to a scheduled maintenance.',
      'severity' => 'warning'
    ];
  }

  // Store $notifications and $alerts in fast caching
  set_cache_item($notifications_item, $notifications, array('ttl' => 900)); // 15 min
  set_cache_item($alerts_item,        $alerts,        array('ttl' => 900)); // 15 min

  // Clear expired cache
  del_cache_expired();

  // End build cache
} else {

  //print_vars(get_cache_data($notifications_item));
  $notifications = array_merge(get_cache_data($notifications_item), $notifications);
  $alerts        = array_merge(get_cache_data($alerts_item),        $alerts);
}

// Sort by unixtime
$notifications = array_sort_by($notifications, 'unixtime', SORT_DESC, SORT_NUMERIC);

// Clean cache item
unset($notifications_item, $alerts_item);
//del_cache_expired();
//print_vars(get_cache_items('__wui'));
//print_vars(get_cache_stats());

// EOF
