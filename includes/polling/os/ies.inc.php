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

preg_match('/IES-(\d)*/', $poll_device['sysDescr'], $matches);
$hardware = $matches[0];

// EOF
