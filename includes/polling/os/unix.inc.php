<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

switch ($device['os']) {
    case 'aix':
        [ $hardware, , $os_detail, ] = explode("\n", $poll_device['sysDescr']);
        if (preg_match('/: 0*(\d+\.)0*(\d+\.)0*(\d+\.)(\d+)/', $os_detail, $matches)) {
            // Base Operating System Runtime AIX version: 05.03.0012.0001
            $version = $matches[1] . $matches[2] . $matches[3] . $matches[4];
        }

        if ($hw = snmp_get_oid($device, 'aixSeMachineType.0', 'IBM-AIX-MIB')) {
            $hw = explode(',', $hw)[1];
            $hardware .= " ($hw)";

            $serial = explode(',', snmp_get_oid($device, 'aixSeSerialNumber.0', 'IBM-AIX-MIB'))[1];
        }
        break;

    case 'freebsd':
        if (preg_match('/FreeBSD ([\d\.]+\-[\w\-]+)/i', $poll_device['sysDescr'], $matches)) {
            $kernel = $matches[1];
        }
        //$hardware = rewrite_unix_hardware($poll_device['sysDescr']);
        break;

    case 'dragonfly':
        [, , $version, , , $features] = explode(' ', $poll_device['sysDescr']);
        //$hardware = rewrite_unix_hardware($poll_device['sysDescr']);
        break;

    case 'netbsd':
        [, , $version, , , $features] = explode(' ', $poll_device['sysDescr']);
        $features = str_replace(['(', ')'], '', $features);
        //$hardware = rewrite_unix_hardware($poll_device['sysDescr']);
        break;

    case 'openbsd':
    case 'solaris':
    case 'opensolaris':
        [, , $version, $features] = explode(' ', $poll_device['sysDescr']);
        $features = str_replace(['(', ')'], '', $features);
        //$hardware = rewrite_unix_hardware($poll_device['sysDescr']);
        break;

    case 'monowall':
    case 'pfsense':
        if (safe_empty($kernel)) {
            [, , $version, , , $kernel] = explode(' ', $poll_device['sysDescr']);
        }
        $distro   = $device['os'];
        //$hardware = rewrite_unix_hardware($poll_device['sysDescr']);
        break;

    case 'truenas':
        if (preg_match('/^(?<version>\d\S+)\. Hardware:/', $poll_device['sysDescr'], $matches)) {
            // 21.08-BETA.1. Hardware: x86_64 AMD Ryzen 5 1600 Six-Core Processor. Software: Linux 5.10.42+truenas (revision #1 SMP Mon Aug 30 21:54:59 UTC 2021)
            $version = $matches['version'];
        }
        //$hardware = rewrite_unix_hardware($poll_device['sysDescr']);
        break;

    case 'freenas':
    case 'nas4free':
    case 'xigmanas':
        if (preg_match('/^(TrueNAS|FreeNAS)\-(?<version>\d\S+)/', $poll_device['sysDescr'], $matches)) {
            // FreeNAS-11.3-U2.1 (6a6a2dfda7). Hardware: amd64 Intel(R) Atom(TM) CPU C2750 @ 2.40GHz running at 2400. Software: FreeBSD 11.3-RELEASE-p7 (revision 199506)
            // TrueNAS-11.3-U2.1 (6a6a2dfda7). Hardware: amd64 Intel(R) Xeon(R) CPU X5560 @ 2.80GHz running at 2800. Software: FreeBSD 11.3-RELEASE-p7 (revision 199506)
            $version = $matches['version'];
        } elseif (preg_match('/Software: (FreeBSD|Linux) ([\d\.]+[\+\-][\w\-]+)/i', $poll_device['sysDescr'], $matches)) {
            // Hardware: amd64 Intel(R) Xeon(R) CPU E5520 @ 2.27GHz running at 2266 Software: FreeBSD 11.1-STABLE (revision 199506)
            $version = $matches[1];
        }
        //$hardware = rewrite_unix_hardware($poll_device['sysDescr']);
        break;

    case 'opnsense':
        // Detect detailed version, see:
        // https://jira.observium.org/browse/OBS-3745
        // https://github.com/opnsense/plugins/pull/1684
        if (is_device_mib($device, 'NET-SNMP-EXTEND-MIB') &&
            $os_data = snmp_get_oid($device, '.1.3.6.1.4.1.8072.1.3.2.3.1.2.7.118.101.114.115.105.111.110', 'NET-SNMP-EXTEND-MIB')) {
            // NET-SNMP-EXTEND-MIB::nsExtendOutputFull."version" = STRING: OPNsense 20.7.8_4 (amd64/OpenSSL)
            $version = explode(' ', $os_data)[1];
        }
        // if (!$hardware) {
        //     $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
        // }
        break;

    case 'ipso':
        // IPSO Bastion-1 6.2-GA039 releng 1 04.14.2010-225515 i386
        // IP530 rev 00, IPSO ruby.infinity-insurance.com 3.9-BUILD035 releng 1515 05.24.2005-013334 i386
        if (preg_match('/IPSO [^ ]+ ([^ ]+) /', $poll_device['sysDescr'], $matches)) {
            $version = $matches[1];
        }

        $data = snmp_get_multi_oid($device, 'ipsoChassisMBType.0 ipsoChassisMBRevNumber.0', [], 'NOKIA-IPSO-SYSTEM-MIB');
        if (isset($data[0])) {
            $hw = $data[0]['ipsoChassisMBType'] . ' rev ' . $data[0]['ipsoChassisMBRevNumber'];
            $hardware = rewrite_unix_hardware($poll_device['sysDescr'], $hw);
        }
        break;

    case 'sofaware':
        // EMBEDDED-NGX-MIB::swHardwareVersion.0 = "1.3T ADSL-A"
        // EMBEDDED-NGX-MIB::swHardwareType.0 = "SBox-200-B"
        // EMBEDDED-NGX-MIB::swLicenseProductName.0 = "Safe@Office 500, 25 nodes"
        // EMBEDDED-NGX-MIB::swFirmwareRunning.0 = "8.2.26x"
        $data = snmp_get_multi_oid($device, 'swHardwareVersion.0 swHardwareType.0 swLicenseProductName.0 swFirmwareRunning.0', [], 'EMBEDDED-NGX-MIB');
        if (isset($data[0])) {
            $hw = explode(',', $data[0]['swLicenseProductName'])[0];
            $hardware = $hw . ' ' . $data[0]['swHardwareType'] . ' ' . $data[0]['swHardwareVersion'];
            $version  = $data[0]['swFirmwareRunning'];
        }
        break;

    case 'osix':
        //Linux me-sg199-ch-zur-acc-1 4.1.39_1-osix- #1 SMP Mon May 7 17:35:37 MEST 2018 x86_64
        //
        //me: the customer company code letters
        //sg:  device type code letters
        //
        //        sg          = "Security Gateway"           exp: me-sg001-ch-gla-1
        //        sg * dns    = "DNS Server"                 exp: me-sg005-ch-gla-dns-1
        //        sg * mep    = "Mobile Entry Point"         exp: me-sg007-ch-gla-mep-1
        //
        //        bgp         = "BGP Router"                 exp: me-bgp001-ch-gla-1
        //        aut         = "Authentication Passport"    exp: me-aut001-ch-gla-1
        //        cvp         = "Client-VPN"                 exp: me-cvp001-ch-gla-1
        //        fw          = "Firewall"                   exp: me-fw001-ch-gla-1
        //        mx          = "Email Gateway"              exp: me-mx001-ch-gla-1
        //        prx         = "Internet Proxy"             exp: me-prx001-ch-gla-1
        //        rpx         = "R-proxy (WAF)"              exp: me-rpx002-ch-gla-1
        //        sw          = "Managed Switch"             exp: me-sw012-ch-gla-1
        //
        //199:  device number. It's unique regarding device type
        // ch:    2 letter ISO code for country location
        // zur:   3 first letter from the location / city name
        // acc:   optional or sub-segment, like dns and mep
        // 1:        Device number in the same clusters for High availability
        if (preg_match('/^Linux\ +\w+\-(?<hw>(?<hw1>[a-z]+)0*\d+)\-\w+\-\w+\-(?:(?<hw2>\w+)\-)?\d+/', $poll_device['sysDescr'], $matches)) {
            switch ($matches['hw1']) {
                case 'sg':
                    if (isset($matches['hw2']) && $matches['hw2'] === 'dns') {
                        $hw   = 'DNS Server';
                        $type = 'server';
                    } elseif (isset($matches['hw2']) && $matches['hw2'] === 'mep') {
                        $hw   = 'Mobile Entry Point';
                        $type = 'network';
                    } else {
                        $hw   = 'Security Gateway';
                        $type = 'security';
                    }
                    break;

                case 'bgp':
                    $hw   = 'BGP Router';
                    $type = 'network';
                    break;

                case 'aut':
                    $hw   = 'Authentication Passport';
                    $type = 'security';
                    break;

                case 'cvp':
                    $hw   = 'Client-VPN';
                    $type = 'security';
                    break;

                case 'fw':
                    $hw   = 'Firewall';
                    $type = 'firewall';
                    break;

                case 'mx':
                    $hw   = 'Email Gateway';
                    $type = 'server';
                    break;

                case 'prx':
                    $hw   = 'Internet Proxy';
                    $type = 'security';
                    break;

                case 'rpx':
                    $hw   = 'R-proxy (WAF)';
                    $type = 'security';
                    break;

                case 'sw':
                    $hw   = 'Managed Switch';
                    $type = 'network';
                    break;

                case 'ids':
                    $hw   = 'Network Security Monitoring';
                    $type = 'security';
                    break;

                case 'apm':
                    $hw   = 'Application Performance Management';
                    $type = 'server';
                    break;

                case 'par':
                    $hw   = 'Partner Security Gateway';
                    $type = 'security';
                    break;

                case 'dns':
                    $hw   = 'DNS Server';
                    $type = 'server';
                    break;

                case 'mep':
                    $hw   = 'Mobile Entry Point';
                    $type = 'network';
                    break;
            }
        }

        if (empty($hw)) {
            $hw = 'OSAG Hardware';
        }
        //$hardware = $hw . ' (' . strtoupper($matches['hw']) . ')';
        $hardware = rewrite_unix_hardware($poll_device['sysDescr'], $hw);
        break;

    case 'unitrends-backup':
        // Prevent Distro/kernel/packages polling
        // But keep other unix group features
        return;

    // case 'linux':
    // case 'endian':
    // case 'ddwrt':
    default:
        // Kernel/Version
        if (str_starts_with($poll_device['sysDescr'], 'Linux')) {
            $kernel = explode(' ', $poll_device['sysDescr'])[2];
            if (!$version) {
                // do not override MIB defined versions
                $version = $kernel;
            }
        }

        // detect os version by installed packages (ie proxmox & ucs)
        if (isset($config['os'][$device['os']]['packages'])) {
            include($config['install_dir'] . "/includes/polling/os/packages.inc.php");
        }

        break;
}

