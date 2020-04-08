<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2015 Observium Limited
 *
 */

if ($entPhysical['entPhysicalDescr'] && $entPhysical['entPhysicalName'] && $entPhysical['entPhysicalSoftwareRev'])
{
  $hardware = $entPhysical['entPhysicalModelName']; # . ' ' . $entPhysical['entPhysicalName'];
  $version = $entPhysical['entPhysicalSoftwareRev'];
  $serial   = $entPhysical['entPhysicalSerialNum'];
  return;
}

// EOF
