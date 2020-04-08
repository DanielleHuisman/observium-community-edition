<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

if (!$hardware)
{
  $hardware = rewrite_definition_hardware($device, $poll_device['sysObjectID']);
  $fn_type  = rewrite_definition_type($device, $poll_device['sysObjectID']);
  if (!empty($fn_type))
  {
    $type = $fn_type;
  }
}

// EOF