// Use agent DMI data if available
if (isset($agent_data['dmi'])) {
    if ($agent_data['dmi']['system-product-name']) {
        $hw = $agent_data['dmi']['system-product-name'];
    }

    if (!$vendor && $agent_data['dmi']['system-manufacturer']) {
        // Cleanup Vendor name
        $vendor = rewrite_vendor($agent_data['dmi']['system-manufacturer']);
    }

    // If these exclude lists grow any further we should move them to definitions...
    if (!$serial && isset($agent_data['dmi']['system-serial-number']) &&
        is_valid_param($agent_data['dmi']['system-serial-number'], 'serial')) {

        $serial = $agent_data['dmi']['system-serial-number'];
    }

    if (!$asset_tag) {
        if (isset($agent_data['dmi']['chassis-asset-tag']) &&
            is_valid_param($agent_data['dmi']['chassis-asset-tag'], 'serial')) {

            $asset_tag = $agent_data['dmi']['chassis-asset-tag'];
        } elseif (isset($agent_data['dmi']['baseboard-asset-tag']) &&
            is_valid_param($agent_data['dmi']['baseboard-asset-tag'], 'serial')) {

            $asset_tag = $agent_data['dmi']['baseboard-asset-tag'];
        }
    }
}

if (!$hardware) {
    $hardware = rewrite_unix_hardware($poll_device['sysDescr'], $hw);
}

