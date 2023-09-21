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

// Try to discover Libvirt Virtual Machines.

if (!$config['enable_libvirt']) {
    print_debug("Libvirt disabled in \$config['enable_libvirt'].");

    return;
}

if (!is_executable($config['virsh'])) {
    print_error("Virsh executable not found. Please install 'libvirt-clients' package.
    apt install libvirt-clients");
    return;
}

$libvirt_vmlist = [];

echo("Libvirt VM: ");

$ssh_ok = 0;

foreach ($config['libvirt_protocols'] as $method) {
    $uri = $method . '://' . $device['hostname'];

    if (str_contains($method, 'ssh') && !$ssh_ok) {
        // Append ssh port to uri
        $uri .= ':' . $device['ssh_port'];
    }

    if (str_contains($method, 'qemu')) {
        $uri .= '/system';
    }

    if (isset($config['libvirt_socket']) && !safe_empty($config['libvirt_socket'])) {
        // Allow setting of socket, used to force use of read only socket
        // $config['libvirt_socket'] = "/var/run/libvirt/libvirt-sock-ro";

        $uri .= "?socket=" . $config['libvirt_socket'];
    }

    if (str_contains($method, 'ssh') && !$ssh_ok) {
        // Check if we are using SSH if we can log in without password - without blocking the discovery
        // Also automatically add the host key so discovery doesn't block on the yes/no question, and run echo so we don't get stuck in a remote shell ;-)
        external_exec('ssh -p ' . $device['ssh_port'] . ' -o "StrictHostKeyChecking no" -o "PreferredAuthentications publickey" -o "IdentitiesOnly yes" ' . $device['hostname'] . ' echo -e', $exec_status);
        if ($exec_status['exitcode'] != 255) {
            $ssh_ok = 1;
        }
    }

    if ($ssh_ok || !str_contains($method, 'ssh')) {
        // Fetch virtual machine list
        unset($domlist);
        $domlist = explode("\n", external_exec($config['virsh'] . ' -c ' . $uri . ' list --all'));

        foreach ($domlist as $dom) {
            $dom_split = str_word_count($dom, 1, '1234567890-.');

            $dom_id         = $dom_split[0];
            $vm_DisplayName = $dom_split[1];
            $vm_State       = implode(' ', array_slice($dom_split, 2)); // Convert split words back into one

            if (is_numeric($dom_id) || $dom_id == '-') // - when domain is not running
            {
                // Fetch the Virtual Machine information.
                unset($vm_info_array);
                $vm_info_xml = external_exec($config['virsh'] . ' -c ' . $uri . ' dumpxml ' . $vm_DisplayName);

                // <domain type='kvm' id='3'>
                //   <name>moo.example.com</name>
                //   <uuid>48cf6378-6fd5-4610-0611-63dd4b31cfd6</uuid>
                //   <memory>1048576</memory>
                //   <currentMemory>1048576</currentMemory>
                //   <vcpu>8</vcpu>
                //   <os>
                //     <type arch='x86_64' machine='pc-0.12'>hvm</type>
                //     <boot dev='hd'/>
                //   </os>
                //   <features>
                //     <acpi/>
                //   (...)

                // Parse XML, add xml header in front as this is required by the parser but not supplied by libvirt
                $xml = simplexml_load_string('<?xml version="1.0"?> ' . $vm_info_xml);
                print_debug_vars($xml);

                // Call VM discovery
                discover_virtual_machine($valid, $device, [ 'id'       => $xml->uuid,
                                                            'name'     => $vm_DisplayName,
                                                            'cpucount' => $xml->vcpu,
                                                            'memory'   => $xml->currentMemory * 1024,
                                                            'status'   => $vm_State,
                                                            'type'     => explode('+', $method)[0],
                                                            'source'   => 'libvirt',
                                                            'protocol' => 'libvirt' ]);

                // Save the discovered Virtual Machine.
                $libvirt_vmlist[] = $vm_DisplayName;
            }
        }
    }

    // If we found VMs, don't cycle the other protocols anymore.
    if (!safe_empty($libvirt_vmlist)) {
        break;
    }
}

unset($libvirt_vmlist);

// Clean up removed VMs (our type - libvirt - only, so we don't clean up other modules' VMs)
check_valid_virtual_machines($device, $valid, 'libvirt');
echo(PHP_EOL);

// EOF
