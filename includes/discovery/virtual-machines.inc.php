<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Run additional supported VM modules (known: libvirt and proxmox)
if (isset($config['os'][$device['os']]['virtual-machines'])) {
    foreach ((array)$config['os'][$device['os']]['virtual-machines'] as $vm_type) {
        if (!is_file($config['install_dir'] . "/includes/discovery/virtual-machines/$vm_type.inc.php")) {
            continue;
        }
        print_cli_data_field("VM type ".nicecase($vm_type));
        include($config['install_dir'] . "/includes/discovery/virtual-machines/$vm_type.inc.php");
        print_cli(PHP_EOL);
    }
}

// Include all discovery modules by MIB
$include_dir = "includes/discovery/virtual-machines";
include($config['install_dir'] . "/includes/include-dir-mib.inc.php");

echo(PHP_EOL);

// EOF
