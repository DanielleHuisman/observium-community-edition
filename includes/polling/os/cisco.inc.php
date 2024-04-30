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

$sysDescr = preg_replace('/\s+/', ' ', $poll_device['sysDescr']); // Replace all spaces and newline to single space
// Generic IOS/IOS-XE/IES/IOS-XR sysDescr
if (preg_match('/^Cisco IOS Software(?: \[\S+?\])?, .+? Software \((?:[^\-]+\-(?<features>\w+)\-\w|\w+_IOSXE(?:\-\w+)?)\),.+?Version (?<version>[^,\s]+)/', $sysDescr, $matches)) {
    // Cisco IOS Software, Catalyst 4500 L3 Switch Software (cat4500e-ENTSERVICESK9-M), Version 15.2(1)E3, RELEASE SOFTWARE (fc1) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2014 by Cisco Systems, Inc. Compiled Mon 05-May-14 07:56 b
    // Cisco IOS Software, IOS-XE Software (PPC_LINUX_IOSD-IPBASEK9-M), Version 15.2(2)S, RELEASE SOFTWARE (fc1) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2012 by Cisco Systems, Inc. Compiled Mon 26-Mar-12 15:23 by mcpre
    // Cisco IOS Software, IES Software (IES-LANBASEK9-M), Version 12.2(52)SE1, RELEASE SOFTWARE (fc1) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2010 by Cisco Systems, Inc. Compiled Tue 09-Feb-10 03:17 by prod_rel_team
    // Cisco IOS Software [Gibraltar], Catalyst L3 Switch Software (CAT9K_IOSXE), Version 16.11.1, RELEASE SOFTWARE (fc3) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2019 by Cisco Systems, Inc. Compiled Thu 28-Mar-19 09:42 by mcpre
    // Cisco IOS Software [Fuji], Catalyst L3 Switch Software (CAT9K_IOSXE), Version 16.8.1a, RELEASE SOFTWARE (fc1) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2018 by Cisco Systems, Inc. Compiled Tue 03-Apr-18 18:49 by mcpre
    // Cisco IOS Software [Amsterdam], C9800 Software (C9800_IOSXE-K9), Version 17.3.5a, RELEASE SOFTWARE (fc2) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2022 by Cisco Systems, Inc. Compiled Mon 28-Feb-22 19:09 by mcpre

    $features = $matches['features'];
    $version  = $matches['version'];
} elseif (preg_match('/^Cisco Internetwork Operating System Software IOS \(tm\) [^ ]+ Software \([^\-]+\-(?<features>\w+)\-\w\),.+?Version (?<version>[^, ]+)/', $sysDescr, $matches)) {
    // Cisco Internetwork Operating System Software IOS (tm) 7200 Software (UBR7200-IK8SU2-M), Version 12.3(17b)BC8, RELEASE SOFTWARE (fc1) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2007 by cisco Systems, Inc. Compiled Fri 29-Ju
    // Cisco Internetwork Operating System Software IOS (tm) C1700 Software (C1700-Y-M), Version 12.2(4)YA2, EARLY DEPLOYMENT RELEASE SOFTWARE (fc1) Synched to technology version 12.2(5.4)T TAC Support: http://www.cisco.com/tac Copyright (c) 1986-2002 by ci

    $features = $matches['features'];
    $version  = $matches['version'];
} elseif (preg_match('/^Cisco IOS XR Software \(Cisco (?<hardware>[^\)]+)\), Version (?<version>[^\[]+)\[(?<features>[^\]]+)\]/', $sysDescr, $matches)) {
    // Cisco IOS XR Software (Cisco 12816/PRP), Version 4.3.2[Default] Copyright (c) 2014 by Cisco Systems, Inc.
    // Cisco IOS XR Software (Cisco 12404/PRP), Version 3.6.0[00] Copyright (c) 2007 by Cisco Systems, Inc.
    // Cisco IOS XR Software (Cisco ASR9K Series), Version 5.1.2[Default] Copyright (c) 2014 by Cisco Systems, Inc.

    //$hardware = $matches['hardware'];
    $features = $matches['features'];
    $version  = $matches['version'];
} elseif (preg_match('/^Cisco NX-OS(?:\(tm\))? (?<hw1>\S+?)( (?<hardware>[\w\-]+) chassis)?, Software \((?<hw2>.+?)\),.+?Version (?<version>[^, ]+)/', $sysDescr, $matches)) {
    // Cisco NX-OS(tm) n7000, Software (n7000-s2-dk9), Version 6.2(8b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc.
    // Cisco NX-OS(tm) n1000v, Software (n1000v-dk9), Version 5.2(1)SV3(1.2), RELEASE SOFTWARE Copyright (c) 2002-2011 by Cisco Systems, Inc. Device Manager Version nms.sro not found,  Compiled 11/11/2014 15:00:00
    // Cisco NX-OS(tm) n5000, Software (n5000-uk9), Version 6.0(2)N2(7), RELEASE SOFTWARE Copyright (c) 2002-2012 by Cisco Systems, Inc. Device Manager Version 6.2(1),  Compiled 4/28/2015 5:00:00
    // Cisco NX-OS(tm) n7000, Software (n7000-s2-dk9), Version 6.2(8a), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc. Compiled 5/15/2014 20:00:00
    // Cisco NX-OS(tm) n3000, Software (n3000-uk9), Version 6.0(2)U2(2), RELEASE SOFTWARE Copyright (c) 2002-2012 by Cisco Systems, Inc. Device Manager Version nms.sro not found, Compiled 2/12/2014 8:00:00
    // Cisco NX-OS(tm) ucs, Software (ucs-6100-k9-system), Version 5.0(3)N2(3.13a), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc. Compiled 4/25/2017 7:00:00
    // Cisco NX-OS(tm) aci, Software (aci-n9000-system), Version 12.0(1q), RELEASE SOFTWARE Copyright (c) 2002-2015 by Cisco Systems, Inc. Compiled 2016/08/18 14:20:16
    // Cisco NX-OS(tm) nxos.9.2.3.bin, Software (nxos), Version 9.2(3), RELEASE SOFTWARE Copyright (c) 2002-2019 by Cisco Systems, Inc. Compiled 2/17/2019 4:00:00
    // Cisco NX-OS n6000, Software (n6000-uk9), Version 7.3(2)N1(1), RELEASE SOFTWARE Copyright (c) 2002-2012, 2016-2017 by Cisco Systems, Inc. Device Manager Version 6.0(2)N1(1),Compiled 5/12/2017 23:00:00
    // Cisco NX-OS(tm) Nexus9000 C93180YC-EX chassis, Software (NXOS 32-bit), Version 9.3(9), RELEASE SOFTWARE Copyright (c) 2002-2022 by Cisco Systems, Inc. Compiled 2/4/2022 7:00:00

    if (str_contains($matches['hw2'], ' ')) {
        // NXOS 32-bit -> 32-bit
        $features = explode(' ', $matches['hw2'], 2)[1];
    } elseif (str_contains($matches['hw2'], '-')) {
        // n7000-s2-dk9 -> s2-dk9
        $features = explode('-', $matches['hw2'], 2)[1];
    }
    $version = $matches['version'];
} elseif (preg_match('/Software \(\w+-(?<features>\w+)-\w\),.+?Version (?<version>[^, ]+),(?:[\w ]+)? RELEASE SOFTWARE/', $sysDescr, $matches)) {
    // C800 Software (C800-UNIVERSALK9-M), Version 15.2(2)T2, RELEASE SOFTWARE (fc1) Technical Support: http://www.cisco.com/techsupport Compiled Thu 02-Aug-12 02:09 by prod_rel_team
    // Cisco IOS Software, Catalyst 4500 L3 Switch Software (cat4500e-ENTSERVICESK9-M), Version 15.2(1)E3, RELEASE SOFTWARE (fc1) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2014 by Cisco Systems, Inc. Compiled Mon 05-May-14 07:56 b
    // Cisco IOS Software, ASR900 Software (PPC_LINUX_IOSD-UNIVERSALK9_NPE-M), Version 15.5(1)S, RELEASE SOFTWARE (fc5) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2014 by Cisco Systems, Inc. Compiled Thu 20-Nov-14 18:16 by mcpre
    // Cisco IOS Software, IOS-XE Software (PPC_LINUX_IOSD-IPBASEK9-M), Version 15.2(2)S, RELEASE SOFTWARE (fc1) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2012 by Cisco Systems, Inc. Compiled Mon 26-Mar-12 15:23 by mcpre
    // Cisco IOS Software, IES Software (IES-LANBASEK9-M), Version 12.2(52)SE1, RELEASE SOFTWARE (fc1) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2010 by Cisco Systems, Inc. Compiled Tue 09-Feb-10 03:17 by prod_rel_team

    // Cisco Internetwork Operating System Software IOS (tm) 7200 Software (UBR7200-IK8SU2-M), Version 12.3(17b)BC8, RELEASE SOFTWARE (fc1) Technical Support: http://www.cisco.com/techsupport Copyright (c) 1986-2007 by cisco Systems, Inc. Compiled Fri 29-Ju
    // Cisco Internetwork Operating System Software IOS (tm) C1700 Software (C1700-Y-M), Version 12.2(4)YA2, EARLY DEPLOYMENT RELEASE SOFTWARE (fc1) Synched to technology version 12.2(5.4)T TAC Support: http://www.cisco.com/tac Copyright (c) 1986-2002 by ci

    $features = $matches['features'];
    $version  = $matches['version'];
} elseif (preg_match('/Software( \([\w\- ]+\))?, Version (?<version>\d[\w\.\(\)]+)/', $sysDescr, $matches)) {
    // Cisco Systems WS-C6509-E Cisco Catalyst Operating System Software, Version 8.4(3) Copyright (c) 1995-2005 by Cisco Systems
    // Cisco Systems, Inc. WS-C2948 Cisco Catalyst Operating System Software, Version 4.5(9) Copyright (c) 1995-2000 by Cisco Systems, Inc.
    // Cisco Systems, Inc. WS-C2948G-GE-TX Cisco Catalyst Operating System Software, Version 8.4(5)GLX Copyright (c) 1995-2005 by Cisco Systems, Inc.
    // Cisco Systems, Inc. WS-C4912 Cisco Catalyst Operating System Software, Version 7.2(2) Copyright (c) 1995-2002 by Cisco Systems, Inc.

    //$features = $matches['features'];
    $version = $matches['version'];
}

