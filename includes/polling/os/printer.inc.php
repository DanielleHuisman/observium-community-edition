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

// Printers hardware
$printer = snmp_get($device, 'hrDeviceDescr.1', '-OQv', 'HOST-RESOURCES-MIB');

if ($printer)
{
  list($hardware) = explode(';', $printer);
} else {
  if ($device['os'] == 'jetdirect')
  {
    // ...7.0 = STRING: "MFG:Hewlett-Packard;CMD:PJL,MLC,BIDI-ECP,PCL,POSTSCRIPT,PCLXL;MDL:hp LaserJet 1320 series;CLS:PRINTER;DES:Hewlett-Packard LaserJet 1320 series;MEM:9MB;COMMENT:RES=1200x1;"
    //                  "MFG:HP;MDL:Officejet Pro K5400;CMD:MLC,PCL,PML,DW-PCL,DESKJET,DYN;1284.4DL:4d,4e,1;CLS:PRINTER;DES:C8185A;SN:MY82E680JG;S:038000ec840010210068eb800008fb8000041c8003844c8004445c8004d46c8003b;Z:0102,05000009000009029cc1016a81017a21025e41,0600,070000000000000"
    $jdinfo = snmp_get($device, '1.3.6.1.4.1.11.2.3.9.1.1.7.0', '-OQv');
    preg_match('/(?:MDL|MODEL|DESCRIPTION):([^;]+);/', $jdinfo, $matches);
    $hardware = $matches[1];
  }
}

// OS version
if (preg_match('/^(?:Samsung )+(?<hardware>[CMKSX][\w\-]+)[^;]*;(?: V|OS )(?<version>[\d\.]+).+?;S/N (?<serial>\w+)/', $poll_device['sysDescr'], $matches))
{
  //Samsung X7600GX; V11.01.05.15_06-24-2015;Engine V1.00.77 06-21-2015;NIC ;S/N 082MB1EG400002T
  //Samsung SL-M4075FR; V4.00.01.12 Jul-26-2013;Engine V1.00.00;NIC V4.01.13 JUL-26-2013;S/N 070ABJFDA0009XR
  //Samsung SCX-8821; V11.12.03.07_01-23-2015;Engine V1.00.66 11-26-2014L;NIC ;S/N ZDW5B1DG300002P
  //Samsung SCX-483x 5x3x Series; V2.00.01.061 NOV-28-2011;Engine 1.00.23;NIC V4.01.07 05-12-2011;S/N Z5RUBAKB600003J
  //Samsung Samsung SCX-472x Series; V3.00.01.18 NOV-07-2012;Engine V0.04.04 10-30-2012;NIC V6.01.00(SCX-472x Series);S/N Z9MRBJDD300038Y
  //Samsung Samsung ML-2950 Series; V3.00.01.07 AUG-12-2011;Engine V1.01.04 07-01-2011;NIC V6.01.00(ML-2950 Series);S/N Z7K4BKBBC00039R
  //Samsung Samsung M267x 287x Series; V3.00.01.27 OCT-01-2014;Engine V1.00.10 06-10-2014;NIC V6.01.00;S/N ZD0VBJCFA000HKY
  //Samsung Samsung K2200 Series; V3.07.01.24 MAY-16-2014;Engine V1.00.19 05-15-2014;NIC V6.01.00;S/N ZD9DB1DF8000F5Z
  //Samsung Samsung CLX-3300 Series; V3.00.02.07 APR-10-2014;Engine V1.00.17 2014-04-10;NIC V6.01.00;S/N Z8BUB8GF4E007RH
  //Samsung Samsung CLP-360 Series; V3.00.01.06 May-16-2012;Engine V1.00.04 2012-05-14;NIC V6.01.00(CLP-360 Series);S/N Z757BJBC60008KP
  //Samsung Samsung C48x Series; V3.00.01.07 JUL-24-2015;Engine V1.00.01 2015-04-27;NIC V6.01.01;S/N 08H7B8GG7E0022M
  //Samsung Samsung C410 Series; V3.00.02.07     APR-10-2014;Engine V1.00.17 2014-04-10;NIC V6.01.01;S/N ZEVQB8GF6E00WLW
  //Samsung ML-8x00;OS 1.60 Jul 08 2009;Engine 1.00:20;NIC V2.03.06(ML-8x00) 11-18-2009;S/N BE58BPAB500027..
  //Samsung ML-8850 8950 Series; V2.02.01.07 Jun-10-2011;Engine ;NIC V4.01.02 09-28-2010;S/N Z5SNB8AG400034D
  //Samsung M5370LX; V11.01.06.03_07-16-2014;Engine V1.00.05_07-03-2014;NIC ;S/N 07A2BJFF70000TX
  //Samsung K4350LX; V11.01.08.12_07-09-2015;Engine V2.00.12 07-08-2015_v1.53;NIC ;S/N 28R0B1AG80001EM
  //Samsung CLX-9821; V11.12.03.06_01-23-2015;Engine V1.00.64 11-27-2014L;NIC ;S/N ZDVXB1CF500001Y
  //Samsung CLP-680 Series; V4.00.01.41 Feb-15-2013;Engine V1.00.20;NIC V4.01.11 02-17-2013;S/N Z7FQBJED500078B
  //Samsung ML-2850 Series OS 1.03.00.16 01-22-2008;Engine 1.01.06;NIC V4.01.02(ML-285x) 09-13-2007;S/N 4F66BKEQ410592R
  if (!$hardware)
  {
    $hardware = $matches['hardware'];
  }
  if (!$serial)
  {
    $serial = $matches['serial'];
  }

  $version = $matches['version'];
}
else if ($device['os'] == 'okilan')
{
  //OKI OkiLAN 8100e Rev.02.73 10/100BASE Ethernet PrintServer: Attached to C3200n Rev.N2.14 : (C)2004 Oki Data Corporation
  //OKI OkiLAN 6600g Rev.1.0 10/100BASE Ethernet PrintServer: Attached to C930 Rev.1.0 : Copyright (c) 2006 Oki Data Corporation. All rights reserved.
  //OKI OkiLAN B63e Rev.1.0.34 10/100BASE EthernetPrintServer Attached to B6300 Rev.3.4.8
  //OkiLAN 6130
  if (preg_match('/OkiLAN (?<hardware>\w+) Rev\.N*(?<version>[\d]+\.[\d\.]+)/', $poll_device['sysDescr'], $matches))
  {
    if (!$hardware)
    {
      $hardware = $matches['hardware'];
    }

    $version = $matches['version'];
  }
}

if ($hardware)
{
  // Strip off useless brand fields
  $hardware = str_ireplace(array('HP ', 'Hewlett-Packard ', ' Series', 'Samsung ', 'Epson ', 'Brother ', 'OKI '), '', $hardware);
  $hardware = ucfirst($hardware);
}

// EOF
