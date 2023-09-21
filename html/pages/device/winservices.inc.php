<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

echo generate_box_open();

echo('<table class="table table-condensed table-striped ">' . PHP_EOL);
echo('  <thead>' . PHP_EOL);
echo('    <tr>' . PHP_EOL);
echo('      <th style="width: 200px;">Service</th>' . PHP_EOL);
echo('      <th>Display Name</th>' . PHP_EOL);
echo('      <th>State</th>' . PHP_EOL);
echo('      <th>Start Mode</th>' . PHP_EOL);
echo('    </tr>' . PHP_EOL);
echo('  </thead>' . PHP_EOL);
echo('  <tbody>' . PHP_EOL);

$i = 0;
foreach (dbFetchRows("SELECT * FROM `winservices` WHERE `device_id` = ? ORDER BY `name`", [$device['device_id']]) as $entry) {

    switch ($entry['state']) {
        case "Running":
            $entry['state_class'] = 'label-success';
            break;
        case "Stopped":
            $entry['state_class'] = 'label-warning';
            break;
    }

    switch ($entry['startmode']) {
        case "Manual":
            $entry['startmode_class'] = 'label-info';
            break;
        case "Disabled":
            $entry['startmode_class'] = 'label-warning';
            break;
        case "Auto":
            $entry['startmode_class'] = 'label-success';
            break;
    }

    echo('    <tr>' . PHP_EOL);
    echo('      <td><span class="entity-name">' . $entry['name'] . '</td>' . PHP_EOL);
    echo('      <td class="entity"><a href="' . generate_url(['page' => 'winservices', 'displayname' => $entry['displayname']]) . '">' . $entry['displayname'] . '</a></td>' . PHP_EOL);
    echo('      <td><span class="label ' . $entry['state_class'] . '">' . $entry['state'] . '</span></td>' . PHP_EOL);
    echo('      <td><span class="label ' . $entry['startmode_class'] . '">' . $entry['startmode'] . '</span></td>' . PHP_EOL);
    echo('    </tr>' . PHP_EOL);

    $i++;
}

echo('  </tbody>' . PHP_EOL);
echo('</table>' . PHP_EOL);

echo generate_box_close();

// EOF
