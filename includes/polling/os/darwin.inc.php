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

// Darwin hostname.local 9.8.0 Darwin Kernel Version 9.8.0: Wed Jul 15 16:55:01 PDT 2009; root:xnu-1228.15.4~1/RELEASE_I386 i386
// Darwin hostname.local 10.8.0 Darwin Kernel Version 10.8.0: Tue Jun 7 16:32:41 PDT 2011; root:xnu-1504.15.3~1/RELEASE_X86_64 x86_64
// Darwin hostname.local 14.4.0 Darwin Kernel Version 14.4.0: Thu May 28 11:35:04 PDT 2015; root:xnu-2782.30.5~1/RELEASE_X86_64 x86_64
// Darwin hostname.local 18.7.0 Darwin Kernel Version 18.7.0: Sat Oct 12 00:02:19 PDT 2019; root:xnu-4903.278.12~1/RELEASE_X86_64 x86_64
// Darwin hostname.local 19.4.0 Darwin Kernel Version 19.4.0: Wed Mar 4 22:28:40 PST 2020; root:xnu-6153.101.6~15/RELEASE_X86_64 x86_64
// Darwin hostname.local 19.5.0 Darwin Kernel Version 19.5.0: Tue May 26 20:41:44 PDT 2020; root:xnu-6153.121.2~2/RELEASE_X86_64 x86_64
// Darwin hostname.local 20.3.0 Darwin Kernel Version 20.3.0: Thu Jan 21 00:07:06 PST 2021; root:xnu-7195.81.3~1/RELEASE_X86_64 x86_64
if (preg_match('/Darwin Kernel Version (?<kernel>\d\S+): .* root\S+ (?<arch>\S+)/', $poll_device['sysDescr'], $matches)) {
    $kernel = $matches['kernel'];
    $arch   = $matches['arch'];

    $macos_kernels = [
        // Cats
        9  => ['version' => '10.5', 'name' => 'Leopard', 'icon' => ''],
        10 => ['version' => '10.6', 'name' => 'Snow Leopard', 'icon' => ''],
        11 => ['version' => '10.7', 'name' => 'Lion', 'icon' => 'macos-lion'],
        12 => ['version' => '10.8', 'name' => 'Mountain Lion', 'icon' => 'macos-mountain-lion'],
        // Mountains
        13 => ['version' => '10.9', 'name' => 'Mavericks', 'icon' => 'macos-mavericks'],
        14 => ['version' => '10.10', 'name' => 'Yosemite', 'icon' => 'macos-yosemite'],
        15 => ['version' => '10.11', 'name' => 'El Capitan', 'icon' => 'macos-el-capitan'],
        16 => ['version' => '10.12', 'name' => 'Sierra', 'icon' => 'macos-sierra'],
        17 => ['version' => '10.13', 'name' => 'High Sierra', 'icon' => 'macos-high-sierra'],
        18 => ['version' => '10.14', 'name' => 'Mojave', 'icon' => 'macos-mojave'],
        19 => ['version' => '10.15', 'name' => 'Catalina', 'icon' => 'macos-catalina'],
        20 => ['version' => '11.0', 'name' => 'Big Sur', 'icon' => 'macos-big-sur'],
    ];

    // 19.5.0 -> 10.15.5, 14.0.0 -> 10.10
    [$k1, $k2, $k3] = explode('.', $kernel);
    if (isset($macos_kernels[$k1])) {
        $version = $macos_kernels[$k1]['version'] . '.' . $k2;
        if ($k3 > 0) {
            $version .= '.' . $k3;
        }
        $features = $macos_kernels[$k1]['name'];
        if (strlen($macos_kernels[$k1]['icon'])) {
            $icon = $macos_kernels[$k1]['icon'];
        }
    } else {
        $version = $kernel;
    }
} else {
    [, , $version] = explode(' ', $poll_device['sysDescr']);
}
$hardware = rewrite_unix_hardware($poll_device['sysDescr']);

