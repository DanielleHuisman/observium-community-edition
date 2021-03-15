<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

if (empty($hardware) && strpos($poll_device['sysDescr'], 'olive'))
{
  $hardware = 'Olive';
  $serial = '';
}

// EOF