// Has 'distro' script data already been returned via the agent?
if (isset($agent_data['distro']['SCRIPTVER'])) {
    $distro = $agent_data['distro']['DISTRO'];
    // Older version of the script used DISTROVER, newer ones use VERSION :-(
    $distro_ver = $agent_data['distro']['DISTROVER'] ?? $agent_data['distro']['VERSION'];
    $kernel     = $agent_data['distro']['KERNEL'];
    $arch       = $agent_data['distro']['ARCH'];
    $virt       = $agent_data['distro']['VIRT'];
    $cont       = $agent_data['distro']['CONT'];
    if (empty($virt) && strlen($cont)) {
        $virt = $cont;
    }
} else {

    // Distro "extend" support
    //if (is_device_mib($device, 'NET-SNMP-EXTEND-MIB'))
    //{
    //  //NET-SNMP-EXTEND-MIB::nsExtendOutput1Line."distro" = STRING: Linux|4.4.0-77-generic|amd64|Ubuntu|16.04|kvm
    //  $os_data = snmp_get_oid($device, '.1.3.6.1.4.1.8072.1.3.2.3.1.1.6.100.105.115.116.114.111', 'NET-SNMP-EXTEND-MIB');
    //}
    if (!$os_data && is_device_mib($device, 'UCD-SNMP-MIB')) {
        $os_data = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.1.3.1.1.6.100.105.115.116.114.111', 'UCD-SNMP-MIB');

        if (!$os_data) {
            // No "extend" support, try "exec" support
            $os_data = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.1.101.1', 'UCD-SNMP-MIB');
        }
    }

    // Disregard data if we're just getting an error.
    if (!$os_data || str_contains($os_data, '/bin/distro')) {
        unset($os_data);
    } elseif (str_contains($os_data, '|')) {
        // distro version less than 1.2: "Linux|3.2.0-4-amd64|amd64|Debian|7.5"
        // distro version 1.2 and above: "Linux|4.4.0-53-generic|amd64|Ubuntu|16.04|kvm"
        // distro version 2.0 and above: "Linux|4.4.0-116-generic|amd64|Ubuntu|16.04|kvm|"
        //                               "Linux|4.4.0|amd64|Ubuntu|16.04||openvz"
        [ $osname, $kernel, $arch, $distro, $distro_ver, $virt, $cont ] = explode('|', $os_data);
        if (empty($virt) && strlen($cont)) {
            $virt = $cont;
        }
    } else {
        // Very old distro, not supported now: "Ubuntu 12.04"
        [ $distro, $distro_ver ] = explode(' ', $os_data);
    }
}