// Hardware/serial/type "extend" support
if (is_device_mib($device, 'UCD-SNMP-MIB')) {
    // Append this line to /etc/snmp/snmpd.conf:
    // extend .1.3.6.1.4.1.2021.7890.2 hardware /usr/sbin/system_profiler SPHardwareDataType SPSoftwareDataType
    // restart service:
    // sudo launchctl unload -w /System/Library/LaunchDaemons/org.net-snmp.snmpd.plist
    // sudo launchctl load -w /System/Library/LaunchDaemons/org.net-snmp.snmpd.plist
    // Check output:
    // snmpget -v2c -c <community> <hostname> .1.3.6.1.4.1.2021.7890.2.3.1.2.8.104.97.114.100.119.97.114.101
    /*
    Hardware:

        Hardware Overview:

          Model Name: MacBook Pro
          Model Identifier: MacBookPro16,1
          Processor Name: 8-Core Intel Core i9
          Processor Speed: 2.3 GHz
          Number of Processors: 1
          Total Number of Cores: 8
          L2 Cache (per Core): 256 KB
          L3 Cache: 16 MB
          Hyper-Threading Technology: Enabled
          Memory: 16 GB
          System Firmware Version: 1554.80.3.0.0 (iBridge: 18.16.14347.0.0,0)
          Serial Number (system): C02ZJ6XXXXX
          Hardware UUID: XXXX-9AE6-5112-A312-XXXXXX
          Provisioning UDID: XXXX-9AE6-5112-A312-XXXXXX
          Activation Lock Status: Enabled

    Software:

        System Software Overview:

          System Version: macOS 11.2.3 (20D91)
          Kernel Version: Darwin 20.3.0
          Boot Volume: Macintosh HD
          Boot Mode: Normal
          Computer Name: hostname
          User Name: System Administrator (root)
          Secure Virtual Memory: Enabled
          System Integrity Protection: Enabled
          Time since boot: 5 days 20:57
    */

    $hw = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.2.3.1.2.8.104.97.114.100.119.97.114.101', 'UCD-SNMP-MIB');
    if (strlen($hw)) {

        $hw_array = [];
        foreach (explode("\n", $hw) as $line) {
            [$param, $value] = explode(': ', trim($line), 2);

            switch ($param) {
                case 'Model Name':
                case 'Model Identifier':
                case 'Processor Name':
                case 'Processor Speed':
                case 'Number of Processors':
                case 'Total Number of Cores':
                case 'Memory':
                    // useful info for detect hardware
                    $param            = strtolower(str_replace(' ', '_', $param));
                    $hw_array[$param] = $value;
                    break;

                case 'Serial Number (system)':
                    $serial = $value;
                    break;

                case 'System Version':
                    // accurate version
                    [, $version] = explode(' ', $value);
                    break;

                case 'Time since boot':
                    // accurate uptime?..
                    break;
            }
        }

        if ($hw_array) {
            print_debug_vars($hw_array);
            $hardware = $hw_array['model_name'];

            // idents
            preg_match('/^(?<base>[a-z]+)(?<num>[\d,]+)/i', $hw_array['model_identifier'], $ident);

            if (str_starts($hw_array['model_identifier'], ['iMacPro', 'MacPro'])) {
                // iMacPro1,1 - iMac Pro "10-Core" 3.0 27-Inch (5K, Late 2017)
                // MacPro7,1  - Mac Pro "28-Core" 2.5 (2019)
                // MacPro7,1  - Mac Pro "28-Core" 2.5 (2019 - Rack)
                $type = 'workstation';

                // append processor cores
                $hardware .= ' "' . $hw_array['total_number_of_cores'] . '-Core"';
                // append processor speed
                $hardware .= ' ' . preg_replace('/[^\d\.]+$/', '', $hw_array['processor_speed']);
                // extra
                if ($ident['base'] === 'iMacPro') {
                    switch ($ident['num']) {
                        case '1,1':
                            $hardware .= ' 27-Inch (Late 2017, 5K)';
                            break;
                        //case '1,2': $hardware .= ' (Early 2013)'; break;
                    }
                } elseif ($ident['base'] === 'MacPro') {
                    switch ($ident['num']) {
                        case '4,1':
                            $hardware .= ' (2009)';
                            break;
                        case '5,1':
                            $hardware .= ' (2012)';
                            break;
                        case '6,1':
                            $hardware .= ' (Late 2013)';
                            break;
                        case '7,1':
                            $hardware .= ' (2019)';
                            break;
                    }
                }
            } elseif (str_starts($hw_array['model_identifier'], ['iMac', 'Macmini', 'MacBook'])) {
                // iMac19,2       - iMac 21.5-Inch "Core i7" 3.2 (4K, 2019)
                // Macmini8,1     - Mac mini "Core i7" 3.2 (Late 2018)
                // MacBook10,1    - MacBook "Core m3" 1.2 12" (Mid-2017)
                // MacBookAir9,1  - MacBook Air "Core i7" 1.2 13" (Scissor, 2020)
                // MacBookPro16,1 - MacBook Pro 16-Inch "Core i9" 2.4 2019 (Scissor)
                $type = 'workstation';

                // append processor
                $hardware .= ' "' . preg_replace('/^.*Intel /', '', $hw_array['processor_name']) . '"';
                // append processor speed
                $hardware .= ' ' . preg_replace('/[^\d\.]+$/', '', $hw_array['processor_speed']);
                // extra
                if ($ident['base'] === 'MacBookPro') {
                    switch ($ident['num']) {
                        case '10,1':
                            $hardware .= ' 15" (Early 2013)';
                            break;
                        case '10,2':
                            $hardware .= ' 13" (Early 2013)';
                            break;

                        case '11,1':
                            $hardware .= ' 13" (Late 2013)';
                            break;
                        case '11,2':
                            $hardware .= ' 15" (Late 2013, IG)';
                            break;
                        case '11,3':
                            $hardware .= ' 15" (Late 2013, DG)';
                            break;
                        case '11,4':
                            $hardware .= ' 15" (Mid-2015, IG)';
                            break;
                        case '11,5':
                            $hardware .= ' 15" (Mid-2015, DG)';
                            break;

                        case '12,1':
                            $hardware .= ' 13" (Early 2015)';
                            break;
                        case '12,2':
                            $hardware .= ' 13" (Early 2015)';
                            break;
                        case '12,3':
                            $hardware .= ' 15" (Early 2015)';
                            break;

                        case '13,1':
                            $hardware .= ' 13" (Late 2016)';
                            break;
                        case '13,2':
                            $hardware .= ' 13" (Touch/Late 2016)';
                            break;
                        case '13,3':
                            $hardware .= ' 15" (Touch/Late 2016)';
                            break;

                        case '14,1':
                            $hardware .= ' 13" (Mid 2017)';
                            break;
                        case '14,2':
                            $hardware .= ' 13" (Touch/Mid 2017)';
                            break;
                        case '14,3':
                            $hardware .= ' 15" (Touch/Mid 2017)';
                            break;

                        case '15,1':
                            $hardware .= ' 15" (2018)';
                            break;
                        case '15,2':
                            $hardware .= ' 13" (2018)';
                            break;
                        case '15,3':
                            $hardware .= ' 13" (2018, Vega)';
                            break;
                        case '15,4':
                            $hardware .= ' 13" (2019, 2)';
                            break;

                        case '16,1':
                            $hardware .= ' 16" (2019)';
                            break;
                        case '16,2':
                            $hardware .= ' 13" (2020, 4)';
                            break;
                        case '16,3':
                            $hardware .= ' 13" (2020, 2)';
                            break;
                        case '16,4':
                            $hardware .= ' 16" (2019, 5600M)';
                            break;

                        case '17,1':
                            $hardware .= ' 13" (M1, 2020)';
                            break;
                    }
                } elseif ($ident['base'] === 'MacBookAir') {
                    switch ($ident['num']) {
                        case '5,1':
                            $hardware .= ' 11" (Mid 2012)';
                            break;
                        case '5,2':
                            $hardware .= ' 13" (Mid 2012)';
                            break;

                        case '6,1':
                            $hardware .= ' 11" (Early 2014)';
                            break;
                        case '6,2':
                            $hardware .= ' 13" (Early 2014)';
                            break;

                        case '7,1':
                            $hardware .= ' 11" (Early 2015)';
                            break;
                        case '7,2':
                            $hardware .= ' 13" (Early 2015)';
                            break;

                        case '8,1':
                            $hardware .= ' 13" (Late 2018)';
                            break;
                        case '8,2':
                            $hardware .= ' 13" (2019)';
                            break;

                        case '9,1':
                            $hardware .= ' 13" (2020)';
                            break;
                        //case '9,2': $hardware .= ' 13" (Early 2013)'; break;

                        case '10,1':
                            $hardware .= ' 13" (M1, 2020)';
                            break;
                    }
                } elseif ($ident['base'] === 'Macmini') {
                    switch ($ident['num']) {
                        case '5,1':
                            $hardware .= ' (Mid 2011)';
                            break;
                        case '5,2':
                            $hardware .= ' (Mid 2011)';
                            break;
                        case '5,3':
                            $hardware .= ' (Mid 2011)';
                            $type     = 'server';
                            break; // Server

                        case '6,1':
                            $hardware .= ' (Late 2012)';
                            break;
                        case '6,2':
                            $hardware .= ' (Late 2012)';
                            $type     = 'server';
                            break; // Server

                        case '7,1':
                            $hardware .= ' (Late 2014)';
                            break;

                        case '8,1':
                            $hardware .= ' (Late 2018)';
                            break;

                        case '9,1':
                            $hardware .= ' (M1, 2020)';
                            break;
                    }
                } elseif ($ident['base'] === 'iMac') {
                    switch ($ident['num']) {
                        case '13,1':
                            $hardware .= ' (Late 2012)';
                            break;
                        case '13,2':
                            $hardware .= ' (Late 2012)';
                            break;

                        case '14,1':
                            $hardware .= ' (Late 2013)';
                            break;
                        case '14,2':
                            $hardware .= ' (Late 2013)';
                            break;
                        case '14,3':
                            $hardware .= ' (Late 2013)';
                            break;
                        case '14,4':
                            $hardware .= ' (Mid 2014)';
                            break;

                        case '15,1':
                            $hardware .= ' (Late 2014, 5K)';
                            break;

                        case '16,1':
                            $hardware .= ' (Late 2015)';
                            break;
                        case '16,2':
                            $hardware .= ' (Late 2015, 4K)';
                            break;

                        case '17,1':
                            $hardware .= ' (Late 2015, 5K)';
                            break;
                        //case '17,2': $hardware .= ' (Mid-2017, 4K)'; break;
                        //case '17,3': $hardware .= ' (Mid-2017, 5K)'; break;

                        case '18,1':
                            $hardware .= ' (Mid 2017)';
                            break;
                        case '18,2':
                            $hardware .= ' (Mid 2017, 4K)';
                            break;
                        case '18,3':
                            $hardware .= ' (Mid 2017, 5K)';
                            break;

                        case '19,1':
                            $hardware .= ' 27-Inch (2019)';
                            break;
                        case '19,2':
                            $hardware .= ' 21.5-Inch (2019, 4K)';
                            break;
                        case '19,3':
                            $hardware .= ' (2019, 5K)';
                            break;

                        case '20,1':
                            $hardware .= ' 27-Inch (Mid 2020, 5K)';
                            break;
                        case '20,2':
                            $hardware .= ' 27-Inch (Mid 2020, 5K, 5700/XT)';
                            break;

                        case '21,1':
                            $hardware .= ' 24-Inch (M1, 2021)';
                            break; // 8-Core
                        case '21,2':
                            $hardware .= ' 24-Inch (M1, 2021, 7c)';
                            break; // 7-Core
                    }
                }
            } elseif (str_starts($hw_array['model_identifier'], ['PowerBook', 'PowerMac'])) {
                // PowerBook5,9   - PowerBook G4 1.67 17" (DLSD/HR - Al)
                // PowerMac11,2   - Power Macintosh G5 "Quad Core" (2.5)
                $type = 'workstation';
            } elseif (str_starts($hw_array['model_identifier'], ['RackMac', 'Xserve'])) {
                $type = 'server';

                // append processor speed
                $hardware .= ' ' . preg_replace('/[^\d\.]+$/', '', $hw_array['processor_speed']);
                // append processor cores
                $hardware .= ' "' . $hw_array['total_number_of_cores'] . '-Core"';
                // extra
                if ($ident['base'] === 'Xserve') {
                    switch ($ident['num']) {
                        case '1,1':
                            $hardware .= ' (Late 2006)';
                            break;
                        case '2,1':
                            $hardware .= ' (Early 2008)';
                            break;
                        case '3,1':
                            $hardware .= ' (Early 2009)';
                            break;
                    }
                }
            }
        }
    }
}

