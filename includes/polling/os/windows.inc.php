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

// Hardware: x86 Family 6 Model 1 Stepping 9 AT/AT COMPATIBLE  - Software: Windows NT Version 4.0  (Build Number: 1381 Multiprocessor Free )
// Hardware: x86 Family 6 Model 3 Stepping 4 AT/AT COMPATIBLE  - Software: Windows NT Version 3.51  (Build Number: 1057 Multiprocessor Free )
// Hardware: x86 Family 16 Model 4 Stepping 2 AT/AT COMPATIBLE - Software: Windows 2000 Version 5.1 (Build 2600 Multiprocessor Free)
// Hardware: x86 Family 15 Model 2 Stepping 5 AT/AT COMPATIBLE - Software: Windows 2000 Version 5.0 (Build 2195 Multiprocessor Free)
// Hardware: AMD64 Family 16 Model 2 Stepping 3 AT/AT COMPATIBLE - Software: Windows Version 6.0 (Build 6002 Multiprocessor Free)
// Hardware: EM64T Family 6 Model 26 Stepping 5 AT/AT COMPATIBLE - Software: Windows Version 5.2 (Build 3790 Multiprocessor Free)
// Hardware: Intel64 Family 6 Model 23 Stepping 6 AT/AT COMPATIBLE - Software: Windows Version 6.1 (Build 7600 Multiprocessor Free)
// Hardware: AMD64 Family 16 Model 8 Stepping 0 AT/AT COMPATIBLE - Software: Windows Version 6.1 (Build 7600 Multiprocessor Free)
// Hardware: Intel64 Family 6 Model 44 Stepping 2 AT/AT COMPATIBLE - Software: Windows Version 6.2 (Build 9200 Multiprocessor Free)
// Hardware: Intel64 Family 6 Model 44 Stepping 2 AT/AT COMPATIBLE - Software: Windows Version 6.3 (Build 9600 Multiprocessor Free)
// Hardware: ARMv8 (64-bit) Family 8 Model 0 Revision   0 ARM processor family - Software: Windows Version 6.3 (Build 22000 Multiprocessor Free)
// Hardware: Intel64 Family 6 Model 63 Stepping 2 AT/AT COMPATIBLE - Software: Windows Version 6.3 (Build 20348 Multiprocessor Free)
// Hardware: Intel64 Family 6 Model 85 Stepping 4 AT/AT COMPATIBLE - Software: Windows Version 6.3 (Build 26100 Multiprocessor Free)
// Microsoft Windows CE Version 5.0 (Build 1400)
// Microsoft Windows CE Version 6.0 (Build 0)

if (!$hardware) {
    if (str_contains($poll_device['sysDescr'], 'x86')) {
        $hardware = 'Generic (32-bit)';
    } elseif (str_contains($poll_device['sysDescr'], 'ia64')) {
        $hardware = 'Intel Itanium IA64';
    } elseif (str_contains($poll_device['sysDescr'], 'EM64')) {
        $hardware = 'Intel (64-bit)';
    } elseif (str_contains($poll_device['sysDescr'], 'AMD64')) {
        $hardware = 'AMD (64-bit)';
    } elseif (str_contains($poll_device['sysDescr'], 'Intel64')) {
        $hardware = 'Intel (64-bit)';
    } elseif (str_contains($poll_device['sysDescr'], 'ARMv')) {
        $hardware = 'ARM (64-bit)';
    }
}

$windows = [];
if (preg_match('/Version ([\d\.]+) +\(Build (?:Number: )?(\d+)/', $poll_device['sysDescr'], $matches)) {
    $windows['version'] = $matches[1];
    $windows['build']   = $matches[2];
}

