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

// FIXME - split this up into separate modules. Need unified methods of calling wmic.

include_once($GLOBALS['config']['install_dir'] . "/includes/wmi.inc.php");
include_once($GLOBALS['config']['install_dir'] . "/includes/discovery/functions.inc.php");

$wmi = [];

echo("WMI Poller:\n");

$wmi_attribs = [];
foreach (get_entity_attribs('device', $device['device_id']) as $attrib => $entry) {
    if (str_starts_with($attrib, 'wmi_')) {
        $wmi_attribs[$attrib] = $entry;
    }
}

foreach ($GLOBALS['config']['wmi']['modules'] as $wmi_module => $wmi_module_status) {
    if (!array_key_exists("wmi_poll_" . $wmi_module, $wmi_attribs)) {
        $wmi_attribs['wmi_poll_' . $wmi_module] = $wmi_module_status;
    }
}

if ($wmi_attribs['wmi_override']) {
    $override = [
      "hostname" => $wmi_attribs['wmi_hostname'],
      "domain"   => $wmi_attribs['wmi_domain'],
      "username" => $wmi_attribs['wmi_username'],
      "password" => $wmi_attribs['wmi_password']
    ];
}

// Computer Name - This is set for WMI classes that need a non-FQDN hostname

$wql = "SELECT Name FROM Win32_ComputerSystem";
$wmi['computer_name'] = wmi_get($device, $wql, "Name");

if (is_null($wmi['computer_name'])) {
    print_error("WMI Error: Invalid security credentials or insufficient WMI security permissions");
    return;
}

// Operating System - Updates device info to an exact OS version installed

if ($wmi_attribs['wmi_poll_os']) {
    $wql = "SELECT * FROM Win32_OperatingSystem";
    //$wmi['os'] = wmi_parse(wmi_query($wql, $override), TRUE);
    $wmi['os'] = wmi_get($device, $wql);

    // Windows Uptime
    //   [LastBootUpTime]                            => string(25) "20220425175804.468750+180"
    //   [LocalDateTime]                             => string(25) "20220425204553.357000+180"

    if (isset($wmi['os']['LastBootUpTime']) &&
        preg_match(OBS_PATTERN_WINDOWSTIME, $wmi['os']['LastBootUpTime'], $boot_match) &&
        preg_match(OBS_PATTERN_WINDOWSTIME, $wmi['os']['LocalDateTime'], $local_match)) {
        $boot_time = int_add((int)$boot_match['year'] * 31536000,
                     int_add((int)$boot_match['month'] * 2628000,
                             (int)$boot_match['day'] * 86400 +
                             (int)$boot_match['hour'] * 3600 +
                             (int)$boot_match['min'] * 60 +
                             round($boot_match['sec'])));
        $local_time = int_add((int)$local_match['year'] * 31536000,
                      int_add((int)$local_match['month'] * 2628000,
                              (int)$local_match['day'] * 86400 +
                              (int)$local_match['hour'] * 3600 +
                              (int)$local_match['min'] * 60 +
                              round($local_match['sec'])));
        $wmi['uptime'] = int_sub($local_time, $boot_time);
        print_debug_vars($local_time);
        print_debug_vars($boot_time);
        print_debug_vars($wmi['uptime']);
        unset($boot_match, $boot_time, $local_match, $local_time);
    }
}

// Processors - Fixes "Unknown Processor Type" and "Intel" values

if ($wmi_attribs['wmi_poll_processors']) {
    $wql = "SELECT NumberOfLogicalProcessors,Name FROM Win32_Processor";
    $wmi['processors'] = wmi_get_all($device, $wql);

    if ($wmi['processors']) {
        include($GLOBALS['config']['install_dir'] . "/includes/polling/processors/wmi.inc.php");
    }
}

// Logical Disks

