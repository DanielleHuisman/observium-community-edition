<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

switch ($device['os'])
{
  case 'linux':
  case 'endian':
  case 'openwrt':
  case 'ddwrt':
    list(,,$version) = explode (' ', $poll_device['sysDescr']);

    $kernel = $version;

    // Use agent DMI data if available
    if (isset($agent_data['dmi']))
    {
      if ($agent_data['dmi']['system-product-name'])
      {
        $hw = $agent_data['dmi']['system-product-name'];
      }
      if ($agent_data['dmi']['system-manufacturer'])
      {
        // Cleanup Vendor name
        $vendor = rewrite_vendor($agent_data['dmi']['system-manufacturer']);
      }

      // If these exclude lists grow any further we should move them to definitions...
      if (isset($agent_data['dmi']['system-serial-number'])
        && $agent_data['dmi']['system-serial-number'] != '............'
        && $agent_data['dmi']['system-serial-number'] != 'Not Specified'
        && $agent_data['dmi']['system-serial-number'] != '0123456789')
      {
        $serial = $agent_data['dmi']['system-serial-number'];
      }

      if (isset($agent_data['dmi']['chassis-asset-tag'])
        && $agent_data['dmi']['chassis-asset-tag'] != '....................'
        && strcasecmp($agent_data['dmi']['chassis-asset-tag'], 'To be filled by O.E.M.') != 0
        && $agent_data['dmi']['chassis-asset-tag'] != 'No Asset Tag')
      {
        $asset_tag = $agent_data['dmi']['chassis-asset-tag'];
      }
      elseif (isset($agent_data['dmi']['baseboard-asset-tag'])
        && $agent_data['dmi']['baseboard-asset-tag'] != '....................'
        && strcasecmp($agent_data['dmi']['baseboard-asset-tag'], 'To be filled by O.E.M.') != 0
        && $agent_data['dmi']['baseboard-asset-tag'] != 'Tag 12345')
      {
        $asset_tag = $agent_data['dmi']['baseboard-asset-tag'];
      }
    }

    if (is_array($entPhysical) && !$hw)
    {
      $hw = $entPhysical['entPhysicalDescr'];
      if (!empty($entPhysical['entPhysicalSerialNum']))
      {
        $serial = $entPhysical['entPhysicalSerialNum'];
      }
    }
    
    if (!$hardware)
    {
      $hardware = rewrite_unix_hardware($poll_device['sysDescr'], $hw);
    }
    break;

  case 'aix':
    list($hardware,,$os_detail,) = explode("\n", $poll_device['sysDescr']);
    if (preg_match('/: 0*(\d+\.)0*(\d+\.)0*(\d+\.)(\d+)/', $os_detail, $matches))
    {
      // Base Operating System Runtime AIX version: 05.03.0012.0001
      $version = $matches[1] . $matches[2] . $matches[3] . $matches[4];
    }

    $hardware_model = snmp_get($device, 'aixSeMachineType.0', '-Oqv', 'IBM-AIX-MIB');
    if ($hardware_model)
    {
      list(,$hardware_model) = explode(',', $hardware_model);

      $serial = snmp_get($device, 'aixSeSerialNumber.0', '-Oqv', 'IBM-AIX-MIB');
      list(,$serial) = explode(',', $serial);

      $hardware .= " ($hardware_model)";
    }
    break;

  case 'freebsd':
    preg_match('/FreeBSD ([\d\.]+-[\w\d-]+)/i', $poll_device['sysDescr'], $matches);
    $kernel = $matches[1];
    $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
    break;

  case 'dragonfly':
    list(,,$version,,,$features) = explode (' ', $poll_device['sysDescr']);
    $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
    break;

  case 'netbsd':
    list(,,$version,,,$features) = explode (' ', $poll_device['sysDescr']);
    $features = str_replace('(', '', $features);
    $features = str_replace(')', '', $features);
    $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
    break;

  case 'openbsd':
  case 'solaris':
  case 'opensolaris':
    list(,,$version,$features) = explode (' ', $poll_device['sysDescr']);
    $features = str_replace('(', '', $features);
    $features = str_replace(')', '', $features);
    $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
    break;

  case 'darwin':
    list(,,$version) = explode (' ', $poll_device['sysDescr']);
    $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
    break;

  case 'monowall':
  case 'pfsense':
    list(,,$version,,, $kernel) = explode(' ', $poll_device['sysDescr']);
    $distro = $device['os'];
    $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
    break;

  case 'freenas':
  case 'nas4free':
  case 'xigmanas':
    preg_match('/Software: FreeBSD ([\d\.]+-[\w\d-]+)/i', $poll_device['sysDescr'], $matches);
    $version = $matches[1];
    $hardware = rewrite_unix_hardware($poll_device['sysDescr']);
    break;

  case 'qnap':
    $hardware = $entPhysical['entPhysicalName'];
    $version  = $entPhysical['entPhysicalFirmwareRev'];
    $serial   = $entPhysical['entPhysicalSerialNum'];
    break;

  case 'ipso':
    // IPSO Bastion-1 6.2-GA039 releng 1 04.14.2010-225515 i386
    // IP530 rev 00, IPSO ruby.infinity-insurance.com 3.9-BUILD035 releng 1515 05.24.2005-013334 i386
    if (preg_match('/IPSO [^ ]+ ([^ ]+) /', $poll_device['sysDescr'], $matches))
    {
      $version = $matches[1];
    }

    $data = snmp_get_multi_oid($device, 'ipsoChassisMBType.0 ipsoChassisMBRevNumber.0', array(), 'NOKIA-IPSO-SYSTEM-MIB');
    if (isset($data[0]))
    {
      $hw = $data[0]['ipsoChassisMBType'] . ' rev ' . $data[0]['ipsoChassisMBRevNumber'];
    }
    $hardware = rewrite_unix_hardware($poll_device['sysDescr'], $hw);
    break;

  case 'sofaware':
    // EMBEDDED-NGX-MIB::swHardwareVersion.0 = "1.3T ADSL-A"
    // EMBEDDED-NGX-MIB::swHardwareType.0 = "SBox-200-B"
    // EMBEDDED-NGX-MIB::swLicenseProductName.0 = "Safe@Office 500, 25 nodes"
    // EMBEDDED-NGX-MIB::swFirmwareRunning.0 = "8.2.26x"
    $data = snmp_get_multi_oid($device, 'swHardwareVersion.0 swHardwareType.0 swLicenseProductName.0 swFirmwareRunning.0', array(), 'EMBEDDED-NGX-MIB');
    if (isset($data[0]))
    {
      list($hw) = explode(',', $data[0]['swLicenseProductName']);
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
    //        sg             = "Security Gateway"          exp: me-sg001-ch-gla-1
    //                sg * dns  = "DNS Server"                     exp: me-sg005-ch-gla-dns-1
    //                sg * mep = "Mobile Entry Point"         exp:  me-sg007-ch-gla-mep-1
    //
    //                bgp      = "BGP Router"                            exp: me-bgp001-ch-gla-1
    //                aut         = "Authentication Passport"    exp: me-aut001-ch-gla-1
    //                cvp       = "Client-VPN"                              exp: me-cvp001-ch-gla-1
    //                fw           = "Firewall"                                    exp: me-fw001-ch-gla-1
    //                mx         = "Email Gateway"                     exp: me-mx001-ch-gla-1
    //                prx         = "Internet Proxy"                        exp: me-prx001-ch-gla-1
    //                rpx         = "R-proxy (WAF)"                     exp: me-rpx002-ch-gla-1
    //                sw          = "Managed Switch"                  exp: me-sw012-ch-gla-1
    //
    //199:  device number. It's unique regarding device type
    // ch:    2 letter ISO code for country location
    // zur:   3 first letter from the location / city name
    // acc:   optional or sub-segment, like dns and mep
    // 1:        Device number in the same clusters for High availability
    if (preg_match('/^Linux\ +\w+\-(?<hw>(?<hw1>[a-z]+)0*\d+)\-\w+\-\w+\-(?:(?<hw2>\w+)\-)?\d+/', $poll_device['sysDescr'], $matches))
    {
      switch ($matches['hw1'])
      {
        case 'sg':
          if (isset($matches['hw2']) && $matches['hw2'] == 'dns')
          {
            $hw = 'DNS Server';
            $type = 'server';
          }
          else if (isset($matches['hw2']) && $matches['hw2'] == 'mep')
          {
            $hw = 'Mobile Entry Point';
            $type = 'network';
          } else {
            $hw = 'Security Gateway';
            $type = 'security';
          }
          break;

        case 'bgp':
          $hw = 'BGP Router';
          $type = 'network';
          break;

        case 'aut':
          $hw = 'Authentication Passport';
          $type = 'security';
          break;

        case 'cvp':
          $hw = 'Client-VPN';
          $type = 'security';
          break;

        case 'fw':
          $hw = 'Firewall';
          $type = 'firewall';
          break;

        case 'mx':
          $hw = 'Email Gateway';
          $type = 'server';
          break;

        case 'prx':
          $hw = 'Internet Proxy';
          $type = 'security';
          break;

        case 'rpx':
          $hw = 'R-proxy (WAF)';
          $type = 'security';
          break;

        case 'sw':
          $hw = 'Managed Switch';
          $type = 'network';
          break;

        case 'ids':
          $hw = 'Network Security Monitoring';
          $type = 'security';
          break;

        case 'apm':
          $hw = 'Application Performance Management';
          $type = 'server';
          break;

        case 'par':
          $hw = 'Partner Security Gateway';
          $type = 'security';
          break;
      }
    }

    if (empty($hw))
    {
      $hw = 'OSAG Hardware';
    }
    //$hardware = $hw . ' (' . strtoupper($matches['hw']) . ')';
    $hardware = rewrite_unix_hardware($poll_device['sysDescr'], $hw);
    break;
}

// Has 'distro' script data already been returned via the agent?
if (isset($agent_data['distro']) && isset($agent_data['distro']['SCRIPTVER']))
{
  $distro     = $agent_data['distro']['DISTRO'];
  // Older version of the script used DISTROVER, newer ones use VERSION :-(
  $distro_ver = (isset($agent_data['distro']['DISTROVER']) ? $agent_data['distro']['DISTROVER'] : $agent_data['distro']['VERSION']);
  $kernel     = $agent_data['distro']['KERNEL'];
  $arch       = $agent_data['distro']['ARCH'];
  $virt       = $agent_data['distro']['VIRT'];
} else {

  // Distro "extend" support
  //if (is_device_mib($device, 'NET-SNMP-EXTEND-MIB'))
  //{
  //  //NET-SNMP-EXTEND-MIB::nsExtendOutput1Line."distro" = STRING: Linux|4.4.0-77-generic|amd64|Ubuntu|16.04|kvm
  //  $os_data = snmp_get_oid($device, '.1.3.6.1.4.1.8072.1.3.2.3.1.1.6.100.105.115.116.114.111', 'NET-SNMP-EXTEND-MIB');
  //}
  if (!$os_data && is_device_mib($device, 'UCD-SNMP-MIB'))
  {
    $os_data = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.1.3.1.1.6.100.105.115.116.114.111', 'UCD-SNMP-MIB');

    if (!$os_data) // No "extend" support, try "exec" support
    {
      $os_data = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.1.101.1', 'UCD-SNMP-MIB');
    }
  }

  // Disregard data if we're just getting an error.
  if (!$os_data || strpos($os_data, '/usr/bin/distro') !== FALSE)
  {
    unset($os_data);
  }
  else if (str_contains($os_data, '|'))
  {
    // distro version less than 1.2: "Linux|3.2.0-4-amd64|amd64|Debian|7.5"
    // distro version 1.2 and above: "Linux|4.4.0-53-generic|amd64|Ubuntu|16.04|kvm"
    // distro version 2.0 and above: "Linux|4.4.0-116-generic|amd64|Ubuntu|16.04|kvm|"
    //                               "Linux|4.4.0|amd64|Ubuntu|16.04||openvz"
    list($osname, $kernel, $arch, $distro, $distro_ver, $virt, $cont) = explode('|', $os_data);
    if (empty($virt) && strlen($cont))
    {
      $virt = $cont;
    }
  } else {
    // Old distro, not supported now: "Ubuntu 12.04"
    list($distro, $distro_ver) = explode(' ', $os_data);
  }
}

// Hardware/vendor "extend" support
if (is_device_mib($device, 'UCD-SNMP-MIB'))
{
  $hw = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.2.3.1.1.8.104.97.114.100.119.97.114.101', 'UCD-SNMP-MIB');
  if (strlen($hw))
  {
    $hardware = rewrite_unix_hardware($poll_device['sysDescr'], $hw);
    $vendor = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.3.3.1.1.6.118.101.110.100.111.114', 'UCD-SNMP-MIB');
    if (!snmp_status())
    {
      // Alternative with manufacturer
      $vendor = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.3.3.1.1.12.109.97.110.117.102.97.99.116.117.114.101.114', 'UCD-SNMP-MIB');
    }
    $serial = snmp_get_oid($device, '.1.3.6.1.4.1.2021.7890.4.3.1.1.6.115.101.114.105.97.108', 'UCD-SNMP-MIB');
    if (str_contains($serial, 'denied') ||
        $serial == '0123456789' || $serial == '............' || $serial == 'Not Specified')
    {
      unset($serial);
    }
  }
}

// Use 'os' script virt output, if virt-what agent is not used
if (!isset($agent_data['virt']['what']) && isset($virt))
{
  $agent_data['virt']['what'] = $virt;
}

// Use agent virt-what data if available
if (isset($agent_data['virt']['what']))
{
  // We cycle through every line here, the previous one is overwritten.
  // This is OK, as virt-what prints general-to-specific order and we want most specific.
  foreach (explode("\n", $agent_data['virt']['what']) as $virtwhat)
  {
    if (isset($config['virt-what'][$virtwhat]))
    {
      //$hardware = $config['virt-what'][$virtwhat];
      $hardware = rewrite_unix_hardware($poll_device['sysDescr'], $config['virt-what'][$virtwhat]);
    }
  }
}

if (!$features && isset($distro))
{
  $features = "$distro $distro_ver";
}

unset($hw, $data);

// EOF