// Detect some distro by kernel strings
if (!isset($distro)) {
    if ($poll_device['sysObjectID'] === '.1.3.6.1.4.1.8072.3.2.10' && str_starts_with($poll_device['sysDescr'], 'Linux ')) {
        if (preg_match('/ \d[\.\d]+(\-\d+)?(\-[a-z]+)? #(\d+\-Ubuntu|\d{12}) /', $poll_device['sysDescr'])) {
            // * Ubuntu (old):
            // Linux hostname 3.11.0-13-generic #20-Ubuntu SMP Wed Oct 23 07:38:26 UTC 2013 x86_64
            // * Ubuntu 16.04:
            // Linux hostname 4.4.0-77-generic #98-Ubuntu SMP Wed Apr 26 08:34:02 UTC 2017 x86_64
            // Linux hostname 4.4.0-201-generic #233-Ubuntu SMP Thu Jan 14 06:10:28 UTC 2021 x86_64
            // * Ubuntu 18.04:
            // Linux hostname 4.15.0-96-generic #97-Ubuntu SMP Wed Apr 1 03:25:46 UTC 2020 x86_64
            // Linux hostname 4.15.0-129-generic #132-Ubuntu SMP Thu Dec 10 14:02:26 UTC 2020 x86_64
            // * Ubuntu 20.04
            // Linux hostname 5.10.4-051004-generic #202012301142 SMP Wed Dec 30 11:44:55 UTC 2020 x86_64
            // Linux hostname 5.4.0-42-generic #46-Ubuntu SMP Fri Jul 10 00:24:02 UTC 2020 x86_64

            $distro = 'Ubuntu';
        } elseif (preg_match('/ Debian \d[\.\d]+(\-\d+)?([\+\-]\w+)? /', $poll_device['sysDescr'])) {
            // * Debian 9
            // Linux hostname 4.9.0-6-amd64 #1 SMP Debian 4.9.88-1+deb9u1 (2018-05-07) x86_64
            // Linux hostname 4.9.0-14-amd64 #1 SMP Debian 4.9.240-2 (2020-10-30) x86_64

            $distro = 'Debian';
        } elseif (preg_match('/\d\-pve | PVE /', $poll_device['sysDescr'])) {
            // * Proxmox (Debian)
            // Linux hostname 5.11.22-2-pve #1 SMP PVE 5.11.22-4 (Tue, 20 Jul 2021 21:40:02 +0200) x86_64
            // Linux hostname 5.4.78-2-pve #1 SMP PVE 5.4.78-2 (Thu, 03 Dec 2020 14:26:17 +0100) x86_64
            // Linux hostname 5.4.44-1-pve #1 SMP PVE 5.4.44-1 (Fri, 12 Jun 2020 08:18:46 +0200) x86_64
            // Linux hostname 5.0.21-5-pve #1 SMP PVE 5.0.21-10 (Wed, 13 Nov 2019 08:27:10 +0100) x86_64
            // Linux hostname 4.4.128-1-pve #1 SMP PVE 4.4.128-111 (Wed, 23 May 2018 14:00:02 +0000) x86_64
            // Linux hostname 4.4.8-1-pve #1 SMP Tue May 31 07:12:32 CEST 2016 x86_64

            $distro = 'Debian';
        } elseif (preg_match('/ \d[\.\d]+(\-v\d+\w*)?\+ #\d+ .* arm/', $poll_device['sysDescr'])) {
            // * Raspbian (Debian)
            // Linux hostname 5.10.17-v7+ #1403 SMP Mon Feb 22 11:29:51 GMT 2021 armv7l
            // Linux hostname 5.4.51-v7l+ #1333 SMP Mon Aug 10 16:51:40 BST 2020 armv7l
            // Linux hostname 4.19.97-v7l+ #1294 SMP Thu Jan 30 13:21:14 GMT 2020 armv7l
            // Linux hostname 4.19.66-v7+ #1253 SMP Thu Aug 15 11:49:46 BST 2019 armv7l
            // Linux hostname 4.14.43+ #1115 Fri May 25 13:54:20 BST 2018 armv6l
            // Linux hostname 4.9.35-v7+ #1014 SMP Fri Jun 30 14:47:43 BST 2017 armv7l
            // Linux hostname 3.12.33+ #724 PREEMPT Wed Nov 26 17:55:23 GMT 2014 armv6l

            $distro = 'Raspbian';
        } elseif (preg_match('/^Linux \S+ \d\S+\d+(\-\w+)?\-sunxi #(?<distro_ver>\d+\.\d+(\.\d+)?) .* arm/', $poll_device['sysDescr'], $matches)) {
            // * Armbian (Ubuntu)
            // Linux hostname 5.10.60-sunxi #21.08.2 SMP Tue Sep 14 16:28:44 UTC 2021 armv7l

            $distro     = 'Armbian';
            $distro_ver = $matches['distro_ver'];
        } elseif (preg_match('/^Linux \S+ \d\S+\d+(\-\w+)? #\d+\-Alpine /', $poll_device['sysDescr'])) {
            // * Alpine
            // Linux hostname 5.15.86-0-virt #1-Alpine SMP Mon, 02 Jan 2023 09:28:30 +0000 x86_64 Linux

            $distro = 'Alpine';
        } elseif (preg_match('/^Linux \S+ \d\S+\d+(\-\w+)?\-ARCH #\d/', $poll_device['sysDescr'])) {
            // * Arch Linux
            // Linux hostname 2.6.37-ARCH #1 SMP PREEMPT Sat Jan 29 20:00:33 CET 2011 x86_64
            // Linux hostname 4.19.86-1-ARCH #1 SMP PREEMPT Sat Nov 30 18:56:30 UTC 2019 armv6l
            // Linux hostname 5.10.27-2-ARCH #1 SMP Fri Apr 9 21:08:37 UTC 2021 armv6l
            // Linux hostname 5.10.79-2-raspberrypi-ARCH #1 SMP Tue Nov 16 20:32:00 UTC 2021 armv6l GNU/Linux

            $distro = 'Arch Linux';

        } elseif (preg_match('/^Linux \S+ \d\S+\d+(\-\w+)?\.amzn\d+(\.\w+)? #\d/', $poll_device['sysDescr'])) {
            // * Amazon Linux
            // Linux hostname 6.1.15-28.43.amzn2023.aarch64 #1 SMP Thu Mar  9 17:17:24 UTC 2023 aarch64 aarch64 aarch64 GNU/Linux

            $distro = 'Amazon Linux';

        } elseif (preg_match('/ \d[\.\d]+(\-\d+[\.\d]*\.el(?<distro_ver>\d+(_\d+)?))/', $poll_device['sysDescr'], $matches)) {
            // * CentOS 5:
            // Linux hostname 2.6.18-274.12.1.el5 #1 SMP Tue Nov 29 13:37:46 EST 2011 x86_64
            // * OracleLinux 6:
            // Linux hostname 2.6.32-131.0.15.el6.x86_64 #1 SMP Fri May 20 15:04:03 EDT 2011 x86_64
            // * CentOS 7:
            // Linux hostname 3.10.0-327.4.5.el7.x86_64 #1 SMP Mon Jan 25 22:07:14 UTC 2016 x86_64
            // * RedHat EL:
            // Linux hostname 3.10.0-327.el7.x86_64 #1 SMP Thu Oct 29 17:29:29 EDT 2015 x86_64
            // Linux hostname 3.10.0-229.20.1.el7.x86_64 #1 SMP Thu Sep 24 12:23:56 EDT 2015 x86_64
            // Linux hostname 4.18.0-240.22.1.el8_3.x86_64 #1 SMP Thu Mar 25 14:36:04 EDT 2021 x86_64

            // Detect distro by packages
            $distro_def = [
                // redhat-release-server-6Server-6.7.0.3.el6
                // redhat-release-8.3-1.0.el8
                ['name'      => 'redhat-release', 'regex' => '/^redhat\-release[\-_](?<version>\d.*)/', 'distro' => 'RedHat',
                 'transform' => [['action' => 'preg_replace', 'from' => '/^(\d+[\.\-]\d+).*/', 'to' => '$1'],
                                 ['action' => 'replace', 'from' => '-', 'to' => '.']]],
                // centos-release-6-3.el6.centos.9
                // centos-release-6-9.el6.12.3
                // centos-release-7-6.1810.2.el7.centos
                ['name'      => 'centos-release', 'regex' => '/^centos\-release[\-_](?<version>\d.*)/', 'distro' => 'CentOS',
                 'transform' => [['action' => 'preg_replace', 'from' => '/^(\d+[\.\-]\d+).*/', 'to' => '$1'],
                                 ['action' => 'replace', 'from' => '-', 'to' => '.']]],
                // rocky-release-8.5-3.el8
                ['name'      => 'rocky-release', 'regex' => '/^rocky\-release[\-_](?<version>\d.*)/', 'distro' => 'Rocky',
                 'transform' => [['action' => 'preg_replace', 'from' => '/^(\d+[\.\-]\d+).*/', 'to' => '$1'],
                                 ['action' => 'replace', 'from' => '-', 'to' => '.']]],
                // oraclelinux-release-6Server-1.0.2
                // fixme. need more examples
            ];
            $metatypes  = ['distro', 'distro_ver'];
            foreach (poll_device_unix_packages($device, $metatypes, $distro_def) as $metatype => $value) {
                $$metatype = $value;
            }
            if (safe_empty($distro)) {
                // there no way for split RedHat vs CentOS..
                //$distro = 'CentOS';
                $distro     = 'RedHat'; // FIXME. no way for correctly detect redhat or centos, probably by packages?..
                $distro_ver = str_replace('_', '.', $matches['distro_ver']);
            }
        } elseif (preg_match('/^Linux \S+ \d\S+\d+(\-\w+)? #\d+\-photon /', $poll_device['sysDescr'], $matches)) {
            // * Photon OS
            $distro = 'Photon';
            // Detect distro_ver by packages
            $distro_def = [
                // photon-release-4.0-2.ph4
                ['name'      => 'photon-release', 'regex' => '/^photon\-release[\-_](?<version>\d.*)/', 'distro' => 'Photon',
                 'transform' => [['action' => 'preg_replace', 'from' => '/^(\d+[\.\-]\d+).*/', 'to' => '$1'],
                                 ['action' => 'replace', 'from' => '-', 'to' => '.']]],
            ];
            $metatypes  = ['distro_ver'];
            foreach (poll_device_unix_packages($device, $metatypes, $distro_def) as $metatype => $value) {
                $$metatype = $value;
            }
        } elseif (preg_match('/ \d[\.\d]+(\-\d+[\.\d]*\.fc(?<distro_ver>\d+(_\d+)?))/', $poll_device['sysDescr'], $matches)) {
            // * Fedora
            // Linux hostname 5.3.7-301.fc31.x86_64 #1 SMP Mon Oct 21 19:18:58 UTC 2019 x86_64 x86_64 x86_64 GNU/Linux
            $distro     = 'Fedora';
            $distro_ver = str_replace('_', '.', $matches['distro_ver']);
        }
        // * Slackware:
        // Linux hostname 2.6.21.5-smp #2 SMP Tue Jun 19 14:58:11 CDT 2007 i686
    } elseif ($poll_device['sysObjectID'] === '.1.3.6.1.4.1.8072.3.2.8' && str_starts($poll_device['sysDescr'], 'FreeBSD ')) {
        // * HardenedBSD
        if (str_contains($poll_device['sysDescr'], '-HBSD ')) {
            $distro = 'HardenedBSD';
        }
    }
    if (isset($distro)) {
        print_debug("Linux Distro was set by sysDescr string.");
    }
}

