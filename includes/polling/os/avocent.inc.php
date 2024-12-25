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

//FIXME. Need sysDescr examples
if (str_contains_array($poll_device['sysDescr'], ['DSR', 'AV3'])) {
    [$hardware, $version] = explode(' ', $poll_device['sysDescr']);
    $hardware = trim($hardware);
    $version  = trim($version);
}

// EOF
