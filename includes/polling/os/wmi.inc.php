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

/*
array(
  [BootDevice]                                => string(23) "\Device\HarddiskVolume1"
  [BuildNumber]                               => string(4) "7601"
  [BuildType]                                 => string(19) "Multiprocessor Free"
  [Caption]                                   => string(42) "Microsoft Windows Server 2008 R2 Standard "
  [CodeSet]                                   => string(4) "1251"
  [CountryCode]                               => string(1) "7"
  [CreationClassName]                         => string(21) "Win32_OperatingSystem"
  [CSCreationClassName]                       => string(20) "Win32_ComputerSystem"
  [CSDVersion]                                => string(14) "Service Pack 1"
  [CSName]                                    => string(3) "WIN"
  [CurrentTimeZone]                           => string(3) "180"
  [DataExecutionPrevention_32BitApplications] => string(4) "True"
  [DataExecutionPrevention_Available]         => string(4) "True"
  [DataExecutionPrevention_Drivers]           => string(4) "True"
  [DataExecutionPrevention_SupportPolicy]     => string(1) "3"
  [Debug]                                     => string(5) "False"
  [Description]                               => string(0) ""
  [Distributed]                               => string(5) "False"
  [EncryptionLevel]                           => string(3) "256"
  [ForegroundApplicationBoost]                => string(1) "2"
  [FreePhysicalMemory]                        => string(7) "1397624"
  [FreeSpaceInPagingFiles]                    => string(7) "1520184"
  [FreeVirtualMemory]                         => string(7) "2997356"
  [InstallDate]                               => string(25) "20140219172159.000000+180"
  [LargeSystemCache]                          => string(1) "0"
  [LastBootUpTime]                            => string(25) "20200423155639.484375+180"
  [LocalDateTime]                             => string(25) "20200624182535.565000+180"
  [Locale]                                    => string(4) "0419"
  [Manufacturer]                              => string(21) "Microsoft Corporation"
  [MaxNumberOfProcesses]                      => string(10) "4294967295"
  [MaxProcessMemorySize]                      => string(10) "8589934464"
  [MUILanguages]                              => string(7) "(en-US)"
  [Name]                                      => string(82) "Microsoft Windows Server 2008 R2 Standard |C:\Windows|\Device\Harddisk0\Partition2"
  [NumberOfLicensedUsers]                     => string(1) "0"
  [NumberOfProcesses]                         => string(2) "49"
  [NumberOfUsers]                             => string(1) "0"
  [OperatingSystemSKU]                        => string(1) "7"
  [Organization]                              => string(0) ""
  [OSArchitecture]                            => string(6) "64-bit"
  [OSLanguage]                                => string(4) "1033"
  [OSProductSuite]                            => string(3) "272"
  [OSType]                                    => string(2) "18"
  [OtherTypeDescription]                      => string(0) ""
  [PAEEnabled]                                => string(5) "False"
  [PlusProductID]                             => string(0) ""
  [PlusVersionNumber]                         => string(0) ""
  [Primary]                                   => string(4) "True"
  [ProductType]                               => string(1) "2"
  [RegisteredUser]                            => string(12) "Windows User"
  [SerialNumber]                              => string(23) "55041-552-2303947-84677"
  [ServicePackMajorVersion]                   => string(1) "1"
  [ServicePackMinorVersion]                   => string(1) "0"
  [SizeStoredInPagingFiles]                   => string(7) "2096616"
  [Status]                                    => string(2) "OK"
  [SuiteMask]                                 => string(3) "272"
  [SystemDevice]                              => string(23) "\Device\HarddiskVolume2"
  [SystemDirectory]                           => string(19) "C:\Windows\system32"
  [SystemDrive]                               => string(2) "C:"
  [TotalSwapSpaceSize]                        => string(1) "0"
  [TotalVirtualMemorySize]                    => string(7) "4193232"
  [TotalVisibleMemorySize]                    => string(7) "2096616"
  [Version]                                   => string(8) "6.1.7601"
  [WindowsDirectory]                          => string(10) "C:\Windows"
)
 */
$os = preg_replace("/(\(R\)|®|«|Â)/u", '', $wmi['os']['Caption']);
$os = str_replace('Microsoft Windows ', '', $os);
if ($wmi['os']['CSDVersion']) {
    $os .= ' ' . str_replace('Service Pack ', 'SP', $wmi['os']['CSDVersion']);
}
$os .= ' (' . $wmi['os']['Version'] . ')';

$version = $os;

// EOF
