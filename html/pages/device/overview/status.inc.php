<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$sql = "SELECT * FROM `status`";
$sql .= " WHERE `device_id` = ? AND `status_deleted` = 0 ORDER BY `measured_entity_label`, `entPhysicalClass` DESC, `status_descr`;";

$statuses = dbFetchRows($sql, [ $device['device_id'] ]);

if (!safe_empty($statuses)) {
    $box_args = [
      'title' => 'Status Indicators',
      'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => 'status']),
      'icon'  => $config['entities']['status']['icon'],
    ];

    // show/hide ignored
    $ignore = 0;
    foreach ($statuses as $status) {
        if ($status['status_event'] === 'ignore') {
            $ignore++;
        }
    }
    if ($ignore) {
        $box_args['header-controls'] = [
          'controls' => [
            'hide' => [ 'text' => 'Show/Hide Ignored <span class="label">' . $ignore . '</span>',
                        'anchor' => TRUE,
                        'data' => ' onclick="$(\'.entity-status.disabled\').toggle();" ']
          ]
        ];
    }
    echo generate_box_open($box_args);

    echo('<table class="table table-condensed table-striped">');
    foreach ($statuses as $status) {
        //$status['status_descr'] = truncate($status['status_descr'], 48, '');

        print_status_row($status, $vars);
    }

    echo("</table>");
    echo generate_box_close();
}

// EOF
