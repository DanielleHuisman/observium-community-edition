<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

echo '
  <table class="table table-hover table-striped  table-condensed">
  <thead>
    <tr>
      <th class="state-marker"></th>
      <th></th>
      <th>Device / Location</th>
      <th>Hardware / Features</th>
      <th>Operating System</th>
      <th>Uptime / sysName</th>
    </tr>
  </thead>';

foreach ($devices as $device) {
    if (device_permitted($device['device_id'])) {
        if (!$location_filter || $device['location'] == $location_filter) {
            $vars['view'] = 'basic';
            print_device_row($device, $vars);
        }
    }
}

echo("  </table>");

// EOF
