#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     snmptraps
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

chdir(dirname($argv[0]));

$options = getopt("d");
if (isset($options['d'])) {
    array_shift($argv);
} // for compatibility

include("includes/observium.inc.php");

$entry = explode(",", $argv[1]);

logfile('SNMPTRAP: ' . $argv[1]);

#print_vars($entry);

$device = @dbFetchRow("SELECT * FROM devices WHERE `hostname` = ?", [$entry['0']]);

if (!$device['device_id']) {
    $device = @dbFetchRow("SELECT * FROM ipv4_addresses AS A, ports AS I WHERE A.ipv4_address = ? AND I.port_id = A.port_id", [$entry['0']]);
}

if (!$device['device_id']) {
    exit;
}

$file = $config['install_dir'] . "/includes/snmptrap/" . $entry['1'] . ".inc.php";
if (is_file($file)) {
    include("$file");
} else {
    echo("unknown trap ($file)");
}

// EOF
