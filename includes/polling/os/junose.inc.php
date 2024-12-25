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

if (empty($hardware) && strpos($poll_device['sysDescr'], 'olive')) {
    $hardware = 'Olive';
    $serial   = '';
}

// EOF
