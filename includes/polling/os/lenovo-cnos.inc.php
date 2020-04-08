<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

 /* Available properties:
  * entPhysicalName = 'NE2572'
  * entPhysicalModelName = '7159-HEB'
  * entPhysicalDescr = 'Lenovo ThinkSystem NE2572 RackSwitch' <-- same as sysDescr
  * entPhysicalSoftwareRev = '10.8.0.3'
  * entPhysicalSerialNum = 'MM0123'
  */
if ($entPhysical['entPhysicalName'])
{
  $hardware = $entPhysical['entPhysicalName'];
  if ($entPhysical['entPhysicalModelName'])
  {
    $hardware .= " (" . $entPhysical['entPhysicalModelName'] . ")";
  }
}

if ($entPhysical['entPhysicalSoftwareRev'])
{
  $version = $entPhysical['entPhysicalSoftwareRev'];
}

/* enrPhysicalSerialNum can come up empty */
if ($entPhysical['entPhysicalSerialNum'] && $entPhysical['entPhysicalSerialNum'] != "<EMPTY>")
{
  $serial = $entPhysical['entPhysicalSerialNum'];
}

// EOF
