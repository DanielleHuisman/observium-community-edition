<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     alerting
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// FIXME: This is fairly messy and crude. Feel free to improve it!

// Slack only params
switch ($message_tags['ALERT_STATE']) {
    case "RECOVER":
        $color = "2850816";
        break;

    case "SYSLOG":
        $color = "8410368";
        break;

    default:
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
