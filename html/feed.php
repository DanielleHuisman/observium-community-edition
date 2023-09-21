<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage feed
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

include_once("../includes/observium.inc.php");

if (is_iframe()) {
    display_error_http(403, 'Not allowed to run in a iframe');
}

//include($config['html_dir'] . "/includes/authenticate.inc.php"); // not for RSS!

$auth = FALSE;
$vars = get_vars('GET');

// Auth
if (isset($vars['hash']) && strlen($vars['hash']) >= 16 &&
    is_numeric($vars['id']) && $vars['id'] > 0) {
    $key = get_user_pref($vars['id'], 'atom_key');
    if ($key) {
        // Check hash auth
        if ($data = decrypt($vars['hash'], $key)) {
            //var_dump($data);
            $data = explode('|', $data); // user_id|user_level|auth_mechanism

            $data_c = count($data);
            if ($data_c == 3) {
                $user_id              = $data[0];
                $user_level           = $data[1]; // FIXME, need new way for check userlevel, because it can be changed
                $check_auth_mechanism = $config['auth_mechanism'] == $data[2];

                // Now set auth
                $auth = $check_auth_mechanism && $user_level > 0 && $user_id == $vars['id'];
            }
            //else if ($data_c == 2)
            //{
            //  // Force delete old keys without auth_mechanism
            //}
        }
    }
}

//var_dump($auth);
if (!$auth) {
    display_error_http(401, 'Update feed url');
}
// End auth

session_start();
$_SESSION['user_id']   = $user_id;
$_SESSION['userlevel'] = $user_level;

$permissions = permissions_cache($_SESSION['user_id']);
session_write_close();

include($config['html_dir'] . "/includes/cache-data.inc.php"); // Need for check permissions

$use_rss = $vars['v'] === 'rss'; // In most cases used ATOM feed
$param   = ['short' => TRUE, 'pagesize' => 25];
if (is_numeric($vars['size'])) {
    $param['pagesize'] = $vars['size'];
}

// base feed info
$base_url         = rtrim($GLOBALS['config']['base_url'], '/');
$feed_generator   = OBSERVIUM_PRODUCT . ' ' . OBSERVIUM_VERSION;
$feed_title       = 'Observium [' . $_SERVER["SERVER_NAME"] . '] :: Eventlog Feed';
$feed_description = "Latest eventlogs from observium on $base_url";
$feed_link        = $base_url . '/eventlog/';

$events = get_events_array($param);
if ($use_rss) {
    // create rss
    // See format options here: http://validator.w3.org/feed/docs/rss2.html
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/"></rss>');
    $xml -> addChild('channel');
    $xml -> channel -> addChild('title', $feed_title);
    $xml -> channel -> addChild('description', $feed_description);
    $xml -> channel -> addChild('link', $feed_link);
    $xml -> channel -> addChild('language', 'en-us');
    $xml -> channel -> addChild('generator', $feed_generator);
    $xml -> channel -> addChild('pubDate', date(DATE_RSS, strtotime($events['updated'])));
    $xml -> channel -> addChild('ttl', '5'); // a number of minutes that indicates how long a channel can be cached before refreshing
} else {
    // create atom
    // See format options here: http://validator.w3.org/feed/docs/atom.html
    $atom_ns = 'http://www.w3.org/2005/Atom';
    $xml     = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><feed xml:lang="en-US" xmlns="' . $atom_ns . '"></feed>');
    $xml -> addChild('title', $feed_title);
    $xml -> addChild('subtitle', $feed_description);
    $xml -> addChild('id', $feed_link);
    $xml -> addChild('icon', $base_url . '/' . $GLOBALS['config']['favicon']);
    $xml -> addChild('link');
    $xml -> link -> addAttribute('href', $feed_link);
    $self_link = $xml -> addChild('link', '', $atom_ns);
    $self_link -> addAttribute('href', $base_url . $_SERVER['REQUEST_URI']);
    $self_link -> addAttribute('rel', 'self');
    $self_link -> addAttribute('type', 'application/atom+xml');
    $xml -> addChild('generator', $feed_generator);
    $xml -> addChild('updated', date(DATE_ATOM, strtotime($events['updated'])));
}

foreach ($events['entries'] as $entry) {
    $entry_device      = device_by_id_cache($entry['device_id']);
    $entry_vars        = ['page'           => 'device',
                          'device'         => $entry['device_id'],
                          'tab'            => 'logs',
                          'section'        => 'eventlog',
                          'type'           => $entry['type'],
                          'timestamp_from' => $entry['timestamp'],
                          'timestamp_to'   => $entry['timestamp']];
    $entry_title       = escape_html('[' . $entry_device['hostname'] . '] ' . $entry['message']);
    $entry_description = escape_html('[' . $entry_device['hostname'] . "]\n" . strtoupper($entry['type']) . ': ' . $entry['message']);
    $entry_link        = $base_url . '/' . generate_device_url($entry_device, $entry_vars);
    $entry_id          = $entry_link . 'guid=' . md5($entry['event_id']);

    if ($use_rss) {
        // add item element for each article
        $item = $xml -> channel -> addChild('item');
        $item -> addChild('title', $entry_title);
        $item -> addChild('description', $entry_description);
        $item -> addChild('guid', $entry_id);
        $item -> addChild('link', $entry_link);
        $item -> addChild('h:dc:creator', $entry['type']);
        $item -> addChild('pubDate', date(DATE_RSS, strtotime($entry['timestamp'])));
    } else {
        // add entry element for each article
        $item = $xml -> addChild('entry');
        $item -> addChild('title', $entry_title);
        $item -> addChild('summary', $entry_description);
        $item -> addChild('id', $entry_id);
        $item -> addChild('link');
        $item -> link -> addAttribute('href', $entry_link);
        $item -> addChild('author');
        $item -> author -> addChild('name', $entry['type']);
        $item -> addChild('updated', date(DATE_ATOM, strtotime($entry['timestamp'])));
    }
}

// Unset & destroy session
session_unset();
session_destroy();

// Print feed
header('Content-Type: text/xml; charset=utf-8');
echo $xml -> asXML();


// DOCME needs phpdoc block
function content_cdata($content)
{
    return '<![CDATA[' . $content . ']]>';
}

// EOF
