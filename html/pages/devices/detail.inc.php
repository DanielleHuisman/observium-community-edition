<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Display devices as a list in detailed format
?>

<table class="table table-hover table-striped  table-condensed ">
  <thead>
    <tr>
      <th class="state-marker"></th>
      <th></th>
      <th>Device / Location</th>
      <th></th>
      <th>Operating System / Hardware Platform</th>
      <th>Uptime / sysName</th>
    </tr>
  </thead>

<?php
foreach ($devices as $device)
{
  if (device_permitted($device['device_id']))
  {
    if (!$location_filter || $device['location'] == $location_filter)
    {
      print_device_row($device, 'details');
    }
  }
}

?>

</table>

<?php

// EOF
