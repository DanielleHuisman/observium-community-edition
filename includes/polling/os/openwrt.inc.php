<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) Adam Armstrong
 *
 */

// Kernel/Version
if (str_starts_with($poll_device['sysDescr'], 'Linux')) {
    $version = explode(' ', $poll_device['sysDescr'])[2];
    $kernel  = $version;

    // https://openwrt.org/about/history
    // this dumb way for detect version....
    // FIXME. I think it incorrect in some cases, but work for latest versions.
    $openwrt_versions = [
        '6.6' => [
            63 => '24.10.0-rc2',
        ],
        '5.15' => [
            167 => '23.05.5',
            162 => '23.05.4',
            150 => '23.05.3',
            137 => '23.05.2',
            134 => '23.05.0',
        ],
        '5.10' => [
            221 => '22.03.7',
            201 => '22.03.6',
            134 => '22.03.5',
            //187 => '19.07.4',
        ],
        '4.14' => [
            212 => '19.07.6',
            199 => '19.07.5',
            187 => '19.07.4',
            172 => '19.07.3',
            169 => '19.07.2', // or 18.06.8
            164 => '19.07.1',
            100 => '19.07.0', // ?

            60 => '18.06.1',
        ],
        '4.9'  => [
            212 => '18.06.8',
            209 => '18.06.7',
            199 => '18.06.6',
            186 => '18.06.5',
            154 => '18.06.4',
            153 => '18.06.3',
            122 => '18.06.2',
            117 => '18.06.1',
            0   => '18.06.0', // ?
        ],
    ];

    $kernel_vers = explode('.', $kernel);
    $kernel_base = $kernel_vers[0] . '.' . $kernel_vers[1];
    if (isset($openwrt_versions[$kernel_base])) {
        //$version = $openwrt_versions[$kernel_base]['base'];
        //unset($openwrt_versions[$kernel_base]['base']);
        foreach ($openwrt_versions[$kernel_base] as $k => $v) {
            if ($kernel_vers[2] >= $k) {
                $version = $v;
                break;
            }
        }
    }

}
$hardware = rewrite_unix_hardware($poll_device['sysDescr']);

// EOF
