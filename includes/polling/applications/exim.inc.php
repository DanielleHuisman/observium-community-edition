<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

if (!empty($agent_data['app']['exim']) && is_string($agent_data['app']['exim'])) {

  $app_id = discover_app($device, 'exim');

  foreach (explode("\n", $agent_data['app']['exim']) as $line) {
    if (strpos($line, ':') !== false) {
      [$item, $value] = explode(":", $line, 2);
      $exim_data[trim($item)] = trim($value);
    }
  }

  rrdtool_update_ng($device, 'exim', $exim_data, $app_id);
  update_application($app_id, $exim_data);

  unset($exim_data, $item, $value, $app_id);
}

// EOF
