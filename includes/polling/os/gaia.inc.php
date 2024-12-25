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

if (empty($hardware)) // Fallback since svnApplianceProductName is only supported since R77.10
{
    $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
}

// EOF
