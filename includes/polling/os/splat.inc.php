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

if (empty($hardware)) // Fallback since svnApplianceProductName (from definitions) is only supported since R77.10
{
    $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
}

// EOF
