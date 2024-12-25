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

if (preg_match('/^(?:HP )?ProCurve (?:AP|Access Point) .*?(?<hardware>\w+)(?:, (?:revision )?|: v)(?<version>[\w\.]+)/', $poll_device['sysDescr'], $matches)) {
    // ProCurve Access Point 10ag WW J9141A, revision WM.01.17, boot version WAB.01.00
    // HP ProCurve Access Point 420: v2.2.3 v3.0.6
    $hardware = 'AP ' . $matches['hardware'];
    $version  = $matches['version'];
} elseif (preg_match('/^(?:HP )?ProCurve (?:AP|Access Point) (?<hardware>\w+?)v(?<version>[\d\.]+).+ SN-(?<serial>\w+)/', $poll_device['sysDescr'], $matches)) {
    // HP ProCurve AP 520wlv2.4.5(758) SN-PG34JL9CWY23 v2.0.10
    $hardware = 'AP ' . $matches['hardware'];
    $version  = $matches['version'];
    $serial   = $matches['serial'];
}

// EOF