// All other Cisco devices
if (is_array($entPhysical)) {
    print_debug_vars($entPhysical);
    if (is_module_enabled($device, 'inventory', 'discovery')) {
        if ($entPhysical['entPhysicalClass'] === 'stack') {
            // If it's stacked device try get chassis instead
            $chassis = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalClass` = ? AND `entPhysicalContainedIn` = ? AND `deleted` IS NULL', [$device['device_id'], 'chassis', '1']);
            if ($chassis['entPhysicalModelName']) {
                $entPhysical = $chassis;
            }
        } elseif ($entPhysical['entPhysicalModelName'] === 'MIDPLANE' || safe_empty($entPhysical['entPhysicalModelName'])) {
            // F.u. Cisco.. for some platforms (4948/4900M) they store correct model and version not in chassis
            $hw_module = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalClass` = ? AND `entPhysicalContainedIn` = ? AND `deleted` IS NULL', [$device['device_id'], 'module', '2']);
            if ($hw_module['entPhysicalModelName']) {
                $entPhysical = $hw_module;
            }
        } elseif (safe_empty($entPhysical['entPhysicalSoftwareRev'])) {
            // 720X, try again get correct serial/version
            $hw_module = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalClass` = ? AND `entPhysicalContainedIn` = ? AND `entPhysicalSerialNum` != ? AND `deleted` IS NULL', [$device['device_id'], 'module', '1', '']);
            if ($hw_module['entPhysicalSoftwareRev']) {
                if ($device['os'] === 'iosxe') {
                    // For IOS-XE fix only version
                    $entPhysical['entPhysicalSoftwareRev'] = $hw_module['entPhysicalSoftwareRev'];
                } else {
                    $entPhysical = $hw_module;
                }
            }
        }
    }

    if ($entPhysical['entPhysicalClass'] === 'chassis' || in_array($entPhysical['entPhysicalContainedIn'], ['0', '1', '2'])) {
        if ((safe_empty($version) || $device['os'] === 'iosxe') && !empty($entPhysical['entPhysicalSoftwareRev'])) {
            $version = $entPhysical['entPhysicalSoftwareRev'];
        }
        if (!empty($entPhysical['entPhysicalModelName'])) {
            if (preg_match('/ (rev|dev)/', $entPhysical['entPhysicalModelName']) || // entPhysicalModelName = "73-7036-1 rev 80 dev 0"
                preg_match('/^\.+$/', $entPhysical['entPhysicalModelName']))        // entPhysicalModelName = "..."
            {
                // F.u. Cisco again.. again..
                // i.e.: entPhysicalModelName = "73-7036-1 rev 80 dev 0",
                //       entPhysicalDescr     = "12404/PRP chassis, Hw Serial#: TBA07510208, Hw Revision: 0x00"
            } else {
                $hardware = $entPhysical['entPhysicalModelName'];
            }
        } else {
            $hardware = str_replace(' chassis', '', $entPhysical['entPhysicalName']);
        }
        if (!empty($entPhysical['entPhysicalSerialNum'])) {
            $serial = $entPhysical['entPhysicalSerialNum'];
        }
    }
}

// NOTE. In CISCO-PRODUCTS-MIB uses weird hardware names (entPhysicalName uses human readable names)
// Examples:
// sysObjectID [.1.3.6.1.4.1.9.1.658]: cisco7604
// entPhysicalModelName:               CISCO7604
// sysObjectID [.1.3.6.1.4.1.9.1.927]: cat296048TCS
// entPhysicalModelName:               WS-C2960-48TC-S
// sysObjectID [.1.3.6.1.4.1.9.1.1208]: cat29xxStack
// entPhysicalModelName:               WS-C2960S-F48TS-L
if (empty($hardware) && $poll_device['sysObjectID']) {
    // Try translate instead duplicate get sysObjectID
    $hardware = snmp_translate($poll_device['sysObjectID'], 'SNMPv2-MIB:CISCO-PRODUCTS-MIB:CISCO-ENTITY-VENDORTYPE-OID-MIB');
}
if (empty($hardware)) {
    // If translate false, try get sysObjectID again
    $hardware = snmp_get_oid($device, 'sysObjectID.0', 'SNMPv2-MIB:CISCO-PRODUCTS-MIB:CISCO-ENTITY-VENDORTYPE-OID-MIB');
}

// Some cisco specific hardware rewrites
if ($hardware) {
    $cisco_replace = [
      '/^[Cc]isco\s*(\d)/'                      => '\1',            // Cisco 7604   -> 7604
      '/^cisco([a-z])/i'                        => '\1',            // ciscoASR9010 -> ASR9010
      '/^cat(?:alyst)?(\d{4}[CGX]?)(\w+)(.)$/i' => 'WS-C\1-\2-\3',  // cat296048TCS -> WS-C2960-48TC-S
      '/^ACE\-?(\w+)\-?K9/'                     => 'ACE \1',        // ACE4710K9    -> ACE 4710
      '/^ACE\-?(\w+)/'                          => 'ACE \1'         // ACE4710      -> ACE 4710
    ];
    $hardware      = array_preg_replace($cisco_replace, $hardware);
}

// Additional checks for IOS devices
$ios_type = '';
if ($device['os'] === 'ios' &&
    (str_icontains_array($hardware, 'AIRAP') || str_starts($hardware, 'AIR-'))) {
    $ios_type = 'wireless';
} else {
    // Set type to a predefined type for the OS if it's not already set
    $ios_type = get_model_param($device, 'type', $poll_device['sysObjectID']);
}

if (!empty($ios_type)) {
    $type = $ios_type;
}

unset($chassis, $model, $ios_type);

// EOF
