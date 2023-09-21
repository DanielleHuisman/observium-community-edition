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

//FIXME. Need sysDescr examples
if (str_contains_array($poll_device['sysDescr'], ['DSR', 'AV3'])) {
    [$hardware, $version] = explode(' ', $poll_device['sysDescr']);
    $hardware = trim($hardware);
    $version  = trim($version);
}

// EOF
