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

// Display devices as a list in detailed format

$header = [
    'state-marker' => '',
    '',
    [ 'hostname' => 'Hostname', 'domain' => 'Domain', 'location' => 'Location' ],
    '',
    [ 'os' => 'Operating System', 'hardware' => 'Hardware Platform' ],
    [ 'uptime' => 'Uptime', 'sysName' => 'sysName']
];

//r($table_header);

?>

    <table class="table table-hover table-striped table-condensed ">
        <?php

        echo generate_table_header($header, $vars);

        foreach ($devices as $device) {
            if (device_permitted($device['device_id'])) {
                if (!$location_filter || $device['location'] == $location_filter) {
                    $vars['view'] = 'details';
                    print_device_row($device, $vars);
                }
            }
        }

        ?>

    </table>

<?php

// EOF
