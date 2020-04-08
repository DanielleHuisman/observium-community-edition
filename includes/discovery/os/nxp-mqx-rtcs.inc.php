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

/// YAH, leave this here, need run it as last :(

if (!$os)
{
  if (str_starts($sysDescr, 'RTCS version'))
  {
    $os = 'nxp-mqx-rtcs';
  }

  if ($os == 'nxp-mqx-rtcs')
  {
    // Accuenergy Accuvim II
    if (snmp_get($device, '.1.3.6.1.4.1.39604.1.1.1.1.6.20.0', '-OQv') != '') { $os = 'accuvimii'; }
  }

}

?>
