<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) Adam Armstrong
 *
 */

echo generate_box_open();

echo('<table class="table table-condensed table-striped ">' . PHP_EOL);
echo('  <thead>' . PHP_EOL);
echo('    <tr>' . PHP_EOL);
echo('      <th style="width: 300px;">Package name</th>' . PHP_EOL);
echo('      <th>Version</th>' . PHP_EOL);
echo('      <th>Architecture</th>' . PHP_EOL);
echo('      <th>Type</th>' . PHP_EOL);
echo('      <th>Size</th>' . PHP_EOL);
echo('    </tr>' . PHP_EOL);
echo('  </thead>' . PHP_EOL);
echo('  <tbody>' . PHP_EOL);

foreach (dbFetchRows("SELECT * FROM `packages` WHERE `device_id` = ? ORDER BY `name`", [ $device['device_id'] ]) as $entry) {

    $dbuild = !safe_empty($entry['build']) ? '-' . $entry['build'] : '';

    echo('    <tr>' . PHP_EOL);
    echo('      <td class="entity"><a href="' . generate_url(['page' => 'packages', 'name' => $entry['name']]) . '">' . $entry['name'] . '</a></td>' . PHP_EOL);
    echo('      <td>' . $entry['version'] . $dbuild . '</td>' . PHP_EOL);
    echo('      <td>' . get_type_class_label($entry['arch'], 'arch') . '</td>' . PHP_EOL);
    echo('      <td>' . get_type_class_label($entry['manager'], 'pkg') . '</td>' . PHP_EOL);
    echo('      <td>' . format_si($entry['size']) . '</td>' . PHP_EOL);
    echo('    </tr>' . PHP_EOL);
}

echo('  </tbody>' . PHP_EOL);
echo('</table>' . PHP_EOL);

echo generate_box_close();

// EOF
