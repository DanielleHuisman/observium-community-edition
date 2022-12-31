<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

if ($fn_hw = rewrite_definition_hardware($device, $poll_device['sysObjectID'])) {
  // Prefer defined hardware
  $hardware = $fn_hw;
  $fn_type  = rewrite_definition_type($device, $poll_device['sysObjectID']);
  if (!empty($fn_type)) {
    $type = $fn_type;
  }
} elseif (str_contains($hardware, 'WiFi')) {
  $type = 'wireless';
}

// EOF
