<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// sysDescr.0 = STRING: "MyPower S3100-9TP"
// sysDescr.0 = STRING: "MyPower S3100-20TP"
// sysDescr.0 = STRING: "MyPower S3100-28TP"
// sysDescr.0 = STRING: "MyPower S3100-9TC"
// sysDescr.0 = STRING: "Switch"

if (!$hardware || !$version) {
// .1.3.6.1.4.1.5651.1.2.1.1.2.2.0 = STRING: "MyPower S3200-10TP V6.2.3.10"
    if (preg_match('/(?<hardware>MyPower \w\S+)( (version|V)\s*(?<version>[\d\.]+))?/i', snmp_get_oid($device, '.1.3.6.1.4.1.5651.1.2.1.1.2.2.0'), $matches)) {
        if ($matches['hardware']) { $hardware = $matches['hardware']; }
        if ($matches['version'])  { $version  = $matches['version']; }
    }
}
$serial = snmp_get_oid($device, '.1.3.6.1.4.1.5651.1.2.1.1.2.19.0');

// EOF
