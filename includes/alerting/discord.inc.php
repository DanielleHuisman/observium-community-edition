<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage alerting
 * @copyright  (C) Adam Armstrong
 *
 */

// FIXME: This is fairly messy and crude. Feel free to improve it!

// Slack only params
if ($message_tags['ALERT_STATE'] === "SYSLOG") {
    $color = "8410368";
} elseif ($message_tags['ALERT_STATE'] === "RECOVER") {
    $color = "2850816";
} elseif (str_contains($message_tags['ALERT_STATE'], "REMINDER")) {
    $color = "8388651";
} else {
    $color = "8388651";
}

$emoji = ':' . $message_tags['ALERT_EMOJI_NAME'] . ':';

// JSON data
$data = [
//  "username"   => $endpoint['username'],
//  'icon_emoji' => $emoji,
//"text"       => $title,
];

if (isset($endpoint['short']) && $endpoint['short'] === 'true') {
    // Short format
    $data['embeds'][] = [
      'title' => $message_tags['TITLE'],
      'url'   => $message_tags['ALERT_URL'],
      'color' => $color
    ];

} else {

    $data['embeds'][] = [
      'title'  => $emoji . ' ' . $message_tags['TITLE'],
      'url'    => $message_tags['ALERT_URL'],
      'color'  => $color,
      //'text'       => simple_template('slack_text.tpl', $message_tags, array('is_file' => TRUE)),
      'fields' => [
        [
          'name'   => 'Device/Location',
          'value'  => $message_tags['DEVICE_HOSTNAME'] . " (" . $message_tags['DEVICE_OS'] . ")" . PHP_EOL . $message_tags['DEVICE_LOCATION'],
          'inline' => TRUE,
        ],
        [
          'name'   => 'Entity',
          'url'    => $message_tags['ENTITY_URL'],
          'value'  => $message_tags['ENTITY_TYPE'] . " / " . $message_tags['ENTITY_NAME'] .
                      (isset($message_tags['ENTITY_DESCRIPTION']) ? PHP_EOL . $message_tags['ENTITY_DESCRIPTION'] : ''),
          'inline' => TRUE,
        ],
        [
          'name'  => 'Alert Message/Duration',
          'value' => $message_tags['ALERT_MESSAGE'] . PHP_EOL . $message_tags['DURATION'],
          //'inline' => TRUE,
        ],
        [
          'name'  => 'Metrics',
          'value' => str_replace("             ", "", $message_tags['METRICS']),
          //'inline' => TRUE,
        ],
      ],
    ];

    /*
    foreach ($graphs as $graph)
    {
        $data['attachments'][] = array('fallback' => "Graph Image",
          'title' => $graph['label'],
          'image_url' => $graph['url'],
          'color' => 'danger');

    }
    */

}


// EOF