if ($wmi_attribs['wmi_poll_storage']) {
    $wql = "SELECT * FROM Win32_LogicalDisk WHERE FileSystem != 'CDFS'";
    $wmi['disk']['logical'] = wmi_get_all($device, $wql);

    /* Example
    [0] => array(
             [Access]                       => string(1) "0"
             [Availability]                 => string(1) "0"
             [BlockSize]                    => string(1) "0"
             [Caption]                      => string(2) "C:"
             [Compressed]                   => string(5) "False"
             [ConfigManagerErrorCode]       => string(1) "0"
             [ConfigManagerUserConfig]      => string(5) "False"
             [CreationClassName]            => string(17) "Win32_LogicalDisk"
             [Description]                  => string(16) "Local Fixed Disk"
             [DeviceID]                     => string(2) "C:"
             [DriveType]                    => string(1) "3"
             [ErrorCleared]                 => string(5) "False"
             [ErrorDescription]             => string(0) ""
             [ErrorMethodology]             => string(0) ""
             [FileSystem]                   => string(4) "NTFS"
             [FreeSpace]                    => string(11) "31064576000"
             [InstallDate]                  => string(0) ""
             [LastErrorCode]                => string(1) "0"
             [MaximumComponentLength]       => string(3) "255"
             [MediaType]                    => string(2) "12"
             [Name]                         => string(2) "C:"
             [NumberOfBlocks]               => string(1) "0"
             [PNPDeviceID]                  => string(0) ""
             [PowerManagementCapabilities]  => string(4) "NULL"
             [PowerManagementSupported]     => string(5) "False"
             [ProviderName]                 => string(0) ""
             [Purpose]                      => string(0) ""
             [QuotasDisabled]               => string(5) "False"
             [QuotasIncomplete]             => string(5) "False"
             [QuotasRebuilding]             => string(5) "False"
             [Size]                         => string(11) "64317550592"
             [Status]                       => string(0) ""
             [StatusInfo]                   => string(1) "0"
             [SupportsDiskQuotas]           => string(5) "False"
             [SupportsFileBasedCompression] => string(4) "True"
             [SystemCreationClassName]      => string(20) "Win32_ComputerSystem"
             [SystemName]                   => string(3) "WIN"
             [VolumeDirty]                  => string(5) "False"
             [VolumeName]                   => string(0) ""
             [VolumeSerialNumber]           => string(8) "D0122308"
           )
    [1] => array(
             [Access]                       => string(1) "1"
             [Availability]                 => string(1) "0"
             [BlockSize]                    => string(1) "0"
             [Caption]                      => string(2) "D:"
             [Compressed]                   => string(5) "False"
             [ConfigManagerErrorCode]       => string(1) "0"
             [ConfigManagerUserConfig]      => string(5) "False"
             [CreationClassName]            => string(17) "Win32_LogicalDisk"
             [Description]                  => string(11) "CD-ROM Disc"
             [DeviceID]                     => string(2) "D:"
             [DriveType]                    => string(1) "5"
             [ErrorCleared]                 => string(5) "False"
             [ErrorDescription]             => string(0) ""
             [ErrorMethodology]             => string(0) ""
             [FileSystem]                   => string(4) "CDFS"
             [FreeSpace]                    => string(1) "0"
             [InstallDate]                  => string(0) ""
             [LastErrorCode]                => string(1) "0"
             [MaximumComponentLength]       => string(3) "110"
             [MediaType]                    => string(2) "11"
             [Name]                         => string(2) "D:"
             [NumberOfBlocks]               => string(1) "0"
             [PNPDeviceID]                  => string(0) ""
             [PowerManagementCapabilities]  => string(4) "NULL"
             [PowerManagementSupported]     => string(5) "False"
             [ProviderName]                 => string(0) ""
             [Purpose]                      => string(0) ""
             [QuotasDisabled]               => string(5) "False"
             [QuotasIncomplete]             => string(5) "False"
             [QuotasRebuilding]             => string(5) "False"
             [Size]                         => string(9) "316628992"
             [Status]                       => string(0) ""
             [StatusInfo]                   => string(1) "0"
             [SupportsDiskQuotas]           => string(5) "False"
             [SupportsFileBasedCompression] => string(5) "False"
             [SystemCreationClassName]      => string(20) "Win32_ComputerSystem"
             [SystemName]                   => string(3) "WIN"
             [VolumeDirty]                  => string(5) "False"
             [VolumeName]                   => string(16) "virtio-win-0.1.1"
             [VolumeSerialNumber]           => string(8) "BEE6DB61"
           )
     */
}

// Microsoft Exchange

if ($wmi_attribs['wmi_poll_exchange']) {
    $wql = "SELECT Name FROM Win32_Service WHERE Name LIKE '%MSExchange%'";
    $wmi['exchange']['services'] = wmi_get($device, $wql);

    if ($wmi['exchange']['services']) {
        include($GLOBALS['config']['install_dir'] . "/includes/polling/applications/exchange.inc.php");
    }
}

// Microsoft SQL Server

if ($wmi_attribs['wmi_poll_mssql']) {
    $wql = "SELECT Name, ProcessId FROM Win32_Service WHERE Name LIKE '%MSSQL$%' OR Name = 'MSSQLSERVER'";
    $wmi['mssql']['services'] = wmi_get_all($device, $wql);

    if ($wmi['mssql']['services']) {
        include($GLOBALS['config']['install_dir'] . "/includes/polling/applications/mssql.inc.php");
    }
}

// Windows Services

if ($wmi_attribs['wmi_poll_winservices']) {

    $wql = "SELECT DisplayName,Name,StartMode,State FROM Win32_Service";

    // Build where statement from permitted services list and append to query if list is populated
    if (safe_count($config['wmi']['service_permit'])) {
        $wql .= " WHERE Name LIKE '";
        $wql .= implode("' OR Name LIKE '", $config['wmi']['service_permit']);
        $wql .= "'";
    }

    $wmi['winservices'] = wmi_get($device, $wql);

    if ($wmi['winservices']) {
        include($GLOBALS['config']['install_dir'] . "/includes/polling/applications/winservices.inc.php");
    }
}

// Do not reset $wmi var, it used in other modules
//unset($wmi);

// EOF
