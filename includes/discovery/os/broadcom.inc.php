<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

/// YAH, leave this here, still many device use same sysObjectId as here :(

if ($os) { return; }

if (match_sysObjectID($sysObjectID, '.1.3.6.1.4.1.4413') || $sysObjectID == '.1.3.6.1.4.1.7244')
{
  $os = 'broadcom_fastpath'; // Generic Broadcom
  if ($sysObjectID == '.1.3.6.1.4.1.4413' && preg_match('/bcm963/i', $sysDescr))
  {
    //Broadcom Bcm963xx Software Version 3.00L.01V.
    //Broadcom Bcm963xx Software Version A131-306CTU-C08_R04
    //$os = 'comtrend-';
  }

}

// EOF