/* https://everymac.com/systems/by_capability/mac-specs-by-machine-model-machine-id.html

- eMac G4/700	PowerMac4,4
- eMac G4/800	PowerMac4,4
- eMac G4/800 (ATI) PowerMac4,4
- eMac G4/1.0 (ATI) PowerMac4,4

- eMac G4/1.25 (USB 2.0) PowerMac6,4
- eMac G4/1.42 (2005) PowerMac6,4

- iBook G3/300 (Original/Clamshell) PowerBook2,1
- iBook G3/366 SE (Original/Clamshell) PowerBook2,1

- iBook G3/366 (Firewire/Clamshell) PowerBook2,2
- iBook G3/466 SE (Firewire/Clamshell) PowerBook2,2

- iBook G3/500 (Dual USB - Tr) PowerBook4,1
- iBook G3/500 (Late 2001 - Tr) PowerBook4,1
- iBook G3/600 (Late 2001 - Tr) PowerBook4,1

- iBook G3/600 14-Inch (Early 2002 - Tr) PowerBook4,2

- iBook G3/600 (16 VRAM - Tr) PowerBook4,3
- iBook G3/700 (16 VRAM - Tr) PowerBook4,3
- iBook G3/700 14-Inch (16 VRAM - Tr) PowerBook4,3
- iBook G3/700 (16 VRAM - Op) PowerBook4,3
- iBook G3/800 (32 VRAM - Tr) PowerBook4,3
- iBook G3/800 14-Inch (32 VRAM - Tr) PowerBook4,3
- iBook G3/800 (Early 2003 - Op) PowerBook4,3
- iBook G3/900 (Early 2003 - Op) PowerBook4,3
- iBook G3/900 14-Inch (Early 2003 - Op) PowerBook4,3

- iBook G4/800 12-Inch (Original - Op) PowerBook6,3
- iBook G4/933 14-Inch (Original - Op) PowerBook6,3
- iBook G4/1.0 14-Inch (Original - Op) PowerBook6,3

- iBook G4/1.0 12-Inch (Early 2004 - Op) PowerBook6,5
- iBook G4/1.0 14-Inch (Early 2004 - Op) PowerBook6,5
- iBook G4/1.2 14-Inch (Early 2004 - Op) PowerBook6,5
- iBook G4/1.2 12-Inch (Late 2004 - Op) PowerBook6,5
- iBook G4/1.33 14-Inch (Late 2004 - Op) PowerBook6,5

- iBook G4/1.33 12-Inch (Mid-2005 - Op) PowerBook6,7
- iBook G4/1.42 14-Inch (Mid-2005 - Op) PowerBook6,7

- iMac 17-Inch "Core Duo" 1.83 iMac4,1

- iMac 17-Inch "Core Duo" 1.83 (IG) iMac4,2

- iMac 17-Inch "Core 2 Duo" 1.83 (IG) iMac5,2

- iMac 17-Inch "Core 2 Duo" 2.0 iMac5,1
- iMac 17-Inch "Core 2 Duo" 2.16 iMac5,1

- iMac 20-Inch "Core Duo" 2.0 iMac4,1

- iMac 20-Inch "Core 2 Duo" 2.16 iMac5,1
- iMac 20-Inch "Core 2 Duo" 2.33 iMac5,1

- iMac 20-Inch "Core 2 Duo" 2.0 (Al) iMac7,1
- iMac 20-Inch "Core 2 Duo" 2.4 (Al) iMac7,1

- iMac 20-Inch "Core 2 Duo" 2.4 (Early 2008) iMac8,1
- iMac 20-Inch "Core 2 Duo" 2.66 (Early 2008) iMac8,1

- iMac 20-Inch "Core 2 Duo" 2.66 (Early 2009) iMac9,1
- iMac 20-Inch "Core 2 Duo" 2.0 (Mid-2009) iMac9,1
- iMac 20-Inch "Core 2 Duo" 2.26 (Mid-2009) iMac9,1

- iMac 21.5-Inch "Core 2 Duo" 3.06 (Late 2009) iMac10,1
- iMac 21.5-Inch "Core 2 Duo" 3.33 (Late 2009) iMac10,1

- iMac 21.5-Inch "Core i3" 3.06 (Mid-2010) iMac11,2
- iMac 21.5-Inch "Core i3" 3.2 (Mid-2010) iMac11,2
- iMac 21.5-Inch "Core i5" 3.6 (Mid-2010) iMac11,2

- iMac 21.5-Inch "Core i5" 2.5 (Mid-2011) iMac12,1
- iMac 21.5-Inch "Core i5" 2.7 (Mid-2011) iMac12,1
- iMac 21.5-Inch "Core i7" 2.8 (Mid-2011) iMac12,1
- iMac 21.5-Inch "Core i3" 3.1 (Late 2011) iMac12,1

- iMac 21.5-Inch "Core i5" 2.7 (Late 2012) iMac13,1
- iMac 21.5-Inch "Core i5" 2.9 (Late 2012) iMac13,1
- iMac 21.5-Inch "Core i7" 3.1 (Late 2012) iMac13,1
- iMac 21.5-Inch "Core i3" 3.3 (Early 2013) iMac13,1

- iMac 21.5-Inch "Core i5" 2.7 (Late 2013) iMac14,1

- iMac 21.5-Inch "Core i5" 2.9 (Late 2013) iMac14,3
- iMac 21.5-Inch "Core i7" 3.1 (Late 2013) iMac14,3

- iMac 21.5-Inch "Core i5" 1.4 (Mid-2014) iMac14,4

- iMac 21.5-Inch "Core i5" 1.6 (Late 2015) iMac16,1

- iMac 21.5-Inch "Core i5" 2.8 (Late 2015) iMac16,2
- iMac 21.5-Inch "Core i5" 3.1 (4K, Late 2015) iMac16,2
- iMac 21.5-Inch "Core i7" 3.3 (4K, Late 2015) iMac16,2

- iMac 21.5-Inch "Core i5" 2.3 (Mid-2017) iMac18,1
- iMac 21.5-Inch "Core i5" 3.0 (4K, Mid-2017) iMac18,2
- iMac 21.5-Inch "Core i5" 3.4 (4K, Mid-2017) iMac18,2
- iMac 21.5-Inch "Core i7" 3.6 (4K, Mid-2017) iMac18,2

- iMac 21.5-Inch "Core i3" 3.6 (4K, 2019) iMac19,2
- iMac 21.5-Inch "Core i5" 3.0 (4K, 2019) iMac19,2
- iMac 21.5-Inch "Core i7" 3.2 (4K, 2019) iMac19,2

- iMac 24-Inch "Core 2 Duo" 2.16 iMac6,1
- iMac 24-Inch "Core 2 Duo" 2.33 iMac6,1

- iMac 24-Inch "Core 2 Duo" 2.4 (Al) iMac7,1
- iMac 24-Inch "Core 2 Extreme" 2.8 (Al) iMac7,1

- iMac 24-Inch "Core 2 Duo" 2.8 (Early 2008) iMac8,1
- iMac 24-Inch "Core 2 Duo" 3.06 (Early 2008) iMac8,1

- iMac 24-Inch "Core 2 Duo" 2.66 (Early 2009) iMac9,1
- iMac 24-Inch "Core 2 Duo" 2.93 (Early 2009) iMac9,1
- iMac 24-Inch "Core 2 Duo" 3.06 (Early 2009) iMac9,1

- iMac 27-Inch "Core 2 Duo" 3.06 (Late 2009) iMac10,1
- iMac 27-Inch "Core 2 Duo" 3.33 (Late 2009) iMac10,1

- iMac 27-Inch "Core i5" 2.66 (Late 2009) iMac11,1
- iMac 27-Inch "Core i7" 2.8 (Late 2009) iMac11,1

- iMac 27-Inch "Core i3" 3.2 (Mid-2010) iMac11,3
- iMac 27-Inch "Core i5" 2.8 (Mid-2010) iMac11,3
- iMac 27-Inch "Core i5" 3.6 (Mid-2010) iMac11,3
- iMac 27-Inch "Core i7" 2.93 (Mid-2010) iMac11,3

- iMac 27-Inch "Core i5" 2.7 (Mid-2011) iMac12,2
- iMac 27-Inch "Core i5" 3.1 (Mid-2011) iMac12,2
- iMac 27-Inch "Core i7" 3.4 (Mid-2011) iMac12,2

- iMac 27-Inch "Core i5" 2.9 (Late 2012) iMac13,2
- iMac 27-Inch "Core i5" 3.2 (Late 2012) iMac13,2
- iMac 27-Inch "Core i7" 3.4 (Late 2012) iMac13,2

- iMac 27-Inch "Core i5" 3.2 (Late 2013) iMac14,2
- iMac 27-Inch "Core i5" 3.4 (Late 2013) iMac14,2
- iMac 27-Inch "Core i7" 3.5 (Late 2013) iMac14,2

- iMac 27-Inch "Core i5" 3.5 (5K, Late 2014) iMac15,1
- iMac 27-Inch "Core i7" 4.0 (5K, Late 2014) iMac15,1
- iMac 27-Inch "Core i5" 3.3 (5K, Mid-2015) iMac15,1

- iMac 27-Inch "Core i5" 3.2 (5K, Late 2015) iMac17,1
- iMac 27-Inch "Core i5" 3.3 (5K, Late 2015) iMac17,1
- iMac 27-Inch "Core i7" 4.0 (5K, Late 2015) iMac17,1

- iMac 27-Inch "Core i5" 3.4 (5K, Mid-2017) iMac18,3
- iMac 27-Inch "Core i5" 3.5 (5K, Mid-2017) iMac18,3
- iMac 27-Inch "Core i5" 3.8 (5K, Mid-2017) iMac18,3
- iMac 27-Inch "Core i7" 4.2 (5K, Mid-2017) iMac18,3

- iMac 27-Inch "Core i5" 3.0 (5K, 2019) iMac19,1
- iMac 27-Inch "Core i5" 3.1 (5K, 2019) iMac19,1
- iMac 27-Inch "Core i5" 3.7 (5K, 2019) iMac19,1
- iMac 27-Inch "Core i9" 3.6 (5K, 2019) iMac19,1

- iMac G3 233 Original - Bondi (Rev. A & B) iMac,1
- iMac G3 266 (Fruit Colors) iMac,1
- iMac G3 333 (Fruit Colors) iMac,1

- iMac G3 350 (Slot Loading - Blueberry) PowerMac2,1
- iMac G3 400 DV (Slot Loading - Fruit) PowerMac2,1
- iMac G3 400 DV SE (Slot Loading) PowerMac2,1

- iMac G3 350 (Summer 2000 - Indigo) PowerMac2,2
- iMac G3 400 DV (Summer 2000 - I/R) PowerMac2,2
- iMac G3 450 DV+ (Summer 2000) PowerMac2,2
- iMac G3 500 DV SE (Summer 2000) PowerMac2,2

- iMac G3 400 (Early 2001 - Indigo) PowerMac4,1
- iMac G3 500 (Early 2001 - Flower/Blue) PowerMac4,1
- iMac G3 600 SE (Early 2001) PowerMac4,1
- iMac G3 500 (Summer 2001 - I/S) PowerMac4,1
- iMac G3 600 (Summer 2001) PowerMac4,1
- iMac G3 700 SE (Summer 2001) PowerMac4,1

- iMac G4 700 (Flat Panel) PowerMac4,2
- iMac G4 800 (Flat Panel) PowerMac4,2
- iMac G4 800 17" (Flat Panel) PowerMac4,5
- iMac G4 800 - X Only (Flat Panel) PowerMac4,2
- iMac G4 1.0 17" (Flat Panel) PowerMac6,1
- iMac G4 1.0 15" "FP" (USB 2.0) PowerMac6,3*
- iMac G4 1.25 17" "FP" (USB 2.0) PowerMac6,3
- iMac G4 1.25 20" "FP" (USB 2.0) PowerMac6,3
- iMac G5 1.6 17" PowerMac8,1
- iMac G5 1.8 17" PowerMac8,1
- iMac G5 1.8 20" PowerMac8,1
- iMac G5 1.8 17" (ALS) PowerMac8,2
- iMac G5 2.0 17" (ALS) PowerMac8,2
- iMac G5 2.0 20" (ALS) PowerMac8,2
- iMac G5 1.9 17" (iSight) PowerMac12,1
- iMac G5 2.1 20" (iSight) PowerMac12,1

- iMac Pro "8-Core" 3.2 27-Inch (5K, Late 2017) iMacPro1,1
- iMac Pro "10-Core" 3.0 27-Inch (5K, Late 2017) iMacPro1,1
- iMac Pro "14-Core" 2.5 27-Inch (5K, Late 2017) iMacPro1,1
- iMac Pro "18-Core" 2.3 27-Inch (5K, Late 2017) iMacPro1,1

- Mac mini G4/1.25 PowerMac10,1
- Mac mini G4/1.42 PowerMac10,1
- Mac mini G4/1.33 PowerMac10,2
- Mac mini G4/1.5 PowerMac10,2

- Mac mini "Core Solo" 1.5 Macmini1,1
- Mac mini "Core Duo" 1.66 Macmini1,1
- Mac mini "Core Duo" 1.83 Macmini1,1
- Mac mini "Core 2 Duo" 1.83 Macmini2,1
- Mac mini "Core 2 Duo" 2.0 Macmini2,1
- Mac mini "Core 2 Duo" 2.0 (Early 2009) Macmini3,1
- Mac mini "Core 2 Duo" 2.26 (Early 2009) Macmini3,1
- Mac mini "Core 2 Duo" 2.26 (Late 2009) Macmini3,1
- Mac mini "Core 2 Duo" 2.53 (Late 2009) Macmini3,1
- Mac mini "Core 2 Duo" 2.66 (Late 2009) Macmini3,1
- Mac mini "Core 2 Duo" 2.53 (Server) Macmini3,1
- Mac mini "Core 2 Duo" 2.4 (Mid-2010) Macmini4,1
- Mac mini "Core 2 Duo" 2.66 (Mid-2010) Macmini4,1
- Mac mini "Core 2 Duo" 2.66 (Server) Macmini4,1
- Mac mini "Core i5" 2.3 (Mid-2011) Macmini5,1
- Mac mini "Core i5" 2.5 (Mid-2011) Macmini5,2
- Mac mini "Core i7" 2.7 (Mid-2011) Macmini5,2
- Mac mini "Core i7" 2.0 (Mid-2011/Server) Macmini5,3
- Mac mini "Core i5" 2.5 (Late 2012) Macmini6,1
- Mac mini "Core i7" 2.3 (Late 2012) Macmini6,2
- Mac mini "Core i7" 2.6 (Late 2012) Macmini6,2
- Mac mini "Core i7" 2.3 (Late 2012/Server) Macmini6,2
- Mac mini "Core i7" 2.6 (Late 2012/Server) Macmini6,2
- Mac mini "Core i5" 1.4 (Late 2014) Macmini7,1
- Mac mini "Core i5" 2.6 (Late 2014) Macmini7,1
- Mac mini "Core i5" 2.8 (Late 2014) Macmini7,1
- Mac mini "Core i7" 3.0 (Late 2014) Macmini7,1
- Mac mini "Core i3" 3.6 (Late 2018) Macmini8,1
- Mac mini "Core i5" 3.0 (Late 2018) Macmini8,1
- Mac mini "Core i7" 3.2 (Late 2018) Macmini8,1

- Mac Pro "Quad Core" 2.0 (Original) MacPro1,1*
- Mac Pro "Quad Core" 2.66 (Original) MacPro1,1*
- Mac Pro "Quad Core" 3.0 (Original) MacPro1,1*
- Mac Pro "Eight Core" 3.0 (2,1) MacPro2,1
- Mac Pro "Quad Core" 2.8 (2008) MacPro3,1
- Mac Pro "Eight Core" 2.8 (2008) MacPro3,1
- Mac Pro "Eight Core" 3.0 (2008) MacPro3,1
- Mac Pro "Eight Core" 3.2 (2008) MacPro3,1
- Mac Pro "Quad Core" 2.66 (2009/Nehalem) MacPro4,1
- Mac Pro "Quad Core" 2.93 (2009/Nehalem) MacPro4,1
- Mac Pro "Quad Core" 3.33 (2009/Nehalem) MacPro4,1
- Mac Pro "Eight Core" 2.26 (2009/Nehalem) MacPro4,1
- Mac Pro "Eight Core" 2.66 (2009/Nehalem) MacPro4,1
- Mac Pro "Eight Core" 2.93 (2009/Nehalem) MacPro4,1
- Mac Pro "Quad Core" 2.8 (2010/Nehalem) MacPro5,1
- Mac Pro "Quad Core" 3.2 (2010/Nehalem) MacPro5,1
- Mac Pro "Six Core" 3.33 (2010/Westmere) MacPro5,1
- Mac Pro "Eight Core" 2.4 (2010/Westmere) MacPro5,1
- Mac Pro "Twelve Core" 2.66 (2010/Westmere) MacPro5,1
- Mac Pro "Twelve Core" 2.93 (2010/Westmere) MacPro5,1
- Mac Pro "Quad Core" 2.8 (Server 2010) MacPro5,1
- Mac Pro "Quad Core" 3.2 (Server 2010) MacPro5,1
- Mac Pro "Six Core" 3.33 (Server 2010) MacPro5,1
- Mac Pro "Eight Core" 2.4 (Server 2010) MacPro5,1
- Mac Pro "Twelve Core" 2.66 (Server 2010) MacPro5,1
- Mac Pro "Twelve Core" 2.93 (Server 2010) MacPro5,1
- Mac Pro "Quad Core" 3.2 (2012/Nehalem) MacPro5,1
- Mac Pro "Six Core" 3.33 (2012/Westmere) MacPro5,1
- Mac Pro "Twelve Core" 2.4 (2012/Westmere) MacPro5,1
- Mac Pro "Twelve Core" 2.66 (2012/Westmere) MacPro5,1
- Mac Pro "Twelve Core" 3.06 (2012/Westmere) MacPro5,1
- Mac Pro "Quad Core" 3.2 (Server 2012) MacPro5,1
- Mac Pro "Six Core" 3.33 (Server 2012) MacPro5,1
- Mac Pro "Twelve Core" 2.4 (Server 2012) MacPro5,1
- Mac Pro "Twelve Core" 2.66 (Server 2012) MacPro5,1
- Mac Pro "Twelve Core" 3.06 (Server 2012) MacPro5,1
- Mac Pro "Quad Core" 3.7 (Late 2013) MacPro6,1
- Mac Pro "Six Core" 3.5 (Late 2013) MacPro6,1
- Mac Pro "Eight Core" 3.0 (Late 2013) MacPro6,1
- Mac Pro "Twelve Core" 2.7 (Late 2013) MacPro6,1
- Mac Pro "Eight Core" 3.5 (2019) MacPro7,1
- Mac Pro "12-Core" 3.3 (2019) MacPro7,1
- Mac Pro "16-Core" 3.2 (2019) MacPro7,1
- Mac Pro "24-Core" 2.7 (2019) MacPro7,1
- Mac Pro "28-Core" 2.5 (2019) MacPro7,1
- Mac Pro "Eight Core" 3.5 (2019 - Rack) MacPro7,1
- Mac Pro "12-Core" 3.3 (2019 - Rack) MacPro7,1
- Mac Pro "16-Core" 3.2 (2019 - Rack) MacPro7,1
- Mac Pro "24-Core" 2.7 (2019 - Rack) MacPro7,1
- Mac Pro "28-Core" 2.5 (2019 - Rack) MacPro7,1

- Mac Server G3 233 Minitower N/A*
- Mac Server G3 266 Minitower N/A*
- Mac Server G3 300 Minitower N/A*
- Mac Server G3 333 Minitower N/A*

- Mac Server G3 350 (Blue & White) PowerMac1,1
- Mac Server G3 400 (Blue & White) PowerMac1,1
- Mac Server G3 450 (Blue & White) PowerMac1,1
- Mac Server G4 350 (AGP) PowerMac3,1
- Mac Server G4 400 (AGP) PowerMac3,1
- Mac Server G4 450 (AGP) PowerMac3,1
- Mac Server G4 500 (AGP) PowerMac3,1
- Mac Server G4 450 DP (Gigabit) PowerMac3,3
- Mac Server G4 500 DP (Gigabit) PowerMac3,3
- Mac Server G4 533 (Digital Audio) PowerMac3,4
- Mac Server G4 533 DP (Digital Audio) PowerMac3,4
- Mac Server G4 733 (Quicksilver) PowerMac3,5
- Mac Server G4 800 DP (Quicksilver) PowerMac3,5
- Mac Server G4 933 (QS 2002) PowerMac3,5
- Mac Server G4 1.0 DP (QS 2002) PowerMac3,5
- Mac Server G4 1.0 DP (MDD) PowerMac3,6
- Mac Server G4 1.25 DP (MDD) PowerMac3,6

- MacBook "Core Duo" 1.83 13" MacBook1,1
- MacBook "Core Duo" 2.0 13" (White) MacBook1,1
- MacBook "Core Duo" 2.0 13" (Black) MacBook1,1
- MacBook "Core 2 Duo" 1.83 13" MacBook2,1
- MacBook "Core 2 Duo" 2.0 13" (White/06) MacBook2,1
- MacBook "Core 2 Duo" 2.0 13" (Black) MacBook2,1
- MacBook "Core 2 Duo" 2.0 13" (White/07) MacBook2,1
- MacBook "Core 2 Duo" 2.16 13" (White) MacBook2,1
- MacBook "Core 2 Duo" 2.16 13" (Black) MacBook2,1
- MacBook "Core 2 Duo" 2.0 13" (White-SR) MacBook3,1
- MacBook "Core 2 Duo" 2.2 13" (White-SR) MacBook3,1
- MacBook "Core 2 Duo" 2.2 13" (Black-SR) MacBook3,1
- MacBook "Core 2 Duo" 2.1 13" (White-08) MacBook4,1
- MacBook "Core 2 Duo" 2.4 13" (White-08) MacBook4,1
- MacBook "Core 2 Duo" 2.4 13" (Black-08) MacBook4,1
- MacBook "Core 2 Duo" 2.0 13" (Unibody) MacBook5,1
- MacBook "Core 2 Duo" 2.4 13" (Unibody) MacBook5,1
- MacBook "Core 2 Duo" 2.0 13" (White-09) MacBook5,2
- MacBook "Core 2 Duo" 2.13 13" (White-09) MacBook5,2
- MacBook "Core 2 Duo" 2.26 13" (Uni/Late 09) MacBook6,1
- MacBook "Core 2 Duo" 2.4 13" (Mid-2010) MacBook7,1
- MacBook "Core M" 1.1 12" (Early 2015) MacBook8,1
- MacBook "Core M" 1.2 12" (Early 2015) MacBook8,1
- MacBook "Core M" 1.3 12" (Early 2015) MacBook8,1
- MacBook "Core m3" 1.1 12" (Early 2016) MacBook9,1
- MacBook "Core m5" 1.2 12" (Early 2016) MacBook9,1
- MacBook "Core m7" 1.3 12" (Early 2016) MacBook9,1
- MacBook "Core m3" 1.2 12" (Mid-2017) MacBook10,1
- MacBook "Core i5" 1.3 12" (Mid-2017) MacBook10,1
- MacBook "Core i7" 1.4 12" (Mid-2017) MacBook10,1

- MacBook Air "Core 2 Duo" 1.6 13" (Original) MacBookAir1,1
- MacBook Air "Core 2 Duo" 1.8 13" (Original) MacBookAir1,1
- MacBook Air "Core 2 Duo" 1.6 13" (NVIDIA) MacBookAir2,1
- MacBook Air "Core 2 Duo" 1.86 13" (NVIDIA) MacBookAir2,1
- MacBook Air "Core 2 Duo" 1.86 13" (Mid-09) MacBookAir2,1
- MacBook Air "Core 2 Duo" 2.13 13" (Mid-09) MacBookAir2,1
- MacBook Air "Core 2 Duo" 1.4 11" (Late '10) MacBookAir3,1
- MacBook Air "Core 2 Duo" 1.6 11" (Late '10) MacBookAir3,1
- MacBook Air "Core 2 Duo" 1.86 13" (Late '10) MacBookAir3,2
- MacBook Air "Core 2 Duo" 2.13 13" (Late '10) MacBookAir3,2

- MacBook Air "Core i5" 1.6 11" (Mid-2011) MacBookAir4,1
- MacBook Air "Core i7" 1.8 11" (Mid-2011) MacBookAir4,1

- MacBook Air "Core i5" 1.7 13" (Mid-2011) MacBookAir4,2
- MacBook Air "Core i7" 1.8 13" (Mid-2011) MacBookAir4,2
- MacBook Air "Core i5" 1.6 13" (Edu Only) MacBookAir4,2

- MacBook Air "Core i5" 1.7 11" (Mid-2012) MacBookAir5,1
- MacBook Air "Core i7" 2.0 11" (Mid-2012) MacBookAir5,1

- MacBook Air "Core i5" 1.7 13" (Edu Only) MacBookAir5,2
- MacBook Air "Core i5" 1.8 13" (Mid-2012) MacBookAir5,2
- MacBook Air "Core i7" 2.0 13" (Mid-2012) MacBookAir5,2

- MacBook Air "Core i5" 1.3 11" (Mid-2013) MacBookAir6,1
- MacBook Air "Core i7" 1.7 11" (Mid-2013) MacBookAir6,1

- MacBook Air "Core i5" 1.3 13" (Mid-2013) MacBookAir6,2
- MacBook Air "Core i7" 1.7 13" (Mid-2013) MacBookAir6,2

- MacBook Air "Core i5" 1.4 11" (Early 2014) MacBookAir6,1
- MacBook Air "Core i7" 1.7 11" (Early 2014) MacBookAir6,1

- MacBook Air "Core i5" 1.4 13" (Early 2014) MacBookAir6,2
- MacBook Air "Core i7" 1.7 13" (Early 2014) MacBookAir6,2

- MacBook Air "Core i5" 1.6 11" (Early 2015) MacBookAir7,1
- MacBook Air "Core i7" 2.2 11" (Early 2015) MacBookAir7,1

- MacBook Air "Core i5" 1.6 13" (Early 2015) MacBookAir7,2
- MacBook Air "Core i7" 2.2 13" (Early 2015) MacBookAir7,2
- MacBook Air "Core i5" 1.8 13" (2017*)      MacBookAir7,2
- MacBook Air "Core i7" 2.2 13" (2017*)      MacBookAir7,2

- MacBook Air "Core i5" 1.6 13" (Late 2018)  MacBookAir8,1

- MacBook Air "Core i5" 1.6 13" (True Tone, 2019) MacBookAir8,2

- MacBook Air "Core i3" 1.1 13" (Scissor, 2020) MacBookAir9,1
- MacBook Air "Core i5" 1.1 13" (Scissor, 2020) MacBookAir9,1
- MacBook Air "Core i7" 1.2 13" (Scissor, 2020) MacBookAir9,1

- MacBook Pro 13-Inch "Core 2 Duo" 2.26 (SD/FW) MacBookPro5,5
- MacBook Pro 13-Inch "Core 2 Duo" 2.53 (SD/FW) MacBookPro5,5

- MacBook Pro 13-Inch "Core 2 Duo" 2.4 Mid-2010 MacBookPro7,1
- MacBook Pro 13-Inch "Core 2 Duo" 2.66 Mid-2010 MacBookPro7,1

- MacBook Pro 13-Inch "Core i5" 2.3 Early 2011 MacBookPro8,1
- MacBook Pro 13-Inch "Core i7" 2.7 Early 2011 MacBookPro8,1
- MacBook Pro 13-Inch "Core i5" 2.4 Late 2011 MacBookPro8,1
- MacBook Pro 13-Inch "Core i7" 2.8 Late 2011 MacBookPro8,1
- MacBook Pro 13-Inch "Core i5" 2.5 Mid-2012 MacBookPro9,2
- MacBook Pro 13-Inch "Core i7" 2.9 Mid-2012 MacBookPro9,2
- MacBook Pro 13-Inch "Core i5" 2.5 Retina 2012 MacBookPro10,2
- MacBook Pro 13-Inch "Core i7" 2.9 Retina 2012 MacBookPro10,2
- MacBook Pro 13-Inch "Core i5" 2.6 Early 2013 MacBookPro10,2
- MacBook Pro 13-Inch "Core i7" 3.0 Early 2013 MacBookPro10,2
- MacBook Pro 13-Inch "Core i5" 2.4 Late 2013 MacBookPro11,1
- MacBook Pro 13-Inch "Core i5" 2.6 Late 2013 MacBookPro11,1
- MacBook Pro 13-Inch "Core i7" 2.8 Late 2013 MacBookPro11,1
- MacBook Pro 13-Inch "Core i5" 2.6 Mid-2014 MacBookPro11,1
- MacBook Pro 13-Inch "Core i5" 2.8 Mid-2014 MacBookPro11,1
- MacBook Pro 13-Inch "Core i7" 3.0 Mid-2014 MacBookPro11,1
- MacBook Pro 13-Inch "Core i5" 2.7 Early 2015 MacBookPro12,1
- MacBook Pro 13-Inch "Core i5" 2.9 Early 2015 MacBookPro12,1
- MacBook Pro 13-Inch "Core i7" 3.1 Early 2015 MacBookPro12,1
- MacBook Pro 13-Inch "Core i5" 2.0 Late 2016 MacBookPro13,1
- MacBook Pro 13-Inch "Core i7" 2.4 Late 2016 MacBookPro13,1
- MacBook Pro 13-Inch "Core i5" 2.9 Touch/Late 2016 MacBookPro13,2
- MacBook Pro 13-Inch "Core i5" 3.1 Touch/Late 2016 MacBookPro13,2
- MacBook Pro 13-Inch "Core i7" 3.3 Touch/Late 2016 MacBookPro13,2
- MacBook Pro 13-Inch "Core i5" 2.3 Mid-2017 MacBookPro14,1
- MacBook Pro 13-Inch "Core i7" 2.5 Mid-2017 MacBookPro14,1
- MacBook Pro 13-Inch "Core i5" 3.1 Touch/Mid-2017 MacBookPro14,2
- MacBook Pro 13-Inch "Core i5" 3.3 Touch/Mid-2017 MacBookPro14,2
- MacBook Pro 13-Inch "Core i7" 3.5 Touch/Mid-2017 MacBookPro14,2
- MacBook Pro 13-Inch "Core i5" 2.3 Touch/2018 MacBookPro15,2
- MacBook Pro 13-Inch "Core i7" 2.7 Touch/2018 MacBookPro15,2
- MacBook Pro 13-Inch "Core i5" 2.4 Touch/2019 MacBookPro15,2
- MacBook Pro 13-Inch "Core i7" 2.8 Touch/2019 MacBookPro15,2
- MacBook Pro 13-Inch "Core i5" 1.4 Touch/2019 2 TB 3 MacBookPro15,4
- MacBook Pro 13-Inch "Core i7" 1.7 Touch/2019 2 TB 3 MacBookPro15,4
- MacBook Pro 13-Inch "Core i5" 1.4 2020 2 TB 3 (Scissor) MacBookPro16,3
- MacBook Pro 13-Inch "Core i7" 1.7 2020 2 TB 3 (Scissor) MacBookPro16,3
- MacBook Pro 13-Inch "Core i5" 2.0 2020 4 TB 3 (Scissor) MacBookPro16,2
- MacBook Pro 13-Inch "Core i7" 2.3 2020 4 TB 3 (Scissor) MacBookPro16,2
- MacBook Pro 15-Inch "Core Duo" 1.67 MacBookPro1,1
- MacBook Pro 15-Inch "Core Duo" 1.83 MacBookPro1,1
- MacBook Pro 15-Inch "Core Duo" 2.0 MacBookPro1,1
- MacBook Pro 15-Inch "Core Duo" 2.16 MacBookPro1,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.16 MacBookPro2,2
- MacBook Pro 15-Inch "Core 2 Duo" 2.33 MacBookPro2,2
- MacBook Pro 15-Inch "Core 2 Duo" 2.2 (SR) MacBookPro3,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.4 (SR) MacBookPro3,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.6 (SR) MacBookPro3,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.4 (08) MacBookPro4,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.5 (08) MacBookPro4,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.6 (08) MacBookPro4,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.4 (Unibody) MacBookPro5,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.53 (Unibody) MacBookPro5,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.8 (Unibody) MacBookPro5,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.66 (Unibody) MacBookPro5,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.93 (Unibody) MacBookPro5,1
- MacBook Pro 15-Inch "Core 2 Duo" 2.53 (SD) MacBookPro5,4
- MacBook Pro 15-Inch "Core 2 Duo" 2.66 (SD) MacBookPro5,3
- MacBook Pro 15-Inch "Core 2 Duo" 2.8 (SD) MacBookPro5,3
- MacBook Pro 15-Inch "Core 2 Duo" 3.06 (SD) MacBookPro5,3
- MacBook Pro 15-Inch "Core i5" 2.4 Mid-2010 MacBookPro6,2
- MacBook Pro 15-Inch "Core i5" 2.53 Mid-2010 MacBookPro6,2
- MacBook Pro 15-Inch "Core i7" 2.66 Mid-2010 MacBookPro6,2
- MacBook Pro 15-Inch "Core i7" 2.8 Mid-2010 MacBookPro6,2
- MacBook Pro 15-Inch "Core i7" 2.0 Early 2011 MacBookPro8,2
- MacBook Pro 15-Inch "Core i7" 2.2 Early 2011 MacBookPro8,2
- MacBook Pro 15-Inch "Core i7" 2.3 Early 2011 MacBookPro8,2
- MacBook Pro 15-Inch "Core i7" 2.2 Late 2011 MacBookPro8,2
- MacBook Pro 15-Inch "Core i7" 2.4 Late 2011 MacBookPro8,2
- MacBook Pro 15-Inch "Core i7" 2.5 Late 2011 MacBookPro8,2
- MacBook Pro 15-Inch "Core i7" 2.3 Mid-2012 MacBookPro9,1
- MacBook Pro 15-Inch "Core i7" 2.6 Mid-2012 MacBookPro9,1
- MacBook Pro 15-Inch "Core i7" 2.7 Mid-2012 MacBookPro9,1
- MacBook Pro 15-Inch "Core i7" 2.3 Retina 2012 MacBookPro10,1
- MacBook Pro 15-Inch "Core i7" 2.6 Retina 2012 MacBookPro10,1
- MacBook Pro 15-Inch "Core i7" 2.7 Retina 2012 MacBookPro10,1
- MacBook Pro 15-Inch "Core i7" 2.4 Early 2013 MacBookPro10,1
- MacBook Pro 15-Inch "Core i7" 2.7 Early 2013 MacBookPro10,1
- MacBook Pro 15-Inch "Core i7" 2.8 Early 2013 MacBookPro10,1
- MacBook Pro 15-Inch "Core i7" 2.0 Late 2013 (IG) MacBookPro11,2
- MacBook Pro 15-Inch "Core i7" 2.3 Late 2013 (IG) MacBookPro11,2
- MacBook Pro 15-Inch "Core i7" 2.6 Late 2013 (IG) MacBookPro11,2
- MacBook Pro 15-Inch "Core i7" 2.3 Late 2013 (DG) MacBookPro11,3
- MacBook Pro 15-Inch "Core i7" 2.6 Late 2013 (DG) MacBookPro11,3
- MacBook Pro 15-Inch "Core i7" 2.2 Mid-2014 (IG) MacBookPro11,2
- MacBook Pro 15-Inch "Core i7" 2.5 Mid-2014 (IG) MacBookPro11,2
- MacBook Pro 15-Inch "Core i7" 2.8 Mid-2014 (IG) MacBookPro11,2
- MacBook Pro 15-Inch "Core i7" 2.5 Mid-2014 (DG) MacBookPro11,3
- MacBook Pro 15-Inch "Core i7" 2.8 Mid-2014 (DG) MacBookPro11,3
- MacBook Pro 15-Inch "Core i7" 2.2 Mid-2015 (IG) MacBookPro11,4
- MacBook Pro 15-Inch "Core i7" 2.5 Mid-2015 (IG) MacBookPro11,4
- MacBook Pro 15-Inch "Core i7" 2.8 Mid-2015 (IG) MacBookPro11,4
- MacBook Pro 15-Inch "Core i7" 2.5 Mid-2015 (DG) MacBookPro11,5
- MacBook Pro 15-Inch "Core i7" 2.8 Mid-2015 (DG) MacBookPro11,5
- MacBook Pro 15-Inch "Core i7" 2.6 Touch/Late 2016 MacBookPro13,3
- MacBook Pro 15-Inch "Core i7" 2.7 Touch/Late 2016 MacBookPro13,3
- MacBook Pro 15-Inch "Core i7" 2.9 Touch/Late 2016 MacBookPro13,3
- MacBook Pro 15-Inch "Core i7" 2.8 Touch/Mid-2017 MacBookPro14,3
- MacBook Pro 15-Inch "Core i7" 2.9 Touch/Mid-2017 MacBookPro14,3
- MacBook Pro 15-Inch "Core i7" 3.1 Touch/Mid-2017 MacBookPro14,3
- MacBook Pro 15-Inch "Core i7" 2.2 Touch/2018 MacBookPro15,1
- MacBook Pro 15-Inch "Core i7" 2.6 Touch/2018 MacBookPro15,1
- MacBook Pro 15-Inch "Core i7" 2.6 Touch/2018 Vega MacBookPro15,3
- MacBook Pro 15-Inch "Core i9" 2.9 Touch/2018 MacBookPro15,1
- MacBook Pro 15-Inch "Core i9" 2.9 Touch/2018 Vega MacBookPro15,3
- MacBook Pro 15-Inch "Core i7" 2.6 Touch/2019 MacBookPro15,1
- MacBook Pro 15-Inch "Core i9" 2.3 Touch/2019 MacBookPro15,1
- MacBook Pro 15-Inch "Core i9" 2.3 Touch/2019 Vega MacBookPro15,3
- MacBook Pro 15-Inch "Core i9" 2.4 Touch/2019 MacBookPro15,1
- MacBook Pro 15-Inch "Core i9" 2.4 Touch/2019 Vega MacBookPro15,3
- MacBook Pro 16-Inch "Core i7" 2.6 2019 (Scissor) MacBookPro16,1
- MacBook Pro 16-Inch "Core i9" 2.3 2019 (Scissor) MacBookPro16,1
- MacBook Pro 16-Inch "Core i9" 2.4 2019 (Scissor) MacBookPro16,1
- MacBook Pro 17-Inch "Core Duo" 2.16 MacBookPro1,2
- MacBook Pro 17-Inch "Core 2 Duo" 2.33 MacBookPro2,1
- MacBook Pro 17-Inch "Core 2 Duo" 2.4 (SR) MacBookPro3,1
- MacBook Pro 17-Inch "Core 2 Duo" 2.6 (SR) MacBookPro3,1
- MacBook Pro 17-Inch "Core 2 Duo" 2.5 (08) MacBookPro4,1
- MacBook Pro 17-Inch "Core 2 Duo" 2.6 (08) MacBookPro4,1
- MacBook Pro 17-Inch "Core 2 Duo" 2.66 (Unibody) MacBookPro5,2
- MacBook Pro 17-Inch "Core 2 Duo" 2.93 (Unibody) MacBookPro5,2
- MacBook Pro 17-Inch "Core 2 Duo" 2.8 Mid-2009 MacBookPro5,2
- MacBook Pro 17-Inch "Core 2 Duo" 3.06 Mid-2009 MacBookPro5,2
- MacBook Pro 17-Inch "Core i5" 2.53 Mid-2010 MacBookPro6,1
- MacBook Pro 17-Inch "Core i7" 2.66 Mid-2010 MacBookPro6,1
- MacBook Pro 17-Inch "Core i7" 2.8 Mid-2010 MacBookPro6,1
- MacBook Pro 17-Inch "Core i7" 2.2 Early 2011 MacBookPro8,3
- MacBook Pro 17-Inch "Core i7" 2.3 Early 2011 MacBookPro8,3
- MacBook Pro 17-Inch "Core i7" 2.4 Late 2011 MacBookPro8,3
- MacBook Pro 17-Inch "Core i7" 2.5 Late 2011 MacBookPro8,3
- Power Macintosh G3 233 Desktop N/A*
- Power Macintosh G3 233 Minitower N/A*
- Power Macintosh G3 266 Desktop N/A*
- Power Macintosh G3 266 Minitower N/A*
- Power Macintosh G3 300 Desktop N/A*
- Power Macintosh G3 300 Minitower N/A*
- Power Macintosh G3 333 Minitower N/A*
- Power Macintosh G3 233 All-in-One N/A*
- Power Macintosh G3 266 All-in-One N/A*
- Power Macintosh G3 300 (Blue & White) PowerMac1,1
- Power Macintosh G3 350 (Blue & White) PowerMac1,1
- Power Macintosh G3 400 (Blue & White) PowerMac1,1
- Power Macintosh G3 450 (Blue & White) PowerMac1,1
- Power Macintosh G4 400 (PCI) PowerMac1,2
- Power Macintosh G4 450 (AGP) PowerMac3,1
- Power Macintosh G4 500 (AGP) PowerMac3,1
- Power Macintosh G4 350 (PCI) PowerMac1,2
- Power Macintosh G4 400 (AGP) PowerMac3,1
- Power Macintosh G4 350 (AGP) PowerMac3,1
- Power Macintosh G4 400 (Gigabit) PowerMac3,3
- Power Macintosh G4 450 DP (Gigabit) PowerMac3,3
- Power Macintosh G4 500 DP (Gigabit) PowerMac3,3
- Power Macintosh G4 450 Cube PowerMac5,1
- Power Macintosh G4 500 Cube PowerMac5,1
- Power Macintosh G4 466 (Digital Audio) PowerMac3,4
- Power Macintosh G4 533 (Digital Audio) PowerMac3,4
- Power Macintosh G4 667 (Digital Audio) PowerMac3,4
- Power Macintosh G4 733 (Digital Audio) PowerMac3,4
- Power Macintosh G4 733 (Quicksilver) PowerMac3,5
- Power Macintosh G4 867 (Quicksilver) PowerMac3,5
- Power Macintosh G4 800 DP (Quicksilver) PowerMac3,5
- Power Macintosh G4 800 (QS 2002) PowerMac3,5
- Power Macintosh G4 933 (QS 2002) PowerMac3,5
- Power Macintosh G4 1.0 DP (QS 2002) PowerMac3,5
- Power Macintosh G4 867 DP (MDD) PowerMac3,6
- Power Macintosh G4 1.0 DP (MDD) PowerMac3,6
- Power Macintosh G4 1.25 DP (MDD) PowerMac3,6
- Power Macintosh G4 1.0 (FW 800) PowerMac3,6
- Power Macintosh G4 1.25 DP (FW 800) PowerMac3,6
- Power Macintosh G4 1.42 DP (FW 800) PowerMac3,6
- Power Macintosh G4 1.25 (MDD 2003) PowerMac3,6
- Power Macintosh G5 1.6 (PCI) PowerMac7,2
- Power Macintosh G5 1.8 (PCI-X) PowerMac7,2
- Power Macintosh G5 2.0 DP (PCI-X) PowerMac7,2
- Power Macintosh G5 1.8 DP (PCI-X) PowerMac7,2
- Power Macintosh G5 1.8 DP (PCI) PowerMac7,3
- Power Macintosh G5 2.0 DP (PCI-X 2) PowerMac7,3
- Power Macintosh G5 2.5 DP (PCI-X) PowerMac7,3
- Power Macintosh G5 1.8 (PCI) PowerMac9,1
- Power Macintosh G5 2.0 DP (PCI) PowerMac7,3
- Power Macintosh G5 2.3 DP (PCI-X) PowerMac7,3
- Power Macintosh G5 2.7 DP (PCI-X) PowerMac7,3
- Power Macintosh G5 Dual Core (2.0) PowerMac11,2
- Power Macintosh G5 Dual Core (2.3) PowerMac11,2
- Power Macintosh G5 "Quad Core" (2.5) PowerMac11,2
- PowerBook G3 250 (Original/Kanga/3500) N/A*
- PowerBook G3 233 (Wallstreet) N/A*
- PowerBook G3 250 (Wallstreet) N/A*
- PowerBook G3 292 (Wallstreet) N/A*
- PowerBook G3 233 (PDQ - Late 1998) N/A*
- PowerBook G3 266 (PDQ - Late 1998) N/A*
- PowerBook G3 300 (PDQ - Late 1998) N/A*
- PowerBook G3 333 (Bronze KB/Lombard) PowerBook1,1
- PowerBook G3 400 (Bronze KB/Lombard) PowerBook1,1
- PowerBook G3 400 (Firewire/Pismo) PowerBook3,1
- PowerBook G3 500 (Firewire/Pismo) PowerBook3,1
- PowerBook G4 400 (Original - Ti) PowerBook3,2
- PowerBook G4 500 (Original - Ti) PowerBook3,2
- PowerBook G4 550 (Gigabit - Ti) PowerBook3,3
- PowerBook G4 667 (Gigabit - Ti) PowerBook3,3
- PowerBook G4 667 (DVI - Ti) PowerBook3,4
- PowerBook G4 800 (DVI - Ti) PowerBook3,4
- PowerBook G4 867 (Ti) PowerBook3,5
- PowerBook G4 1.0 (Ti) PowerBook3,5
- PowerBook G4 867 12" (Al) PowerBook6,1
- PowerBook G4 1.0 17" (Al) PowerBook5,1
- PowerBook G4 1.0 12" (DVI - Al) PowerBook6,2
- PowerBook G4 1.0 15" (FW800 - Al) PowerBook5,2
- PowerBook G4 1.25 15" (FW800 - Al) PowerBook5,2
- PowerBook G4 1.33 17" (Al) PowerBook5,3
- PowerBook G4 1.33 12" (Al) PowerBook6,4
- PowerBook G4 1.33 15" (Al) PowerBook5,4
- PowerBook G4 1.5 15" (Al) PowerBook5,4
- PowerBook G4 1.5 17" (Al) PowerBook5,5
- PowerBook G4 1.5 12" (Al) PowerBook6,8
- PowerBook G4 1.5 15" (SMS/BT2 - Al) PowerBook5,6
- PowerBook G4 1.67 15" (Al) PowerBook5,6
- PowerBook G4 1.67 17" (Al) PowerBook5,7
- PowerBook G4 1.67 15" (DLSD/HR - Al) PowerBook5,8
- PowerBook G4 1.67 17" (DLSD/HR - Al) PowerBook5,9
- Xserve G4/1.0 RackMac1,1
- Xserve G4/1.0 DP RackMac1,1
- Xserve G4/1.33 (Slot Load) RackMac1,2
- Xserve G4/1.33 DP (Slot Load) RackMac1,2
- Xserve G4/1.33 DP Cluster Node RackMac1,2
- Xserve G5/2.0 (PCI-X) RackMac3,1
- Xserve G5/2.0 DP (PCI-X) RackMac3,1
- Xserve G5/2.0 DP Cluster Node (PCI-X) RackMac3,1
- Xserve G5/2.3 DP (PCI-X) RackMac3,1
- Xserve G5/2.3 DP Cluster Node (PCI-X) RackMac3,1
- Xserve Xeon 2.0 "Quad Core" (Late 2006) Xserve1,1
- Xserve Xeon 2.66 "Quad Core" (Late 2006) Xserve1,1
- Xserve Xeon 3.0 "Quad Core" (Late 2006) Xserve1,1
- Xserve Xeon 2.8 "Quad Core" (Early 2008) Xserve2,1
- Xserve Xeon 2.8 "Eight Core" (Early 2008) Xserve2,1
- Xserve Xeon 3.0 "Eight Core" (Early 2008) Xserve2,1
- Xserve Xeon Nehalem 2.26 "Quad Core" Xserve3,1
- Xserve Xeon Nehalem 2.26 "Eight Core" Xserve3,1
- Xserve Xeon Nehalem 2.66 "Eight Core" Xserve3,1
- Xserve Xeon Nehalem 2.93 "Eight Core" Xserve3,1
 */

// EOF
