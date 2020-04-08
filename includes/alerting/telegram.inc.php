<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage alerting
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$message['text'] = simple_template($endpoint['contact_method'] . '_text', $message_tags, array('is_file' => TRUE));

$url = 'https://api.telegram.org/bot' . $endpoint['bot_hash'] . '/sendMessage';

// POST Data
$postdata = http_build_query(
  array(
    "chat_id"                  => $endpoint['recipient'],
    "disable_web_page_preview" => 'true',                 // Disables link previews for links in message
    "text"                     => $message['text'])
);

$context_data = array(
  'method'  => 'POST',
  'content' => $postdata
);

// Send out API call and parse response into an associative array
$response = get_http_request($url, $context_data);

$notify_status['success'] = FALSE;
if ($response !== FALSE)
{
  $response = json_decode($response, TRUE);
  //var_dump($response);
  if (isset($response['ok']) && $response['ok'] == TRUE) { $notify_status['success'] = TRUE; }
}

unset($url, $send, $message, $response, $postdata, $context_data);

// EOF

