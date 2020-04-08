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

if (preg_match('/^Meraki ([A-Z\-_0-9]+) (.*)/', $poll_device['sysDescr'], $matches))
{
  $hardware = $matches[1];
  $platform = $matches[2];

  if (str_contains($platform, 'AP') || str_starts($hardware, 'MR'))
  {
    // Meraki MR34 Cloud Managed AP
    $type = 'wireless';
  }
  else if (str_contains($platform, 'Security') || str_starts($hardware, 'MX'))
  {
    // Meraki MX100 Cloud Managed Security Appliance
    $type = 'firewall';
  }
  // else keep network for switches MS
}

// EOF