// Hardware/vendor "extend" support
if (is_device_mib($device, 'UCD-SNMP-MIB')) {
    $hw = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.2.3.1.1.8.104.97.114.100.119.97.114.101', 'UCD-SNMP-MIB');
    if (strlen($hw)) {
        $hardware = rewrite_unix_hardware($poll_device['sysDescr'], $hw);
        $vendor   = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.3.3.1.1.6.118.101.110.100.111.114', 'UCD-SNMP-MIB');
        if (!snmp_status()) {
            // Alternative with manufacturer
            $vendor = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.3.3.1.1.12.109.97.110.117.102.97.99.116.117.114.101.114', 'UCD-SNMP-MIB');
        }
        $serial = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.4.3.1.1.6.115.101.114.105.97.108', 'UCD-SNMP-MIB');
        //if (str_contains_array($serial, 'denied') || str_starts($serial, [ '0123456789', '..', 'Not Specified' ]))
        if (!is_valid_param($serial, 'serial')) {
            unset($serial);
        }
    }
}

// Use 'os' script virt output, if virt-what agent is not used
if (!isset($agent_data['virt']['what']) && isset($virt)) {
    $agent_data['virt']['what'] = $virt;
}

// Use agent virt-what data if available
if (isset($agent_data['virt']['what'])) {
    // We cycle through every line here, the previous one is overwritten.
    // This is OK, as virt-what prints general-to-specific order and we want most specific.
    foreach (explode("\n", $agent_data['virt']['what']) as $virtwhat) {
        if (isset($config['virt-what'][$virtwhat])) {
            //$hardware = $config['virt-what'][$virtwhat];
            $hardware = rewrite_unix_hardware($poll_device['sysDescr'], $config['virt-what'][$virtwhat]);
            $vendor   = ''; // Always reset vendor for VMs, while this doesn't make sense
        }
    }
}

if (!$features && isset($distro)) {
    $features = trim("$distro $distro_ver");
}

unset($hw, $data, $os_data);

// EOF