// https://en.wikipedia.org/wiki/List_of_Microsoft_Windows_versions
if ($poll_device['sysObjectID'] === '.1.3.6.1.4.1.311.1.1.3.1.1') { // Workstation

    // References Table: https://www.lifewire.com/windows-version-numbers-2625171
    switch ($windows['version']) {
        case '3.1':
        case '3.5':
        case '3.51':
        case '4.0':
            $icon    = 'windows_old';
            $version = 'NT ' . $windows['version'] . ' Workstation';
            break;
        case '5.0':
            $icon    = 'windows2000';
            $version = '2000 (NT 5.0)';
            break;
        case '5.1':
            $icon    = 'windows2003';
            $version = 'XP (NT 5.1)';
            break;
        case '5.2':
            $icon    = 'windows2003';
            $version = 'XP x64 (NT 5.2)';
            break;
        case '6.0':
            if ($windows['build'] == '6001') {
                $windows['sp'] = 'SP1 ';
            } elseif ($windows['build'] == '6002') {
                $windows['sp'] = 'SP2 ';
            } elseif ($windows['build'] > '6002') {
                $windows['sp'] = 'SP3 ';
            }
            $icon    = 'windows_vista';
            $version = 'Vista ' . $windows['sp'] . '(NT 6.0)';
            break;
        case '6.1':
            if ($windows['build'] == '7601') {
                $windows['sp'] = 'SP1 ';
            } elseif ($windows['build'] > '7601') {
                $windows['sp'] = 'SP2 ';
            }
            $icon    = 'windows7';
            $version = '7 ' . $windows['sp'] . '(NT 6.1)';
            break;
        case '6.2':
            $icon    = 'windows8';
            $version = '8 (NT 6.2)';
            break;
        case '6.3':
            if ($windows['build'] <= 9600) {
                $icon = 'windows8';
                if ($windows['build'] > 9200) {
                    $windows['sp'] = ', Update 1';
                }
                $version = '8.1' . $windows['sp'] . ' (NT 6.3)';
            } elseif ($windows['build'] <= 21326) {
                $version = '10 (NT 10.0)';
                $icon    = 'windows10';

                /*
                   10.0.10240	Windows 10 Version 1507
                   10.0.10586	Windows 10 (1511)
                   10.0.14393	Windows 10 (1607)
                   10.0.15063	Windows 10 (1703)
                   10.0.16299	Windows 10 (1709)
                   10.0.17134	Windows 10 (1803)
                   10.0.17763   Windows 10 (1809)
                   10.0.18362   Windows 10 (1903)
                   10.0.18363   Windows 10 (1909)
                   10.0.19041   Windows 10 (2004)
                   10.0.19042   Windows 10 (20H2)
                   10.0.19043   Windows 10 (21H1)
                   10.0.19044   Windows 10 (21H2)
                */
            } else {
                // https://betawiki.net/wiki/Windows_11_(original_release)
                $version = '11 (NT 10.0)';
                $icon    = 'windows11';

                /*
                   10.0.21996.1	Windows 11 version Dev
                   10.0.22000   Windows 11 (21H2)
                   10.0.22621   Windows 11 (22H2)
                   10.0.22631   Windows 11 (23H2)
                   10.0.26100   Windows 11 (24H2)
                */

            }
            break;
        default:
            $icon    = $windows['version'] >= 6.3 ? 'windows11' : 'windows_old';
            $version = 'NT ' . $windows['version'] . ' Workstation';
    }
    $windows['type'] = 'workstation';
} elseif ($poll_device['sysObjectID'] === '.1.3.6.1.4.1.311.1.1.3.1.2' || // Server
          $poll_device['sysObjectID'] === '.1.3.6.1.4.1.311.1.1.3.1.3') { // Datacenter Server

    $windows['subtype'] = ($poll_device['sysObjectID'] === '.1.3.6.1.4.1.311.1.1.3.1.3') ? 'Datacenter ' : '';
    switch ($windows['version']) {
        case '3.1':
        case '3.5':
        case '3.51':
        case '4.0':
            $icon    = 'windows_old';
            $version = 'NT ' . $windows['subtype'] . 'Server ' . $windows['version'];
            break;
        case '5.0':
            $icon    = 'windows2000';
            $version = '2000 ' . $windows['subtype'] . 'Server (NT 5.0)';
            break;
        case '5.2':
            $icon    = 'windows2003';
            $version = 'Server 2003 ' . $windows['subtype'] . '(NT 5.2)';
            break;
        case '6.0':
            if ($windows['build'] == '6001') {
                $windows['sp'] = '';
            } elseif ($windows['build'] == '6002') {
                $windows['sp'] = 'SP2 ';
            } elseif ($windows['build'] > '6002') {
                $windows['sp'] = 'SP3 ';
            }
            $icon    = 'windows_vista';
            $version = 'Server 2008 ' . $windows['subtype'] . $windows['sp'] . '(NT 6.0)';
            break;
        case '6.1':
            if ($windows['build'] == '7601') {
                $windows['sp'] = 'SP1 ';
            } elseif ($windows['build'] > '7601') {
                $windows['sp'] = 'SP2 ';
            }
            $icon    = 'windows7';
            $version = 'Server 2008 ' . $windows['subtype'] . 'R2 ' . $windows['sp'] . '(NT 6.1)';
            break;
        case '6.2':
            $icon    = 'windows8';
            $version = 'Server 2012 ' . $windows['subtype'] . '(NT 6.2)';
            break;
        case '6.3':
            if ($windows['build'] <= 9600) {
                // https://betawiki.net/wiki/Windows_Server_2012_R2
                // 9309-9600
                $icon = 'windows8';
                if ($windows['build'] > '9200') {
                    $windows['sp'] = ', Update 1';
                }
                $version = 'Server 2012 ' . $windows['subtype'] . 'R2' . $windows['sp'] . ' (NT 6.3)';
            } elseif ($windows['build'] < 17040) {
                // https://betawiki.net/wiki/Windows_Server_2016
                // 9726-15063
                $version = 'Server 2016 ' . $windows['subtype'] . '(NT 10.0)';
                $icon    = 'windows10';
            } elseif ($windows['build'] < 19504) {
                // https://betawiki.net/wiki/Windows_Server_2019
                // 17040-17763
                $version = 'Server 2019 ' . $windows['subtype'] . '(NT 10.0)';
                $icon    = 'windows10';
            } elseif ($windows['build'] < 25871) {
                // https://betawiki.net/wiki/Windows_Server_2022
                // 19504-20348
                $version = 'Server 2022 ' . $windows['subtype'] . '(NT 10.0)';
                $icon    = 'windows11';
            } else {
                // https://betawiki.net/wiki/Windows_Server_2025
                // 25871-26100
                $version = 'Server 2025 ' . $windows['subtype'] . '(NT 10.0)';
                $icon    = 'windows2025';
            }
            break;
        default:
            $icon    = $windows['version'] >= 6.3 ? 'windows11' : 'windows_old';
            $version = 'NT ' . $windows['subtype'] . 'Server ' . $windows['version'];
    }
    $windows['type'] = 'server';
} elseif ($poll_device['sysObjectID'] === '.1.3.6.1.4.1.311.1.1.3.3') { // Windows CE
    $icon            = 'windows7';
    $version         = 'CE ' . $windows['version'];
    $windows['type'] = 'workstation';
}

if (isset($windows['type'])) {
    $type = $windows['type'];
}

if (str_contains($poll_device['sysDescr'], 'Uniprocessor')) {
    $features = 'Uniprocessor';
} elseif (str_contains($poll_device['sysDescr'], 'Multiprocessor')) {
    $features = 'Multiprocessor';
}

// Detect processor type? : I.E.  x86 Family 15 Model 2 Stepping 7

if (empty($version) && !safe_empty($wmi['os'])) {
    // FIXME. Currently not sure when required
    include('wmi.inc.php');
}

unset($windows);

// EOF
