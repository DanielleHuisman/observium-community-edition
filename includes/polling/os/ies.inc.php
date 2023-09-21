<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

preg_match('/IES-(\d)*/', $poll_device['sysDescr'], $matches);
$hardware = $matches[0];

// EOF
