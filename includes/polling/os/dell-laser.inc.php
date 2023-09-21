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

# SNMPv2-SMI::enterprises.253.8.51.10.2.1.7.2.28110202 = STRING: "MFG:Dell;CMD:PJL,RASTER,DOWNLOAD,PCLXL,PCL,POSTSCRIPT;MDL:Laser Printer
# 3100cn;DES:Dell Laser Printer 3100cn;CLS:PRINTER;STS:AAAMAwAAAAAAAgJ/HgMKBigDCgY8AwAzcJqwggAAwAAACAAAAAAA/w==;"

$modelinfo = explode(';', snmp_get($device, '1.3.6.1.4.1.253.8.51.10.2.1.7.2.28110202', '-OQv'));

# SNMPv2-SMI::enterprises.674.10898.100.2.1.2.1.3.1 = STRING: "COMMAND SET:;MODEL:Dell Laser Printer 5310n"
$modelinfo = array_merge($modelinfo, explode(';', snmp_get($device, '1.3.6.1.4.1.674.10898.100.2.1.2.1.3.1', '-OQv')));

# SNMPv2-SMI::enterprises.641.2.1.2.1.3.1 = STRING: "COMMAND SET:;MODEL:Dell Laser Printer 1700n"
$modelinfo = array_merge($modelinfo, explode(';', snmp_get($device, '1.3.6.1.4.1.641.2.1.2.1.3.1', '-OQv')));

foreach ($modelinfo as $line) {
    [$key, $value] = explode(':', $line);
    $dell_laser[$key] = $value;
}

$hardware = ($dell_laser['MDL'] != '' ? $dell_laser['MDL'] : $dell_laser['MODEL']);

[, $version] = explode('Engine ', $poll_device['sysDescr']);

if ($version) {
    $version = 'Engine ' . trim($version, ')');
} else {
    $version = snmp_get($device, '1.3.6.1.4.1.674.10898.100.1.1.1.0', '-OQv');
    if (!$version) {
        $version = snmp_get($device, '1.3.6.1.4.1.641.1.1.1.0', '-OQv');
    }
}

// EOF
