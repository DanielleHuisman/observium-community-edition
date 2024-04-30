<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$valid['neighbours'] = [];

// SELECT * FROM `autodiscovery` WHERE `remote_device_id` IS NOT NULL AND `remote_device_id` NOT IN (SELECT `device_id` FROM `devices`)

// Include all discovery modules
$include_dir   = "includes/discovery/neighbours";
$include_order = 'default'; // Use MIBs from default os definitions by first!
include("includes/include-dir-mib.inc.php");

$table_rows    = [];
$neighbours_db = dbFetchRows('SELECT * FROM `neighbours` WHERE `device_id` = ?', [ $device['device_id'] ]);
foreach ($neighbours_db as $neighbour) {
    $local_port_id   = $neighbour['port_id'];
    $remote_hostname = $neighbour['remote_hostname'];
    $remote_address  = $neighbour['remote_address'];
    $remote_port     = $neighbour['remote_port'];
    $valid_host_key  = $remote_hostname;
    if (strlen($remote_address)) {
        $valid_host_key .= '-' . $remote_address;
    }
    print_debug("$local_port_id -> $remote_hostname ($remote_address) -> $remote_port");
    if (!$valid['neighbours'][$local_port_id][$valid_host_key][$remote_port]) {
        // Do not remove deleted from db
        //dbDelete('neighbours', '`neighbour_id` = ?', array($neighbour['neighbour_id']));
        if ($neighbour['active'] == '1') {
            dbUpdate(['active' => 0], 'neighbours', '`neighbour_id` = ?', [$neighbour['neighbour_id']]);
            $GLOBALS['module_stats'][$module]['deleted']++;
        }
    } else {
        $port = get_port_by_id_cache($local_port_id);
        if (is_numeric($neighbour['remote_port_id']) && $neighbour['remote_port_id']) {
            $remote_port_array = get_port_by_id_cache($neighbour['remote_port_id']);
            $remote_port       = $remote_port_array['port_label'];
        }
        if (strlen($remote_address)) {
            $remote_hostname .= " ($remote_address)";
        }
        $table_rows[] = [nicecase($neighbour['protocol']), $port['port_label'], $remote_hostname, $remote_port, truncate($neighbour['remote_platform'], 20), truncate($neighbour['remote_version'], 40)];
    }
}

echo(PHP_EOL);
$table_headers = ['%WProtocol%n', '%WifName%n', '%WRemote: hostname%n', '%Wport%n', '%Wplatform%n', '%Wversion%n'];
print_cli_table($table_rows, $table_headers);

$GLOBALS['module_stats'][$module]['status'] = safe_count($valid[$module]);
if (OBS_DEBUG && $GLOBALS['module_stats'][$module]['status']) {
    print_vars($valid[$module]);
}

unset($valid['neighbours']);
echo(PHP_EOL);

// EOF
